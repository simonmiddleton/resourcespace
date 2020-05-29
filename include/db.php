<?php
/**
 * db.php
 * Connects to the database, loads all the necessary things all pages need such as configuration, plugins, languages.
 * Provides database abstraction functions
 */

# Include the most used functions
include_once dirname(__FILE__) . '/config.security.php';
include_once dirname(__FILE__) . '/general_functions.php';
include_once dirname(__FILE__) . '/definitions.php';
include_once dirname(__FILE__) . '/search_functions.php';
include_once dirname(__FILE__) . '/resource_functions.php';
include_once dirname(__FILE__) . '/collections_functions.php';
include_once dirname(__FILE__) . '/language_functions.php';
include_once dirname(__FILE__) . '/message_functions.php';
include_once dirname(__FILE__) . '/node_functions.php';
include_once dirname(__FILE__) . '/encryption_functions.php';
include_once dirname(__FILE__) . '/render_functions.php';
include_once dirname(__FILE__) . '/user_functions.php';
include_once dirname(__FILE__) . '/debug_functions.php';
include_once dirname(__FILE__) . '/log_functions.php';
include_once dirname(__FILE__) . '/file_functions.php';
include_once dirname(__FILE__) . '/config_functions.php';
include_once dirname(__FILE__) . '/plugin_functions.php';

# Switch on output buffering.
ob_start(null,4096);

$pagetime_start = microtime();
$pagetime_start = explode(' ', $pagetime_start);
$pagetime_start = $pagetime_start[1] + $pagetime_start[0];

if ((!isset($suppress_headers) || !$suppress_headers) && !isset($nocache))
	{
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	}

# Error handling
function errorhandler($errno, $errstr, $errfile, $errline)
    {
    global $baseurl, $pagename, $show_report_bug_link, $email_errors, $show_error_messages,$show_detailed_errors, $use_error_exception;

    if (!error_reporting()) 
        {
        return true;
        }

    $error_note = "Sorry, an error has occurred. ";
    $error_info  = "$errfile line $errline: $errstr";


    if($use_error_exception === true)
        {
        $errline = ($errline == "N/A" || !is_numeric($errline) ? NULL : $errline);
        throw new ErrorException($error_info, 0, E_ALL, $errfile, $errline);
        }
    else if (substr(PHP_SAPI, 0, 3) == 'cli')
        {
        echo $error_note;
        if ($show_error_messages) 
            {
            echo $error_info;
            }
        echo PHP_EOL;
        }
    else
        {
        ?>
        </select></table></table></table>
        <div style="box-shadow: 3px 3px 20px #666;font-family:ubuntu,arial,helvetica,sans-serif;position:absolute;top:150px;left:150px; background-color:white;width:450px;padding:20px;font-size:15px;color:#fff;border-radius:5px;">
            <div style="font-size:30px;background-color:red;border-radius:50%;min-width:35px;float:left;text-align:center;font-weight:bold;">!</div>
            <span style="font-size:30px;color:black;padding:14px;"><?php echo $error_note; ?></span>
            <p style="font-size:14px;color:black;margin-top:20px;">Please <a href="#" onClick="history.go(-1)">go back</a> and try something else.</p>
            <?php 
            if ($show_error_messages) 
                { 
                if (checkperm('a')) //Only show check installtion if you have permissions for that page.
                    {?>
                    <p style="font-size:14px;color:black;">You can <a href="<?php echo $baseurl?>/pages/check.php">check</a> your installation configuration.</p>
                    <?php 
                    } ?>
                <hr style="margin-top:20px;">
                <?php
                if ($show_detailed_errors===true)
                    {?>
                    <p style="font-size:11px;color:black;"><?php echo htmlspecialchars($error_info); ?></p>
                    <?php
                    }
                } ?>
        </div>
        <?php
        }

    if ($email_errors)
        {
        global $email_notify, $email_from, $email_errors_address, $applicationname;
        if ($email_errors_address == "") 
            { 
            $email_errors_address = $email_notify; 
            }
        send_mail($email_errors_address, "$applicationname Error", $error_info, $email_from, $email_from, "", null, "Error Reporting");
        }
    hook('after_error_handler', '', array($errno, $errstr, $errfile, $errline));
    exit();
    }

set_error_handler("errorhandler");

# *** LOAD CONFIG ***
# Load the default config first, if it exists, so any new settings are present even if missing from config.php
if (file_exists(dirname(__FILE__)."/config.default.php")) {include dirname(__FILE__) . "/config.default.php";}
# Load the real config
if (!file_exists(dirname(__FILE__)."/config.php")) {header ("Location: pages/setup.php" );die(0);}
include (dirname(__FILE__)."/config.php");

error_reporting($config_error_reporting);


# -------------------------------------------------------------------------------------------
# Remote config support - possibility to load the configuration from a remote system.
#
if (isset($remote_config_url) && (isset($_SERVER["HTTP_HOST"]) || getenv("RESOURCESPACE_URL") != ""))
	{
	sql_connect(); # Connect a little earlier
	if(isset($_SERVER['HTTP_HOST']))
		{
		$host=$_SERVER['HTTP_HOST'];                   
		}
	else
		{
		// If running scripts from command line the host will not be available and will need to be set as an environment variable
		// e.g. export RESOURCESPACE_URL="www.yourresourcespacedomain.com";cd /var/www/pages/tools; php update_checksums.php
		$host=getenv("RESOURCESPACE_URL");
		}
	$hostmd=md5($host);
	
	# Look for configuration for this host (supports multiple hosts)
	$remote_config_sysvar="remote-config-" . $hostmd; # 46 chars (column is 50)
	$remote_config=get_sysvar($remote_config_sysvar);
	if ($remote_config!==false && get_sysvar("remote_config-exp" .  $hostmd)>time() && !isset($_GET["reload_remote_config"]))
		{
		# Local cache exists and has not expired. Use this copy.
		}
	else
		{ 
		# Cache not present or has expired.
		# Fetch new config and store. Set a very low timeout of 2 seconds so the config server going down does not take down the site.
		$ctx = stream_context_create(array('http' => array('timeout' => 2),'https' => array('timeout' => 2)));
		# Attempt to fetch the remote contents but suppress errors.
		$r=@file_get_contents($remote_config_url . "?host=" . urlencode($host) . "&sign=" . md5($remote_config_key . $host),0,$ctx);
		if ($r!==false)
			{
			# Fetch remote config was a success.
			
			# Validate the return to make sure it's an expected config file
			# The last 33 characters must be a hash and the sign of the previous characters.
			$sign=substr($r,-32); # Last 32 characters is a signature
			$r=substr($r,0,strlen($r)-33);
			if ($sign==md5($remote_config_key . $r))
					{
					$remote_config=$r;
					set_sysvar($remote_config_sysvar,$remote_config);
					}
			else
					{
					# Validation of returned config failed. Possibly the remote config server is misconfigured or having issues.
					# Do nothing; proceed with old config and try again later.
					}
			}
		else
			{
			# The attempt to fetch the remote configuration failed.
			# Do nothing; the cached copy will be used and we will try again later.
			}
		set_sysvar("remote_config-exp" .  $hostmd,time()+(60*10)); # Load again (or try again if failed) in ten minutes
		}
	# Load and use the config
	eval($remote_config);
	}
#
# End of remote config support
# ---------------------------------------------------------------------------------------------

if((!isset($suppress_headers) || !$suppress_headers) && $xframe_options!="")
    {
    // Add X-Frame-Options to HTTP header, so that page cannot be shown in an iframe unless specifically set in config.
    header('X-Frame-Options: ' . $xframe_options);
    }

if($system_down_redirect && getval('show', '') === '') {
	redirect($baseurl . '/pages/system_down.php?show=true');
}

# Set time limit
set_time_limit($php_time_limit);

# Set the storage directory and URL if not already set.
if (!isset($storagedir)) {$storagedir=dirname(__FILE__)."/../filestore";}
if (!isset($storageurl)) {$storageurl=$baseurl."/filestore";}


/**
* Check if ResourceSpace has been configured to run with differnt users (read-write and/or read-only)
* 
* @return boolean
*/
function db_use_multiple_connection_modes()
    {
    if(
        isset($GLOBALS["read_only_db_username"]) && isset($GLOBALS["read_only_db_password"])
        && is_string($GLOBALS["read_only_db_username"]) && is_string($GLOBALS["read_only_db_password"])
        && trim($GLOBALS["read_only_db_username"]) !== ""
    )
        {
        return true;
        }

    return false;
    }


/**
* Used to force the database connection mode before running a particular SQL query
* 
* NOTE: this will generate a global variable that can be used to determine which mode is currently set.
* 
* IMPORTANT: It is the responsibility of each function to clear the current db mode once it finished running the query
* as the variable is not meant to persist between queries.
* 
* @param string $name The name of the connection mode
* 
* @return void
*/
function db_set_connection_mode($name)
    {
    if(
        !(is_string($name) && trim($name) !== "")
        || !db_use_multiple_connection_modes()
        || !array_key_exists($name, $GLOBALS["db"])
    )
        {
        return;
        }

    // IMPORTANT: It is the responsibility of each function to clear the current db mode once it finished running the 
    // query as the variable is not meant to persist between queries.
    $GLOBALS["db_connection_mode"] = $name;

    return;
    }


/**
* Return the current DB connection mode
* 
* @return string
*/
function db_get_connection_mode()
    {
    if(
        !db_use_multiple_connection_modes()
        || !(isset($GLOBALS["db_connection_mode"]) && trim($GLOBALS["db_connection_mode"]) !== "")
    )
        {
        return "";
        }

    return $GLOBALS["db_connection_mode"];
    }


/**
* Clear the current DB connection mode that is in use to override the current SQL queries. @see db_set_connection_mode()
* for more details.
* 
* @return void 
*/
function db_clear_connection_mode()
    {
    if(!db_use_multiple_connection_modes() || !isset($GLOBALS["db_connection_mode"]))
        {
        return;
        }

    unset($GLOBALS["db_connection_mode"]);

    return;
    }


/**
* @var  array  Holds database connections for different users (e.g read-write and/or read-only). NULL if no connection 
*              has been registered.
*/
$db = null;
function sql_connect() 
    {
    global $db,$mysql_server,$mysql_username,$mysql_password,$mysql_db,$mysql_charset,$mysql_force_strict_mode, 
           $mysql_server_port, $use_mysqli_ssl, $mysqli_ssl_server_cert, $mysqli_ssl_ca_cert;

    $init_connection = function(
        $mysql_server, 
        $mysql_server_port, 
        $mysql_username, 
        $mysql_password, 
        $mysql_db) use ($mysql_charset, $use_mysqli_ssl, $mysqli_ssl_server_cert, $mysqli_ssl_ca_cert)
        {
        $db_connection = mysqli_connect($mysql_server, $mysql_username, $mysql_password, $mysql_db, $mysql_server_port);

        if($use_mysqli_ssl)
            {
            mysqli_ssl_set($db_connection, null, $mysqli_ssl_server_cert, $mysqli_ssl_ca_cert, null, null);
            }

        if(isset($mysql_charset) && is_string($mysql_charset) && trim($mysql_charset) !== "")
            {
            mysqli_set_charset($db_connection, $mysql_charset);
            }

        return $db_connection;
        };

    $db["read_write"] = $init_connection($mysql_server, $mysql_server_port, $mysql_username, $mysql_password, $mysql_db);

    if(db_use_multiple_connection_modes())
        {
        $db["read_only"] = $init_connection(
            $mysql_server,
            $mysql_server_port,
            $GLOBALS["read_only_db_username"],
            $GLOBALS["read_only_db_password"],
            $mysql_db);
        }

    foreach($db as $db_connection_mode => $db_connection)
        {
        # Group concat limit increased to support option based metadata with more realistic limit for option entries
        # Chose number of countries (approx 200 * 30 bytes) = 6000 as an example and scaled this up by factor of 5 (arbitrary)
        db_set_connection_mode($db_connection_mode);
        sql_query("SET SESSION group_concat_max_len = 32767", false, -1, false, 0); 

        if ($mysql_force_strict_mode)    
            {
            db_set_connection_mode($db_connection_mode);
            sql_query("SET SESSION sql_mode='STRICT_ALL_TABLES'", false, -1, false, 0);
            continue;
            }

        db_set_connection_mode($db_connection_mode);
        $mysql_version = sql_query('SELECT LEFT(VERSION(), 3) AS ver');
        if(version_compare($mysql_version[0]['ver'], '5.6', '>')) 
            {
            db_set_connection_mode($db_connection_mode);
            $sql_mode_current = sql_query('select @@SESSION.sql_mode');
            $sql_mode_string = implode(" ", $sql_mode_current[0]);
            $sql_mode_array_new = array_diff(explode(",",$sql_mode_string), array("ONLY_FULL_GROUP_BY", "NO_ZERO_IN_DATE", "NO_ZERO_DATE"));
            $sql_mode_string_new = implode (",", $sql_mode_array_new);

            db_set_connection_mode($db_connection_mode);
            sql_query("SET SESSION sql_mode = '$sql_mode_string_new'", false, -1, false, 0);
            }
        }

    db_clear_connection_mode();
    return;
    }
sql_connect();

#if (function_exists("set_magic_quotes_runtime")) {@set_magic_quotes_runtime(0);}

# Automatically set a HTTPS URL if running on the SSL port.
if(isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"]==443)
    {
    $baseurl=str_replace("http://","https://",$baseurl);
    }

# Set a base URL part consisting of the part after the server name, i.e. for absolute URLs and cookie paths.
$baseurl=str_replace(" ","%20",$baseurl);
$bs=explode("/",$baseurl);
$bs=array_slice($bs,3);
$baseurl_short="/" . join("/",$bs) . (count($bs)>0?"/":"");


# statistics
$querycount=0;
$querytime=0;
$querylog=array();

# -----------LANGUAGES AND PLUGINS-------------------------------

if ($use_plugins_manager)
	{
	$legacy_plugins = $plugins; # Make a copy of plugins activated via config.php
	# Check that manually (via config.php) activated plugins are included in the plugins table.
	foreach($plugins as $plugin_name)
		{
		if ($plugin_name!='')
			{
			if (sql_value("SELECT inst_version AS value FROM plugins WHERE name='$plugin_name'",'',"plugins")=='')
				{
				# Installed plugin isn't marked as installed in the DB.  Update it now.
				# Check if there's a plugin.yaml file to get version and author info.
				$plugin_yaml_path = get_plugin_path($plugin_name) . "/{$plugin_name}.yaml";
				$p_y = get_plugin_yaml($plugin_yaml_path, false);
				# Write what information we have to the plugin DB.
				sql_query("REPLACE plugins(inst_version, author, descrip, name, info_url, update_url, config_url, priority, disable_group_select) ".
						  "VALUES ('{$p_y['version']}','{$p_y['author']}','{$p_y['desc']}','{$plugin_name}'," .
						  "'{$p_y['info_url']}','{$p_y['update_url']}','{$p_y['config_url']}','{$p_y['default_priority']}','{$p_y['disable_group_select']}')");
				}
			}
		}
    # Need verbatim queries for this query
    $mysql_vq = $mysql_verbatim_queries;
    $mysql_verbatim_queries = true;
	$active_plugins = sql_query("SELECT name,enabled_groups,config,config_json FROM plugins WHERE inst_version>=0 order by priority","plugins");
    $mysql_verbatim_queries = $mysql_vq;

    $active_yaml = array();
    $plugins = array();
    foreach($active_plugins as $plugin)
	    {
	    # Check group access && YAML, only enable for global access at this point
	    $plugin_yaml_path = get_plugin_path($plugin["name"])."/".$plugin["name"].".yaml";
	    $py = get_plugin_yaml($plugin_yaml_path, false);
	    array_push($active_yaml,$py);
	    if ($py['disable_group_select'] || $plugin['enabled_groups'] == '')
		    {
		    # Add to the plugins array if not already present which is what we are working with
		    $plugins[]=$plugin['name'];
		    }
	    }

	for ($n=count($active_plugins)-1;$n>=0;$n--)
		{
		$plugin=$active_plugins[$n];
        # Check group access && YAML, only enable for global access at this point
	    $plugin_yaml_path = get_plugin_path($plugin["name"])."/".$plugin["name"].".yaml";
	    $py = get_plugin_yaml($plugin_yaml_path, false);
		if ($py['disable_group_select'] || $plugin['enabled_groups'] == '')
			{
			include_plugin_config($plugin['name'], $plugin['config'], $plugin['config_json']);
			}
		}
	}
else
	{
	for ($n=count($plugins)-1;$n>=0;$n--)
		{
		include_plugin_config($plugins[$n]);
		}
	}

// Load system wide config options from database and then store them to distinguish between the system wide and user preference
process_config_options();
$system_wide_config_options = get_defined_vars();

# Include the appropriate language file
$pagename=safe_file_name(str_replace(".php","",pagename()));

// Allow plugins to set $language from config as we cannot run hooks at this point
if(!isset($language))
	{
	$language = setLanguage();
	}

# Fix due to rename of US English language file
if (isset($language) && $language=="us") {$language="en-US";}

# Always include the english pack (in case items have not yet been translated)
include dirname(__FILE__)."/../languages/en.php";
if ($language!="en")
	{
	if (substr($language, 2, 1)=='-' && substr($language, 0, 2)!='en')
	@include dirname(__FILE__)."/../languages/" . safe_file_name(substr($language, 0, 2)) . ".php";
	@include dirname(__FILE__)."/../languages/" . safe_file_name($language) . ".php";
	}

# Register all plugins
for ($n=0;$n<count($plugins);$n++)
	{
	register_plugin($plugins[$n]);
	hook("afterregisterplugin");
	}

# Register their languages in reverse order
for ($n=count($plugins)-1;$n>=0;$n--)
	{
	register_plugin_language($plugins[$n]);
	}

global $suppress_headers;
# Set character set.
if (($pagename!="download") && ($pagename!="graph") && !$suppress_headers) {header("Content-Type: text/html; charset=UTF-8");} // Make sure we're using UTF-8.
#------------------------------------------------------

# ----------------------------------------------------------------------------------------------------------------------
# Basic CORS and CSRF protection
#
if($CSRF_enabled && PHP_SAPI != 'cli' && !$suppress_headers && !in_array($pagename,$CSRF_exempt_pages))
    {
    /*
    Based on OWASP: General Recommendations For Automated CSRF Defense
    (https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet)
    ==================================================================
    # Verifying Same Origin with Standard Headers
    There are two steps to this check:
    1. Determining the origin the request is coming from (source origin)
    2. Determining the origin the request is going to (target origin)

    # What to do when Both Origin and Referer Headers Aren't Present
    If neither of these headers is present, which should be VERY rare, you can either accept or block the request. 
    We recommend blocking, particularly if you aren't using a random CSRF token as your second check. You might want to 
    log when this happens for a while and if you basically never see it, start blocking such requests.

    # Verifying the Two Origins Match
    Once you've identified the source origin (from either the Origin or Referer header), and you've determined the target
    origin, however you choose to do so, then you can simply compare the two values and if they don't match you know you 
    have a cross-origin request.
    */
    $CSRF_source_origin = '';
    $CSRF_target_origin = parse_url($baseurl, PHP_URL_SCHEME) . '://' . parse_url($baseurl, PHP_URL_HOST);
    $CORS_whitelist     = array_merge(array($CSRF_target_origin), $CORS_whitelist);

    // Determining the origin the request is coming from (source origin)
    if(isset($_SERVER['HTTP_ORIGIN']))
        {
        $CSRF_source_origin = $_SERVER['HTTP_ORIGIN'];
        }

    if($CSRF_source_origin === '')
        {
        debug('WARNING: Automated CSRF protection could not detect "Origin" or "Referer" headers in the request!');
        debug("CSRF: Logging attempted request: {$_SERVER['REQUEST_URI']}");

        // If source origin cannot be obtained, set to base URL. The reason we can do this is because we have a second
        // check on the CSRF Token, so if this is a malicious request, the CSRF Token validation will fail.
        // This can also be a genuine request when users go to ResourceSpace straight to login/ home page.
        $CSRF_source_origin = $baseurl;
        }

    $CSRF_source_origin = parse_url($CSRF_source_origin, PHP_URL_SCHEME) . '://' . parse_url($CSRF_source_origin, PHP_URL_HOST);

    debug("CSRF: \$CSRF_source_origin = {$CSRF_source_origin}");
    debug("CSRF: \$CSRF_target_origin = {$CSRF_target_origin}");

    // Verifying the Two Origins Match
    if(
        $CSRF_source_origin !== $CSRF_target_origin && !in_array($CSRF_source_origin, $CORS_whitelist)
        && !hook('modified_cors_process')
    )
        {
        debug("CSRF: Cross-origin request detected and not white listed!");
        debug("CSRF: Logging attempted request: {$_SERVER['REQUEST_URI']}");

        http_response_code(403);
        exit();
        }

    // CORS
    if(in_array($CSRF_source_origin, $CORS_whitelist))
        {
        debug("CORS: Origin: {$CSRF_source_origin}");
        debug("CORS: Access-Control-Allow-Origin: {$CSRF_source_origin}");

        header("Origin: {$CSRF_target_origin}");
        header("Access-Control-Allow-Origin: {$CSRF_source_origin}");
        }
    header('Vary: Origin');
    }
#
# End of basic CORS and automated CSRF protection
# ----------------------------------------------------------------------------------------------------------------------


// Facial recognition setup
if($facial_recognition)
    {
    include __DIR__ . '/facial_recognition_functions.php';
    $facial_recognition = initFacialRecognition();
    }

# Pre-load all text for this page.
$pagefilter="AND (page = '" . $pagename . "' OR page = 'all' OR page = '' " .  (($pagename=="dash_tile")?" OR page = 'home'":"") . ")";
if ($pagename=="admin_content") {$pagefilter="";} # Special case for the team content manager. Pull in all content from all pages so it's all overridden.

$site_text=array();
$results=sql_query("select language,name,text from site_text where (page='$pagename' or page='all' or page='') and (specific_to_group is null or specific_to_group=0)","sitetext");
for ($n=0;$n<count($results);$n++) {$site_text[$results[$n]["language"] . "-" . $results[$n]["name"]]=$results[$n]["text"];}

$query = sprintf('
		SELECT `name`,
		       `text`,
		       `page`,
		       `language`, specific_to_group 
		  FROM site_text
		 WHERE (`language` = "%s" OR `language` = "%s")
		   %s  #pagefilter
		   AND (specific_to_group IS NULL OR specific_to_group = 0);
	',
	escape_check($language),
	escape_check($defaultlanguage),
	$pagefilter
);
$results=sql_query($query,"sitetext");

// Create a new array to hold customised text at any stage, may be overwritten in authenticate.php. Needed so plugin lang file can be overidden if plugin only enabled for specific groups
$customsitetext=array();
// Go through the results twice, setting the default language first, then repeat for the user language so we can override the default with any language specific entries
for ($n=0;$n<count($results);$n++) 
	{
	if($results[$n]["language"]!=$defaultlanguage){continue;}
	if ($results[$n]["page"]=="") 
		{
		$lang[$results[$n]["name"]]=$results[$n]["text"];
		$customsitetext[$results[$n]['name']] = $results[$n]['text'];
		} 
	else 
		{
		$lang[$results[$n]["page"] . "__" . $results[$n]["name"]]=$results[$n]["text"];
		}
	}
for ($n=0;$n<count($results);$n++) 
	{
	if($results[$n]["language"]!=$language){continue;}
	if ($results[$n]["page"]=="") 
		{
		$lang[$results[$n]["name"]]=$results[$n]["text"];
		$customsitetext[$results[$n]['name']] = $results[$n]['text'];
		} 
	else 
		{
		$lang[$results[$n]["page"] . "__" . $results[$n]["name"]]=$results[$n]["text"];
		}
	}
	
# Blank the header insert
$headerinsert="";

# Initialise hook for plugins
hook("initialise");

# Load the language specific stemming algorithm, if one exists
$stemming_file=dirname(__FILE__) . "/../lib/stemming/" . safe_file_name($defaultlanguage) . ".php"; # Important - use the system default language NOT the user selected language, because the stemmer must use the system defaults when indexing for all users.
if(file_exists($stemming_file))
    {
    include_once $stemming_file;
    }

# Global hook cache and related hits counter
$hook_cache = array();
$hook_cache_hits = 0;

# Load the sysvars into an array. Useful so we can check migration status etc.
$systemvars = sql_query("SELECT name, value FROM sysvars");
$sysvars = array();
foreach($systemvars as $systemvar)
    {
    $sysvars[$systemvar["name"]] = $systemvar["value"];
    }


/**
* Indicate that from now on we want to group together DML statements into one transaction.
* 
* @param string $name Savepoint name for the transaction.
* 
* @return boolean Returns TRUE on success or FALSE on failure.
*/
function db_begin_transaction($name)
	{
	global $db;

    if(!is_string($name))
        {
        $name = null;
        }

	if(function_exists('mysqli_begin_transaction'))
		{
		return mysqli_begin_transaction($db["read_write"], 0, $name);
		}

    return false;
	}

# Used to perform the same DML operation over-and-over-again without the hit of preparing the statement every time.
# Useful for re-indexing fields etc.
# Example usage:
#
# sql_query_prepared('INSERT INTO `my_table`(`colint`,`colstring`) VALUES (?,?)',array('is',10,'Ten');
#
# Where first array parameter indicates types of bind data:
# i=integer
# s=string
function sql_query_prepared($sql,$bind_data)
    {
    global $prepared_statement_cache,$db;
    if(!isset($prepared_statement_cache[$sql]))
        {
        if(!isset($prepared_statement_cache))
            {
            $prepared_statement_cache=array();
            }
        $prepared_statement_cache[$sql]=$db["read_write"]->prepare($sql);
        if($prepared_statement_cache[$sql]===false)
            {
            die('Bad prepared SQL statement:' . $sql);
            }
        }
    $bind_data_processed = array();
    foreach($bind_data as $key => $value)
        {
        $bind_data_processed[$key] = &$bind_data[$key];
        }
    call_user_func_array(array($prepared_statement_cache[$sql], 'bind_param'), $bind_data_processed);
    mysqli_stmt_execute($prepared_statement_cache[$sql]);
    }

/**
* Tell the database to commit the current transaction.
* 
* @param string $name Savepoint name for the transaction.
* 
* @return boolean Returns TRUE on success or FALSE on failure.
*/
function db_end_transaction($name)
	{
	global $db;

    if(!is_string($name))
        {
        $name = null;
        }

	if(function_exists('mysqli_commit'))
		{
		return mysqli_commit($db["read_write"], 0, $name);
		}

    return false;
	}

/**
* Tell the database to rollback the current transaction.
* 
* @param string $name Savepoint name for the transaction.
* 
* @return boolean Returns TRUE on success or FALSE on failure.
*/
function db_rollback_transaction($name)
	{
	global $db;

    if(!is_string($name))
        {
        $name = null;
        }

	if(function_exists('mysqli_rollback'))
		{
		return mysqli_rollback($db["read_write"], 0, $name);
		}

    return false;
	}        

function sql_query($sql,$cache="",$fetchrows=-1,$dbstruct=true, $logthis=2, $reconnect=true, $fetch_specific_columns=false)
    {
    # sql_query(sql) - execute a query and return the results as an array.
	# Database functions are wrapped in this way so supporting a database server other than MySQL is 
	# easier.
	
	# $cache - disk based caching - cache the results on disk, if a cache group is specified. The group allows selected parts of the cache to be cleared by certain operations, for example clearing all cached site content whenever site text is edited.
	# At the moment this is basic and ignores $fetchrows and $fetch_specific_columns so isn't useful for queries employing those parameters

    # If $fetchrows is set we don't have to loop through all the returned rows. We
    # just fetch $fetchrows row but pad the array to the full result set size with empty values.
    # This has been added retroactively to support large result sets, yet a pager can work as if a full
    # result set has been returned as an array (as it was working previously).
	# $logthis parameter is only relevant if $mysql_log_transactions is set.  0=don't log, 1=always log, 2=detect logging - i.e. SELECT statements will not be logged
    global $db, $config_show_performance_footer, $debug_log, $debug_log_override, $suppress_sql_log,
    $mysql_verbatim_queries, $mysql_log_transactions, $storagedir, $scramble_key, $query_cache_expires_minutes, $query_cache_already_completed_this_time;
	
	// Check cache for this query
	$cache_write=false;
	if ($cache!="" && (!isset($query_cache_already_completed_this_time) || !in_array($cache,$query_cache_already_completed_this_time))) // Caching active and this cache group has not been cleared by a previous operation this run
		{
		$cache_write=true;
		$cache_location=get_query_cache_location();
		$cache_file=$cache_location . "/" . $cache . "_" . md5($sql) . "_" . md5($scramble_key . $sql) . ".json"; // Scrambled path to cache
		if (file_exists($cache_file))
			{
			$cachedata=json_decode(file_get_contents($cache_file),true);
			if (!is_null($cachedata)) // JSON decode success
				{
				if ($sql==$cachedata["query"]) // Query matches so not a (highly unlikely) hash collision
					{
					if (time()-$cachedata["time"]<(60*$query_cache_expires_minutes)) // Less than 30 mins old?
						{
						return $cachedata["results"];
						}
					}
				}
			}
		}

	if (!isset($debug_log_override))
		{
		check_debug_log_override();
		}
	
    if ($config_show_performance_footer)
    	{
    	# Stats
    	# Start measuring query time
    	$time_start = microtime(true);
   	    global $querycount;
		$querycount++;
    	}
    	
    if (($debug_log || $debug_log_override) && !$suppress_sql_log)
		{
		debug("SQL: " . $sql);
		}
	
    if($mysql_log_transactions && !($logthis==0))
    	{	
		global $mysql_log_location, $lang;

		$requirelog = true;

		if($logthis==2)
			{
			// Ignore any SELECTs if the decision to log has not been indicated by function call, 	
			if(strtoupper(substr(trim($sql), 0, 6)) == "SELECT")
				{
                $requirelog = false;
                }
			}
			
		if($logthis==1 || $requirelog)
			{
			# Log this to a transaction log file so it can be replayed after restoring database backup
			$mysql_log_dir = dirname($mysql_log_location);
			if (!is_dir($mysql_log_dir))
				{
				@mkdir($mysql_log_dir, 0333, true);
				if (!is_dir($mysql_log_dir))
					{exit("ERROR: Unable to create  folder for \$mysql_log_location specified in config file: " . $mysql_log_location);}
				}	
			
			if(!file_exists($mysql_log_location))
				{
				global $mysql_db;
				$mlf=@fopen($mysql_log_location,"wb");
				@fwrite($mlf,"USE " . $mysql_db . ";\r\n");
				if(!file_exists($mysql_log_location))
					{exit("ERROR: Invalid \$mysql_log_location specified in config file: " . $mysql_log_location);}
				// Set the permissions if we can to prevent browser access (will not work on Windows)
				chmod($mysql_log_location,0333);
				}
			
			$mlf=@fopen($mysql_log_location,"ab");
			fwrite($mlf,"/* " . date("Y-m-d H:i:s") . " */ " .  $sql . ";\n"); // Append the ';' so the file can be used to replay the changes
			fclose ($mlf);
			}
		}

    // Establish DB connection required for this query. Note that developers can force the use of read-only mode if
    // available using db_set_connection_mode(). An example use case for this can be reports.
    $db_connection_mode = "read_write";
    $db_connection = $db["read_write"];
    if(
        db_use_multiple_connection_modes()
        && (
            db_get_connection_mode() == "read_only"
            || ($logthis == 2 && strtoupper(substr(trim($sql), 0, 6)) == "SELECT")
        )
    )
        {
        $db_connection_mode = "read_only";
        $db_connection = $db["read_only"];

        // In case it needs to retry and developer has forced a read-only
        $logthis = 2;

        db_clear_connection_mode();
        }

	$result = mysqli_query($db_connection, $sql);
	
    if ($config_show_performance_footer){
    	# Stats
   		# Log performance data		
		global $querytime,$querylog;
		
		$time_total=(microtime(true) - $time_start);
		if (isset($querylog[$sql]))
			{
			$querylog[$sql]['dupe']=$querylog[$sql]['dupe']+1;
			$querylog[$sql]['time']=$querylog[$sql]['time']+$time_total;
			}
		else
			{
			$querylog[$sql]['dupe']=1;
			$querylog[$sql]['time']=$time_total;
			}	
		$querytime += $time_total;
	}
	
	$error = mysqli_error($db_connection);
	
	$return_rows=array();
    if ($error!="")
        {
        if ($error=="Server shutdown in progress")
        	{
			echo "<span class=error>Sorry, but this query would return too many results. Please try refining your query by adding addition keywords or search parameters.<!--$sql--></span>";        	
        	}
        elseif (substr($error,0,15)=="Too many tables")
        	{
			echo "<span class=error>Sorry, but this query contained too many keywords. Please try refining your query by removing any surplus keywords or search parameters.<!--$sql--></span>";        	
        	}
        elseif (strpos($error,"has gone away")!==false && $reconnect)
			{
			# SQL server connection has timed out or been killed. Try to reconnect and run query again.
			sql_connect();
            db_set_connection_mode($db_connection_mode);
			return sql_query($sql,$cache,$fetchrows,$dbstruct,$logthis,false);
			}
        else
        	{
        	# Check that all database tables and columns exist using the files in the 'dbstruct' folder.
        	if ($dbstruct) # should we do this?
        		{
                db_clear_connection_mode();
				check_db_structs();
                db_set_connection_mode($db_connection_mode);

        		# Try again (no dbstruct this time to prevent an endless loop)
        		return sql_query($sql,$cache,$fetchrows,false,$reconnect);
        		}

	        errorhandler("N/A", $error . "<br/><br/>" . $sql, "(database)", "N/A");
	        }

        exit();
        }
    elseif ($result===true)
        {
		return $return_rows;		// no result set, (query was insert, update etc.) - simply return empty array.
        }
	
	$return_row_count=0;	
	while(($fetchrows == -1 || $return_row_count < $fetchrows) && $result_row = mysqli_fetch_assoc($result))
		{
		if ($mysql_verbatim_queries)		// no need to do clean up on every cell
			{
			if($fetch_specific_columns===false)
                {
                $return_rows[$return_row_count]=$result_row;		// simply dump the entire row into the return results set
                }
            else
                {
                foreach($fetch_specific_columns as $fetch_specific_column)
                    {
                    $return_rows[$return_row_count][$fetch_specific_column]=$result_row[$fetch_specific_column];        // dump the specific column into the results set
                    }
                }
			}
		else
			{
            if($fetch_specific_columns===false)     // for all columns
                {
                foreach ($result_row as $name => $value)
                    {
                    $return_rows[$return_row_count][$name] = str_replace("\\", "", stripslashes($value));        // iterate through each cell cleaning up
                    }
                }
            else
                {
                foreach($fetch_specific_columns as $fetch_specific_column)      // for specific columns
                    {
                    $return_rows[$return_row_count][$fetch_specific_column]=str_replace("\\", "", stripslashes($result_row[$fetch_specific_column]));       // iterate through each cell cleaning up
                    }
                }
            }
		$return_row_count++;
		}

	// Write to the cache
	if ($cache_write)
		{
		if (!file_exists($storagedir . "/tmp")) {mkdir($storagedir . "/tmp",0777);}
		if (!file_exists($cache_location)) {mkdir($cache_location,0777);}
		$cachedata=array();
		$cachedata["query"]=$sql;
		$cachedata["time"]=time();
		$cachedata["results"]=$return_rows;
		file_put_contents($cache_file,json_encode($cachedata));
		}

    if($fetchrows == -1)
        {
        mysqli_free_result($result);
        return $return_rows;
        }
	
	# If we haven't returned all the rows ($fetchrows isn't -1) then we need to fill the array so the count
	# is still correct (even though these rows won't be shown).
	
	$query_returned_row_count = mysqli_num_rows($result);

    mysqli_free_result($result);
	
    if($return_row_count < $query_returned_row_count)
        {
        // array_pad has a hardcoded limit of 1,692,439 elements. If we need to pad the results more than that, we do it in
        // 1,000,000 elements batches.
        while(count($return_rows) < $query_returned_row_count)
            {
            $padding_required = $query_returned_row_count - count($return_rows);
            $pad_by = ($padding_required > 1000000 ? 1000000 : $query_returned_row_count);
            $return_rows = array_pad($return_rows, $pad_by, 0);
            }
        }

    return $return_rows;        
    }
	

/**
* Return a single value from a database query, or the default if no rows
* 
* NOTE: The value returned must have the column name aliased to 'value'
* 
* @uses sql_query()
* 
* @param string $query    SQL query
* @param mixed  $default  Default value
* 
* @return string
*/
function sql_value($query, $default, $cache="")
    {
    db_set_connection_mode("read_only");
    $result = sql_query($query, $cache, -1, true, 0, true, false);

    if(count($result) == 0)
        {
        return $default;
        }

        return $result[0]["value"];
    }


/**
* Like sql_value() but returns an array of all values found
* 
* NOTE: The value returned must have the column name aliased to 'value'
* 
* @uses sql_query()
* 
* @param string $query SQL query
* 
* @return array
*/
function sql_array($query,$cache="")
	{
	$return = array();

    db_set_connection_mode("read_only");
    $result = sql_query($query, $cache, -1, true, 0, true, false);

    for($n = 0; $n < count($result); $n++)
    	{
    	$return[] = $result[$n]["value"];
    	}

    return $return;
	}

function sql_insert_id()
	{
    global $db;

    return mysqli_insert_id($db["read_write"]);
	}

function get_query_cache_location()
	{
	global $storagedir;
	return $storagedir . "/tmp/querycache";
	}

function clear_query_cache($cache)
	{
	// Clear all cached queries for cache group $cache

	// If we've already done this on this page load, don't do it again as it will only add to the load in the case of batch operations.
	global $query_cache_already_completed_this_time;
	if (!isset($query_cache_already_completed_this_time)) {$query_cache_already_completed_this_time=array();}
	if (in_array($cache,$query_cache_already_completed_this_time)) {return false;}

	$cache_location=get_query_cache_location();
	if (!file_exists($cache_location)) {return false;} // Cache has not been used yet.
	$cache_files=scandir($cache_location);
	foreach ($cache_files as $file)
		{
		if (substr($file,0,strlen($cache)+1)==$cache . "_")
			{
			unlink($cache_location . "/" . $file);
			}
		}
	
	$query_cache_already_completed_this_time[]=$cache;
	return true;
	}

function check_db_structs($verbose=false)
	{
	CheckDBStruct("dbstruct",$verbose);
	global $plugins;
	for ($n=0;$n<count($plugins);$n++)
		{
		CheckDBStruct("plugins/" . $plugins[$n] . "/dbstruct");
		}
	hook("checkdbstruct");
	}

function CheckDBStruct($path,$verbose=false)
	{
	# Check the database structure against the text files stored in $path.
	# Add tables / columns / data / indices as necessary.
	global $mysql_db, $resource_field_column_limit;
	
	if (!file_exists($path)){
		# Check for path
		$path=dirname(__FILE__) . "/../" . $path; # Make sure this works when called from non-root files..
		if (!file_exists($path)) {return false;}
	}
	
	# Tables first.
	# Load existing tables list
	$ts=sql_query("show tables",false,-1,false);
	$tables=array();
	for ($n=0;$n<count($ts);$n++)
		{
		$tables[]=$ts[$n]["Tables_in_" . $mysql_db];
		}
	$dh=opendir($path);
	while (($file = readdir($dh)) !== false)
		{
		if (substr($file,0,6)=="table_")
			{
			$table=str_replace(".txt","",substr($file,6));
			
			# Check table exists
			if (!in_array($table,$tables))
				{
				# Create Table
				$sql="";
				$f=fopen($path . "/" . $file,"r");
				$hasPrimaryKey = false;
				$pk_sql = "PRIMARY KEY (";
				while (($col = fgetcsv($f,5000)) !== false)
				{
					if ($sql.="") {$sql.=", ";}
					$sql.=$col[0] . " " . str_replace("§",",",$col[1]);
					if ($col[4]!="") {$sql.=" default " . $col[4];}
                    if ($col[3]=="PRI")
					{
						if($hasPrimaryKey)
						{
							$pk_sql .= ",";
						}
						$pk_sql.=$col[0];
						$hasPrimaryKey = true;
					}
					if ($col[5]=="auto_increment") {$sql.=" auto_increment ";}
				}
				$pk_sql .= ")";
				if($hasPrimaryKey)
				{
					$sql.="," . $pk_sql;
				}
				debug($sql);

				# Verbose mode, used for better output from the test script.
				if ($verbose) {echo "$table ";ob_flush();}
				
				sql_query("create table $table ($sql)",false,-1,false);
				
				# Add initial data
				$data=str_replace("table_","data_",$file);
				if (file_exists($path . "/" . $data))
					{
					$f=fopen($path . "/" . $data,"r");
					while (($row = fgetcsv($f,5000)) !== false)
						{
						# Escape values
						for ($n=0;$n<count($row);$n++)
							{
							$row[$n]=escape_check($row[$n]);
							$row[$n]="'" . $row[$n] . "'";
							if ($row[$n]=="''") {$row[$n]="null";}
							}
						sql_query("insert into $table values (" . join (",",$row) . ")",false,-1,false);
						}
					}
				}
			else
				{
				# Table already exists, so check all columns exist
				
				# Load existing table definition
				$existing=sql_query("describe $table",false,-1,false);

				##########
				# Copy needed resource_data into resource for search displays
				if ($table=="resource")
                    {
					$joins=get_resource_table_joins();
					for ($m=0;$m<count($joins);$m++)
                        {
						# Look for this column in the existing columns.	
						$found=false;

						for ($n=0;$n<count($existing);$n++)
							{
							if ("field".$joins[$m]==$existing[$n]["Field"]) {$found=true;}
							}

						if (!$found)
							{
							# Add this column.
							$sql="alter table $table add column ";
							$sql.="field".$joins[$m] . " VARCHAR(" . $resource_field_column_limit . ")";
							sql_query($sql,false,-1,false);

                            $resources = sql_array("SELECT ref AS `value` FROM resource WHERE ref > 0");
                            foreach($resources as $resource)
                                {
                                // Do not use permissions here as we want to sync all fields
                                $resource_field_data       = get_resource_field_data($resource, false, false);
                                $resource_field_data_index = array_search($joins[$m], array_column($resource_field_data, 'ref'));

                                if(
                                    $resource_field_data_index !== false
                                    && trim($resource_field_data[$resource_field_data_index]["value"]) != ""
                                )
                                    {
                                    $new_joins_field_value = $resource_field_data[$resource_field_data_index]["value"];
                                    $truncated_value = truncate_join_field_value($new_joins_field_value);
                                    $truncated_value_escaped = escape_check($truncated_value);

                                    sql_query("
                                        UPDATE resource
                                           SET field{$joins[$m]} = '{$truncated_value_escaped}'
                                         WHERE ref = '{$resource}'");
                                    }
                                }
                            }
                        }
                    }
				##########
				
				##########
				## RS-specific mod:
				# add theme columns to collection table as needed.
				global $theme_category_levels;
				if ($table=="collection"){
					for ($m=1;$m<=$theme_category_levels;$m++){
						if ($m==1){$themeindex="";}else{$themeindex=$m;}
						# Look for this column in the existing columns.	
						$found=false;

						for ($n=0;$n<count($existing);$n++)
							{
							if ("theme".$themeindex==$existing[$n]["Field"]) {$found=true;}
							}
						if (!$found)
							{
							# Add this column.
							$sql="alter table $table add column ";
							$sql.="theme".$themeindex . " VARCHAR(100)";
							sql_query($sql,false,-1,false);

						}
					}	
				}		
				
				##########				
								
				if (file_exists($path . "/" . $file))
					{
					$f=fopen($path . "/" . $file,"r");
					while (($col = fgetcsv($f,5000)) !== false)
						{
						if (count($col)> 1)
							{   
							# Look for this column in the existing columns.
							$found=false;
							for ($n=0;$n<count($existing);$n++)
								{
								if ($existing[$n]["Field"]==$col[0])
									{ 
									$found=true;
									$existingcoltype=strtoupper($existing[$n]["Type"]);
									$basecoltype=strtoupper(str_replace("§",",",$col[1]));									
									# Check the column is of the correct type
									preg_match('/\s*(\w+)\s*\((\d+)\)/i',$basecoltype,$matchbase);
									preg_match('/\s*(\w+)\s*\((\d+)\)/i',$existingcoltype,$matchexisting);
									// Checks added so that we don't trim off data if a varchar size has been increased manually or by a plugin. 
									// - If column is of same type but smaller number, update
									// - If target column is of type text, update
									// - If target column is of type varchar and currently int, update (e.g. the 'archive' column in collection_savedsearch moved from a single state to a multiple)
									// - If target column is of type longtext and currently is text
                                    if(
                                        (count($matchbase) == 3 && count($matchexisting) == 3 && $matchbase[1] == $matchexisting[1] && $matchbase[2] > $matchexisting[2])
                                        || (stripos($basecoltype, "text") !== false && stripos($existingcoltype, "text") === false)
                                        || (strtoupper(substr($basecoltype, 0, 6)) == "BIGINT" && strtoupper(substr($existingcoltype, 0, 3) == "INT"))
                                        || (
                                                strtoupper(substr($basecoltype, 0, 3)) == "INT"
                                                && (strtoupper(substr($existingcoltype,0,7))=="TINYINT" || strtoupper(substr($existingcoltype,0,8))=="SMALLINT")
                                           )
                                        || (strtoupper(substr($basecoltype, 0, 7)) == "VARCHAR" && strtoupper(substr($existingcoltype, 0, 3) == "INT"))
                                        || (strtoupper(substr($basecoltype, 0, 8)) == "LONGTEXT" && strtoupper(substr($existingcoltype, 0, 4) == "TEXT"))
                                    )
                                        {
                                        debug("DBSTRUCT - updating column " . $col[0] . " in table " . $table . " from " . $existing[$n]["Type"] . " to " . str_replace("§",",",$col[1]) );
                                        // Update the column type
                                        sql_query("alter table $table modify `" .$col[0] . "` " .  $col[1]);
                                        }
									}
								}
							if (!$found)
									{
									# Add this column.										
									$sql="alter table $table add column ";
									$sql.=$col[0] . " " . str_replace("§",",",$col[1]); # Allow commas to be entered using '§', necessary for a type such as decimal(2,10)
									if ($col[4]!="") {$sql.=" default " . $col[4];}
									if ($col[3]=="PRI") {$sql.=" primary key";}
									if ($col[5]=="auto_increment") {$sql.=" auto_increment ";}
									sql_query($sql,false,-1,false);
									}	
							}
						}
					}
				}
				
			# Check all indices exist
			# Load existing indexes
			$existing=sql_query("show index from $table",false,-1,false);
					
			$file=str_replace("table_","index_",$file);
			if (file_exists($path . "/" . $file))
				{
				$done=array(); # List of indices already processed.
				$f=fopen($path . "/" . $file,"r");
				while (($col = fgetcsv($f,5000)) !== false)
					{
					# Look for this index in the existing indices.
					$found=false;
					for ($n=0;$n<count($existing);$n++)
						{
						if ($existing[$n]["Key_name"]==$col[2]) {$found=true;}
						}
					if (!$found && !in_array($col[2],$done))
						{
						# Add this index.
						
						# Fetch list of columns for this index
						$cols=array();
						$f2=fopen($path . "/" . $file,"r");
						while (($col2 = fgetcsv($f2,5000)) !== false)
							{
							if ($col2[2]==$col[2]) # Matching column
								{
								# Add an index size if present, for indexing text fields
								$indexsize="";
                                                            	if (trim($col2[7])!="") {$indexsize="(" . $col2[7] . ")";}

								$cols[]=$col2[4] . $indexsize;
								}
							}
						
						$sql="create index " . $col[2] . " on $table (" . join(",",$cols) . ")";
						sql_query($sql,false,-1,false);
						$done[]=$col[2];
						}
					}
				}
			}
		}
	}
	











/**
* Generate the LIMIT statement for a SQL query
* 
* @param  integer  $offset  Specifies the offset of the first row to return
* @param  integer  $rows    Specifies the maximum number of rows to return
* 
* @return string
*/
function sql_limit($offset, $rows)
    {
    $offset_true = !is_null($offset) && is_int($offset) && $offset > 0;
    $rows_true   = !is_null($rows) && is_int($rows) && $rows > 0;

    $limit = ($offset_true || $rows_true ? 'LIMIT ' : '');

    if($offset_true && !$rows_true)
        {
        return '';
        }

    if($offset_true)
        {
        $limit .= abs($offset);
        }

    if($rows_true)
        {
        $rows = abs($rows);
        $limit .= ($offset_true ? ",{$rows}" : $rows);
        }

    return $limit;
    }

// IMPORTANT: make sure the upgrade.php is the last line in this file
include_once __DIR__ . '/../upgrade/upgrade.php';

