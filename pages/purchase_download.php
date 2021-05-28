<?php
include "../include/db.php";

include "../include/authenticate.php"; 

$collection=getvalescaped("collection","",true);

# Reload collection frame to show new (empty) basket
refresh_collection_frame($usercollection);


include "../include/header.php";

?>

<div class="BasicsBox"> 
<h1><?php echo $lang["downloadpurchaseitems"]?></h1>
<?php

$resources=do_search("!collection" . $collection);
$valid=true;
foreach ($resources as $resource)
		{
		if($resource["purchase_complete"]!=1) {$valid=false;}
		}
		

if (!$valid)
	{
	# ------------------- Notification not yet received. Show a please wait message. -----------------------
	?>
    <p><?php echo $lang["waitingforpaymentauthorisation"] ?></p>
	   
	<form method="get" action="<?php echo $baseurl_short?>pages/purchase_download.php">
	<input type="submit" name="reload" value="&nbsp;&nbsp;&nbsp;<?php echo $lang["reload"] ?>&nbsp;&nbsp;&nbsp;">
	<input type="hidden" name="collection" value="<?php echo $usercollection ?>">
	</form>
	<?php
	}
else
	{
	# ------------------- Show download links ----------------------------------------------------------------
	?>
	<div class="RecordPanel">
	<p><strong><?php echo $lang["downloadpurchaseitemsnow"] ?></strong></p>
	<div class="RecordDownloadSpace">

	<table class="InfoTable">
	<?php 
	foreach ($resources as $resource)
		{ ?>
		<tr class="DownloadDBlend">
		<?php
		$size=$resource["purchase_size"];
		$title=get_data_by_field($resource["ref"],$view_title_field);
		?>
		<td><h2><?php echo i18n_get_translated($title) ?></h2></td>
		<td class="DownloadButton">
		<?php if ($terms_download || $save_as) { ?>
			<a href="<?php echo $baseurl?>/pages/terms.php?ref=<?php echo urlencode($resource["ref"])?>&url=<?php echo urlencode("pages/download_progress.php?ref=".$resource["ref"]
											    ."&ext=&size=".$size) ?>"><?php echo $lang["action-download"]?></a>
		<?php } 

		elseif ($download_usage) { ?>
			<a href="<?php echo $baseurl?>/pages/download_usage.php?ref=<?php echo urlencode($resource["ref"])."&ext=&size=".$size."&k=".urlencode($k)?>">
			<?php echo $lang["action-download"]?></a>
		<?php }

		else { ?>
			<a href="<?php echo $baseurl?>/pages/download.php?ref=<?php echo urlencode($resource["ref"]) ?>&size=<?php echo $size ?>"><?php echo $lang["action-download"]?></a>
		<?php } ?>
		</td>
		</tr>
	<?php } ?>
	</table>
	
	</div>
	</div>
	<?php
	}
?>
</div>

<?php
include "../include/footer.php";
?>
