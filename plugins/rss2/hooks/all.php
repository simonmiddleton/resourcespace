<?php

use Montala\ResourceSpace\UserInterfaceComponents\Icon;

function HookRss2AllInitialise()
    {
    global $rss_fieldvars;
    config_register_core_fieldvars("RSS2 plugin",$rss_fieldvars);
    }

function HookRss2AllPreheaderoutput()
    {
    if(!function_exists("get_api_key"))
        {
        include_once __DIR__ . "/../../../include/api_functions.php";
        }
    }

function HookRss2AllSearchbarbeforebottomlinks()
    {
    include_once __DIR__ . "/../../../include/api_functions.php";   
    global $baseurl,$lang,$username,$userref;
    
    $query = [
        'user' => base64_encode($username),
        'search' => '!last50',
    ];
    $url = generateURL(
        "{$baseurl}/plugins/rss2/pages/rssfilter.php",
        $query,
        ['sign' => hash('sha256', get_api_key($userref) . http_build_query($query))]
    );
    ?>
    <a href="<?php echo $url; ?>">
        <?php render_icon_wrapper_component(Icon::Rss); ?>
        <span><?php echo escape($lang['new_content_rss_feed']); ?></span>
    </a>
    <?php
    }

