<?php
function HookBrand_guidelinesAllInitialise()
    {
    include_once dirname(__DIR__) . '/include/brand_guidelines_functions.php';
    }

function HookBrand_guidelinesAllHandleuserref() {
    if (acl_can_view_brand_guidelines()) {
        $GLOBALS['custom_top_nav'][] = [
            'title' => '<i aria-hidden="true" class="fa-fw fa-solid fa-list-check"></i>&nbsp;' . $GLOBALS['lang']['brand_guidelines_top_nav_title'],
            'link' => "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php",
        ];
    }
}
