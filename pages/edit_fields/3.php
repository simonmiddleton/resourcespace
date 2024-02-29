<?php
/* -------- Drop down list ------------------ */ 

// Selected nodes should be used most of the times.
// When searching, an array of searched_nodes can be found instead
// which represent the same thing (ie. already selected values)
if(!isset($selected_nodes))
    {
    $selected_nodes = array();
    }

if((bool) $field['automatic_nodes_ordering'])
    {
    $field['nodes'] = reorder_nodes($field['nodes']);
    }
?>
<select class="stdwidth"
        name="<?php echo $name; ?>"
        id="<?php echo $name; ?>"
        <?php
        echo $help_js;
        hook('additionaldropdownattributes', '', array($field));
        if($edit_autosave)
            {
            ?>
            onChange="AutoSave('<?php echo $field['ref']; ?>');"
            <?php
            }
            ?>>
<?php
global $pagename;
if(!hook('replacedropdowndefault', '', array($field)))
    {
    ?>
    <option value=""></option>
    <?php
    }

foreach($field['nodes'] as $node)
    {
    if('' != trim($node['name']))
        {
        ?>
        <option value="<?php echo htmlspecialchars(trim($node['ref'])); ?>"
        <?php 
        // When editing multiple resources, we don't want to preselect any options; the user must make the necessary selection
        if((!$multiple || $copyfrom != '')
            && in_array($node['ref'], $selected_nodes) || (isset($user_set_values[$field['ref']]) 
            && $node['ref']==$user_set_values[$field['ref']])) 
            { ?>
             selected
             <?php 
            } ?>><?php echo htmlspecialchars(trim(i18n_get_translated($node['name']))); ?></option>
        <?php
        }
    }
    ?>
</select>
