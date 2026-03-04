<?php

/**
 * Nav link list. Each link follows the $custom_top_nav structure for consistency reasons.
 *
 * @var list<array{title: string, link: string, modal?: bool}>
 */
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

if (count($nav_links) > 3) {
    // $overflow_menu_btn = '<button type="button" class="icon-ellipsis-vertical"></button>';

}

// Group links into primary and overflown
$nav_groupings = count($nav_links) > 3 ? [array_slice($nav_links, 0, 3), array_slice($nav_links, 3)] : [$nav_links];
// $nav_groupings = [array_slice($nav_links, 0, 3)];
?>
<nav class="primary-navigation" aria-label="<?php echo escape(text('mainmenu')); ?>">
    <ul>
    <?php
        foreach ($nav_groupings as $idx => $nav_group_links) {
            $overflow = $idx === 1;

            if ($overflow) {
                echo '<span>';
            }

            foreach ($nav_group_links as $nav) {
                if (!url_starts_with($baseurl, $nav['link'])) {
                    // External links always open in a new tab
                    $on_click = '';
                    $target   = ' target="_blank" rel="noopener noreferrer"';
                } else {
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
                    <a href="<?php echo sanitise_url($nav["link"]); ?>"<?php echo $target . $on_click; ?>><?php
                        echo escape(i18n_get_translated($nav["title"]));
                    ?></a>
                </li>
                <?php
            }

            if ($overflow) {
                echo '</span>';
            }
        }
    ?>
    </ul>
    <button type="button" class="icon-ellipsis-vertical"></button>
</nav>