<?php

function HookConditional_termsViewDownloadlink($baseparams, $view_in_browser=false)
    {
    global $baseurl, $resource, $conditional_terms_field, $conditional_terms_value, $fields, $search, $order_by, $archive, $sort, $offset, $download_usage;

    $showterms=false;
    $additional_params = array();

    $resource_value_to_test=trim( get_data_by_field($resource['ref'],$conditional_terms_field) );

    if( $conditional_terms_value==$resource_value_to_test )
        {
        $showterms=true;
        }

    if(!$showterms)
        {
        return false;
        }
    
    if (!$view_in_browser)
        {
        $redirect = "pages/download_progress.php";
        }
    else
        {
        $redirect = "pages/download.php";
        $additional_params = array(
            'direct' => '1',
            'noattach' => 'true',
            );
        }

    if ($download_usage)
        {
        $redirect = "pages/download_usage.php";
        }

    // Build return url
    $link_params = array();
    $baseparams = explode("&", $baseparams);
    foreach ($baseparams as $param)
        {
        $key_value = explode("=", $param);
        $link_params[$key_value[0]] = $key_value[1];
        }

    $redirect_params = $link_params;

    // Build redirect url
    $redirect_params = array_merge($redirect_params, array(
        'search' => $search,
        'offset' => $offset,
        'archive' => $archive,
        'sort' => $sort,
        'order_by' => $order_by
    ));

    $redirect_url = generateURL($redirect, $redirect_params, $additional_params);

    $link_params = array_merge($link_params, array('search' => $search, 'url' => $redirect_url));
    $return_url = generateURL($baseurl . '/pages/terms.php', $link_params, array('noredir' => 'true'));

    ?>href="<?php echo $return_url ;?>"<?php

    return true;
    }
