<?php
include '../../include/db.php';
include '../../include/authenticate.php';
if(!acl_can_manage_tabs()) { exit($lang['error-permissiondenied']); }



// [Sorting functionality]
$tab_orderby = getval('tab_orderby', 'ref');
$tab_sort = (strtoupper(getval('tab_sort', 'ASC')) === 'DESC') ? 'DESC' : 'ASC';


// [Paging functionality]
$per_page = (int) getval('per_page', $default_perpage_list, true);
$list_display_array[] = $default_perpage_list;
$list_display_array[] = $default_perpage;
if($per_page === 99999)
    {
    // all results option - see render_table()
    $list_display_array['all'] = 99999;
    $allow_reorder = true;
    }
$list_display_array = array_unique($list_display_array);
natsort($list_display_array);
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
    'class' => 'SystemTabs',
    'headers' => [
        'reorder_handle' => ['name' => '', 'sortable' => false, 'html' => true],
        'ref' => ['name' => $lang['property-reference'], 'sortable' => true],
        'name' => ['name' => $lang['name'], 'sortable' => false, 'html' => true, 'width' => '50%'],
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
    ],

    'data' => [
        // 'modal' => false,
    ],
];


foreach($tab_records['data'] as $tab_record)
    {
    $tab_record['reorder_handle'] = isset($allow_reorder) ? '<i class="fas fa-sort"></i>' : '';
    $tab_record['name'] = sprintf(
        '<span>%s</span><input name="tab_name_inline_edit_%s" type="text" class="DisplayNone" value="%s">',
        htmlspecialchars(i18n_get_translated($tab_record['name'])),
        escape_quoted_data($tab_record['ref']),
        escape_quoted_data($tab_record['name'])
    );
    $tab_record['usage'] = sprintf(
        '%s %s, %s %s',
        $tab_record['usage_rtf'],
        mb_strtolower($lang['admin_resource_type_fields']),
        $tab_record['usage_rt'],
        mb_strtolower($lang['resourcetypes'])
    );

    // Allow users to delete tabs except the Default one which is always ID #1 (created by dbstruct).
    if($tab_record['ref'] > 1)
        {
        $tab_record['tools'] = [
            [
                'icon' => 'fa fa-fw fa-trash',
                'text' => $lang['action-delete'],
                'url' => '#',
                'modal' => false,
                'onclick' => "return delete_tabs(this, [{$tab_record['ref']}]);",
            ],
        ];
        }

    $tab_record['tools'][] = [
        'icon' => 'fa fa-fw fa-edit',
        'text' => $lang['action-edit'],
        'url' => '#',
        'modal' => false,
        'onclick' => "return update_tab(this, {$tab_record['ref']}, \"init_edit\");"
    ];

    // Save & Cancel buttons for when editing a tab record
    $tab_record['tools'][] = [
        'icon' => 'fa fa-solid fa-xmark',
        'text' => $lang['cancel'],
        'url' => '#',
        'url:class' => 'DisplayNone',
        'modal' => false,
        'onclick' => "return update_tab(this, {$tab_record['ref']}, \"cancel\");"
    ];
    $tab_record['tools'][] = [
        'icon' => 'fa fa-regular fa-floppy-disk',
        'text' => $lang['save'],
        'url' => '#',
        'url:class' => 'DisplayNone',
        'modal' => false,
        'onclick' => "return update_tab(this, {$tab_record['ref']}, \"save\");"
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
    <p><?php echo htmlspecialchars($lang['manage_tabs_instructions']); render_help_link('systemadmin/manage-tabs'); ?></p>

    <?php echo render_table($table_info); ?>
    <!-- TODO: add form to create new tabs -->
</div>
<script>
// Re-order capability
jQuery(function() {
    // Disable for touch screens
    if(is_touch_device())
        {
        return false;
        }

    // Make all table rows sortable (except the header)
    jQuery('.BasicsBox .Listview.SystemTabs > table').sortable({
        items: 'tr:not(:first-child)',
        handle: 'td > i.fa-sort',
        containment: 'div.SystemTabs > table',
        update: function(event, ui)
            {
            let tabs_new_order = jQuery(event.target)
                .find('tr:not(:first-child) > td:nth-child(2)')
                .map((i, val) => parseInt(jQuery(val).text())).get();
            console.debug('tabs_new_order=%o', tabs_new_order);
            api('reorder_tabs', {'refs': tabs_new_order});
            }
    });
});

function delete_tabs(el, refs)
    {
    if(confirm('<?php echo escape_quoted_data($lang["confirm-deletion"]); ?>'))
        {
        api('delete_tabs', {'refs': refs}, function(successful)
            {
            if(successful)
                {
                // Remove row from table
                jQuery(el).parents('tr').remove();
                }
            else
                {
                styledalert("<?php echo escape_quoted_data($lang["error"]); ?>", "<?php echo escape_quoted_data($lang["error-failed-to-delete"]); ?>");
                }
            });
        };

    return false;
    }

function update_tab(el, ref, action)
    {
    console.log('%o for ref = %o', action, ref);
    let el_obj = jQuery(el);
    let record = el_obj.parents('tr');
    let tools = el_obj.parents('div.ListTools');
    let tools_edit_save_cancel = tools.find('a span.fa-edit, a span.fa-floppy-disk, a span.fa-xmark').parents('a');

    let record_name_inline_edit = record.find('input[name="tab_name_inline_edit_' + ref + '"');
    let record_name_translated = record_name_inline_edit.siblings().first();

    if(action === 'init_edit')
        {
        // Hide the translated tab name and show the inline edit input
        record_name_translated.toggleClass('DisplayNone');
        record_name_inline_edit.toggleClass('DisplayNone');

        // Hide the edit tool and show the Save & Cancel ones
        tools_edit_save_cancel.toggleClass('DisplayNone');
        }
    else if(action === 'save')
        {
        api(
            'save_tab',
            {
                tab: {
                    ref: ref,
                    name: record_name_inline_edit.val()
                }
            },
            function(response) {
                if(response.status === 'success')
                    {
                    record_name_translated.text(response.data.name_translated);
                    }
                else
                    {
                    styledalert("<?php echo escape_quoted_data($lang["error"]); ?>", response.data.message);
                    }
            });


        // Show the translated tab name and hide the inline edit input
        record_name_translated.toggleClass('DisplayNone');
        record_name_inline_edit.toggleClass('DisplayNone');

        // Show the edit tool and hide the Save & Cancel ones
        tools_edit_save_cancel.toggleClass('DisplayNone');
        }
    else if(action === 'cancel')
        {
        // Show the translated tab name and hide the inline edit input
        record_name_translated.toggleClass('DisplayNone');
        record_name_inline_edit.toggleClass('DisplayNone');

        // Show the edit tool and hide the Save & Cancel ones
        tools_edit_save_cancel.toggleClass('DisplayNone');
        }
    }
</script>
<?php
include '../../include/footer.php';