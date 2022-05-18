<?php
#
# Reindex_field.php
#
#
# Reindexes the resource metadata for a single field
#

include "../../include/db.php";

if (!(PHP_SAPI == 'cli'))
    {
    include "../../include/authenticate.php";
    if (!checkperm("a"))
        {
        exit("Permission denied");
        }
    $field=getval("field",0,true);
    }
else
    {
    $field = $argv[1] ?? 0;
    if ($field==0)
        {
        exit("Please specify a field ID\n e.g.\n php reindex_field.php 8\n");
        }
    }
include_once "../../include/image_processing.php";

set_time_limit(0);
$chunk_size=100; // Number of nodes to reindex in each batch 

# Reindex a single field
if($field != 0)
    {
    $fieldinfo = get_resource_type_field($field);
    if ($fieldinfo == false)
        {
        exit("Invalid field specified.");
        }
    if (!in_array($fieldinfo['type'], $FIXED_LIST_FIELD_TYPES) && !$fieldinfo["keywords_index"])
        {
        exit("Field is not set to be indexed.");
        }
    }
if (!(PHP_SAPI == 'cli'))
    {
    include __DIR__ . "/../../include/header.php";
    }

if (PHP_SAPI == 'cli' || (getval("submit","")!="" && enforcePostRequest(false)))
    {
    $is_date = in_array($fieldinfo['type'],[FIELD_TYPE_DATE_AND_OPTIONAL_TIME,FIELD_TYPE_EXPIRY_DATE,FIELD_TYPE_DATE,FIELD_TYPE_DATE_RANGE]);
    $is_html = ($fieldinfo["type"] == FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR);
    $nodecount = get_nodes_count($field);
    $completed = 0;
    $start = 0;
    while($completed < $nodecount)
        {
        $nodes = get_nodes($field,NULL,TRUE,$start,$chunk_size);
        foreach($nodes as $node)
            {            
            // Populate node_keyword table
            remove_all_node_keyword_mappings($node['ref']);
            add_node_keyword_mappings($node, $fieldinfo["partial_index"], $is_date,$is_html);
            $completed++;
			}
        $start += ($chunk_size +1);
        }

	echo "Reindex complete\n\n\n";
	}


if (!(PHP_SAPI == 'cli'))
    {
    include __DIR__ . "/../../include/header.php";
    ?>
    <form method="post" action="reindex_field.php">
        <?php generateFormToken("reindex_field"); ?>
    <?php render_field_selector_question("Choose field to reindex", "field",[]); ?>
    <input type="submit" name="submit" value="Reindex field">
    </form>
    <?php

    include __DIR__ . "/../../include/footer.php";
    }
