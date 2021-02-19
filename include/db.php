<?php
/**
 * db.php
 * 
 * Connects to the database, loads all the necessary things all pages need such as configuration, plugins, languages.
 */

# Include the most commonly used functions
include_once dirname(__FILE__) . '/definitions.php';
include_once dirname(__FILE__) . '/version.php';
include_once dirname(__FILE__) . '/config.security.php';
include_once dirname(__FILE__) . '/general_functions.php';
include_once dirname(__FILE__) . '/database_functions.php';
include_once dirname(__FILE__) . '/search_functions.php';
include_once dirname(__FILE__) . '/search_do.php';
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
include_once dirname(__FILE__) . '/migration_functions.php';
include_once dirname(__FILE__) . '/metadata_functions.php';

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

set_error_handler("errorhandler");

// Check the PHP version.
if (PHP_VERSION_ID<PHP_VERSION_SUPPORTED) {exit("PHP version not supported. Your version: " . PHP_VERSION_ID . ", minimum supported: " . PHP_VERSION_SUPPORTED);}

# *** LOAD CONFIG ***
# Load the default config first, if it exists, so any new settings are present even if missing from config.php
if (file_exists(dirname(__FILE__)."/config.default.php")) {include dirname(__FILE__) . "/config.default.php";}
if (file_exists(dirname(__FILE__)."/config.deprecated.php")) {include dirname(__FILE__) . "/config.deprecated.php";}

# Load the real config
if (!file_exists(dirname(__FILE__)."/config.php")) {header ("Location: pages/setup.php" );die(0);}
include (dirname(__FILE__)."/config.php");

error_reporting($config_error_reporting);


# -------------------------------------------------------------------------------------------
# Remote config support - possibility to load the configuration from a remote system.
#
debug('[db.php] Remote config support...');
debug('[db.php] isset($remote_config_url) = ' . json_encode(isset($remote_config_url)));
debug('[db.php] isset($_SERVER["HTTP_HOST"]) = ' . json_encode(isset($_SERVER["HTTP_HOST"])));
debug('[db.php] getenv("RESOURCESPACE_URL") != "") = ' . json_encode(getenv("RESOURCESPACE_URL") != ""));
if (isset($remote_config_url) && (isset($_SERVER["HTTP_HOST"]) || getenv("RESOURCESPACE_URL") != ""))
	{
    debug("[db.php] \$remote_config_url = {$remote_config_url}");
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
    debug("[db.php] \$host = {$host}");
    debug("[db.php] \$hostmd = {$hostmd}");

	# Look for configuration for this host (supports multiple hosts)
	$remote_config_sysvar="remote-config-" . $hostmd; # 46 chars (column is 50)
	$remote_config=get_sysvar($remote_config_sysvar);
    $remote_config_expiry = get_sysvar("remote_config-exp" .  $hostmd,0);
	if ($remote_config!==false && $remote_config_expiry>time() && !isset($_GET["reload_remote_config"]))
		{
		# Local cache exists and has not expired. Use this copy.
        debug("[db.php] Using local cached version of remote config. \$remote_config_expiry = {$remote_config_expiry}");
		}
	elseif(function_exists('curl_init'))
		{
		# Cache not present or has expired.
		# Fetch new config and store. Set a very low timeout of 2 seconds so the config server going down does not take down the site.
		# Attempt to fetch the remote contents but suppress errors.
        $rc_url = $remote_config_url . "?host=" . urlencode($host) . "&sign=" . md5($remote_config_key . $host);
        $ch=curl_init();
        $checktimeout=2;
        curl_setopt($ch, CURLOPT_URL, $rc_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $checktimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $checktimeout);
        $r=curl_exec($ch);

        if (!curl_errno($ch))
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
                debug('[db.php][warn] Validation of returned remote config failed');
                }
			}
		else
			{
			# The attempt to fetch the remote configuration failed.
			# Do nothing; the cached copy will be used and we will try again later.
            $errortext = curl_strerror(curl_errno($ch));
            debug("[db.php][warn] Remote config check failed from '"  . $remote_config_url . "' : " . $errortext . " : " . $r);
            }
        curl_close($ch);

		set_sysvar("remote_config-exp" .  $hostmd,time()+(60*10)); # Load again (or try again if failed) in ten minutes
		}
	# Load and use the config
	eval($remote_config);
	}
#
# End of remote config support
# ---------------------------------------------------------------------------------------------

// Set system to read only mode
if(isset($system_read_only) && $system_read_only)
    {
    $global_permissions_mask="a,t,c,d,e0,e1,e2,e-1,e-2,i,n,h,q,u,dtu,hdta";
    $global_permissions="p";
    $remove_resources_link_on_collection_bar = false;
    $allow_save_search = false;
    $mysql_log_transactions=false;
    $enable_collection_copy = false;
    }
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

sql_connect();

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
                sql_query("REPLACE plugins(inst_version, author, descrip, name, info_url, update_url, config_url, priority, disable_group_select, title, icon) ".
                        "VALUES ('{$p_y['version']}','{$p_y['author']}','{$p_y['desc']}','{$plugin_name}'," .
                        "'{$p_y['info_url']}','{$p_y['update_url']}','{$p_y['config_url']}','{$p_y['default_priority']}','{$p_y['disable_group_select']}','{$p_y['title']}','{$p_y['icon']}')");
                clear_query_cache("plugins");
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
        if (!isset($plugins[$n])) { continue; }
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
    if (!isset($plugins[$n])) { continue; }
	register_plugin($plugins[$n]);
	hook("afterregisterplugin");
	}

# Register their languages in reverse order
for ($n=count($plugins)-1;$n>=0;$n--)
	{
    if (!isset($plugins[$n])) { continue; }
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

# Load the sysvars into an array. Useful so we can check migration status etc.
# Needs to be actioned before the 'initialise' hook or plugins can't use get_sysvar()
$systemvars = sql_query("SELECT name, value FROM sysvars");
$sysvars = array();
foreach($systemvars as $systemvar)
    {
    $sysvars[$systemvar["name"]] = $systemvar["value"];
    }
    
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



// IMPORTANT: make sure the upgrade.php is the last line in this file
include_once __DIR__ . '/../upgrade/upgrade.php';
