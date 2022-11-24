<?php 
command_line_only();

// --- Set up
// Metadata fields
$rtf_checkbox = create_resource_type_field("Test #402 checkbox", 1, FIELD_TYPE_CHECK_BOX_LIST, "test_402_checkbox", true);
$ckb_opt_a = set_node(null, $rtf_checkbox, '~en:Scanned negative~fr:Négatif scanné', null, 10);
$ckb_opt_b = set_node(null, $rtf_checkbox, '~en:Digital camera~fr:Appareil photo numérique', null, 20);

// Resources
$resource_a = create_resource(1, 0);
$resource_b = create_resource(1, 0);

// Collections
$collection_ref = create_collection($userref, 'test_402', 1);
add_resource_to_collection($resource_a, $collection_ref);
add_resource_to_collection($resource_b, $collection_ref);
// --- End of Set up



// Check that $field_column_string_separator config is applied/used
$field_column_string_separator = '|:|';
$data_joins[] = $rtf_checkbox;
$_POST['nodes'][$rtf_checkbox] = [$ckb_opt_a, $ckb_opt_b];
$_POST["editthis_field_{$rtf_checkbox}"] = 'yes';
$_POST["modeselect_{$rtf_checkbox}"] = 'RT';
save_resource_data_multi($collection_ref);
if(mb_strpos(get_resource_data($resource_b, false)["field{$rtf_checkbox}"], $field_column_string_separator) === false)
    {
    echo PHP_EOL;
    echo "Use case: use separator for storing multiple node values in the resource table (column fieldX) - ";
    return false;
    }



// Tear down
$data_joins = [];
$_POST = [];
unset($rtf_checkbox, $ckb_opt_a, $ckb_opt_b, $resource_a);
 
return true;