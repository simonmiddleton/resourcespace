<?php
$rs_root = dirname(dirname(dirname(__DIR__)));
include_once "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/authenticate.php";

$id      = getval("id", "");
$preview = getval("preview", "");

include_once "{$rs_root}/include/header.php";
?>



<div class="RecordBox">
<div class="RecordPanel RecordPanelLarge">

<div class="RecordHeader">

<div class="backtoresults">
		<a href="#" onclick="ModalClose();" class="closeLink fa fa-times" title="<?php echo $lang["close"] ?>"></a>
</div>

<h1><?php echo getval("description","") ?></h1>
</div>


<div class="RecordResource">
    <div id="previewimagewrapper">
                <img id="previewimage" class="Picture" src="<?php echo $preview ?>" alt="Full screen preview" galleryimg="no">
    </div>
      
<div class="RecordDownload" id="RecordDownload">
<div class="RecordDownloadSpace">
<h2 id="resourcetools"><?php echo $lang["resourcetools"] ?></h2>

    <table cellpadding="0" cellspacing="0" id="ResourceDownloadOptions">
    <tbody><tr>
        <td><?php echo $lang["fileinformation"] ?></td>
        <td class="textcenter"><?php echo $lang["options"] ?></td>
    </tr>
    <tr class="DownloadDBlend" id="DownloadBox0">
        <td class="DownloadFileName"><h2><?php echo $lang["image_banks_shutterstock_id"] . " " . getval("id","") ?></h2></td>
        <td class="DownloadButton">
        <a id="downloadlink" target="_blank" href="https://www.shutterstock.com/image-photo/<?php echo getval("id","") ?>"><?php echo $lang["download"] ?></a>
    	</td>
    </tr>
    </tbody></table>

</div>
<div class="clearerleft"> </div>
</div>
              
        



</div>
<?php
include_once "{$rs_root}/include/footer.php";

