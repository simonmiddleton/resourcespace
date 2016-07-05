<?php
// EMu Server connection settings
$emu_api_server               = 'http://[server.address]';
$emu_api_server_port          = '25040';
$emu_api_authentication_token = '';

// EMu script
$emu_enable_script = true;
$emu_test_mode     = false;
$emu_email_notify  = '';
$emu_interval_run  = ''; // see http://php.net/manual/en/datetime.formats.relative.php or http://php.net/manual/en/datetime.add.php

// EMu settings
// metadata field used to store the EMu identifier (IRN)
$emu_irn_field      = null;
$emu_resource_types = array();