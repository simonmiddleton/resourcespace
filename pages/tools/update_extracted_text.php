<?php

# This script can be used to update all extracted text for supported file types
# Passing -u/--update-all will run the extraction even if there is existing data, and overwrite the old value

include_once __DIR__ . "/../../include/boot.php";
command_line_only();

set_time_limit(0);

$shortopts = "u";
$longopts = array("update-all");
$clargs = getopt($shortopts, $longopts);

$updateall = isset($clargs["update-all"]) || isset($clargs["u"]);


if(!isset($extracted_text_field)) {
    echo 'No $extracted_text_field set - exiting' . PHP_EOL;
    exit();
}

$resources = ps_query("SELECT ref, file_extension 
                        FROM resource 
                        WHERE ref > 0
                        AND LOWER(file_extension) IN ('doc','docx','xlsx','odt','ods','odp','pdf','ai','html','htm','txt','zip')
                        ORDER BY ref ASC;");


$edit_count = 0;

foreach ($resources as $resource) {

    $current_extracted_text = get_data_by_field($resource['ref'], $extracted_text_field);

    if (!empty($current_extracted_text) && !$updateall) {
        echo "Ref: " . $resource['ref'] . " - already has extracted text - skipping" . PHP_EOL;
    } else {

        $result = extract_text($resource['ref'], $resource['file_extension']);

        if ($result) {
            echo "Ref: " . $resource['ref'] . " - updating extracted text" . PHP_EOL;
            $edit_count++;
        } else {
            echo "Ref: " . $resource['ref'] . " - error extracting text" . PHP_EOL;
        }

    }
}

echo $edit_count . " of " . count($resources)  .  " resources processed" . PHP_EOL;