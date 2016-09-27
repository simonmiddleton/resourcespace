<?php
/* -------- Category Tree ------------------- */ 
global $lang, $baseurl, $css_reload_key, $category_tree_show_status_window, $category_tree_open, $is_search;

if(!isset($is_search))
    {
    $is_search = false;
    }
    ?>

<div class="Fixed">
<?php
if(!(isset($treeonly) && true == $treeonly))
	{
	?>
    <div id="<?php echo $name?>_statusbox" class="CategoryBox" <?php if(!$category_tree_show_status_window) { ?>style="display:none;"<?php } ?>></div>
    <div>
        <a href="#"
           onclick="
                if(document.getElementById('tree_<?php echo $field['ref']; ?>').style.display!='block')
                    {
                    document.getElementById('tree_<?php echo $field['ref']; ?>').style.display='block';
                    }
                else
                    {
                    document.getElementById('tree_<?php echo $field['ref']; ?>').style.display='none';
                    }
                return false;"
        >&gt; <?php echo $lang['showhidetree']; ?></a>
        &nbsp;
        <a href="#"
           onclick="
                if(confirm('<?php echo $lang["clearcategoriesareyousure"]?>'))
                    {
                    jQuery('#tree_<?php echo $field['ref']; ?>').jstree(true).deselect_all();

                    <!-- remove the hidden inputs -->
                    var elements = document.getElementsByName('nodes_searched[<?php echo $field['ref']; ?>][]');
                    while(elements[0])
                        {
                        elements[0].parentNode.removeChild(elements[0]);
                        }
                    
                    UpdateResultCount();
                    }
                return false;"
        >&gt; <?php echo $lang['clearall']; ?></a>
    </div>
    <?php
    }

foreach($field['nodes'] as $node)
    {
    if(!in_array($node['ref'], $searched_nodes))
        {
        continue;
        }

    echo "<input id=\"nodes_searched_{$node['ref']}\" type=\"hidden\" name=\"nodes_searched[{$field['ref']}][]\" value=\"{$node['ref']}\">";
    }
    ?>
    <div id="tree_<?php echo $field['ref']; ?>" style="display: none;"></div>
    <script>
    jQuery('#tree_<?php echo $field["ref"]; ?>').jstree({
        'core' : {
            'data' : {
                    url  : '<?php echo $baseurl; ?>/pages/ajax/category_tree_lazy_load.php',
                    data : function(node) {
                        return {
                            ajax           : true,
                            node_ref       : node.id,
                            field          : <?php echo $field['ref']; ?>,
                            searched_nodes : <?php echo json_encode($searched_nodes); ?>
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
    jQuery('#tree_<?php echo $field["ref"]; ?>').on('changed.jstree', function (event, data)
        {
        // Add value to the hidden input array
        if(data.action == 'select_node')
            {
            document.getElementById('tree_<?php echo $field["ref"]; ?>').insertAdjacentHTML('beforeBegin', '<input id="nodes_searched_' + data.node.id + '" type="hidden" name="nodes_searched[<?php echo $field["ref"]; ?>][]" value="' + data.node.id + '">');
            }
        // Remove the value from the array
        else if(data.action == 'deselect_node')
            {
            document.getElementById('nodes_searched_' + data.node.id).remove();
            }

        UpdateResultCount();
        });
    </script>
</div>