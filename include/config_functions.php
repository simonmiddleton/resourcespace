<?php
/**
 * Helper and rendering function for the configuration pages in the team center
 * 
 * @package ResourceSpace
 * @subpackage Includes
 */

 
/**
 * Renders a select element.
 * 
 * Takes an array of options (as returned from sql_query and returns a valid 
 * select element.  The query must have a column aliased as value and label.  
 * Option groups can be created as well with the optional $groupby parameter.
 * This function retrieves a language field in the form of 
 * $lang['cfg-<fieldname>'] to use for the element label.
 * 
 * <code>
 * $options = sql_select("SELECT name AS label, ref AS value FROM resource_type");
 * 
 * render_select_option('myfield', $options, 18);
 * </code>
 * 
 * @param string $fieldname Name to use for the field.
 * @param array $opt_array Array of options to fill the select with
 * @param mixed $selected If matches value the option is marked as selected
 * @param string $groupby Column to group by
 * @return string HTML output.
 */
function render_select_option($fieldname, $opt_array, $selected, $groupby=''){
    global $errorfields, $lang;
    $output = '';
    $output .= "<tr><th><label for=\"$fieldname\">".$lang['cfg-'.$fieldname]."</label></th>";
    $output .= "<td><select name=\"$fieldname\">";
    if ($groupby!=''){
        $cur_group = $opt_array[0][$groupby];
        $output .= "<optgroup label=\"$cur_group\">";
    }
    foreach ($opt_array as $option){
        if ($groupby!='' && $cur_group!=$option[$groupby]){
          $cur_group = $option[$groupby];
          $output .= "</optgroup><optgroup label=\"$cur_group\">";            
        }
        $output .= "<option ";
        $output .= $option['value']==$selected?'selected="selected" ':'';
        $output .= "value=\"{$option['value']}\">{$option['label']}</option>";
    }
    $output .= '</optgroup>';
    $output .= isset($errorfields[$fieldname])?'<span class="error">* '.$errorfields[$fieldname].'</span>':'';
    $output .= '</td></tr>';
    return $output;
}

/**
 * Render a yes/no field with the given fieldname.
 * 
 * This function will use $lang['cfg-<fieldname>'] as the text label for the
 * element.
 * @param string $fieldname Name of field.
 * @param bool $value Current field value
 * @return string HTML Output
 */
function render_bool_option($fieldname, $value){
    global $errorfields, $lang;
    $output = '';
    $output .= "<tr><th><label for=\"$fieldname\">".$lang['cfg-'.$fieldname]."</label></th>";
    $output .= "<td><select name=\"$fieldname\">";
    $output .= "<option value='true' ";
    $output .= $value?'selected':'';
    $output .= ">Yes</option>";
    $output .= "<option value='false' ";
    $output .= !$value?'selected':'';
    $output .= ">No</option></select>";
    $output .= isset($errorfields[$fieldname])?'<span class="error">* '.$errorfields[$fieldname].'</span>':'';
    $output .= "</td></tr>";
    return $output;
}

/**
 * Renders a text field for a given field name.
 * 
 * Uses $lang['cfg-<fieldname>'] as the field label.
 * 
 * @param string $fieldname Name of field
 * @param string $value Current field value
 * @param int $size Size of text field, optional, defaults to 20
 * @param string $units Optional units parameter. Displays to right of text field.
 * @return string HTML Output
 */
function render_text_option($fieldname, $value, $size=20, $units=''){
    global $errorfields, $lang;
    if (isset($errorfields[$fieldname]) && isset($_POST[$fieldname]))
        $value = $_POST[$fieldname];
    $output = '';
    $output .= "<tr><th><label for=\"$fieldname\">".$lang['cfg-'.$fieldname]."</label></th>";
    $output .= "<td><input type=\"text\" value=\"$value\" size=\"$size\" name=\"$fieldname\"/> $units ";
    $output .= isset($errorfields[$fieldname])?'<span class="error">* '.$errorfields[$fieldname].'</span>':'';
    $output .= "</td></tr>";
    return $output;
}


/**
* Save/ Update config option
*
* @param  integer  $user_id      Current user ID. Use NULL for system wide config options
* @param  string   $param_name   Parameter name
* @param  string   $param_value  Parameter value
*
* @return boolean
*/
function set_config_option($user_id, $param_name, $param_value)
    {
    // We do allow for param values to be empty strings or 0 (zero)
    if(empty($param_name) || is_null($param_value))
        {
        return false;
        }

    // Prepare the value before inserting it
    $param_value = config_clean($param_value);

    $query = "INSERT INTO user_preferences (user,parameter,`value`) VALUES (?,?,?)";
   
    $current_param_value = null;
    if(get_config_option($user_id, $param_name, $current_param_value))
        {
        if($current_param_value == $param_value)
            {
            return true;
            }
        $params[] = 's'; $params[] = $param_value;
        if(is_null($user_id))
            {
            $user_query = 'user IS NULL';
            }
        else    
            {
            $user_query = 'user = ?';
            $params[] = 'i'; $params[] = $user_id;
            }

        $query = "UPDATE user_preferences SET `value` = ? WHERE ". $user_query ." AND parameter = ?";
        $params[] = "s"; $params[] = $param_name;

        if (is_null($user_id))		// only log activity for system changes, i.e. when user not specified
            {
            log_activity(null, LOG_CODE_EDITED, $param_value, 'user_preferences', 'value', "parameter='" . $param_name . "'", null, $current_param_value);
            }
        }
    else
        {
        $params  = ["i",$user_id,"s",$param_name,"s",$param_value,];
        }
    ps_query($query,$params);

    // Clear disk cache
    clear_query_cache("preferences");

    return true;
    }
    

/**
 * Delete entry from the user_preferences table completely (instead of setting to blank via set_config_option).
 * Used by system preferences page when deleting a file to allow fallback to value (if set) in config.php instead
 * of replacing it with blank from user_preference value.
 *
 * @param  int|null   $user_id      User ID. Use NULL for system wide config options.
 * @param  string     $param_name   Parameter name
 * 
 * @return bool       True if preference was deleted else false.
 */
function delete_config_option(?int $user_id, string $param_name) : bool
    {
    if(empty($param_name))
        {
        return false;
        }

    $current_param_value = null;
    if(get_config_option($user_id, $param_name, $current_param_value))
        {
        if(is_null($user_id))
            {
            $user_query = 'user IS NULL';
            }
        else
            {
            $user_query = 'user = ?';
            $params[] = 'i'; $params[] = $user_id;
            }

        $query = "DELETE FROM user_preferences WHERE ". $user_query ." AND parameter = ?";
        $params[] = "s"; $params[] = $param_name;

        if (is_null($user_id))		// only log activity for system changes, i.e. when user not specified
            {
            log_activity(null, LOG_CODE_DELETED, null, 'user_preferences', 'value', "parameter='" . $param_name . "'", null, $current_param_value);
            }

        ps_query($query,$params);

        // Clear disk cache
        clear_query_cache("preferences");

        return true;
        }

    return false;
    }


/**
 * Remove system/user preferences
 * 
 * @param ?int $user_id Database user ID
 * @param string $name  Configuration option (variable) name
 */
function remove_config_option(?int $user_id, string $name): bool
    {
    if(trim($name) === '')
        {
        return false;
        }

    $user = is_null($user_id)
        ? new PreparedStatementQuery('user IS NULL')
        : new PreparedStatementQuery('user = ?', ['i', $user_id]);
    
    $psq = new PreparedStatementQuery(
        "DELETE FROM user_preferences WHERE {$user->sql} AND parameter = ?",
        array_merge($user->parameters, ['s', $name])
    );

    ps_query($psq->sql, $psq->parameters);
    clear_query_cache('preferences');
    return true;
    }


/**
* Get config option value from the database (system wide -or- a user preference).
* 
* @param  ?integer $user_id         Current user ID. Use NULL to get the system wide setting.
* @param  string   $name            Parameter name
* @param  string   $returned_value  The config value will be returned through this parameter which is passed by reference.
*                                   IMPORTANT: it falls back (defaults) to the globally scoped config option value if 
*                                   there's nothing in the database.
* @param  mixed    $default         Optionally used to set a default that may not be the current
*                                   global setting e.g. for checking admin resource preferences
*
* @return boolean Indicates if the config option was found in the database or not.
*/
function get_config_option($user_id, $name, &$returned_value, $default = null)
    {
    if(trim($name) === '')
        {
        return false;
        }

    if(is_null($user_id))
        {
        $user_query = 'user IS NULL';
        }
    else    
        {
        $user_query = 'user = ?';
        $params[] = 'i'; $params[] = $user_id;
        }

    $query = "SELECT `value` FROM user_preferences WHERE ". $user_query ." AND parameter = ?";
    $params[] = "s"; $params[] = $name;
    $config_option = ps_value($query,$params, null);

    if(is_null($default) && isset($GLOBALS[$name]))
        {
        $default = $GLOBALS[$name];
        }

     if(is_null($config_option))
        {
        $returned_value = $default;
        return false;
        }

    $returned_value = unescape($config_option);
    return true;
    }

	
/**
* Get all user refs with a specific configuration option set from database
* 
* @param  string  $option         	Parameter name
* @param  string  $value         	Parameter value
*
* @return array 					Array of user  references
*/
function get_config_option_users($option,$value)
    {
    $users = ps_array("SELECT user value FROM user_preferences WHERE parameter = ? AND value=?",array("s",$option,"s",$value), "preferences");
    return $users;   
    }

/**
* Get config option from database for a specific user or system wide
* 
* @param  integer  $user_id           Current user ID. Can also be null to retrieve system wide config options
* @param  array    $returned_options  If a value does exist it will be returned through
*                                     this parameter which is passed by reference
* @return boolean
*/
function get_config_options($user_id, array &$returned_options)
    {
    $params = [];
    if(is_null($user_id))
        {
        $sql = 'user IS NULL';
        }
    else
        {
        $sql = 'user = ?';
        $params = ['i', $user_id];
        }

    $query = 'SELECT parameter, `value` FROM user_preferences WHERE ' . $sql;
    $config_options = ps_query($query, $params,"preferences");

    if(empty($config_options))
        {
        return false;
        }

    // Strip out any system configs that are blocked from being edited in the UI that might have been set previously.
    global $system_config_hide;
    if (is_null($user_id) && count($system_config_hide)>0)
        {
        $new_config_options=array();
        for($n=0;$n<count($config_options);$n++)
            {
            if (!in_array($config_options[$n]["parameter"],$system_config_hide)) {$new_config_options[]=$config_options[$n];} // Add if not blocked
            }
        $config_options=$new_config_options;
        }

    $returned_options = $config_options;

    return true;
    }


/**
* Process configuration options from database
* either system wide or user specific by setting
* the global variable
*
* @param int $user_id
*
* @return void
*/
function process_config_options($user_id = null)
    {
    global $user_preferences;

    // If the user doesn't have the ability to set his/her own preferences, then don't load it either
    if(!is_null($user_id) && !$user_preferences)
        {
        return;
        }

    $config_options = array();

    if(get_config_options($user_id, $config_options))
        {
        foreach($config_options as $config_option)
            {
            $param_value = $config_option['value'];

            // Prepare the value since everything is stored as a string
            if((is_numeric($param_value) && '' !== $param_value))
                {
                $param_value = (int) $param_value;
                }

            $GLOBALS[$config_option['parameter']] = $param_value;
            }
        }

    return;
    }


/**
 * Utility function to "clean" the passed $config. Cleaning consists of two parts:
 *  *    Suppressing really simple XSS attacks by refusing to allow strings
 *       containing the characters "<script" in upper, lower or mixed case.
 *  *    Unescaping instances of "'" and '"' that have been escaped by the
 *       lovely magic_quotes_gpc facility, if it's on.
 *
 * @param $config mixed thing to be cleaned.
 * @return a cleaned version of $config.
 */
function config_clean($config)
    {
    if (is_array($config))
        {
        foreach ($config as &$item)
            {
            $item = config_clean($item);
            }
        }
    elseif (is_string($config))
        {
        if (strpos(strtolower($config),"<script") !== false)
            {
            $config = '';
            }
        }
    return $config;
    }


/**
 * Generate arbitrary html
 *
 * @param string $content arbitrary HTML 
 */
function config_html($content)
    {
    echo $content;
    }


/**
 * Return a data structure that will instruct the configuration page generator functions to add
 * arbitrary HTML
 *
 * @param string $content
 */
function config_add_html($content)
    {
    return array('html',$content);
    }


 /**
 * Generate an html text entry or password block
 *
 * @param string $name the name of the text block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the text block. Usually a $lang string.
 * @param string $current the current value of the config variable being set.
 * @param boolean $password whether this is a "normal" text-entry field or a password-style
 *          field. Defaulted to false.
 * @param integer $width the width of the input field in pixels. Default: 420.
 */
function config_text_input($name, $label, $current, $password = false, $width = 420, $textarea = false, $title = null, $autosave = false, $hidden = false)
    {
    global $lang;

    if(is_null($title))
        {
        // This is how it was used on plugins setup page. Makes sense for developers when trying to debug and not much for non-technical users
        $title = str_replace('%cvn', $name, $lang['plugins-configvar']);
        }
    ?>

    <div class="Question" id="question_<?php echo $name; ?>" <?php if ($hidden){echo "style=\"display:none;\"";} ?> >
        <label for="<?php echo $name; ?>" title="<?php echo $title; ?>"><?php echo $label; ?></label>
    <?php
    if($autosave)
        {
        ?>
        <div class="AutoSaveStatus">
            <span id="AutoSaveStatus-<?php echo $name; ?>" style="display:none;"></span>
        </div>
        <?php
        }

    if($textarea == false)
        {
        ?>
        <input id="<?php echo $name; ?>"
               name="<?php echo $name; ?>"
               type="<?php echo $password ? 'password' : 'text'; ?>"
               value="<?php echo escape((string) $current); ?>"
               <?php if($autosave) { ?>onFocusOut="AutoSaveConfigOption('<?php echo $name; ?>');"<?php } ?>
               style="width:<?php echo $width; ?>px" />
        <?php
        }
    else
        {
        ?>
        <textarea id="<?php echo $name; ?>" name="<?php echo $name; ?>" style="width:<?php echo $width; ?>px"><?php echo htmlspecialchars($current, ENT_QUOTES); ?></textarea>
        <?php
        }
        ?>
        <div class="clearerleft"></div>
    </div>

    <?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a text entry configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the text block. Usually a $lang string.
 * @param boolean $password whether this is a "normal" text-entry field or a password-style
 *          field. Defaulted to false.
 * @param integer $width the width of the input field in pixels. Default: 420.
 */
function config_add_text_input($config_var, $label, $password = false, $width = 420, $textarea = false, $title = null, $autosave = false, $hidden=false)
    {
    return array('text_input', $config_var, $label, $password, $width, $textarea, $title, $autosave, $hidden);
    }


/**
* Generate a data structure to instruct the configuration page generator to add a hidden input
* 
* @param string $cf_var_name  Plugins' configuration variable name
* @param string $cf_var_value Value
* 
* @return array
*/
function config_add_hidden_input(string $cf_var_name, string $cf_var_value = '')
    {
    return array('text_hidden_input', $cf_var_name, $cf_var_value);
    }


/**
* Generate an HTML input file with its own form
*
* @param string $name        HTML input file name attribute
* @param string $label
* @param string $form_action URL where the form should post to
* @param int    $width       Wdidth of the input file HTML tag. Default - 420
*/
function config_file_input($name, $label, $current, $form_action, $width = 420, $valid_extensions = array(), $file_preview = false)
    {
    global $lang,$storagedir;

    if($current !=='')
        {
        $origin_in_config = (substr($current, 0, 13) != '[storage_url]');
        if ($origin_in_config)
            {
            # Current value may have originated in config.php - file uploader to consider this unset
            # to enable override of config.php by uploading a file.
            $current = '';
            }
        else
            {
            $missing_file = str_replace('[storage_url]', $storagedir, $current);
            $pathparts=explode("/",$current);
            }
        }

    ?>
    <div class="Question" id="question_<?php echo escape($name); ?>">
        <form method="POST" action="<?php echo escape($form_action); ?>" enctype="multipart/form-data">
        <label <?php if ($file_preview && $current !== "") echo 'id="config-image-preview-label"'; ?> for="<?php echo escape($name); ?>"><?php echo htmlspecialchars($label); ?></label>
        <div class="AutoSaveStatus">
        <span id="AutoSaveStatus-<?php echo escape($name); ?>" style="display:none;"></span>
        </div>
        <?php
        if($current !== '' && $pathparts[1]=="system" && !file_exists($missing_file))
            {
            ?>
            <span><?php echo htmlspecialchars($lang['applogo_does_not_exists']); ?></span>
            <input type="submit" name="clear_<?php echo escape($name); ?>" value="<?php echo escape($lang["clearbutton"]); ?>">
            <?php
            }
        elseif('' === $current || !get_config_option(null, $name, $current_option) || $current_option === '')
            {
            ?>
            <input type="file" name="<?php echo escape($name); ?>" style="width:<?php echo (int) $width; ?>px">
            <input type="submit" name="upload_<?php echo escape($name); ?>" <?php if (count($valid_extensions) > 0) {echo 'onclick="return checkValidExtension_' . htmlspecialchars($name) . '()"';} ?> value="<?php echo escape($lang['upload']); ?>">
            <?php
            if (count($valid_extensions) > 0)
                {
                ?>
                <script>
                function checkValidExtension_<?php echo htmlspecialchars($name) ?>()
                    {
                    let file_path = document.getElementsByName("<?php echo escape($name); ?>")[0].value;
                    let ext = file_path.toLowerCase().substr(file_path.lastIndexOf(".")+1);
                    let valid_extensions = [<?php
                        foreach ($valid_extensions as $extension) {
                            echo '"' . escape($extension) . '",';
                        } ?>];
                    if (file_path != "" && valid_extensions.includes(ext)) return true;
                    alert(<?php echo '"' . escape(str_replace('%%EXTENSIONS%%', implode(', ', $valid_extensions), $lang['systemconfig_invalid_extension'])) .'"'?>);
                    return false;
                    }
                </script>
                <?php
                }
            }
        else
            {
            ?>
            <span><?php echo htmlspecialchars(str_replace('[storage_url]/', '', $current), ENT_QUOTES); ?></span>
            <input type="submit" name="delete_<?php echo escape($name); ?>" value="<?php echo escape($lang['action-delete']); ?>">
            <?php
            }
            generateFormToken($name);
            ?>
        </form>
        <?php
        if ($file_preview && $current !== "")
            {
            global $baseurl; ?>
            <div id="preview_<?php echo escape($name); ?>">
            <img class="config-image-preview" src="<?php echo escape($baseurl . '/filestore/' . str_replace('[storage_url]/', '', $current)) . '?v=' . date("s") ?>" alt="<?php echo escape($lang["preview"] . ' - ' . $label) ?>">
            </div>
            <?php } ?>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

/**
 * Generate colour picker input
 *
 * @param string $name          HTML input name attribute
 * @param string $label
 * @param string $current       Current value
 * @param string $default       Default value
 * @param string $title         Title
 * @param boolean $autosave     Automatically save the value on change
 * @param string on_change_js   JavaScript run onchange of value (useful for "live" previewing of changes)
 */
function config_colouroverride_input($name, $label, $current, $default, $title=null, $autosave=false, $on_change_js=null, $hidden=false)
    {
    global $lang;
    $checked=$current && $current!=$default;
    if (is_null($title))
        {
        // This is how it was used on plugins setup page. Makes sense for developers when trying to debug and not much for non-technical users
        $title = str_replace('%cvn', $name, $lang['plugins-configvar']);
        }
    ?><div class="Question" style="min-height: 1.5em;" id="question_<?php echo $name; ?>" <?php if ($hidden){echo "style=\"display:none;\"";} ?> >
        <label for="<?php echo $name; ?>" title="<?php echo $title; ?>"><?php echo $label; ?></label>
        <div class="AutoSaveStatus">
            <span id="AutoSaveStatus-<?php echo $name; ?>" style="display:none;"></span>
        </div>
        <input type="checkbox" <?php if ($checked) { ?>checked="true" <?php } ?>onchange="
            jQuery('#container_<?php echo $name; ?>').toggle();
            if(!this.checked)
            {
            jQuery('#<?php echo $name; ?>').val('<?php echo $default; ?>');
        <?php if ($autosave)
            {
            ?>AutoSaveConfigOption('<?php echo $name; ?>');
                jQuery('#<?php echo $name; ?>').trigger('change');
            <?php
            }
        if(!empty($on_change_js))
            {
            echo $on_change_js;
            }
        ?>
            }
            " style="float: left;" />
        <div id="container_<?php echo $name; ?>"<?php if (!$checked) { ?>style="display: none;" <?php } ?>>
            <input id="<?php echo $name; ?>" name="<?php echo $name; ?>" type="text" value="<?php echo escape($current); ?>" onchange="<?php
            if ($autosave)
                {
                ?>AutoSaveConfigOption('<?php echo $name; ?>');<?php
                }
            if(!empty($on_change_js))
                {
                echo $on_change_js;
                }
            ?>" default="<?php echo $default; ?>" />
            <script>
                jQuery('#<?php echo $name; ?>').spectrum({
                    showAlpha: true,
                    showInput: true,
                    clickoutFiresChange: true,
                    preferredFormat: 'rgb'
                });
            </script>
        </div>
        <div class="clearerleft"></div>
        </div>
        
    <?php
    }

/**
* Return a data structure that will be used to generate the HTML for
* uploading a file
*
* @param string  $name               HTML input file name attribute
* @param string  $label              Label for field
* @param string  $form_action        URL where the form should post to
* @param int     $width              Width of the input file HTML tag. Default - 420
* @param array   $valid_extensions   Optional array of file extensions that will be validated during upload, see config_process_file_input()
*/
function config_add_file_input($config_var, $label, $form_action, $width = 420, $valid_extensions = array(), $file_preview = false)
    {   
    return array('file_input', $config_var, $label, $form_action, $width, $valid_extensions, $file_preview);
    }


/**
 * Generate an html single-select + options block
 *
 * @param string        $name      The name of the select block. Usually the name of the config variable being set.
 * @param string        $label     The user text displayed to label the select block. Usually a $lang string.
 * @param string        $current   The current value of the config variable being set.
 * @param string array  $choices   The array of the alternatives -- the options in the select block. The keys
 *                                 are used as the values of the options, and the values are the alternatives the user sees. (But
 *                                 see $usekeys, below.) Usually a $lang entry whose value is an array of strings.
 * @param boolean       $usekeys   Tells whether to use the keys from $choices as the values of the options. If set
 *                                 to false the values from $choices will be used for both the values of the options and the text
 *                                 the user sees. Defaulted to true.
 * @param integer       $width     The width of the input field in pixels. Default: 420.
 * @param string        $title     Title to be used for the label title. Default: null
 * @param boolean       $autosave  Flag to say whether the there should be an auto save message feedback through JS. Default: false
 *                                 Note: onChange event will call AutoSaveConfigOption([option name])
 */
function config_single_select($name, $label, $current, $choices, $usekeys = true, $width = 420, $title = null, $autosave = false, $on_change_js=null,$hidden=false)
    {
    global $lang;
    
    if(is_null($title))
        {
        // This is how it was used on plugins setup page. Makes sense for developers when trying to debug and not much for non-technical users
        $title = str_replace('%cvn', $name, $lang['plugins-configvar']);
        }
    ?>
    <div class="Question" id="question_<?php echo $name; ?>" <?php if ($hidden){echo "style=\"display:none;\"";} ?> >
        <label for="<?php echo $name; ?>" title="<?php echo $title; ?>"><?php echo $label; ?></label>
        <?php
        if($autosave)
            {
            ?>
            <div class="AutoSaveStatus">
                <span id="AutoSaveStatus-<?php echo $name; ?>" style="display:none;"></span>
            </div>
            <?php
            }
        ?>
        <select id="<?php echo $name; ?>"
                name="<?php echo $name; ?>"
                <?php if($autosave) { ?> onChange="<?php echo $on_change_js; ?>AutoSaveConfigOption('<?php echo $name; ?>');"<?php } ?>
                style="width:<?php echo $width; ?>px">
        <?php
        foreach($choices as $key => $choice)
            {
            $value = $usekeys ? $key : $choice;
            echo '<option value="' . $value . '"' . (($current == $value) ? ' selected' : '') . ">$choice</option>";
            }
        ?>
        </select>
     <div class="clearerleft"></div>
    </div>
    <?php
    }


/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a single select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param string array $choices the array of the alternatives -- the options in the select block. The keys
 *          are used as the values of the options, and the values are the alternatives the user sees. (But
 *          see $usekeys, below.) Usually a $lang entry whose value is an array of strings.
 * @param boolean $usekeys tells whether to use the keys from $choices as the values of the options. If set
 *          to false the values from $choices will be used for both the values of the options and the text
 *          the user sees. Defaulted to true.
 * @param integer $width the width of the input field in pixels. Default: 420.
 */
function config_add_single_select($config_var, $label, $choices = '', $usekeys = true, $width = 420, $title = null, $autosave = false, $on_change_js=null, $hidden=false)
    {
    return array('single_select', $config_var, $label, $choices, $usekeys, $width, $title, $autosave, $on_change_js, $hidden);
    }


/**
 * Generate an html boolean select block
 *
 * @param string        $name      The name of the select block. Usually the name of the config variable being set.
 * @param string        $label     The user text displayed to label the select block. Usually a $lang string.
 * @param boolean       $current   The current value (true or false) of the config variable being set.
 * @param string array  $choices   Array of the text to display for the two choices: False and True. Defaults
 *                                 to array('False', 'True') in the local language.
 * @param integer       $width     The width of the input field in pixels. Default: 420.
 * @param string        $title     Title to be used for the label title. Default: null
 * @param boolean       $autosave  Flag to say whether the there should be an auto save message feedback through JS. Default: false
 *                                 Note: onChange event will call AutoSaveConfigOption([option name])
 * @param string        $help      Help text to display for this question
 * @param boolean       $reload_page Reload the page after saving, useful for large CSS changes.
 */
function config_boolean_select(
    $name,
    $label,
    $current,
    $choices = '',
    $width = 420,
    $title = null,
    $autosave = false,
    $on_change_js = null,
    $hidden = false,
    string $help = '',
    bool $reload_page = false
)
    {
    global $lang;

    $help = trim($help);

    if($choices == '')
        {
        $choices = $lang['false-true'];
        }

    if(is_null($title))
        {
        // This is how it was used on plugins setup page. Makes sense for developers when trying to debug and not much for non-technical users
        $title = str_replace('%cvn', $name, $lang['plugins-configvar']);
        }
    
    $html_question_id = "question_{$name}";
    ?>
    <div class="Question" id="<?php echo escape($html_question_id); ?>" <?php if ($hidden){echo "style=\"display:none;\"";} ?> >
        <label for="<?php echo $name; ?>" title="<?php echo $title; ?>"><?php echo $label; ?></label>

        <?php
        if($autosave)
            {
            ?>
            <div class="AutoSaveStatus">
                <span id="AutoSaveStatus-<?php echo $name; ?>" style="display:none;"></span>
            </div>
            <?php
            }
            ?>
        <select id="<?php echo $name; ?>"
                name="<?php echo $name; ?>"
                <?php if($autosave) { ?>
                    onChange="<?php echo $on_change_js; ?>AutoSaveConfigOption('<?php echo escape($name); ?>'<?php echo $reload_page ? ", true" : ""?>);"
                <?php } ?>
                style="width:<?php echo $width; ?>px">
            <option value="1"<?php if($current == '1') { ?> selected<?php } ?>><?php echo $choices[1]; ?></option>
            <option value="0"<?php if($current == '0') { ?> selected<?php } ?>><?php echo $choices[0]; ?></option>
        </select>
        <?php
        if ($help !== '')
            {
            render_question_form_helper($help, $html_question_id, []);
            }
        ?>
        <div class="clearerleft"></div>
    </div>
    <?php
    }


/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a boolean configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param string array $choices array of the text to display for the two choices: False and True. Defaults
 *          to array('False', 'True') in the local language.
 * @param integer $width the width of the input field in pixels. Default: 420.
 * @param string $help Help text to display for this question
 * @param boolean $reload_page Reload the page after saving.
 */
function config_add_boolean_select($config_var, $label, $choices = '', $width = 420, $title = null, $autosave = false,$on_change_js=null, $hidden=false, string $help = '', bool $reload_page = false)
    {
    return array('boolean_select', $config_var, $label, $choices, $width, $title, $autosave,$on_change_js,$hidden, $help, $reload_page);
    }
	
/**
 * Generate an html checkbox options block
 *
 * @param string $name the name of the checkbox block.
 * @param string $label the user text displayed to label the checkbox block. Usually a $lang string.
 * @param string array $current the current array of selected values for the config variable being set.
 * @param string array $choices the array of choices -- the list of checkboxes. The keys are
 *          used to generate the values of the checkbox, and the values are the checkbox labels the user sees. (But see
 *          $usekeys, below.) 
 * @param boolean $usekeys tells whether to use the keys from $choices as the values of the options.
 *          If set to false the values from $choices will be used for both the values of the options
 *          and the text the user sees. Defaulted to true.
 * @param integer $width the width of the input field in pixels. Default: 300. 
 * @param integer $columns the number of columns to use 
 */
function config_checkbox_select($name, $label, $current, $choices, $usekeys=true, $width=300, $columns=1, $autosave = false,$on_change_js=null, $hidden=false)
    {
    global $lang;
    if(trim($current) != "")
        {
        $currentvalues=explode(",",$current);
        }
    else
        {
        $currentvalues = [];
        }
	$wrap = 0;
	?>
	<div class="Question" id="question_<?php echo $name; ?>" <?php if ($hidden){echo "style=\"display:none;\"";} ?> >
	<label for="<?php echo escape($name)?>" ><?php echo htmlspecialchars($label)?></label>
		<?php
        if($autosave)
            {
            ?>
            <div class="AutoSaveStatus">
                <span id="AutoSaveStatus-<?php echo $name; ?>" style="display:none;"></span>
            </div>
            <?php
            }
        ?>
     
        <table cellpadding=2 cellspacing=0>
            <tr>
        <?php
        foreach($choices as $key => $choice)
			{
			$value=$usekeys?$key:$choice;
            $wrap++;
            if($wrap > $columns)
                {
                $wrap = 1;
                ?>
                </tr>
                <tr>
                <?php
                }
                ?>
            <td width="1">
                <input type="checkbox"
                       name="<?php echo $name; ?>"
                       value="<?php echo $value; ?>"
                    <?php
					if($autosave) { ?> onChange="<?php echo $on_change_js; ?>AutoSaveConfigOption('<?php echo $name; ?>');"<?php }
                    if(in_array($value, $currentvalues))
                        {
                        ?>
                        checked
                        <?php
                        }?>
					>
            </td>
            <td><?php echo htmlspecialchars(i18n_get_translated($choice)); ?>&nbsp;</td>
            <?php
            }
            ?>
            </tr>
        </table>
        <div class="clearerleft"></div>
  </div>
  
<?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multi select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param string array $choices the array of choices -- the options in the select block. The keys are
 *          used as the values of the options, and the values are the choices the user sees. (But see
 *          $usekeys, below.) Usually a $lang entry whose value is an array of strings.
 * @param boolean $usekeys tells whether to use the keys from $choices as the values of the options.
 *          If set to false the values from $choices will be used for both the values of the options
 *          and the text the user sees. Defaulted to true.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_checkbox_select($config_var, $label, $choices, $usekeys=true, $width=300, $columns=1, $autosave = false, $on_change_js=null, $hidden=false)
    {
    return array('checkbox_select', $config_var, $label, $choices, $usekeys, $width, $columns, $autosave, $on_change_js, $hidden);
    }


function config_add_colouroverride_input($config_var, $label='', $default='', $title='', $autosave=false, $on_change_js=null, $hidden=false)
    {
    return array('colouroverride_input', $config_var, $label, $default, $title, $autosave, $on_change_js, $hidden);
    }
    
/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a single RS field-type select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 * @param integer $rtype optional to specify a resource type to get fields for 
 * @param integer array $ftypes an array of field types e.g. (4,6,10) will return only fields of a date type
 */
function config_add_single_ftype_select($config_var, $label, $width=300, $rtype=false, $ftypes=array(),$autosave=false)
    {
    return array('single_ftype_select', $config_var, $label, $width, $rtype, $ftypes,$autosave);
    }
    

/**
 * Generate an html single-select + options block for selecting one of the RS field types. The
 * selected field type is posted as the value of the "ref" column of the selected field type.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $current the current value of the config variable being set
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_single_ftype_select($name, $label, $current, $width=300, $rtype=false, $ftypes=array(), $autosave = false)
    {
    global $lang;
	$fieldtypefilter="";
    $params = [];
	if(count($ftypes)>0)
		{
		$fieldtypefilter = " type in (". ps_param_insert(count($ftypes)) .")";
        $params = ps_param_fill($ftypes, 'i');
		}
		
    if($rtype===false){
    	$fields= ps_query('select ' . columns_in("resource_type_field") . ' from resource_type_field ' .  (($fieldtypefilter=="")?'':' where ' . $fieldtypefilter) . ' order by title, name', $params, "schema");
    }
    else{
    	$fields= ps_query("select " . columns_in("resource_type_field") . " from resource_type_field where resource_type= ? " .  (($fieldtypefilter=="")?"":" and " . $fieldtypefilter) . "order by title, name", array_merge(['i', $rtype], $params),"schema");
    }
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    
     <?php
    if($autosave)
        {
        ?>
        <div class="AutoSaveStatus">
            <span id="AutoSaveStatus-<?php echo $name; ?>" style="display:none;"></span>
        </div>
        <?php
        }
        ?>
    <select name="<?php echo $name?>" id="<?php echo $name?>" style="width:<?php echo $width ?>px"
    <?php if($autosave) { ?> onChange="AutoSaveConfigOption('<?php echo $name; ?>');"<?php } ?>>
    <option value="" <?php echo (($current=="")?' selected':'') ?>><?php echo $lang["select"]; ?></option>
<?php
    foreach($fields as $field)
        {
        echo '    <option value="'. $field['ref'] . '"' . (($current==$field['ref'])?' selected':'') . '>' . lang_or_i18n_get_translated($field['title'],'fieldtitle-') . '</option>';
        }
?>
    </select>
  <div class="clearerleft"></div>
  </div>
<?php
    }

/**
* Generate Javascript function used for auto saving individual config options
*
* @param string $post_url URL to where the data will be posted
*/
function config_generate_AutoSaveConfigOption_function($post_url)
    {
    global $lang;
    ?>
    
    <script>
    function AutoSaveConfigOption(option_name, reload_page = false)
        {
        jQuery('#AutoSaveStatus-' + option_name).html('<?php echo $lang["saving"]; ?>');
        jQuery('#AutoSaveStatus-' + option_name).show();

        if (jQuery('input[name=' + option_name + ']').is(':checkbox')) {
            var option_value = jQuery('input[name=' + option_name + ']:checked').map(function(){
            return jQuery(this).val();
          }).get().toString();
        
        }
        
        else {
            var option_value = jQuery('#' + option_name).val();
        }
        
        var post_url  = '<?php echo $post_url; ?>';
        var post_data = {
            ajax: true,
            autosave: true,
            autosave_option_name: option_name,
            autosave_option_value: option_value,
            <?php echo generateAjaxToken($post_url); ?>
        };

        jQuery.post(post_url, post_data, function(response) {

            if(response.success === true)
                {
                jQuery('#AutoSaveStatus-' + option_name).html('<?php echo $lang["saved"]; ?>');
                jQuery('#AutoSaveStatus-' + option_name).fadeOut('slow');
                if (reload_page)
                    {
                    location.reload();
                    }
                }
            else if(response.success === false && response.message && response.message.length > 0)
                {
                jQuery('#AutoSaveStatus-' + option_name).html("<?php echo escape($lang['save-error']); ?> " + response.message);
                }
            else
                {
                jQuery('#AutoSaveStatus-' + option_name).html("<?php echo escape($lang['save-error']); ?>");
                }

        }, 'json');

        return true;
        }
    </script>
    
    <?php
    }


function config_process_file_input(array $page_def, $file_location, $redirect_location)
    {
    global $baseurl, $storagedir, $storageurl, $banned_extensions;

    $file_server_location = $storagedir . '/' . $file_location;

    // Make sure there is a target location
    if(!(file_exists($file_server_location) && is_dir($file_server_location)))
        {
        mkdir($file_server_location, 0777, true);
        }

    $redirect = false;

    foreach($page_def as $page_element)
        {
        if($page_element[0] !== 'file_input')
            {
            continue;
            }

        $config_name = $page_element[1];
        $valid_extensions = $page_element[5];

        // DELETE
        if(getval('delete_' . $config_name, '') !== '' && enforcePostRequest(false))
            {
            if(get_config_option(null, $config_name, $delete_filename))
                {
                $delete_filename = str_replace('[storage_url]' . '/' . $file_location, $file_server_location, $delete_filename);

                if(file_exists($delete_filename))
                    {
                    unlink($delete_filename);
                    hook("configdeletefilesuccess",'',array($delete_filename));
                    }
                delete_config_option(null, $config_name);
                $redirect = true;
                }
            }
        // CLEAR
        if(getval('clear_' . $config_name, '') !== '' && enforcePostRequest(false))
			{
			if(get_config_option(null, $config_name, $missing_file))
                {
				$missing_file = str_replace('[storage_url]' . '/' . $file_location, $file_server_location, $missing_file);
				 if(!file_exists($missing_file))
					{
					set_config_option(null, $config_name, '');

					$redirect = true;
					}
				}
			}

        // UPLOAD
        if(getval('upload_' . $config_name, '') !== '' && enforcePostRequest(false))
            {
            if(isset($_FILES[$config_name]['tmp_name']) && is_uploaded_file($_FILES[$config_name]['tmp_name']))
                {
                $uploaded_file_pathinfo  = pathinfo($_FILES[$config_name]['name']);
                $uploaded_file_extension = $uploaded_file_pathinfo['extension'];
                $uploaded_filename       = sprintf('%s/%s.%s', $file_server_location, $config_name, $uploaded_file_extension);
                // We add a placeholder for storage_url so we can reach the file easily 
                // without storing the full path in the database
                $saved_filename          = sprintf('[storage_url]/%s/%s.%s', $file_location, $config_name, $uploaded_file_extension);

                if(is_banned_extension($uploaded_file_extension))
                    {
                    trigger_error('You are not allowed to upload "' . $uploaded_file_extension . '" files to the system!');
                    }
                
                if (count($valid_extensions) > 0 && !check_valid_file_extension($_FILES[$config_name], $valid_extensions))
                    {
                    trigger_error('File type not valid for this selection. Please choose from ' . implode(', ', $valid_extensions) . '.');
                    }

                if(!move_uploaded_file($_FILES[$config_name]['tmp_name'], $uploaded_filename))
                    {
                    unset($uploaded_filename);
                    }
                }

            if(isset($uploaded_filename) && set_config_option(null, $config_name, $saved_filename))
                {
                $redirect = true;
                hook("configuploadfilesuccess",'',array($uploaded_filename));
                }
            }
        }

    if($redirect)
        {
        redirect($redirect_location);
        }
    }


/**
* Generates HTML foreach element found in the page definition
* 
* @param array $page_def Array of all elements for which we need to generate HTML
*/
function config_generate_html(array $page_def)
    {
    global $lang,$baseurl;
    $included_colour_picker_library=false;

    foreach($page_def as $def)
        {
        if(!isset($def[0])){continue;}
        switch($def[0])
            {
            case 'html':
                config_html($def[1]);
                break;

            case 'text_input':
                config_text_input($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8]);
                break;

            case 'file_input':
                config_file_input($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6]);
                break;

            case 'boolean_select':
                config_boolean_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8], $def[9], $def[10]);
                break;

            case 'single_select':
                config_single_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8], $def[9]);
                break;
			
			 case 'checkbox_select':
                config_checkbox_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8], $def[9]);
                break;

            case 'colouroverride_input':
                if (!$included_colour_picker_library)
                    {
                    ?><script src="<?php echo $baseurl; ?>/lib/spectrum/spectrum.js"></script>
                        <link rel="stylesheet" href="<?php echo $baseurl; ?>/lib/spectrum/spectrum.css" />
                    <?php
                    $included_colour_picker_library=true;
                    }
                config_colouroverride_input($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5],$def[6],$def[7]);
                break;
            case 'multi_rtype_select':
                config_multi_rtype_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
            case 'single_ftype_select':
                config_single_ftype_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5],$def[6]);
                break;
            case 'multi_archive_select':
                config_multi_archive_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4]);
                break;
            }
        }
    }


/**
* Merge all non image configurations
*
* @return array Returns merged array of non image configurations.
*/
function config_merge_non_image_types()
    {
    global $non_image_types,$ffmpeg_supported_extensions,$unoconv_extensions,$ghostscript_extensions;

    return array_unique(
        array_map(
            'strtolower',
            array_merge(
                $non_image_types,
                $ffmpeg_supported_extensions,
                $unoconv_extensions,
                $ghostscript_extensions)));
    }

function get_header_image($full = false)
    {
    global $linkedheaderimgsrc, $baseurl_short, $baseurl, $storageurl;

    if(trim($linkedheaderimgsrc) != "")
        {
        $header_img_src = $linkedheaderimgsrc;
        if(substr($header_img_src, 0, 4) !== 'http')
            {
            // Set via System Config page?
            if (substr($header_img_src, 0, 13) == '[storage_url]')
                {
                // Parse and replace the storage URL
                $header_img_src = str_replace('[storage_url]', $storageurl, $header_img_src);
                }
            else
                {
                // Set via config.php
                // if image source already has the baseurl short, then remove it and add it here
                if(substr($header_img_src, 0, 1) === '/')
                    {
                    $header_img_src = substr($header_img_src, 1);
                    }
                $header_img_src = $baseurl_short . $header_img_src;
                }

            if($full && substr($header_img_src, 0, 1) === '/')
                {
                $header_img_src = $baseurl . substr($header_img_src, 1);
                }
            }
        }
    else 
        {
        $header_img_src = $baseurl.'/gfx/titles/title-black.svg';
        }
        
    return $header_img_src;
    }

/**
* Used to block deletion of 'core' fields. Any variable added to the $corefields array will be checked before a field is deleted and if the field is referenced by one of these core variables the deletion will be blocked
* 
* @param string $source Optional origin of variables e.g. 'Transform plugin'
* @param array $varnames Array of variable names
*
* @return void
*/
function config_register_core_fieldvars($source="BASE", $varnames=array())
    {
    global $corefields;
    if(!isset($corefields[$source]))
        {
        $corefields[$source] = array();
        }    
    
    foreach($varnames as $varname)
        {
        if(preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/',$varname))
            {
            $corefields[$source][] = $varname;
            }
        }
    }


/**
* Used to block deletion of 'core' fields.
* 
* @param string $source What part (e.g plugin) relies on this list of metadata fields
* @param array  $refs   List of metadata field IDs to prevent being deleted
*/
function config_register_core_field_refs(string $source, array $refs)
    {
    global $core_field_refs;

    $source = trim($source);
    $source = ($source !== '' ? $source : 'BASE');

    if(!isset($core_field_refs[$source]))
        {
        $core_field_refs[$source] = [];
        }

    foreach($refs as $ref)
        {
        if(is_int_loose($ref) && $ref > 0)
            {
            $core_field_refs[$source][] = $ref;
            }
        }

    return;
    }

/**
 * Run PHP code on array of variables. Used for modifying $GLOBALS.
 *
 * @param  array   $variables   Array of variables to apply override on.
 * @param  string  $code        Signed string containing the PHP code to run.
 * 
 * @return void
 */
function override_rs_variables_by_eval(array $variables, string $code)
    {
    global $configs_overwritten;
    // Remove all previous overwrides that have been set
    if(is_array($configs_overwritten) && count($configs_overwritten) != 0)
        {
        foreach($configs_overwritten as $option => $value)
            {
            $variables[$option] = $value;
            }
        }

    $temp_variables = $variables;
    extract($temp_variables, EXTR_REFS | EXTR_SKIP);
    eval(eval_check_signed($code));
    $temp_array = [];
    foreach($temp_variables as $temp_variable_name => $temp_variable_val)
        {
        if($variables[$temp_variable_name] !== $temp_variable_val)
            {
            $temp_array[$temp_variable_name] = $GLOBALS[$temp_variable_name];
            }
        $GLOBALS[$temp_variable_name] = $temp_variable_val;
        }
    $configs_overwritten = $temp_array;
    return;
    }


/**
 * Update the resource_type_field - resource_type mappings
 *
 * @param int $ref                  Resource type field ref
 * @param array $resource_types     Array of resource type refs
 * 
 * @return void
 * 
 */
function update_resource_type_field_resource_types(int $ref,array $resource_types)
    {
    ps_query("DELETE FROM resource_type_field_resource_type WHERE resource_type_field = ?",["i",$ref]);
    if(in_array(0,$resource_types))
        {
        // Global field, cannot have specific fields assigned
        ps_query("UPDATE resource_type_field SET global=1 WHERE ref = ?",["i",$ref]);
        }
    elseif(count($resource_types)>0)
        {
        $query = "INSERT INTO resource_type_field_resource_type (resource_type_field, resource_type) VALUES ";
        $valuestring = "(" . (int)$ref . (str_repeat(",?),(" . $ref,count($resource_types)-1)) . ",?)";
        ps_query($query .$valuestring,ps_param_fill($resource_types,"i"));
        ps_query("UPDATE resource_type_field SET global=0 WHERE ref = ?",["i",$ref]);
        }
    clear_query_cache("schema");
    }


/**
 * Get all resource_type->resource-type_field associations
 * 
 * @param array $fields Optional array of resource_type_field data returned by get_resource_type_fields()
 * 
 * @return array    Array with resource_type_field ID as keys and arrays of resource_type IDs as values
 * 
 */
function get_resource_type_field_resource_types(array $fields = [])
    {
    $field_restypes = [];
    $allrestypes = array_column(get_resource_types("",false,true,true),"ref");
    if(count($fields)==0)
        {
        $fields = get_resource_type_fields();
        }

    foreach($fields as $field)
        {
        if($field["global"]==1)
            {
            $field_restypes[$field["ref"]] = $allrestypes;
            }
        else
            {
            $field_restypes[$field["ref"]] = explode(",",(string) $field["resource_types"]);
            }
        }
    return $field_restypes;
    }

/**
 * Create a new resource type with the specified name
 *
 * @param $name         Name of new resouce type
 * 
 * @return int| bool    ref of new resource type or false if invalid data passed
 * 
 */
function create_resource_type($name)
    {
    if(!checkperm('a') || trim($name) == "")
        {
        return false;
        }

    ps_query("INSERT INTO resource_type (name) VALUES (?) ",array("s",$name));
    $newid = sql_insert_id();
    clear_query_cache("schema");
    return $newid;
    }

/**
 * Save updated resource_type data
 *
 * @param int   $ref        Ref of resource type
 * @param array $savedata   Array of column values
 * 
 * @return bool
 * 
 */

function save_resource_type(int $ref, array $savedata)
    {
    global $execution_lockout;

    $restypes = get_resource_types("",true,false,true);
    $restype_refs = array_column($restypes,"ref");
    if(!checkperm('a') || !in_array($ref,$restype_refs))
        {
        return false;
        }

    $setcolumns = [];
    $setparams = [];
    $restypes = array_combine($restype_refs,$restypes);
    foreach($savedata as $savecol=>$saveval)
        {
        debug("checking for column " . $savecol . " in " . json_encode(($restype_refs),true));
        if($saveval == $restypes[$ref][$savecol])
            {
            // Unchanged value, skip
            continue;
            }
        switch($savecol)
            {
            case "name":               
                $setcolumns[] = "name";
                $setparams[] = "s";
                $setparams[] = mb_strcut($saveval, 0, 100);
                break;

            case "order_by":
            case "push_metadata":
            case "tab":
            case "colour":
                $setcolumns[] = $savecol;
                $setparams[] = "i";
                $setparams[] = $saveval;
                break;

            case "config_options":
                if (!$execution_lockout) 
                    {
                    // Not allowed to save PHP if execution_lockout set.
                    $setcolumns[] = $savecol;
                    $setparams[] = "s";
                    $setparams[] = $saveval;
                    }
                break;

            case "allowed_extensions":
                $setcolumns[] = $savecol;
                $setparams[] = "s";
                $setparams[] = $saveval;
                break;
                
            case "icon":            
                $setcolumns[] = $savecol;
                $setparams[] = "s";
                $setparams[] = mb_strcut($saveval, 0, 120);
                break;

            default:
                // Invalid option, ignore
                break;
            }
        }
    if(count($setcolumns) === 0)
        {
        return false;
        }
    
    $setparams[] = "i";
    $setparams[] = $ref;
    
    ps_query(
        "UPDATE resource_type
            SET " . implode("=?,",$setcolumns) . "=?
            WHERE ref = ?", $setparams
        );

    for($n=0;$n<count($setcolumns);$n++)
        {
        log_activity(null,LOG_CODE_EDITED,$setparams[(2*$n)+1],'resource_type',$setcolumns[$n],$ref,null,$restypes[$ref][$setcolumns[$n]]);
        }

    clear_query_cache("schema");
    return true;
    }


/**
 * Get resource_type data
 *
 * @param int $ref
 * 
 * @return array
 * 
 */
function rs_get_resource_type(int $ref)
    {
    return ps_query("SELECT " . columns_in('resource_type') . "
                    FROM resource_type
                WHERE ref = ?
                ORDER BY `name`",
            array("i",$ref),
            "schema"
            );
    }

/**
 * Save resource type field - used on pages/admin/admin_resource_type_field_edit.php
 *
 * @param int   $ref        Field ID
 * @param array $columns    Array of column data
 * @param mixed $postdata   POST'd data
 * 
 * @return bool 
 * 
 */
function save_resource_type_field(int $ref, array $columns, $postdata): bool
    {
    global $regexp_slash_replace, $migrate_data, $onload_message, $lang, $baseurl;

    $existingfield = get_resource_type_field($ref);
    $params= $syncparams = [];

    $resource_types=get_resource_types("",true,false,true);

    // Array of resource types to remove data from if no longer associated with field
    $remove_data_restypes = [];
    foreach($resource_types as $resource_type)
        {
        $resource_type_array[$resource_type["ref"]]=$resource_type["name"];
        }
    $valid_columns = columns_in("resource_type_field",null,null,true);
    foreach ($columns as $column=>$column_detail)		
        {
        if(!in_array($column,$valid_columns))
            {
            continue;
            }
        if ($column_detail[2]==1)
            {
            $val= (int)(bool)($postdata[$column] ?? 0);
            }		
        else
            {
            $val=trim($postdata[$column] ?? "");
            if($column == 'regexp_filter')
                {
                $val = str_replace('\\', $regexp_slash_replace, $val);   
                }
        
            if($column == "type" && $val != $existingfield["type"] && (bool)($postdata["migrate_data"] ?? false))
                {
                // Need to migrate field data
                $migrate_data = true;				
                }
            
            // Set shortname if not already set or invalid
            if($column=="name" && ($val=="" || in_array($val,array("basicday","basicmonth","basicyear"))))
                {
                $val="field" . $ref;
                }

            if($column === 'tab' && $val == 0)
                {
                $val = ''; # set to blank so the code will convert to SQL NULL later
                }
            }
        if (isset($sql))
            {
            $sql.=",";
            }
        else
            {
            $sql="UPDATE resource_type_field SET ";
            }		
        
        $sql.="{$column}=";
        if ($val=="")
            {
            $sql.="NULL";
            }
        else    
            {
            $sql.="?";
            $params[]=($column_detail[2]==1?"i":"s"); // Set the type, boolean="i", other two are strings
            $params[]=$val;
            }
        if($column == "global")
            {
            // Also need to update all resource_type_field -> resource_type associations
            $setresypes = [];
            if($val == 0)
                {
                $currentrestypes = get_resource_type_field_resource_types([$existingfield]);
                // Only need to check them if field is not global
                foreach($resource_type_array as $resource_type=>$resource_type_name)
                    {
                    if(trim($postdata["field_restype_select_" . $resource_type] ?? "") != "")
                        {
                        $setresypes[] = $resource_type;
                        }
                    }
                 // Set to remove existing data from the resource types that had data stored
                 $remove_data_restypes = $existingfield["type"] == 1 ? array_column($resource_type_array,"ref") : array_diff($currentrestypes[$ref],$setresypes);
                }
            update_resource_type_field_resource_types($ref,$setresypes);
            }

        log_activity(null,LOG_CODE_EDITED,$val,'resource_type_field',$column,$ref);
        }
    // add field_constraint sql
    if (isset($postdata["field_constraint"]) && trim($postdata["field_constraint"]) != "")
        {
        $sql.=",field_constraint=?";
        $params[]="i";$params[]= (int)$postdata["field_constraint"];
        }

    // Add automatic nodes ordering if set (available only for fixed list fields - except category trees)
    $sql .= ", automatic_nodes_ordering = ?";
    $params[]="i";$params[]= (1 == ($postdata['automatic_nodes_ordering'] ?? 0) ? 1 : 0);

    $sql .= " WHERE ref = ?";
    $params[]="i";$params[]=$ref;

    ps_query($sql,$params);
    clear_query_cache("schema");
    clear_query_cache("featured_collections");

    if(count($remove_data_restypes)>0)
        {
        // Don't delete invalid nodes immediately in case of accidental/inadvertent change - just show a link to the cleanup page
        $cleanup_url = generateURL($baseurl . "/pages/tools/cleanup_invalid_nodes.php",["cleanupfield"=>$ref, "cleanuprestype"=>implode(",",$remove_data_restypes)]);
        $onload_message= ["title" => $lang["cleanup_invalid_nodes"],"text" => str_replace("%%CLEANUP_LINK%%","<br/><a href='" . $cleanup_url . "' target='_blank'>" . $lang["cleanup_invalid_nodes"] . "</a>",$lang["information_field_restype_deselect_cleanup"])];
        }
    
    hook('afterresourcetypefieldeditsave');
    
    return true;
    }

function get_resource_type_field_columns()
    {
    global $lang;

    $resource_type_field_column_definitions = execution_lockout_remove_resource_type_field_props([
        'active'                   => [$lang['property-field_active'],'',1,1],
        'global'                   => [$lang['property-resource_type'],'',1,0],
        'title'                    => [$lang['property-title'],'',0,1],
        'type'                     => [$lang['property-field_type'],'',0,1],
        'linked_data_field'        => [$lang['property-field_raw_edtf'],'',0,1],
        'name'                     => [$lang['property-shorthand_name'],$lang['information-shorthand_name'],0,1],
        'required'                 => [$lang['property-required'],'',1,1],
        'order_by'                 => [$lang['property-order_by'],'',0,0],
        'keywords_index'           => [$lang['property-index_this_field'],$lang["information_index_warning"] . " " . $lang['information-if_you_enable_indexing_below_and_the_field_already_contains_data-you_will_need_to_reindex_this_field'],1,1],
        'display_field'            => [$lang['property-display_field'],'',1,1],
        'full_width'               => [$lang['property-field_full_width'],'',1,1],
        'advanced_search'          => [$lang['property-enable_advanced_search'],'',1,1],
        'simple_search'            => [$lang['property-enable_simple_search'],'',1,1],        
        'browse_bar'               => [$lang['field_show_in_browse_bar'],'',1,1],
        'read_only'                => [$lang['property-read_only_field'], '', 1, 1],
        'exiftool_field'           => [$lang['property-exiftool_field'],'',0,1],
        'fits_field'               => [$lang['property-fits_field'], $lang['information-fits_field'], 0, 1],
        'personal_data'            => [$lang['property-personal_data'],'',1,1],
        'use_for_similar'          => [$lang['property-use_for_find_similar_searching'],'',1,1],
        'hide_when_uploading'      => [$lang['property-hide_when_uploading'],'',1,1],
        'hide_when_restricted'     => [$lang['property-hide_when_restricted'],'',1,1],
        'help_text'                => [$lang['property-help_text'],'',2,1],
        'tooltip_text'             => [$lang['property-tooltip_text'],$lang['information-tooltip_text'],2,1],
        'tab'                      => [$lang['property-tab_name'], '', 0, 0],
        'partial_index'            => [$lang['property-enable_partial_indexing'],$lang['information-enable_partial_indexing'],1,1],
        'iptc_equiv'               => [$lang['property-iptc_equiv'],'',0,1],                                  
        'display_template'         => [$lang['property-display_template'],'',2,1],
        'display_condition'        => [$lang['property-display_condition'],$lang['information-display_condition'],2,1],
        'regexp_filter'            => [$lang['property-regexp_filter'],$lang['information-regexp_filter'],2,1],
        'smart_theme_name'         => [$lang['property-smart_theme_name'],'',0,1],
        'display_as_dropdown'      => [$lang['property-display_as_dropdown'],$lang['information-display_as_dropdown'],1,1],
        'external_user_access'     => [$lang['property-external_user_access'],'',1,1],
        'omit_when_copying'        => [$lang['property-omit_when_copying'],'',1,1],
        'include_in_csv_export'    => [$lang['property-include_in_csv_export'],'',1,1],
        'autocomplete_macro'       => [$lang['property-autocomplete_macro'],'',2,1],
        'exiftool_filter'          => [$lang['property-exiftool_filter'],'',2,1],
        'value_filter'             => [$lang['property-value_filter'],'',2,1],
        'onchange_macro'           => [$lang['property-onchange_macro'],$lang['information-onchange_macro'],2,1],
    ]);

    $modify_resource_type_field_definitions=hook("modifyresourcetypefieldcolumns","",array($resource_type_field_column_definitions));
    if($modify_resource_type_field_definitions!='')
        {
        $resource_type_field_column_definitions=$modify_resource_type_field_definitions;
        }

    return $resource_type_field_column_definitions;
    }

