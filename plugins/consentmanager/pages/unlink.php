<?php
include "../../../include/db.php";

include_once "../../../include/authenticate.php";


$ref=getval("ref","");
$resource=getval("resource","");

# Check access
$edit_access=get_edit_access($resource);
if (!$edit_access && !checkperm("cm")) {exit("Access denied");} # Should never arrive at this page without edit access

$url_params = array(
    'ref'        => $resource,
    'search'     => getval('search',''),
    'order_by'   => getval('order_by',''),
    'collection' => getval('collection',''),
    'offset'     => getval('offset',0),
    'restypes'   => getval('restypes',''),
    'archive'    => getval('archive','')
);
$redirect_url = generateURL($baseurl_short . "/pages/view.php",$url_params);

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
    ps_query("delete from resource_consent where consent= ? and resource= ?", ['i', $ref, 'i', $resource]);
	
	resource_log($resource,"","",$lang["unlink_consent"] . " " . $ref);
	
	redirect($redirect_url);
	}
		
include "../../../include/header.php";
?>
<div class="BasicsBox">
<p><a href="<?php echo $redirect_url ?>"  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>

<h1><?php echo $lang["unlink_consent"] ?></h1>

<form method="post" action="<?php echo $baseurl_short?>plugins/consentmanager/pages/unlink.php" onSubmit="return CentralSpacePost(this,true);">
<input type=hidden name="submitted" value="true">
<input type=hidden name="ref" value="<?php echo $ref?>">
<input type=hidden name="resource" value="<?php echo $resource?>">
<?php generateFormToken("consentmanager_unlink"); ?>
<div class="Question"><label><?php echo $lang["resourceid"]?></label><div class="Fixed"><?php echo htmlspecialchars($resource)?></div>
<div class="clearerleft"> </div></div>

<div class="Question"><label><?php echo $lang["consent_id"]?></label><div class="Fixed"><?php echo htmlspecialchars($ref)?></div>
<div class="clearerleft"> </div></div>


<div class="QuestionSubmit">		
<input name="unlink" type="submit" value="&nbsp;&nbsp;<?php echo $lang["action-unlink"]?>&nbsp;&nbsp;" />
</div>
</form>
</div>

<?php		
include "../../../include/footer.php";
?>
