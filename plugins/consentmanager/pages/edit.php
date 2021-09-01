<?php
include "../../../include/db.php";
include_once "../../../include/authenticate.php";
include "../include/file_functions.php";

$ref=getvalescaped("ref","");if (!is_numeric($ref)) {$ref="new";} // force to either a number or "new"
$resource=getvalescaped("resource","",true);
$file_path=get_consent_file_path($ref);

# Check access
if ($resource!="")
    {
    $edit_access=get_edit_access($resource);
    if (!$edit_access) {exit("Access denied");} # Should never arrive at this page without edit access
    }
else
    {
    # Editing all consents via Manage Consents - admin only
    if (!checkperm("a")) {exit("Access denied");} 
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
    $expires="'" . getvalescaped("expires_year","") . "-" . getvalescaped("expires_month","") . "-" . getvalescaped("expires_day","") . "'";
    

    # Construct usage
    $consent_usage="";
    if (isset($_POST["consent_usage"])) {$consent_usage=escape_check(join(", ",$_POST["consent_usage"]));}

    # No expiry date ticked? Insert null
    if (getval("no_expiry_date","")=="yes")
        {
        $expires="null";
        }

   
    if ($ref=="new")
        {
        # New record 
        sql_query("insert into consent (name,email,telephone,consent_usage,notes,expires) values ('" . getvalescaped("name","") . "', '" . getvalescaped("email","") . "', '" . getvalescaped("telephone","") . "', '" . $consent_usage . "', '" . getvalescaped("notes","") . "', $expires)");	
        $ref=sql_insert_id();
        $file_path=get_consent_file_path($ref); // get updated path

        # Add to all the selected resources
        if (getvalescaped("resources","")!="")
            {
            $resources=explode(", ",getvalescaped("resources",""));
            foreach ($resources as $r)
                {
                $r=trim($r);
                if (is_numeric($r))
                    {
                    sql_query("insert into resource_consent(resource,consent) values ('" . escape_check($r) . "','" . escape_check($ref) . "')");
                    resource_log($r,"","",$lang["new_consent"] . " " . $ref);
                    }
                }
            }
        }
    else
        {
        # Existing record	
        sql_query("update consent set name='" . getvalescaped("name","") . "',email='" . getvalescaped("email","") . "', telephone='" . getvalescaped("telephone","") . "',consent_usage='" . $consent_usage . "',notes='" . getvalescaped("notes","") . "',expires=$expires where ref='$ref'");

        # Add all the selected resources
        sql_query("delete from resource_consent where consent='$ref'");
        $resources=explode(",",getvalescaped("resources",""));

        if (getvalescaped("resources","")!="")
            {
            foreach ($resources as $r)
                {
                $r=trim($r);
                if (is_numeric($r))
                    {
                    sql_query("insert into resource_consent(resource,consent) values ('" . escape_check($r) . "','" . escape_check($ref) . "')");
                    resource_log($r,"","",$lang["new_consent"] . " " . $ref);
                    }
                }
            }
        }

    # Handle file upload
    if (isset($_FILES["file"]) && $_FILES["file"]["tmp_name"]!="")
        {
        move_uploaded_file($_FILES["file"]["tmp_name"],$file_path);  
        sql_query("update consent set file='" . escape_check($_FILES["file"]["name"]) . "' where ref='$ref'");
        }

    # Handle file clear
    if (getval("clear_file","")!="")
        {
        if (file_exists($file_path)) {unlink($file_path);}  
        sql_query("update consent set file='' where ref='$ref'");
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
    $consent=sql_query("select name,email,telephone,consent_usage,notes,expires,file from consent where ref='$ref'");
    if (count($consent)==0) {exit("Consent not found.");}
    $consent=$consent[0];
    $resources=sql_array("select distinct resource value from resource_consent where consent='$ref' order by resource");
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
<?php
$s=trim_array(explode(",",$consent["consent_usage"]));
$allchecked=true;
foreach ($consent_usage_mediums as $medium)
    {
    ?>
    <input type="checkbox" class="consent_usage" name="consent_usage[]" value="<?php echo $medium ?>" <?php if (in_array($medium, $s)) { ?>checked<?php } else {$allchecked=false;} ?>>&nbsp;<?php echo lang_or_i18n_get_translated($medium, "consent_usage-") ?>
    &nbsp;
    &nbsp;
    &nbsp;
    <?php
    }
?>

    <!-- Option to tick all mediums -->
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox"
onChange="jQuery('.consent_usage').attr('checked',this.checked);" <?php if ($allchecked) { ?>checked<?php } ?>
    /><?php echo $lang["selectall"] ?>

<div class="clearerleft"> </div></div>




<div class="Question"><label><?php echo $lang["fieldtitle-expiry_date"]?></label>


    <select id="expires_day" name="expires_day" class="SearchWidth" style="width:98px;">
      <?php
      for ($n=1;$n<=31;$n++)
        {
        $m=str_pad($n,2,"0",STR_PAD_LEFT);
        ?><option <?php if ($n==substr($consent["expires"],8,2)) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
        }
      ?>
    </select>

    <select id="expires_month" name="expires_month" class="SearchWidth" style="width:98px;">
      <?php
      for ($n=1;$n<=12;$n++)
        {
        $m=str_pad($n,2,"0",STR_PAD_LEFT);
        ?><option <?php if ($n==substr($consent["expires"],5,2)) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$n-1]?></option><?php
        }
      ?>
    </select>
    
    <select id="expires_year" name="expires_year" class="SearchWidth" style="width:98px;">
      <?php
      $y=date("Y")+30;
      for ($n=$minyear;$n<=$y;$n++)
        {
        ?><option <?php if ($n==substr($consent["expires"],0,4)) { ?>selected<?php } ?>><?php echo $n?></option><?php
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
<label for="buttons"> </label>			
<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../../include/footer.php";
?>
