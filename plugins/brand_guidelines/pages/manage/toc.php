<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

include_once dirname(__DIR__, 4) . '/include/boot.php';
include_once RESOURCESPACE_BASE_PATH . '/include/authenticate.php';
if (!acl_can_edit_brand_guidelines()) {
    http_response_code(401);
    exit(escape($lang['error-permissiondenied']));
}

$form_action = "{$baseurl_short}plugins/brand_guidelines/pages/manage/toc.php";
$parent = (int) getval('parent', 0, false, 'is_positive_int_loose');
// todo: ensure parent is never a page

if (getval('posting', '') !== '' && enforcePostRequest(false)) {
    new_page_record();
}


include_once RESOURCESPACE_BASE_PATH . '/include/header.php';
?>
todo: update names/ids
<div class="BasicsBox">
    <h1><?php echo escape($lang['brand_guidelines_new_section_title']); ?></h1>
    <form name="new_collection_form" id="new_collection_form" class="modalform"
            method="POST" action="<?php echo $form_action; ?>" onsubmit="return CentralSpacePost(this, true, true);">
        <?php generateFormToken('name'); ?>
        <input type="hidden" name="parent" value="<?php echo $parent; ?>"></input>
        <div class="Question">
            <label for="newcollection" ><?php echo escape($lang['name']); ?></label>
            <input type="text" name="name" id="newcollection" maxlength="255" required="true"></input>
            <div class="clearleft"></div>
        </div>
        <div class="QuestionSubmit" >
            <input type="submit" name="create" value="<?php echo escape($lang['create']); ?>"></input>
            <div class="clearleft"></div>
        </div>
    </form>
</div>
<script>
// todo: delete if left unused
</script>
<?php
include_once RESOURCESPACE_BASE_PATH . '/include/footer.php';
