<?php
include_once "../../../include/db.php";

include_once "../../../include/authenticate.php";


$title=getval("title","");
$thumb=getval("thumb","");
$large_thumb=getval("large_thumb","");
$xl_thumb=getval("xl_thumb","");
$url=getval("url","");
$back=getval("back","");


/**
 * Does a record exist in the table already for this resource. 
 * Identify matching resource using collection id, resource ref in original database and resourceconnect source
 */

$pattern_ref = "/ref=(\d+)/"; // regex pattern to match resource id
$pattern_source = "/^(.*)\/pages/"; // regex patter to match remote resourceconnect host

/* check that a match exists in url for both the ref and the source  */
if (preg_match($pattern_ref, $url) == 1 && preg_match($pattern_source, $url) == 1)
    { 

    /** 
     * both ref and source patterns have been matched in url, 
     * now check if existing entry exists in table for this resource  
     */

    $matches_ref = array();
    preg_match($pattern_ref, $url, $matches_ref);
    $ref = $matches_ref[0];

    $matches_source = array();
    preg_match($pattern_source, $url, $matches_source);
    $source = $matches_source[0];

    $entry_exists = ps_value("SELECT count(ref) as value FROM resourceconnect_collection_resources WHERE collection = ? AND instr(url, ?) AND LOCATE(?, url,0) = 0", array("i",$usercollection,"s",$ref,"s",$source), 0);
    } else 
    {
    /* url does not match patterns to allow comparison, so set entry_exists var to 0 */
    $entry_exists = 0;
    }

// if no existing record then add
if ($entry_exists == 0)
    {
    # Add to collection
    $params = [
        'i', $usercollection,
        's', $thumb,
        's', $large_thumb,
        's', $xl_thumb,
        's', $url,
        's', $title
    ];

    if(isset($ref)){$params[]='i'; $params[]=explode('=', $ref)[1];}
    ps_query("INSERT INTO resourceconnect_collection_resources (collection,thumb,large_thumb,xl_thumb,url,title".((isset($ref))?',source_ref':'').") VALUES (". ps_param_insert(count($params)/2) .")", $params);
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