<?php

declare(strict_types=1);

function HookBrand_guidelinesUpload_batchPostUploadActions_before_csl_redirurl_js(): void
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
