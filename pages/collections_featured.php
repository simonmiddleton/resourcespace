<?php
include_once "../include/db.php";
include "../include/authenticate.php";

if(!$enable_themes)
    {
    http_response_code(403);
    exit($lang['error-permissiondenied']);
    }

$parent = (int) getval("parent", 0, true);
$smart_theme = (int) getval("smart_theme", 0, true);


include "../include/header.php";
?>
<div class="BasicsBox FeaturedSimpleLinks">
<?php
echo "<p>TODO: render breadcrumbs (@line ".__LINE__.")</p>";


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
// $rendering_options["smart_featured_collections"] = ($smart_theme > 0);
// render_featured_collections($rendering_options, $smart_themes);


if(!$smart_theme && checkperm('h'))
    {
    renderCallToActionTile(
        generateURL(
            "{$baseurl_short}pages/collections_featured.php",
            array(
                "new" => "true",
                "call_to_action_tile" => "true",
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