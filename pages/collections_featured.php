<?php
include_once "../include/db.php";
include "../include/authenticate.php";

if(!$enable_themes)
    {
    http_response_code(403);
    exit($lang['error-permissiondenied']);
    }

$parent = getval("parent", 0, true);
$smart_theme = getvalescaped("smart_theme", "");


include "../include/header.php";
?>
<div class="BasicsBox FeaturedSimpleLinks">
<?php
echo "TODO: render breadcrumbs";

$context = array(); // TODO; remove if we don't need it in the end
render_featured_collections($context, $parent);






$new_collection_additional_params = array(); // TODO: add extra params needed to process new FC categories being created.


if(!$smart_theme && checkperm('h'))
   {
   renderCallToActionTile(
       generateURL(
           "{$baseurl_short}pages/collections_featured.php",
           array(
               'new' => 'true',
               'call_to_action_tile' => 'true'
           ),
           $new_collection_additional_params
       ));
   }
?>
</div> <!-- End of BasicsBox FeaturedSimpleLinks -->
<?php
include "../include/footer.php";