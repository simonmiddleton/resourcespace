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

$ref = (int) getval('ref', 0, false, 'is_positive_int_loose'); # consider renaming to be more specific
$type = (int) getval('type', 0, false, fn($V) => in_array($V, BRAND_GUIDELINES_CONTENT_TYPES));
$delete = (int) getval('delete', 0, false, 'is_positive_int_loose');
$reorder = getval('reorder', '', false, __NAMESPACE__ . '\reorder_input_validator');
$save = getval('posting', '') !== '' && enforcePostRequest(false) && $delete === 0 && $reorder === '';
$edit = false;

$page = getval('page', 0, false, 'is_positive_int_loose');
$page_contents_db = get_page_contents($page);

if ($ref > 0) {
    /* $all_pages_index = array_column($pages_db, null,'ref');
    if (isset($all_pages_index[$ref])) {
        $edit = true;

        // Help the process_custom_fields_submission() fill in the form
        if (!$save) {
            $_GET['name'] = $all_pages_index[$ref]['name'];
            $parent = $all_pages_index[$ref]['parent'];
        }
    } */
}

// Page setup
$page_def = [
    BRAND_GUIDELINES_CONTENT_TYPES['text'] => [
        'title' => [
            'new' => $lang['brand_guidelines_title_new_text'],
            'edit' => $lang['brand_guidelines_title_edit_text'],
        ],
        'fields' => [
            [
            'id'       => 'name',
            'title'    => $lang['name'],
            'type'     => FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
            'required' => true,
            ],
            [
            'id'       => 'width',
            'title'    => $lang['property-width'],
            'type'     => FIELD_TYPE_DROP_DOWN_LIST,
            'options'  => [
                'full_width' => $lang['brand_guidelines_full_width'],
                'half_width' => $lang['brand_guidelines_half_width']
            ],
            'required' => true,
            ],
        ],
    ],
    BRAND_GUIDELINES_CONTENT_TYPES['resource'] => [
        'title' => [
            'new' => $lang['brand_guidelines_title_select_resource'],
            'edit' => $lang['brand_guidelines_title_select_resource'],
        ],
        'fields' => [
            [
            'id'       => 'resource_id',
            'title'    => $lang['resourceid'],
            'type'     => FIELD_TYPE_NUMERIC,
            'required' => true,
            'constraints' => ['min' => 1],
            ],
        ],
    ],
    BRAND_GUIDELINES_CONTENT_TYPES['colour'] => [],
];
$page_title = $page_def[$type]['title'];
$processed_fields = process_custom_fields_submission($page_def[$type]['fields'], $save, ['html_properties_prefix' => '']);

if ($save && count_errors($processed_fields) === 0) {
    /* $bg_page_name = $processed_fields[0]['value'];
    $bg_page_parent = $is_page && isset($processed_fields[1]['selected_options'][$parent]) ? $parent : 0;

    if ($edit) {
        // save_page($ref, $bg_page_name, $bg_page_parent);
        $redirect_params = $is_page ? ['spage' => $ref] : [];
    } else {
        $redirect_params = ['spage' => create_page($bg_page_name, $bg_page_parent)];
    }

    js_call_CentralSpaceLoad(
        generateURL("{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php", $redirect_params)
    ); */
} elseif ($delete > 0 && enforcePostRequest(false)) {
    /* if (delete_pages($delete_list)) {
        js_call_CentralSpaceLoad("{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php");
    } */
    exit(error_alert($lang['error-failed-to-delete'], true, 200));
} elseif ($reorder !== '' && enforcePostRequest(false)) {
    if ($edit) {
        /* reorder_items(
            'brand_guidelines_content',
            array_replace(
                // todo: change to the right var
                $all_pages_index,
                // todo: add logic to jump over item groups (when relevant)
                [$ref => compute_item_order($all_pages_index[$ref], $reorder)]
            ),
            fn($V) => $V['page'] === $all_pages_index[$ref]['page']
        ); */
        js_call_CentralSpaceLoad(
            // todo: use sections (id="content-item-<ref>") to navigate back to the moved items' position to allow user to continue their work
            generateURL("{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php", ['spage' => $page])
        );
    }
    exit(error_alert($lang['error-failed-to-move'], true, 200));
}


include_once RESOURCESPACE_BASE_PATH . '/include/header.php';
?>
<div class="BasicsBox">
    <h1><?php echo escape($edit ? $page_title['edit'] : $page_title['new']); ?></h1>
    <form
        name="manage_content"
        id="manage_content" class="modalform guidelines"
        method="POST"
        action="<?php echo "{$baseurl_short}plugins/brand_guidelines/pages/manage/content.php"; ?>"
        onsubmit="return CentralSpacePost(this, true, true);"
    >
        <?php
        generateFormToken('manage_content');
        render_hidden_input('ref', (string) $ref);
        render_hidden_input('type', (string) $type);
        render_custom_fields($processed_fields);
        ?>
        <div class="QuestionSubmit" >
            <input type="submit" name="content_submit" value="<?php echo escape($edit ? $lang['save'] : $lang['create']); ?>"></input>
            <div class="clearleft"></div>
        </div>
    </form>
</div>
<?php
include_once RESOURCESPACE_BASE_PATH . '/include/footer.php';
