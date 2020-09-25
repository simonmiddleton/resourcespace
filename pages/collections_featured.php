<?php
include_once "../include/db.php";
include "../include/authenticate.php";

if(!$enable_themes)
    {
    http_response_code(403);
    exit($lang["error-permissiondenied"]);
    }

$k = getval("k", "");
$parent = (int) getval("parent", 0, true);
$smart_rtf = (int) getval("smart_rtf", 0, true);
$smart_fc_parent = getval("smart_fc_parent", 0, true);
$smart_fc_parent = ($smart_fc_parent > 0 ? $smart_fc_parent : null);


if(getval("new", "") == "true" && getval("cta", "") == "true")
    {
    new_featured_collection_form($parent);
    exit();
    }





include "../include/header.php";
?>
<div class="BasicsBox FeaturedSimpleLinks">
<?php
echo "<p>TODO: render breadcrumbs (@line ".__LINE__.")</p>";

// Default rendering options (should apply to both FCs and smart FCs)
$full_width = !$themes_simple_view;
$rendering_options = array(
    "full_width" => $full_width,
);


$featured_collections = ($smart_rtf == 0 ? get_featured_collections($parent) : array());
usort($featured_collections, function(array $a, array $b)
    {
    if($a["has_resources"] == $b["has_resources"])
        {
        return 0;
        }

    return ($a["has_resources"] < $b["has_resources"] ? -1 : 1);
    });
render_featured_collections($rendering_options, $featured_collections);


$smart_fcs_list = array();
if($parent == 0 && $smart_rtf == 0)
    {
    // Root level - this is made up of all the fields that have a Smart theme name set.
    $smart_fc_headers = array_filter(get_smart_theme_headers(), function(array $v) { return metadata_field_view_access($v["ref"]); });
    $smart_fcs_list = array_map(function(array $v) use ($FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS)
        {
        return array(
            "ref" => $v["ref"],
            "name" => $v["smart_theme_name"],
            "type" => COLLECTION_TYPE_FEATURED,
            "parent" => null,
            "thumbnail_selection_method" => $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"],
            "has_resources" => 0,
            "resource_type_field" => $v["ref"]);
        },
        $smart_fc_headers);
    }
else if($parent == 0 && $smart_rtf > 0 && metadata_field_view_access($smart_rtf))
    {
    // Smart fields. If a category tree, then a parent could be passed once user requests a lower level than root of the tree
    $resource_type_field = get_resource_type_field($smart_rtf);
    if($resource_type_field !== false)
        {
        $smart_fc_nodes = get_smart_themes_nodes($smart_rtf, (FIELD_TYPE_CATEGORY_TREE == $resource_type_field["type"]), $smart_fc_parent);
        $smart_fcs_list = array_map(function(array $v) use ($smart_rtf, $smart_fc_parent, $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS)
            {
            return array(
                "ref" => $v["ref"],
                "name" => $v["name"],
                "type" => COLLECTION_TYPE_FEATURED,
                "parent" => $v["ref"], # parent here is the node ID. When transformed to a FC this parent will be used for going to the next level down the branch
                "thumbnail_selection_method" => $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["most_popular_image"],
                "has_resources" => 0,
                "resource_type_field" => $smart_rtf,
                "node_is_parent" => $v["is_parent"]);
            },
            $smart_fc_nodes);
        }
    }
$rendering_options["smart"] = (count($smart_fcs_list) > 0);
render_featured_collections($rendering_options, $smart_fcs_list);
unset($rendering_options["smart"]);


if($smart_rtf == 0 && checkperm("h") && $collection_allow_creation)
    {
    render_new_featured_collection_cta(
        generateURL(
            "{$baseurl_short}pages/collections_featured.php",
            array(
                "new" => "true",
                "cta" => "true",
                "parent" => $parent,
            )
        ),
        $rendering_options);
    }
?>
</div> <!-- End of BasicsBox FeaturedSimpleLinks -->
<script>
jQuery(document).ready(function ()
    {
    jQuery('.FeaturedSimpleTile').hover(
    function(e)
        {
        tileid = jQuery(this).attr('id').substring(19);
        jQuery('#FeaturedSimpleTileActions_' + tileid).stop(true, true).slideDown();
        },
    function(e)
        {
        tileid=jQuery(this).attr('id').substring(19);
        jQuery('#FeaturedSimpleTileActions_' + tileid).stop(true, true).slideUp();
        });
    });
</script>
<?php
if($themes_show_background_image && !$full_width)
    {
    $slideshow_files = get_slideshow_files_data();

    if(!$featured_collection_static_bg && ($parent > 0 || ($smart_rtf > 0 && count($smart_fcs_list) > 0)))
        {
        // Overwrite background_image_url with theme specific ones
        $get_fc_imgs_ctx = array("limit" => 1);

        if($parent > 0)
            {
            $collection_data = get_collection($parent);
            $collection_resources = get_collection_resources($parent);
            $collection_data["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);

            // get_featured_collection_resources() is expecting a featured collection structure. $collection_data being a 
            // collection structure is a superset containing the required information (ref, parent, has_resources) for the function to work
            $bg_fc_images = get_featured_collection_resources($collection_data, $get_fc_imgs_ctx);
            $bg_fc_images = generate_featured_collection_image_urls($bg_fc_images, "scr");
            }
        else if((count($smart_fcs_list) > 0))
            {
            $get_fc_imgs_ctx["smart"] = true;
            foreach($smart_fcs_list as $smart_fc)
                {
                $smart_fc_images = get_featured_collection_resources($smart_fc, $get_fc_imgs_ctx);
                $smart_fc_images = generate_featured_collection_image_urls($smart_fc_images, "scr");

                if(!empty($smart_fc_images))
                    {
                    $bg_fc_images = $smart_fc_images;
                    break;
                    }
                }
            }

        if(isset($bg_fc_images) && is_array($bg_fc_images) && !empty($bg_fc_images))
            {
            $background_image_url = $bg_fc_images[0]; # get_fc_imgs_ctx is limiting to 1 so we know we have this

            // Reset slideshow files as we want to use the featured collection image
            $slideshow_files = array();
            }
        }
    ?>
    <script>
    var SlideshowImages = new Array();
    var SlideshowCurrent = -1;
    var big_slideshow_timer = <?php echo $slideshow_photo_delay; ?>;
    <?php
    foreach($slideshow_files as $slideshow_file_info)
        {
        if((bool) $slideshow_file_info['featured_collections_show'] === false)
            {
            continue;
            }

        $image_download_url = "{$baseurl_short}pages/download.php?slideshow={$slideshow_file_info['ref']}";
        $image_resource = isset($slideshow_file_info['link']) ? $slideshow_file_info['link'] : '';
        ?>
        RegisterSlideshowImage('<?php echo $image_download_url; ?>', '<?php echo $image_resource; ?>');
        <?php
        }

    if(!$featured_collection_static_bg && isset($background_image_url) && trim($background_image_url) != '')
        {
        ?>
        RegisterSlideshowImage('<?php echo $background_image_url; ?>', '', true);
        <?php
        }
    ?>
    jQuery(document).ready(function() 
        {
        ClearTimers();
        ActivateSlideshow();
        });
    </script>
    <?php
    }
include "../include/footer.php";