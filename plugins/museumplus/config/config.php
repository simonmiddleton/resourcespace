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
$museumplus_rs_saved_mappings = plugin_encode_complex_configs(array());

// TODO: once structure established, create/compute migration from old configs to new one.
// TODO: build new page for configuring a module mapping
$new_mapping_structure = array(
    array(
        'module_name' => 'Object', # for migration purposes: always Object
        'mplus_id_field' => 'ObjObjectNumberVrt', # for migration purposes: $museumplus_search_mpid_field. this means we search by a virtual ID first and if invalid, try the technical id (ie __id). If empty, only try the technical one
        'rs_uid_field' => 88, # for migration purposes: $museumplus_mpid_field. this can be re-used between modules
        'applicable_resource_types' => array(5), # for migration purposes: $museumplus_resource_types.
        'media_sync' => false, # for migration purposes: N/A.
        'media_sync_df_field' => 89, # for migration purposes: N/A.
        'mplus_rs_mappings' => array(
            'ObjUuidVrt' => 85,
            'ObjObjectNumberVrt' => 86,
        ), # for migration purposes: $museumplus_rs_saved_mappings.
    )
);
$museumplus_rs_saved_mappings = plugin_encode_complex_configs($new_mapping_structure);
