<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include "../../../include/resource_functions.php";
include "../../../include/collections_functions.php";
if (!checkperm("a")) {exit("Access denied");} # Should never arrive at this page without admin access


$collection=trim(str_replace("!collection","",getvalescaped("collection","")));
$unlink=(getval("unlink","")!=""); # Unlink mode

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
    $resources=get_collection_resources($collection);
    $ref=getvalescaped("ref","");

    foreach ($resources as $resource)
        {
        // Always remove any existing relationship
        sql_query("delete from resource_license where license='$ref' and resource='$resource'");

        // Add link?
        if (!$unlink) {sql_query("insert into resource_license (resource,license) values ('$resource','$ref')");}

        // Log
        resource_log($resource,"","",$lang[($unlink?"un":"") . "linklicense"] . " " . $ref);
        }

	redirect("plugins/licensemanager/pages/edit.php?ref=" . $ref);
	}
		
include "../../../include/header.php";
?>
<div class="BasicsBox">

<h1><?php echo ($unlink?$lang["unlinklicense"]:$lang["linklicense"]) ?></h1>

<form method="post" action="<?php echo $baseurl_short?>plugins/licensemanager/pages/batch.php" onSubmit="return CentralSpacePost(this,true);">
<input type=hidden name="submitted" value="true">
<input type=hidden name="collection" value="<?php echo $collection?>">
<input type=hidden name="unlink" value="<?php echo ($unlink?"true":"") ?>">
<?php generateFormToken("licensemanager_batch"); ?>

<div class="Question"><label><?php echo $lang["license_id"]?></label>
<select name="ref"><option value=""><?php echo $lang["select"] ?></option>
<?php $licenses=sql_query("select ref,description,holder from license order by ref"); foreach ($licenses as $license) { ?>
<option value="<?php echo $license["ref"] ?>"><?php echo $license["ref"] ?> - <?php echo $license["description"] . " / " . $license["holder"]; ?></option>
<?php } ?>
</select>
<div class="clearerleft"> </div></div>

<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name="batch" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../../include/footer.php";
?>
