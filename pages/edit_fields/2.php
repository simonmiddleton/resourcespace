<?php
/* -------- Check box list ------------------ */
if(!hook('customchkboxes', '', array($field)))
    {
    global $checkbox_ordered_vertically;

    // Selected nodes should be used most of the times.
    // When searching, an array of searched_nodes can be found instead
    // which represent the same thing (ie. already selected values)
    if(!isset($selected_nodes))
        {
        $selected_nodes = array();

        if(isset($searched_nodes) && is_array($selected_nodes))
            {
            $selected_nodes = $selected_nodes;
            }
        }

    $node_options = array();
    foreach($field['nodes'] as $node)
        {
        $node_options[] = $node['name'];
        }

    // Work out an appropriate number of columns based on the average length of the options.
    $l = average_length($node_options);
    switch($l)
        {
        case($l > 40): $cols = 1; break; 
        case($l > 25): $cols = 2; break;
        case($l > 15): $cols = 3; break;
        case($l > 10): $cols = 4; break;
        case($l > 5):  $cols = 5; break;
        default:       $cols = 10;
        }

    ##### Reordering options #####
    $reordered_options = array();
    foreach($field['nodes'] as $node)
        {
        $reordered_options[$node['ref']] = i18n_get_translated($node['name']);
        }
	
    if($auto_order_checkbox && !hook('ajust_auto_order_checkbox', '', array($field)))
        {
        if($auto_order_checkbox_case_insensitive)
            {
            natcasesort($reordered_options);
            }
        else
            {
            natsort($reordered_options);
            }
        }

    $new_node_order    = array();
    $order_by_resetter = 0;
    foreach($reordered_options as $reordered_node_id => $reordered_node_option)
        {
        $new_node_order[$reordered_node_id] = $field['nodes'][array_search($reordered_node_id, array_column($field['nodes'], 'ref', 'ref'))];

        // Special case for vertically ordered checkboxes.
        // Order by needs to be reset as per the new order so that we can reshuffle them using the order by as a reference
        if($checkbox_ordered_vertically)
            {
            $new_node_order[$reordered_node_id]['order_by'] = $order_by_resetter++;
            }
        }

    $field['nodes'] = $new_node_order;
    ##### End of reordering options #####

    $wrap = 0;
    $rows = ceil(count($field['nodes']) / $cols);

    if($edit_autosave)
        {
        ?>
        <script type="text/javascript">
            // Function to allow checkboxes to save automatically when $edit_autosave from config is set: 
            function checkbox_allow_save() {
                preventautosave=false;
                
                setTimeout(function () {
                    preventautosave=true;
                }, 500);
            }
        </script>
        <?php
        }

    if($checkbox_ordered_vertically)
        {
        if(!hook('rendereditchkboxes'))
            {
            # ---------------- Vertical Ordering -----------

            ##### Vertical shuffling #####
            $reshuffled_nodes    = array();

            for($i = 0; $i < $rows; $i++)
                {
                for($j = 0; $j < $cols; $j++)
                    {
                    $order_by = ($rows * $j) + $i;

                    $node_index_to_be_reshuffled = array_search($order_by, array_column($field['nodes'], 'order_by', 'ref'));

                    if(false === $node_index_to_be_reshuffled)
                        {
                        continue;
                        }

                    $reshuffled_nodes[$field['nodes'][$node_index_to_be_reshuffled]['ref']] = $field['nodes'][$node_index_to_be_reshuffled];
                    }
                }

            $field['nodes'] = $reshuffled_nodes;
            ##### End of vertical shuffling #####
            ?>
            <fieldset class="customFieldset" name="<?php echo $field['title']; ?>">
                <legend class="accessibility-hidden"><?php echo $field['title']; ?></legend>
                <table cellpadding=3 cellspacing=0>
                    <tr>
                <?php
                $row = 1;
                $col = 1;

                foreach($field['nodes'] as $node)
                    {
                    if('' == $node['name'])
                        {
                        continue;
                        }

                    if($col > $cols) 
                        {
                        $col = 1;
                        $row++;
                        ?>
                        </tr>
                        <tr>
                        <?php 
                        }

                    $col++;
                        ?>
                    <td>
                        <input type="checkbox"
                               id="nodes_<?php echo $node['ref']; ?>"
                               name="<?php echo $name; ?>"
                               value="<?php echo $node['ref']; ?>"
                            <?php
                            if(in_array($node['ref'], $selected_nodes))
                                {
                                ?>
                                checked
                                <?php
                                }

                            if($edit_autosave)
                                {
                                ?>
                                onChange="AutoSave('<?php echo $field['ref']; ?>');" onmousedown="checkbox_allow_save();"
                                <?php
                                }
                                ?>><label class="customFieldLabel" for="nodes_<?php echo $node['ref']; ?>" <?php if($edit_autosave) { ?>onmousedown="checkbox_allow_save();" <?php } ?>><?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?></label>
                    </td>
                    <?php
                    }
                    ?>
                    </tr>
                </table>
            </fieldset>
            <?php
            }
        }
    else
        {
        # ---------------- Horizontal Ordering ---------------------             
        ?>
		<fieldset class="customFieldset" name="<?php echo $field['title']; ?>">
        <legend class="accessibility-hidden"><?php echo $field['title']; ?></legend>
        <table cellpadding=3 cellspacing=0>
            <tr>
        <?php
        foreach($field['nodes'] as $node)
            {
            $wrap++;
            if($wrap > $cols)
                {
                $wrap = 1;
                ?>
                </tr>
                <tr>
                <?php
                }
                ?>
            <td>
                <input type="checkbox"
                       name="<?php echo $name; ?>"
                       value="<?php echo $node['ref']; ?>"
					   id="nodes_<?php echo $node['ref']; ?>"
                    <?php
                    if(in_array($node['ref'], $selected_nodes))
                        {
                        ?>
                        checked
                        <?php
                        }

                    if($edit_autosave)
                        {
                        ?>
                        onChange="AutoSave('<?php echo $field['ref']; ?>');"
                        <?php
                        }
                        ?>><label class="customFieldLabel" for="nodes_<?php echo $node['ref']; ?>" <?php if($edit_autosave) { ?>onmousedown="checkbox_allow_save();" <?php } ?>><?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?></label></td>
            <?php
            }
            ?>
            </tr>
        </table>
		</fieldset>
        <?php
        }
    }