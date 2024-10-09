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

// Content (item) always has a type
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
    BRAND_GUIDELINES_CONTENT_TYPES['colour'] => [
        'title' => [
            'new' => $lang['colour'],
            'edit' => $lang['colour'],
        ],
        'fields' => [
            [
            'id'       => 'name',
            'title'    => $lang['name'],
            'type'     => FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
            'required' => true,
            ],
            [
            'id'       => 'hex',
            'title'    => $lang['brand_guidelines_hex'],
            'type'     => FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
            'required' => false,
            ],
            [
            'id'       => 'rgb',
            'title'    => $lang['brand_guidelines_rgb'],
            'type'     => FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
            'required' => false,
            ],
            [
            'id'       => 'cmyk',
            'title'    => $lang['brand_guidelines_cmyk'],
            'type'     => FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
            'required' => false,
            ],
        ],
    ],
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

        // find elements that need re-ordering
        $page_contents_db = array_column(get_page_contents($page), null, 'ref');
        // echo '<pre>';print_r($page_contents_db);echo '</pre>';die('Process stopped in file ' . __FILE__ . ' at line ' . __LINE__);

        // apply group logic (e.g. skip entire group) - if applicable
            // determine what kind of groups we have (resource/color) and how many items are in each group (for skipping calculations)

        reorder_items(
            'brand_guidelines_content',
            array_replace(
                $page_contents_db,
                // todo: add logic to jump over item groups (when relevant)
                [$ref => compute_item_order($page_contents_db[$ref], $reorder)]
            ),
            null
        );
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
    <div class="form-wrapper-<?php echo escape((string) $type); ?>">
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
    <div class="preview guidelines-colour-block--colour <?php echo (BRAND_GUIDELINES_CONTENT_TYPES['colour'] !== $type ? 'DisplayNone' : ''); ?>">Preview</div>
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

jQuery('#hex, #rgb, #cmyk').on('focusout', (e) => {
    const field_value = jQuery(e.target).val();
    if (field_value === '') {
        return;
    }

    // jQuery('.Question div.FormError').addClass('DisplayNone');
    jQuery('.Question div.FormError').remove();

    let background_colour = '';
    switch (e.target.id) {
        case 'hex':
            background_colour = `#${field_value}`;
            jQuery('#rgb').val(Object.values(hex_to_rgb(field_value)));
            jQuery('#cmyk').val(Object.values(hex_to_cmyk(field_value)));
            break;

        case 'rgb':
            const [r, g, b] = field_value.split(',');
            background_colour = rgb_to_hex(r, g, b);
            if (background_colour !== '') {
                jQuery('#hex').val(background_colour.substring(1));
                jQuery('#cmyk').val(Object.values(rgb_to_cmyk(r, g, b)));
            } else {
                show_form_error('#Question_rgb', '<?php echo escape($lang['brand_guidelines_err_invalid_input']); ?>');
            }
            break;

        case 'cmyk':
            const [c, m, y, k] = field_value.split(',');
            const rgb = cmyk_to_rgb(c, m, y, k);
            if (Object.keys(rgb).length !== 0) {
                background_colour = rgb_to_hex(rgb.r, rgb.g, rgb.b);
                jQuery('#hex').val(background_colour.substring(1));
                jQuery('#rgb').val(Object.values(rgb));
            } else {
                show_form_error('#Question_cmyk', '<?php echo escape($lang['brand_guidelines_err_invalid_input']); ?>');
            }
            break;
    }

    jQuery('.preview.guidelines-colour-block--colour').css('background-color', background_colour);
    console.log('field_value = %o', field_value);
    console.log('e.target.id = %o', e.target.id);
});

function show_form_error(selector, msg) {
    jQuery('<div>', { class: 'FormError' }).text(msg).appendTo(selector);
    // jQuery('<div>', { class: 'FormError DisplayNone' }).text(msg).appendTo(selector);
    // jQuery(selector).find('div.FormError').removeClass('DisplayNone');
}

function hex_to_rgb(hex) {
    // Expand short form, if applicable
    hex = hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, (m, r, g, b) => r + r + g + g + b + b);
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
    };
}

function rgb_to_hex(r, g, b) {
    return ([r, g, b].filter((i) => i >= 0 && i <= 255).length === 3)
        ? `#${(1 << 24 | r << 16 | g << 8 | b).toString(16).slice(1).toUpperCase()}`
        : '';
}

function hex_to_cmyk(hex) {
    const { r, g, b } = hex_to_rgb(hex);
    return rgb_to_cmyk(r, g, b);
}

function rgb_to_cmyk(red, green, blue) {
    if (red === 0 && green === 0 && blue === 0) {
        return { c: 0, m: 0, y: 0, k: 100 };
    }

    const R = red > 0 ? red / 255 : 0;
    const G = green > 0 ? green / 255 : 0;
    const B = blue > 0 ? blue / 255 : 0;
    const K = 1 - Math.max(R, G, B);
    return {
        c: Math.round(((1 - R - K) / (1 - K)) * 100),
        m: Math.round(((1 - G - K) / (1 - K)) * 100),
        y: Math.round(((1 - B - K) / (1 - K)) * 100),
        k: Math.round(K * 100),
    };
}

function cmyk_to_rgb(c, m, y, k) {
    if (([c, m, y, k].filter((i) => i >= 0 && i <= 100).length !== 4)) {
        return {};
    }
    const black = 1 - (k / 100);
    return {
        r: Math.round(255 * (1 - (c / 100)) * black),
        g: Math.round(255 * (1 - (m / 100)) * black),
        b: Math.round(255 * (1 - (y / 100)) * black),
    };
}
</script>
<?php
include_once RESOURCESPACE_BASE_PATH . '/include/footer.php';
