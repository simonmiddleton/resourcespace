<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Set up
$fc_struct = function($name, $order_by, $is_categ = true) {
    return [
        'name' => $name,
        'order_by' => $order_by,
        'has_resources' => (int) $is_categ,
    ];
};
$fc_A = $fc_struct('A', 30);
$fc_B = $fc_struct('B', 40);
$fc_C = $fc_struct('C', 50);
$fc_1_stars = $fc_struct('*1 stars', 10);
$fc_2_stars = $fc_struct('**2 stars', 20);



$test_1404_ucs = [
    [
        'name' => 'Simple order by',
        'input' => [$fc_B, $fc_A, $fc_C],
        'expected' => [$fc_A, $fc_B, $fc_C],
    ],
];
foreach($test_1404_ucs as $use_case)
    {
    usort($use_case['input'], 'order_featured_collections');
    if($use_case['expected'] !== $use_case['input'])
        {
        echo "Use case: {$use_case['name']} - ";
        return false;
        }
    }

// TEST other use cases - wip - TODO: delete once test suite is complete
$uc = [$fc_B, $fc_A, $fc_C];
$uc_expected = [$fc_A, $fc_B, $fc_C];
usort($uc, 'order_featured_collections');
if($uc_expected !== $uc)
    {
    echo 'UC test - ';
    return false;
    }


// Tear down
unset($fc_struct, $test_1404_ucs);
unset($fc_A, $fc_B, $fc_C, $fc_1_stars, $fc_2_stars);

return true;