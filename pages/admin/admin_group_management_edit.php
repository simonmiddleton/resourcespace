<?php
include "../../include/db.php";

include "../../include/authenticate.php";

if (!checkperm("a"))
    {
    exit ("Permission denied.");
    }


$offset=getval("offset",0);
$order_by=getval("orderby","");
$filter_by_parent=getval("filterbyparent","");
$find=getval("find","");
$filter_by_permissions=getval("filterbypermissions","");

$url_params=
    ($offset ? "&offset={$offset}" : "") .
    ($order_by ? "&orderby={$order_by}" : "") .
    ($filter_by_parent ? "&filterbyparent={$filter_by_parent}" : "") .
    ($find ? "&find={$find}" : "") .
    ($filter_by_permissions ? "&filterbypermissions={$filter_by_permissions}" : "");

# create new record from callback
$new_group_name=getvalescaped("newusergroupname","");
if ($new_group_name!="" && enforcePostRequest(false))
    {
    $setoptions =array("request_mode" => 1, "name" => $new_group_name);
    $ref = save_usergroup(0, $setoptions);

    log_activity(null,LOG_CODE_CREATED,null,'usergroup',null,$ref);
    log_activity(null,LOG_CODE_CREATED,$new_group_name,'usergroup','name',$ref,null,'');
    log_activity(null,LOG_CODE_CREATED,'1','usergroup','request_mode',$ref,null,'');

    redirect($baseurl_short."pages/admin/admin_group_management_edit.php?ref={$ref}{$url_params}"); // redirect to prevent repost and expose of form data
    exit;
    }

$ref=getvalescaped("ref","");

if (!sql_value("select ref as value from usergroup where ref='{$ref}'",false))
    {
    redirect("{$baseurl_short}pages/admin/admin_group_management.php?{$url_params}");       // fail safe by returning to the user group management page if duff ref passed
    exit;
    }

$dependant_user_count=sql_value("select count(*) as value from user where usergroup='{$ref}'",0);
$dependant_groups=sql_value("select count(*) as value from usergroup where parent='{$ref}'",0);
$has_dependants=$dependant_user_count + $dependant_groups > 0;
    
if (!$has_dependants && getval("deleteme",false) && enforcePostRequest(false))
    {
    sql_query("delete from usergroup where ref='{$ref}'");
    log_activity('',LOG_CODE_DELETED,null,'usergroup',null,$ref);

    // No need to keep any records of language content for this user group
    sql_query('DELETE FROM site_text WHERE specific_to_group = "' . $ref . '";');

    redirect("{$baseurl_short}pages/admin/admin_group_management.php?{$url_params}");       // return to the user group management page
    exit;
    }
    
if (getval("save",false) && enforcePostRequest(false))
    {
    $error = false;
    $logo_dir="{$storagedir}/admin/groupheaderimg/";

    if (isset($_POST['removelogo']))
        {
        $logo_extension=sql_value("select group_specific_logo as value from usergroup where ref='{$ref}'", false);
        $logo_filename="{$logo_dir}/group{$ref}.{$logo_extension}";
        
        if ($logo_extension && file_exists($logo_filename) && unlink($logo_filename))
            {
            $logo_extension="";
            }
        else
            {
            unset ($logo_extension);
            }
        }

        if (isset ($_FILES['grouplogo']['tmp_name']) && is_uploaded_file($_FILES['grouplogo']['tmp_name']))
            {

            if(!(file_exists($logo_dir) && is_dir($logo_dir)))
                {
                mkdir($logo_dir,0777,true);
                }

            $logo_pathinfo=pathinfo($_FILES['grouplogo']['name']);
            $logo_extension=$logo_pathinfo['extension'];
            $logo_filename="{$logo_dir}/group{$ref}.{$logo_extension}";
            
            if(!in_array(strtolower($logo_extension), array("jpg","jpeg","gif","svg","png")))
                {
                //trigger_error('You are not allowed to upload "' . $logo_extension . '" files to the system!');
                $error = true;
                $onload_message= array("title" => $lang["error"],"text" => str_replace('%EXTENSIONS',"JPG, GIF, SVG, PNG",$lang["allowedextensions-extensions"]));
                }

            if ($error || !move_uploaded_file($_FILES['grouplogo']['tmp_name'], $logo_filename))        // this will overwrite if already existing
                {
                unset ($logo_extension);
                }
            }

        if (isset($logo_extension))
            {
            $logo_extension_escaped = escape_check($logo_extension);
            sql_query("UPDATE usergroup SET group_specific_logo = '{$logo_extension_escaped}' WHERE ref = '{$ref}'");
            log_activity(null,null,null,'usergroup','group_specific_logo',$ref);
            }

    foreach (array("name","permissions","parent","search_filter","search_filter_id","edit_filter","edit_filter_id","derestrict_filter",
                    "derestrict_filter_id","resource_defaults","config_options","welcome_message","ip_restrict","request_mode",
                    "allow_registration_selection","inherit_flags", "download_limit","download_log_days") as $column)		
		
		{
        if ($execution_lockout && $column=="config_options")
            {
            # Do not allow config overrides to be changed from UI if $execution_lockout is set.
            continue;
            }

		if (in_array($column,array("allow_registration_selection")))
			{
			$val=getval($column,"0") ? "1" : "0";
			}

		elseif($column=="inherit_flags" && getvalescaped($column,'')!="")
			{
			$val=implode(",",getvalescaped($column,''));
			}
		elseif(in_array($column,array("parent","download_limit","download_log_days","search_filter_id","edit_filter_id","derestrict_filter_id")))
			{
			$val=getval($column,0,true);
			}
		elseif($column=="request_mode")
			{
			$val=getval($column, 1, true);
            }
		else
			{
			$val=getvalescaped($column,"");
			}

		if (isset($sql))
			{
			$sql.=",";
			}
		else
			{
			$sql="update usergroup set ";
			}		
		$sql.="{$column}='{$val}'";
		log_activity(null,LOG_CODE_EDITED,$val,'usergroup',$column,$ref);
		}
    
    $sql.=" where ref='{$ref}'";
    sql_query($sql);

	hook("usergroup_edit_add_form_save","",array($ref));
	if(!$error)
		{
		redirect("{$baseurl_short}pages/admin/admin_group_management.php?{$url_params}");		// return to the user group management page
		exit;
		}
	}

$record = get_usergroup($ref);

# prints out an option tag per config.default.php file and moves any comments to the label attribute.
function dump_config_default_options()
    {   
    global $baseurl_short;
    
    $config_defaults = file_get_contents("../../include/config.default.php");
    $config_defaults = preg_replace("/\<\?php|\?\>/s","",$config_defaults);     // remove php open and close tags
    $config_defaults = preg_replace("/\/\*.*?\*\//s","",$config_defaults);      // remove multi-line comments

    preg_match_all("/\n(\S*?)(\\$.*?\=.*?\;)(.*?)\n/s",$config_defaults,$matches);

    for ($i=0; $i<count($matches[0]); $i++)
        {       
        $matches[1][$i]=preg_replace('/\#|(\/\/)/s','',$matches[1][$i]);        // hashes and double forward slash comments
        $matches[1][$i]=preg_replace('/\n\s+/s',"\n",$matches[1][$i]);      // white space at the start of new lines
        $matches[1][$i]=preg_replace('/^\s*/s','',$matches[1][$i]);     // leading white space
        $matches[1][$i]=preg_replace('/\s*$/s','',$matches[1][$i]);     // trailing white space
        
        $matches[3][$i]=preg_replace('/\#|(\/\/)/s','',$matches[3][$i]);        // hashes and double forward slash comments
        $matches[3][$i]=preg_replace('/\n\s+/s',"\n",$matches[3][$i]);      // white space at the start of new lines
        $matches[3][$i]=preg_replace('/^\s*/s','',$matches[3][$i]);     // leading white space
        $matches[3][$i]=preg_replace('/\s*$/s','',$matches[3][$i]);     // trailing white space     
        
        if ($matches[1][$i]!="" && $matches[3][$i]!="") $matches[1][$i].="\n";
            
        echo "<option value=\"" . nl2br (htmlentities ($matches[1][$i] . $matches[3][$i],ENT_COMPAT)) . "\">" . htmlentities ($matches[2][$i]) . "</option>\n";
        }
    }

include "../../include/header.php";

?><form method="post" enctype="multipart/form-data" action="<?php echo $baseurl_short; ?>pages/admin/admin_group_management_edit.php?ref=<?php echo $ref . $url_params ?>" id="mainform" class="FormWide">
    <?php generateFormToken("mainform"); ?>
    <div class="BasicsBox">
    <?php
        $links_trail = array(
            array(
                'title' => $lang["systemsetup"],
                'href'  => $baseurl_short . "pages/admin/admin_home.php"
            ),
            array(
                'title' => $lang["page-title_user_group_management"],
                'href'  => $baseurl_short . "pages/admin/admin_group_management.php?" . $url_params
            ),
            array(
                'title' => $lang["page-title_user_group_management_edit"]
            )
        );

        renderBreadcrumbs($links_trail);
    ?>

    <p><?php echo $lang['page-subtitle_user_group_management_edit']; render_help_link("systemadmin/creating-user-groups"); ?></p>

        <input type="hidden" name="save" value="1">

        <div class="Question">
            <label for="reference"><?php echo $lang["property-reference"]; ?></label>
            <div class="Fixed"><?php echo $ref; ?></div>
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label for="name"><?php echo $lang["property-name"]; ?></label>
            <input name="name" type="text" class="stdwidth" value="<?php echo htmlspecialchars($record['name']); ?>"> 
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label for="dependants"><?php echo $lang["property-contains"]; ?></label>
            <div class="Fixed"><?php echo $dependant_user_count; ?>&nbsp;<?php echo $lang['users']; ?>, <?php echo $dependant_groups; ?>&nbsp;<?php echo $lang['property-groups']; ?></div>
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label for="permissions"><?php echo $lang["property-permissions"]; ?></label>
            
            <?php if ($record['parent'])
                {?>
                <label><?php echo $lang["property-permissions_inherit"] ?></label>
                <input id="permissions_inherit" name="inherit_flags[]" type="checkbox" value="permissions" onClick="if(jQuery('#permissions_inherit').is(':checked')){jQuery('#permissions_area').slideUp();}else{jQuery('#permissions_area').slideDown();}" <?php if(in_array("permissions",$record['inherit'])){echo "checked";} ?>>
                <div class="clearerleft"></div> 
                <?php
                }?>
                
            <div id ="permissions_area" <?php if(in_array("permissions",$record['inherit'])){echo "style=display:none;";} ?>>
                <label></label>
                <input type="button" class="stdwidth" onclick="return CentralSpaceLoad('<?php echo $baseurl_short; ?>pages/admin/admin_group_permissions.php?ref=<?php echo $ref . $url_params; ?>',true);" value="<?php echo $lang["launchpermissionsmanager"]; ?>"></input>                       
                <div class="clearerleft"></div>         
                <label></label>
                <textarea name="permissions" class="stdwidth" rows="5" cols="50"><?php echo $record['permissions']; ?></textarea>
                <div class="clearerleft"></div>
                <label></label>
                <div><?php echo $lang["documentation-permissions"]; ?></div>
                <div class="clearerleft"></div>
            </div> <!-- End of permissions_area -->
        </div>

        <div class="Question">
            <label for="parent"><?php echo $lang["property-parent"]; ?></label>
            <select name="parent" class="stdwidth">
                <option value="0" ><?php if ($record['parent']) echo $lang["property-user_group_remove_parent"]; ?></option>
                <?php
                $groups=sql_query("select ref, name from usergroup order by name");

                foreach ($groups as $group)
                {
                    if ($group['ref']==$ref) continue;      // not allowed to be the parent of itself

                    ?>              <option <?php if ($record['parent']==$group['ref']) { ?> selected="true" <?php } ?>value="<?php echo $group['ref']; ?>"><?php echo $group['name']; ?></option>
                <?php
                }
                ?>          </select>
            <div class="clearerleft"></div>
        </div>

    <?php hook("usergroup_edit_add_form",'',array($record));?>

    </div>

    <h2 class="CollapsibleSectionHead collapsed"><?php echo $lang["fieldtitle-advanced_options"]; ?></h2>

    <div class="CollapsibleSection" style="display:none;">

        <p><?php echo $lang["action-title_see_wiki_for_user_group_advanced_options"]; ?></p>

        <?php
        $filters = get_filters("name","ASC");
        $filters[] = array("ref" => -1, "name" => $lang["disabled"]);

		if ($search_filter_nodes)
			{
            // Show filter selector if already migrated or no filter has been set
            // Add the option to indicate filter migration failed
			?>
			<div class="Question">
				<label for="search_filter_id"><?php echo $lang["property-search_filter"]; ?></label>
				<select name="search_filter_id" class="stdwidth">
					<?php
					echo "<option value='0' >" . ($record['search_filter_id'] ? $lang["filter_none"] : $lang["select"]) . "</option>";
					foreach	($filters as $filter)
						{
						echo "<option value='" . $filter['ref'] . "' " . ($record['search_filter_id'] == $filter['ref'] ? " selected " : "") . ">" . i18n_get_translated($filter['name']) . "</option>";
						}
                    ?>
				</select>
				<div class="clearerleft"></div>
			</div>
			<?php	
			}
		if((strlen($record['search_filter']) != "" && (!(is_numeric($record['search_filter_id']) || $record['search_filter_id'] < 1))) || !$search_filter_nodes)
			{
            // Show old style text filter input - will not appear once a new style filter has been selected
			?>
			<div class="Question">
				<label for="search_filter"><?php echo $lang["property-search_filter"]; ?></label>
				<textarea name="search_filter" class="stdwidth" rows="3" cols="50" <?php echo ($search_filter_nodes ? "readonly" : "");?>><?php echo $record['search_filter']; ?></textarea>
				<div class="clearerleft"></div>
			</div>
			<?php
            }
        
		if ($search_filter_nodes)
            {
            ?>
            <div class="Question">
                <label for="edit_filter_id"><?php echo $lang["property-edit_filter"]; ?></label>
                <select name="edit_filter_id" class="stdwidth">
                    <?php
                    echo "<option value='0' >" . ($record['edit_filter_id'] ? $lang["filter_none"] : $lang["select"]) . "</option>";
                    foreach	($filters as $filter)
                        {
                        echo "<option value='" . $filter['ref'] . "' " . ($record['edit_filter_id'] == $filter['ref'] ? " selected " : "") . ">" . i18n_get_translated($filter['name']) . "</option>";
                        }
                    ?>
                </select>
                <div class="clearerleft"></div>
            </div>
            <?php	
            }

        if((strlen($record['edit_filter']) != "" && (!is_numeric($record['edit_filter_id']) || (int)$record['edit_filter_id'] < 1)) || !$search_filter_nodes)
            {
            ?>
            <div class="Question">
                <label for="edit_filter"><?php echo $search_filter_nodes ? "" : $lang["property-edit_filter"]; ?></label>
                <textarea name="edit_filter" class="stdwidth" rows="3" cols="50" <?php echo ($search_filter_nodes ? "readonly" : "");?>><?php echo $record['edit_filter']; ?></textarea>
                <div class="clearerleft"></div>
            </div>
            <?php
            }
        
        if ($search_filter_nodes)
            {
            ?>
            <div class="Question">
                <label for="derestrict_filter_id"><?php echo $lang["fieldtitle-derestrict_filter"]; ?></label>
                <select name="derestrict_filter_id" class="stdwidth">
                    <?php
                    echo "<option value='0' >" . ($record['derestrict_filter_id'] ? $lang["filter_none"] : $lang["select"]) . "</option>";
                    foreach	($filters as $filter)
                        {
                        echo "<option value='" . $filter['ref'] . "' " . ($record['derestrict_filter_id'] == $filter['ref'] ? " selected " : "") . ">" . i18n_get_translated($filter['name']) . "</option>";
                        }
                    ?>
                </select>
                <div class="clearerleft"></div>
            </div>
            <?php	
            }
        if((strlen($record['derestrict_filter']) != "" && (!(is_numeric($record['derestrict_filter_id']) || $record['derestrict_filter_id'] < 1))) || !$search_filter_nodes)
            {
            ?>
            <div class="Question">
                <label for="derestrict_filter"><?php echo $lang["fieldtitle-derestrict_filter"]; ?></label>
                <textarea name="derestrict_filter" class="stdwidth" rows="3" cols="50" <?php echo ($search_filter_nodes ? "readonly" : "");?>><?php echo $record['derestrict_filter']; ?></textarea>
                <div class="clearerleft"></div>
            </div>
            <?php
            }?>

        <div class="Question">
            <label for="download_limit"><?php echo $lang["group_download_limit_title"]; ?></label>
            <input name="download_limit" type="number" class="vshrtwidth" value="<?php echo htmlspecialchars($record['download_limit']); ?>">
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label for="download_log_days"><?php echo $lang["group_download_limit_period"]; ?></label>
            <input name="download_log_days" type="number" class="vshrtwidth" value="<?php echo htmlspecialchars($record['download_log_days']); ?>">
            <div class="clearerleft"></div>
        </div>


        <div class="Question">
            <label for="resource_defaults"><?php echo $lang["property-resource_defaults"]; ?></label>
            <textarea name="resource_defaults" class="stdwidth" rows="3" cols="50"><?php echo $record['resource_defaults']; ?></textarea>
            <div class="clearerleft"></div>
        </div>

        <?php if (!$execution_lockout) { ?>
        <div class="Question">
            <label for="config_options"><?php echo $lang["property-override_config_options"]; ?></label>
            
            <?php if ($record['parent'])
                {?>
                <label><?php echo $lang["property-config_inherit"] ?></label>
                <input id="config_inherit" name="inherit_flags[]" type="checkbox" value="config_options" onClick="if(jQuery('#config_inherit').is(':checked')){jQuery('#config_area').slideUp();}else{jQuery('#config_area').slideDown();}" <?php if(in_array("config_options",$record['inherit'])){echo "checked";} ?>>
                <div class="clearerleft"></div> 
                <?php
                }?>
            
            <div id ="config_area" <?php if(in_array("config_options",$record['inherit'])){echo "style=display:none;";} ?>> 
                <label></label>
                <textarea name="config_options" id="configOptionsBox" class="stdwidth" rows="12" cols="50"><?php echo $record['config_options']; ?></textarea>
                <div class="clearerleft"></div>
            </div>
          </div>
        <?php } ?>

        <div class="Question">
            <label for="welcome_message"><?php echo $lang["property-email_welcome_message"]; ?></label>
            <textarea name="welcome_message" class="stdwidth" rows="12" cols="50"><?php echo $record['welcome_message']; ?></textarea>
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label for="ip_restrict"><?php echo $lang["property-ip_address_restriction"]; ?></label>
            <input name="ip_restrict" type="text" class="stdwidth" value="<?php echo $record['ip_restrict']; ?>">
            <div class="clearerleft"></div>
        </div>

        <div class="FormHelp">
            <div class="FormHelpInner"><?php echo $lang["information-ip_address_restriction"]; ?></div>
        </div>

        <div class="Question">
            <label for="request_mode"><?php echo $lang["property-request_mode"]; ?></label>
            <select name="request_mode" class="stdwidth">
<?php
    for ($i=0; $i<4; $i++)
        {
?>              <option <?php if ($record['request_mode']==$i) { ?> selected="true" <?php } ?>value="<?php echo $i; ?>"><?php echo $lang["resourcerequesttype{$i}"]; ?></option>
<?php
        }
?>              </select>
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label for="allow_registration_selection"><?php echo $lang["property-allow_registration_selection"]; ?></label>
            <input name="allow_registration_selection" type="checkbox" value="1" <?php if ($record['allow_registration_selection']==1) { ?> checked="checked"<?php } ?>>
            <div class="clearerleft"></div>
        </div>

<?php
    if ($record['group_specific_logo'])
        {
        $linkedheaderimgsrc = (isset($storageurl)? $storageurl : $baseurl."/filestore"). "/admin/groupheaderimg/group".$record['ref'].".".$record["group_specific_logo"];
        ?>
        <div class="Question">
            <label for="grouplogocurrent"><?php echo $lang["fieldtitle-group_logo"]; ?></label>
                <img src="<?php echo $linkedheaderimgsrc;?>" alt="Group logo" height='126'>
        </div>
        <div class="Question">
            <label for="grouplogo"><?php echo $lang["fieldtitle-group_logo_replace"]; ?></label>
            <input name="grouplogo" type="file">
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="removelogo"><?php echo $lang["action-title_remove_user_group_logo"]; ?></label>
            <input name="removelogo" type="checkbox" value="1">
            <div class="clearerleft"></div>
        </div>
<?php
        }
    else
        {
?>      <div class="Question">
            <label for="grouplogo"><?php echo $lang["fieldtitle-group_logo"]; ?></label>
            <input name="grouplogo" type="file">
            <div class="clearerleft"></div>
        </div>
<?php
        }
?>

    </div>      <!-- end of advanced options -->

    <div class="BasicsBox">

        <div class="Question">
            <label><?php echo $lang["fieldtitle-tick_to_delete_group"]?></label>
            <input id="delete_user_group" name="deleteme" type="checkbox" value="yes" <?php if($has_dependants) { ?> disabled="disabled"<?php } ?>>
            <div class="clearerleft"></div>
        </div>
        
        <div class="FormHelp">
            <div class="FormHelpInner"><?php echo $lang["fieldhelp-tick_to_delete_group"]; ?></div>
        </div>
        
        <div class="QuestionSubmit">
            <label for="buttonsave"></label>
            <input name="buttonsave" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]; ?>&nbsp;&nbsp;">
        </div>

    </div>

</form>

<script>
    registerCollapsibleSections();

    jQuery('#delete_user_group').click(function () {
        <?php
        $language_specific_results = sql_value('SELECT count(*) AS `value` FROM site_text WHERE specific_to_group = "' . $ref . '";', 0);
        $alert_message = str_replace('%%RECORDSCOUNT%%', $language_specific_results, $lang["delete_user_group_checkbox_alert_message"]);
        ?>

        if(<?php echo $language_specific_results; ?> > 0 && jQuery('#delete_user_group').is(':checked'))
            {
            alert('<?php echo $alert_message; ?>');
            }
    });
</script>

<?php
include "../../include/footer.php";
