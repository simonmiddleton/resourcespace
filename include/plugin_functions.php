<?php
/**
 * Functions related to the management of plugins.
 *
 * @package ResourceSpace
 * @subpackage Includes
 */
/**
 * Activate a named plugin.
 *
 * Parses the plugins directory to look for a pluginname.yaml
 * file and adds the plugin to the plugins database, setting
 * the inst_version field to the version specified in the yaml file.
 *
 * @param string $name Name of plugin to be activated.
 * @return bool Returns true if plugin directory was found.
 * @see deactivate_plugin
 */
function activate_plugin($name)
    {
    $plugin_dir = get_plugin_path($name);
    if (file_exists($plugin_dir))
        {
        $plugin_yaml = get_plugin_yaml("$plugin_dir/$name.yaml", false);
        # If no yaml, or yaml file but no description present, attempt to read an 'about.txt' file
        if ('' == $plugin_yaml['desc'])
            {
            $about = $plugin_dir . $name . '/about.txt';
            if(file_exists($about))
                {
                $plugin_yaml['desc'] = substr(file_get_contents($about), 0, 95) . '...';
                }
            }
    
        # Add/Update plugin information.
        # Check if the plugin is already in the table.
        $c = ps_value("SELECT name as value FROM plugins WHERE name = ?", array("s", $name), '');

        if ($c == '')
            {
            ps_query("INSERT INTO plugins (name) VALUE (?)", array("s", $name));
            }

        ps_query("UPDATE plugins SET config_url = ?, descrip = ?, author = ?, inst_version = ?, priority = ?, update_url = ?, info_url = ?, disable_group_select = ?,
        title = ?, icon = ? WHERE name = ?", array("s", $plugin_yaml['config_url'], "s", $plugin_yaml['desc'], "s", $plugin_yaml['author'], "d", $plugin_yaml['version'],
        "i", $plugin_yaml['default_priority'], "s", $plugin_yaml['update_url'], "s", $plugin_yaml['info_url'], "i", $plugin_yaml['disable_group_select'], "s",
        $plugin_yaml['title'], "s", $plugin_yaml['icon'], "s", $plugin_yaml['name']));

        log_activity(null, LOG_CODE_ENABLED, $plugin_yaml['version'], 'plugins', 'inst_version', $plugin_yaml['name'], 'name', '', null, true);

        // Clear query cache
        clear_query_cache("plugins");

        hook("after_activate_plugin","",array($name));
        return true;
        }
    else
        {
        return false;
        }
    }
/**
 * Deactivate a named plugin.
 *
 * Blanks the inst_version field in the plugins database, which has the effect
 * of deactivating the plugin while maintaining any configuration that is stored
 * in the database.
 *
 * @param string $name Name of plugin to be deativated.
 * @see activate_plugin
 */
function deactivate_plugin($name): void
    {
    $inst_version = ps_value("SELECT inst_version AS value FROM plugins WHERE name = ?", array("s", $name), '');
  
    if($inst_version >= 0)
        {
        # Remove the version field. Leaving the rest of the plugin information.  This allows for a config column to remain (future).
        ps_query("UPDATE plugins SET inst_version = NULL WHERE name = ?", array("s", $name));

        log_activity(null, LOG_CODE_DISABLED, '', 'plugins', 'inst_version', $name, 'name', $inst_version, null, true);
        }

    // Clear query cache
    clear_query_cache("plugins");

    }

/**
 * Purge configuration of a plugin.
 *
 * Replaces config value in plugins table with NULL.  Note, this function
 * will operate on an activated plugin as well so its configuration can
 * be 'defaulted' by the plugin's configuration page.
 *
 * @param string $name Name of plugin to purge configuration.
 * @category PluginAuthors
 */
function purge_plugin_config($name)
    {
    ps_query("UPDATE plugins SET config = NULL, config_json = NULL where name = ?", array("s", $name));

    // Clear query cache
    clear_query_cache("plugins");
    }
/**
 * Load plugin .yaml file.
 *
 * Load a .yaml file for a plugin and return an array of its
 * values.
 *
 * @param string $path Path to .yaml file to open.
 * @param bool $validate Check that the .yaml file is complete. [optional, default=false]
 * @return array|bool Associative array of yaml values. If validate is false, this function will return an array of 
 *                    blank values if a yaml isn't available
 */
function get_plugin_yaml($path, $validate=true)
    {
    #We're not using a full YAML structure, so this parsing function will do
    $plugin_yaml['name'] = basename($path, '.yaml');
    $plugin_yaml['version'] = '0';
    $plugin_yaml['author'] = '';
    $plugin_yaml['info_url'] = '';
    $plugin_yaml['update_url'] = '';
    $plugin_yaml['config_url'] = '';
    $plugin_yaml['desc'] = '';
    $plugin_yaml['default_priority'] = '999';
    $plugin_yaml['disable_group_select'] = '0';
    $plugin_yaml['title'] = '';
    $plugin_yaml['icon'] = '';

    if(!(file_exists($path) && is_readable($path)))
        {
        return $validate ? false : $plugin_yaml;
        }
    $yaml_file_ptr = fopen($path, 'r');

    if ($yaml_file_ptr!=false)
        {
        while (($line = fgets($yaml_file_ptr))!='')
            {
            if (
                $line[0] != '#' #Exclude comments from parsing
                && ($pos = strpos($line,':')) != false
                ) {
                    $plugin_yaml[trim(substr($line,0,$pos))] = trim(substr($line, $pos+1));
                }
            }
            
        if ($plugin_yaml['config_url']!='' && $plugin_yaml['config_url'][0]=='/') # Strip leading spaces from the config url.
            {
            $plugin_yaml['config_url'] = trim($plugin_yaml['config_url'], '/');
            }
        fclose($yaml_file_ptr);
        if ($validate)
            {
            if (isset($plugin_yaml['name']) && $plugin_yaml['name']==basename($path,'.yaml') && isset($plugin_yaml['version']))
                {
                return $plugin_yaml;
                }
            else return false;
            }
        }
    elseif ($validate)
        {
        return false;
        }

    return $plugin_yaml;
    }

/**
 * A subset json_encode function that only works on $config arrays but has none
 * of the version-to-version variability and other "unusual" behavior of PHP's.
 * implementation.
 *
 * @param $config mixed a configuration variables array. This *must* be an array
 *      whose elements are UTF-8 encoded strings, booleans, numbers or arrays
 *      of such elements and whose keys are either numbers or UTF-8 encoded
 *      strings.
 * @return json encoded version of $config or null if $config is beyond our
 *         capabilities to encode
 */
function config_json_encode($config)
    {
    $i=0;
    $simple_keys = true;
    foreach ($config as $name => $value)
        {
        if (!is_numeric($name) || ($name != $i++))
            {
            $simple_keys = false;
            break;
            }
        }
    $output = $simple_keys?'[':'{';
    foreach ($config as $name => $value)
        {
        if (!$simple_keys)
            {
            $output .= '"' . config_encode($name) . '":';
            }
        if (is_string($value))
            {
            $output .= '"' . config_encode($value) . '"';
            }
        elseif (is_bool($value))
            {
            $output .= $value?'true':'false';
            }
        elseif (is_numeric($value))
            {
            $output .= strval($value);
            }
        elseif (is_array($value))
            {
            $output .= config_json_encode($value);
            }
        else
            {
            return null; // Give up; beyond our capabilities
            }
        $output .= ', ';
        }
    if (substr($output, -2) == ', ')
        {
        $output = substr($output, 0, -2);
        }
    return $output . ($simple_keys?']':'}');
    }

/**
 * Utility function to encode the passed string to something that conforms to
 * the json spec for a string.  Json doesn't allow strings with double-quotes,
 * backslashes or control characters in them. For double-quote and backslash,
 * the encoding is '\"' and '\\' respectively.  The encoding for control
 * characters is of the form '\uxxx' where "xxx" is the UTF-8 4-digit hex
 * value of the encoded character.
 *
 * @param $input string the string that needs encoding
 * @return an encoded version of $input
 */
function config_encode($input)
    {
    $output = '';
    for ($i = 0; $i < strlen($input); $i++)
        {
        $char = substr($input, $i, 1);
        if (ord($char) < 32)
            {
            $char = '\\u' . substr('0000' . dechex(ord($char)),-4);
            }
        elseif ($char == '"')
            {
            $char = '\\"';
            }
        elseif ($char == '\\')
            {
            $char = '\\\\';
            }
        $output .= $char;
        }
    return $output;
    }   

/**
 * Return plugin config stored in plugins table for a given plugin name.
 *
 * Queries the plugins table for a stored config value and, if found,
 * unserializes the data and returns the result.  If config isn't found
 * returns null.
 *
 * @param string $name Plugin name
 * @return mixed|null Returns config data or null if no config.
 * @see set_plugin_config
 */
function get_plugin_config($name){
    global $mysql_verbatim_queries, $mysql_charset;

    # Need verbatim queries here
    $mysql_vq = $mysql_verbatim_queries;
    $mysql_verbatim_queries = true;
    $configs = ps_query("SELECT config, config_json from plugins where name = ?", array("s", $name), 'plugins');
    $configs = $configs[0] ?? [];
    $mysql_verbatim_queries = $mysql_vq;
    if (!array_key_exists('config', $configs) || is_null($configs['config_json']))
        {
        return null;
        }
    elseif (array_key_exists('config_json', $configs) && function_exists('json_decode'))
        {
        if (!isset($mysql_charset))
            {
            $configs['config_json'] = iconv('ISO-8859-1', 'UTF-8', $configs['config_json']);
            }
            return json_decode($configs['config_json'], true);

        }
    else
        {
        return unserialize(base64_decode($configs['config']));
        }
}

/**
 * Store a plugin's configuration in the database.
 *
 * Serializes the $config parameter and stores in the config
 * and config_json columns of the plugins table.
 * <code>
 * <?php
 * $plugin_config['a'] = 1;
 * $plugin_config['b'] = 2;
 * set_plugin_config('myplugin', $plugin_config);
 * ?>
 * </code>
 *
 * @param string $plugin_name Plugin name
 * @param mixed $config Configuration variable to store.
 * @see get_plugin_config
 */
function set_plugin_config($plugin_name, $config)
    {
    global $db, $mysql_charset;
    $config = config_clean($config);
    $config_ser_bin =  base64_encode(serialize($config));
    $config_ser_json = config_json_encode($config);
    if (!isset($mysql_charset))
        {
        $config_ser_json = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $config_ser_json);
        }

    // We record the activity before running the query because log_activity() is trying to be clever and figure out the old value
    // which will make the new value also show up (incorrectly) as the old value.
    log_activity(null, LOG_CODE_EDITED, $config_ser_json, 'plugins', 'config_json', $plugin_name, 'name', null, null, true);

    ps_query("UPDATE plugins SET config = ?, config_json = ? WHERE name = ?", array("s", $config_ser_bin, "s", $config_ser_json, "s", $plugin_name));

    // Clear query cache
    clear_query_cache("plugins");

    return true;
    }

/**
 * Check if a plugin is activated.
 *
 * Returns true is a plugin is activated in the plugins database.
 *
 * @param $name Name of plugin to check
 * @return bool Returns true is plugin is activated.
 */
function is_plugin_activated($name)
    {
    $activated = ps_query("SELECT name FROM plugins WHERE name = ? and inst_version IS NOT NULL", array("s", $name), "plugins");
    return is_array($activated) && count($activated)>0;
    }


/**
 * Get active plugins
 * 
 * @return array
 */
function get_active_plugins()
    {
    return ps_query('SELECT name, enabled_groups, config, config_json FROM plugins WHERE inst_version >= 0 ORDER BY priority', array(), 'plugins');
    }

/**
 * Generate the first half of the "guts" of a plugin setup page from a page definition array. This
 * function deals with processing the POST that comes (usually) as a result of clicking on the Save
 * Configuration button.
 *
 * The page definition array is typically constructed by a series of calls to config_add_xxxx
 * functions (see below). See the setup page for the sample plugin for information on how to use
 * this and the associated functions.
 * 
 * If wishing to store array of values in one config option, in your setup page have something like the 
 * following which adds a single definition for each key of your config option:
 * foreach($usergroups as $k => $group)
 *   {
 *   global $usergroupemails;
 *   if(!isset($usergroupemails[$group["ref"]])){$usergroupemails[$group["ref"]]=array();} // Define any missing keys
 *   $page_def[] = config_add_text_list_input("usergroupemails[".$group["ref"]."]",$group["name"]); //need to pass a string that looks like: "$configoption["key"]"
 *   }
 * The key can consist of numbers, letters or an underscore contained within "" or ''. If using numbers you don't need the quote marks
 *
 *
 * @param $page_def mixed an array whose elements are generated by calls to config_add_xxxx functions
 *        each of which describes how one of the plugin's configuration variables.
 * @param $plugin_name string the name of the plugin for which the function is being invoked.
 * @return void|string Returns NULL 
 */
function config_gen_setup_post($page_def,$plugin_name)
    {
    if((getval('submit', '') != '' || getval('save','') != '') && enforcePostRequest(false))
        {
        $config=array();
        foreach ($page_def as $def)
            {
            $array_offset="";
            if(preg_match("/\[[\"|']?\w+[\"|']?\]/",$def[1],$array_offset))
                {
                $array=preg_replace("/\[[\"|']?\w+[\"|']?\]/","",$def[1]);
                preg_match("/[\"|']?\w+[\"|']?/",$array_offset[0],$array_offset);
                }
            $omit = false;
            if(!empty($array_offset))
                {
                $curr_post=getval($array,"");
                if($curr_post==""){continue;} //Ignore if Array already handled or blank
                foreach($curr_post as $key => $val)
                    {
                    $config[$array][$key] = explode(',', $val);
                    $GLOBALS[$array][$key] = explode(',', $val);
                    }
                unset($_POST[$array]); //Unset once array has been handled to prevent duplicate changes
                $omit=true;
                }
            else 
                {
                $config_global=(isset($GLOBALS[$def[1]]) ? $GLOBALS[$def[1]] :false);
                switch ($def[0])
                    {
                    case 'html':
                        $omit = true;
                        break;  
                    case 'section_header':
                        $omit = true;
                        break;
                    case 'text_list':
                        $pval = getval($def[1],'');
                        $GLOBALS[$def[1]] = (trim($pval) != '') ? explode(',',$pval) : array();
                        break;
                    case 'hidden_param':
                        break;
                    default:
                        $GLOBALS[$def[1]] = getval($def[1], is_array($GLOBALS[$def[1]])?array():'');
                        break;
                    }
                
                hook('custom_config_post', '', array($def, $config, $omit, $config_global));
                
                }
            if (!$omit)
                {
                $config[$def[1]]=$GLOBALS[$def[1]];
                }
            }
        set_plugin_config($plugin_name,$config);
        if (getval('submit','')!=''){redirect('pages/team/team_plugins.php');}
        }
    }

/**
 * Generate the second half of the "guts" of a plugin setup page from a page definition array. The
 * page definition array is typically constructed by a series of calls to config_add_xxxx functions
 * (see below). See the setup page for the sample plugin for information on how to use this and the
 * associated functions.
 *
  * If wishing to ouput array of values for one config option, in your setup page have something like the 
 * following which adds a single definition for each key of your config option:
 * foreach($usergroups as $k => $group)
 *   {
 *   global $usergroupemails;
 *   if(!isset($usergroupemails[$group["ref"]])){$usergroupemails[$group["ref"]]=array();} // Define any missing keys
 *   $page_def[] = config_add_text_list_input("usergroupemails[".$group["ref"]."]",$group["name"]); //need to pass a string that looks like: "$configoption["key"]"
 *   }
 * The key can consist of numbers, letters or an underscore contained within "" or ''. If using numbers you don't need the quote marks
 *
 * @param $page_def mixed an array whose elements are generated by calls to config_add_xxxx functions
 *          each of which describes how one of the plugin's configuratoin variables.
 * @param $plugin_name string the name of the plugin for which the function is being invoked.
 * @param $upload_status string the status string returned by config_get_setup_post().
 * @param $plugin_page_heading string the heading to be displayed for the setup page for this plugin,
 *          typically a $lang[] variable.
 * @param $plugin_page_frontm string front matter for the setup page in html format. This material is
 *          placed after the page heading and before the form. Default: '' (i.e., no front matter).
 */
function config_gen_setup_html($page_def,$plugin_name,$upload_status,$plugin_page_heading,$plugin_page_frontm='')
    {
    global $lang,$baseurl_short;
?>
    <div class="BasicsBox">
    <h1><?php echo htmlspecialchars($plugin_page_heading); ?></h1>
<?php
    $links_trail = array(
        array(
            'title' => $lang["systemsetup"],
            'href'  => $baseurl_short . "pages/admin/admin_home.php",
            'menu' =>  true
        ),
        array(
            'title' => $lang["pluginmanager"],
            'href'  => $baseurl_short . "pages/team/team_plugins.php"
        ),
        array(
            'title' => $plugin_page_heading
        )
    );
    renderBreadcrumbs($links_trail);

    if ($plugin_page_frontm!='')
        {
        echo $plugin_page_frontm;
        }
?>
        <form id="form1" name="form1" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <?php
    generateFormToken("form1");

    foreach ($page_def as $def)
        {
        $array_offset="";
        if(preg_match("/\[[\"|']?\w+[\"|']?\]/",$def[1],$array_offset))
            {
            $array=preg_replace("/\[[\"|']?\w+[\"|']?\]/","",$def[1]);
            preg_match("/[\"|']?\w+[\"|']?/",$array_offset[0],$array_offset);
            }
        
        hook ("custom_config_def", '', array($def)); //this comes first so overriding the below is possible

        switch ($def[0])
            {
            case 'section_header':
                 config_section_header($def[1], $def[2]);
                 break;
            case 'html':
                 config_html($def[1]);
                 break;     
            case 'text_input':
                config_text_input($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8]);
                break;
            case 'text_hidden_input':
                $value = (trim($def[2]) !== '' ? $def[2] : $GLOBALS[$def[1]]);
                render_hidden_input($def[1], $value);
                break;
            case 'text_list':
            if (!empty($array_offset)) 
                {
                config_text_input($def[1], $def[2], implode(',', $GLOBALS[$array][$array_offset[0]]), $def[3], $def[4]);
                }
            else
                {
                config_text_input($def[1], $def[2], implode(',', $GLOBALS[$def[1]]), $def[3], $def[4]);
                }
                
                break;
            case 'boolean_select':
                config_boolean_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4]);
                break;
            case 'single_select':
                config_single_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5]);
                break;
            case 'multi_select':
                config_multi_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5]);
                break;
            case 'single_user_select':
                config_single_user_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
            case 'multi_user_select':
                config_multi_user_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;          
            case 'single_ftype_select':
                config_single_ftype_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5],$def[6]);
                break;
            case 'multi_ftype_select':
                config_multi_ftype_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3],$def[4], $def[5]); 
                break;
            case 'single_rtype_select':
                config_single_rtype_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
            case 'multi_rtype_select':
                config_multi_rtype_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
            case 'db_single_select':
                config_db_single_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8]);
                break;
            case 'db_multi_select':
                config_db_multi_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8]);
                break;
            case 'single_group_select':
                config_single_group_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
            case 'multi_group_select':
                config_multi_group_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
            case 'checkbox_select':
                config_checkbox_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3], $def[4], $def[5], $def[6], $def[7], $def[8], $def[9]);
                break;
            case 'multi_archive_select':
                config_multi_archive_select($def[1], $def[2], $GLOBALS[$def[1]], $def[3]);
                break;
    
            }
        }
        ?>
        <div class="Question">
          <input type="submit" name="save" id="save" value="<?php echo $lang['plugins-saveconfig']?>">
          <input type="submit" name="submit" id="submit" value="<?php echo $lang['plugins-saveandexit']?>">
          <div class="clearerleft"></div>
        </div>
      </form>
    </div>
<?php
    }

/**
 * Generate an html text section header
 *
 * @param string $title the title of the section.
 * @param string $description the user text displayed to describe the section. Usually a $lang string.
 */
function config_section_header($title, $description)
    {
?>
   <div class="Question">
    <br /><h2><?php echo $title?></h2>
    <?php if ($description!=""){?>
        <p><?php echo $description?></p>
    <?php } ?>
    <div class="clearerleft"></div>
  </div>
<?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to add
 * a section header.
 *
 * @param string $title the title of the section.
 * @param string $description Usually a $lang string.
 */
function config_add_section_header($title, $description='')
    {
    return array('section_header',$title,$description);
    }


/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a comma-separated list text entry configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the text block. Usually a $lang string.
 * @param boolean $password whether this is a "normal" text-entry field or a password-style
 *          field. Defaulted to false.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_text_list_input($config_var, $label, $password=false, $width=300)
    {
    return array('text_list', $config_var, $label, $password, $width);
    }

/**
 * Generate an html multi-select + options block
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param string array $current the current value of the config variable being set.
 * @param string array $choices the array of choices -- the options in the select block. The keys are
 *          used as the values of the options, and the values are the choices the user sees. (But see
 *          $usekeys, below.) Usually a $lang entry whose value is an array of strings.
 * @param boolean $usekeys tells whether to use the keys from $choices as the values of the options.
 *          If set to false the values from $choices will be used for both the values of the options
 *          and the text the user sees. Defaulted to true.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_multi_select($name, $label, $current, $choices, $usekeys=true, $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>[]" id="<?php echo $name?>" class="MultiSelect" multiple="multiple" <?php if(count($choices) > 7) echo ' size="7"'?> style="width:<?php echo $width ?>px">
<?php
    foreach($choices as $key => $choice)
        {
        $value=$usekeys?$key:$choice;
        echo '    <option value="' . $value . '"' . ((in_array($value,$current))?' selected':'') . ">$choice</option>";
        }
?>
    </select>
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
function config_add_multi_select($config_var, $label, $choices, $usekeys=true, $width=300)
    {
    return array('multi_select', $config_var, $label, $choices, $usekeys, $width);
    }

/**
 * Generate an html single-select block for selecting one of the RS users.
 *
 * The user key (i.e., the value from the "ref" column of the user table) of the selected user is the
 * value posted.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $current the current value of the config variable being set.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_single_user_select($name, $label, $current=array(), $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>" id="<?php echo $name?>" style="width:<?php echo $width ?>px">
<?php
    $users=get_users();
    foreach ($users as $user)
        {
        echo '    <option value="' . $user['ref'] . '"' . (($user['ref']==$current)?' selected':'') . '>' . $user['fullname'] . ' (' . $user['email'] . ')</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
  
<?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a single RS user select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_single_user_select($config_var, $label, $width=300)
    {
    return array('single_user_select', $config_var,$label, $width);
    }

/**
 * Generate an html multi-select block for selecting from among RS users.
 *
 * An array consisting of the user keys (i.e., values from the "ref" column of the user table) for the
 * selected users is the value posted.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer array $current the current value of the config variable being set.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_multi_user_select($name, $label, $current=array(), $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>[]" id="<?php echo $name?>" class="MultiSelect" multiple="multiple" size="7" style="width:<?php echo $width ?>px">
<?php
    $users=get_users();
    foreach ($users as $user)
        {
        echo '    <option value="' . $user['ref'] . '"' . ((in_array($user['ref'],$current))?' selected':'') . '>' . $user['fullname'] . ' (' . $user['email'] . ')</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
  
<?php
    }
    


/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multiple RS user select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_multi_user_select($config_var, $label, $width=300)
    {
    return array('multi_user_select', $config_var, $label, $width);
    }
    
/**
 * Generate an html single-select block for selecting from among RS user groups.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer array $current the current value of the config variable being set.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_single_group_select($name, $label, $current=array(), $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>" id="<?php echo $name?>" style="width:<?php echo $width ?>px">
<?php
    $usergroups=get_usergroups();
    foreach ($usergroups as $usergroup)
        {
        echo '    <option value="' . $usergroup['ref'] . '"' . (($usergroup['ref']==$current)?' selected':'') . '>' . $usergroup['name'] . '</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
  
<?php
    }
    
/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a single RS group select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_single_group_select($config_var, $label, $width=300)
    {
    return array('single_group_select', $config_var, $label, $width);
    }
    
/**
 * Generate an html multi-select block for selecting from among RS user groups.
 *
 * An array consisting of the group keys (i.e., values from the "ref" column of the usergroup table) for the
 * selected groups is the value posted.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer array $current the current value of the config variable being set.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_multi_group_select($name, $label, $current=array(), $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>[]" id="<?php echo $name?>" class="MultiSelect" multiple="multiple" size="7" style="width:<?php echo $width ?>px">
<?php
    $usergroups=get_usergroups();
    foreach ($usergroups as $usergroup)
        {
        echo '    <option value="' . $usergroup['ref'] . '"' . ((in_array($usergroup['ref'],$current))?' selected':'') . '>' . $usergroup['name'] . '</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
  
<?php
    }
    
/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multiple RS user select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_multi_group_select($config_var, $label, $width=300)
    {
    return array('multi_group_select', $config_var, $label, $width);
    }

/**
 * Generate an html multi-select + options block for selecting multiple RS field types. The
 * selected field type is posted as an array of the values of the "ref" column of the selected
 * field types.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param array  $current Array holding the current field IDs of the config variable being set
 * @param integer $width the width of the input field in pixels. Default: 300.
 * @param integer $size - Number of visible options. Default 7
 * @param integer $rtype - Limit to fields associated with a specific resource type 
 */
function config_multi_ftype_select($name, $label, $current, $width=300,$size=7,$rtype=false) 
    {
    global $lang;
    if($rtype===false){
        $fields = get_resource_type_fields("","order_by");
    }
    else{
        $fields = get_resource_type_fields($rtype,"order_by");
    }
    $all_resource_types = get_resource_types();
    $resource_types = array_column($all_resource_types,"name","ref");
    ?>
    <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>[]" id="<?php echo $name?>" class="MultiSelect" multiple="multiple" size="<?php echo $size?>" style="width:<?php echo $width ?>px">
    <?php
    foreach($fields as $field)
        {
        $str_restypes = "";
        $fieldrestypes = explode(",",(string)$field["resource_types"]);
        $fieldrestypenames = [];
        if($field["global"] != 1)
            {
            foreach($fieldrestypes as $fieldrestype)
                {
                $fieldrestypenames[] =i18n_get_translated($resource_types[$fieldrestype]);
                }
            if(count($fieldrestypes) < count($all_resource_types)-2)
                {
                // Don't show this if they are linked to all but one resource types
                $str_restypes = " (" .  implode(",",$fieldrestypenames) . ")";
                }
            }
        echo '<option value="'. $field['ref'] . '"' . (in_array($field['ref'],$current) ? ' selected':'') . '>' . htmlspecialchars(lang_or_i18n_get_translated($field['title'],'fieldtitle-') .  $str_restypes) .  '</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
<?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multiple RS field-type select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_multi_ftype_select($config_var, $label, $width=300,$size=7,$ftype=false) 
    {
    return array('multi_ftype_select',$config_var, $label, $width,$size,$ftype); 
    }

/**
 * Generate an html single-select + options block for selecting one of the RS resource types. The
 * selected field type is posted as the value of the "ref" column of the selected resource type.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $current the current value of the config variable being set
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_single_rtype_select($name, $label, $current, $width=300)
    {
    global $lang;
    $rtypes=get_resource_types("",true,false,true);
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>" id="<?php echo $name?>" style="width:<?php echo $width ?>px">
<?php
    foreach($rtypes as $rtype)
        {
        echo '    <option value="'. $rtype['ref'] . '"' . (($current==$rtype['ref'])?' selected':'') . '>' . lang_or_i18n_get_translated($rtype['name'],'resourcetype-') . '</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
<?php
}

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a single RS resource-type select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_single_rtype_select($config_var, $label, $width=300)
    {
    return array('single_rtype_select',$config_var, $label, $width);
    }

/**
 * Generate an html multi-select check boxes block for selecting multiple the RS resource types. The
 * selected field type is posted as an array of the values of the "ref" column of the selected
 * resource types.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer array $current the current value of the config variable being set
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_multi_rtype_select($name, $label, $current, $width=300)
    {
    global $lang;
    $rtypes=get_resource_types();
    ?>
    <div class="Question">
        <label for="<?php echo escape($name) ?>" title="<?php echo escape(str_replace('%cvn', $name, $lang['plugins-configvar'])) ?>"><?php echo htmlspecialchars($label) ?></label>
        <fieldset id="<?php echo escape($name) ?>" class="MultiRTypeSelect">
            <?php foreach($rtypes as $rtype) { ?>
                <input type="checkbox"
                    value="<?php echo escape($rtype['ref']) ?>"
                    name="<?php echo escape($name) ?>[]"
                    id="<?php echo escape($name . $rtype['ref']) ?>"
                    <?php echo in_array($rtype['ref'],$current) ? ' checked="checked"' : '' ?>>
                <label for="<?php echo escape($name . $rtype['ref']) ?>"><?php echo htmlspecialchars(lang_or_i18n_get_translated($rtype['name'],'resourcetype-')) ?></label>
                <br />
            <?php } ?>
        </fieldset>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multiple RS resource-type select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_multi_rtype_select($config_var, $label, $width=300)
    {
    return array('multi_rtype_select', $config_var, $label, $width);
    }


/**
 * Generate an html multi-select check boxes block for selecting multiple the RS archive states. 
 * The selections are posted as an array of the archive states
 * archive state.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer array $current the current value of the config variable being set
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_multi_archive_select($name, $label, $current, $choices, $width=300)
    {
    global $lang;
    ?>
    <div class="Question">
        <label for="<?php echo escape($name)?>" title="<?php echo escape(str_replace('%cvn', $name, $lang['plugins-configvar']))?>"><?php echo htmlspecialchars($label)?></label>
        <fieldset id="<?php echo escape($name)?>" class="MultiRTypeSelect">
            <?php foreach($choices as $statekey => $statename) { ?>
                <span id="archivestate<?php echo escape($statekey) ?>">
                    <input type="checkbox"
                        value="<?php echo escape($statekey) ?>"
                        name="<?php echo escape($name) . '[]' ?>"
                        id="<?php echo escape($name . $statekey) ?>" 
                        <?php echo isset($current) && $current != '' && in_array($statekey,$current) ? ' checked="checked"' : '' ?>>
                    <label for="<?php echo escape($name . $statekey) ?>"><?php echo htmlspecialchars($statename) ?></label>
                    <br />
                </span>
            <?php } ?>
        </fieldset>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multiple RS archive select configuration variable to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_multi_archive_select($config_var, $label, $choices, $width=300)
{
return array('multi_archive_select', $config_var, $label, $choices, $width);
}

/**
 * Generate an html single-select + options block for selecting from among rows returned by a
 * database query in which one of the columns is the unique key (by default, the "ref" column) and
 * one of the others is the text to display (by default the "name" column). The value posted is the
 * value at the intersection of the selected rown with the column given by the $ixcol variable.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param string $current the current value of the config variable being set.
 * @param array $choices the array of db rows that make up the choices.
 * @param string $ixcol the key in $choices (i.e., the db column) for the value of the choice.
 *          Defaulted to 'ref'.
 * @param string $dispcolA the key in $choices (i.e., the db column) for the text to display to the
 *          user. Defaulted to 'name'.
 * @param string $dispcolB the key in $choices (i.e., the db column) for secondary text to display to
 *          the user. Defaulted to '' indicating that only $dispcolA is to be displayed.
 * @param string $fmt the formatting string for combining $dispcolA and B when both are specified.
 *          Defaulted to $lang['plugin_field_fmt']. $fmt is all literal except for %A and %B which
 *          are replaced with values. In English $fmt is '%A(%B)' which results in the i-th choice
 *          displaying as: $choices[i][$dispcolA] . '(' . $choices[i][$dispcolB] . ')'
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_db_single_select($name, $label, $current, $choices, $ixcol='ref', $dispcolA='name', $dispcolB='', $fmt='', $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>" id="<?php echo $name?>" style="width:<?php echo $width ?>px">
<?php
    foreach($choices as $item)
        {
        if ($dispcolB!='')
            {
            $usertext=str_replace(array('%A','%B'), array($item[$dispcolA],$item[$dispcolB]),$fmt==''?$lang['plugin_field_fmt']:$fmt);
            }
        else
            {
            $usertext=$item[$dispcolA];
            }
        echo '    <option value="' . $item[$ixcol] . '"' . (($item[$ixcol]==$current)?' selected':'') . '>' . $usertext . '</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
<?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a single select configuration variable whose value is chosen from among the results of
 * a db query to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param array $choices the array of db rows that make up the choices.
 * @param string $ixcol the key in $choices (i.e., the db column) for the value of the choice.
 *          Defaulted to 'ref'.
 * @param string $dispcolA the key in $choices (i.e., the db column) for the text to display to the
 *          user. Defaulted to 'name'.
 * @param string $dispcolB the key in $choices (i.e., the db column) for secondary text to display to
 *          the user. Defaulted to '' indicating that only $dispcolA is to be displayed.
 * @param string $fmt the formatting string for combining $dispcolA and B when both are specified.
 *          Defaulted to $lang['plugin_field_fmt']. $fmt is all literal except for %A and %B which are
 *          replaced with values. In English $fmt is '%A(%B)' which results in the i-th choice
 *          displaying as: $choices[i][$dispcolA] . '(' . $choices[i][$dispcolB] . ')'
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_db_single_select($config_var, $label, $choices, $ixcol='ref', $dispcolA='name', $dispcolB='', $fmt='', $width=300)
    {
    return array('db_single_select', $config_var, $label, $choices, $ixcol, $dispcolA, $dispcolB, $fmt, $width);
    }

/**
 * Generate an html multi-select + options block for selecting from among rows returned by a
 * database query in which one of the columns is the unique key (by default, the "ref" column) and one
 * of the others is the text to display (by default the "name" column). The value posted is an array
 * of the values of the column given by the $ixcol variable for the rows selected.
 *
 * @param string $name the name of the select block. Usually the name of the config variable being set.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param string array $current the current value of the config variable being set.
 * @param array $choices the array of db rows that make up the choices.
 * @param string $ixcol the key in $choices (i.e., the db column) for the value of the choice
 *          Defaulted to 'ref'.
 * @param string $dispcolA the key in $choices (i.e., the db column) for the text to display to the
 *          user. Defaulted to 'name'.
 * @param string $dispcolB the key in $choices (i.e., the db column) for secondary text to display to
 *          the user.  Defaulted to '' indicating that only $dispcolA is to be displayed.
 * @param string $fmt the formatting string for combining $dispcolA and B when both are specified.
 *          Defaulted to $lang['plugin_field_fmt']. $fmt is all literal except for %A and %B which are
 *          replaced with values. In English $fmt is '%A(%B)' which results in the i-th choice
 *          displaying as: $choices[i][$dispcolA] . '(' . $choices[i][$dispcolB] . ')'
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
 function config_db_multi_select($name, $label, $current, $choices, $ixcol='ref', $dispcolA='name', $dispcolB='', $fmt='', $width=300)
    {
    global $lang;
?>
  <div class="Question">
    <label for="<?php echo $name?>" title="<?php echo str_replace('%cvn', $name, $lang['plugins-configvar'])?>"><?php echo $label?></label>
    <select name="<?php echo $name?>[]" id="<?php echo $name?>" multiple="multiple" size="7" class="MultiSelect" style="width:<?php echo $width ?>px">
<?php
    foreach($choices as $item)
        {
        if ($dispcolB!='')
            {
            $usertext=str_replace(array('%A','%B'), array($item[$dispcolA],$item[$dispcolB]),$fmt==''?$lang['plugin_field_fmt']:$fmt);
            }
            else
            {
            $usertext=$item[$dispcolA];
            }
        echo '    <option value="' . $item[$ixcol] . '"' . (in_array($item[$ixcol],$current)?' selected':'') . '>' . $usertext . '</option>';
        }
?>
    </select>
    <div class="clearerleft"></div>
  </div>
<?php
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a multi select configuration variable whose values are chosen from among the results of
 * a db query to the setup page.
 *
 * @param string $config_var the name of the configuration variable to be added.
 * @param string $label the user text displayed to label the select block. Usually a $lang string.
 * @param array $choices the array of db rows that make up the choices.
 * @param string $ixcol the key in $choices (i.e., the db column) for the value of the choice.
 *          Defaulted to 'ref'.
 * @param string $dispcolA the key in $choices (i.e., the db column) for the text to display to the
 *          user. Defaulted to 'name'.
 * @param string $dispcolB the key in $choices (i.e., the db column) for secondary text to display to
 *          the user. Defaulted to '' indicating that only $dispcolA is to be displayed.
 * @param string $fmt the formatting string for combining $dispcolA and B when both are specified.
 *          Defaulted to $lang['plugin_field_fmt']. $fmt is all literal except for %A and %B which are
 *          replaced with values. In English $fmt is '%A(%B)' which results in the i-th choice
 *          displaying as: $choices[i][$dispcolA] . '(' . $choices[i][$dispcolB] . ')'
 * @param integer $width the width of the input field in pixels. Default: 300.
 */
function config_add_db_multi_select($config_var, $label, $choices, $ixcol='ref', $dispcolA='name', $dispcolB='',  $fmt='', $width=300)
    {
    return array('db_multi_select', $config_var, $label, $choices, $ixcol, $dispcolA, $dispcolB, $fmt, $width);
    }

/**
 * Return a data structure that will instruct the configuration page generator functions to
 * add a hidden configuration variable.
 *
 * @param string $config_var the name of the configuration variable to be added.
 */
function config_add_hidden($config_var)
    {
    return array('hidden_param', $config_var);
    }

/**
 *  Deprecated -- use config_text_input instead
 */
function config_text_field($name, $label, $value, $size='30')
    {
    config_text_input($name, $label, $value, false, $size*10);
    }

/**
 *  Deprecated -- use config_multi_user_select instead
 */
function config_userselect_field($name, $label, $values=array())
    {
    config_multi_user_select($name, $label, $values);
    }

/**
 *  Deprecated -- use config_single_ftype_select instead
 */
function config_field_select($name, $label, $value)
    {
    config_single_ftype_select($name, $label, $value);
    }

/**
 *  Deprecated -- use config_boolean_select instead
 */
function config_boolean_field($name, $label, $value)
    {
    config_boolean_select($name,$label,$value,array('False','True'));
    }

/**
 *  Deprecated -- use config_db_multi_select instead
 */
function config_custom_select_multi($name, $label, $available, $values, $index='ref', $nameindex='name', $additional='')
    {
    config_db_multi_select($name, $label, $values, $available, $index, $nameindex, $additional, '%A(%B)');
    }

/**
 *  Deprecated -- use config_single_select instead
 */
function config_custom_select($name, $label, $available, $value)
    {
    config_single_select($name, $label, $value, $available, false);
    }

function get_plugin_css()
    {
    global $plugins,$baseurl,$language,$css_reload_key;

    $plugincss="";
    for ($n=count($plugins)-1;$n>=0;$n--)
    {
    if (!isset($plugins[$n])) { continue; }
    $csspath=get_plugin_path($plugins[$n]) . "/css/style.css";
    if (file_exists($csspath))
        {
        $plugincss.='<link href="'.get_plugin_path($plugins[$n],true).'/css/style.css?css_reload_key='.$css_reload_key.'" rel="stylesheet" type="text/css" media="screen,projection,print" class="plugincss" />
		';
        }   

        # Allow language specific CSS files
        $csspath=get_plugin_path($plugins[$n]) . "/css/style-" . $language . ".css";
        if (file_exists($csspath))
            {
            $plugincss.='<link href="' . get_plugin_path($plugins[$n],true) . '/css/style-' . $language . '.css?css_reload_key='.$css_reload_key.'" rel="stylesheet" type="text/css" media="screen,projection,print" class="plugincss" />';
            }
        
        # additional plugin css functionality
        $plugincss.=hook('moreplugincss','',array($plugins, $n));
            
        }
    return $plugincss;
    }
/*
Activate language and configuration for plugins for use on setup page if plugin is not enabled for user group

@param string $plugin_name the name of the plugin to activate
*/
function plugin_activate_for_setup($plugin_name)
    {   
    // Add language file
    register_plugin_language($plugin_name);
    
    // Include <plugin>/hooks/all.php case functions are included here
    $pluginpath=get_plugin_path($plugin_name);
    $hookpath=$pluginpath . "/hooks/all.php";
    if (file_exists($hookpath)) {include_once $hookpath;}   

    // Include plugin configuration for displaying on Options page
    $active_plugin = ps_query("SELECT `name`, enabled_groups, config, config_json FROM plugins WHERE `name` = ? AND inst_version >= 0 order by priority", array("s", $plugin_name));
    if (empty($active_plugin))
        {
        include_plugin_config($plugin_name);
        }
        else
        {
        include_plugin_config($plugin_name, $active_plugin[0]['config'], $active_plugin[0]['config_json']);
        }       
    return true;
    }

    

function include_plugin_config($plugin_name,$config="",$config_json="")
    {
    global $mysql_charset;
    
    $pluginpath=get_plugin_path($plugin_name);
    
    $configpath = $pluginpath . "/config/config.default.php";
    if (file_exists($configpath)) {include_once $configpath;}
    $configpath = $pluginpath . "/config/config.php";
    if (file_exists($configpath)) {include_once $configpath;}

    if ($config_json != "" && function_exists('json_decode'))
        {
        if (!isset($mysql_charset))
            {
            $config_json = iconv('ISO-8859-1', 'UTF-8', $config_json);
            }
        $config_json = json_decode($config_json, true);
        if ($config_json)
            {
            foreach($config_json as $key=>$value)
                {
                $$key = $value;
                }
            }
        }
    elseif ($config != "")
        {
        $config=unserialize(base64_decode($config));
        foreach($config as $key=>$value)
            $$key = $value;
        }

    # Copy config variables to global scope.
    unset($plugin_name, $config, $config_json, $configpath);
    $vars = get_defined_vars();
    foreach ($vars as $name=>$value)
        {
        global $$name;
        $$name = $value;
        }
    }

function register_plugin_language($plugin)
    {
    global $plugins,$language,$pagename,$lang,$applicationname,$customsitetext;
    
    # Include language file
    $langpath = get_plugin_path($plugin) . "/languages/";

    if (file_exists($langpath . "en.php"))
        {
        include $langpath . "en.php";
        }

    if ($language != "en")
        {
        if (
            substr($language, 2, 1) == '-' 
            && substr($language, 0, 2) != 'en'
            && file_exists($langpath . safe_file_name(substr($language, 0, 2)) .  ".php")
            ) {
                include $langpath . safe_file_name(substr($language, 0, 2)) . ".php";
            }
        if (file_exists($langpath . safe_file_name($language) . ".php"))
            {
            include $langpath . safe_file_name($language) . ".php";
            }
        }

    // If we have custom text created from Manage Content we need to reset this
    if(isset($customsitetext))
        {
        foreach ($customsitetext as $customsitetextname=>$customsitetextentry)
            {
            $lang[$customsitetextname] = $customsitetextentry;
            }
        }
    }
    
function get_plugin_path($plugin,$url=false)
    {
    # For the given plugin shortname, return the path on disk
    # Supports plugins being in the filestore folder (for user uploaded plugins)
    global $baseurl_short,$storagedir,$storageurl;
    
    # Sanitise $plugin
    $plugin=safe_file_name($plugin);
    
    # Standard location    
    $pluginpath=dirname(__FILE__) . "/../plugins/" . $plugin;
    if (file_exists($pluginpath)) {return $url ? $baseurl_short . "plugins/" . $plugin : $pluginpath;}

    # Filestore location
    $pluginpath=$storagedir . "/plugins/" . $plugin;
    if (file_exists($pluginpath)) {return $url ? $storageurl . "/plugins/" . $plugin : $pluginpath;}
    }
    
function register_plugin($plugin)
    {
    global $plugins,$language,$pagename,$lang,$applicationname;

    # Also include plugin hook file for this page.
    if ($pagename=="collections_frameless_loader"){$pagename="collections";}
    
    $pluginpath=get_plugin_path($plugin);
        
    $hookpath=$pluginpath . "/hooks/" . $pagename . ".php";
    if (file_exists($hookpath)) {include_once $hookpath;}
    
    # Support an 'all' hook
    $hookpath=$pluginpath . "/hooks/all.php";
    if (file_exists($hookpath)) {include_once $hookpath;}

    # Support standard location for API bindings
    $api_bindings_path=$pluginpath . "/api/api_bindings.php";
    if (file_exists($api_bindings_path)) {include_once $api_bindings_path;}

    return true;    
    }

/**
* Encode complex plugin configuration (e.g mappings defined by users on plugins' setup page)
* 
* @param mixed $c Configuration requiring encoding
* 
* @return string
*/
function plugin_encode_complex_configs($c)
    {
    return base64_encode(serialize($c));
    }

/**
* Decode complex plugin configuration (e.g mappings defined by users on plugins' setup page)
* 
* @param string $b64sc Configuration encoded prior with {@see plugin_encode_complex_configs()}
* 
* @return mixed
*/
function plugin_decode_complex_configs(string $b64sc)
    {
    return unserialize(base64_decode($b64sc));
    }
