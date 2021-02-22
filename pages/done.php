<?php
include "../include/db.php";

if (getval("user","")!="" || isset($anonymous_login) || hook('provideusercredentials'))
    {
    // Authenticate if already logged in, so the correct theme is displayed when using user group specific themes.
    include "../include/authenticate.php";
    }

if (getval("refreshcollection","")!="")
	{
	refresh_collection_frame();
	}

# fetch the current search 
$search=getvalescaped("search","");
$order_by=getvalescaped("order_by","relevance");
$offset=getvalescaped("offset",0,true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getvalescaped("archive","");
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$k = getval("k","");

include "../include/header.php";
?>

<div class="BasicsBox">
    <h1><?php echo $lang["complete"]?></h1>
    <p><?php echo text(htmlspecialchars(getvalescaped("text",""))) ?></p>
   
    <?php
    if ((getval("user","")!="" || $k!="" || isset($anonymous_login) || hook('checkuserloggedin')) && getval("notloggedin","")=="")
        {
        # User logged in?
        if(!hook("donebacktoresource"))
            {
            # Ability to link back to a resource page
            $resource=getval("resource","");
            if ($resource!="")
                {
                ?>
                <p><a href="<?php echo $baseurl_short?>pages/view.php?ref=<?php echo urlencode($resource) ?>&k=<?php echo urlencode(getval("k","")) ?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset) ?>&order_by=<?php echo ($order_by) ?>&sort=<?php echo urlencode($sort) ?>&archive=<?php echo urlencode($archive) ?>" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["continuetoresourceview"]?></a></p>
                <?php
                }
            }
        if ($k=="")
            {?>
            <p><a href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset) ?>&order_by=<?php echo urlencode($order_by) ?>&sort=<?php echo urlencode($sort) ?>&archive=<?php echo urlencode($archive) ?>&amp;restypes=<?php echo urlencode($restypes); ?>" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["continuetoresults"]?></a></p>
            <p><a href="<?php echo ($use_theme_as_home?$baseurl_short.'pages/collections_featured.php':$default_home_page)?>" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["continuetohome"]?></a></p>
            <?php
            }
        elseif($k!="" && upload_share_active())
            {
            $collection = getval("collection",0,true);
            $uploadurl = get_upload_url($collection,$k);
            ?>
            <div class='clearerleft'></div>
            <div>
                <input type='button' value='<?php echo $lang["upload"];?>' onclick='CentralSpaceLoad("<?php echo $uploadurl; ?>");'>
            </div>    
            <?php
            }
        hook("extra");
        }
    else
        {?>
        <p><a href="<?php echo $baseurl_short?>login.php" ><?php echo LINK_CARET ?><?php echo $lang["continuetouser"]?></a></p>
        <?php
        }?>
</div>

<?php
include "../include/footer.php";
?>
