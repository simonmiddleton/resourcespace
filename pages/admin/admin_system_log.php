<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";

if (!checkperm_user_edit($userref))
	{
	redirect($baseurl_short ."login.php?error=error-permissions-login&url={$baseurl_short}pages/admin/admin_system_log.php");
	exit;
	}

$log_search = getval("log_search", "");
$backurl = getval("backurl", "");
$actasuser = getval('actasuser', $userref, true);

// Filter by a particular table and its reference
$table = getval('table', '');
$table_reference = getval('table_reference', 0, true);
$tables_data = array(
    'resource_type_field' => array(
        'display_title' => $lang['field'],
        'title_column' => 'title',
        'get_data_function' => 'get_resource_type_field',
        'get_data_function_params' => array($table_reference),
    ),
    'user' => array(
        'display_title' => $lang['user'],
        'title_column' => 'fullname',
        'get_data_function' => 'get_user',
        'get_data_function_params' => array($table_reference),
    ),
);

$log_tables_where_statements = array(
    'activity_log' => "`activity_log`.`user`='{$actasuser}' AND ",
    'resource_log' => "`resource_log`.`user`='{$actasuser}' AND ",
    'collection_log' => "`collection_log`.`user`='{$actasuser}' AND ",
);

// Paging functionality
$url = generateURL("{$baseurl_short}pages/admin/admin_system_log.php",
    array(
        'log_search' => $log_search,
        'backurl' => $backurl,
        'actasuser' => $actasuser,
        'table' => $table,
        'table_reference' => $table_reference,
    )
);
$offset = (int) getval('offset', 0, true);
$per_page = (int) getval('per_page_list', $default_perpage_list, true);
$all_records = count(get_activity_log($log_search, NULL, NULL, $log_tables_where_statements, $table, $table_reference));
$totalpages = ceil($all_records / $per_page);
$curpage = floor($offset / $per_page) + 1;
$jumpcount = 0;
// End of paging functionality

include "../../include/header.php";
?>
<div class="BasicsBox">
<?php
if($backurl != "")
    {
    $backurl_text = (strpos($backurl, "team_user_edit") !== false ? $lang["edituser"] : $lang["manageusers"]);
    ?>
    <p>
        <a href="<?php echo $backurl; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK . $backurl_text; ?></a>
    </p>
    <?php
    }

$title = $lang["systemlog"];
if($table != '' && $table_reference > 0 && array_key_exists($table, $tables_data))
    {
    $table_data = $tables_data[$table];
    $table_reference_data = call_user_func_array($table_data['get_data_function'], $table_data['get_data_function_params']);

    if($table_reference_data !== false)
        {
        $title .= " - {$table_data['display_title']}: {$table_reference_data[$table_data['title_column']]}";
        }
    }
?>
    <h1><?php echo htmlspecialchars($title); ?>
        <form class="ResultsFilterTopRight" method="get">
            <input type="hidden" name="actasuser" value="<?php echo $actasuser; ?>">
            <input type="hidden" name="backurl" value="<?php echo $backurl; ?>">
            <input type="text" name="log_search" placeholder="<?php echo htmlspecialchars($log_search); ?>">
            <input type="submit" name="searching" value="<?php echo htmlspecialchars($lang["searchbutton"]); ?>">
        <?php
        if($log_search != "")
            {
            ?>
            <input type="submit" name="clear_search" value="<?php echo htmlspecialchars($lang["clearbutton"]); ?>">
            <?php
            }
            ?>
        </form>
    </h1>

<?php
if($table == '' && $table_reference == 0)
    {
    $select_table_url = generateURL(
        "{$baseurl_short}pages/admin/admin_system_log.php",
        array(
            'log_search' => $log_search,
            'backurl' => $backurl,
            'actasuser' => $actasuser
        ));
    ?>
    <form id="TableFilterForm" method="get" action="<?php echo $select_table_url; ?>">
        <?php generateFormToken('TableFilterForm'); ?>
        <select name="table" onchange="return CentralSpacePost(document.getElementById('TableFilterForm'));">
            <option value=""><?php echo $lang['filter_by_table']; ?></option>
            <?php
            foreach($tables_data as $select_table => $select_table_data)
                {
                ?>
                <option value="<?php echo $select_table; ?>"><?php echo $select_table; ?></option>
                <?php
                }
                ?>
        </select>
    </form>
    <?php
    }
    ?>
    <div class="TopInpageNav">
        <div class="TopInpageNavLeft"></div>
        <span class="TopInpageNavRight">
        <?php pager(false); ?>
        </span>
        <div class="clearerleft"></div>
    </div>
    <div class="Listview">
        <table class="ListviewStyle" border="0" cellspacing="0" cellpadding="5">
            <tbody>
                <tr class="ListviewTitleStyle">
                    <td><?php echo $lang['fieldtype-date_and_time']; ?></td>
                    <td><?php echo $lang['user']; ?></td>
                    <td><?php echo $lang['property-operation']; ?></td>
                    <td><?php echo $lang['fieldtitle-notes']; ?></td>
                    <td><?php echo $lang['property-resource-field']; ?></td>
                    <td><?php echo $lang['property-old_value']; ?></td>
                    <td><?php echo $lang['property-new_value']; ?></td>
                    <td><?php echo $lang['difference']; ?></td>
                    <?php
                    if($table == '' || $table_reference == 0)
                        {
                        ?>
                        <td><?php echo $lang['property-table']; ?></td>
                        <?php
                        }
                        ?>
                    <td><?php echo $lang['property-column']; ?></td>
                    <?php
                    if($table == '' || $table_reference == 0)
                        {
                        ?>
                        <td><?php echo $lang['property-table_reference']; ?></td>
                        <?php
                        }
                        ?>
                </tr>
            <?php
            foreach(get_activity_log($log_search, $offset, $per_page, $log_tables_where_statements, $table, $table_reference) as $record)
                {
                ?>
                <tr>
                    <td><?php echo $record['datetime']; ?></td>
                    <td><?php echo $record['user']; ?></td>
                    <td><?php echo $record['operation']; ?></td>
                    <td><?php echo $record['notes']; ?></td>
                    <td><?php echo $record['resource_field']; ?></td>
                    <td><?php echo $record['old_value']; ?></td>
                    <td><?php echo $record['new_value']; ?></td>
                    <td><?php echo $record['difference']; ?></td>
                    <?php
                    if($table == '' || $table_reference == 0)
                        {
                        ?>
                        <td><?php echo $record['table']; ?></td>
                        <?php
                        }
                        ?>
                    <td><?php echo $record['column']; ?></td>
                    <?php
                    if($table != '' && $table_reference == 0 && array_key_exists($record['table'], $tables_data))
                        {
                        $record_table_data = $tables_data[$record['table']];
                        $record_table_reference_data = call_user_func_array(
                            $record_table_data['get_data_function'],
                            array($record['table_reference']));

                        if($record_table_reference_data !== false)
                            {
                            ?>
                            <td><?php echo htmlspecialchars($record_table_reference_data[$record_table_data['title_column']]); ?></td>
                            <?php
                            }
                        }
                    else if($table == '' || $table_reference == 0)
                        {
                        ?>
                        <td><?php echo $record['table_reference']; ?></td>
                        <?php
                        }
                        ?>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div><!-- end of ListView -->

    <div class="BottomInpageNav">
        <div class="BottomInpageNavRight">  
        <?php pager(false, false); ?>
        </div>
    </div>
</div> <!-- End of BasicBox -->
<?php
include "../../include/footer.php";