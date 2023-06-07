<?php
include "../../../include/db.php";
include_once "../../../include/authenticate.php";
include "../include/file_functions.php";

$ref=getval("ref","");if (!is_numeric($ref)) {$ref="new";} // force to either a number or "new"
$resource=getval("resource","",true);
$file_path=get_consent_file_path($ref);

# Check access
if ($resource!="")
    {
    $edit_access=get_edit_access($resource);
    if (!$edit_access && !checkperm("cm")) {exit("Access denied");} # Should never arrive at this page without edit access
    }
else
    {
    # Editing all consents via Manage Consents - admin only
    if (!checkperm("a") && !checkperm("cm")) {exit("Access denied");} 
    }

$url_params = array(
    'search'     => getval('search',''),
    'order_by'   => getval('order_by',''),
    'collection' => getval('collection',''),
    'offset'     => getval('offset',0),
    'restypes'   => getval('restypes',''),
    'archive'    => getval('archive','')
);
    # Added from resource page?
    if ($resource!="") 
        {
        $url_params['ref'] = $resource;
        $redirect_url = generateURL($baseurl_short . "pages/view.php",$url_params);
        }
    else
        {
        # Added from Manage Consents
        $redirect_url = generateURL($baseurl_short . "plugins/consentmanager/pages/list.php",$url_params);
        }
if (getval("submitted","")!="")
    {
    # Save consent data
    
    # Construct expiry date
    $expires= getval("expires_year","") . "-" . getval("expires_month","") . "-" . getval("expires_day","");
    

    # Construct usage
    $consent_usage="";
    if (isset($_POST["consent_usage"])) {$consent_usage=join(", ",$_POST["consent_usage"]);}

    # No expiry date ticked? Insert null
    if (getval("no_expiry_date","")=="yes")
        {
        $expires=null;
        }

   
    if ($ref=="new")
        {
        # New record 
        ps_query(
            "insert into consent (name,email,telephone,consent_usage,notes,expires) values ( ?, ?, ?, ?, ?, ?)",
            [
                's', getval('name', ''),
                's', getval('email', ''),
                's', getval('telephone', ''),
                's', $consent_usage,
                's', getval('notes', ''),
                's', $expires
            ]
            
        );	
        $ref=sql_insert_id();
        $file_path=get_consent_file_path($ref); // get updated path

        # Add to all the selected resources
        if (getval("resources","")!="")
            {
            $resources=explode(", ",getval("resources",""));
            foreach ($resources as $r)
                {
                $r=trim($r);
                if (is_numeric($r))
                    {
                    ps_query("insert into resource_consent(resource,consent) values (?, ?)", ['i', $r, 'i', $ref]);
                    resource_log($r,"","",$lang["new_consent"] . " " . $ref);
                    }
                }
            }
        }
    else
        {
        # Existing record	
        ps_query(
            "update consent set name= ?,email= ?, telephone= ?,consent_usage= ?,notes= ?,expires= ? where ref= ?",
            [
                's', getval('name', ''),
                's', getval('email', ''),
                's', getval('telephone', ''),
                's', $consent_usage,
                's', getval('notes', ''),
                's', $expires,
                'i', $ref
            ]
        );

        # Add all the selected resources
        ps_query("delete from resource_consent where consent= ?",['i', $ref]);
        $resources=explode(",",getval("resources",""));

        if (getval("resources","")!="")
            {
            foreach ($resources as $r)
                {
                $r=trim($r);
                if (is_numeric($r))
                    {
                    ps_query("insert into resource_consent(resource,consent) values (?, ?)", ['i', $r, 'i', $ref]);
                    resource_log($r,"","",$lang["new_consent"] . " " . $ref);
                    }
                }
            }
        }

    # Handle file upload
    global $banned_extensions;
    if (isset($_FILES["file"]) && $_FILES["file"]["tmp_name"]!="")
        {
        # Work out the extension
        $uploadfileparts=explode(".",$_FILES["file"]["name"]);
        $uploadfileextension=trim($uploadfileparts[count($uploadfileparts)-1]);
        $uploadfileextension=strtolower($uploadfileextension);

        if (in_array($uploadfileextension,$banned_extensions))
            {
            $error_extension = str_replace("%%FILETYPE%%",$uploadfileextension,$lang["error_upload_invalid_file"]);
            error_alert($error_extension, true);
            exit();
            }
        else
            {
            move_uploaded_file($_FILES["file"]["tmp_name"],$file_path);  
            ps_query("UPDATE consent set file= ? where ref= ?", ['s', $_FILES["file"]["name"], 'i', $ref]);
            }
        }

    # Handle file clear
    if (getval("clear_file","")!="")
        {
        if (file_exists($file_path)) {unlink($file_path);}  
        ps_query("UPDATE consent set file='' where ref= ?", ['i', $ref]);
        }

    redirect($redirect_url);
    }


# Fetch consent data
if ($ref=="new")
    {
    # Set default values for the creation of a new record.
    $consent=array(
        "name"=>"",		
        "email"=>"",
        "telephone"=>"",
        "consent_usage"=>"",
        "notes"=>"",
        "expires"=>"",
        "file"=>""
        );
    if ($resource=="") {$resources=array();} else {$resources=array($resource);}
    }
else
    {
    $consent=ps_query("select name,email,telephone,consent_usage,notes,expires,file from consent where ref= ?", ['i', $ref]);
    if (count($consent)==0) {exit("Consent not found.");}
    $consent=$consent[0];
    $resources=ps_array("select distinct resource value from resource_consent where consent= ? order by resource", ['i', $ref]);
    }
        
include "../../../include/header.php";
?>
<div class="BasicsBox">

<?php if ($resource!="") { ?>
<p><a href="<?php echo $redirect_url ?>"  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
<?php } else { ?>
<p><a href="<?php echo $redirect_url ?>  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a></p>
<?php } ?>


<h1><?php echo ($ref=="new"?$lang["new_consent"]:$lang["edit_consent"]) ?></h1>

<form method="post" action="<?php echo $baseurl_short?>plugins/consentmanager/pages/edit.php" enctype="multipart/form-data">
<input type=hidden name="submitted" value="true">
<input type=hidden name="ref" value="<?php echo $ref?>">
<input type=hidden name="resource" value="<?php echo $resource?>">
<?php generateFormToken("consentmanager_edit"); ?>

<div class="Question"><label><?php echo $lang["consent_id"]?></label><div class="Fixed"><?php echo ($ref=="new"?$lang["consentmanager_new"]:htmlspecialchars($ref))?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["name"]?></label><input type=text class="stdwidth" name="name" id="name" value="<?php echo htmlspecialchars($consent["name"])?>" />
<div class="clearerleft"> </div></div>


<div class="Question"><label><?php echo $lang["email"]?></label><input type=text class="stdwidth" name="email" id="email" value="<?php echo htmlspecialchars($consent["email"])?>" />
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["telephone"]?></label><input type=text class="stdwidth" name="telephone" id="telephone" value="<?php echo htmlspecialchars($consent["telephone"])?>" />
<div class="clearerleft"> </div></div>


<div class="Question"><label><?php echo $lang["indicateusagemedium"]?></label>
<table>
<?php
$s=trim_array(explode(",",$consent["consent_usage"]));
$allchecked=true;
foreach ($consent_usage_mediums as $medium)
    {
    ?>
    <tr><td>
    <input type="checkbox" class="consent_usage" name="consent_usage[]" value="<?php echo $medium ?>" <?php if (in_array($medium, $s)) { ?>checked<?php } else {$allchecked=false;} ?>>&nbsp;<?php echo lang_or_i18n_get_translated($medium, "consent_usage-") ?>
    </td></tr>
    <?php
    }
?>
    <tr><td>
    <!-- Option to tick all mediums -->
    <input type="checkbox"
onChange="jQuery('.consent_usage').attr('checked',this.checked);" <?php if ($allchecked) { ?>checked<?php } ?>
    /><?php echo $lang["selectall"] ?>
    </td></tr>
</table>

<div class="clearerleft"> </div></div>




<div class="Question"><label><?php echo $lang["fieldtitle-expiry_date"]?></label>


    <select id="expires_day" name="expires_day" class="SearchWidth" style="width:98px;">
      <?php
      for ($n=1;$n<=31;$n++)
        {
        $m=str_pad($n,2,"0",STR_PAD_LEFT);
        ?><option <?php if ($n==substr((string) $consent["expires"],8,2)) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
        }
      ?>
    </select>

    <select id="expires_month" name="expires_month" class="SearchWidth" style="width:98px;">
      <?php
      for ($n=1;$n<=12;$n++)
        {
        $m=str_pad($n,2,"0",STR_PAD_LEFT);
        ?><option <?php if ($n==substr((string) $consent["expires"],5,2)) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$n-1]?></option><?php
        }
      ?>
    </select>
    
    <select id="expires_year" name="expires_year" class="SearchWidth" style="width:98px;">
      <?php
      $y=date("Y")+30;
      for ($n=$minyear;$n<=$y;$n++)
        {
        ?><option <?php if ($n==substr((string) $consent["expires"],0,4)) { ?>selected<?php } ?>><?php echo $n?></option><?php
        }
      ?>
    </select>

    <!-- Option for no expiry date -->
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="no_expiry_date" value="yes" id="no_expiry" <?php if ($consent["expires"]=="") { ?>checked<?php } ?>
    onChange="jQuery('#expires_day, #expires_month, #expires_year').attr('disabled',this.checked);"
    /><?php echo $lang["no_expiry_date"] ?>
    <?php if ($consent["expires"]=="") { ?><script>jQuery('#expires_day, #expires_month, #expires_year').attr('disabled',true);</script><?php } ?>

<div class="clearerleft"> </div></div>


<div class="Question">
        <label for="resources"><?php echo $lang["linkedresources"]?></label>
        <textarea class="stdwidth" rows="3" name="resources" id="resources"><?php echo join(", ",$resources)?></textarea>
        <div class="clearerleft"> </div>
    </div>

    <div class="Question">
        <label for="notes"><?php echo $lang["notes"]?></label>
        <textarea class="stdwidth" rows="5" name="notes" id="notes"><?php echo htmlspecialchars($consent["notes"]) ?></textarea>
        <div class="clearerleft"> </div>
    </div>

    <div class="Question" id="file">
        <label for="file"><?php echo $lang["file"] ?></label>
        <?php
        
        if($consent["file"]!="")
			{
			?>
            <span><i class="fa fa-file"></i> <a href="download.php?resource=<?php echo $resource ?>&ref=<?php echo $ref ?>"><?php echo $consent['file']; ?></a></span>
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="clear_file" value="<?php echo $lang["clearbutton"]; ?>" onclick="return confirm('<?php echo $lang["confirmdeleteconsentfile"] ?>');">
            <?php
			}
        else
            {
            ?>
            <input type="file" name="file" style="width:300px">
            <input type="submit" name="upload_file" value="<?php echo $lang['upload']; ?>">
            <?php
            }
            ?>
        <div class="clearerleft"></div>
    </div>



<div class="QuestionSubmit">		
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../../include/footer.php";
?>
