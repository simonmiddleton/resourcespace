<?php

declare(strict_types=1);

/**
 * Hooks onto the upload review workflow (for upload then edit mode) when "save and next" is carried out and adds the
 * first resource ID (which is supposed to be the last uploaded resource, for brand guidelines) to the manage content
 * callback (redirect URL).
 */
function HookBrand_guidelinesEditUploadreviewabortnext(): bool
{
    $redirect_url = $GLOBALS['urlparams']['redirecturl'] ?? '';
    $query_string = parse_url($redirect_url, PHP_URL_QUERY);
    if (!(url_starts_with(BRAND_GUIDELINES_URL_MANAGE_CONTENT, $redirect_url) && is_string($query_string))) {
        return false;
    }

    $qs_parts = [];
    parse_str($query_string, $qs_parts);

    if (!isset($qs_parts['w_ref'])) {
        $qs_parts['w_ref'] = $GLOBALS['ref'];
        $GLOBALS['urlparams']['redirecturl'] = str_replace($query_string, http_build_query($qs_parts), $redirect_url);
    }

    return false;
}
