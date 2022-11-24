<?php 
command_line_only();

// --- Set up
// Metadata fields
$rtf_checkbox = create_resource_type_field("Test #401 checkbox", 1, FIELD_TYPE_CHECK_BOX_LIST, "test_401_checkbox", true);
$ckb_opt_a = set_node(null, $rtf_checkbox, '~en:Scanned negative~fr:Négatif scanné', null, 10);
$ckb_opt_b = set_node(null, $rtf_checkbox, '~en:Digital camera~fr:Appareil photo numérique', null, 20);

$resource_a = create_resource(1, 0);
// --- End of Set up



// Check field_column_string_separator is applied
$field_column_string_separator = '|:|';
$data_joins[] = $rtf_checkbox;
$_POST['nodes'][$rtf_checkbox] = [$ckb_opt_a, $ckb_opt_b];
save_resource_data($resource_a, false, $rtf_checkbox);
if(mb_strpos(get_resource_data($resource_a, false)["field{$rtf_checkbox}"], $field_column_string_separator) === false)
    {
    echo "Use case: use separator for storing multiple node values in the resource table (column fieldX) - ";
    return false;
    }



// Tear down
$data_joins = [];
$_POST = [];
unset($rtf_checkbox, $ckb_opt_a, $ckb_opt_b, $resource_a);
 
return true;