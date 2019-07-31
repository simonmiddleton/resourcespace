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

$per_page=getval("per_page","");
$order_by=getval("order_by","");
$sort=getval("sort","");


# Parse and replace nodes.
$k=(split_keywords($search));$search="";
foreach ($k as $kw)
	{
	if (substr($kw,0,2)=="@@")
		{
		# Node, resolve to string
		$n=substr($kw,2);
		$node=array();get_node($n,$node);$name=$node["name"];
        
        if (in_array($node["resource_type_field"],$resourceconnect_bind_fields))
            {
            # Preserve filter to bind the field.
            $field_info=get_resource_type_field($node["resource_type_field"]);
            $search.=' ' . $field_info['name'] . ':' . i18n_get_translated($name) ;
            }
        else
            {
            # Strip back to plain text and match on any field.             
            $search.=' "' . i18n_get_translated($name) . '"';
            }
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

echo file_get_contents($abaseurl . "/plugins/resourceconnect/pages/remote_results.php?user=" . urlencode($username) . "&search=" . urlencode($search) . "&pagesize=" . $pagesize .
                       "&per_page=" . $per_page ."&order_by=" . $order_by ."&sort=" . $sort .
                       "&offset=" . $offset . "&sign=" . urlencode($sign) . "&language_set="  . urlencode($language) . "&affiliatename=" . urlencode(getval("affiliatename","")) . "&restypes=" . urlencode($restypes) . "&resourceconnect_source=" . urlencode($baseurl));


?>
