<?php
function HookResource_usageViewCustompanels()
    {
    global $lang,$baseurl_short,$ref,$edit_access,$k;
    
    if($k != '')
        {
        return false;
        }
    
    $usages = sql_query("SELECT * FROM resource_usage WHERE resource = '$ref' ORDER BY ref");
    ?>

    <div class="RecordBox">
    <div class="RecordPanel">
    <div class="Title"><?php echo $lang['resource_usage']; ?></div>

    <?php
    if($edit_access)
        {
        ?>    
        <p><?php echo LINK_CARET_PLUS ?><a href="<?php echo $baseurl_short; ?>plugins/resource_usage/pages/edit.php?resource=<?php echo $ref; ?>" onClick="return CentralSpaceLoad(this, true);"><?php echo $lang['new_usage']; ?></a></p>
        <?php
        }

    if(count($usages) > 0)
        {
        ?>
        <div class="Listview">
            <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
                <tr class="ListviewTitleStyle">
                    <td><?php echo $lang['usage_ref']; ?></a></td>
                    <td><?php echo $lang['usage_location']; ?></a></td>
                    <td><?php echo $lang['usage_medium']; ?></a></td>
                    <td><?php echo $lang['description']; ?></a></td>
                    <td><?php echo $lang['usage_date']; ?></a></td>
    <?php
    if($edit_access)
        {
        ?>
        <td><div class="ListTools"><?php echo $lang['tools'] ?></div></td>
        <?php
        }
        ?>
        </tr>

    <?php
    foreach($usages as $usage)
        {
        ?>
        <tr>
        <td><?php echo $usage['ref']; ?></td>
        <td><?php echo $usage['usage_location']; ?></td>
        <td><?php echo $usage['usage_medium']; ?></td>
        <td><?php echo $usage['description']; ?></td>
        <td><?php echo nicedate($usage['usage_date']); ?></td>
    
        <?php 
        if($edit_access)
            {
            ?>
            <td>
                <div class="ListTools">
                    <a href="<?php echo $baseurl_short ?>plugins/resource_usage/pages/edit.php?ref=<?php echo $usage['ref'] ?>&resource=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php echo $lang["action-edit"]?></a>
                    <a href="<?php echo $baseurl_short ?>plugins/resource_usage/pages/delete.php?ref=<?php echo $usage['ref'] ?>&resource=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php echo $lang["action-delete"]?></a>
                </div>
            </td>
            <?php
            }
            ?>
        </tr>
        <?php
        }
        ?>
        </table>
        </div>
        <?php
        }
        ?>
    </div>
    </div>
    <?php
    # Allow further custom panels
    return false;
    }