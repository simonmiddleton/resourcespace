<?php
function HookMuseumplusViewRenderfield($field, $resource)
    {
    global $baseurl, $search, $ref, $lang, $museumplus_secondary_links_field;

    $resource_association = mplus_get_associated_module_conf(array($resource['ref']), false);
    if(empty($resource_association))
        {
        return false;
        }
    $rs_uid_field = $resource_association[$resource['ref']]['rs_uid_field'];
    $applicable_resource_types = $resource_association[$resource['ref']]['applicable_resource_types'];
    $module_name = $resource_association[$resource['ref']]['module_name'];

    $field_ref = (isset($field['ref']) ? $field['ref'] : 0);
    $field_value = (isset($field['value']) ? trim($field['value']) : '');

    if(!in_array($resource['resource_type'], $applicable_resource_types) || $field_ref == 0 || $field_value === '')
        {
        return false;
        }

    // Generate and render the secondary MPlus links
    if($museumplus_secondary_links_field == $field_ref)
        {
        $rendered_secondary_links = array();
        $sec_links = explode(',', $field_value);
        foreach($sec_links as $sec_link_str)
            {
            $sec_link_str = trim($sec_link_str);
            if($sec_link_str === '')
                {
                continue;
                }

            $sec_link_parts = explode(':', $sec_link_str);
            $sec_link_module = (isset($sec_link_parts[0]) ? $sec_link_parts[0] : '');
            $sec_link_id = (isset($sec_link_parts[1]) && is_numeric($sec_link_parts[1]) ? $sec_link_parts[1] : 0);

            $mplus_module_record_url = mplus_generate_module_record_url($sec_link_module, (int) $sec_link_id);

            if($mplus_module_record_url === '')
                {
                continue;
                }

            $rendered_secondary_links[] = sprintf('<a href="%s" target="_blank">%s</a>', $mplus_module_record_url, htmlspecialchars($sec_link_str));
            }

        if(!empty($rendered_secondary_links))
            {
            ?>
            <div class="item">
                <h3><?php echo htmlspecialchars($field['title']); ?></h3>
                <p><?php echo implode(', ', $rendered_secondary_links); ?></p>
            </div>
            <div class="clearerleft"></div>
            <?php

            return true;
            }
        
        return false;
        }


    if(!checkperm('a'))
        {
        return false;
        }

    if($field_ref == $rs_uid_field)
        {
        $mpid = $field_value;
        if($mpid === '')
            {
            return false;
            }

        $value = highlightkeywords($mpid, $search, $field['partial_index'], $field['name'], $field['keywords_index']);

        $mplus_object_url = (is_numeric($mpid) ? mplus_generate_module_record_url($module_name, $mpid) : '');
        ?>
        <div class="itemNarrow">
            <h3><?php echo htmlspecialchars($field['title']); ?></h3>
            <p>
                <a href="<?php echo $baseurl; ?>/plugins/museumplus/pages/museumplus_object_details.php?ref=<?php echo $ref; ?>"><?php echo $value; ?></a>
            </p>
            <?php
            if($mplus_object_url !== '')
                {
                ?>
                <p>
                    <a href="<?php echo $mplus_object_url; ?>" target="_blank"><?php echo htmlspecialchars($lang['museumplus_view_in_museumplus']); ?></a>
                </p>
                <?php
                }
                ?>
        </div>
        <?php

        return true;
        }

    return false;
    }