<?php
include "../../../include/db.php";
include_once "../../../include/general.php";
include "../../../include/authenticate.php";
include "../../../include/search_functions.php";
include_once "../../../include/collections_functions.php";
include "../inc/flickr_functions.php";
include __DIR__ . "/../lib/phpFlickr.php";

include "../../../include/header.php";

$theme=getvalescaped("theme","");
$private=getvalescaped("permission","");
$publish_type=getvalescaped("publish_type","");
$id="flickr_".$theme;
$progress_file=get_temp_dir(false,$id) . "/progress_file.txt";


?>
<h1><?php echo $lang["flickr_title"] ?></h1>
<?php

# Does this user have a Flickr token set? If so let's try and use it.
$last_xml="";

// Get a Request Token
# Get a Flickr token first
$flickr = new phpFlickr($flickr_api_key,$flickr_api_secret);
flickr_get_access_token($userref,(isset($_GET['oauth_verifier']) && $_GET['oauth_verifier'] != ''));


if($publish_type!='')
    {
    $photoset_array=flickr_get_photoset();	
    $photoset_name=$photoset_array[0];
    $photoset=$photoset_array[1];
    }
    
if(getval("start_publish","")!="")
    {
    if($publish_type=="all")
        {
        # Perform sync publishing all (updating any existing)
        sync_flickr("!collection" . $theme,false,$photoset,$photoset_name,getvalescaped("private",""));
        }
    elseif($publish_type=="new")
        {
        # Perform sync publishing new only.
        sync_flickr("!collection" . $theme,true,$photoset,$photoset_name,getvalescaped("private",""));
        }
    }
?>
