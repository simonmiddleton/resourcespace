<?php
include "../include/db.php";

include "../include/authenticate.php";

$search=getvalescaped("search","");
$offset=getvalescaped("offset",0,true);
$order_by=getvalescaped("order_by","");
$archive=getvalescaped("archive","",true);
$restypes=getvalescaped("restypes","");
$starsearch=getvalescaped("starsearch","");
if (strpos($search,"!")!==false) {$restypes="";}

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);

$results=do_search(getval("search",""),getvalescaped("restypes",""),"relevance",getval("archive",""),-1,"desc",false,$starsearch,false,true,getvalescaped("daylimit",""));
$disk_usage=$results[0]["total_disk_usage"];
$count=$results[0]["total_resources"];

include ("../include/header.php");

?>
<div class="BasicsBox">
<h1><?php echo $lang["searchitemsdiskusage"] ?></h1>
<?php
$intro=text("introtext");
if ($intro!="") { ?><p><?php echo $intro ?></p><?php } 
?>
<div class="Question">
<label><?php echo $lang["matchingresourceslabel"] ?></label>
<div class="Fixed"><?php echo number_format($count)  ?></div>
<div class="clearerleft"></div>
</div>

<div class="Question">
<label><?php echo $lang["diskusage"] ?></label>
<div class="Fixed"><strong> <?php echo formatfilesize($disk_usage) ?></strong></div>
<div class="clearerleft"></div>
</div>

</div>

<?php


include ("../include/footer.php");
