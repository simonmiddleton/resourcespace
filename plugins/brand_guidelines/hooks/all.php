<?php

declare(strict_types=1);

use function Montala\ResourceSpace\Plugins\BrandGuidelines\acl_can_view_brand_guidelines;

function HookBrand_guidelinesAllInitialise()
    {
    $plugin_root = dirname(__DIR__);
    include_once "{$plugin_root}/include/brand_guidelines_functions.php";
    include_once "{$plugin_root}/include/database_functions.php";
    include_once "{$plugin_root}/include/render_functions.php";
    
    /** Types of content you can add to brand guideline pages */
    define('BRAND_GUIDELINES_CONTENT_TYPES', [
        'text' => 0,
        'resource' => 1,
        'colour' => 2,
        'group' => 100, # internal use by group_content_items()
    ]);
    define('BRAND_GUIDELINES_DB_COLS_PAGES', columns_in('brand_guidelines_pages', null, 'brand_guidelines'));
    define('BRAND_GUIDELINES_DB_COLS_CONTENT', columns_in('brand_guidelines_content', null, 'brand_guidelines'));
    define(
        'BRAND_GUIDELINES_DEFAULT_IMAGE_SIZES',
        array_diff_key(
            array_column(get_all_image_sizes(true), 'name', 'id'),
            array_flip(['col', 'thm', 'hpr'])
        )
    );
    define(
        'BRAND_GUIDELINES_URL_MANAGE_CONTENT',
        "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/manage/content.php"
    );

    // Custom field types - see HookBrand_guidelinesContentRender_custom_fields_default_case_override()
    define('FIELD_TYPE_NUMERIC', 101);
    define('FIELD_TYPE_TEXT_RICH', 102);
    define('FIELD_TYPE_COLOUR_PREVIEW', 103);
    }

function HookBrand_guidelinesAllHandleuserref() {
    if (acl_can_view_brand_guidelines()) {
        $GLOBALS['custom_top_nav'][] = [
            'title' => '<i aria-hidden="true" class="fa-fw fa-solid fa-list-check"></i>&nbsp;' . $GLOBALS['lang']['brand_guidelines_top_nav_title'],
            'link' => "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php",
        ];
    }
}

function HookBrand_guidelinesAllModify_redirecturl()
{
    $redirecturl = getval('redirecturl', '');
    if (mb_strcut($redirecturl, 0, mb_strlen(BRAND_GUIDELINES_URL_MANAGE_CONTENT)) === BRAND_GUIDELINES_URL_MANAGE_CONTENT) {
        return $redirecturl;
    } else {
        return false;
    }
}

function HookBrand_guidelinesAllPostUploadActions_before_csl_redirurl_js(): void
{
    /*
    IMPORTANT: Directly within Javascript world on the upload_batch page!

    The logic will modify the "redirurl" URL and inject the newly uploaded resource ID required by the plugin.
    */
    printf('<!-- Logic for %s -->%s', escape(__FUNCTION__), PHP_EOL);
    ?>
    if (redirurl.startsWith('<?php echo BRAND_GUIDELINES_URL_MANAGE_CONTENT; ?>')) {
        console.debug('HookBrand_guidelinesAllPostUploadActions_before_csl_redirurl_js specific logic ...');
        redirurl += `&w_ref=${
            resource_keys
                .map((v) => parseInt(v, 10))
                .find((v) => v > 0)
        }`;
        CentralSpaceHideProcessing();
        rscompleted = [];
        processerrors = [];
        return ModalLoad(redirurl, true, true);
    }
    <?php
    printf('<!-- Logic (end) for %s -->%s', escape(__FUNCTION__), PHP_EOL);
}
