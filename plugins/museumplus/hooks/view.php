<?php
function HookMuseumplusViewRenderfield($field, $resource)
    {
    global $baseurl, $search, $ref, $museumplus_mpid_field, $museumplus_resource_types, $lang, $museumplus_secondary_links_field,
    $museumplus_module_name_field;

    $field_ref = (isset($field['ref']) ? $field['ref'] : 0);
    $field_value = (isset($field['value']) ? trim($field['value']) : '');

    if(!in_array($resource['resource_type'], $museumplus_resource_types) || $field_ref == 0 || $field_value === '')
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
            $sec_link_id = (isset($sec_link_parts[1]) ? $sec_link_parts[1] : 0);

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

    if($field_ref == $museumplus_mpid_field)
        {
        $museumplus_mpid_field = $field['value'];
        if(trim($museumplus_mpid_field) == '')
            {
            return false;
            }

        $resource_module_name = get_resource_nodes($resource['ref'], $museumplus_module_name_field, true);
        if(empty($resource_module_name))
            {
            return false;
            }
        $resource_module_name = $resource_module_name[0]['name'];

        $value = highlightkeywords($museumplus_mpid_field, $search, $field['partial_index'], $field['name'], $field['keywords_index']);

        $mplus_object_url = mplus_generate_module_record_url($resource_module_name, $museumplus_mpid_field);
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