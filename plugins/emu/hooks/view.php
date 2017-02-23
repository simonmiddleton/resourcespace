<?php
function HookEmuViewRenderfield($field, $resource)
    {
    if(!checkperm('a'))
        {
        return false;
        }

    global $baseurl, $search, $ref, $emu_irn_field, $emu_resource_types, $emu_created_by_script_field;

    if($field['ref'] == $emu_irn_field && in_array($resource['resource_type'], $emu_resource_types))
        {
        $emu_irn = $field['value'];
        $value   = highlightkeywords($emu_irn, $search, $field['partial_index'], $field['name'], $field['keywords_index']);
        ?>
        <div class="itemNarrow">
            <h3><?php echo htmlspecialchars($field['title']); ?></h3>
            <p>
                <a href="<?php echo $baseurl; ?>/plugins/emu/pages/emu_object_details.php?ref=<?php echo $ref; ?>&irn=<?php echo $emu_irn; ?>"><?php echo $value; ?></a>
            </p>
        </div>
        <?php

        return true;
        }

    return false;
    }