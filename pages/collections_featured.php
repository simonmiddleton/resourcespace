<?php
include_once "../include/db.php";

$k = trim(getval("k", ""));
$parent = (int) getval("parent", 0, true);
if($k == "" || !check_access_key_collection($parent, $k))
    {
    include "../include/authenticate.php";
    }

if(!$enable_themes)
    {
    http_response_code(403);
    exit($lang["error-permissiondenied"]);
    }

// Access control
if($parent > 0 && !featured_collection_check_access_control($parent))
    {
    exit(error_alert($lang["error-permissiondenied"], true, 403));
    }

$smart_rtf = (int) getval("smart_rtf", 0, true);
$smart_fc_parent = getval("smart_fc_parent", 0, true);
$smart_fc_parent = ($smart_fc_parent > 0 ? $smart_fc_parent : null);

$general_url_params = ($k == "" ? array() : array("k" => $k));

$parent_collection_data = get_collection($parent);
$parent_collection_data = (is_array($parent_collection_data) ? $parent_collection_data : array());


if(getval("new", "") == "true" && getval("cta", "") == "true")
    {
    new_featured_collection_form($parent);
    exit();
    }

// List of all FCs. For huge trees, helps increase performance but might require an increase for memory_limit in php.ini
$all_fcs = get_all_featured_collections();
include "../include/header.php";
?>
<div class="BasicsBox FeaturedSimpleLinks">
<?php
if($enable_theme_breadcrumbs && $parent > 0)
    {
    $links_trail = array(
        array(
            "title" => $lang["themes"],
            "href"  => generateURL("{$baseurl_short}pages/collections_featured.php", $general_url_params)
        )
    );

    $branch_trail = array_map(function($branch) use ($baseurl_short, $general_url_params)
        {
        return array(
            "title" => strip_prefix_chars(i18n_get_translated($branch["name"]),"*"),
            "href"  => generateURL("{$baseurl_short}pages/collections_featured.php", $general_url_params, array("parent" => $branch["ref"]))
        );
        }, compute_node_branch_path($all_fcs, $parent));

    renderBreadcrumbs(array_merge($links_trail, $branch_trail), "", "BreadcrumbsBoxTheme");
    }

// Default rendering options (should apply to both FCs and smart FCs)
$full_width = !$themes_simple_view;
$rendering_options = array(
    "full_width" => $full_width,
    "general_url_params" => $general_url_params,
    "all_fcs" => $all_fcs,
);

$featured_collections = ($smart_rtf == 0 ? get_featured_collections($parent, array()) : array());
usort($featured_collections, "order_featured_collections");
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
        // We go one level at a time so we don't need it to search recursively even if this is a FIELD_TYPE_CATEGORY_TREE
        $smart_fc_nodes = get_smart_themes_nodes($smart_rtf, false, $smart_fc_parent, $resource_type_field);
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


if($k == "" && $smart_rtf == 0)
    {
    if($collection_allow_creation && checkperm("h"))
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

    if(allow_upload_to_collection($parent_collection_data))
        {
        $upload_url = generateURL(
            "{$baseurl_short}pages/edit.php",
            array(
                "uploader" => $top_nav_upload_type,
                "ref" => -$userref,
                "collection_add" => $parent
            )
        );
        if($upload_then_edit)
            {
            $upload_url = generateURL("{$baseurl_short}pages/upload_plupload.php", array("collection_add" => $parent));
            }

        $rendering_options["html_h2_span_class"] = "fa fa-fw fa-upload";
        $rendering_options["centralspaceload"] = true;

        render_new_featured_collection_cta($upload_url, $rendering_options);
        }
    }
?>
</div><!-- End of BasicsBox FeaturedSimpleLinks -->
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

    // Get and update display for total resource count for each of the rendered featured collections (@see render_featured_collection() for more info)
    var fcs_waiting_total = jQuery('.FeaturedSimpleTile.FullWidth .FeaturedSimpleTileContents h2 span[data-tag="resources_count"]');
    var fc_refs = [];
    fcs_waiting_total.each(function(i, v) { fc_refs.push(jQuery(v).data('fc-ref')); });
    if(fc_refs.length > 0)
        {
        api('get_collections_resource_count', {'refs': fc_refs.join(',')}, function(response)
            {
            var lang_resource = '<?php echo htmlspecialchars($lang['youfoundresource']); ?>';
            var lang_resources = '<?php echo htmlspecialchars($lang['youfoundresources']); ?>';

            Object.keys(response).forEach(function(k)
                {
                var total_count = response[k];
                jQuery('.FeaturedSimpleTile.FullWidth .FeaturedSimpleTileContents h2 span[data-tag="resources_count"][data-fc-ref="' + k + '"]')
                    .text(total_count + ' ' + (total_count == 1 ? lang_resource : lang_resources));
                });

            });
        }
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
            $collection_resources = get_collection_resources($parent);
            $parent_collection_data["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);

            // get_featured_collection_resources() is expecting a featured collection structure. $parent_collection_data being a 
            // collection structure is a superset containing the required information (ref, parent, has_resources) for the function to work
            $get_fc_imgs_ctx["use_thumbnail_selection_method"] = true;
            $get_fc_imgs_ctx["all_fcs"] = $all_fcs;
            $bg_fc_images = get_featured_collection_resources($parent_collection_data, $get_fc_imgs_ctx);
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