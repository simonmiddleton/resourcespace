<?php
function HookTransformTermsTerms_save_input_attributes($resource, $url)
    {
    if(!$GLOBALS['terms_download'])
        {
        return false;
        }

    // Ensure this kicks off only for transform crop page
    if(substr($url, 0, 33) !== '/plugins/transform/pages/crop.php')
        {
        return false;
        }

    $url = "{$GLOBALS['baseurl']}{$url}";

    echo " onclick=\"transform_download_file({$resource}, '{$url}');\"";
    }