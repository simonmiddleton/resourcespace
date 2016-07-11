<?php
// EMu Server connection settings
$emu_api_server               = 'http://[server.address]';
$emu_api_server_port          = 25040;

// EMu script
$emu_enable_script              = true;
$emu_test_mode                  = false;
$emu_email_notify               = '';
$emu_interval_run               = ''; // see http://php.net/manual/en/datetime.formats.relative.php or http://php.net/manual/en/datetime.add.php
$emu_script_failure_notify_days = 3;
$emu_log_directory              = '';

// EMu settings
// metadata field used to store the EMu identifier (IRN)
$emu_irn_field      = null;
$emu_resource_types = array();


/* EMu - ResourceSpace mappings
IMPORTANT: ResourceSpace is expecting an atomic value for the field, anything else will fail

$emu_rs_saved_mappings[module_name][column] = rs_field_id

Example:
$emu_rs_saved_mappings['epublic']['ObjTitle'] = 20;
$emu_rs_saved_mappings['epublic']['ObjName'] = 17;
$emu_rs_saved_mappings['emultimedia']['ChaAspectRatio'] = 32;
*/
$emu_rs_saved_mappings = base64_encode(serialize(array()));