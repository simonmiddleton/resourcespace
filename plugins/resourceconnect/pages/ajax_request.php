<?php
include_once "../../../include/db.php";
include_once "../../../include/general.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/search_functions.php";

# This basically acts as a proxy to fetch the remote results, because AJAX is unable to make requests directly to remote servers for security reasons.

$affiliate=$resourceconnect_affiliates[getval("affiliate","")];

$abaseurl=$affiliate["baseurl"];

$search=getval("search","");
$offset=getval("offset","");
$pagesize=getval("pagesize","");
$restypes=getval("restypes","");


# Parse and replace nodes.
$k=(split_keywords($search));$search="";
foreach ($k as $kw)
	{
	if (substr($kw,0,2)=="@@")
		{
		# Node, resolve to string
		$n=substr($kw,2);
		$node=array();get_node($n,$node);$name=$node["name"];
		$search.=' "' . i18n_get_translated($name) . '"';
		}
	else
		{
		# Not a node, add as is
		$search.=" " . $kw;
		}
	}
$search=trim($search);


# Sign this request.
$access_key=$affiliate["accesskey"];
$sign=md5($access_key . $search);

echo file_get_contents($abaseurl . "/plugins/resourceconnect/pages/remote_results.php?search=" . urlencode($search) . "&pagesize=" . $pagesize . "&offset=" . $offset . "&sign=" . urlencode($sign) . "&language_set="  . urlencode($language) . "&affiliatename=" . urlencode(getval("affiliatename","")) . "&restypes=" . urlencode($restypes) . "&resourceconnect_source=" . urlencode($baseurl));


?>
