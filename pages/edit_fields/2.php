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

        if(isset($searched_nodes) && is_array($searched_nodes))
			{
			$selected_nodes = $searched_nodes;
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

    if((bool) $field['automatic_nodes_ordering'])
        {
        $field['nodes'] = reorder_nodes($field['nodes']);
        }

    $new_node_order    = array();
    $order_by_resetter = 0;
    foreach($field['nodes'] as $node_index => $node)
        {
        // Special case for vertically ordered checkboxes.
        // Order by needs to be reset as per the new order so that we can reshuffle them using the order by as a reference
        if($checkbox_ordered_vertically)
            {
            $field['nodes'][$node_index]['order_by'] = $order_by_resetter++;
            }
        }

    $wrap = 0;
    $rows = ceil(count($field['nodes']) / $cols);

    if($checkbox_ordered_vertically)
        {
        if(!hook('rendereditchkboxes'))
            {
            # ---------------- Vertical Ordering -----------
            ?>
            <fieldset class="customFieldset" name="<?php echo $field['title']; ?>">
                <legend class="accessibility-hidden"><?php echo $field['title']; ?></legend>
                <table cellpadding="5" cellspacing="0">
                    <tr>
                <?php
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

                        $node = $field['nodes'][$node_index_to_be_reshuffled];
                        ?>
                        <td>
                            <input type="checkbox"
                                   id="nodes_<?php echo $node['ref']; ?>"
                                   name="<?php echo $name; ?>"
                                   value="<?php echo $node['ref']; ?>"
                                <?php
								if(in_array($node['ref'], $selected_nodes) || (isset($user_set_values[$field['ref']]) && in_array($node['ref'],$user_set_values[$field['ref']])))
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
                                    ?>><label class="customFieldLabel" for="nodes_<?php echo $node['ref']; ?>"><?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?></label>
                        </td>
                        <?php
                        }
                        ?>
                    </tr>
                    <tr>
                    <?php
                    }
                    ?>
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
        <table cellpadding="3" cellspacing="0">
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
                    if(in_array($node['ref'], $selected_nodes) || (isset($user_set_values[$field['ref']]) && in_array($node['ref'],$user_set_values[$field['ref']])))
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
                        ?>><label class="customFieldLabel" for="nodes_<?php echo $node['ref']; ?>"><?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?></label></td>
            <?php
            }
            ?>
            </tr>
        </table>
		</fieldset>
        <?php
        }
    }
