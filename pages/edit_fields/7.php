<?php
/* -------- Category Tree ------------------- */ 
global $lang, $baseurl, $css_reload_key, $category_tree_show_status_window, $category_tree_open, $is_search;

$is_search      = (isset($is_search) ? $is_search : false);
$forsearchbar   = (isset($forsearchbar) ? $forsearchbar : false);
$edit_autosave  = (isset($edit_autosave) ? $edit_autosave : false);

$hidden_input_elements             = '';
$hidden_input_elements_id_prefix   = ($is_search ? 'nodes_searched_' : 'nodes_');
$status_box_id                     = ($is_search ? "nodes_searched_{$field['ref']}_statusbox" : "nodes_{$field['ref']}_statusbox");
$status_box_elements               = '';
$update_result_count_function_call = 'UpdateResultCount();';
$tree_id                           = ($is_search ? "search_tree_{$field['ref']}" : "tree_{$field['ref']}");
$tree_container_styling            = 'display: none;';

if(!isset($selected_nodes))
    {
    $selected_nodes = array();

    if(isset($searched_nodes) && is_array($searched_nodes))
        {
        $selected_nodes = $searched_nodes;
        }
    }

foreach($field['nodes'] as $node)
    {
    if(!in_array($node['ref'], $selected_nodes))
        {
        continue;
        }

    $hidden_input_elements .= "<input id=\"{$hidden_input_elements_id_prefix}{$node['ref']}\" type=\"hidden\" name=\"{$name}\" value=\"{$node['ref']}\">";

    // Show previously searched options on the status box
    if(!(isset($treeonly) && true == $treeonly))
        {
        $status_box_elements .= "<span id=\"{$status_box_id}_option_{$node['ref']}\">{$node['name']}</span><br>";
        }
    }

if($forsearchbar)
    {
    $update_result_count_function_call = '';

    $tree_container_styling = '';
    }

if(!$is_search)
    {
    $update_result_count_function_call = '';
    }
?>
<div class="Fixed">
<?php
if(!(isset($treeonly) && true == $treeonly))
	{
	?>
    <div id="<?php echo $status_box_id; ?>" class="CategoryBox" <?php if(!$category_tree_show_status_window) { ?>style="display:none;"<?php } ?>>
        <?php echo $status_box_elements; ?>
    </div>
    <div>
        <a href="#"
           onclick="
                if(document.getElementById('<?php echo $tree_id; ?>').style.display!='block')
                    {
                    document.getElementById('<?php echo $tree_id; ?>').style.display='block';
                    }
                else
                    {
                    document.getElementById('<?php echo $tree_id; ?>').style.display='none';
                    }
                return false;"
        >&gt; <?php echo $lang['showhidetree']; ?></a>
        &nbsp;
        <a href="#"
           onclick="
                if(confirm('<?php echo $lang["clearcategoriesareyousure"]?>'))
                    {
                    jQuery('#<?php echo $tree_id; ?>').jstree(true).deselect_all();

                    /* remove the hidden inputs */
                    var elements = document.getElementsByName('<?php echo $name; ?>');
                    while(elements[0])
                        {
                        elements[0].parentNode.removeChild(elements[0]);
                        }

                    /* update status box */
                    var node_statusbox = document.getElementById('<?php echo $status_box_id; ?>');
                    while(node_statusbox.lastChild)
                        {
                        node_statusbox.removeChild(node_statusbox.lastChild);
                        }

                    <?php echo $update_result_count_function_call; ?>
                    }
                return false;"
        >&gt; <?php echo $lang['clearall']; ?></a>
    </div>
    <?php
    }

echo $hidden_input_elements;
?>
    <div id="<?php echo $tree_id; ?>" style="<?php echo $tree_container_styling; ?>"></div>
    <script>
    jQuery('#<?php echo $tree_id; ?>').jstree({
        'core' : {
            'data' : {
                    url  : '<?php echo $baseurl; ?>/pages/ajax/category_tree_lazy_load.php',
                    data : function(node) {
                        return {
                            ajax           : true,
                            node_ref       : node.id,
                            field          : <?php echo $field['ref']; ?>,
                            selected_nodes : <?php echo json_encode($selected_nodes); ?>
                            };
                    }
                },
            'themes' : {
                'icons' : false
            }
        },
        'plugins' : [
            'wholerow',
            'checkbox'
        ],
        'checkbox' : {
            'three_state' : false // to tick checkboxes individually
        }
    });

    // Update changes done in category tree
    jQuery('#<?php echo $tree_id; ?>').on('changed.jstree', function (event, data)
        {
        // Add value to the hidden input array
        if(data.action == 'select_node')
            {
            // Add hidden input in order to do the search
            document.getElementById('<?php echo $tree_id; ?>').insertAdjacentHTML('beforeBegin', '<input id="<?php echo $hidden_input_elements_id_prefix; ?>' + data.node.id + '" type="hidden" name="<?php echo $name; ?>" value="' + data.node.id + '">');

            // Update status box with the selected option
            var status_option_element = document.getElementById('<?php echo $status_box_id;?>_option_' + data.node.id);
            if(status_option_element == null)
                {
                document.getElementById('<?php echo $status_box_id; ?>').insertAdjacentHTML('beforeEnd', '<span id="<?php echo $status_box_id;?>_option_' + data.node.id + '">' + data.node.text + '</span><br>');
                }
            }
        // Remove the value from the array
        else if(data.action == 'deselect_node')
            {
            jQuery('#<?php echo $hidden_input_elements_id_prefix; ?>' + data.node.id).remove();
            jQuery('#<?php echo $status_box_id;?>_option_' + data.node.id).next('br').remove();
            jQuery('#<?php echo $status_box_id;?>_option_' + data.node.id).remove();
            }

        // Common actions for both selecting or deselecting a node
        if(data.action == 'select_node' || data.action == 'deselect_node')
            {
            <?php
            if($edit_autosave)
                {
                echo "AutoSave('{$field['ref']}');";
                }

            echo $update_result_count_function_call;
            ?>

            // Trigger an event so we can chain actions once we've changed a category tree option
            jQuery('#CentralSpace').trigger('categoryTreeChanged',[{node: data.node.id}]);
			}
        });
    </script>
</div>