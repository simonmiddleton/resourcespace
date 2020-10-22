<?php
include "../../../include/db.php";

include_once "../../../include/authenticate.php";


# Check if it's necessary to upgrade the database structure
include dirname(__FILE__) . "/../upgrade/upgrade.php";

$ref=getvalescaped("ref","");
$resource=getvalescaped("resource","");

# Check access
if ($resource!="")
    {
    $edit_access=get_edit_access($resource);
    if (!$edit_access) {exit("Access denied");} # Should never arrive at this page without edit access
    }
else
    {
    # Editing all licenses via Manage Licenses - admin only
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
        # Added from Manage Licenses
        $redirect_url = generateURL($baseurl_short . "plugins/licensemanager/pages/list.php",$url_params);
        }
if (getval("submitted","")!="")
    {
    # Save license data
    
    # Construct expiry date
    $expires="'" . getvalescaped("expires_year","") . "-" . getvalescaped("expires_month","") . "-" . getvalescaped("expires_day","") . "'";
    
    # No expiry date ticked? Insert null
    if (getval("no_expiry_date","")=="yes")
        {
        $expires="null";
        }

    # Construct usage
    $license_usage="";
    if (isset($_POST["license_usage"])) {$license_usage=escape_check(join(", ",$_POST["license_usage"]));}
    
    if ($ref=="new")
        {
        # New record 
        sql_query("insert into license (outbound,holder,license_usage,description,expires) values ('" . getvalescaped("outbound","") . "', '" . getvalescaped("holder","") . "', '$license_usage', '" . getvalescaped("description","") . "', $expires)");	
        $ref=sql_insert_id();

        # Add to all the selected resources
        if (getvalescaped("resources","")!="")
            {
            $resources=explode(", ",getvalescaped("resources",""));
            foreach ($resources as $r)
                {
                $r=trim($r);
                if (is_numeric($r))
                    {
                    sql_query("insert into resource_license(resource,license) values ('" . escape_check($r) . "','" . escape_check($ref) . "')");
                    resource_log($r,"","",$lang["new_license"] . " " . $ref);
                    }
                }
            }
        }
    else
        {
        # Existing record	
        sql_query("update license set outbound='" . getvalescaped("outbound","") . "',holder='" . getvalescaped("holder","") . "', license_usage='$license_usage',description='" . getvalescaped("description","") . "',expires=$expires where ref='$ref'");

        # Add all the selected resources
        sql_query("delete from resource_license where license='$ref'");
        $resources=explode(",",getvalescaped("resources",""));

        if (getvalescaped("resources","")!="")
            {
            foreach ($resources as $r)
                {
                $r=trim($r);
                if (is_numeric($r))
                    {
                    sql_query("insert into resource_license(resource,license) values ('" . escape_check($r) . "','" . escape_check($ref) . "')");
                    resource_log($r,"","",$lang["new_license"] . " " . $ref);
                    }
                }
            }
        }
    
    redirect($redirect_url);
    }


# Fetch license data
if ($ref=="new")
    {
    # Set default values for the creation of a new record.
    $license=array(
        "outbound"=>1,
        "holder"=>"",		
        "license_usage"=>"",
        "description"=>"",
        "expires"=>date("Y-m-d")
        );
    if ($resource=="") {$resources=array();} else {$resources=array($resource);}
    }
else
    {
    $license=sql_query("select * from license where ref='$ref'");
    if (count($license)==0) {exit("License not found.");}
    $license=$license[0];
    $resources=sql_array("select distinct resource value from resource_license where license='$ref' order by resource");
    }
        
include "../../../include/header.php";
?>
<div class="BasicsBox">

<?php if ($resource!="") { ?>
<p><a href="<?php echo $redirect_url ?>"  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
<?php } else { ?>
<p><a href="<?php echo $redirect_url ?>  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a></p>
<?php } ?>


<h1><?php echo ($ref=="new"?$lang["new_license"]:$lang["edit_license"]) ?></h1>

<form method="post" action="<?php echo $baseurl_short?>plugins/licensemanager/pages/edit.php" onSubmit="return CentralSpacePost(this,true);">
<input type=hidden name="submitted" value="true">
<input type=hidden name="ref" value="<?php echo $ref?>">
<input type=hidden name="resource" value="<?php echo $resource?>">
<?php generateFormToken("licensemanager_edit"); ?>

<div class="Question"><label><?php echo $lang["license_id"]?></label><div class="Fixed"><?php echo ($ref=="new"?$lang["licensemanager_new"]:htmlspecialchars($ref))?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["type"]?></label>
<div class="Fixed">
<input type=radio name="outbound" id="outbound_1" value="1" <?php if ($license["outbound"]==1) { ?>checked<?php } ?> /> <strong><?php echo $lang["outbound"] ?></strong> <?php echo $lang["outbound_license_description"] ?><br>
<input type=radio name="outbound" id="outbound_0" value="0" <?php if ($license["outbound"]==0) { ?>checked<?php } ?> /> <strong><?php echo $lang["inbound"] ?></strong> <?php echo $lang["inbound_license_description"] ?>
</div>
<div class="clearerleft"> </div></div>


<div class="Question"><label><?php echo $lang["licensor_licensee"]?></label><input type=text class="stdwidth" name="holder" id="holder" value="<?php echo htmlspecialchars($license["holder"])?>" />
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["indicateusagemedium"]?></label>
<?php
$s=trim_array(explode(",",$license["license_usage"]));
$allchecked=true;
foreach ($license_usage_mediums as $medium)
    {
    ?>
    <input type="checkbox" class="license_usage" name="license_usage[]" value="<?php echo $medium ?>" <?php if (in_array($medium, $s)) { ?>checked<?php } else {$allchecked=false;} ?>>&nbsp;<?php echo lang_or_i18n_get_translated($medium, "license_usage-") ?>
    &nbsp;
    &nbsp;
    &nbsp;
    <?php
    }
?>

    <!-- Option to tick all mediums -->
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox"
onChange="jQuery('.license_usage').attr('checked',this.checked);" <?php if ($allchecked) { ?>checked<?php } ?>
    /><?php echo $lang["selectall"] ?>

<div class="clearerleft"> </div></div>



<div class="Question"><label><?php echo $lang["description"]?></label><textarea rows="4" class="stdwidth" name="description" id="description"><?php echo htmlspecialchars($license["description"]) ?></textarea>
<div class="clearerleft"> </div></div>


<div class="Question"><label><?php echo $lang["fieldtitle-expiry_date"]?></label>


    <select id="expires_day" name="expires_day" class="SearchWidth" style="width:98px;">
      <?php
      for ($n=1;$n<=31;$n++)
        {
        $m=str_pad($n,2,"0",STR_PAD_LEFT);
        ?><option <?php if ($n==substr($license["expires"],8,2)) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
        }
      ?>
    </select>

    <select id="expires_month" name="expires_month" class="SearchWidth" style="width:98px;">
      <?php
      for ($n=1;$n<=12;$n++)
        {
        $m=str_pad($n,2,"0",STR_PAD_LEFT);
        ?><option <?php if ($n==substr($license["expires"],5,2)) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$n-1]?></option><?php
        }
      ?>
    </select>
    
    <select id="expires_year" name="expires_year" class="SearchWidth" style="width:98px;">
      <?php
      $y=date("Y")+30;
      for ($n=$minyear;$n<=$y;$n++)
        {
        ?><option <?php if ($n==substr($license["expires"],0,4)) { ?>selected<?php } ?>><?php echo $n?></option><?php
        }
      ?>
    </select>

    <!-- Option for no expiry date -->
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="no_expiry_date" value="yes" id="no_expiry" <?php if ($license["expires"]=="") { ?>checked<?php } ?>
    onChange="jQuery('#expires_day, #expires_month, #expires_year').attr('disabled',this.checked);"
    /><?php echo $lang["no_expiry_date"] ?>
    <?php if ($license["expires"]=="") { ?><script>jQuery('#expires_day, #expires_month, #expires_year').attr('disabled',true);</script><?php } ?>

<div class="clearerleft"> </div></div>


<div class="Question">
        <label for="resources"><?php echo $lang["linkedresources"]?></label>
        <textarea class="stdwidth" rows="3" name="resources" id="resources"><?php echo join(", ",$resources)?></textarea>
        <div class="clearerleft"> </div>
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
