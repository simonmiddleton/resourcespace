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

// Actions
$delete = (int) getval('delete', 0, false, 'is_positive_int_loose');
$reorder = getval('reorder', '', false, __NAMESPACE__ . '\reorder_input_validator');
$save = getval('posting', '') !== '' && enforcePostRequest(false) && $delete === 0 && $reorder === '';
$edit = false;

// Specific page content item 
$ref = (int) getval('ref', 0, false, 'is_positive_int_loose');
if ($ref > 0) {
    $db_item = get_page_content_item($ref);
    if ($db_item !== []) {
        // echo '<pre>';print_r($db_item);echo '</pre>';die('Process stopped in file ' . __FILE__ . ' at line ' . __LINE__);
        $edit = true;
        $page = $db_item['page'];
        $type = $db_item['type'];

        // Help the process_custom_fields_submission() fill in the form
        if (!$save) {
            $content = json_decode($db_item['content'], true);
            $_GET['richtext'] = $content['text-content'];
        }
    }
}

// Content always belongs to a page
$page ??= (int) getval('page', 0, false, 'is_positive_int_loose');
$all_pages = array_column(array_filter(get_all_pages(), __NAMESPACE__ . '\filter_only_pages'), null, 'ref');
if ($page === 0 && $all_pages !== []) {
    $page = (int) array_key_first($all_pages);
} elseif (!isset($all_pages[$page])) {
    http_response_code(400);
    exit(escape(str_replace('%key', 'page', $GLOBALS['lang']['error-request-missing-key'])));
}
$page_contents_db = get_page_contents($page);

$type ??= (int) getval(
    'type',
    BRAND_GUIDELINES_CONTENT_TYPES['text'],
    false,
    fn($V) => in_array($V, BRAND_GUIDELINES_CONTENT_TYPES)
);

// Page setup
$page_def = [
    BRAND_GUIDELINES_CONTENT_TYPES['text'] => [
        'title' => [
            'new' => $lang['brand_guidelines_title_new_text'],
            'edit' => $lang['brand_guidelines_title_edit_text'],
        ],
        'fields' => [
            [
            'id'       => 'richtext',
            'title'    => $lang['text'],
            'type'     => FIELD_TYPE_TEXT_RICH,
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
    $item = [
        'type' => $type,
        'fields' => $processed_fields,
    ];

    if (
        ($edit && save_page_content($ref, $item))
        || (!$edit && create_page_content($page, $item))
    ) {
        js_call_CentralSpaceLoad(
            generateURL("{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php", ['spage' => $page])
        );
    }

    error_alert($lang['error_fail_save'], true, 200);
    exit();
} elseif ($delete > 0 && enforcePostRequest(false)) {
    $db_item = get_page_content_item($delete);
    if ($db_item !== [] && delete_page_content([$delete])) {
        js_call_CentralSpaceLoad(
            generateURL(
                "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php",
                ['spage' => $db_item['page']]
            )
        );
    }

    error_alert($lang['error-failed-to-delete'], true, 200);
    exit();
} elseif ($reorder !== '' && enforcePostRequest(false)) {
    if ($edit) {
        // Remap left/right direction to up/down where the item supports it
        if ($reorder === 'left') {
            $reorder = 'up';
        } elseif ($reorder === 'right') {
            $reorder = 'down';
        }

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
    error_alert($lang['error-failed-to-move'], true, 200);
    exit();
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
        render_hidden_input('page', (string) $page);
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
<script>
tinymce.remove();
tinymce.init({
    selector: '#manage_content .Question textarea#richtext',
    width: 900,
    height: 400,
    license_key: 'gpl',
    promotion: false,
    branding: false,
    plugins: 'lists, link',
    toolbar: 'h2 h3 bold italic underline strikethrough removeformat | bullist numlist link | outdent indent',
    // toolbar1: 'h2 h3 bold italic underline strikethrough',
    // toolbar2: 'bullist numlist link | outdent indent removeformat',
    menubar: '',
    setup: (editor) => {
        editor.on('blur', (e) => tinymce.triggerSave());
    },
});
</script>
<?php
include_once RESOURCESPACE_BASE_PATH . '/include/footer.php';
