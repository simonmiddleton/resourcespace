<?php
include '../../include/db.php';
include '../../include/authenticate.php';

if(!checkperm('k'))
    {
    header('HTTP/1.1 401 Unauthorized');
    die('Permission denied!');
    }

include_once '../../include/node_functions.php';


// Initialize
$ajax       = getvalescaped('ajax', '');
$action     = getvalescaped('action', '');

$field      = getvalescaped('field', '');
$field_data = get_field($field);

$node_ref   = getvalescaped('node_ref', '');
$nodes      = array();

// Array of nodes to expand immediately upon page load
$expand_nodes = getval("expand_nodes","");

$import_export_parent = getvalescaped('import_export_parent', null);

$filter_by_name = unescape(getvalescaped('filter_by_name', ''));

$chosencsslink ='<link type="text/css" rel="stylesheet" href="' . $baseurl_short . 'lib/chosen/chosen.min.css"></link>';
$chosenjslink = '<script type="text/javascript" src="' . $baseurl_short . 'lib/chosen/chosen.jquery.min.js"></script>';

if(!$ajax)
    {
    $headerinsert .= $chosencsslink;
    $headerinsert .= $chosenjslink;
    }

$new_node_record_form_action = '/pages/admin/admin_manage_field_options.php?field=' . $field;


// Process form requests
if('true' === $ajax && !(trim($node_ref)=="") && 0 < $node_ref)
    {
    $option_name     = trim(getvalescaped('option_name', ''));
    $option_parent   = getvalescaped('option_parent', '');
    $option_new_index = getvalescaped('node_order_by', '', true);
    if ($option_new_index != "")
        {
        $option_new_index -= 1;
        }
    $node_action     = getvalescaped('node_action', '');
    // [Save Option]
    if('save' === $node_action && enforcePostRequest($ajax))
        {
        $response['refresh_page'] = false;
        $node_ref_data            = array();

        if(trim($option_parent) != '' || (get_node($node_ref, $node_ref_data) && $node_ref_data['parent'] != $option_parent))
            {
            $response['refresh_page'] = true;
            }

        // Option order_by is not being sent because that can be asynchronously changed and we might not know about it,
        // thus this will be checked upon saving the data. If order_by is null / empty string, then we will use the current value
        set_node($node_ref, $field, $option_name, $option_parent, $option_new_index);

        // update value of corresponding fieldx
        update_fieldx($field); 
        
        echo json_encode($response);
        exit();
        }

    // [Move Option]
    if(('movedown' === $node_action || 'moveup' === $node_action || 'moveto' === $node_action) && enforcePostRequest($ajax))
        {
        $response['error']   = null;
        $response['sibling'] = null;
        $response['refresh_page']=false;

        $current_node     = array();
        if(!get_node($node_ref, $current_node))
            {
            $response['error'] = 'No node found!';
            exit(json_encode($response));
            }

        // Locate current node position within its siblings
        $siblings                    = get_nodes($field, $current_node['parent']);
        $current_node_siblings_index = array_search($node_ref, array_column($siblings, 'ref'));

        $pre_sibling      = 0;
        $post_sibling     = 0;
        if ($option_new_index > count($siblings))
            {
            $option_new_index = count($siblings);
            }

        $allow_reordering = false;
        $new_nodes_order  = array();
        $url_parameters = array('field' => $field, 'filter_by_name' => $filter_by_name);

        // Get pre & post siblings of current node
        // Note: these can be 0 if current node is either first/ last in the list
        if(1 < count($siblings) && isset($current_node_siblings_index))
            {
            if(isset($siblings[$current_node_siblings_index - 1]))
                {
                $pre_sibling = $siblings[$current_node_siblings_index - 1]['ref'];
                }

            if(isset($siblings[$current_node_siblings_index + 1]))
                {
                $post_sibling = $siblings[$current_node_siblings_index + 1]['ref'];
                }
            }

        // Create the new order for nodes based on direction
        switch($node_action)
            {
            case 'moveup':
                $response['sibling'] = $pre_sibling;
                move_array_element($siblings, $current_node_siblings_index, $current_node_siblings_index - 1);

                // This is the first node in the list so we can't reorder upwards
                if(0 < $pre_sibling)
                    {
                    $allow_reordering = true;
                    }
                break;
            
            case 'movedown':
                $response['sibling'] = $post_sibling;
                move_array_element($siblings, $current_node_siblings_index, $current_node_siblings_index + 1);

                // This is the last node in the list so we can't reorder downwards
                if(0 < $post_sibling)
                    {
                    $allow_reordering = true;
                    }
                break;
            case 'moveto':
                
                move_array_element($siblings,$current_node_siblings_index, $option_new_index); # issue here

                // This node is already in this position
                if($current_node_siblings_index != $option_new_index)
                    {
                    $allow_reordering = true;
                    $response['refresh_page']=true;

                    if($field_data['type'] != 7) // Not a category tree
                        {
                        $per_page    = (int) getvalescaped('per_page_list', $default_perpage_list, true);
                        $move_to_page_offset = floor($option_new_index/$per_page)*$per_page;
                        $url_parameters['offset'] = $move_to_page_offset;
                        }
                    }
                break;
            }

            $move_to_page_url = generateURL("{$baseurl_short}pages/admin/admin_manage_field_options.php",$url_parameters);
            $response['url']=$move_to_page_url;
        // Create the new array of nodes order
        foreach($siblings as $sibling)
            {
            $new_nodes_order[] = $sibling['ref'];
            }

        if($allow_reordering)
            {
            reorder_node($new_nodes_order);
            }

        echo json_encode($response);
        exit();
        }

    // [Delete Option]
    if('delete' === $node_action && enforcePostRequest($ajax))
        {
        delete_node($node_ref);
        }
    }

// [Toggle tree node]
if('true' === $ajax && 'true' === getval('draw_tree_node_table', '') && 7 == $field_data['type'])
    {
    $nodes         = get_nodes($field, $node_ref, FALSE, NULL, NULL, '', true);
    $nodes_counter = count($nodes);
    $i             = 0;
    $node_index    = 0;
    foreach($nodes as $node)
        {
        $last_node = false;
        $node_index ++;
        if(++$i === $nodes_counter)
            {
            $last_node = true;
            }
        draw_tree_node_table($node['ref'], $node['resource_type_field'], $node['name'], $node['parent'], $node['order_by'], $last_node, $node['use_count']);
        }
    exit();
    }

// [New Option]
$submit_new_option = getvalescaped('submit_new_option', '');
if('true' === $ajax && '' != trim($submit_new_option) && 'add_new' === $submit_new_option && enforcePostRequest($ajax))
    {
    $new_option_name     = trim(getval('new_option_name', ''));
    $new_option_parent   = getvalescaped('new_option_parent', '');
    $new_option_order_by = get_node_order_by($field, 7 == $field_data['type'], $new_option_parent);
    $new_node_index      = $new_option_order_by/10;

    $new_record_ref = set_node(NULL, $field, $new_option_name, $new_option_parent, $new_option_order_by);

    if(getval("reload","") == "")
        {
        if(isset($new_record_ref) && !(trim($new_record_ref)==""))
            {
            if(7 != $field_data['type'] && (trim($new_option_parent)==""))
                {
                ?>
                <tr id="node_<?php echo $new_record_ref; ?>">
                    <td>
                        <input type="text" class="stdwidth" name="option_name" form="option_<?php echo $new_record_ref; ?>" value="<?php echo htmlspecialchars($new_option_name); ?>" onblur="this.value=this.value.trim()" >
                    </td>
                    <td align="left">0</td>
                    
                        <div class="ListTools">
                            <form id="option_<?php echo $new_record_ref; ?>" method="post" action="/pages/admin/admin_manage_field_options.php?field=<?php echo $field; ?>">
                                <td>
                                    <input type="hidden" name="node_ref" value="<?php echo $new_record_ref; ?>">
                                    <input 
                                        type="number" 
                                        name="node_order_by" 
                                        value="<?php echo $new_node_index; ?>" 
                                        id="option_<?php echo $new_record_ref; ?>_order_by" 
                                        readonly='true'
                                        min='1'
                                    >
                                </td>
                            <?php
                            // Show order by tools if not filtering or using automatic ordering
                            if('' == $filter_by_name && !$field_data['automatic_nodes_ordering'])
                                {
                                    ?>
                                
                                <td> <!-- Buttons for changing order -->
                                    <button 
                                        type="button"
                                        id="option_<?php echo $new_record_ref; ?>_move_to"
                                        onclick="
                                            EnableMoveTo(<?php echo $new_record_ref; ?>);
                                            return false;
                                        ">
                                        <?php echo $lang['action-move-to']; ?>
                                    </button>
                                    <button 
                                        type="submit"
                                        id="option_<?php echo $new_record_ref; ?>_order_by_apply"
                                        onclick="
                                            ApplyMoveTo(<?php echo $new_record_ref; ?>);
                                            return false;
                                        "
                                        style="display: none;"
                                    >
                                    <?php echo $lang['action-title_apply']; ?>
                                    </button>
                                    <button type="submit" onclick="ReorderNode(<?php echo $new_record_ref; ?>, 'moveup'); return false;"><?php echo $lang['action-move-up']; ?></button>
                                    <button type="submit" onclick="ReorderNode(<?php echo $new_record_ref; ?>, 'movedown'); return false;"><?php echo $lang['action-move-down']; ?></button>
                                </td>
                                <?php
                                }
                                ?>
                            <td> <!-- Action buttons -->
                                <button type="submit" onclick="SaveNode(<?php echo $new_record_ref; ?>); return false;"><?php echo $lang['save']; ?></button>
                                <button type="submit" onclick="DeleteNode(<?php echo $new_record_ref; ?>); return false;"><?php echo $lang['action-delete']; ?></button>
                            </td>
                                
                            <?php generateFormToken("option_{$new_record_ref}"); ?>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php

                exit();
                }

            draw_tree_node_table($new_record_ref, $field, $new_option_name, $new_option_parent, $new_option_order_by);
            }

        exit();
        }
    }

// [Import nodes]
if('' !== getval('upload_import_nodes', '') && isset($_FILES['import_nodes']['tmp_name']) && is_uploaded_file($_FILES['import_nodes']['tmp_name']))
    {
    $error = false;
    $uploaded_file_pathinfo  = pathinfo($_FILES['import_nodes']['name']);
    $uploaded_file_extension = $uploaded_file_pathinfo['extension'];

    if(strtolower($uploaded_file_extension) != "txt")
        {
        $error = true;
        $onload_message= array("title" => $lang["error"],"text" => $lang["invalidextension_mustbe"] . " .txt");
        }

    $uploaded_tmp_filename   = $_FILES['import_nodes']['tmp_name'];

    // Get each line from file into an array
    $file_handle  = fopen($uploaded_tmp_filename, 'rb');
    if ($file_handle)
        {
        $import_nodes = array();
        while (($line = fgets($file_handle)) !== false)
            {
            if(trim($line) != "" &&  mb_check_encoding($line,'UTF-8'))
                {
                $import_nodes[] = trim($line);
                }
            }
        fclose($file_handle);
        }
    else
        {
        // error opening the file.
        $error = true;
        $onload_message= array("title" => $lang["error"],"text" => $lang["invalidextension_mustbe"] . " .txt");
        } 

    if(count($import_nodes) > 0 && !$error)
        {
        // Setup needed vars for this process
        $import_options = getval('import_options', '');
        $existing_nodes = get_nodes($field, $import_export_parent);

        // Phase 1 - add new nodes, without creating duplicates
        foreach($import_nodes as $import_node_name)
            {
            $existing_node_key = array_search($import_node_name, array_column($existing_nodes, 'name'));

            // Node doesn't exist so we can create it now.
            if(false === $existing_node_key)
                {
                set_node(null, $field, $import_node_name, $import_export_parent, '');

                log_activity("{$lang['import']} metadata field options - field {$field}", LOG_CODE_CREATED, $import_node_name, 'node', 'name');
                }
            }

        // Phase 2 - Remove any nodes that don't exist in the imported file
        // Note: only for "Replace options" option
        $reorder_required = false;
        foreach($existing_nodes as $existing_node)
            {
            if('replace_nodes' != $import_options)
                {
                break;
                }

            if(!in_array($existing_node['name'], $import_nodes))
                {
                delete_node($existing_node['ref']);

                log_activity("{$lang['import']} metadata field options - field {$field}", LOG_CODE_DELETED, null, 'node', 'name', $existing_node['ref'], null, $existing_node['name']);

                $reorder_required = true;
                }
            }

        if($reorder_required)
            {
            $new_nodes_order = array();

            foreach(get_nodes($field, $import_export_parent) as $node)
                {
                $new_nodes_order[] = $node['ref'];
                }

            reorder_node($new_nodes_order);
            }
        }
    }

// [Export nodes]
if('true' === $ajax && 'export' === $action)
    {
    include_once '../../include/csv_export_functions.php';

    generateNodesExport($field_data, $import_export_parent, true);

    exit();
    }

// [Paging functionality]
$url         = generateURL("{$baseurl_short}pages/admin/admin_manage_field_options.php",
                        array(
                            'field'          => $field,
                            'filter_by_name' => $filter_by_name
                        )
                    );
$offset      = (int) getvalescaped('offset', 0, true);
$per_page    = (int) getvalescaped('per_page_list', $default_perpage_list, true);
$count_nodes = get_nodes_count($field, $filter_by_name);
$totalpages  = ceil($count_nodes / $per_page);
$curpage     = floor($offset / $per_page) + 1;
$jumpcount   = 0;

// URL used for redirect on JS - AddNode() when used in combination with the pager in order to add the node at the end of
// the options list
$last_page_offset = (($totalpages - 1) * $per_page);
if($last_page_offset < 0)
    {
    $last_page_offset = 0;
    }

$last_page_url    = generateURL(
    "{$baseurl_short}pages/admin/admin_manage_field_options.php",
    array(
        'field'          => $field,
        'filter_by_name' => $filter_by_name,
        'go'             => 'page',
        'offset'         => $last_page_offset
    )
);

if($offset > $count_nodes || $offset < 0)
    {
    $offset = 0;
    }

include '../../include/header.php';

if($ajax)
    {
    echo $chosencsslink;
    echo $chosenjslink;
    }
    ?>
<div class="BasicsBox">
<?php
    $links_trail = array(
        array(
            'title' => $lang["systemsetup"],
            'href'  => $baseurl_short . "pages/admin/admin_home.php"
        ),
        array(
            'title' => $lang["admin_resource_type_fields"],
            'href'  => $baseurl_short . "pages/admin/admin_resource_type_fields.php"
        ),
        array(
            'title' => $lang["admin_resource_type_field"] . ": " . i18n_get_translated($field_data["title"]),
            'href'  => $baseurl_short . "pages/admin/admin_resource_type_field_edit.php?ref=" . $field
        ),
        array(
            'title' => $lang['manage_metadata_field_options'] . (isset($field_data['title']) ? ': ' . i18n_get_translated($field_data['title']) : '')
        )
    );

    renderBreadcrumbs($links_trail);
?>

    <p><?php echo $lang['manage_metadata_text']; render_help_link("resourceadmin/modifying-field-options");?></p>
    <?php
    if(in_array($field, $default_to_first_node_for_fields))
        {
        ?>
        <p><?php echo $lang["metadata_first_option_is_default"]; ?>
        <p>
            <a href="<?php echo $baseurl; ?>/pages/tools/update_empty_field_with_default.php?field=<?php echo $field?>" onClick="CentralSpaceLoad(this,true);"><?php echo $lang['metadata_populate_default_node_for_empty_values']; ?></a>
        </p>
        <?php
        }
        ?>
    <div id="AdminManageMetadataFieldOptions" class="ListView">
    <?php
    if(7 != $field_data['type'])
        {
        ?>
        <form id="FilterNodeOptions" class="FormFilter" method="GET" action="<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php">
            <input type="hidden" name="field" value="<?php echo htmlspecialchars($field); ?>">
            <fieldset>
                <legend><?php echo $lang['filter_label']; ?></legend>
                <div class="FilterItemContainer">
                    <label><?php echo $lang['name']; ?></label>
                    <input type="text" name="filter_by_name" value="<?php echo htmlspecialchars($filter_by_name); ?>">
                </div>
                <button type="submit"><?php echo $lang['filterbutton']; ?></button>
                <button class="ClearButton" type="submit" onCLick="ClearFilterForm('FilterNodeOptions'); return false;"><?php echo $lang['clearbutton']; ?></button>
            </fieldset>
        </form>
        <!-- Pager -->
        <div class="TopInpageNavRight">
        <?php
        pager(true,false);
        $draw_pager = true;
        ?>
        </div>
        <div class="clearerleft"></div>
        <?php
        }
        ?>
        <div class="Listview">
        <table class="ListviewStyle" border="0" cellspacing="0" cellpadding="5">
        <?php
        // When editing a category tree we won't show the table headers since the data
        // will move to the right every time we go one level deep
        if(7 != $field_data['type'])
            {
            ?>
            <thead>
                <tr class="ListviewTitleStyle">
                    <td><?php echo $lang['name']; ?></td>
                    <td><?php echo $lang['resources']; ?></td>
                    <td><?php echo $lang['property-order_by']; ?></td>
                    <td><?php echo $lang['actions']; ?></td>
                    <td> </td>
                    <td> </td>
                </tr>
            </thead>
            <tbody>
        <?php
        // Render existing nodes
        $nodes = get_nodes($field, null, false, $offset, $per_page, $filter_by_name, true, (bool)$field_data['automatic_nodes_ordering']);

        if(0 == count($nodes))
            {
            $fieldinfo = get_resource_type_field($field);

            migrate_resource_type_field_check($fieldinfo);

            $nodes = get_nodes($field, null, false, $offset, $per_page, $filter_by_name, true);
            }

        $node_index=getvalescaped('offset',0,true);
        foreach($nodes as $node)
            {
            check_node_indexed($node, $field_data['partial_index']);
            $node_index +=1;
            ?>
            <tr id="node_<?php echo $node['ref']; ?>">
                <td>
                    <input type="text" class="stdwidth" name="option_name" form="option_<?php echo $node['ref']; ?>" value="<?php echo htmlspecialchars($node['name']); ?>" onblur="this.value=this.value.trim()" >
                </td>
                <td align="left">
                    <?php echo $node['use_count'] ?>
                </td>

                    <div class="ListTools">
                        <form id="option_<?php echo $node['ref']; ?>" method="post" action="<?php echo $baseurl_short ?>pages/admin/admin_manage_field_options.php?field=<?php echo $field; ?>">
                            <td>
                                <input type="hidden" name="node_ref" value="<?php echo $node['ref']; ?>">
                                <input 
                                    type="number"
                                    name="node_order_by" 
                                    value="<?php echo $node_index; ?>" 
                                    id="option_<?php echo $node['ref']; ?>_order_by" 
                                    readonly='true'
                                    min='1'
                                    class="TableOrderBy"
                                >
                            </td>
                        <?php
                        // Show order by tools if not filtering or using automatic ordering
                        if('' == $filter_by_name && !$field_data['automatic_nodes_ordering'])
                            {
                                ?>
                            
                            <td> <!-- Buttons for changing order -->
                                <button 
                                    type="button"
                                    id="option_<?php echo $node['ref']; ?>_move_to"
                                    onclick="
                                        EnableMoveTo(<?php echo $node['ref']; ?>);
                                        
                                        return false;
                                    ">
                                    <?php echo $lang['action-move-to']; ?>
                                </button>
                                <button 
                                    type="submit"
                                    id="option_<?php echo $node['ref']; ?>_order_by_apply"
                                    onclick="
                                        ApplyMoveTo(<?php echo $node['ref']; ?>);
                                        return false;
                                    "
                                    style="display: none;"
                                >
                                <?php echo $lang['action-title_apply']; ?>
                                </button>
                                <button type="submit" onclick="ReorderNode(<?php echo $node['ref']; ?>, 'moveup'); return false;"><?php echo $lang['action-move-up']; ?></button>
                                <button type="submit" onclick="ReorderNode(<?php echo $node['ref']; ?>, 'movedown'); return false;"><?php echo $lang['action-move-down']; ?></button>
                                </td>
                            <?php
                            }
                            ?>
                        <!-- Action buttons -->
                        <td>
                            <button type="submit" onclick="SaveNode(<?php echo $node['ref']; ?>); return false;"><?php echo $lang['save']; ?></button>
                            <button type="submit" onclick="DeleteNode(<?php echo $node['ref']; ?>); return false;"><?php echo $lang['action-delete']; ?></button>
                        </td>
                            
                        <?php generateFormToken("option_{$node['ref']}"); ?>
                        </form>
                    </div>
                </td>
            </tr>
            
            <?php
            }
        render_new_node_record($new_node_record_form_action, FALSE);
        ?>
            </tbody>
            <?php
            }
            ?>
        </table>
        </div>
    <?php
    if(7 != $field_data['type'])
        {
        ?>
        <div class="BottomInpageNav">
            <div class="BottomInpageNavRight">  
            <?php 
            if(isset($draw_pager))
                {
                pager(false,false);
                } 
                ?>
            </div>
            <div class="clearerleft"></div>
        </div>
        <?php
        }
        ?>
    </div><!-- end of ListView -->

<?php
// Category trees
$tree_nodes = get_nodes($field,null,false,null,null,'',true,'',true);
if($field_data['type'] == 7 && !($tree_nodes==""))
    {
    $nodes_counter = count($tree_nodes);
    $i             = 0;
    $node_index    = 0;

    foreach($tree_nodes as $node)
        {
        check_node_indexed($node, $field_data['partial_index']);
        $node_index++;

        $last_node = false;
        if(++$i === $nodes_counter)
            {
            $last_node = true;
            }

        draw_tree_node_table($node['ref'], $node['resource_type_field'], $node['name'], $node['parent'], $node['order_by'], $last_node, $node['use_count']);
        }
    }

// Render a new node record form when we don't have any node set in the database
if($field_data['type'] == 7 && !$tree_nodes)
    {
    render_new_node_record($new_node_record_form_action, TRUE);
    ?>
    <script>
    jQuery('.node_parent_chosen_selector').chosen({});
    </script>
    <?php
    }
?>
</div><!-- end of BasicBox -->
<script>
function AddNode(parent)
    {
    var new_node_children     = jQuery('#new_node_' + parent + '_children');
    var new_option_name       = new_node_children.find('input[name=new_option_name]');
    var new_option_parent     = new_node_children.find('select[name=new_option_parent]');
    var new_option_parent_val = new_option_parent.val();

    if(typeof new_option_parent_val === 'undefined' || new_option_parent_val===null || new_option_parent_val == '')
        {
        new_option_parent_val = 0;
        }

    var new_node_parent_children = jQuery('#new_node_' + new_option_parent_val + '_children');
    var node_parent_children     = jQuery('#node_' + new_option_parent_val + '_children');

    var post_url  = '<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php';
    var post_data = 
        {
        ajax: true,
        field: <?php echo $field; ?>,
        submit_new_option: 'add_new',
        new_option_name: new_option_name.val(),
        new_option_parent: new_option_parent.val(),
        <?php echo generateAjaxToken("AddNode"); ?>
        };

    jQuery.post(post_url, post_data, function(response)
        {
        if(typeof response !== 'undefined')
            {
            // Add new node and reset to default the values for a new record
            // If there are no children in the node append for now
            if(new_node_parent_children.length == 0)
                {
                node_parent_children.append(response);

                // Mark node as parent on the UI
                jQuery('#node_' + new_option_parent_val).data('toggleNodeMode', 'ex');
                jQuery('#node_' + new_option_parent_val + '_toggle_button').attr('src', '<?php echo $baseurl_short; ?>gfx/interface/node_ex.gif');
                jQuery('#node_' + new_option_parent_val + '_toggle_button').attr('onclick', 'ToggleTreeNode(' + new_option_parent_val + ', <?php echo $field; ?>);');
                }
            else
                {
                <?php
                // When adding new options for non category tree fields AND we are not on the last page of the pager,
                // then redirect to the last page
                if($last_page_offset != $offset)
                    {
                    ?>
                    CentralSpaceLoad('<?php echo $last_page_url; ?>');
                    return;
                    <?php
                    }
                    ?>

                new_node_parent_children.before(response);
                }

            initial_new_option_name = new_option_name.val();

            new_option_name.val('');
            new_option_parent.val(parent);

            jQuery('.node_parent_chosen_selector').chosen({});

        <?php
        if(FIELD_TYPE_CATEGORY_TREE == $field_data['type'])
            {
            ?>
            // Reload parent selectors to contain the new value
            new_option_response_element = jQuery(jQuery.parseHTML(response)).filter("table[id^='node_']");
            jQuery('#CentralSpace').trigger('reloadParentSelectors', [new_option_response_element[0].id.substring(5), initial_new_option_name]);
            <?php
            }
            ?>
            }
        });
    }

function SaveNode(ref)
    {
    var node          = jQuery('#node_' + ref);
    var node_children = jQuery('#node_' + ref + '_children');
    var option_name   = node.find('input[name=option_name]').val();
    var option_parent = node.find('select[name=option_parent]').val();

    var post_url  = '<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php';
    var post_data = 
        {
        ajax: true,
        field: <?php echo $field; ?>,
        node_ref: ref,
        node_action: 'save',
        option_name: option_name,
        option_parent: option_parent,
        <?php echo generateAjaxToken("SaveNode"); ?>
        };

    jQuery.post(post_url, post_data, function(response)
        {
        if(typeof response.refresh_page !== 'undefined' && response.refresh_page === true)
            {
            location.reload();
            }
        }, 'json');

    }

function DeleteNode(ref)
    {
    var confirmation = confirm('Are you sure you wish to DELETE this field option?');
    if(!confirmation)
        {
        return false;
        }

    var post_url  = '<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php';
    var post_data = 
        {
        ajax: true,
        field: <?php echo $field; ?>,
        node_ref: ref,
        node_action: 'delete',
        <?php echo generateAjaxToken("DeleteNode"); ?>
        };

    jQuery.post(post_url, post_data);
    jQuery('#node_' + ref).remove();
    jQuery('#node_' + ref + '_children').remove();

    <?php
    if(FIELD_TYPE_CATEGORY_TREE == $field_data['type'])
        {
        ?>
        jQuery('#CentralSpace').trigger('reloadParentSelectors', ref);
        <?php
        }
        ?>

    return true;
    }

function ReorderNode(ref, direction, move_to)
    {
    var node          = jQuery('#node_' + ref);
    var node_children = jQuery('#node_' + ref + '_children');

    var post_url  = '<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php';
    var post_data =
        {
        ajax: true,
        field: <?php echo $field; ?>,
        node_ref: ref,
        node_action: direction,
        node_order_by: move_to,
        <?php echo generateAjaxToken("ReorderNode"); ?>
        };

    jQuery.post(post_url, post_data, function(response)
        {
        if(direction == 'moveup' && response.sibling && response.sibling.length > 0)
            {
            node.insertBefore('#node_' + response.sibling);
            node_children.insertBefore('#node_' + response.sibling);
            document.getElementById('option_' + ref + '_order_by').value --;
            document.getElementById('option_' + response.sibling + '_order_by').value ++;
            }

        else if(direction == 'movedown' && response.sibling && response.sibling.length > 0)
            {
            node.insertAfter('#node_' + response.sibling);
            node_children.insertAfter('#node_' + response.sibling);
            document.getElementById('option_' + ref + '_order_by').value ++;
            document.getElementById('option_' + response.sibling + '_order_by').value --;
            }

        else if(response.refresh_page=true)
            {
            CentralSpaceLoad(response.url,true);
            }
        }, 'json');
    }

function ToggleTreeNode(ref, field_ref)
    {
    var node_children    = jQuery('#node_' + ref + '_children');
    var table_node       = jQuery('#node_' +ref);
    var toggle_node_mode = jQuery(table_node).data('toggleNodeMode');
    var toggle_button    = jQuery('#node_' + ref + '_toggle_button');

    var post_url  = '<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php';
    var post_data = 
        {
        ajax: true,
        field: field_ref,
        node_ref: ref,
        draw_tree_node_table: true,
        <?php echo generateAjaxToken("ToggleTreeNode"); ?>
        };

    // Hide expanded children
    if('ex' === toggle_node_mode && '' !== node_children.html())
        {
        node_children.hide();
        jQuery(table_node).data('toggleNodeMode', 'unex');
        jQuery(toggle_button).attr('src', '<?php echo $baseurl_short; ?>gfx/interface/node_unex.gif');

        return true;
        }

    // Show parent children
    if('unex' === toggle_node_mode && '' !== node_children.html())
        {
        node_children.show();
        jQuery(table_node).data('toggleNodeMode', 'ex');
        jQuery(toggle_button).attr('src', '<?php echo $baseurl_short; ?>gfx/interface/node_ex.gif');

        return true;
        }

    jQuery.post(post_url, post_data, function(response)
        {
        if(typeof response !== 'undefined')
            {
            node_children.html(response);
            jQuery('.node_parent_chosen_selector').chosen({});

            jQuery(table_node).data('toggleNodeMode', 'ex');
            jQuery(toggle_button).attr('src', '<?php echo $baseurl_short; ?>gfx/interface/node_ex.gif');
            }
        });

    return true;
    }

function EnableMoveTo(ref)
    {
    // Set order by field to be writeable, show apply button and hide move to button
    document.getElementById('option_' + ref + '_order_by').readOnly = false;
    document.getElementById('option_' + ref + '_order_by_apply').style.display='inline';
    document.getElementById('option_' + ref + '_move_to').style.display='none';
    }

function ApplyMoveTo(ref)
    {
    // Use the value in the order by field to move this node to that position.
    var moveto = document.getElementById('option_' + ref + '_order_by').value;
    if (moveto < 1)
    {
        moveto = 1;
    }
    ReorderNode(ref, 'moveto', moveto);
    document.getElementById('option_' + ref + '_order_by').readOnly = true;
    document.getElementById('option_' + ref + '_move_to').style.display='inline';
    document.getElementById('option_' + ref + '_order_by_apply').style.display='none';
    }

function ClearFilterForm(filter_form_id)
    {
    var input_elements = jQuery("#" + filter_form_id + " input[type=text]");

    for(var index = input_elements.length - 1; index >= 0; index--)
        {
        input_elements[index].value = '';
        }

    document.getElementById(filter_form_id).submit();
    }

jQuery('.node_parent_chosen_selector').chosen({});

<?php
if(FIELD_TYPE_CATEGORY_TREE == $field_data['type'])
    {
    ?>
    // Update all parent selectors so that all of them have the same nodes as valid options
    jQuery('#CentralSpace').on('reloadParentSelectors', function (e, node_id, node_name)
        {
        if(typeof node_id === 'undefined')
            {
            return false;
            }

        var delete_option_name = false;
        if(typeof node_name === 'undefined')
            {
            // this means the node_ref came from a delete action so remove option from select
            delete_option_name = true;
            }

        jQuery('.node_parent_chosen_selector').each(function (index, element)
            {
            if(delete_option_name)
                {
                jQuery(element).find("option[value='" + node_id + "']").remove();
                return true;
                }

            // Example of ID from jQuery(element)[0].id
            // node_option_413_parent_select
            current_node_id = jQuery(element)[0].id.substring(12).substring(0, jQuery(element)[0].id.indexOf('_') - 1);

            if(current_node_id != '' && current_node_id == node_id)
                {
                return true;
                }

            jQuery(element).append('<option value="' + node_id + '">' + node_name + '</option>');
            });

        jQuery('.node_parent_chosen_selector').trigger("chosen:updated");
        });
    <?php
    
    if($expand_nodes != "")
        {
        echo "jQuery(document).ready(function(){";
        $toexpand = explode(",",$expand_nodes);
        foreach($toexpand as $node)
            {
            echo "ToggleTreeNode('" . (int)$node . "','" . (int)$field . "');";
            }
        echo "});";
        }
    //
    }
    ?>
</script>

<div class="BasicsBox">
    <h3><?php echo $lang['import_export']; ?></h3>

    <?php 
    // Select a parent node to import for
    if(7 == $field_data['type'])
        {
        $import_export_parent_nodes = array('' => '');
        foreach(get_nodes($field, null, true) as $import_export_parent_node)
            {
            $import_export_parent_nodes[$import_export_parent_node['ref']] = $import_export_parent_node['name'];
            }

        render_dropdown_question(
            $lang['property-parent'],
            'import_export_parent',
            $import_export_parent_nodes,
            '',
            'form="import_nodes_form"'
        );
        }

    render_dropdown_question(
        $lang['manage_metadata_field_options_import_options'],
        'import_options',
        array(
            'append_nodes'  => $lang['appendtext'],
            'replace_nodes' => $lang['replacealltext']
        ),
        '',
        'form="import_nodes_form"'
    );
    ?>

    <div class="Question">
        <form id="import_nodes_form" method="POST" action="<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php?field=<?php echo $field; ?>" enctype="multipart/form-data">
            <label for="import_nodes"><?php echo $lang['import']; ?></label>
            <?php generateFormToken("import_nodes_form"); ?>
            <input type="file" name="import_nodes">
            <input type="submit" name="upload_import_nodes" value="<?php echo $lang['import']; ?>">
        </form>
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang['export']; ?></label>
        <button type="submit" onclick="ExportNodes();"><?php echo $lang['export']; ?></button>
        <script>
        function ExportNodes()
            {
            var import_export_parent = jQuery('#import_export_parent').val();
            if(typeof import_export_parent === 'undefined')
                {
                import_export_parent = '';
                }

            window.location.href = '<?php echo $baseurl; ?>/pages/admin/admin_manage_field_options.php?ajax=true&field=<?php echo $field; ?>&action=export&import_export_parent=' + import_export_parent;

            return false;
            }
        </script>
        <div class="clearerleft"></div>
    </div>
</div> <!-- end of BasicBox -->
<?php
include '../../include/footer.php';
