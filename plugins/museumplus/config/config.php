<?php
include_once dirname(__DIR__) . '/include/museumplus_functions.php';

// Constants
define('MPLUS_LOCK', 'museumplus_import');
define('MPLUS_LAST_IMPORT', 'last_museumplus_import');


// API
$museumplus_host = '';
$museumplus_application = '';
$museumplus_api_user = '';
$museumplus_api_pass = '';
$museumplus_search_mpid_field = '';


// ResourceSpace settings
$museumplus_mpid_field = null;
$museumplus_resource_types = array();

// Script settings
$museumplus_enable_script = true;
$museumplus_interval_run = ''; // see http://php.net/manual/en/datetime.formats.relative.php or http://php.net/manual/en/datetime.add.php
$museumplus_log_directory = '';
$museumplus_script_failure_notify_days = 3;
$museumplus_integrity_check_field = null; # not in use until we can reliably get integrity checks of the data from M+

// MuseumPlus - ResourceSpace mappings
$museumplus_rs_saved_mappings = base64_encode(serialize(array()));

