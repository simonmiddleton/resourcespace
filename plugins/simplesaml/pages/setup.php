<?php
#
# simplesaml setup page
#

include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include_once dirname(__FILE__) . '/../include/simplesaml_functions.php';

$plugin_name = 'simplesaml';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
	
if ((getval('submit','') != '' || getval('save','') != '') && enforcePostRequest(false))
	{
		
	$simplesaml['simplesaml_site_block'] = getvalescaped('simplesaml_site_block','');
	$simplesaml['simplesaml_login'] = getvalescaped('simplesaml_login','');
	$simplesaml['simplesaml_allow_public_shares'] = getvalescaped('simplesaml_allow_public_shares','');
	$simplesaml['simplesaml_allowedpaths'] = explode(",",getvalescaped('simplesaml_allowedpaths',''));
	$simplesaml['simplesaml_allow_standard_login'] = getvalescaped('simplesaml_allow_standard_login','');
	$simplesaml['simplesaml_prefer_standard_login'] = getvalescaped('simplesaml_prefer_standard_login','');
	$simplesaml['simplesaml_sp'] = getvalescaped('simplesaml_sp','');
	$simplesaml['simplesaml_login_expiry'] = getvalescaped('simplesaml_login_expiry','');
	
	
	$simplesaml['simplesaml_username_attribute'] = getvalescaped('simplesaml_username_attribute','');
	$simplesaml['simplesaml_fullname_attribute'] = getvalescaped('simplesaml_fullname_attribute','');
	$simplesaml['simplesaml_email_attribute'] = getvalescaped('simplesaml_email_attribute','');
	$simplesaml['simplesaml_group_attribute'] = getvalescaped('simplesaml_group_attribute','');	
	$simplesaml['simplesaml_fallback_group'] = getvalescaped('simplesaml_fallback_group','');
	$simplesaml['simplesaml_update_group'] = getvalescaped('simplesaml_update_group','');
	$simplesaml['simplesaml_fullname_separator'] = getvalescaped('simplesaml_fullname_separator','');
	$simplesaml['simplesaml_username_separator'] = getvalescaped('simplesaml_username_separator','');
    $simplesaml['simplesaml_custom_attributes'] = getvalescaped('simplesaml_custom_attributes', '');
    $simplesaml['simplesaml_lib_path'] = getvalescaped('simplesaml_lib_path', '');
	
	$samlgroups = $_REQUEST['samlgroup'];
	$rsgroups = $_REQUEST['rsgroup'];
	$priority = $_REQUEST['priority'];

	if (count($samlgroups) > 0){	
		$simplesaml_groupmap=array();	
		$mappingcount=0;
		}
	
	for ($i=0; $i < count($samlgroups); $i++)
		{
		if ($samlgroups[$i] <> '' && $rsgroups[$i] <> '' && is_numeric($rsgroups[$i]))
			{
			$simplesaml_groupmap[$mappingcount]=array();
			$simplesaml_groupmap[$mappingcount]["samlgroup"]=$samlgroups[$i];
			$simplesaml_groupmap[$mappingcount]["rsgroup"]=$rsgroups[$i];
			if(isset($priority[$i])){$simplesaml_groupmap[$mappingcount]["priority"]=$priority[$i];}
			$mappingcount++;
			}			
		}
	
	$simplesaml["simplesaml_groupmap"]=$simplesaml_groupmap;
	set_plugin_config("simplesaml",$simplesaml);
	include_plugin_config($plugin_name,base64_encode(serialize($simplesaml)));
	if (getval('submit','')!=''){redirect('pages/team/team_plugins.php');}
	}	


global $baseurl;


// Retrieve list of groups for use in mapping dropdown
$rsgroups = sql_query('select ref, name from usergroup order by name asc');


include '../../../include/header.php';
?>
<div class="BasicsBox"> 
  <h2>&nbsp;</h2>
  <h1><?php echo $lang['simplesaml_configuration'] ?></h1>
  
<?php
 if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
            {
            echo "<div class='PageInfoMessage'>" . $lang['simplesaml_sp_configuration'] . ". <a href='" . $baseurl . "/plugins/simplesaml/pages/about.php'>" . $baseurl . "/plugins/simplesaml/pages/about.php</a></div>";
			 }  
  
?>
<form id="form1" name="form1" method="post" action="">
<?php
generateFormToken("simplesaml_form");
echo config_section_header($lang['simplesaml_main_options'],'');
echo config_text_input('simplesaml_lib_path', $lang['simplesaml_lib_path_label'], $simplesaml_lib_path);
?>
<?php echo config_boolean_field("simplesaml_site_block",$lang['simplesaml_site_block'],$simplesaml_site_block,30);?>
<?php echo config_boolean_field("simplesaml_login",$lang['simplesaml_login'],$simplesaml_login,30);?>
<?php echo config_boolean_field("simplesaml_allow_public_shares",$lang['simplesaml_allow_public_shares'],$simplesaml_allow_public_shares,30);?>
<?php echo config_text_input("simplesaml_sp",$lang['simplesaml_service_provider'],$simplesaml_sp);?>
<?php echo config_text_input("simplesaml_login_expiry",$lang['simplesaml_login_expiry'],$simplesaml_login_expiry);?>

<?php echo config_text_input("simplesaml_allowedpaths",$lang['simplesaml_allowedpaths'],implode(',',$simplesaml_allowedpaths));?>
<?php echo config_boolean_field("simplesaml_allow_standard_login",$lang['simplesaml_allow_standard_login'],$simplesaml_allow_standard_login,30);?>
<?php echo config_boolean_field("simplesaml_prefer_standard_login",$lang['simplesaml_prefer_standard_login'],$simplesaml_prefer_standard_login,30);?>


<?php echo config_section_header($lang['simplesaml_idp_configuration'],$lang['simplesaml_idp_configuration_description']);?>


<?php echo config_text_input("simplesaml_username_attribute",$lang['simplesaml_username_attribute'],$simplesaml_username_attribute);?>
<?php echo config_text_input("simplesaml_username_separator",$lang['simplesaml_username_separator'],$simplesaml_username_separator);?>
<?php echo config_text_input("simplesaml_fullname_attribute",$lang['simplesaml_fullname_attribute'],$simplesaml_fullname_attribute);?>
<?php echo config_text_input("simplesaml_fullname_separator",$lang['simplesaml_fullname_separator'],$simplesaml_fullname_separator);?>
<?php echo config_text_input("simplesaml_email_attribute",$lang['simplesaml_email_attribute'],$simplesaml_email_attribute);?>
<?php echo config_text_input("simplesaml_group_attribute",$lang['simplesaml_group_attribute'],$simplesaml_group_attribute);?>


<?php echo config_boolean_field("simplesaml_update_group",$lang['simplesaml_update_group'],$simplesaml_update_group,30);?>

<?php
$rsgroupoption=array();
foreach($rsgroups as $rsgroup)
	{$rsgroupoption[$rsgroup["ref"]]=$rsgroup["name"];}

echo config_single_select("simplesaml_fallback_group",$lang['simplesaml_fallback_group'],$simplesaml_fallback_group,$rsgroupoption, true);
echo config_text_input('simplesaml_custom_attributes', $lang['simplesaml_custom_attributes'], $simplesaml_custom_attributes);
?>
<div class="Question">
<h3><?php echo $lang['simplesaml_groupmapping']; ?></h3>
<table id='groupmaptable'>
<tr><th>
<strong><?php echo $lang['simplesaml_samlgroup']; ?></strong>
</th><th>
<strong><?php echo $lang['simplesaml_rsgroup']; ?></strong>
</th><th>
<strong><?php echo $lang['simplesaml_priority']; ?></strong>
</th>
</tr>

<?php
	for($i = 0; $i < count($simplesaml_groupmap)+1; $i++){
		if ($i >= count($simplesaml_groupmap)){
			$thegroup = array();
			$thegroup['samlgroup'] = '';
			$thegroup['rsgroup'] = '';
			$thegroup['priority'] = '';
			$rowid = 'groupmapmodel';
		} else {
			$thegroup = $simplesaml_groupmap[$i];
			$rowid = "row$i";
		}
?>
<tr id='<?php echo $rowid; ?>'>
   <td><input type='text' name='samlgroup[]' value='<?php echo $thegroup['samlgroup']; ?>' /></td>
   <td><select name='rsgroup[]'><option value=''></option>
	<?php 	
		foreach ($rsgroups as $rsgroup){
			echo  "<option value='" . $rsgroup['ref'] . "'";
			if ($thegroup['rsgroup'] == $rsgroup['ref']){
				echo " selected";
			}
			echo ">". $rsgroup['name'] . "</option>\n";
		} 
 	?></select>
    </td>
    <td><input type='text' name='priority[]' value='<?php echo $thegroup['priority']; ?>' /></td>
</tr>
<?php } ?>
</table>

<a onclick='addGroupMapRow()'><?php echo $lang['simplesaml_addrow']; ?></a>
</div>

<div class="Question">  
<label for="submit"></label>
<input type="submit" name="save" id="save" value="<?php echo $lang['plugins-saveconfig']?>">
<input type="submit" name="submit" id="submit" value="<?php echo $lang['plugins-saveandexit']?>">
</div><div class="clearerleft"></div>

</form>
</div>	

<script language="javascript">
        function addGroupMapRow() {
 
            var table = document.getElementById("groupmaptable");
 
            var rowCount = table.rows.length;
            var row = table.insertRow(rowCount);
 
            row.innerHTML = document.getElementById("groupmapmodel").innerHTML;
        }
</script> 
<?php

include '../../../include/footer.php';
