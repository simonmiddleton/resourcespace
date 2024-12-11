<?php
function HookFormat_chooserViewAppend_to_download_filename_td(array $resource, string $ns)
{
    if (failed_format_chooser_checks($resource)) {
        return false;
    }

    // IMPORTANT: the namespace variables (i.e. "ns") exist in both PHP and JS worlds and are generated
    // by render_resource_tools_size_download_options() which are then relied upon on the view page.
    ?>
    <select id="<?php echo escape($ns); ?>format"><?php
    foreach ($GLOBALS['format_chooser_output_formats'] as $format) {
        echo render_dropdown_option(
            $format,
            str_replace_formatted_placeholder('%extension', $format, $GLOBALS['lang']['field-fileextension']),
            [],
            $format === getDefaultOutputFormat($resource['file_extension']) ? 'selected' : ''
        );
    }
    ?></select>
    <?php
    showProfileChooser('', false, $ns);
}

function HookFormat_chooserViewAppend_to_resource_tools_size_download_options_script(string $ns, array $allowed_sizes, array $resource)
{
    if (failed_format_chooser_checks($resource)) {
        return false;
    }

    // IMPORTANT: Directly within Javascript world on the view page (via render_resource_tools_size_download_options())!

    if (count($allowed_sizes) > 1) {
        // Size selector available
        ?>
        jQuery('select#<?php echo escape($ns); ?>format').change(function() {
            const picker = jQuery('select#<?php echo escape($ns); ?>size');
            updateDownloadLink('<?php echo escape($ns); ?>', picker.val(), picker);
        });
        jQuery('select#<?php echo escape($ns); ?>profile').change(function() {
            const picker = jQuery('select#<?php echo escape($ns); ?>size');
            updateDownloadLink('<?php echo escape($ns); ?>', picker.val(), picker);
        });
        <?php
        return;
    }

    // If only one size is available, there's no "size" to select from so ensure functions get called correctly to
    // the context.
    $selected_size = $allowed_sizes[array_key_first($allowed_sizes)]['id'];
    ?>
    jQuery('select#<?php echo escape($ns); ?>format').change(function() {
        updateDownloadLink(
            '<?php echo escape($ns); ?>',
            '<?php echo escape($selected_size); ?>',
            jQuery('.Picker #<?php echo escape($ns); ?>sizeInfo')
        );
    });
    jQuery('select#<?php echo escape($ns); ?>profile').change(function() {
        updateDownloadLink(
            '<?php echo escape($ns); ?>',
            '<?php echo escape($selected_size); ?>',
            jQuery('.Picker #<?php echo escape($ns); ?>sizeInfo')
        );
    });
    <?php
}

function HookFormat_chooserViewAppend_to_updateDownloadLink_js(array $resource)
{
    global $baseurl;

    if (failed_format_chooser_checks($resource)) {
        return false;
    }


    /*
    IMPORTANT: Directly within Javascript world on the view page!

    The logic will modify the "downloadlink" URL and inject the users' selection (format & profile) required by the
    plugin.

    Use cases and URL placement (href/onclick attributes):
    - Download progress (normal download) - onclick=directDownload()
    - Request resource - href -> leave alone, no action required
    - Terms and Download usage - href -> the URL of interest is inside the "url" query string param.
    */
    ?>
    console.debug('HookFormat_chooserViewAppend_to_updateDownloadLink_js specific logic ...');
    const format = jQuery('select#' + ns + 'format').find(":selected").val().toLowerCase();
    const profile = jQuery('select#' + ns + 'profile').find(":selected").val();
    console.debug('HookFormat_chooserViewAppend_to_updateDownloadLink_js: format = %o', format);
    console.debug('HookFormat_chooserViewAppend_to_updateDownloadLink_js: profile = %o', profile);

    // Example (final) regex (simplified): /^directDownload\('(https\:\/\/localhost\S*)', this\)$/m
    const direct_dld_regex = new RegExp("^directDownload\\('(<?php echo preg_quote(parse_url($baseurl, PHP_URL_SCHEME)); ?>\\:\\\/\\\/<?php echo preg_quote(parse_url($baseurl, PHP_URL_HOST)); ?>\\S*)', this\\)$", 'm');
    const dld_btn_onclick = download_btn.attr('onclick');
    const dld_btn_href = download_btn.attr('href');

    if (dld_btn_href === '#' && direct_dld_regex.test(dld_btn_onclick)) {
        const orig_url = direct_dld_regex.exec(dld_btn_onclick)[1];
        let format_chooser_modified = new URL(orig_url);
        format_chooser_modified.searchParams.set('ext', format);
        if (profile) {
            format_chooser_modified.searchParams.set('profile', profile);
        }
        download_btn.attr('onclick', dld_btn_onclick.replace(orig_url, format_chooser_modified.toString()));
    } else if (
        dld_btn_href.startsWith('<?php echo "{$baseurl}/pages/download_usage.php"; ?>')
        || dld_btn_href.startsWith('<?php echo "{$baseurl}/pages/terms.php"; ?>')
    ) {
        const orig_url = new URL(dld_btn_href);
        let format_chooser_modified = new URL(dld_btn_href);
        let inner_url = new URL(orig_url.searchParams.get('url'));
        inner_url.searchParams.set('ext', format);
        if (profile) {
            inner_url.searchParams.set('profile', profile);
        }
        format_chooser_modified.searchParams.set('url', inner_url.toString());
        download_btn.prop('href', dld_btn_href.replace(orig_url, format_chooser_modified.toString()));
    }
    <?php
}

function HookFormat_chooserViewModifySizesArray ($resource, $sizes) : array 
    {
    return get_image_sizes($resource['ref'],false,$resource['file_extension'],false);
    }