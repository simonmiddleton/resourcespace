<?php
include_once "../../../include/db.php";
include_once "../../../include/general.php";
include_once "../../../include/search_functions.php";
include_once "../../../include/collections_functions.php";
include_once "../../../include/authenticate.php";


$title=getvalescaped("title","");
$thumb=getvalescaped("thumb","");
$large_thumb=getvalescaped("large_thumb","");
$xl_thumb=getvalescaped("xl_thumb","");
$url=getvalescaped("url","");
$back=getvalescaped("back","");




/**
 * Does a record exist in the table already for this resource. Identify matching resource using collection id, resource ref in original database and resourceconnect source
 */
$matches = array();

preg_match("/ref=(\d+)/", $url, $matches); // get resource ref from url
$ref = $matches[0];

preg_match("/^(.*)\/pages/", $url, $matches); // get resourceconnect source from url
$source = $matches[0];

$entry_exists = sql_value("SELECT count(ref) as value FROM resourceconnect_collection_resources WHERE collection = '$usercollection' AND instr(url, '$ref') AND LOCATE('$source', url,0) = 0", 0);


// if no existing record then add
if ($entry_exists == 0)
    {
    # Add to collection
    sql_query("INSERT INTO resourceconnect_collection_resources (collection,thumb,large_thumb,xl_thumb,url,title) VALUES ('$usercollection','$thumb','$large_thumb','$xl_thumb','$url','$title')");
    }

redirect("pages/collections.php?nc=" . time());

/*
refresh_collection_frame();

$bodyattribs="onload=\"window.setTimeout('history.go(-1);',1000);";

include "../../../include/header.php";
?>
<h1><?php echo $lang["addtocollection"] ?></h1>
<p><?php echo $lang["resourceconnect_addedcollection"] ?></p>
<p>&lt;&nbsp;<a href="<?php echo $back ?>"><?php echo $lang["backtoresourceview"] ?></a></p>
<?php
include "../../../include/footer.php";
?>
*/