<?php
include '../../include/db.php';
include '../../include/authenticate.php';
if(!checkperm('a')) { exit($lang['error-permissiondenied']); }



// [Sorting functionality]
$tab_orderby = getval('tab_orderby', 'ref');
$tab_sort = (strtoupper(getval('tab_sort', 'ASC')) === 'DESC') ? 'DESC' : 'ASC';


// [Paging functionality]
$per_page = (int) getval('per_page', $default_perpage_list, true);
$per_page = in_array($per_page, $list_display_array) ? $per_page : $default_perpage;
rs_setcookie('per_page', $per_page);
$offset = (int) getval('offset', 0, true);
$tab_records = get_tabs_with_usage_count($per_page, $offset);
$totalpages = ceil($tab_records['total'] / $per_page);
$curpage = floor($offset / $per_page) + 1;


$request_params = [
    // 'tab_ref' => $tab_ref,
    'tab_orderby'  => $tab_orderby,
    'tab_sort' => $tab_sort,
];




$table_info = [
    // 'class' => '',
    'headers' => [
        'ref' => ['name' => $lang['property-reference'], 'sortable' => true],
        'name' => ['name' => $lang['name'], 'sortable' => false, 'width' => '50%'],
        'usage' => ['name' => $lang['usage'], 'sortable' => false],
        'tools' => ['name' => $lang['tools'], 'sortable' => false, 'width' => '20%']
    ],

    'orderbyname' => 'tab_orderby',
    'orderby' => $tab_orderby,
    'sortname' => 'tab_sort',
    'sort' => $tab_sort,

    'defaulturl' => "{$baseurl}/pages/admin/tabs.php",
    'params' => $request_params,
    'pager' => [
        'current' => $curpage,
        'total' => $totalpages,
        'per_page' => $per_page,
        'break' => false, # TODO: remove, doesn't seem to be needed
    ],

    'data' => [
        // 'modal' => false,
    ],
];


foreach($tab_records['data'] as $tab_record)
    {
    $tab_record['name'] = i18n_get_translated($tab_record['name']);
    $tab_record['usage'] = sprintf(
        '%s %s, %s %s',
        $tab_record['usage_rtf'],
        mb_strtolower($lang['admin_resource_type_fields']),
        $tab_record['usage_rt'],
        mb_strtolower($lang['resourcetypes'])
    );
    $tab_record['tools'] = [
        // TODO: N/A for the tab ID #1 (default can't be deleted)
        [
        'icon' => 'fa fa-fw fa-trash',
        'text' => $lang['action-delete'],
        'url' => '#',
        'modal' => false,
        'onclick' => "update_tab(\"{$tab_record['ref']}\", \"delete_tab\");"
        ],
        [
        'icon' => 'fa fa-fw fa-edit',
        'text' => $lang['action-edit'],
        'url' => '#',
        'modal' => false,
        'onclick' => "update_tab(\"{$tab_record['ref']}\", \"edit_tab\");"
        ],
    ];

    $table_info['data'][] = $tab_record;
    }




include '../../include/header.php';
?>
<div class="BasicsBox">
    <?php
    renderBreadcrumbs([
        ['title' => $lang['systemsetup'], 'href'  => "{$baseurl_short}pages/admin/admin_home.php"],
        ['title' => $lang['system_tabs']]
    ]); ?>
    <p><?php echo $lang['manage_tabs_instructions']; render_help_link('systemadmin/manage-tabs'); ?></p>

    <?php echo render_table($table_info); ?>
</div>
<?php
include '../../include/footer.php';