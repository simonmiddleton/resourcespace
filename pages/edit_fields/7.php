<?php
/* -------- Category Tree ------------------- */ 
global $lang, $baseurl, $css_reload_key, $category_tree_show_status_window, $category_tree_open, $is_search;

if(!isset($is_search))
    {
    $is_search = false;
    }

$hidden_input_elements = '';
$status_box_elements   = '';

foreach($field['nodes'] as $node)
    {
    if(!in_array($node['ref'], $searched_nodes))
        {
        continue;
        }

    $hidden_input_elements .= "<input id=\"nodes_searched_{$node['ref']}\" type=\"hidden\" name=\"nodes_searched[{$field['ref']}][]\" value=\"{$node['ref']}\">";

    // Show previously searched options on the status box
    if(!(isset($treeonly) && true == $treeonly))
        {
        $status_box_elements .= "<span id=\"statusbox_option_{$node['ref']}\">{$node['name']}</span><br>";
        }
    }
?>
<div class="Fixed">
<?php
if(!(isset($treeonly) && true == $treeonly))
	{
	?>
    <div id="<?php echo $name?>_statusbox" class="CategoryBox" <?php if(!$category_tree_show_status_window) { ?>style="display:none;"<?php } ?>>
        <?php echo $status_box_elements; ?>
    </div>
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

echo $hidden_input_elements;
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
            // Add hidden input in order to do the search
            document.getElementById('tree_<?php echo $field["ref"]; ?>').insertAdjacentHTML('beforeBegin', '<input id="nodes_searched_' + data.node.id + '" type="hidden" name="nodes_searched[<?php echo $field["ref"]; ?>][]" value="' + data.node.id + '">');

            // Update status box with the selected option
            var status_option_element = document.getElementById('statusbox_option_' + data.node.id);
            if(status_option_element == null)
                {
                document.getElementById('nodes_searched[<?php echo $field['ref']; ?>]_statusbox').insertAdjacentHTML('beforeEnd', '<span id="statusbox_option_' + data.node.id + '">' + data.node.text + '</span><br>');
                }
            }
        // Remove the value from the array
        else if(data.action == 'deselect_node')
            {
            document.getElementById('nodes_searched_' + data.node.id).remove();
            jQuery('#statusbox_option_' + data.node.id).next('br').remove();
            document.getElementById('statusbox_option_' + data.node.id).remove();
            }

        UpdateResultCount();
        });
    </script>
</div>