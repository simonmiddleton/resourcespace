<?php
/*******************************************/
/**************RADIO BUTTONS****************/
/*******************************************/

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
$l = average_length($node_options);

$cols = 10;
if($l > 5) 
    {
    $cols = 6;
    }

if($l > 10)
    {
    $cols = 4;
    }

if($l > 15)
    {
    $cols = 3;
    }

if($l > 25)
    {
    $cols = 2;
    }

$rows = ceil(count($node_options) / $cols);

// Default behaviour
if(!isset($display_as_radiobuttons))
    {
    $display_as_radiobuttons = true;
    }

// Display as checkboxes is a feature for advanced search only
if(!isset($display_as_checkbox))
    {
    $display_as_checkbox = false;
    }

// Display as dropdown is a feature for advanced search only, if set in field options
if(!isset($display_as_dropdown))
    {
    $display_as_dropdown = false;
    }

// Autoupdate is set only on search forms, otherwise it should be false
if(!isset($autoupdate))
    {
    $autoupdate = false;
    }

if(!isset($help_js))
    {
    $help_js = '';
    }

if($edit_autosave)
    {
    ?>
    <script type="text/javascript">
    // Function to allow radio buttons to save automatically when $edit_autosave from config is set: 
    function radio_allow_save()
        {
        preventautosave = false;

        setTimeout(function()
            {
            preventautosave = true;
            }, 1000);
        }
    </script>
    <?php
    }

if($display_as_radiobuttons) 
    {
    ?>
    <table id="" class="radioOptionTable" cellpadding="3" cellspacing="3">                    
        <tbody>
            <tr>
            <?php 
            $row = 1;
            $col = 1;

            foreach($field['nodes'] as $node)
                {
                if($col > $cols) 
                    {
                    $col = 1;
                    $row++; ?>
                    </tr>
                    <tr>
                    <?php 
                    }
                $col++;
                ?>
                <td width="10" valign="middle">
                    <input type="radio"
                           id="field_<?php echo $field["ref"] . '_' . sha1($node['name']); ?>"
                           name="<?php echo $name; ?>"
                           value="<?php echo $node['ref']; ?>"
                       <?php
                       if(in_array($node['ref'], $selected_nodes) || (isset($user_set_values[$field['ref']]) && $node['ref'] == $user_set_values[$field['ref']]))
                            {
                            ?>
                            checked
                            <?php
                            }

                        if($edit_autosave)
                            {
                            ?>
                            onChange="AutoSave('<?php echo $field["ref"]; ?>');"
                            <?php
                            }
                        if($autoupdate)
                            {
                            ?>
                            onChange="UpdateResultCount();"
                            <?php
                            }

                        echo $help_js;?>>
                </td>
                <td align="left" valign="middle">
                    <label class="customFieldLabel"
                           for="field_<?php echo $field["ref"] . '_' . sha1($node['name']); ?>"
                        <?php
                        if($edit_autosave)
                            {
                            ?>
                            onmousedown="radio_allow_save();"<?php
                            }
                            ?>><?php echo i18n_get_translated($node['name']); ?></label>
                </td>
                <?php 
                } 
                ?>
            </tr>
        </tbody>
    </table>
    <?php
    }
// On advanced search, by default, show as checkboxes:
else if($display_as_checkbox)
    {
    ?>
    <table cellpadding=2 cellspacing=0>
        <tbody>
            <tr>
            <?php
            $row = 1;
            $col = 1;

            foreach($field['nodes'] as $node)
                {
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
                <td valign=middle>
                    <input id="nodes_searched_<?php echo $node['ref']; ?>"
                           type="checkbox"
                           name="<?php echo $name; ?>"
                           value="<?php echo $node['ref']; ?>"
                        <?php
                        if(in_array($node['ref'], $selected_nodes))
                            {
                            ?>
                            checked
                            <?php
                            }
                        if($autoupdate)
                            {
                            ?>
                            onClick="UpdateResultCount();"
                            <?php
                            }
                            ?>>
                </td>
                <td valign=middle>
                    <?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?>&nbsp;&nbsp;
                </td>
                <?php 
                }
                ?>
            </tr>
        </tbody>
    </table>
    <?php
    }
// On advanced search, display it as a dropdown, if set like this:
else if($display_as_dropdown)
    {
    ?>
    <select class="<?php echo $class; ?>" name="<?php echo $name; ?>" <?php if($autoupdate) { ?>onChange="UpdateResultCount();"<?php } ?>>
        <option value=""></option>
    <?php
    foreach($field['nodes'] as $node)
        {
        if('' != trim($node['name']))
            {
            ?>
            <option value="<?php echo htmlspecialchars(trim($node['ref'])); ?>"<?php if(in_array($node['ref'], $selected_nodes)) { ?> selected<?php } ?>><?php echo htmlspecialchars(trim(i18n_get_translated($node['name']))); ?></option>
            <?php
            }
        }
        ?>
    </select>
    <?php
    }
