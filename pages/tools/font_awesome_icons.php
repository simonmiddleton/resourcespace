<?php

// This CLI script takes a Font Awesome version number e.g. 6.7.2 as an argument, fetches an icons.json file from
// the Font Awesome GitHub repository, generates an array of icon CSS class names and places it in the file
// lib/fontawesome/resourcespace/icon_classes.php
// The script should be run every time Font Awesome is upgraded.

include "../../include/boot.php";
command_line_only();

if (isset($argv[1]) && preg_match('/^\d+\.\d+\.\d+$/', $argv[1])) {
    $fa_version = $argv[1];
} else {
    echo "Specify the Font Awesome version number, e.g. php font_awesome_icons.php 6.7.2\n";
    exit();
}

echo "Fetching icons.json from GitHub...\n";
ob_flush();

$metadata_json = file_get_contents("https://github.com/FortAwesome/Font-Awesome/raw/refs/tags/" . $fa_version . "/metadata/icons.json");
$metadata_decoded = json_decode($metadata_json);

$icon_classes = "<?php\n\n" . '$font_awesome_icons = array(' . "\n";

foreach ($metadata_decoded as $icon_key => $icon_value) {
    foreach ($icon_value as $icon_attribute => $icon_attribute_value) {
        if ($icon_attribute == "styles") {
            $icon_style = $icon_attribute_value[0];
        }
    }

    $icon_classes .= '    "fa-' . $icon_style . ' fa-' . $icon_key . '",' . "\n";
}

$icon_classes .= ");";

echo "Updating lib/fontawesome/resourcespace/icon_classes.php\n";
file_put_contents("../../lib/fontawesome/resourcespace/icon_classes.php", $icon_classes);
