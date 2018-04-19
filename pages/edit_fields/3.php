<?php
/* -------- Drop down list ------------------ */ 
global $default_to_first_node_for_fields;

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
global $default_to_first_node_for_fields, $pagename;
if(!hook('replacedropdowndefault', '', array($field)) && (!in_array($field["ref"],$default_to_first_node_for_fields) || (in_array($field["ref"],$default_to_first_node_for_fields) && $pagename=="edit" && getval("uploader","")=="" && $value=='')))
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
        <option value="<?php echo htmlspecialchars(trim($node['ref'])); ?>"<?php if(in_array($node['ref'], $selected_nodes) || (isset($user_set_values[$field['ref']]) && $node['ref']==$user_set_values[$field['ref']])) { ?> selected<?php } ?>><?php echo htmlspecialchars(trim(i18n_get_translated($node['name']))); ?></option>
        <?php
        }
    }
    ?>
</select>
