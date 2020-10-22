<?php
include "../../include/db.php";

include "../../include/authenticate.php";

if (!checkperm_user_edit($userref))
	{
	redirect($baseurl_short ."login.php?error=error-permissions-login&url={$baseurl_short}pages/admin/admin_system_log.php");
	exit;
	}

$log_search = getval("log_search", "");
$backurl = getval("backurl", "");
$requesteduser = getval('actasuser',0, true);
$actasuser = $requesteduser === $userref ? $userref : $requesteduser;

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
    'resource' => array(
        'display_title' => "resource",
        'title_column' => 'field' . $view_title_field,
        'get_data_function' => 'get_resource_data',
        'get_data_function_params' => array($table_reference),
    ),
);

// TODO: over time, these can be put under tables_data once we can use the referenced information (ie. if there is a function to do so - see examples above)
$no_reference_data_tables = sql_array('
        SELECT DISTINCT remote_table AS "value"
          FROM activity_log
         WHERE remote_table IS NOT NULL AND remote_table <> ""
    ',
    "");

if(!checkperm('a') || $requesteduser == $actasuser && $requesteduser != 0)
    {
    $log_tables_where_statements = array(
        'activity_log' => "`activity_log`.`user`='{$actasuser}' AND ",
        'resource_log' => "`resource_log`.`user`='{$actasuser}' AND ",
        'collection_log' => "`collection_log`.`user`='{$actasuser}' AND ",
    );
    }
else
    {
    // Admins see all user activity by default
    $log_tables_where_statements = array(
        'activity_log' => "TRUE AND ",
        'resource_log' => "TRUE AND ",
        'collection_log' => "TRUE AND ",
    );   
    }

// Add date restriction
$curmonth   = date('m');
$curyear    = date('Y');
$logmonth   = getvalescaped("logmonth",($log_search != "" ? "" : $curmonth), true);
$logyear    = getvalescaped("logyear",($log_search != "" ? "" : $curyear), true);

// Add filtering if not searching
if($logmonth != 0 || $logyear != 0)
    {
    $monthstart = $logmonth == 0 ? 1 : $logmonth;
    $monthend = $logmonth == 0 ? 12 : $logmonth;
    $datevals = " BETWEEN CAST('$logyear-$monthstart-01' AS DATE) AND LAST_DAY(CAST('$logyear-$monthend-01' AS DATE)) ";
    $log_tables_where_statements['activity_log']    .= "(logged " . $datevals . ") AND ";
    $log_tables_where_statements['resource_log']    .= "(date " . $datevals . ") AND ";
    $log_tables_where_statements['collection_log']  .= "(date " . $datevals . ") AND ";
    }


// Paging functionality
$url = generateURL("{$baseurl_short}pages/admin/admin_system_log.php",
    array(
        'log_search' => $log_search,
        'backurl' => $backurl,
        'actasuser' => $requesteduser,
        'table' => $table,
        'table_reference' => $table_reference,
        'logmonth' => $logmonth,
        'logyear' => $logyear,
    )
);
$offset = (int) getval('offset', 0, true);
$per_page = (int) getval('per_page_list', $default_perpage_list, true);
$all_records = get_activity_log($log_search, NULL, NULL, $log_tables_where_statements, $table, $table_reference, true);
$totalpages = ceil($all_records / $per_page);
$curpage = floor($offset / $per_page) + 1;
$jumpcount = 0;
// End of paging functionality

include "../../include/header.php";
?>

<script>
jQuery(document).ready(function()
    {
    jQuery('#logyear').change(function()
        {
        if(jQuery(this).val()==0)
            {
            jQuery('#logmonth').val(0);
            }
        });
    jQuery('#logmonth').change(function()
        {
        if(jQuery(this).val()!=0 && jQuery('#logyear').val()==0)
            {
            jQuery('#logyear').val(<?php echo $curyear?>);
            }
        });
    });
</script>
<div class="BasicsBox">
<?php

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

if (strpos($backurl, "pages/team/team_user.php") !== false)
    {
    // Arrived from Manage users page
    $links_trail = array(
        array(
            'title' => $lang["teamcentre"],
            'href'  => $baseurl_short . "pages/team/team_home.php"
        ),
        array(
            'title' => $lang["manageusers"],
            'href'  => $backurl
        )
    );
    }
elseif (strpos($backurl, "pages/team/team_user_edit.php") !== false)
    {
    // Arrived from Edit user page
    $links_trail = array(
        array(
            'title' => $lang["teamcentre"],
            'href'  => $baseurl_short . "pages/team/team_home.php"
        ),
        array(
            'title' => $lang["manageusers"],
            'href'  => $baseurl_short . "pages/team/team_user.php"
        ),
        array(
            'title' => $lang["edituser"],
            'href'  => $backurl
        )
    );
    }

$links_trail[] = array(
    'title' => htmlspecialchars($title),
    'href'  => $baseurl_short . "pages/admin/admin_system_log.php"
);

renderBreadcrumbs($links_trail);
?>
    <h1>
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

$select_table_url = generateURL(
    "{$baseurl_short}pages/admin/admin_system_log.php",
    array(
        'log_search' => $log_search,
        'backurl' => $backurl,
        'actasuser' => $requesteduser
    ));
?>
<form id="TableFilterForm" method="get" action="<?php echo $select_table_url; ?>">
    <?php generateFormToken('TableFilterForm'); ?>



<div class="Question" id="QuestionFilter">
    <div class="SplitSearch">
        <select class="SplitSearch" id="logmonth" name="logmonth">
            <?php                
            // Not filtered by default when searching, add option to filter by month
            echo "<option " .  ($logmonth == "" ? " selected" : "") . " value='0'>" . $lang["anymonth"] . "</option>\n";                   
                for ($m=1;$m<=12;$m++)
                {
                echo "<option " .  ($m == $logmonth ? " selected" : "") . " value=\"" .  sprintf("%02d",$m) . "\">" . $lang["months"][$m-1] . "</option>\n";
                }
            ?>
        </select>    
    </div>

    <div class="SplitSearch" id="Questionyear">
        <select class="SplitSearch" id="logyear" name="logyear">
            <?php 
            // Not filtered by default when searching, add option to filter by month
                echo "<option " .  ($logyear == "" ? " selected" : "") . " value='0'>" . $lang["anyyear"] . "</option>\n";                   
            for ($n=$curyear;$n>=$minyear;$n--)
                {
                echo "<option " .  ($n == $logyear ? " selected" : "") . " value=\"" .  $n . "\">" . $n . "</option>\n";
                }
            ?>
        </select>    
    </div>   
    <?php
    if($table == "")
        {
        ?>
        <select class="SplitSearch" name="table">
            <option value=""><?php echo $lang['filter_by_table']; ?></option>
            <?php
            foreach($tables_data as $select_table => $select_table_data)
                {
                ?>
                <option value="<?php echo $select_table  . "\"" . ($select_table == $table ? " selected" : ""); ?>><?php echo $select_table; ?></option>
                <?php
                }

            foreach($no_reference_data_tables as $no_reference_data_table)
                {
                if(!isset($tables_data[$no_reference_data_table]))
                    {
                    ?>
                    <option value="<?php echo $no_reference_data_table  . "\"" . ($no_reference_data_table == $table ? " selected" : ""); ?>><?php echo $no_reference_data_table; ?></option>
                    <?php
                    }
                }
                ?>
        </select>
        <?php
        }
        ?>

<input type="button" id="datesubmit" class="searchbutton" value="<?php echo $lang['filterbutton']; ?>" onclick="return CentralSpacePost(document.getElementById('TableFilterForm'));">
<div class="clearerleft"></>
</div>

</form>


    <div class="TopInpageNav">
        <div class="TopInpageNavLeft">&nbsp;</div>
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
            $original_permitted_html_tags = $permitted_html_tags;
            $permitted_html_tags = array("html", "body");
            foreach(get_activity_log($log_search, $offset, $per_page, $log_tables_where_statements, $table, $table_reference) as $record)
                {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars(nicedate($record['datetime'], true, true, true)); ?></td>
                    <td><?php echo htmlspecialchars($record['user']); ?></td>
                    <td><?php echo htmlspecialchars($record['operation']); ?></td>
                    <td><?php echo hook("userdisplay","",array(array("access_key"=>$record['access_key'],'username'=>$record['user'])))?"":htmlspecialchars($record['notes']); ?></td>
                    <td><?php echo htmlspecialchars($record['resource_field']); ?></td>
                    <td><?php echo htmlspecialchars($record['old_value']); ?></td>
                    <td><?php echo htmlspecialchars($record['new_value']); ?></td>
                    <td><?php echo strip_tags_and_attributes($record['difference'], array("pre")); ?></td>
                    <?php
                    if($table == '' || $table_reference == 0)
                        {
                        ?>
                        <td><?php echo htmlspecialchars($record['table']); ?></td>
                        <?php
                        }
                        ?>
                    <td><?php echo htmlspecialchars($record['column']); ?></td>
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
                        $ref = htmlspecialchars($record['table_reference']);
                        
                        switch ($record['column'])
                            {
                            // if this is resource ref, then add link to view resource
                            case "ref":
                                if ($record['table'] == "resource") // only display links where ref field is in resource table
                                {
?>
                               
                            <td><a href="<?php echo "$baseurl/pages/view.php?search=&order_by=&ref=$ref" ?>" title="View resource" onclick="return ModalLoad(this,true);"><?php echo $ref ?></a></td>
                           
<?php
}
                            break;

                            default:
                                print "<td>$ref</td>";
                            break;


                            }
                        }
                        ?>
                </tr>
                <?php
                }
                $permitted_html_tags = $original_permitted_html_tags;
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
