<?php
#
# action_dates setup page
#

include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

$plugin_name = 'action_dates';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
	
// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'action_dates';
$plugin_page_heading = $lang['action_dates_configuration'];

$editable_states = array();
foreach(get_editable_states($userref) as $archive_state)
    {
    $editable_states[$archive_state['id']] = $archive_state['name'];
    }

$allowable_fields = get_resource_type_fields('','order_by','asc','',$DATE_FIELD_TYPES);

if(getval('submit', '') != '' || getval('save','') != '' && enforcePostRequest(false))
    {
    // Save the plugin config
    $action_dates_config["action_dates_deletefield"] = getvalescaped('action_dates_deletefield','',true);
    $action_dates_config["action_dates_reallydelete"] = getvalescaped('action_dates_reallydelete','');
    $action_dates_config["action_dates_new_state"] = getvalescaped('action_dates_new_state','',true);
    $action_dates_config["action_dates_email_admin_days"] = getvalescaped('action_dates_email_admin_days','',true);
    $action_dates_config["action_dates_restrictfield"] = getvalescaped('action_dates_restrictfield','',true);
    $action_dates_config["action_dates_remove_from_collection"] = getvalescaped('action_dates_remove_from_collection','');
    
    // Get the extra rows fom the table
    $action_date_extra_fields     = getvalescaped('action_dates_extra_field',array());
    $action_date_extra_statuses   = getvalescaped('action_dates_extra_status',array());
    	
    $action_dates_extra_config = array();	
    $mappingcount=0;
    
    // Store the extra config in a new array
    for ($i=0; $i < count($action_date_extra_fields); $i++)
        {
        if ($action_date_extra_fields[$i] != '' && $action_date_extra_statuses[$i] != '')
            {
            $action_dates_extra_config[$mappingcount]=array();
            $action_dates_extra_config[$mappingcount]["field"] = (int)$action_date_extra_fields[$i];
            $action_dates_extra_config[$mappingcount]["status"] = (int)$action_date_extra_statuses[$i];
            $mappingcount++;
            }			
        }    
    $action_dates_config["action_dates_extra_config"] = $action_dates_extra_config;

    set_plugin_config("action_dates",$action_dates_config);
    }
    
    
// Build the $page_def array of descriptions of each configuration variable the plugin uses.
$page_def[] = config_add_section_header($lang['action_dates_deletesettings']);
$page_def[] = config_add_single_ftype_select('action_dates_deletefield',$lang['action_dates_delete'],420,false,$DATE_FIELD_TYPES);
$page_def[] = config_add_boolean_select('action_dates_reallydelete',$lang['action_dates_reallydelete']);
$page_def[] = config_add_single_select('action_dates_new_state', $lang['action_dates_new_state'], $editable_states);
$page_def[] = config_add_text_input('action_dates_email_admin_days',$lang['action_dates_email_admin_days']);
$page_def[] = config_add_single_ftype_select('action_dates_restrictfield',$lang['action_dates_restrict'],420);
$page_def[] = config_add_boolean_select('action_dates_remove_from_collection',$lang['action_dates_remove_from_collection']);


$page_def[] = config_add_section_header($lang['action_dates_additional_settings']);

$action_dates_extra_config[] = array('field' =>'', 'status'=>'');

// Set up the table in HTML to add the extra config to the page
$page_def_extra = "<div class='Question'>
<label>" . $lang['action_dates_additional_settings_info'] . "</label>
<table id='action_dates_extra_table' class='ListviewStyle' style='width: 420px;'>
<tr>
    <th>
        <strong>" . $lang['action_dates_additional_settings_date'] . "</strong>
    </th>
    <th>
        <strong>" . $lang['action_dates_additional_settings_status'] . "</strong>
    </th>
</tr>";

foreach($action_dates_extra_config as $action_dates_extra_config)
    {
    $page_def_extra .= "<tr" . (($action_dates_extra_config['field'] == '') ? " id='action_dates_empty' style='display: none'" : "" ) . ">
        <td>
        <select name='action_dates_extra_field[]' class='stdwidth'>
            <option value=''></option>";
            foreach ($allowable_fields as $allowable_field)
                {
                $page_def_extra .=   "<option value='" . $allowable_field['ref'] . "'";
                if ($action_dates_extra_config['field'] == $allowable_field['ref'])
                    {
                    $page_def_extra .=  " selected";
                    }
                $page_def_extra .=  ">" . $allowable_field['title'] . "</option>\n";
                }
    
        $page_def_extra .= "         
        </select>
        </td>
        <td>
            <select name='action_dates_extra_status[]'  class='stdwidth'>
            <option value=''></option>";
            
            foreach ($editable_states as $editable_state=>$state_name)
                {
                $page_def_extra .= "<option value='" . $editable_state . "'";
                if ($action_dates_extra_config['status'] == $editable_state)
                    {
                    $page_def_extra .= " selected ";
                    }
                $page_def_extra .= ">" . $state_name . "</option>\n";
            } 
        $page_def_extra .= "</select>
        </td>
        </tr>";
    }

$page_def_extra .= "</table>
    <div class='clearerleft' ></div>";    
$page_def_extra .= "<label></label><a onclick='addActionDatesExtraRow()'><i aria-hidden='true' class='fa fa-plus-circle'></i></a>
</div>";
$page_def_extra .="<script>
        function addActionDatesExtraRow() {
            var table = document.getElementById('action_dates_extra_table');
            var rowCount = table.rows.length;
            var row = table.insertRow(rowCount);
            row.innerHTML = document.getElementById('action_dates_empty').innerHTML;
        }
</script>";

$page_def[] = config_add_html($page_def_extra);


// Do the page generation ritual -- don't change this section.
//$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, true, $plugin_page_heading);



include '../../../include/footer.php';
