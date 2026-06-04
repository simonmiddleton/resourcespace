<?php

include "../../include/boot.php";
command_line_only();

// Report orphaned language entries, optionally deleting them from non-English language files.
//
// Usage:
//   php report_orphaned_language_entries.php
//   php report_orphaned_language_entries.php --delete

$delete_orphans = in_array("--delete", $argv, true);

$orphans = 0;
$deleted = 0;

$plugins = array_filter(scandir("../../plugins"), function ($plugin) {
    return !in_array($plugin, [".", ".."], true);
});

$plugins[] = ""; // Base languages, not plugin languages.
$plugins = array_reverse($plugins);

foreach ($plugins as $plugin) {
    $plugin_path = ($plugin !== "") ? "plugins/" . $plugin . "/" : "";
    $basefile = "../../" . $plugin_path . "languages/en.php";

    if (!file_exists($basefile)) {
        continue;
    }

    $lang = [];
    include $basefile;
    $lang_en = $lang;

    foreach ($languages as $language => $lang_name) {
        if (in_array($language, ["en", "en-US"], true)) {
            continue;
        }

        $langfile = "../../" . $plugin_path . "languages/" . $language . ".php";

        if (!file_exists($langfile)) {
            continue;
        }

        $lang = [];
        include $langfile;

        $missing = array_diff(array_keys($lang), array_keys($lang_en));
        $missing = array_filter($missing, function ($key) {
            return substr($key, 0, 7) !== "plugin-";
        });

        if (count($missing) === 0) {
            continue;
        }

        foreach ($missing as $mkey) {
            echo ($plugin !== "" ? $plugin : "base") . ":" . $language . ":" . $mkey . "\n";
            $orphans++;
        }

        if ($delete_orphans) {
            $deleted += delete_language_entries($langfile, $missing);
        }
    }
}

echo $orphans . " orphaned language entries found\n";

if ($delete_orphans) {
    echo $deleted . " orphaned language entries deleted\n";
}

/**
 * Delete matching $lang[...] assignments from a language file.
 *
 * Handles single-line and multi-line language strings, stopping at the assignment
 * statement's terminating semicolon.
 */
function delete_language_entries(string $filepath, array $keys): int
{
    $lang = [];

    include $filepath;

    $deleted = 0;

    foreach ($keys as $key) {
        if (array_key_exists($key, $lang)) {
            unset($lang[$key]);
            $deleted++;
        }
    }

    if ($deleted === 0) {
        return 0;
    }

    $output = "<?php\n\n";

    foreach ($lang as $key => $value) {
        $output .= '$lang[' . var_export($key, true) . '] = ' . var_export($value, true) . ";\n";
    }

    file_put_contents($filepath, $output);

    return $deleted;
}
