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


// ResourceSpace settings
$museumplus_module_name_field = 0;
// Field to hold extra secondary links. RS will just generate appropriate M+ URLs based on each link. Secondary links 
// have a special syntax format: <module name>:<technical ID> e.g. "Object:1234".
$museumplus_secondary_links_field = 0;


// Script settings
$museumplus_enable_script = true;
$museumplus_interval_run = ''; // see http://php.net/manual/en/datetime.formats.relative.php or http://php.net/manual/en/datetime.add.php
$museumplus_log_directory = '';
$museumplus_script_failure_notify_days = 3;
$museumplus_integrity_check_field = 0; # not in use until we can reliably get integrity checks of the data from M+


// MuseumPlus - ResourceSpace mappings
$museumplus_modules_saved_config = plugin_encode_complex_configs(array(
    1 => array(
        'module_name' => 'Object',
        'mplus_id_field' => '',
        'rs_uid_field' => 0,
        'applicable_resource_types' => array(),
        'media_sync' => false,
        'media_sync_df_field' => 0,
        'field_mappings' => array(),
    )
));
