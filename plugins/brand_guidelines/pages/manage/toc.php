<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

include_once dirname(__DIR__, 4) . '/include/boot.php';
include_once RESOURCESPACE_BASE_PATH . '/include/authenticate.php';
include_once RESOURCESPACE_BASE_PATH . '/include/request_functions.php';
if (!acl_can_edit_brand_guidelines()) {
    http_response_code(401);
    exit(escape($lang['error-permissiondenied']));
}

$save = getval('posting', '') !== '' && enforcePostRequest(false);
$pages_db = get_pages();
$all_sections = extract_node_options(array_filter($pages_db, __NAMESPACE__ . '\is_section'), true, true);
$parent = getval('parent', 0, false, (fn($V) => is_positive_int_loose($V) || ($save && is_array($V) && count($V) === 1)));

// Quick page parent (ie. section) validation (value type is different depending on the save status)
foreach ($all_sections as $section_id => $section) {
    if (is_positive_int_loose($parent) && $section_id == $parent) {
        $parent = (int) $parent;
        break;
    } elseif ($save && is_array($parent) && $parent[0] === md5("parent_{$section}")) {
        $parent = (int) $section_id;
        break;
    }
}


// Page setup
$page_title = $lang['brand_guidelines_new_section_title'];
$toc_fields = [
    [
    'id'       => 'name',
    'title'    => $lang['name'],
    'type'     => FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
    'required' => true,
    ],
];
if (!is_section(['parent' => $parent])) {
    $page_title = $lang['brand_guidelines_new_page_title'];
    $toc_fields[] = [
        'id'       => 'parent',
        'title'    => $lang['brand_guidelines_section'],
        'type'     => FIELD_TYPE_DROP_DOWN_LIST,
        'options'  => $all_sections,
        // todo: make it select based on the $parent
        'required' => true,
    ];
}
$processed_toc_fields = process_custom_fields_submission($toc_fields, $save, ['html_properties_prefix' => '']);

if ($save && count_errors($processed_toc_fields) === 0) {
    new_page_record();
}


include_once RESOURCESPACE_BASE_PATH . '/include/header.php';
?>
<div class="BasicsBox">
    <h1><?php echo escape($page_title); ?></h1>
    <form
        name="manage_toc"
        id="manage_toc" class="modalform guidelines"
        method="POST"
        action="<?php echo "{$baseurl_short}plugins/brand_guidelines/pages/manage/toc.php"; ?>"
        onsubmit="return CentralSpacePost(this, true, true);"
    >
        <?php
        generateFormToken('manage_toc');
        render_custom_fields($processed_toc_fields);
        ?>
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
