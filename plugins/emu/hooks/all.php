<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';

function HookEmuAllInitialise()
{
    $emu_config = get_plugin_config('emu');
    if (isset($emu_config["emu_log_directory"])) {
        // Legacy config  - remove from plugin settings
        save_removed_ui_config('emu_log_directory');
        unset($emu_config["emu_log_directory"]);
        set_plugin_config('emu', $emu_config);
    }
    check_removed_ui_config("emu_log_directory");
}

function HookEmuAllExtra_checks()
    {
    $GLOBALS['use_error_exception'] = true;
    try
        {
        new EMuAPI($GLOBALS['emu_api_server'], $GLOBALS['emu_api_server_port']);
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