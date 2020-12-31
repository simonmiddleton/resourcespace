<?php
include_once dirname(__DIR__) . '/include/museumplus_functions.php';
include_once dirname(__DIR__) . '/include/museumplus_resource_functions.php';
include_once dirname(__DIR__) . '/include/museumplus_search_functions.php';

############################################################
### Constants ##############################################
############################################################
define('MPLUS_LOCK', 'museumplus_import');
define('MPLUS_LAST_IMPORT', 'last_museumplus_import');
define('MPLUS_MEDIA_MODULE_NAME', 'Multimedia'); # @see http://docs.zetcom.com/ws/
define('MPLUS_FIELD_ID', '__id'); # @see http://docs.zetcom.com/ws/


############################################################
### MuseumPlus API (MpRIA) #################################
############################################################
$museumplus_host = '';
$museumplus_application = '';
$museumplus_api_user = '';
$museumplus_api_pass = '';
// Batch chunk size for Mplus API requests (e.g for expert searches).
// IMPORTANT: Do not expose this to the end user. If you need to adjust it, add it to config.php. @type is integer
$museumplus_api_batch_chunk_size = (isset($GLOBALS['museumplus_api_batch_chunk_size']) && is_int($GLOBALS['museumplus_api_batch_chunk_size']) && $GLOBALS['museumplus_api_batch_chunk_size'] > 0 ? $GLOBALS['museumplus_api_batch_chunk_size'] : 50);


############################################################
### ResourceSpace settings #################################
############################################################
$museumplus_module_name_field = 0;
// Field to hold extra secondary links. RS will just generate appropriate M+ URLs based on each link. Secondary links 
// have a special syntax format: <module name>:<technical ID> e.g. "Object:1234".
$museumplus_secondary_links_field = 0;
// Should "unlinking" a resource from a module record clear the data from the mapped fields in RS? If you need to change it, add it to config.php.
$museumplus_clear_field_mappings_on_change = (isset($GLOBALS['museumplus_clear_field_mappings_on_change']) && is_bool($GLOBALS['museumplus_clear_field_mappings_on_change']) ? $GLOBALS['museumplus_clear_field_mappings_on_change'] : false);


############################################################
### Script settings ########################################
############################################################
$museumplus_enable_script = true;
$museumplus_interval_run = ''; // see http://php.net/manual/en/datetime.formats.relative.php or http://php.net/manual/en/datetime.add.php
$museumplus_log_directory = '';
$museumplus_script_failure_notify_days = 3;
$museumplus_integrity_check_field = 0; # not in use until we can reliably get integrity checks of the data from M+


############################################################
### MuseumPlus - ResourceSpace mappings ####################
############################################################
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
/*
TODO: delete once finished. Useful when working out what code to change/remove:
Configs now under modules:
- museumplus_search_mpid_field  => mplus_id_field
- museumplus_mpid_field         => rs_uid_field
- museumplus_resource_types     => applicable_resource_types
- museumplus_rs_saved_mappings  => field_mappings (modified where each mapping is an array now as opposed to "module_name=>rs_field_ref" mappings)
*/