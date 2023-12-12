<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';

function HookEmuAllExtra_checks()
    {
    $GLOBALS['use_error_exception'] = true;
    try
        {
        $emu_api = new EMuAPI($GLOBALS['emu_api_server'], $GLOBALS['emu_api_server_port']);
        }
    catch(Throwable $t)
        {
        $message['emu'] = [
            'status' => 'FAIL',
            'info' => "{$GLOBALS['lang']['emu_configuration']}: {$t->getMessage()}",
            'severity' => SEVERITY_WARNING,
            'severity_text' => $GLOBALS["lang"]["severity-level_" . SEVERITY_WARNING],
            ];
        return $message;
        }
    unset($GLOBALS['use_error_exception']);
    }