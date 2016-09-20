<?php
/*******************************************/
/**************RADIO BUTTONS****************/
/*******************************************/
$set = trim($value);

// Translate the options:
for($i = 0; $i < count($field['node_options']); $i++)
    {
    $field['node_options'][$i] = i18n_get_translated($field['node_options'][$i]);
    }

$l = average_length($field['node_options']);

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

$rows = ceil(count($field['node_options']) / $cols);

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

            foreach($field['node_options'] as $val)
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
                    <input type="radio" id="field_<?php echo $field["ref"] . '_' . sha1($val); ?>" name="field_<?php echo $field["ref"]; ?>" value="<?php echo $val; ?>" <?php if(i18n_get_translated($val)==i18n_get_translated($set) || ','.i18n_get_translated($val) == i18n_get_translated($set)) {?>checked<?php } ?> <?php if($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"] ?>');"<?php } if ($autoupdate) { ?>onChange="UpdateResultCount();"<?php } echo $help_js; ?>/>
                </td>
                <td align="left" valign="middle">
                    <label class="customFieldLabel" for="field_<?php echo $field["ref"] . '_' . sha1($val); ?>" <?php if($edit_autosave) { ?>onmousedown="radio_allow_save();" <?php } ?>><?php echo $val; ?></label>
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

                if(isset($searched_nodes) && 0 < count($searched_nodes) && in_array($node['ref'], $searched_nodes))
                    {
                    $set = $node['ref'];
                    }
                    ?>
                <td valign=middle>
                    <input id="nodes_searched_<?php echo $node['ref']; ?>" type="checkbox" name="nodes_searched[<?php echo $field['ref']; ?>][]" value="<?php echo $node['ref']; ?>" <?php if($node['ref'] == $set) { ?>checked<?php } ?> <?php if($autoupdate) { ?>onClick="UpdateResultCount();"<?php } ?>>
                </td>
                <td valign=middle>
                    <?php echo htmlspecialchars($node['name']); ?>&nbsp;&nbsp;
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
    <select class="<?php echo $class; ?>" name="nodes_searched[<?php echo $field['ref']; ?>]" <?php if($autoupdate) { ?>onChange="UpdateResultCount();"<?php } ?>>
        <option value=""></option>
    <?php
    foreach($field['nodes'] as $node)
        {
        if('' != trim($node['name']))
            {
            if(isset($searched_nodes) && 0 < count($searched_nodes) && in_array($node['ref'], $searched_nodes))
                {
                $set = $node['ref'];
                }
                ?>
            <option value="<?php echo htmlspecialchars(trim($node['ref'])); ?>" <?php if($node['ref'] == $set) { ?>selected<?php } ?>><?php echo htmlspecialchars(trim($node['name'])); ?></option>
            <?php
            }
        }
        ?>
    </select><?php
    }
    ?>