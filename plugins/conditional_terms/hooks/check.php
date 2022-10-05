<?php 
function HookConditional_termsCheckAddinstallationcheck()
    {
    global $lang;

    if(!conditional_terms_config_check())
        {
        ?>
        <tr>
            <td colspan="2"><?php echo htmlspecialchars("{$lang['pluginssetup']}: {$lang['conditional_terms_title']}"); ?></td>
            <td><b><?php echo htmlspecialchars($lang['status-fail']); ?></b></td>
        </tr>
        <?php
        }

    return false;
    }