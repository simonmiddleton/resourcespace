<?php
include_once "../include/db.php";
include "../include/authenticate.php";

if(!$enable_themes)
    {
    http_response_code(403);
    exit($lang['error-permissiondenied']);
    }

$parent = (int) getval("parent", 0, true);
$smart_fc = (int) getval("smart_fc", 0, true); # default null. We need to distinguish between we are at root and we went into a different FC categ
$smart_fc_parent = getval("smart_fc_parent", 0, true);
$smart_fc_parent = ($smart_fc_parent > 0 ? $smart_fc_parent : null);


if(getval("new", "") == "true" && getval("cta", "") == "true")
    {
    // TODO: refactor the new_featured_collection_form() once discussed more with the Dan
    // collections_featured is meant to be the managing page for FCs yet you have to create new collections for new categories.
    // A clean UX is needed here. What are we expecting of users here?
    new_featured_collection_form($parent);
    exit();
    }





include "../include/header.php";
?>
<div class="BasicsBox FeaturedSimpleLinks">
<?php
echo "<p>TODO: render breadcrumbs (@line ".__LINE__.")</p>";

// TODO: render these FCs only if we don't have a smart_fc 
$featured_collections = get_featured_collections($parent);
usort($featured_collections, function(array $a, array $b)
    {
    if($a["has_resources"] == $b["has_resources"])
        {
        return 0;
        }

    return ($a["has_resources"] < $b["has_resources"] ? -1 : 1);
    });

$rendering_options = array(
    "full_width" => false, # TODO: Add a new full width tile mode to the page that simulates the existing list view
);
render_featured_collections($rendering_options, $featured_collections);


// TODO: Add support for 'Smart themes' configured from the metadata field edit page
$smart_fcs_list = array();
if($smart_fc == 0)
    {
    // root level
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
else if($smart_fc > 0 && metadata_field_view_access($smart_fc))
    {
    // we know which field and have access
    // TODO: work going to the next level in the SMART FC
    // $smart_fcs_list = get_smart_themes_nodes($smart_fc, (7 == $headers[$n]['type']), $smart_fc_parent);
    }
$rendering_options["smart"] = (count($smart_fcs_list) > 0);
render_featured_collections($rendering_options, $smart_fcs_list);


if(checkperm('h'))
    {
    renderCallToActionTile(
        generateURL(
            "{$baseurl_short}pages/collections_featured.php",
            array(
                "new" => "true",
                "cta" => "true",
                "parent" => $parent,
            )
        ));
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