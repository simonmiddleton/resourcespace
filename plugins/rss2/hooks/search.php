<?php
function HookRss2SearchRender_search_actions_add_option($options)
    {
    global $baseurl_short, $search, $restypes, $archive, $starsearch, $lang, $username, $userref, $api_scramble_key, $k;
    
    $c = count($options);
    
    if ($k=='')
        {   
        $querystring = "user=" . base64_encode($username) . "&search=" . $search . "&restypes=" . $restypes . "&archive=" . $archive . "&starsearch=" . $starsearch;
        $private_key = get_api_key($userref);

        // Sign the query using the private key
        $sign = hash("sha256",$private_key . $querystring);
        $url = $baseurl_short . "plugins/rss2/pages/rssfilter.php?" . $querystring  . "&sign=" . urlencode($sign);

        $data_attribute['url'] = $url;
        $data_attribute['no-ajax'] = true;
        $options[$c]['value'] = 'rss';
        $options[$c]['label'] = $lang["rss_feed_for_search_filter"];
        $options[$c]['data_attr'] = $data_attribute;
        $options[$c]['category'] = ACTIONGROUP_ADVANCED;
        $options[$c]['order_by'] = 500;

        return $options;
        }
    }
