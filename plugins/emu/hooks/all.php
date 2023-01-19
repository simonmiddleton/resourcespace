<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';

function HookEmuAllExtra_warn_checks()
    {
    $GLOBALS['use_error_exception'] = true;
    try
        {
        $emu_api = new EMuAPI($GLOBALS['emu_api_server'], $GLOBALS['emu_api_server_port']);
        }
    catch(Throwable $t)
        {
        return [[
            'name' => 'emu',
            'info' => "{$GLOBALS['lang']['emu_configuration']}: {$t->getMessage()}",
        ]];
        }
    unset($GLOBALS['use_error_exception']);

    return false;
    }