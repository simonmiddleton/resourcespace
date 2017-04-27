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
        asort($reordered_options);
        }
    }

$new_node_order = array();
foreach($reordered_options as $reordered_node_id => $reordered_node_option)
    {
    $new_node_order[$reordered_node_id] = $field['nodes'][array_search($reordered_node_id, array_column($field['nodes'], 'ref', 'ref'))];
    }

$field['nodes'] = $new_node_order;
##### End of reordering options #####
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
        <option value="<?php echo htmlspecialchars(trim($node['ref'])); ?>"<?php if(in_array($node['ref'], $selected_nodes)) { ?> selected<?php } ?>><?php echo htmlspecialchars(trim(i18n_get_translated($node['name']))); ?></option>
        <?php
        }
    }
    ?>
</select>