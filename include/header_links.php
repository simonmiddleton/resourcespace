<?php

$nav_links = [];
$contact_us_link = $nav2contact_link ? [['title' => '(lang)contactus', 'link' => "{$baseurl}/pages/contact.php"]] : [];

if ($advanced_search_nav) {
    $nav_links[] = [
        'title' => '(lang)advancedsearch',
        'link' => "{$baseurl}/pages/search_advanced.php",
    ];
}

if ($search_results_link) {
    if (
        checkperm("s")
        && (
            isset($_COOKIE["search_form_submit"])
            || (isset($_COOKIE["search"]) && strlen($_COOKIE["search"]) > 0)
            || (isset($search) && strlen($search) > 0 && strpos($search, "!") === false)
        )
    ) {
        $nav_links[] = [
            'title' => '(lang)searchresults',
            'link' => "{$baseurl}/pages/search.php",
        ];
    } else {
        $nav_links[] = [
            'title' => '(lang)searchresults',
            'link' => '#',
        ];
    }
}

if (checkperm("s") && $enable_themes && !$theme_direct_jump && $themes_navlink) {
    $nav_links[] = [
        'title' => '(lang)themes',
        'link' => "{$baseurl}/pages/collections_featured.php",
    ];
}

if (checkperm("s") && ($public_collections_top_nav)) {
    $nav_links[] = [
        'title' => '(lang)publiccollections',
        'link' => "{$baseurl}/pages/collection_public.php",
    ];
}

if (checkperm("s") && $mycollections_link && !checkperm("b")) {
    $nav_links[] = [
        'title' => '(lang)mycollections',
        'link' => "{$baseurl}/pages/collection_manage.php",
    ];
}

if (checkperm("s") && $recent_link) {
    $recent_url_params = $recent_search_by_days
        ? [
            "search" => "",
            "recentdaylimit" => $recent_search_by_days_default,
        ]
        : ["search" => "!last{$recent_search_quantity}"];
    $recent_url_params["order_by"]  = "resourceid";
    $recent_url_params["sort"]      = "desc";
    $recenturl = generateURL("$baseurl/pages/search.php", $recent_url_params);

    $nav_links[] = [
        'title' => '(lang)recent',
        'link' => $recenturl,
    ];
}

if (checkperm("s") && $myrequests_link && checkperm("q")) {
    $nav_links[] = [
        'title' => '(lang)myrequests',
        'link' => "{$baseurl}/pages/requests.php",
    ];
}

if (checkperm("d") || ($mycontributions_link && checkperm("c"))) {
    $nav_links[] = [
        'title' => '(lang)mycontributions',
        'link' => "{$baseurl}/pages/contribute.php",
    ];
}

if ($research_request && $research_link && checkperm("s") && checkperm("q")) {
    $nav_links[] = [
        'title' => '(lang)researchrequest',
        'link' => "{$baseurl}/pages/research_request.php",
    ];
}

$nav_links = array_merge($nav_links, $custom_top_nav ?? [], $contact_us_link);
?>
<nav class="primary-navigation" aria-label="<?php echo escape(text('mainmenu')); ?>">
    <ul>
    <?php
        foreach ($nav_links as $nav) {
            if (!is_safe_url($nav['link'])) {
                debug("Unsafe link detected in configuration - {$nav['link']}");
                continue;
            }

            // External links should open in a new tab
            if (!url_starts_with($baseurl, $nav['link'])) {
                $on_click = '';
                $target   = ' target="_blank"';
            }
            //Internal links can still open in the same tab
            else {
                if (isset($nav['modal']) && $nav['modal']) {
                    $on_click = ' onClick="return ModalLoad(this, true);"';
                    $target   = '';
                } elseif (!isset($nav['modal']) || (isset($nav['modal']) && !$nav['modal'])) {
                    $on_click = ' onClick="return CentralSpaceLoad(this, true);"';
                    $target   = '';
                }
            }

            if (strpos($nav['title'], '(lang)') !== false) {
                $custom_top_nav_title = str_replace("(lang)", "", $nav["title"]);
                $nav["title"] = $lang[$custom_top_nav_title];
            }
            ?>
            <li>
                <a href="<?php echo $nav["link"]; ?>"<?php echo $target . $on_click; ?>><?php
                    echo escape(i18n_get_translated($nav["title"]));
                ?></a>
            </li>
            <?php
        }
    ?>
    </ul>
    <button type="button" class="icon-ellipsis-vertical"></button>
</nav>