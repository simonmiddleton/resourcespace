<?php
function HookMuseumplusViewRenderfield($field, $resource)
    {
    if(!checkperm('a'))
        {
        return false;
        }

    global $baseurl, $search, $ref, $museumplus_mpid_field, $museumplus_resource_types;

    if($field['ref'] == $museumplus_mpid_field && in_array($resource['resource_type'], $museumplus_resource_types))
        {
        $museumplus_mpid_field = $field['value'];
        if(trim($museumplus_mpid_field) == '')
            {
            return false;
            }

        $value = highlightkeywords($museumplus_mpid_field, $search, $field['partial_index'], $field['name'], $field['keywords_index']);
        ?>
        <div class="itemNarrow">
            <h3><?php echo htmlspecialchars($field['title']); ?></h3>
            <p>
                <a href="<?php echo $baseurl; ?>/plugins/museumplus/pages/museumplus_object_details.php?ref=<?php echo $ref; ?>"><?php echo $value; ?></a>
            </p>
        </div>
        <?php

        return true;
        }

    return false;
    }