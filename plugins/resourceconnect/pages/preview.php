<?php
include_once "../../../include/db.php";

$k=getval("k","");
$kauth=true;
if ($k!="")
	{
	# Check that a valid access key exists for this collection.
	$kauth=false;
	
	$col=getval("col","");
	$keys=ps_query("select access_key from external_access_keys where collection=? and access_key=?",array("i",$col,"s",$k));
	$kauth=count($keys)>0;
	}

if ($k=="" || !$kauth) {include "../../../include/authenticate.php";}

# Wrap the remote view page with the local header/footer.

$search=getval("search","");
setcookie("search",$search, 0, '', '', false, true);

# Assemble a URL from the existing parameters.
$url=getval("resourceconnect_source","") . "/pages/preview.php?" . $_SERVER["QUERY_STRING"];

$html=file_get_contents($url);

#<!-- START GRAB -->
#<!-- END GRAB -->

$s=strpos($html, "<!-- START GRAB -->");
$e=strpos($html, "<!-- END GRAB -->",$s);
$html=substr($html,$s,$e-$s);

include "../../../include/header.php";

echo $html;

include_once "../../../include/footer.php";