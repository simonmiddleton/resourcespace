<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';

function HookEmuCheckAddinstallationcheck()
    {
    $GLOBALS['use_error_exception'] = true;
    try
        {
        $emu_api = new EMuAPI($GLOBALS['emu_api_server'], $GLOBALS['emu_api_server_port']);
        }
    catch(Throwable $t)
        {
        ?>
        <tr>
            <td colspan="2"><?php echo htmlspecialchars("{$GLOBALS['lang']['emu_configuration']}: {$t->getMessage()}"); ?></td>
            <td><b><?php echo htmlspecialchars($GLOBALS['lang']['status-fail']); ?></b></td>
        </tr>
        <?php
        }
    unset($GLOBALS['use_error_exception']);

    return false;
    }