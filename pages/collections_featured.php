<?php
include_once "../include/db.php";
include "../include/authenticate.php";

if(!$enable_themes)
    {
    http_response_code(403);
    exit($lang['error-permissiondenied']);
    }

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
$rendering_options = array(
    "full_width" => !$themes_simple_view,
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
    $smart_fcs_list = array_map(function(array $v)
        {
        return array(
            "ref" => $v["ref"],
            "name" => $v["smart_theme_name"],
            "type" => COLLECTION_TYPE_FEATURED,
            "parent" => null,
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
        $smart_fcs_list = array_map(function(array $v) use ($smart_rtf, $smart_fc_parent)
            {
            return array(
                "ref" => $v["ref"],
                "name" => $v["name"],
                "type" => COLLECTION_TYPE_FEATURED,
                "parent" => $v["ref"], # parent here is the node ID. When transformed to a FC this parent will be used for going to the next level down the branch
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
include "../include/footer.php";