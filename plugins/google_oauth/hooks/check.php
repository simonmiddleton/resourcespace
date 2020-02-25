<?php
function HookGoogle_oauthCheckAddinstallationcheck()
    {
    global $google_oauth_dependencies_ready, $google_oauth_lib_autoload, $lang;

    $status = "{$lang['status-fail']}: {$lang['google_oauth_lib_not_exists_error']}";
    if($google_oauth_dependencies_ready)
        {
        $status = $lang["status-ok"];
        }
    ?>
    <tr>
        <td><?php echo $lang['google_oauth_plugin']; ?></td>
        <td><?php echo $google_oauth_lib_autoload; ?></td>
        <td><b><?php echo $status; ?></b></td>
    </tr>
    <?php
    return;
    }