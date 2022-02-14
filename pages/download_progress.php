<?php
include "../include/db.php";

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getvalescaped("k","");if (($k=="") || (!check_access_key(getvalescaped("ref","",true),$k))) {include "../include/authenticate.php";}

$ref=getval("ref","");
$size=getval("size","");
$ext=getval("ext","");
if(!preg_match('/^[a-zA-Z0-9]+$/', $ext)){$ext="jpg";} # Mitigate path injection
$alternative=getval("alternative",-1);
$search=getvalescaped("search","");
$iaccept=getvalescaped("iaccept","off");
$usage=getval("usage","-1");
$usagecomment=getval("usagecomment","");
$email       = getvalescaped('email', '');
$url=getval('url','');

if($url == '')
    {
    $download_url_suffix="?ref=" . urlencode($ref)  . "&size=" . urlencode($size) . "&ext=" . urlencode($ext) 
                        . "&k=" . urlencode($k) . "&alternative=" . urlencode($alternative) . "&iaccept=" . urlencode($iaccept);
    $download_url_suffix.= hook("addtodownloadquerystring");

    if ($download_usage && getval("usage","")=="" && $terms_download)
        {
        redirect($baseurl_short."pages/download_usage.php".$download_url_suffix);
        }

    if (!($url=hook("getdownloadurl", "", array($ref, $size, $ext, 1, $alternative)))) // used in remotedownload-plugin
        {
        $download_url_suffix.="&usage=" . urlencode($usage) . "&usagecomment=" . urlencode($usagecomment) . "&email=" . urlencode($email);
        $url=$baseurl."/pages/download.php" . $download_url_suffix;
        }   
    }

include "../include/header.php";

if (!$save_as)
	{
	?>
	<script type="text/javascript">
	window.setTimeout("document.location='<?php echo str_replace('\'', '%27', $url); ?>'",1000);
	</script>
	<?php
	}
?>

<div class="BasicsBox">
    
	<?php if ($save_as) { 
	# $save_as set or Opera browser? Provide a download link instead. Opera blocks any attempt to send it a download (meta/js redirect)	?>
    <h2>&nbsp;<h2> 
    <h1><?php echo $lang["downloadresource"]?></h1>
    <p style="font-weight:bold;"><?php echo LINK_CARET ?><a href="<?php echo htmlspecialchars($url); ?>"><?php echo $lang["rightclicktodownload"]?></a></p>
	<?php } else { 
	# Any other browser - standard 'your download will start shortly' text.
	?>
	<h2>&nbsp;<h2>
    <h1><?php echo $lang["downloadinprogress"]?></h1>
    <p><?php echo text("introtext")?></p>
	<?php } 
	$offset= getval("saved_offset",getval("offset",0,true));
	$order_by= getval("saved_order_by",getval("order_by",""));
	$sort= getval("saved_sort",getval("sort",""));
	$archive= getval("saved_archive",getval("archive",""));
	
	// Set parameters for links
	$url_parameters=array
		(
		"ref"=>$ref,
		"k"=>$k,
		"search"=>getval("search",""),
		"offset"=>$offset,
		"order_by"=>$order_by,
		"sort"=>$sort,
		"archive"=>$archive
		);
	
	?>
    <?php if (!hook("downloadfinishlinks"))
        {?>
        <p><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateURL($baseurl_short . "pages/view.php",$url_parameters) ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
        <?php
        if(strpos($search,"!collection") !== false)
            {?>
            <p><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateURL($baseurl_short . "pages/search.php", $url_parameters) ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresults"]?></a></p>
            <?php
            }

        if ($k=="")
            { ?>
            <p><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateURL($baseurl_short  . "pages/home.php") ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtohome"]?></a></p>
            <?php
            }
        }?>
	
</div>
<?php
include "../include/footer.php";