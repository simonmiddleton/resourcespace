<?php
include_once "../../../include/db.php";
include_once "../../../include/render_functions.php";
include_once "../../lightbox_preview/include/utility.php";
include_once "../config/config.php";

$search=getvalescaped("search","");
$sign=getvalescaped("sign","");
$offset=getvalescaped("offset",0);
$pagesize=getvalescaped("pagesize",$resourceconnect_pagesize);
$affiliatename=getvalescaped("affiliatename","");

$per_page=getvalescaped("per_page","");if (is_numeric($per_page)) {$pagesize=$per_page;} # Manual setting of page size.
$sort=getvalescaped("sort","");
$order_by=getvalescaped("order_by","");

$original_user=getval("user","");

# Authenticate as 'resourceconnect' user.
global $resourceconnect_user; # Which user to use for remote access?
if ($resourceconnect_user == "" || !isset($resourceconnect_user))
{
echo $lang["resourceconnect_user_not_configured"];
exit();
}
$userdata=validate_user("u.ref='$resourceconnect_user'");
setup_user($userdata[0]);


$restypes="";
# Resolve resource types
$resource_types=get_resource_types("", false);
$rtx=explode(",",getvalescaped("restypes",""));
foreach ($rtx as $rt)
    {
    # Locate the resource type name in the local list.  
    # We have to handle resource type names because the resource type numeric IDs could be different from system to system.
    foreach ($resource_types as $resource_type)
        {
        if ($rt!="" && strpos($resource_type["name"],$rt)!==false)
            {
            if ($restypes!="") {$restypes.=",";}    
            $restypes.=$resource_type["ref"];
            }
        }
    }

$results=do_search($search,$restypes,$order_by,0,$pagesize+$offset,$sort,false); # Search

# The access key is used to sign all inbound queries, the remote system must therefore know the access key.
$access_key=md5("resourceconnect" . $scramble_key);

# Check the search query against the signature.
$expected_sign=md5($access_key . $search);
if ($sign!=$expected_sign) {exit("<p>" . $lang["resourceconnect_error-not_signed_with_correct_key"] . "</p>");}

if (is_array($results) && $offset>count($results)) {while ($offset>count($results)) {$offset-=$pagesize;}}
if ($offset<0) {$offset=0;}
?><div class="BasicsBox"><?php

if (!is_array($results))
    {
    ?>
    <h1><?php echo $affiliatename ?></h1>
    <p><?php echo $lang["nomatchingresources"] ?></p>
    <?php
    }
else
    {
    ?>
    <div class="TopInpageNav">
    <div class="InpageNavLeftBlock"><?php echo $lang["youfound"] ?>:<br><span class="Selected"><?php echo count($results) . " " ?></span><?php if (count($results)==1){echo $lang["youfoundresource"];} else {echo $lang["youfoundresources"];}?></div>

    <div class="InpageNavLeftBlock"><?php echo $lang["resourceconnect_affiliate"] ?>:<br><span class="Selected"><?php echo $affiliatename ?></span></div>   

    
    
    <div id="searchSortOrderContainer" class="InpageNavLeftBlock ">
        Sort order:<br>
        <select id="rc_order_by" onChange="ResourceConnect_Repage(0);">
            <option value="relevance"><?php echo $lang["relevance"] ?></option>
            <option value="popularity"><?php echo $lang["popularity"] ?></option>
            <option value="colour"><?php echo $lang["colour"] ?></option>
            <option value="date"><?php echo $lang["date"] ?></option> 
    </select>
    <select id="rc_sort" onChange="ResourceConnect_Repage(0);">
        <option value="ASC">ASC</option>
        <option value="DESC" selected="">DESC</option>
    </select>
    </div>
    
    
    <div class="InpageNavLeftBlock">Per page:
    <br>
        <select id="rc_per_page" style="width:auto" name="resultsdisplay" onChange="ResourceConnect_Repage(0);">
            <option>24</option>
            <option>48</option>
            <option>72</option>
            <option>120</option>
            <option>240</option>
        </select>
    </div>
    
    <div class="InpageNavLeftBlock"><a href="#" onClick="jQuery('#RefineResults').slideToggle();jQuery('#refine_keywords').focus();">+ <?php echo $lang["refineresults"] ?></a></div>
    <div class="InpageNavLeftBlock">Resource sharing:<br><?php echo $lang["resourceconnect_help"] ?></div>
        
    <?php
    function rc_pager()
        {
        global $offset,$pagesize,$lang,$results;
        ?>
        <div class="InpageNavRightBlock">
        <span class="TopInpageNavRight"><br />
        <a href="#" 
        
        <?php if ($offset-$pagesize>=0) { ?>onClick="ResourceConnect_Repage(-<?php echo $pagesize ?>);return false;"<?php } ?>
        ><i aria-hidden="true" class="fa fa-arrow-left"></i></a>
        &nbsp;
        <?php echo $lang["page"] . " " .  (floor($offset/$pagesize)+1) . " " . $lang["of"] . " " . (floor(count($results)/$pagesize)+1) ?>
        &nbsp;
        <a href="#" 
        <?php if ($offset+$pagesize<=count($results)) { ?>onClick="ResourceConnect_Repage(<?php echo $pagesize ?>);return false;"<?php } ?>
        
        ><i aria-hidden="true" class="fa fa-arrow-right"></i></a>
        </span>
        </div>
        <?php
        }
    rc_pager();
    ?>

    
    <div class="RecordBox clearerleft" id="RefineResults" style="display:none;">

    <div class="Question Inline" id="question_refine" style="border-top:none;">
    <label id="label_refine" for="refine_keywords"><?php echo $lang["additionalkeywords"]?></label>
    <input class="medwidth Inline" type=text id="refine_keywords" name="refine_keywords" value="">
    <input class="vshrtwidth Inline" name="save" type="submit" id="refine_submit" onClick="ResourceConnect_Repage(0);return false;" value="&nbsp;&nbsp;<?php echo $lang["refine"]?>&nbsp;&nbsp;" />
    <div class="clearerleft"> </div>

    </div>
    </div>
        
        
        
    </div>
    
    <div class="clearerleft"></div>
    
            
            
    <!--<h1><?php echo $affiliatename ?></h1>-->
    <?php
    
    for ($n=$offset;$n<count($results) && $n<($offset+$pagesize);$n++)
        {
        $result=$results[$n];
        $ref=$result["ref"];
        # Set $k value to enable files fetched through download.php
        global $k;
        $k=$original_user. "-" . substr(md5($access_key . $ref),0,10);

        $url=$baseurl . "/pages/view.php?modal=true&ref=" . $ref . "&k=" . $k . "&language_set=" . urlencode($language) . "&search=" . urlencode($search) . "&offset=" . $offset . "&resourceconnect_source=" . urlencode(getval("resourceconnect_source",""));
        
        # Wrap with local page that includes header/footer/sidebar
        $link_url="../plugins/resourceconnect/pages/view.php?search=" . urlencode($search) . "&url=" . urlencode($url);
        
        $title=str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated($result["field".$view_title_field])));
        
        # Add to collections link.
        $add_url=getval("resourceconnect_source","") . "/plugins/resourceconnect/pages/add_collection.php?nc=" . time();
        $add_url.="&title=" . urlencode(get_data_by_field($ref,$view_title_field));
        $add_url.="&url=" . urlencode(str_replace("&search","&source_search",$url)); # Move the search so it doesn't get set, and therefore the nav is hidden when viewing the resource
        $add_url.="&back=" . urlencode($baseurl . "/pages/view.php?" . $_SERVER["QUERY_STRING"]);
        # Add image 
        if ($result["has_image"]==1)
            { 
            $add_url.="&thumb=" . urlencode(get_resource_path($ref,false,"col",false,"jpg"));
            $add_url.="&large_thumb=" . urlencode(get_resource_path($ref,false,"thm",false,"jpg"));
            $add_url.="&xl_thumb=" . urlencode(get_resource_path($ref,false,"pre",false,"jpg"));
            }   
        else
            {
            $add_url.="&thumb=" . urlencode($baseurl . "/gfx/" . get_nopreview_icon($result["resource_type"],$result["file_extension"],true));
            $add_url.="&large_thumb=" . urlencode($baseurl . "/gfx/" . get_nopreview_icon($result["resource_type"],$result["file_extension"],false));
            $add_url.="&xl_thumb=" . urlencode($baseurl . "/gfx/" . get_nopreview_icon($result["resource_type"],$result["file_extension"],false));
            }
        
        ?>
        <div class="ResourcePanel">
        
    
        <a class="ImageWrapper" href="<?php echo $link_url?>" title="<?php echo $title ?>" onClick="return CentralSpaceLoad(this,true,true);">
        
        <?php if ($result["has_image"]==1) {
            
            $img_url = get_resource_path($ref,false,"thm",false,$result["preview_extension"],-1,1,false,$result["file_modified"]);
            
            
            $size = getimagesize($img_url);
            $ratio = (isset($size[0]))? $size[0] / $size[1] : 1; 
            
            $defaultheight = $defaultwidth = 175;

            if ($ratio > 1)
            {
            $width = $defaultwidth;
            $height = round($defaultheight / $ratio);
            $margin = floor(($defaultheight - $height ) / 2) . "px";
            }
        elseif ($ratio < 1)
            {
            # portrait image dimensions
            $height = $defaultheight;
            $width = round($defaultwidth * $ratio);
            $margin = floor(($defaultheight - $height ) / 2) . "px";
            }
        else
            {
            # square image or no image dimensions
            $height = $defaultheight;
            $width = $defaultwidth;
            $margin = "auto";
            }
            echo "<img height=\"$height\" width=\"$width\" margin=\"$margin\" src=\"$img_url\" style=\"margin-top:$margin;\" />";
            # add icon overlay if remote image
            hook("aftersearchimg","",array($result, $img_url));
            ?>
        <?php } else { ?>
                
        <img border=0 src="<?php echo $baseurl ?>/gfx/<?php echo get_nopreview_icon($result["resource_type"],$result["file_extension"],false,false,true) ?>"

        /><?php } ?></a>
    
        <div class="ResourcePanelInfo"><?php echo tidy_trim($title,25) ?>&nbsp;</div>

        <div class="clearer"></div>
                
        <div class="ResourcePanelIcons">
        
        <!-- Preview icon -->
        <?php
        #$url = getPreviewURL($result);
        $url = $baseurl . "/pages/preview.php?ref=" . $result["ref"] . "&k=" . substr(md5($access_key . $ref),0,10) . "&resourceconnect_source=1";
    if ($url!==false)
                { ?>
                <a aria-hidden="true" class="fa fa-expand" target="_blank" 
                        href="<?php echo $url ?>"
                        title="<?php echo $lang["fullscreenpreview"]?>" rel="lightbox"
                ></a>
                <?php
                }
            ?>

        
        <a class="addToCollection fa fa-plus-circle" target="collections" href="<?php echo $add_url ?>" onClick="return CollectionDivLoad(this,true);"></a>
        </div>

        
        </div>

        <?php
        }
    ?><div class="BottomInpageNav"><?php
    rc_pager();
    ?></div>
    
    </div><!-- End of BasicsBox -->
    <script>
    ResourceConnect_SetPageOptions();
    
    </script>
    <?php
    # Initiate lightbox.
    addLightBox('.RCfullscreen');
    }
