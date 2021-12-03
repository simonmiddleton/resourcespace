<?php
include "../../../include/db.php";

include_once "../../../include/authenticate.php";
if (!checkperm("a")) {exit("Access denied");} # Should never arrive at this page without admin access


$collection=trim(str_replace("!collection","",getvalescaped("collection","")));
$unlink=(getval("unlink","")!=""); # Unlink mode

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
    $resources=get_collection_resources($collection);
    $ref=getvalescaped("ref", 0, true);
    if($ref <= 0)
        {
        error_alert($lang["selectconsent"], false);
        exit();
        }
    $url_params = array(
        'ref'        => $ref,
        'search'     => getval('search',''),
        'order_by'   => getval('order_by',''),
        'collection' => getval('collection',''),
        'offset'     => getval('offset',0),
        'restypes'   => getval('restypes',''),
        'archive'    => getval('archive','')
    );
    $redirect_url = generateURL($baseurl_short . "/plugins/consentmanager/pages/edit.php",$url_params);

    foreach ($resources as $resource)
        {
        // Always remove any existing relationship
        sql_query("delete from resource_consent where consent='$ref' and resource='$resource'");

        // Add link?
        if (!$unlink) {sql_query("insert into resource_consent (resource,consent) values ('$resource','$ref')");}

        // Log
        resource_log($resource,"","",$lang[($unlink?"un":"") . "linkconsent"] . " " . $ref);
        }

	redirect($redirect_url);
	}
		
include "../../../include/header.php";
?>
<div class="BasicsBox">

<h1><?php echo ($unlink?$lang["unlinkconsent"]:$lang["linkconsent"]) ?></h1>

<form method="post" action="<?php echo $baseurl_short?>plugins/consentmanager/pages/batch.php" onSubmit="return CentralSpacePost(this,true);">
<input type=hidden name="submitted" value="true">
<input type=hidden name="collection" value="<?php echo $collection?>">
<input type=hidden name="unlink" value="<?php echo ($unlink?"true":"") ?>">
<?php generateFormToken("consentmanager_batch"); ?>

<div class="Question"><label><?php echo $lang["consent_id"]?></label>
<select name="ref"><option value=""><?php echo $lang["select"] ?></option>
<?php $consents=sql_query("select ref,name from consent order by ref"); foreach ($consents as $consent) { ?>
<option value="<?php echo $consent["ref"] ?>"><?php echo $consent["ref"] ?> - <?php echo $consent["name"]; ?></option>
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
