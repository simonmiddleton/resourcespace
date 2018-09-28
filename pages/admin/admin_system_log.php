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

$log_tables_where_statements = array(
    'activity_log' => "`activity_log`.`user`='{$actasuser}' AND ",
    'resource_log' => "`resource_log`.`user`='{$actasuser}' AND ",
    'collection_log' => "`collection_log`.`user`='{$actasuser}' AND ",
);

// [Paging functionality]
$url = generateURL("{$baseurl_short}pages/admin/admin_system_log.php",
    array(
        'log_search' => $log_search,
        'backurl' => $backurl,
        'actasuser' => $actasuser,
    )
);
$offset = (int) getval('offset', 0, true);
$per_page = (int) getval('per_page_list', $default_perpage_list, true);
$all_records = count(get_activity_log($log_search, NULL, NULL, $log_tables_where_statements));
$totalpages = ceil($all_records / $per_page);
$curpage = floor($offset / $per_page) + 1;
$jumpcount = 0;

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
    ?>
    <h1><?php echo $lang["systemlog"]; ?>
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
                    <td><?php echo $lang['property-table']; ?></td>
                    <td><?php echo $lang['property-column']; ?></td>
                    <td><?php echo $lang['property-table_reference']; ?></td>
                </tr>
            <?php
            foreach(get_activity_log($log_search, $offset, $per_page, $log_tables_where_statements) as $record)
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
                    <td><?php echo $record['table']; ?></td>
                    <td><?php echo $record['column']; ?></td>
                    <td><?php echo $record['table_reference']; ?></td>
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