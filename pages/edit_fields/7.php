<?php
/* -------- Category Tree ------------------- */ 
global $lang, $baseurl, $css_reload_key, $category_tree_show_status_window,
$category_tree_open, $is_search, $cat_tree_singlebranch, $category_tree_add_parents,
$category_tree_remove_children;

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

    $hidden_input_elements .= "<input id=\"{$hidden_input_elements_id_prefix}{$node['ref']}\" class =\"{$tree_id}_nodes\" type=\"hidden\" name=\"{$name}\" value=\"{$node['ref']}\">";

    // Show previously searched options on the status box
    if(!(isset($treeonly) && true == $treeonly))
        {
        $status_box_elements .= "<div class=\"" . $tree_id . "_option_status\"  ><span id=\"{$status_box_id}_option_{$node['ref']}\">{$node['name']}</span><br></div>";
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
        >&gt; <?php echo $lang['showhidetree']; ?></a>
        &nbsp;
        <a href="#"
           onclick="
			<?php if (!$is_search) // No need to confim, this is just for searching
				{?>
                if(confirm('<?php echo $lang["clearcategoriesareyousure"]?>'))
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
        >&gt; <?php echo $lang['clearall']; ?></a>
    </div>
    <?php
    }

echo $hidden_input_elements;
?>
    <div id="<?php echo $tree_id; ?>" style="<?php echo $tree_container_styling; ?>"></div>
    <script>
	jstree_singlenode=<?php echo (($cat_tree_singlebranch && !$is_search)? 'true' : 'false'); ?>;	
	
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
            'three_state' : false,
            'cascade' : '<?php echo (($category_tree_add_parents && !$is_search ) ? 'up' : '' ); ?>'
        }
    });

    // Update changes done in category tree
    jQuery('#<?php echo $tree_id; ?>').on('changed.jstree', function (event, data)
        {
        // Add value to the hidden input array
        if(data.action == 'select_node')
			{			
			if(jstree_singlenode && ((typeof autocheck == 'undefined') || !autocheck))
				{
				jQuery('.<?php echo $tree_id; ?>_nodes').remove();
				jQuery('.<?php echo $tree_id; ?>_option_status').remove();
				}
				
            // Add hidden input in order to do the search
            document.getElementById('<?php echo $tree_id; ?>').insertAdjacentHTML('beforeBegin', '<input id="<?php echo $hidden_input_elements_id_prefix; ?>' + data.node.id + '" type="hidden" name="<?php echo $name; ?>" class ="<?php echo $tree_id; ?>_nodes" value="' + data.node.id + '">');

            // Update status box with the selected option
            var status_option_element = document.getElementById('<?php echo $status_box_id;?>_option_' + data.node.id);
            if(status_option_element == null)
                {
                document.getElementById('<?php echo $status_box_id; ?>').insertAdjacentHTML('beforeEnd', '<div class="<?php echo $tree_id; ?>_option_status" ><span id="<?php echo $status_box_id;?>_option_' + data.node.id + '">' + data.node.text + '</span><br></div>');
                }
			<?php if ($category_tree_add_parents && !$is_search )
				{?>
				// Add parents
				ParentNode = jQuery('#<?php echo $tree_id; ?>').jstree('get_parent', data.node);
				autocheck=true;
				jQuery('#<?php echo $tree_id; ?>').jstree('select_node', ParentNode);
				<?php
				}?>
		}
        // Remove the value from the array
        else if(data.action == 'deselect_node')
            {
            jQuery('#<?php echo $hidden_input_elements_id_prefix; ?>' + data.node.id).remove();
            jQuery('#<?php echo $status_box_id;?>_option_' + data.node.id).next('br').remove();
            jQuery('#<?php echo $status_box_id;?>_option_' + data.node.id).remove();
            <?php
            if($category_tree_remove_children && !$is_search)
                {
                ?>
                // If parent node is closed, make sure we open its children and deselect them as well
                if(jQuery('#<?php echo $tree_id; ?>').jstree('is_closed', data.node))
                    {
                    jQuery('#<?php echo $tree_id; ?>').jstree(
                        'open_node',
                        data.node,
                        function ()
                            {
                            // Remove child nodes
                            ChildNodes = jQuery('#<?php echo $tree_id; ?>').jstree('get_children_dom', data.node);

                            jQuery.each(ChildNodes, function( index, value )
                                {
                                jQuery('#<?php echo $tree_id; ?>').jstree('deselect_node', value);
                                });
                            },
                        false);
                    }

                // Remove child nodes
                ChildNodes = jQuery('#<?php echo $tree_id; ?>').jstree('get_children_dom', data.node);

                jQuery.each(ChildNodes, function( index, value )
                    {
                    jQuery('#<?php echo $tree_id; ?>').jstree('deselect_node', value);
                    });
                <?php
                }
                ?>
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
			autocheck=false;
        });
    </script>
</div>
