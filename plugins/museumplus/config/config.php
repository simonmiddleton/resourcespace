<?php
include_once dirname(__DIR__) . '/include/museumplus_functions.php';

// Constants
define('MPLUS_LOCK', 'museumplus_import');
define('MPLUS_LAST_IMPORT', 'last_museumplus_import');
define('MPLUS_MEDIA_MODULE_NAME', 'Multimedia'); # @see http://docs.zetcom.com/ws/


// API
$museumplus_host = '';
$museumplus_application = '';
$museumplus_api_user = '';
$museumplus_api_pass = '';
$museumplus_search_mpid_field = '';


// ResourceSpace settings
$museumplus_mpid_field = null;
$museumplus_module_name_field = null;
// Field to hold extra secondary links. RS will just generate appropriate M+ URLs based on each link. Secondary links 
// have a special syntax format: <module name>:<technical ID> e.g. "Object:1234".
$museumplus_secondary_links_field = null;
$museumplus_resource_types = array();


// Script settings
$museumplus_enable_script = true;
$museumplus_interval_run = ''; // see http://php.net/manual/en/datetime.formats.relative.php or http://php.net/manual/en/datetime.add.php
$museumplus_log_directory = '';
$museumplus_script_failure_notify_days = 3;
$museumplus_integrity_check_field = null; # not in use until we can reliably get integrity checks of the data from M+


// Media syncing
$museumplus_media_sync = false;
$museumplus_media_sync_df_field = null; # must be a checkbox type with only one option as all we'll check is if the resource will have this field set (e.g a field like 'sync with CMS' : yes)


// MuseumPlus - ResourceSpace mappings
$museumplus_rs_saved_mappings = base64_encode(serialize(array()));

