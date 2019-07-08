<?php
// called by node_field_options_override function to migrate comma separated options to nodes
include_once 'migration_functions.php';


/**
* Set node - Used for both creating and saving a node in the database.
* Use NULL for ref if you just want to insert a new record.
*
* @param  integer  $ref                   ID of the node. To insert new record ID should be NULL
* @param  integer  $resource_type_field   ID of the metadata field
* @param  string   $name                  Node name to be used (international)
* @param  integer  $parent                ID of the parent of this node (null for non trees)
* @param  integer  $order_by              Value of the order in the list (e.g. 10)
* @param  boolean  $returnexisting        Return an existing node if a match is found for this field. Duplicate nodes may be required for category trees but are not desirable for non-fixed list fields
*
* @return boolean|integer
*/
function set_node($ref, $resource_type_field, $name, $parent, $order_by,$returnexisting=false)
    {
    if(!is_null($name))
        {
        $name = trim($name);
        }

    if (is_null($resource_type_field) || '' == $resource_type_field || is_null($name) || '' == $name)
        {
        return false;
        }

    if(is_null($ref) && '' == $order_by)
        {
        $order_by = get_node_order_by($resource_type_field, (is_null($parent) || '' == $parent), $parent);
        }

    $query = sprintf("INSERT INTO `node` (`resource_type_field`, `name`, `parent`, `order_by`) VALUES ('%s', '%s', %s, '%s')",
        escape_check($resource_type_field),
        escape_check($name),
        ('' == trim($parent) ? 'NULL' : "'" . escape_check($parent) . "'"),
        escape_check($order_by)
    );

    // Check if we only need to save the record
    $current_node = array();
    if(get_node($ref, $current_node))
        {
        // If nothing has changed, just return true, otherwise continue and update record
        if($resource_type_field === $current_node['resource_type_field'] &&
            $name === $current_node['name'] &&
            $parent === $current_node['parent'] &&
            $order_by === $current_node['order_by']
            )
            {
            return $ref;
            }

        // When changing parent we need to make sure order by is changed as well
        // to reflect the fact that the node has just been added (ie. at the end of the list)
        if($parent !== $current_node['parent'])
            {
            $order_by = get_node_order_by($resource_type_field, true, $parent);
            }

        // Order by can be changed asynchronously, so when we save a node we can pass null or an empty
        // order_by value and this will mean we can use the current order
        if(!is_null($ref) && '' == $order_by)
            {
            $order_by = $current_node['order_by'];
            }

        $query = sprintf("
                UPDATE node
                   SET resource_type_field = '%s',
                       `name` = '%s',
                       parent = %s,
                       order_by = '%s'
                 WHERE ref = '%s'
            ",
            escape_check($resource_type_field),
            escape_check($name),
            (trim($parent)=="" ? 'NULL' : '\'' . escape_check($parent) . '\''),
            escape_check($order_by),
            escape_check($ref)
        );

        // Handle node indexing for existing nodes
        remove_node_keyword_mappings(array('ref' => $current_node['ref'], 'resource_type_field' => $current_node['resource_type_field'], 'name' => $current_node['name']), NULL);
        add_node_keyword_mappings(array('ref' => $ref, 'resource_type_field' => $resource_type_field, 'name' => $name), NULL);
        }

    if($returnexisting)
        {
        // Check for an existing match
        $existingnode=sql_value("SELECT ref value FROM node WHERE resource_type_field ='" . escape_check($resource_type_field) . "' AND name ='" . escape_check($name) . "'",0);
        if($existingnode > 0)
            {return $existingnode;}
        }

    sql_query($query);
    $new_ref = sql_insert_id();
    if ($new_ref == 0 || $new_ref === false)
        {
        if ($ref == null)
            {
            return sql_value("SELECT `ref` AS 'value' FROM `node` WHERE `resource_type_field`='" . escape_check($resource_type_field) . "' AND `name`='" . escape_check($name) . "'",0);
            }
        else
            {
            return $ref;
            }
        }
    else
        {
        log_activity("Set metadata field option for field {$resource_type_field}", LOG_CODE_CREATED, $name, 'node', 'name');

        // Handle node indexing for new nodes
        add_node_keyword_mappings(array('ref' => $new_ref, 'resource_type_field' => $resource_type_field, 'name' => $name), NULL);

        return $new_ref;
        }

    }


/**
* Delete node
*
* @param  integer  $ref  ID of the node
*
* @return void
*/
function delete_node($ref)
    {
    // TODO: if node is parent then don't delete it for now
    if(is_parent_node($ref))
        {
        return;
        }

    $query = "DELETE FROM node WHERE ref = '" . escape_check($ref) . "';";
    sql_query($query);

    remove_all_node_keyword_mappings($ref);

    return;
    }


/**
* Delete all nodes for a resource type field
*
* @param  integer  $resource_type_field  ID of the resource type field
*
* @return void
*/
function delete_nodes_for_resource_type_field($ref)
    {
    if(is_null($ref) || '' === trim($ref) || 0 === $ref)
        {
        trigger_error('$ref must be an integer greater than 0');
        }

    sql_query("DELETE FROM node WHERE resource_type_field = '" . escape_check($ref) . "';");

    return;
    }


/**
* Get a specific node by ref
* 
* @param  integer  $ref              ID of the node
* @param  array    $returned_node    If a value does exist it will be returned through
*                                    this parameter which is passed by reference
* @return boolean
*/
function get_node($ref, array &$returned_node)
    {
    if(is_null($ref) || (trim($ref)=="") || 0 >= $ref)
        {
        return false;
        }

    $query = "SELECT * FROM node WHERE ref = '" . escape_check($ref) . "';";
    $node  = sql_query($query);

    if(count($node)==0)
        {
        return false;
        }

    $returned_node = $node[0];

    return true;
    }


/**
* Get all nodes from database for a specific metadata field or parent.
* Use $parent = NULL and recursive = TRUE to get all nodes for a field
* 
* Use $offset and $rows only when returning a subset.
* 
* @param  integer  $resource_type_field         ID of the metadata field
* @param  integer  $parent                      ID of parent node
* @param  boolean  $recursive                   Set to true to get children nodes as well.
*                                               IMPORTANT: this should be used only with category trees
* @param  integer  $offset                      Specifies the offset of the first row to return
* @param  integer  $rows                        Specifies the maximum number of rows to return
* @param  string   $name                        Filter by name of node
* @param  boolean  $use_count                   Show how many resources use a particular node in the node properties
* @param  boolean  $order_by_translated_name    Flag to order by translated names rather then the order_by column
* 
* @return array
*/
function get_nodes($resource_type_field, $parent = NULL, $recursive = FALSE, $offset = NULL, $rows = NULL, $name = '', 
    $use_count = false, $order_by_translated_name = false)
    {
    global $language,$defaultlanguage;
    $asdefaultlanguage=$defaultlanguage;

    if (!isset($asdefaultlanguage))
        $asdefaultlanguage='en';

    // Use langauge specified if not use default
    isset($language)?$language_in_use = $language:$language_in_use = $defaultlanguage;

    $return_nodes = array();

    // Check if limiting is required
    $limit = '';

    if(!is_null($offset) && is_int($offset))
        {
        $limit = "LIMIT {$offset}";
        }

    if('' != $limit && !is_null($rows) && is_int($rows))
        {
        $limit .= ",{$rows}";
        }

    // Filter by name if required
    $filter_by_name = '';
    if('' != $name)
        {
        $filter_by_name = " AND `name` LIKE '%" . escape_check($name) . "%'";
        }

    // Option to include a usage count alongside each node
    $use_count_sql="";
    if($use_count)
        {
        $use_count_sql = ",(SELECT count(resource) FROM resource_node WHERE resource_node.resource > 0 AND resource_node.node = node.ref) AS use_count";
        }

    // Order by translated_name or order_by based on flag
    $order_by = $order_by_translated_name ? "translated_name" : "order_by";
    
    // Get length of language string + 2 (for ~ and :) for usuage in SQL below
    $language_string_length = (strlen($language_in_use) + 2);

    $parent_sql = (trim($parent)=="") ? "parent IS NULL" : "parent = '" . escape_check($parent) . "'";

    $query = "
        SELECT 
            *,
            CASE
                WHEN
                    POSITION('~" . $language_in_use . "' IN name) > 0
                THEN
                    TRIM(SUBSTRING(name,
                            POSITION('~" . $language_in_use . ":' IN name) + " . $language_string_length . ",
                            CASE
                                WHEN
                                    POSITION('~' IN SUBSTRING(name,
                                            POSITION('~" . $language_in_use . ":' IN name) + " . $language_string_length . ",
                                            LENGTH(name) - 1)) > 0
                                THEN
                                    POSITION('~' IN SUBSTRING(name,
                                            POSITION('~" . $language_in_use . ":' IN name) + " . $language_string_length . ",
                                            LENGTH(name) - 1)) - 1
                                ELSE LENGTH(name)
                            END))
                ELSE TRIM(name)
            END AS translated_name
            " . $use_count_sql . "
        FROM node 
        WHERE resource_type_field = " . escape_check($resource_type_field) . "
        " . $filter_by_name . "
        AND " . $parent_sql . "
        ORDER BY " . $order_by . " ASC
        " . $limit;

    $nodes = sql_query($query);

    foreach($nodes as $node)
        {
        array_push($return_nodes, $node);

        if($recursive)
            {
            foreach(get_nodes($resource_type_field, $node['ref'], TRUE) as $sub_node)
                {
                array_push($return_nodes, $sub_node);
                }
            }
        }
    return $return_nodes;
    }


/**
* Checks whether a node is parent to other nodes or not
*
* @param  integer    $ref    Node ref
*
* @return boolean
*/
function is_parent_node($ref)
    {
    if(is_null($ref))
        {
        return false;
        }

    $query = "SELECT exists (SELECT ref from node WHERE parent = '" . escape_check($ref) . "') AS value;";
    $parent_exists = sql_value($query, 0);

    if($parent_exists > 0)
        {
        return true;
        }

    return false;
    }


/**
* Determine how many level deep a node is. Useful for knowing how much
* indent a node
*
* @param  integer    $ref    Node ref
*
* @return integer            The depth value of a tree node
*/
function get_tree_node_level($ref)
    {
    if(!isset($ref))
        {
        trigger_error('Node ID should be set AND NOT NULL');
        }

    $parent      = escape_check($ref);
    $depth_level = -1;

    do
        {
        $query  = "SELECT parent AS value FROM node WHERE ref = '" . $parent . "';";
        $parent = sql_value($query, 0);

        $depth_level++;
        }
    while('' != trim($parent) && $parent!=0);

    return $depth_level;
    }


/**
* Find node ID of the root parent when searching by one
* of the leaves ID
* Example:
* 1
* 2
* 2.1
* 2.2
* 2.2.1
* 2.2.2
* 2.2.3
* 2.3
* 3
* Searching by "2.2.1" ID will give us the ID of node "2"
* 
* @param integer $ref   Node ID of tree leaf
* @param integer $level Node depth level (as returned by get_tree_node_level())
* 
* @return integer|boolean
*/
function get_root_node_by_leaf($ref, $level)
    {
    $ref   = escape_check($ref);
    $level = escape_check($level);

    if(!is_numeric($level) && 0 >= $level)
        {
        return false;
        }

    $query = "SELECT n0.ref AS `value` FROM node AS n{$level}";

    $from_level = $level;
    $level--;

    while(0 <= $level)
        {
        $query .= " LEFT JOIN node AS n{$level} ON n" . ($level + 1) . ".parent = n{$level}.ref";

        if(0 === $level)
            {
            $query .= " WHERE n{$from_level}.ref = '{$ref}'";
            }

        $level--;
        }
        
    $root_node = sql_value($query, '');

    if('' == $root_node)
        {
        $root_node = 0;
        }

    return (int) $root_node;
    }


/**
* Function used to reorder nodes based on an array with nodes in the new order
*
* @param  array  $nodes_new_order  Array of nodes 
*
* @return void
*/
function reorder_node(array $nodes_new_order)
    {
    if(0 === count($nodes_new_order))
        {
        trigger_error('$nodes_new_order cannot be an empty array!');
        }

    $order_by = 10;

    $query = 'UPDATE node SET order_by = (CASE ref ';
    foreach($nodes_new_order as $node_ref)
        {
        $query    .= 'WHEN \'' . $node_ref . '\' THEN \'' . $order_by . '\' ';
        $order_by += 10;
        }
    $query .= 'ELSE order_by END);';

    sql_query($query);

    return;
    }

/**
* Virtually re-order nodes
* 
* Temporarily re-order nodes (mostly) for display purposes
* 
* 
* @param array $unordered_nodes Original nodes array
* 
* @return array
*/
function reorder_nodes(array $unordered_nodes)
    {
    // Put $auto_order_checkbox and $auto_order_checkbox_case_insensitive as global 
    // to future proof function for when we will drop globals for easier refactoring
    global $auto_order_checkbox, $auto_order_checkbox_case_insensitive;

    $reordered_options = array();
    $use_index_key     = array();
    foreach($unordered_nodes as $unordered_node_index => $node)
        {
        $reordered_options[$node['ref']] = i18n_get_translated($node['name']);
        $use_index_key[$node['ref']]     = ($unordered_node_index == $node['ref']);
        }

    if(isset($auto_order_checkbox) && $auto_order_checkbox && $auto_order_checkbox_case_insensitive)
        {
        natcasesort($reordered_options);
        }
    else
        {
        natsort($reordered_options);
        }

    $reordered_nodes = array();
    foreach($reordered_options as $reordered_node_id => $reordered_node_option)
        {
        if(!$use_index_key[$reordered_node_id])
            {
            $reordered_nodes[$reordered_node_id] = $unordered_nodes[array_search($reordered_node_id, array_column($unordered_nodes, 'ref'))];
            }
        else
            {
            $reordered_nodes[$reordered_node_id] = $unordered_nodes[array_search($reordered_node_id, array_column($unordered_nodes, 'ref', 'ref'))];
            }
        }

    return $reordered_nodes;
    }


/**
* Renders HTML for adding a new node record in the database
*
* @param  string   $form_action          Set the action path of the form
* @param  boolean  $is_tree              Set to TRUE if the field is category tree type
* @param  integer  $parent               ID of the parent of this node
* @param  integer  $node_depth_level     When rendering for trees, we need to know how many levels deep we need to render it
* @param  array    $parent_node_options  Array of node options to be used as parent for new records
*
* @return void
*/
function render_new_node_record($form_action, $is_tree, $parent = 0, $node_depth_level = 0, array $parent_node_options = array())
    {
    global $baseurl_short, $lang;
    if(!isset($is_tree))
        {
        trigger_error('$is_tree param for render_new_node_record() must be set to either TRUE or FALSE!');
        }

    if (trim($form_action)=="")
        {
        trigger_error('$form_action param for render_new_node_record() must be set and not be an empty string!');
        }

    // Render normal fields first then go to tree type
    if(!$is_tree)
        {
        ?>
        <tr id="new_node_<?php echo $parent; ?>_children">
            <td>
                <input type="text" class="stdwidth" name="new_option_name" form="new_option" value="">
            </td>
            <td> </td>
            <td>
                <div class="ListTools">
                    <form id="new_option" method="post" action="<?php echo $form_action; ?>">
                        <?php generateFormToken("new_option"); ?>
                        <button type="submit" onClick="AddNode(<?php echo $parent; ?>); return false;"><?php echo $lang['add']; ?></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php

        return;
        }

    // Trees only
    ?>
    <table id="new_node_<?php echo $parent; ?>_children" cellspacing="0" cellpadding="5">
        <tbody>
            <tr>
            <?php
            // Indent node to the correct depth level
            $i = $node_depth_level;
            while(0 < $i)
                {
                $i--;
                ?>
                <td class="backline" width="10">
                    <img width="11" height="11" hspace="4" src="<?php echo $baseurl_short; ?>gfx/interface/sp.gif">
                </td>
                <?php
                }
                ?>
                <td class="backline" width="10">
                    <img width="11" height="11" hspace="4" src="<?php echo $baseurl_short; ?>gfx/interface/sp.gif">
                </td>
                <td>
                    <input type="text" name="new_option_name" form="new_node_<?php echo $parent; ?>_option" value="">
                </td>
                <td>
                    <select class="node_parent_chosen_selector" name="new_option_parent" form="new_node_<?php echo $parent; ?>_option">
                        <option value="">Select parent</option>
                    <?php
                    foreach($parent_node_options as $node)
                        {
                        $selected = '';
                        if(!(trim($parent)=="") && $node['ref'] == $parent)
                            {
                            $selected = ' selected';
                            }
                        ?>
                        <option value="<?php echo $node['ref']; ?>"<?php echo $selected; ?>><?php echo htmlspecialchars($node['name']); ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
                <td>
                    <div class="ListTools">
                        <form id="new_node_<?php echo $parent; ?>_option" method="post" action="<?php echo $form_action; ?>">
                            <?php generateFormToken("new_node_{$parent}_option"); ?>
                            <button type="submit" onClick="AddNode(<?php echo $parent; ?>); return false;"><?php echo $lang['add']; ?></button>
                        </form>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
    return;
    }


/**
* Calculate the next order by for a new record
*
* @param  integer  $resource_type_field   ID of the metadata field
* @param  boolean  $is_tree               Param to flag whether this is for a tree node
* @param  integer  $parent                ID of the parent of this node
*
* @return integer  $order_by  
*/
function get_node_order_by($resource_type_field, $is_tree = FALSE, $parent = NULL)
    {
    $order_by = 10;

    $query         = "SELECT COUNT(*) AS value FROM node WHERE resource_type_field = '" . escape_check($resource_type_field) . "' ORDER BY order_by ASC;";
    $nodes_counter = sql_value($query, 0);

    if($is_tree)
        {
        $query = sprintf('SELECT COUNT(*) AS value FROM node WHERE resource_type_field = \'%s\' AND %s ORDER BY order_by ASC;',
            escape_check($resource_type_field),
            (trim($parent)=="") ? 'parent IS NULL' : 'parent = \'' . escape_check($parent) . '\''
        );

        $nodes_counter = sql_value($query, 0);
        }

    if(0 < $nodes_counter)
        {
        $order_by = ($nodes_counter + 1) * 10;
        }

    return $order_by;
    }


/**
* Renders HTML for a tree node
*
* @param  integer  $ref                   ID of the node
* @param  integer  $resource_type_field   ID of the metadata field
* @param  string   $name                  Node name to be used (international)
* @param  integer  $parent                ID of the parent of this node
* @param  integer  $order_by              Value of the order in the list (e.g. 10)
* @param  boolean  $last_node             Set to true to allow to insert new records after last node in each level
* @param  integer  $use_count             Counter of how many resources use a particular node
*
* @return boolean
*/
function draw_tree_node_table($ref, $resource_type_field, $name, $parent, $order_by, $last_node = false, $use_count = 0)
    {
    global $baseurl_short, $lang;

    static $resource_type_field_last = 0;
    static $all_nodes = array();    

    if(is_null($ref) || (trim($ref)==""))
        {
        return false;
        }

    $toggle_node_mode = '';
    $spacer_filename  = 'sp.gif';
    $onClick          = '';
    $node_index = $order_by / 10;

    if(is_parent_node($ref))
        {
        $toggle_node_mode = 'unex';
        $spacer_filename  = 'node_unex.gif';
        $onClick          = sprintf('ToggleTreeNode(%s, %s);', $ref, $resource_type_field);
        }

    // Determine Node depth
    $node_depth_level = get_tree_node_level($ref);

    // Fetch all nodes on change of resource type field
    if($resource_type_field !== $resource_type_field_last)
        {
        $all_nodes = get_nodes($resource_type_field, NULL, TRUE, NULL, NULL, '', TRUE);
        $resource_type_field_last = $resource_type_field;    
        }

    // We remove the current node from the list of parents for it( a node should not add to itself)
    $nodes = $all_nodes;
    $nodes_index_to_remove = array_search($ref, array_column($nodes, 'ref'));
    unset($nodes[$nodes_index_to_remove]);
    $nodes = array_values($nodes);
    ?>
    <table id="node_<?php echo $ref; ?>" cellspacing="0" cellpadding="5" data-toggle-node-mode = "<?php echo $toggle_node_mode; ?>">
        <tbody>
            <tr>
            <?php
            // Indent node to the correct depth level
            $i = $node_depth_level;
            while(0 < $i)
                {
                $i--;
                ?>
                <td class="backline" width="10">
                    <img width="11" height="11" hspace="4" src="<?php echo $baseurl_short; ?>gfx/interface/sp.gif">
                </td>
                <?php
                }
                ?>
                <td class="backline" width="10">
                    <img id="node_<?php echo $ref; ?>_toggle_button" width="11" height="11" hspace="4" src="<?php echo $baseurl_short; ?>gfx/interface/<?php echo $spacer_filename; ?>" onclick="<?php echo $onClick; ?>">
                </td>
                <td>
                    <input type="text" name="option_name" form="option_<?php echo $ref; ?>" value="<?php echo $name; ?>">
                </td>
                <td>
                    <select id="node_option_<?php echo $ref; ?>_parent_select" class="node_parent_chosen_selector" name="option_parent" form="option_<?php echo $ref; ?>">
                        <option value="">Select parent</option>
                    <?php
                    foreach($nodes as $node)
                        {
                        $selected = '';
                        if(!(trim($parent)=="") && $node['ref'] == $parent)
                            {
                            $selected = ' selected';
                            }
                        ?>
                        <option value="<?php echo $node['ref']; ?>"<?php echo $selected; ?>><?php echo htmlspecialchars($node['name']); ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </td>
                <td><?php echo $use_count ?></td>
                <td>
                    <div class="ListTools">
                        <form id="option_<?php echo $ref; ?>" method="post" action="/pages/admin/admin_manage_field_options.php?field=<?php echo $resource_type_field; ?>">
                            <input type="hidden" name="option_order_by" value="<?php echo $order_by; ?>">
                            <input 
                                    type="number"
                                    name="node_order_by" 
                                    value="<?php echo $node_index; ?>" 
                                    id="option_<?php echo $ref; ?>_order_by" 
                                    readonly='true'
                                    min='1'
                                >
                            </td>
                            <td> <!-- Buttons for changing order -->
                                <button 
                                    type="button"
                                    id="option_<?php echo $ref; ?>_move_to"
                                    onclick="
                                        EnableMoveTo(<?php echo $ref; ?>);
                                        
                                        return false;
                                    ">
                                    <?php echo $lang['action-move-to']; ?>
                                </button>
                                <button 
                                    type="submit"
                                    id="option_<?php echo $ref; ?>_order_by_apply"
                                    onclick="
                                        ApplyMoveTo(<?php echo $ref; ?>);
                                        return false;
                                    "
                                    style="display: none;"
                                >
                                <?php echo $lang['action-title_apply']; ?>
                                </button>
                                <button type="submit" onclick="ReorderNode(<?php echo $ref; ?>, 'moveup'); return false;"><?php echo $lang['action-move-up']; ?></button>
                                <button type="submit" onclick="ReorderNode(<?php echo $ref; ?>, 'movedown'); return false;"><?php echo $lang['action-move-down']; ?></button>
                            </td>
                        <td> <!-- Action buttons -->
                            <button type="submit" onclick="SaveNode(<?php echo $ref; ?>); return false;"><?php echo $lang['save']; ?></button>
                            <?php
                            if(!is_parent_node($ref))
                                {?>
                            <button type="submit" onclick="DeleteNode(<?php echo $ref; ?>); return false;"><?php echo $lang['action-delete']; ?></button>
                                <?php 
                                }
                            ?>
                        </td>
                            
                        <?php generateFormToken("option_{$ref}"); ?>
                        </form>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <div id="node_<?php echo $ref; ?>_children"></div>

    <?php
    // Add a way of inserting new records after the last node of each level
    if($last_node)
        {
        if(trim($parent)=="")
            {
            $parent = 0;
            }
        render_new_node_record('/pages/admin/admin_manage_field_options.php?field=' . $resource_type_field, TRUE, $parent, $node_depth_level, $all_nodes);
        }

    return true;
    }


/**
 * Overrides either a field[options] array structure or a flat option array with values derived from nodes.
 * If a field, then will also add field=>nodes[] sub array ready for field rendering.
 *
 * @param  mixed    $field                 Either field array structure or flat options list array
 * @param  integer  $resource_type_field   ID of the metadata field, if specified will treat as flat options list
 *
 * @return boolean
 */
function node_field_options_override(&$field,$resource_type_field=null)
    {
    if (!is_null($resource_type_field))     // we are dealing with a single specified resource type so simply return array of options
        {
        $options = get_nodes($resource_type_field);
        if(count($options) > 0)     // only override if field options found within nodes
            {
            $field = array();
            foreach ($options as $option)
                {
                array_push($field,$option['name']);
                }
            }
        return true;
        }

    if (
        !isset($field['ref']) ||
        !isset($field['type']) ||
        !in_array($field['type'], array(2, 3, 7, 9, 12))
    )
        {
        return false;       // get out of here if not a node supported field type
        }

    migrate_resource_type_field_check($field);

    $field['nodes'] = array();          // setup new nodes associate array to be used by node-aware field renderers
    $field['node_options'] = array();   // setup new node options list for render of flat fields such as drop down lists (saves another iteration through nodes to grab names)

    if ($field['type'] == 7)        // category tree
        {
        $category_tree_nodes = get_nodes($field['ref'], null, true);
        if (count($category_tree_nodes) > 0)
            {
            foreach ($category_tree_nodes as $node)
                {
                $field['nodes'][$node['ref']] = $node;
                }
            }
        }
    else        // normal comma separated options used for checkboxes, selects, etc.
        {
        $nodes = get_nodes($field['ref'],null,false,null,null,null,null,(bool)$field['automatic_nodes_ordering']);
        if (count($nodes) > 0)
            {
            foreach ($nodes as $node)
                {
                $field['nodes'][$node['ref']]=$node;
                array_push($field['node_options'],$node['name']);
                }
            }
        }
    return true;
    }


/**
* Adds node keyword for indexing purposes
*
* @param  integer  $node        ID of the node (from node table) the keyword should be linked to
* @param  string   $keyword     Keyword to index
* @param  integer  $position    The position of the keyword in the string that was indexed
* @param  boolean  $normalized  If this keyword is normalized by the time we add it, set as true
*  
* @return boolean
*/
function add_node_keyword($node, $keyword, $position, $normalize = true, $stem = true)
    {
    global $unnormalized_index, $noadd, $stemming;

    debug("add_node_keyword: node:" . $node . ", keyword: " . $keyword . ", position: " . $position . ", normalize:" . ($normalize?"TRUE":"FALSE") . ", stem:" . ($stem?"TRUE":"FALSE"));
    if($normalize)
        {
        $original_keyword = $keyword;
        $kworig          = normalize_keyword($keyword);
        // if $keyword has changed after normalizing it, then index the original value as well
        if($keyword != $kworig && $unnormalized_index)
            {
            add_node_keyword($node, $kworig, $position, false, $stem);
            }
        }
        
     if ($stem && $stemming && function_exists("GetStem"))
        {
        $unstemmed=$keyword;
        $keyword=GetStem($keyword);
        if($keyword!=$unstemmed)
            {
            // $keyword has been changed by stemming, also index the original value
            debug("add_node_keyword - adding unstemmed: " . $unstemmed);
            add_node_keyword($node, $unstemmed, $position, $normalize,false);
            }
        }
        
        
    // $keyword should not be indexed if it can be found in the $noadd array, no need to continue
    if(in_array($keyword, $noadd))
        {
        debug('Ignored keyword "' . $keyword . '" as it is in the $noadd array. Triggered in ' . __FUNCTION__ . '() on line ' . __LINE__);
        return false;
        }

    $keyword_ref = resolve_keyword($keyword, true,$normalize,false); // We have already stemmed

    sql_query("INSERT INTO node_keyword (node, keyword, position) VALUES ('" . escape_check($node) . "', '" . escape_check($keyword_ref) . "', '" . escape_check($position) . "')");
    sql_query("UPDATE keyword SET hit_count = hit_count + 1 WHERE ref = '" . escape_check($keyword_ref) . "'");

    log_activity("Keyword {$keyword_ref} added for node ID #{$node}", LOG_CODE_CREATED, $keyword, 'node_keyword');

    return true;
    }


/**
* Removes node keyword for indexing purposes
*
* @param  integer  $node        ID of the node (from node table) the keyword should be linked to
* @param  string   $keyword     Keyword to index
* @param  integer  $position    The position of the keyword in the string that was indexed
* @param  boolean  $normalized  If this keyword is normalized by the time we add it, set as true
*  
* @return void
*/
function remove_node_keyword($node, $keyword, $position, $normalized = false)
    {
    global $unnormalized_index, $noadd;

    if(!$normalized)
        {
        $original_keyword = $keyword;
        $keyword          = normalize_keyword($keyword);

        // if $keyword has changed after normalizing it, then remove the original value as well
        if($keyword != $original_keyword && $unnormalized_index)
            {
            remove_node_keyword($node, $original_keyword, $position, true);
            }
        }

    $keyword_ref = resolve_keyword($keyword, true);

    $position_sql = '';
    if('' != trim($position))
        {
        $position_sql = " AND position = '" . escape_check($position) . "'";
        }

    sql_query("DELETE FROM node_keyword WHERE node = '" . escape_check($node) . "' AND keyword = '" . escape_check($keyword_ref) . "' $position_sql");
    sql_query("UPDATE keyword SET hit_count = hit_count - 1 WHERE ref = '" . escape_check($keyword_ref) . "'");

    log_activity("Keyword ID {$keyword_ref} removed for node ID #{$node}", LOG_CODE_DELETED, null, 'node_keyword', null, null, null, $keyword);

    return;
    }


/**
* Removes all indexed keywords for a specific node ID
*
* @param  integer  $node  Node ID
*  
* @return void
*/
function remove_all_node_keyword_mappings($node)
    {
    sql_query("DELETE FROM node_keyword WHERE node = '" . escape_check($node) . "'");

    return;
    }


/**
* Function used to check if a fields' node needs (re-)indexing
*
* @param  array    $node           Individual node for a field ( as returned by get_nodes() )
* @param  boolean  $partial_index  Partially index flag for node keywords
*  
* @return void
*/
function check_node_indexed(array $node, $partial_index = false)
    {
    if('' === trim($node['name']))
        {
        return;
        }

    $count_indexed_node_keywords = sql_value("SELECT count(node) AS 'value' FROM node_keyword WHERE node = '" . escape_check($node['ref']) . "'", 0);
    $keywords                    = split_keywords($node['name'], true, $partial_index);

    if($count_indexed_node_keywords == count($keywords))
        {
        // node has already been indexed
        return;
        }

    // (re-)index node
    remove_all_node_keyword_mappings($node['ref']);
    add_node_keyword_mappings($node, $partial_index);

    return;
    }


/**
* Function used to index node keywords
*
* @param  array         $node           Individual node for a field ( as returned by get_nodes() )
* @param  boolean|null  $partial_index  Partially index flag for node keywords. Use NULL if code doesn't
*                                       have access to the fields' data
*  
* @return boolean
*/
function add_node_keyword_mappings(array $node, $partial_index = false)
    {
    if('' == trim($node['ref']) && '' == trim($node['name']) && '' == trim($node['resource_type_field']))
        {
        return false;
        }

    // Client code does not know whether field is partially indexed or not
    if(is_null($partial_index))
        {
        $field_data = get_field($node['resource_type_field']);

        if(isset($field_data['partial_index']) && '' != trim($field_data['partial_index']))
            {
            $partial_index = $field_data['partial_index'];
            }
        }

    $keywords = split_keywords($node['name'], true, $partial_index);
    add_verbatim_keywords($keywords, $node['name'], $node['resource_type_field']);

    db_begin_transaction();
    for($n = 0; $n < count($keywords); $n++)
        {
        unset($keyword_position);

        if(is_array($keywords[$n]))
            {
            $keyword_position = $keywords[$n]['position'];
            $keywords[$n]     = $keywords[$n]['keyword'];
            }

        if(!isset($keyword_position))
            {
            $keyword_position = $n;
            }

        add_node_keyword($node['ref'], $keywords[$n], $keyword_position);
        }
    db_end_transaction();

    return true;
    }


/**
* Function used to un-index node keywords
*
* @param  array         $node           Individual node for a field ( as returned by get_nodes() )
* @param  boolean|null  $partial_index  Partially index flag for node keywords. Use NULL if code doesn't
*                                       have access to the fields' data
*  
* @return boolean
*/
function remove_node_keyword_mappings(array $node, $partial_index = false)
    {
    if('' == trim($node['ref']) && '' == trim($node['name']) && '' == trim($node['resource_type_field']))
        {
        return false;
        }

    // Client code does not know whether field is partially indexed or not
    if(is_null($partial_index))
        {
        $field_data = get_field($node['resource_type_field']);

        if(isset($field_data['partial_index']) && '' != trim($field_data['partial_index']))
            {
            $partial_index = $field_data['partial_index'];
            }
        }

    $keywords = split_keywords($node['name'], true, $partial_index);
    add_verbatim_keywords($keywords, $node['name'], $node['resource_type_field']);

    for($n = 0; $n < count($keywords); $n++)
        {
        unset($keyword_position);

        if(is_array($keywords[$n]))
            {
            $keyword_position = $keywords[$n]['position'];
            $keywords[$n]     = $keywords[$n]['keyword'];
            }

        if(!isset($keyword_position))
            {
            $keyword_position = $n;
            }

        remove_node_keyword($node['ref'], $keywords[$n], $keyword_position);
        }

    return true;
    }


/**
* Add nodes in array to resource
*
* @param  integer      $resourceid         Resource ID to add nodes to
* @param  array        $nodes              Array of node IDs to add
* @param  boolean      $checkperms         Check permissions before adding? 
*  
* @return boolean
*/        
function add_resource_nodes($resourceid,$nodes=array(), $checkperms = true)
    {
    if(!is_array($nodes) && (string)(int)$nodes != $nodes)
        {return false;}

    if($checkperms && (PHP_SAPI != 'cli' || defined("RS_TEST_MODE")))
        {
        // Need to check user has permissions to add nodes (unless running from any CLI script other than unit tests)
        $resourcedata = get_resource_data($resourceid);
        $access = get_edit_access($resourceid,$resourcedata["archive"],false,$resourcedata);
        if(!$access)
            {return false;}
        }
    if(!is_array($nodes))
        {$nodes=array($nodes);}

    sql_query("insert into resource_node (resource, node) values ('" . escape_check($resourceid) . "','" . implode("'),('" . escape_check($resourceid) . "','",$nodes) . "') ON DUPLICATE KEY UPDATE hit_count=hit_count");

    $field_nodes_arr = array();
    foreach ($nodes as $node)
        {
        $nodedata = array();
        get_node($node, $nodedata);
        $field_nodes_arr[$nodedata["resource_type_field"]][] = $nodedata["name"];
        }

    foreach ($field_nodes_arr as $key => $value)
        {
        resource_log($resourceid,"e",$key,"","","," . implode(",",$value));
        }

    return true;
    }

/**
* Add nodes in array to multiple resources
*
* @param  array        $resources           Array of resource IDs to add nodes to
* @param  array        $nodes               Array of node IDs to add
* @param  boolean      $checkperms          Check permissions before adding?
* 
* @return boolean
*/
function add_resource_nodes_multi($resources=array(),$nodes=array(), $checkperms = true)
    {
    if((!is_array($resources) && (string)(int)$resources != $resources) || (!is_array($nodes) && (string)(int)$nodes != $nodes))
        {return false;}
    
    if($checkperms)
        {
        // Need to check user has permissions to add nodes
        foreach($resources as $resourceid)
            {
            $resourcedata = get_resource_data($resourceid);
            $access = get_edit_access($resourceid,$resourcedata["archive"],false,$resourcedata);
            if(!$access)
                {return false;}
            }
        }

    if(!is_array($nodes))
        {$nodes=array($nodes);}

    $nodes_escaped = escape_check_array_values($nodes);

    $sql = "insert into resource_node (resource, node) values ";
    $nodesql = "";
    foreach($resources as $resource)
        {
        if($nodesql!=""){$nodesql .= ",";}
        $nodesql .= " ('" . escape_check($resource) . "','" . implode("'),('" . escape_check($resource) . "','",$nodes_escaped) . "') ";
        }
    $sql = "insert into resource_node (resource, node) values " . $nodesql . "  ON DUPLICATE KEY UPDATE hit_count=hit_count";
    sql_query($sql);
    return true;
    }

/**
* Get nodes associated with a particular resource for all / a specific field (optionally)
* 
* @param integer $resource
* @param integer $resource_type_field
* @param boolean $detailed             Set to true to return full node details (as get_node() does)
* @param boolean $node_sort            Set to SORT_ASC to sort nodes ascending, SORT_DESC sort nodes descending, null means do not sort
* 
* @return array
*/
function get_resource_nodes($resource, $resource_type_field = null, $detailed = false, $node_sort = null)
    {
    $sql_select = 'n.ref AS `value`';

    if($detailed)
        {
        $sql_select = 'n.*';
        }

    $query = "SELECT {$sql_select} FROM node AS n INNER JOIN resource_node AS rn ON n.ref = rn.node WHERE rn.resource = '" . escape_check($resource) . "'";

    if(!is_null($resource_type_field) && is_numeric($resource_type_field))
        {
        $query .= " AND n.resource_type_field = '" . escape_check($resource_type_field) . "'";
        }

    if(!is_null($node_sort))
        {
        if($node_sort == SORT_ASC)
            {
            $query .= " ORDER BY n.ref ASC";
            }
        if($node_sort == SORT_DESC)
            {
            $query .= " ORDER BY n.ref DESC";
            }
        }

    if($detailed)
        {
        return sql_query($query);
        }

    return sql_array($query);
    }


function delete_resource_nodes($resourceid,$nodes=array())
    {
    if(!is_array($nodes))
        {$nodes=array($nodes);}
    sql_query("DELETE FROM resource_node WHERE resource ='$resourceid' AND node in ('" . implode("','",$nodes) . "')"); 

    $field_nodes_arr = array();
    foreach ($nodes as $node)
        {
        $nodedata = array();
        get_node($node, $nodedata);
        $field_nodes_arr[$nodedata["resource_type_field"]][] = $nodedata["name"];
        }

    foreach ($field_nodes_arr as $key => $value)
        {
        resource_log($resourceid,"e",$key,"",implode(",",$value),"");
        }
    }


function delete_resource_nodes_multi($resources=array(),$nodes=array())
    {
    if(!is_array($nodes))
        {$nodes=array($nodes);}
        
    $sql = "DELETE FROM resource_node WHERE resource in ('" . implode("','",$resources) . "') AND node in ('" . implode("','",$nodes) . "')";
    sql_query($sql);
    }

function delete_all_resource_nodes($resourceid)
    {
    sql_query("DELETE FROM resource_node WHERE resource ='$resourceid';");  
    }


/**
* Copy resource nodes from one resource to another
* 
* @uses escape_check()
* @uses sql_array()
* @uses sql_query()
* 
* @param integer $resourcefrom Resource we are copying data from
* @param integer $resourceto   Resource we are copying data to
* 
* @return void
*/
function copy_resource_nodes($resourcefrom, $resourceto)
    {
    $resourcefrom    = escape_check($resourcefrom);
    $resourceto      = escape_check($resourceto);
    $omit_fields_sql = '';

    // When copying normal resources from one to another, check for fields that should be excluded
    // NOTE: this does not apply to user template resources (negative ID resource)
    if($resourcefrom > 0)
        {
        $omitfields      = sql_array("SELECT ref AS `value` FROM resource_type_field WHERE omit_when_copying = 1", 0);
        $omit_fields_sql = "AND n.resource_type_field NOT IN ('" . implode("','", $omitfields) . "')";
        }

    sql_query("
        INSERT INTO resource_node(resource, node, hit_count, new_hit_count)
             SELECT '{$resourceto}', node, 0, 0
               FROM resource_node AS rnold
          LEFT JOIN node AS n ON n.ref = rnold.node
              WHERE resource ='{$resourcefrom}'
                {$omit_fields_sql}
                 ON DUPLICATE KEY UPDATE hit_count = rnold.new_hit_count;
    ");

    return;
    }

function get_nodes_from_keywords($keywords=array())
    {
    if(!is_array($keywords)){$keywords=array($keywords);}
    return sql_array("select node value FROM node_keyword WHERE keyword in (" . implode(",",$keywords) . ");"); 
    }

function update_resource_node_hitcount($resource,$nodes)
    {
    # For the specified $resource, increment the hitcount for each node in array
    if(!is_array($nodes)){$nodes=array($nodes);}
    if (count($nodes)>0) {sql_query("update resource_node set new_hit_count=new_hit_count+1 WHERE resource='$resource' AND node in (" . implode(",",$nodes) . ")",false,-1,true,0);}
    }


/**
* Copy all nodes from one metadata field to another one.
* Used mostly with copy field functionality
* 
* @param integer $from resource_type_field ID FROM which we copy
* @param integer $to   resource_type_field ID TO which we copy
* 
* @return boolean
*/
function copy_resource_type_field_nodes($from, $to)
    {
    global $FIXED_LIST_FIELD_TYPES;

    // Since field has been copied, they are both the same, so we only need to check the from field
    $type = sql_value("SELECT `type` AS `value` FROM resource_type_field WHERE ref = '{$from}'", 0);

    if(!in_array($type, $FIXED_LIST_FIELD_TYPES))
        {
        return false;
        }

    $nodes = get_nodes($from, null, true);

    // Handle category trees
    if(7 == $type)
        {
        // array(from_ref => new_ref)
        $processed_nodes = array();

        foreach($nodes as $node)
            {
            if(array_key_exists($node['ref'], $processed_nodes))
                {
                continue;
                }

            $parent = $node['parent'];

            // Child nodes need to have their parent set to the new parent ID
            if('' != trim($parent))
                {
                $parent = $processed_nodes[$parent];
                }

            $new_node_id                   = set_node(null, $to, $node['name'], $parent, $node['order_by']);
            $processed_nodes[$node['ref']] = $new_node_id;
            }

        return true;
        }

    // Default handle for types different than category trees
    foreach($nodes as $node)
        {
        set_node(null, $to, $node['name'], $node['parent'], $node['order_by']);
        }

    return true;
    }

function get_parent_nodes($noderef)
    {
    $parent_nodes=array();
    $topnode=false;
    do
        {
        $node=sql_query("select n.parent, pn.name from node n join node pn on pn.ref=n.parent where n.ref='" . escape_check($noderef) . "' ");
        if(empty($node[0]["parent"]))
            {
            $topnode=true;
            }
        else
            {
            $parent_nodes[$node[0]["parent"]]=$node[0]["name"];
            $noderef=$node[0]["parent"];
            }
        }
    while (!$topnode);
    return $parent_nodes;
    }

/**
* Get the total number of nodes for a specific field
* 
* @param integer $resource_type_field ID of the metadata field
* @param string  $name                Filter by name of node
* 
* @return integer
*/
function get_nodes_count($resource_type_field, $name = '')
    {
    $resource_type_field = escape_check($resource_type_field);
    $filter_by_name = '';
    if('' != $name)
        {
        $filter_by_name = " AND `name` LIKE '%" . escape_check($name) . "%'";
        }

    return (int) sql_value("SELECT count(ref) AS `value` FROM node WHERE resource_type_field = '{$resource_type_field}'{$filter_by_name}", 0);
    }

/**
* Extract option names (in raw form if desired) from a nodes array.
* 
* @param  array    $nodes               Array of nodes as returned by get_nodes()
* @param  boolean  $i18n                Set to false if you don't need to translate the option name
* @param  boolean  $index_with_node_id  Set to false if you don't want a map between node ID and its name
* 
* @return array
*/
function extract_node_options(array $nodes, $i18n = true, $index_with_node_id = true)
    {
    if(0 == count($nodes))
        {
        return array();
        }

    $return = array();

    foreach($nodes as $node)
        {
        $value = $node['name'];

        if($i18n)
            {
            $value = i18n_get_translated($node['name']);
            }

        if($index_with_node_id)
            {
            $return[$node['ref']] = $value;

            continue;
            }

        $return[] = $value;
        }

    return $return;
    }


/**
* Search an array of nodes by name
* 
* Useful to avoid querying the database multiple times 
* if we already have a full detail array of nodes
* 
* @uses i18n_get_translated()
* 
* @param array   $nodes Nodes array as returned by get_nodes()
* @param string  $name  Filter by name of node
* @param boolean $i18n  Use the translated option value?
* 
* @return array
*/
function get_node_by_name(array $nodes, $name, $i18n = true)
    {
    if(0 == count($nodes) || is_null($name) || '' == trim($name))
        {
        return array();
        }

    $name = mb_strtolower($name);

    foreach($nodes as $node)
        {
        $option = $node['name'];

        if($i18n)
            {
            $option = i18n_get_translated($node['name']);
            }

        if($name === mb_strtolower($option))
            {
            return $node;
            }
        }

    return array();
    }

/**
* Comparator function for uasort to allow sorting of node array by name
* 
* @param array   $n1 Node one to compare
* @param string  $n2 Node two to compare
* 
* @return        0 means $n1 equals $n2
*               <0 means $n1 less than $n2
*               >0 means $n1 greater than $n2
*/
function node_name_comparator($n1, $n2)
    {
    return strcmp($n1["name"], $n2["name"]);
    }

/**
* Comparator function for uasort to allow sorting of node array by order_by field
* 
* @param array   $n1 Node one to compare
* @param string  $n2 Node two to compare
* 
* @return        0 means $n1 equals $n2
*               <0 means $n1 less than $n2
*               >0 means $n1 greater than $n2
*/
function node_orderby_comparator($n1, $n2)
    {
    return $n1["order_by"] - $n2["order_by"];
    }
