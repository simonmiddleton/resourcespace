<?php

#
# canva_user_consent setup page
#

include '../../../include/boot.php';
include '../../../include/authenticate.php';

if (!checkperm('a')) {
    exit(escape($lang['error-permissiondenied']));
}

global $baseurl;

// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'canva_user_consent';
if (!in_array($plugin_name, $plugins)) {
    plugin_activate_for_setup($plugin_name);
}

$plugin_page_heading = $lang['canva_user_consent_configuration'];
include '../../../include/header.php';
include_once '../include/canva_user_consent_functions.php';
$deleteaccess   = getval('rm', 0);

if ($deleteaccess) {
    $deleteref = getval('ref', '');
    delete_canva_users($deleteref);
}

$data = get_canva_users();
foreach ($data as &$td) {
    $editlink = generateURL(
        $baseurl . "/plugins/canva_user_consent/pages/setup.php",
        array(
            "ref" => $td['ref'],
            "rm"  => true,
        )
    );
    $td["tools"][] = array(
        "icon"    => "fas fa-trash",
        "text"    => $lang["action-delete"],
        "url"     => $editlink,
        "modal"   => false,
        "onclick" => "return CentralSpaceLoad(\"" . $editlink . "\");"
    );
}

unset($td);
$curpage = 1;
$totalpages = 0;
$per_page = 1000;
$curparams = array();
$tabledata = array(
    "class" => "ShareTable",
    "headers" => array(
        "ref" => array("name" => $lang["canva_user_consent_ref_id"],"sortable" => true),
        "canva_id" => array("name" => $lang["canva_user_consent_canva_user_id"],"sortable" => true),
        "last_used" => array("name" => $lang["lastused"],"sortable" => true),
        "hit" => array("name" => $lang["canva_user_consent_total_hits"],"sortable" => true),
        "tools" => array("name" => $lang["tools"],"sortable" => false)
        ),

    "orderbyname" => "ref",
    "orderby" => "desc",
    "sortname" => "share_sort",
    "sort" => "desc",

    "defaulturl" => $baseurl . "/plugins/canva_user_consent/pages/setup.php",
    "params" => $curparams,
    "data" => $data
    );

echo "<div id='share_list_container' class='BasicsBox'>\n";
echo '<h2>' . escape($lang["canva_user_consent_configuration"]) . '</h2>';
render_table($tabledata);
echo "\n</div><!-- End of BasicsBox -->\n";

include '../../../include/footer.php';
