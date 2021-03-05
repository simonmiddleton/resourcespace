<?php
/* -------- Category Tree ------------------- */ 
global $lang, $baseurl, $css_reload_key, $category_tree_show_status_window;
global$category_tree_open, $is_search, $cat_tree_singlebranch, $category_tree_add_parents,
$category_tree_remove_children, $k;

$is_search      = (isset($is_search) ? $is_search : false);
$forsearchbar   = (isset($forsearchbar) ? $forsearchbar : false);
$edit_autosave  = (isset($edit_autosave) ? $edit_autosave : false);

$hidden_input_elements             = '';
$hidden_input_elements_id_prefix   = ($is_search ? 'nodes_searched_' : 'nodes_');
$status_box_id                     = ($is_search ? "nodes_searched_{$field['ref']}_statusbox" : "nodes_{$field['ref']}_statusbox");
$status_box_elements               = '';
$update_result_count_function_call = 'UpdateResultCount();';
$tree_id                           = ($is_search ? "search_tree_{$field['ref']}" : "tree_{$field['ref']}");
$tree_container_styling            = ($category_tree_open ? 'display: block;' : 'display: none;');

$current_val_fieldname = "field_{$field['ref']}_currentval";

if(!isset($selected_nodes))
    {
    $selected_nodes = array();

    if(isset($searched_nodes) && is_array($searched_nodes))
        {
        $selected_nodes = $searched_nodes;
        }
    }

// User set values are options selected by user - used to render what users selected before submitting the form and
// receiving an error (e.g required field missing)
if(isset($user_set_values[$field['ref']]) && is_array($user_set_values[$field['ref']]) && !empty($user_set_values[$field['ref']]))
    {
    $selected_nodes = $user_set_values[$field['ref']];
    }

foreach($selected_nodes as $node)
    {
    $node_data = array();
    if(get_node($node, $node_data) && $node_data["resource_type_field"] != $field["ref"])
        {
        continue;
        }

    if(get_node($node, $node_data)===false)
        {
        continue;
        }

    $hidden_input_elements .= "<input id=\"{$hidden_input_elements_id_prefix}{$node_data["ref"]}\" class =\"{$tree_id}_nodes\" type=\"hidden\" name=\"{$name}\" value=\"{$node_data["ref"]}\">";

    // Show previously searched options on the status box
    if(!(isset($treeonly) && true == $treeonly))
        {
        $status_box_elements .= "<div class=\"" . $tree_id . "_option_status\"  ><span id=\"{$status_box_id}_option_{$node_data['ref']}\">" . htmlspecialchars($node_data['name']) . "</span><br /></div>";
        }
    }

if($forsearchbar)
    {
    $update_result_count_function_call = '';
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
        ><?php echo LINK_CARET . $lang['showhidetree']; ?></a>
        &nbsp;
        <a href="#"
           onclick="
			<?php if (!$is_search) // No need to confim, this is just for searching
				{?>
                if(confirm('<?php echo $lang["clearcategoriesareyousure"];?>'))
                    {
				<?php }?>
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

                    <?php
					if(!$is_search && $edit_autosave)
						{
						echo "AutoSave('{$field['ref']}');";
						}

					echo $update_result_count_function_call;
					
				if (!$is_search) 
					{?>
					}<?php
					}
				?>
                return false;"
        ><?php echo LINK_CARET .  $lang['clearall']; ?></a>
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
                            selected_nodes : <?php echo json_encode($selected_nodes); ?>,
                            k : '<?php echo htmlspecialchars($k); ?>',
                            };
                    }
            },
            'multiple' : <?php echo ($cat_tree_singlebranch && !$is_search ? 'false' : 'true'); ?>,
            'themes' : {
                'icons' : false
            }
        },
        'plugins' : [
            'wholerow',
            'checkbox'
        ],
        'checkbox' : {
            // jsTree Documentation: three_state is a boolean indicating if checkboxes should cascade down and have an 
            // undetermined state. Defaults to true
            'three_state': false,
            // jsTree Documentation: This setting controls how cascading and undetermined nodes are applied.
            // If 'up' is in the string - cascading up is enabled, if 'down' is in the string - cascading down is enabled,
            // if 'undetermined' is in the string - undetermined nodes will be used.
            // If three_state is set to true this setting is automatically set to 'up+down+undetermined'. Defaults to ''.
            // IMPORTANT: we set it to default so we can create our intended behaviour
            'cascade': ''
        }
    });

    
    /*
    Intended behaviour (jstree does certain things by default that we don't want)
    ----------------------------------------------
    1. Selecting a sub (child) node will automatically select all parent nodes up to and including the root level, unless 
       the option $category_tree_add_parents is set to false
    2. Deselecting a parent node will automatically deselect all child nodes, unless the option $category_tree_remove_children
       is set to false
    3. Deselecting a sub node will not affect any parent nodes
    4. If $cat_tree_singlebranch is set to true, selection of any node will automatically remove any previously selected
       nodes and follow the behaviour described in (1)
    5. Selection of a parent node will have no effect on any sub nodes
    */
    var jquery_tree_by_id             = jQuery('#<?php echo $tree_id; ?>');
    var category_tree_add_parents     = <?php echo ($category_tree_add_parents ? 'true' : 'false'); ?>;
    var category_tree_remove_children = <?php echo ($category_tree_remove_children ? 'true' : 'false'); ?>;

    // When a node is selected, and add parents is enabled, then automatically select all ancestors 
    jquery_tree_by_id.on('select_node.jstree', function (event, data)
        {
        if(category_tree_add_parents)
            {
            // Establish the parent of the selected node
            var parent_node = jQuery('#<?php echo $tree_id; ?>')
                .jstree(true)
                .get_parent(jQuery('#<?php echo $tree_id; ?>').jstree(true).get_node(data.node.id));

            // Trigger selection of the parent node
            jQuery('#<?php echo $tree_id; ?>')
                .jstree(true)
                .select_node(jQuery('#<?php echo $tree_id; ?>').jstree(true).get_node(parent_node), data.selected, event);
            }
        });

<?php
if(!$is_search)
    {
    ?>
    // When a node is deselected, and remove children is enabled, then automatically deselect its descendants 
    jquery_tree_by_id.on('deselect_node.jstree', function (event, data)
        {					
        if(category_tree_remove_children)
            {
            // If the deselected node is closed then open it
            if(jQuery('#<?php echo $tree_id; ?>').jstree('is_closed', data.node))
                {
                jQuery('#<?php echo $tree_id; ?>').jstree(
                    'open_node',
                    data.node);
                }

            // Establish the descendents of the deselected node
            var rendered_childrens = jQuery('#<?php echo $tree_id; ?>').jstree('get_children_dom', data.node);
        
            for(var i = 0; i < rendered_childrens.length; i++)
                {
                // Trigger deselection of each descendent
                jQuery('#<?php echo $tree_id; ?>')
                    .jstree(true)
                    .deselect_node(
                        jQuery('#<?php echo $tree_id; ?>')
                        .jstree(true)
                        .get_node(rendered_childrens[i]), data.selected, event
                    );
                }
            }
        });
    
    <?php
    }	
    ?>				

    // Reflect node selections onto the status box and hidden inputs 
    jquery_tree_by_id.on('select_node.jstree', function (event, data)
        {
        // Add hidden input which is used to post to the edit or search 
        document.getElementById('<?php echo $tree_id; ?>').insertAdjacentHTML(
            'beforeBegin',
            '<input id="<?php echo $hidden_input_elements_id_prefix; ?>' + data.node.id +
            '" type="hidden" name="<?php echo $name; ?>" class ="<?php echo $tree_id; ?>_nodes" value="' + data.node.id +
            '">');
        });

    // Reflect node deselections onto the status box and hidden inputs 
    jquery_tree_by_id.on('deselect_node.jstree', function (event, data)
        {
        // Remove hidden input so that it is no longer posted to the edit or search 
        jQuery('#<?php echo $hidden_input_elements_id_prefix; ?>'+data.node.id+'.<?php echo "{$tree_id}_nodes"?>').remove();
        });

    // Reflect aggregated changes on status box; trigger centralspace events; trigger autosave
    jquery_tree_by_id.on('changed.jstree', function (event, data)
        {
        if(!(data.action == 'select_node' || data.action == 'deselect_node'))
            {
            return;
            }

        // Clear the status box containing the names of the selected nodes
        jQuery('#<?php echo $status_box_id; ?>').empty();

        var selected_rs_node_ids = data.selected;

        for(var i = 0; i < selected_rs_node_ids.length; i++)
            {
            // Update status box with the selected option
            var status_option_element = document.getElementById('<?php echo $status_box_id; ?>_option_' + selected_rs_node_ids[i]);
            if(status_option_element == null)
                {
                document.getElementById('<?php echo $status_box_id; ?>').insertAdjacentHTML(
                    'beforeEnd',
                    '<div class="<?php echo $tree_id; ?>_option_status"><span id="<?php echo $status_box_id;?>_option_'
                    + selected_rs_node_ids[i] + '">'
                    + jQuery('#<?php echo $tree_id; ?>').jstree(true).get_node(selected_rs_node_ids[i]).text
                    + '</span><br /></div>');
                }

            // Trigger an event so we can chain actions once we've changed a category tree option
            jQuery('#CentralSpace').trigger('categoryTreeChanged', [{node: selected_rs_node_ids[i]}]);
            //console.log('Category tree: Sending node ID ' + selected_rs_node_ids[i]);
            }

        if(selected_rs_node_ids.length == 0)
            {
            //console.log('Category tree cleared');
            jQuery('#CentralSpace').trigger('categoryTreeChanged', 0);
            }

        <?php
        if($edit_autosave)
            {
            echo "AutoSave('{$field['ref']}');";
            }

        echo $update_result_count_function_call;
        ?>
        });
    </script>
</div>
