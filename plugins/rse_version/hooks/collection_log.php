<?php
function HookRse_versionCollection_logLog_extra_columns_header()
    {
    global $lang;
    ?>
    <td width="5%">
        <div class="ListTools"><?php echo $lang["tools"]; ?></div>
    </td>
    <?php
    return;
    }


function HookRse_versionCollection_logLog_extra_columns_row($log, array $collection_info)
    {
    global $lang, $baseurl;

    if(!$log['revert_state_enabled'])
        {
        ?>
        <td></td>
        <?php
        return;
        }

    $url = generateURL(
        "{$baseurl}/plugins/rse_version/pages/revert.php",
        array(
            "collection" => $collection_info["ref"],
            "date"       => $log["date"],
            "resource"   => $log["resource"],
        )
    );
    ?>
    <td>
        <div class="ListTools">
        <a href="<?php echo $url; ?>"
           onclick="CentralSpaceLoad(this, true); return false;"><?php echo LINK_CARET . $lang["rse_version_revert_state"]; ?></a>
        </div>
    </td>
    <?php
    return;
    }


function HookRse_versionCollection_logCollection_log_extra_fields()
    {
    return ",
            IF(
                   (`type` = 'a' AND BINARY `type` <> BINARY UPPER(`type`))
                OR `type` = 'r', true, false
            ) AS revert_state_enabled";
    }