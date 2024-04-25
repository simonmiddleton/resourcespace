<?php

// Check either resource file integrity or presence only depending on config

include __DIR__ . "/../../include/boot.php";
command_line_only();

if (is_process_lock("file_integrity_check")) {
    exit (" - File integrity process lock is in place.Skipping.\n");
}

// CLI options check
$maxresources = 0;
$lastchecked = 0;
$cli_long_options  = [
    'help',
    'lastchecked:',
    'maxresources:',
];

foreach (getopt('', $cli_long_options) as $option_name => $option_value) {
    if($option_name=='help') {
        echo $argv[0] . " - used to Check either resource file integrity or presence only depending on config.\n\n";
        echo "Script options\n\n";
        echo "    --lastchecked [number of days]\n    Only check resources not validated in the last X days\n\n";
        echo "    --maxresources [number]\n    Maximum number of resources to check\n\n";
        exit(1);
    } elseif ($option_name=='lastchecked') {
        $lastchecked = (int) $option_value;
    } elseif($option_name=='maxresources') {
        $maxresources = (int) $option_value;
    }
}


set_process_lock("file_integrity_check");
$resources = get_resources_to_validate($lastchecked);
$failures = [];
if (count($resources) > 0) {
    if ($maxresources > 0) {
        $resources = array_slice($resources,0,$maxresources);
    }
    echo "Validating " . count($resources) . " resources." . PHP_EOL;
    $failures = check_resources($resources);
    if (count($failures) > 0) {
        send_integrity_failure_notices($failures);
    }
}

clear_process_lock("file_integrity_check");
echo "Finished validating " . count($resources) . " resources. There were " . count($failures) . " failures". PHP_EOL;