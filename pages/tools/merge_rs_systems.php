<?php
/**
* @package ResourceSpace\Tools
* 
* A script to help administrators merge two ResourceSpace systems.
*/
if('cli' != PHP_SAPI)
    {
    http_response_code(401);
    exit('Access denied - Command line only!');
    }

$help_text = "NAME
    merge_rs_systems - a script to help administrators merge two ResourceSpace systems.

SYNOPSIS
    On the system (also known as SRC system) that is going to merge with the other (DEST) system:
        php path/tools/merge_rs_systems.php [OPTION...] DEST

    On the system (also known as DEST system), merging in data from the other (SRC) system:
        php path/tools/merge_rs_systems.php [OPTION...] SRC

DESCRIPTION
    A script to help administrators merge two ResourceSpace systems.

    A specification file is required for the migration to be possible. The spec file will contain:
    - A mapping between the SRC system and the DEST systems' records. Use the --generate-spec-file option to get
      an example.
    - If new workflow states will have to be created, the script will attempt to update config.php with this extra 
      information.

OPTIONS SUMMARY
    Here is a short summary of the options available in merge_rs_systems. Please refer to the detailed description below 
    for a complete description.

    -h, --help              display this help and exit
    -u, --user              run script as a ResourceSpace user. Use the ID of the user
    --dry-run               perform a trial run with no changes made. IMPORTANT: unavailable for import!
    --clear-progress        clear the progress file which is automatically generated at import
    --generate-spec-file    generate an example specification file
    --spec-file=FILE        read specification from FILE
    --export                export information from ResourceSpace
    --import                import information to ResourceSpace based on the specification file (Requires spec-file and 
                            user options)

EXAMPLES
    Export
    ======
    php /path/to/pages/tools/merge_rs_systems.php --dry-run --export /path/to/export_folder/

    Import
    ======
    php /path/to/pages/tools/merge_rs_systems.php --clear-progress --spec-file=\"/path/to/spec.php\" --import /path/to/export_folder_from_src/
    " . PHP_EOL;


$cli_short_options = "hu:";
$cli_long_options  = array(
    "help",
    "dry-run",
    "clear-progress",
    "user:",
    "spec-file:",
    "export",
    "import",
    "generate-spec-file",
);
$options = getopt($cli_short_options, $cli_long_options);

$help = false;
$dry_run = false;
$export = false;
$import = false;
$clear_progress = false;

foreach($options as $option_name => $option_value)
    {
    if(in_array($option_name, array("h", "help")))
        {
        echo $help_text;
        exit(0);
        }

    if(in_array(
        $option_name,
        array(
            "dry-run",
            "clear-progress",
            "export",
            "import",
            "generate-spec-file")))
        {
        fwrite(STDOUT, "Script running with '{$option_name}' option enabled!" . PHP_EOL);

        $option_name = str_replace("-", "_", $option_name);
        $$option_name = true;
        }

    if($option_name == "spec-file" && !is_array($option_value))
        {
        if(!file_exists($option_value))
            {
            fwrite(STDERR, "ERROR: Unable to open input file '{$option_value}'!" . PHP_EOL);
            exit(1);
            }

        $spec_file_path = $option_value;
        }

    if(in_array($option_name, array("u", "user")) && !is_array($option_value))
        {
        if(!is_numeric($option_value) || (int) $option_value <= 0)
            {
            fwrite(STDERR, "ERROR: Invalid 'user' value provided: '{$option_value}' of type " . gettype($option_value) . PHP_EOL);
            fwrite(STDOUT, PHP_EOL . $help_text);
            exit(1);
            }

        $user = $option_value;
        }
    }

$webroot = dirname(dirname(__DIR__));
include_once "{$webroot}/include/db.php";

include_once "{$webroot}/include/log_functions.php";

$get_file_handler = function($file_path, $mode)
    {
    $file_handler = fopen($file_path, $mode);
    if($file_handler === false)
        {
        logScript("ERROR: Unable to open output file '{$file_path}'!");
        exit(1);
        }

    return $file_handler;
    };

$json_decode_file_data = function($fh)
    {
    $input_lines = array();
    while(($line = fgets($fh)) !== false)
        {
        if(trim($line) != "" &&  mb_check_encoding($line, "UTF-8"))
            {
            $input_lines[] = trim($line);
            }
        }
    fclose($fh);

    if(empty($input_lines))
        {
        logScript("WARNING: No data to import! To be safe, double check on the source side whether this is true.");
        return array();
        }

    $json_decoded_data = array();
    foreach($input_lines as $input_line)
        {
        $value = json_decode($input_line, true);
        if(json_last_error() !== JSON_ERROR_NONE)
            {
            logScript("ERROR: Unable to decode JSON because of the following error: " . json_last_error_msg());
            exit(100);
            }
        $json_decoded_data[] = $value;
        }

    return $json_decoded_data;
    };

if(isset($generate_spec_file) && $generate_spec_file)
    {
    $spec_fh = $get_file_handler("spec_file_example.php", "w+b");
    fwrite($spec_fh, '<?php
// All of the following configuration options have the left side (keys) represent the SRC and DEST on the right (values)


// User groups can be configured in three ways:
// - map to an existing record in DEST system
// - create new
// - do not create
$usergroups_spec = array(
    3 => 3, # Super Admin
    9 => array(
        "create" => true,
    ),
    13 => array(
        "create" => false,
    ),
);


// Archive states can either be mapped to an existing record or be created as a new state on the DEST system
$archive_states_spec = array(
    0 => 0, # Active
    4 => null, # Marketing - new on DEST system
);


// Resource types can either be mapped to an existing record or be created as a new state on the DEST system
$resource_types_spec = array(
    0 => 0, # Global
    1 => 1, # Photo
    5 => null, # Case Study
);


/*
Resource type fields can be configured in three ways:
- map to an existing record on DEST system
- do not create 
- create new record

IMPORTANT: make sure you map to a compatible field on the DEST system. This is especially true for a category tree which
can end up a flat structure if mapped to a fixed list field of different type.
*/
$resource_type_fields_spec = array(
    // Note: when mapping to a field on DEST system, the "create" property should still be true
    3  => array(
        "create" => true,
        "ref"    => 3,
    ), # Country | dynamic keywords
    8  => array(
        "create" => true,
        "ref"    => 8,
    ), # Title | text box
    9  => array("create" => false), # Document extract
    10 => array("create" => false), # Credit
    87 => array(
        "create" => true,
        "ref" => null,
    ), # Display condition parent | drop down
    88 => array(
        "create" => true,
        "ref" => null,
    ), # Display condition child | text box
);
    ' . PHP_EOL);
    fclose($spec_fh);
    logScript("Successfully generated an example of the spec file. Location: '" . __DIR__ . "/spec_file_example.php'");
    exit(0);
    }

if($import && !isset($user))
    {
    logScript("ERROR: You need to specify a user when importing. It is best if it is a Super Admin.");
    echo $help_text;
    exit(1);
    }

if(isset($user))
    {
    $user_data = validate_user("AND u.ref = '" . escape_check($user) . "'", true);
    if(!is_array($user_data) || count($user_data) == 0)
        {
        logScript("ERROR: Unable to validate user ID #{$user}!");
        exit(1);
        }

    // Reset any "maintenance mode" config options the system might be configured with
    $global_permissions_mask = "";

    setup_user($user_data[0]);
    logScript("Running script as user '{$username}' (ID #{$userref})");
    }

/*
For the following usage:
 - php path/tools/merge_rs_systems.php [OPTION...] --export DEST
 - php path/tools/merge_rs_systems.php [OPTION...] --import SRC
Ensure DEST/SRC folder has been provided when exporting or importing data
*/
if($export || $import)
    {
    $folder_path = end($argv);
    if(!file_exists($folder_path) || !is_dir($folder_path))
        {
        $folder_type = $export ? "DEST" : ($import ? "SRC" : "");
        logScript("ERROR: {$folder_type} MUST be folder. Value provided: '{$folder_path}'");
        exit(1);
        }
    }

if($export && isset($folder_path))
    {   
    $tables = array(
        array(
            "name" => "usergroup",
            "formatted_name" => "user groups",
            "filename" => "usergroups",
            "record_feedback" => array(
                "text" => "User group #%ref '%name'",
                "placeholders" => array("ref", "name")
            )
        ),
        array(
            "name" => "user",
            "formatted_name" => "users",
            "filename" => "users",
            "record_feedback" => array(
                "text" => "User #%ref '%fullname' (Username: %username | E-mail: %email)",
                "placeholders" => array("ref", "fullname", "username", "email")
            ),
            "sql" => array(
                "select" => "*",
                "from"  => "user",
                "where" => "
                    username IS NOT NULL AND trim(username) <> ''
                    AND usergroup IS NOT NULL AND trim(usergroup) <> ''",
            ),
            "additional_process" => function($record) {
                $user_preferences = array();
                if(get_config_options($record["ref"], $user_preferences))
                    {
                    logScript("Found user preferences");
                    $record["user_preferences"] = $user_preferences;
                    }
                return $record;
            },
        ),
        array(
            "name" => "resource_type",
            "formatted_name" => "resource types",
            "filename" => "resource_types",
            "record_feedback" => array(
                "text" => "Resource type #%ref '%name'",
                "placeholders" => array("ref", "name")
            )
        ),
        array(
            "name" => "resource_type_field",
            "formatted_name" => "resource type fields",
            "filename" => "resource_type_fields",
            "record_feedback" => array(
                "text" => "Resource type field #%ref '%title' (shortname: '%name')",
                "placeholders" => array("ref", "title", "name")
            )
        ),
        array(
            "name" => "node",
            "formatted_name" => "nodes",
            "filename" => "nodes",
            "record_feedback" => array(),
            "sql" => array(
                "where" => "resource_type_field IN (SELECT ref FROM resource_type_field)",
            ),
        ),
        array(
            "name" => "resource",
            "formatted_name" => "resources",
            "filename" => "resources",
            "record_feedback" => array(),
            "sql" => array(
                "where" => "ref > 0
                    AND resource_type IN (SELECT ref FROM resource_type)
                    AND archive IN (SELECT `code` FROM archive_states)
                    AND (file_extension IS NOT NULL AND trim(file_extension) <> '')
                    AND (preview_extension IS NOT NULL AND trim(preview_extension) <> '')",
            ),
            "additional_process" => function($record) use ($hide_real_filepath) {
                if($hide_real_filepath)
                    {
                    logScript("ERROR: --export requires configuration option '\$hide_real_filepath' to be disabled (ie. set to FALSE)");
                    exit(1);
                    }

                // new fake column used at import for ingesting the file in the DEST system
                $record["merge_rs_systems_file_url"] = "";

                $path = get_resource_path($record["ref"], true, "", false, $record["file_extension"]);

                if(!file_exists($path))
                    {
                    logScript("WARNING: unable to get original file for resource #{$record["ref"]}");
                    return false;
                    }

                $record["merge_rs_systems_file_url"] = get_resource_path($record["ref"], false, "", false, $record["file_extension"]);

                return $record;
            },
        ),
        array(
            "name" => "resource_alt_files",
            "formatted_name" => "resource alternative files",
            "filename" => "resource_alt_files",
            "record_feedback" => array(),
            "sql" => array(
                "select"  => "raf.*",
                "from"  => "resource_alt_files AS raf
                    INNER JOIN resource AS r ON raf.resource = r.ref",
                "where" => "raf.resource > 0
                    AND r.resource_type IN (SELECT ref FROM resource_type)
                    AND r.archive IN (SELECT `code` FROM archive_states)
                    AND (r.file_extension IS NOT NULL AND trim(r.file_extension) <> '')
                    AND (r.preview_extension IS NOT NULL AND trim(r.preview_extension) <> '')",
            ),
            "additional_process" => function($record) {
                // new fake column used at import for ingesting the file in the DEST system
                $record["merge_rs_systems_file_url"] = "";

                $path = get_resource_path(
                    $record["resource"],
                    true,
                    "",
                    false,
                    $record["file_extension"],
                    true,
                    1,
                    false,
                    "",
                    $record["ref"]);

                if(!file_exists($path))
                    {
                    logScript("WARNING: unable to get original file for resource - alternative pair: #{$record["resource"]} - #{$record["ref"]}");
                    return false;
                    }

                $record["merge_rs_systems_file_url"] = get_resource_path(
                    $record["resource"],
                    false,
                    "",
                    false,
                    $record["file_extension"],
                    true,
                    1,
                    false,
                    "",
                    $record["ref"]);

                return $record;
            },
        ),
        array(
            "name" => "resource_data",
            "formatted_name" => "resource data",
            "filename" => "resource_data",
            "record_feedback" => array(),
            "sql" => array(
                "select"  => "rd.resource, rd.resource_type_field, rd. value",
                "from"  => "resource_data AS rd
                    RIGHT JOIN resource AS r ON rd.resource = r.ref",
                "where" => "resource > 0 AND resource_type_field IN (SELECT ref FROM resource_type_field)",
            ),
        ),
        array(
            "name" => "resource_node",
            "formatted_name" => "resource nodes",
            "filename" => "resource_nodes",
            "record_feedback" => array(),
            "sql" => array(
                "select" => "rn.resource, rn.node, rn.hit_count, rn.new_hit_count",
                "from" => "resource_node AS rn
                    RIGHT JOIN resource AS r ON rn.resource = r.ref
                    RIGHT JOIN node AS n ON rn.node = n.ref",
                "where" => "resource > 0
                    AND r.resource_type IN (SELECT ref FROM resource_type)
                    AND r.archive IN (SELECT `code` FROM archive_states)
                    AND (r.file_extension IS NOT NULL AND trim(r.file_extension) <> '')
                    AND (r.preview_extension IS NOT NULL AND trim(r.preview_extension) <> '')
                    AND n.resource_type_field IN (SELECT ref FROM resource_type_field)",
            ),
        ),
        array(
            "name" => "resource_dimensions",
            "formatted_name" => "resource dimensions",
            "filename" => "resource_dimensions",
            "record_feedback" => array(),
            "sql" => array(
                "where" => "resource > 0 AND EXISTS(SELECT ref FROM resource WHERE ref = resource_dimensions.resource)",
            ),
        ),
        array(
            "name" => "resource_related",
            "formatted_name" => "resource related",
            "filename" => "resource_related",
            "record_feedback" => array(),
            "sql" => array(
                "where" => "resource > 0
                    AND EXISTS(SELECT ref FROM resource WHERE ref = resource_related.resource)
                    AND EXISTS(SELECT ref FROM resource WHERE ref = resource_related.related)",
            ),
        ),
    );

    foreach($tables as $table)
        {
        logScript("");
        logScript("Exporting {$table["formatted_name"]}...");

        $export_fh = $get_file_handler($folder_path . DIRECTORY_SEPARATOR . "{$table["filename"]}_export.json", "w+b");

        $select = isset($table["sql"]["select"]) && trim($table["sql"]["select"]) != "" ? $table["sql"]["select"] : "*";
        $from = isset($table["sql"]["from"]) && trim($table["sql"]["from"]) != "" ? $table["sql"]["from"] : $table["name"];
        $where = isset($table["sql"]["where"]) && trim($table["sql"]["where"]) != "" ? "WHERE {$table["sql"]["where"]}" : "";
        $additional_process = isset($table["additional_process"]) && is_callable($table["additional_process"]) ? $table["additional_process"] : null;

        // @todo: consider limiting the results and keep paging until all data is retrieved to avoid running out of memory
        $records = sql_query("SELECT {$select} FROM {$from} {$where}");

        if(empty($records))
            {
            logScript("WARNING: no data found!");
            }

        foreach($records as $record)
            {
            // Sometimes you want to provide feedback to the user to let him/ her know a particular record is being processed
            if(isset($table["record_feedback"]) && !empty($table["record_feedback"]))
                {
                $log_msg = $table["record_feedback"]["text"];
                foreach($table["record_feedback"]["placeholders"] as $placeholder)
                    {
                    $log_msg = str_replace("%{$placeholder}", $record["{$placeholder}"], $log_msg);
                    }
                logScript($log_msg);
                }

            if(!is_null($additional_process))
                {
                $record = $additional_process($record);

                // additional processing might determine we don't want to process this record at all
                if($record === false)
                    {
                    continue;
                    }
                }

            if($dry_run)
                {
                continue;
                }

            fwrite($export_fh, json_encode($record, JSON_NUMERIC_CHECK) . PHP_EOL);
            }

        fclose($export_fh);
        }


    # ARCHIVE STATES
    ################
    logScript("");
    logScript("Exporting archive states...");

    $archive_states_export_fh = $get_file_handler($folder_path . DIRECTORY_SEPARATOR . "archive_states_export.json", "w+b");
    foreach(get_workflow_states() as $archive_state)
        {
        if(!isset($lang["status{$archive_state}"]))
            {
            logScript("Warning: language not set for archive state #{$archive_state}");
            continue;
            }
        $archive_state_text = $lang["status{$archive_state}"];

        logScript("Archive state '{$archive_state_text}' (ID #{$archive_state})");

        if($dry_run)
            {
            continue;
            }

        $exported_archive_state = array(
            "ref"  => $archive_state,
            "lang" => $archive_state_text);

        fwrite($archive_states_export_fh, json_encode($exported_archive_state, JSON_NUMERIC_CHECK) . PHP_EOL);
        }
    fclose($archive_states_export_fh);
    }


if($import && isset($folder_path))
    {
    if(!isset($spec_file_path) || trim($spec_file_path) == "")
        {
        logScript("ERROR: Specification file not provided or empty!");
        exit(1);
        }
    include_once $spec_file_path;

    /*
    progress.php is used to override the specification file that was provided as original input by keeping track of new 
    mappings created and saving them in the progress file.
    */
    $progress_fp = $folder_path . DIRECTORY_SEPARATOR . "progress.php";
    $progress_fh = $get_file_handler($progress_fp, "a+b");
    $php_tag_found = false;
    while(($line = fgets($progress_fh)) !== false)
        {
        if(trim($line) != "" &&  mb_check_encoding($line, "UTF-8") && mb_strpos($line, "<?php") !== false)
            {
            $php_tag_found = true;
            }
        }
    if(!$php_tag_found || $clear_progress)
        {
        ftruncate($progress_fh, 0);
        fwrite($progress_fh, "<?php" . PHP_EOL);
        }
    include_once $progress_fp;

    define("TX_SAVEPOINT", "merge_rs_systems_import");
    if(!db_begin_transaction(TX_SAVEPOINT))
        {
        logScript("ERROR: MySQL - unable to begin transaction!");
        exit(1);
        }
    db_end_transaction(TX_SAVEPOINT);



    # USER GROUPS
    #############
    logScript("");
    logScript("Importing user groups...");
    if(!isset($usergroups_spec) || empty($usergroups_spec))
        {
        logScript("ERROR: Spec missing 'usergroups_spec'");
        exit(1);
        }
    $processed_usergroups = (isset($processed_usergroups) ? $processed_usergroups : array());
    $usergroups_not_created = (isset($usergroups_not_created) ? $usergroups_not_created : array());
    $src_usergroups = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "usergroups_export.json", "r+b"));
    $dest_usergroups = get_usergroups(false, "", true);
    foreach($src_usergroups as $src_ug)
        {
        if(in_array($src_ug["ref"], $processed_usergroups) || in_array($src_ug["ref"], $usergroups_not_created))
            {
            continue;
            }

        logScript("Processing {$src_ug["name"]} (ID #{$src_ug["ref"]})...");
        if(!array_key_exists($src_ug["ref"], $usergroups_spec))
            {
            logScript("WARNING: Specification for usergroups does not contain a mapping for this group! Skipping");
            $usergroups_not_created[] = $src_ug["ref"];
            fwrite($progress_fh, "\$usergroups_not_created[] = {$src_ug["ref"]};" . PHP_EOL);
            continue;
            }

        $spec_cfg_value = $usergroups_spec[$src_ug["ref"]];
        if(is_numeric($spec_cfg_value) && $spec_cfg_value > 0 && array_key_exists($spec_cfg_value, $dest_usergroups))
            {
            logScript("Found direct 1:1 mapping to '{$dest_usergroups[$spec_cfg_value]}' (ID #{$spec_cfg_value})... Skipping");
            $processed_usergroups[] = $src_ug["ref"];
            fwrite($progress_fh, "\$processed_usergroups[] = {$src_ug["ref"]};" . PHP_EOL);
            continue;
            }
        else if(is_array($spec_cfg_value))
            {
            if(!isset($spec_cfg_value["create"]))
                {
                logScript("ERROR: usergroup specification config value is invalid. Required keys: create - true/false");
                exit(1);
                }

            if((bool) $spec_cfg_value["create"] == false)
                {
                logScript("Skipping usergroup as per the specification record");
                $usergroups_not_created[] = $src_ug["ref"];
                fwrite($progress_fh, "\$usergroups_not_created[] = {$src_ug["ref"]};" . PHP_EOL);
                continue;
                }

            sql_query("INSERT INTO usergroup(name, request_mode) VALUES ('" . escape_check($src_ug["name"]) . "', '1')");
            $new_ug_ref = sql_insert_id();
            log_activity(null, LOG_CODE_CREATED, null, 'usergroup', null, $new_ug_ref);
            log_activity(null, LOG_CODE_CREATED, $src_ug["name"], 'usergroup', 'name', $new_ug_ref, null, '');
            log_activity(null, LOG_CODE_CREATED, '1', 'usergroup', 'request_mode', $new_ug_ref, null, '');

            logScript("Created new user group '{$src_ug["name"]}' (ID #{$new_ug_ref})");
            $usergroups_spec[$src_ug["ref"]] = $new_ug_ref;
            $processed_usergroups[] = $src_ug["ref"];
            fwrite(
                $progress_fh,
                "\$usergroups_spec[{$src_ug["ref"]}] = {$new_ug_ref};"
                . PHP_EOL
                . "\$processed_usergroups[] = {$src_ug["ref"]};"
                . PHP_EOL);
            }
        else
            {
            logScript("ERROR: Invalid usergroup specification record for key #{$src_ug["ref"]}");
            exit(1);
            }

        }
    unset($src_usergroups);
    unset($dest_usergroups);


    # USERS & USER PREFERENCES
    ##########################
    logScript("");
    logScript("Importing users and their preferences...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $usernames_mapping = (isset($usernames_mapping) ? $usernames_mapping : array());
    $users_not_created = (isset($users_not_created) ? $users_not_created : array());
    $src_users = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "users_export.json", "r+b"));
    $process_user_preferences = function($user_ref, $user_data) use ($progress_fh, &$usernames_mapping)
        {
        db_begin_transaction(TX_SAVEPOINT);
        if(isset($user_data["user_preferences"]) && is_array($user_data["user_preferences"]) && !empty($user_data["user_preferences"]))
            {
            logScript("Processing user preferences (if no warning is showing, this is ok)");
            foreach($user_data["user_preferences"] as $user_p)
                {
                if(!set_config_option($user_ref, $user_p["parameter"], $user_p["value"]))
                    {
                    logScript("ERROR: uanble to save user preference: {$user_p["parameter"]} = '{$user_p["value"]}'");
                    exit(1);
                    }
                }
            }

        $usernames_mapping[$user_data["ref"]] = $user_ref;
        fwrite($progress_fh, "\$usernames_mapping[{$user_data["ref"]}] = {$user_ref};" . PHP_EOL);
        db_end_transaction(TX_SAVEPOINT);
        };
    foreach($src_users as $user)
        {
        if(array_key_exists($user["ref"], $usernames_mapping) || in_array($user["ref"], $users_not_created))
            {
            continue;
            }

        $found_uref = get_user_by_username($user["username"]);
        if($found_uref !== false)
            {
            $found_udata = get_user($found_uref);
            logScript("Username '{$user["username"]}' found in current system as '{$found_udata["username"]}', full name '{$found_udata["fullname"]}'");
            $process_user_preferences($found_uref, $user);
            continue;
            }

        if(in_array($user["usergroup"], $usergroups_not_created))
            {
            logScript("WARNING: User '{$user["username"]}' belongs to a user group that was not created as per the specification file. Skipping");
            $users_not_created[] = $user["ref"];
            fwrite($progress_fh, "\$users_not_created[] = {$user["ref"]};" . PHP_EOL);
            continue;
            }

        db_begin_transaction(TX_SAVEPOINT);
        $new_uref = new_user($user["username"], $usergroups_spec[$user["usergroup"]]);
        logScript("Created new user '{$user["username"]}' (ID #{$new_uref} | User group ID: {$usergroups_spec[$user["usergroup"]]})");

        $_GET["username"] = $user["username"];
        $_GET["password"] = $user["password"];
        $_GET["fullname"] = $user["fullname"];
        $_GET["email"] = $user["email"];
        $_GET["expires"] = $user["account_expires"];
        $_GET["usergroup"] = $usergroups_spec[$user["usergroup"]];
        $_GET["ip_restrict"] = $user["ip_restrict"];
        $_GET["search_filter_override"] = $user["search_filter_override"];
        $_GET["search_filter_o_id"] = $user["search_filter_o_id"];
        $_GET["comments"] = $user["comments"];
        $_GET["suggest"] = "";
        $_GET["emailresetlink"] = $user["password_reset_hash"];
        $_GET["approved"] = $user["approved"];
        $save_user_status = save_user($new_uref);
        if($save_user_status === false)
            {
            logScript("ERROR: failed to save user '{$user["username"]}' - Username or e-mail address already exist?");
            exit(1);
            }
        else if(is_string($save_user_status))
            {
            logScript("ERROR: failed to save user '{$user["username"]}'. Reason: '{$save_user_status}'");
            exit(1);
            }
        else
            {
            logScript("Saved user details");
            }

        $process_user_preferences($new_uref, $user);
        }
    unset($src_users);


    # ARCHIVE STATES
    ################
    logScript("");
    logScript("Importing archive states...");
    if(!isset($archive_states_spec) || empty($archive_states_spec))
        {
        logScript("ERROR: Spec missing 'archive_states_spec'");
        exit(1);
        }
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_archive_states = (isset($processed_archive_states) ? $processed_archive_states : array());
    $src_archive_states = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "archive_states_export.json", "r+b"));
    $dest_archive_states = get_workflow_states();
    foreach($src_archive_states as $archive_state)
        {
        if(in_array($archive_state["ref"], $processed_archive_states))
            {
            continue;
            }

        logScript("Processing '{$archive_state["lang"]}' (ID #{$archive_state["ref"]})");
        if(
            array_key_exists($archive_state["ref"], $archive_states_spec)
            && in_array($archive_states_spec[$archive_state["ref"]], $dest_archive_states)
            && !is_null($archive_states_spec[$archive_state["ref"]]))
            {
            $lang_text = $lang["status{$archive_states_spec[$archive_state["ref"]]}"];
            logScript("Found direct 1:1 mapping to #{$archive_states_spec[$archive_state["ref"]]} - {$lang_text}");

            $processed_archive_states[] = $archive_state["ref"];
            fwrite($progress_fh, "\$processed_archive_states[] = {$archive_state["ref"]};" . PHP_EOL);

            continue;
            }
        else if(
            array_key_exists($archive_state["ref"], $archive_states_spec)
            && !in_array($archive_states_spec[$archive_state["ref"]], $dest_archive_states))
            {
            logScript("ERROR: Incorrect mapping? Attempted to map to workflow state #{$archive_states_spec[$archive_state["ref"]]}!");
            exit(1);
            }

        if(array_key_exists($archive_state["ref"], $archive_states_spec) && is_null($archive_states_spec[$archive_state["ref"]]))
            {
            logScript("Updating config.php with extra workflow state:");

            $new_archive_state = end($dest_archive_states) + 1;
            $additional_archive_states[] = $new_archive_state;
            $lang["status{$new_archive_state}"] = $archive_state["lang"];
            $dest_archive_states[] = $new_archive_state;

            $processed_archive_states[] = $archive_state["ref"];
            fwrite($progress_fh, "\$processed_archive_states[] = {$archive_state["ref"]};" . PHP_EOL);

            $config_fh = fopen("{$webroot}/include/config.php", "a+b");
            if($config_fh === false)
                {
                logScript("WARNING: Unable to open output file '{$file_path}'! Please add manually to the file the following:");
                logScript("CONFIG.PHP: \$additional_archive_states[] = {$new_archive_state};");
                logScript("CONFIG.PHP: \$lang['status{$new_archive_state}'] = '{$archive_state["lang"]}';");
                continue;
                }

            fwrite(
                $config_fh,
                PHP_EOL
                . "\$additional_archive_states[] = {$new_archive_state};"
                . PHP_EOL
                . "\$lang['status{$new_archive_state}'] = '{$archive_state["lang"]}';");
            fclose($config_fh);
            }
        }
    unset($src_archive_states);


    # RESOURCE TYPES
    ################
    logScript("");
    logScript("Importing resource types...");
    if(!isset($resource_types_spec) || empty($resource_types_spec))
        {
        logScript("ERROR: Spec missing 'resource_types_spec'");
        exit(1);
        }
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_types = (isset($processed_resource_types) ? $processed_resource_types : array());
    $src_resource_types = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_types_export.json", "r+b"));
    $dest_resource_types = get_resource_types("", false);
    foreach($src_resource_types as $resource_type)
        {
        if(in_array($resource_type["ref"], $processed_resource_types))
            {
            continue;
            }

        logScript("Processing #{$resource_type["ref"]} '{$resource_type["name"]}'");

        if(!array_key_exists($resource_type["ref"], $resource_types_spec))
            {
            logScript("ERROR: resource_types_spec does not have a record for this resource type");
            exit(1);
            }

        if(!is_null($resource_types_spec[$resource_type["ref"]]))
            {
            if(!is_numeric($resource_types_spec[$resource_type["ref"]]))
                {
                logScript("ERROR: Invalid mapped value!");
                exit(1);
                }

            $found_rt_index = array_search($resource_types_spec[$resource_type["ref"]], array_column($dest_resource_types, "ref"));
            if($found_rt_index === false)
                {
                logScript("ERROR: Unable to find destination resource type!");
                exit(1);
                }

            $found_rt = $dest_resource_types[$found_rt_index];
            logScript("Found direct 1:1 mapping to #{$found_rt["ref"]} '{$found_rt["name"]}'");

            $processed_resource_types[] = $resource_type["ref"];
            fwrite($progress_fh, "\$processed_resource_types[] = {$resource_type["ref"]};" . PHP_EOL);

            continue;
            }

        // New record
        sql_query(
            sprintf("INSERT INTO resource_type(`name`, config_options, allowed_extensions, tab_name, push_metadata, inherit_global_fields)
                          VALUES (%s, %s, %s, %s, %s, %s);",
            (trim($resource_type["name"]) != "" ? "'" . escape_check($resource_type["name"]) . "'" : "NULL"),
            (trim($resource_type["config_options"]) != "" ? "'" . escape_check($resource_type["config_options"]) . "'" : "NULL"),
            (trim($resource_type["allowed_extensions"]) != "" ? "'" . escape_check($resource_type["allowed_extensions"]) . "'" : "NULL"),
            (trim($resource_type["tab_name"]) != "" ? "'" . escape_check($resource_type["tab_name"]) . "'" : "NULL"),
            (trim($resource_type["push_metadata"]) != "" ? "'" . escape_check($resource_type["push_metadata"]) . "'" : "NULL"),
            (trim($resource_type["inherit_global_fields"]) != "" ? "'" . escape_check($resource_type["inherit_global_fields"]) . "'" : "NULL")
        ));
        $new_rt_ref = sql_insert_id();

        log_activity(null, LOG_CODE_EDITED, $resource_type["name"], 'resource_type', 'name', $new_rt_ref);
        log_activity(null, LOG_CODE_EDITED, $resource_type["config_options"], 'resource_type', 'config_options', $new_rt_ref);
        log_activity(null, LOG_CODE_EDITED, $resource_type["allowed_extensions"], 'resource_type', 'allowed_extensions', $new_rt_ref);
        log_activity(null, LOG_CODE_EDITED, $resource_type["tab_name"], 'resource_type', 'tab_name', $new_rt_ref);
        log_activity(null, LOG_CODE_EDITED, $resource_type["push_metadata"], 'resource_type', 'push_metadata', $new_rt_ref);
        log_activity(null, LOG_CODE_EDITED, $resource_type["inherit_global_fields"], 'resource_type', 'inherit_global_fields', $new_rt_ref);

        logScript("Created new record #{$new_rt_ref} '{$resource_type["name"]}'");
        $resource_types_spec[$resource_type["ref"]] = $new_rt_ref;
        $processed_resource_types[] = $resource_type["ref"];
        fwrite(
            $progress_fh,
            "\$resource_types_spec[{$resource_type["ref"]}] = {$new_rt_ref};"
            . PHP_EOL
            . "\$processed_resource_types[] = {$resource_type["ref"]};"
            . PHP_EOL);
        }
    unset($src_resource_types);
    unset($dest_resource_types);


    # RESOURCE TYPE FIELDS
    ######################
    logScript("");
    logScript("Importing resource type fields...");
    if(!isset($resource_type_fields_spec) || empty($resource_type_fields_spec))
        {
        logScript("ERROR: Spec missing 'resource_type_fields_spec'");
        exit(1);
        }
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_type_fields = (isset($processed_resource_type_fields) ? $processed_resource_type_fields : array());
    $resource_type_fields_not_created = (isset($resource_type_fields_not_created) ? $resource_type_fields_not_created : array());
    $src_resource_type_fields = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_type_fields_export.json", "r+b"));
    $dest_resource_type_fields = get_resource_type_fields("", "ref", "ASC", "", array());
    $compatible_rtf_types = array(
        FIELD_TYPE_TEXT_BOX_SINGLE_LINE => $TEXT_FIELD_TYPES,
        FIELD_TYPE_TEXT_BOX_MULTI_LINE => $TEXT_FIELD_TYPES,
        FIELD_TYPE_CHECK_BOX_LIST => $FIXED_LIST_FIELD_TYPES,
        FIELD_TYPE_DROP_DOWN_LIST => $FIXED_LIST_FIELD_TYPES,
        FIELD_TYPE_DATE_AND_OPTIONAL_TIME => $DATE_FIELD_TYPES,
        FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE => $TEXT_FIELD_TYPES,
        FIELD_TYPE_EXPIRY_DATE => array(FIELD_TYPE_EXPIRY_DATE),
        FIELD_TYPE_CATEGORY_TREE => $FIXED_LIST_FIELD_TYPES,
        FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR => array(FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR),
        FIELD_TYPE_DYNAMIC_KEYWORDS_LIST => $FIXED_LIST_FIELD_TYPES,
        FIELD_TYPE_DATE => $DATE_FIELD_TYPES,
        FIELD_TYPE_RADIO_BUTTONS => $FIXED_LIST_FIELD_TYPES,
        FIELD_TYPE_WARNING_MESSAGE => array(FIELD_TYPE_WARNING_MESSAGE),
        FIELD_TYPE_DATE_RANGE => array(FIELD_TYPE_DATE_RANGE)
    );
    foreach($src_resource_type_fields as $src_rtf)
        {
        if(in_array($src_rtf["ref"], $processed_resource_type_fields) || in_array($src_rtf["ref"], $resource_type_fields_not_created))
            {
            continue;
            }

        logScript("Processing #{$src_rtf["ref"]} '{$src_rtf["title"]}'");

        if(!array_key_exists($src_rtf["ref"], $resource_type_fields_spec))
            {
            logScript("WARNING: Specification missing mapping for this resource type field! Skipping");
            $resource_type_fields_not_created[] = $src_rtf["ref"];
            fwrite($progress_fh, "\$resource_type_fields_not_created[] = {$src_rtf["ref"]};" . PHP_EOL);
            continue;
            }

        // Check if we need to create this field
        if(!(isset($resource_type_fields_spec[$src_rtf["ref"]]["create"]) && is_bool($resource_type_fields_spec[$src_rtf["ref"]]["create"])))
            {
            logScript("ERROR: invalid mapping configuration for mapped value. Expecting array type with index 'create' of type boolean.");
            exit(1);
            }
        if(!$resource_type_fields_spec[$src_rtf["ref"]]["create"])
            {
            logScript("Mapping set to not be created. Skipping");
            $resource_type_fields_not_created[] = $src_rtf["ref"];
            fwrite($progress_fh, "\$resource_type_fields_not_created[] = {$src_rtf["ref"]};" . PHP_EOL);
            continue;
            }

        /* 
        Check if we have a field mapped. Expected values:
            - integer when we have a direct mapping
            - null when a new field should be created
        */
        if(
            !(
                (
                    isset($resource_type_fields_spec[$src_rtf["ref"]]["ref"])
                    && (
                            is_int($resource_type_fields_spec[$src_rtf["ref"]]["ref"])
                            && $resource_type_fields_spec[$src_rtf["ref"]]["ref"] > 0
                        )
                )
                || is_null($resource_type_fields_spec[$src_rtf["ref"]]["ref"])
            )
        )
            {
            logScript("ERROR: invalid mapping configuration for mapped value. Expecting array type with index 'ref' of type integer OR use 'null' to create new field.");
            exit(1);
            }
        $mapped_rtf_ref = $resource_type_fields_spec[$src_rtf["ref"]]["ref"];

        // This is merged as a new field
        if(is_null($mapped_rtf_ref))
            {
            db_begin_transaction(TX_SAVEPOINT);
            $new_rtf_ref = create_resource_type_field(
                $src_rtf["title"],
                $resource_types_spec[$src_rtf["resource_type"]],
                $src_rtf["type"],
                $src_rtf["name"],
                $src_rtf["keywords_index"]);

            if($new_rtf_ref === false)
                {
                logScript("ERROR: unable to create new resource type field!");
                exit(1);
                }

            // IMPORTANT: we explicitly don't escape SQL values in this case as this should be the exact value stored in the SRC DB
            $sql = "";
            foreach($src_rtf as $column => $value)
                {
                // Ignore columns that have been used for creating this field
                if(in_array($column, array("ref", "name", "title", "type", "keywords_index", "resource_type")))
                    {
                    continue;
                    }

                if(trim($sql) != "")
                    {
                    $sql .= ", ";
                    }

                $col_val = (trim($value) == "" ? "NULL" : "'{$value}'");
                $sql .= "`{$column}` = {$col_val}";
                log_activity(null, LOG_CODE_EDITED, $col_val, 'resource_type_field', $column, $new_rtf_ref);
                }
            sql_query("UPDATE resource_type_field SET {$sql} WHERE ref = '{$new_rtf_ref}'");

            logScript("Created new record #{$new_rtf_ref} '{$src_rtf["title"]}'");
            $resource_type_fields_spec[$src_rtf["ref"]] = array("create" => true, "ref" => $new_rtf_ref);
            $processed_resource_type_fields[] = $src_rtf["ref"];
            fwrite(
                $progress_fh,
                "\$resource_type_fields_spec[{$src_rtf["ref"]}] = array(\"create\" => true, \"ref\" => {$new_rtf_ref});"
                . PHP_EOL
                . "\$processed_resource_type_fields[] = {$src_rtf["ref"]};"
                . PHP_EOL);

            $new_rtf_data = $src_rtf;
            $new_rtf_data["ref"] = $new_rtf_ref;
            $new_rtf_data["resource_type"] = $resource_types_spec[$src_rtf["resource_type"]];
            $dest_resource_type_fields[] = $new_rtf_data;

            unset($new_rtf_ref);
            unset($new_rtf_data);
            db_end_transaction(TX_SAVEPOINT);
            continue;
            }

        $found_rtf_index = array_search($mapped_rtf_ref, array_column($dest_resource_type_fields, "ref"));
        if($found_rtf_index === false)
            {
            logScript("ERROR: Unable to find destination resource type field!");
            exit(1);
            }
        $found_rtf = $dest_resource_type_fields[$found_rtf_index];
        logScript("Found direct 1:1 mapping to #{$found_rtf["ref"]} '{$found_rtf["title"]}'");

        if(!in_array($found_rtf["type"], $compatible_rtf_types[$src_rtf["type"]]))
            {
            logScript("ERROR: incompatible types! Consider mapping to a field with one of these types: " . implode(", ", $compatible_rtf_types[$found_rtf["type"]]));
            exit(1);
            }

        $processed_resource_type_fields[] = $src_rtf["ref"];
        fwrite($progress_fh, "\$processed_resource_type_fields[] = {$src_rtf["ref"]};" . PHP_EOL);

        if($src_rtf["type"] == FIELD_TYPE_CATEGORY_TREE && $found_rtf["type"] != FIELD_TYPE_CATEGORY_TREE)
            {
            logScript("WARNING: SRC field is a category type and DEST field is a different fixed list type. THIS WILL FLATTEN THE CATEGORY TREE!");
            }
        }
    unset($src_resource_type_fields);


    # NODES
    #######
    logScript("");
    logScript("Importing nodes...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $nodes_mapping = (isset($nodes_mapping) ? $nodes_mapping : array());
    $nodes_not_created = (isset($nodes_not_created) ? $nodes_not_created : array());
    $src_nodes = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "nodes_export.json", "r+b"));
    foreach($src_nodes as $src_node)
        {
        if(array_key_exists($src_node["ref"], $nodes_mapping) || in_array($src_node["ref"], $nodes_not_created))
            {
            continue;
            }

        logScript("Processing #{$src_node["ref"]} '{$src_node["name"]}'");

        if(in_array($src_node["resource_type_field"], $resource_type_fields_not_created))
            {
            logScript("Skipping as resource type field was not created on the destination system!");
            $nodes_not_created[] = $src_node["ref"];
            fwrite($progress_fh, "\$nodes_not_created[] = {$src_node["ref"]};" . PHP_EOL);
            continue;
            }

        $mapped_rtf_ref = $resource_type_fields_spec[$src_node["resource_type_field"]]["ref"];
        $found_rtf_index = array_search($mapped_rtf_ref, array_column($dest_resource_type_fields, "ref"));
        if($found_rtf_index === false)
            {
            logScript("ERROR: Unable to find destination resource type field!");
            exit(1);
            }
        $found_rtf = $dest_resource_type_fields[$found_rtf_index];

        // Determine parent node
        if(
            $found_rtf["type"] == FIELD_TYPE_CATEGORY_TREE
            && (!is_null($src_node["parent"]) || trim($src_node["parent"]) != "")
            && in_array($src_node["parent"], $nodes_not_created)
        )
            {
            logScript("WARNING: unable to create new node because its parent was not created!");
            $nodes_not_created[] = $src_node["ref"];
            fwrite($progress_fh, "\$nodes_not_created[] = {$src_node["ref"]};" . PHP_EOL);
            continue;
            }
        else if(
            $found_rtf["type"] == FIELD_TYPE_CATEGORY_TREE
            && (!is_null($src_node["parent"]) || trim($src_node["parent"]) != "")
            && !in_array($src_node["parent"], $nodes_not_created)
            && isset($nodes_mapping[$src_node["parent"]])
        )
            {
            $node_parent = $nodes_mapping[$src_node["parent"]];
            }
        else
            {
            $node_parent = null;
            }

        db_begin_transaction(TX_SAVEPOINT);
        $new_node_ref = set_node(null, $mapped_rtf_ref, $src_node["name"], $node_parent, "", true);
        if($new_node_ref === false)
            {
            logScript("ERROR: unable to create new node!");
            exit(1);
            }

        logScript("Created new record #{$new_node_ref} '{$src_node["name"]}'");
        $nodes_mapping[$src_node["ref"]] = $new_node_ref;
        fwrite($progress_fh, "\$nodes_mapping[{$src_node["ref"]}] = {$new_node_ref};" . PHP_EOL);
        db_end_transaction(TX_SAVEPOINT);
        }
    unset($src_nodes);


    # RESOURCES
    ###########
    logScript("");
    logScript("Importing resources...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $resources_mapping = (isset($resources_mapping) ? $resources_mapping : array());
    $src_resources = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resources_export.json", "r+b"));
    foreach($src_resources as $src_resource)
        {
        if(array_key_exists($src_resource["ref"], $resources_mapping))
            {
            continue;
            }

        logScript("Processing #{$src_resource["ref"]} | resource_type: {$src_resource["resource_type"]} | archive: {$src_resource["archive"]} | created_by: {$src_resource["created_by"]}");

        if(
            !array_key_exists($src_resource["archive"], $archive_states_spec)
            || !in_array($archive_states_spec[$src_resource["archive"]], $dest_archive_states))
            {
            logScript("ERROR: Invalid resource archive state! Please check archive_states_spec or dest_archive_states.");
            exit(1);
            }

        $created_by = isset($user) && isset($userref) ? $userref : -1;
        if(!in_array($src_resource["created_by"], $users_not_created) && isset($usernames_mapping[$src_resource["created_by"]]))
            {
            $created_by = $usernames_mapping[$src_resource["created_by"]];
            }

        db_begin_transaction(TX_SAVEPOINT);
        $new_resource_ref = create_resource(
            $resource_types_spec[$src_resource["resource_type"]],
            $archive_states_spec[$src_resource["archive"]],
            $created_by);

        if($new_resource_ref === false)
            {
            logScript("ERROR: unable to create new resource!");
            exit(1);
            }

        // we don't want to extract, revert or autorotate. This is a basic file pull into the DEST system from a remote SRC
        $job_data = array(
            "resource" => $new_resource_ref,
            "extract" => false,
            "revert" => false,
            "autorotate" => false,
            "upload_file_by_url" => $src_resource["merge_rs_systems_file_url"],
        );
        $job_code = "merge_rs_systems_{$src_resource["ref"]}_{$new_resource_ref}_" . md5("{$src_resource["ref"]}_{$new_resource_ref}");
        $job_success_lang = "Merge RS systems - upload processing success "
            . str_replace(
                array('%ref', '%title'),
                array($new_resource_ref, ""),
                $lang["ref-title"]);
        $job_failure_lang = "Merge RS systems - upload processing fail "
            . str_replace(
                array('%ref', '%title'),
                array($new_resource_ref, ""),
                $lang["ref-title"]);
        $job_queue_added = job_queue_add("upload_processing", $job_data, $userref, "", $job_success_lang, $job_failure_lang, $job_code);
        if($job_queue_added === false)
            {
            logScript("ERROR: unable to create job queue for uploading (copying) resource original file from SRC system");
            exit(1);
            }
        else if(is_string($job_queue_added) && trim($job_queue_added) != "")
            {
            logScript("ERROR: unable to create job queue. Reason: '{$job_queue_added}'");
            exit(1);
            }

        resource_log(
            $new_resource_ref,
            LOG_CODE_SYSTEM,
            "",
            "merge_rs_systems: SRC resource ref was #{$src_resource["ref"]}",
            $src_resource["ref"],
            $new_resource_ref);

        logScript("Created new record #{$new_resource_ref}");
        $resources_mapping[$src_resource["ref"]] = $new_resource_ref;
        fwrite($progress_fh, "\$resources_mapping[{$src_resource["ref"]}] = {$new_resource_ref};" . PHP_EOL);
        db_end_transaction(TX_SAVEPOINT);
        }
    unset($src_resources);


    # RESOURCE NODES
    ################
    logScript("");
    logScript("Importing resource nodes...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_nodes = (isset($processed_resource_nodes) ? $processed_resource_nodes : array());
    $src_resource_nodes = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_nodes_export.json", "r+b"));
    foreach($src_resource_nodes as $src_rn)
        {
        if(in_array("{$src_rn["resource"]}_{$src_rn["node"]}", $processed_resource_nodes))
            {
            continue;
            }

        if(!array_key_exists($src_rn["resource"], $resources_mapping))
            {
            logScript("WARNING: Unable to find a resource mapping. Skipping");
            continue;
            }

        logScript("Processing resource #{$src_rn["resource"]} and node #{$src_rn["node"]}");

        if(in_array($src_rn["node"], $nodes_not_created))
            {
            logScript("Skipping as the node was not created on the destination system!");
            continue;
            }

        if(!isset($nodes_mapping[$src_rn["node"]]))
            {
            logScript("WARNING: unable to find a node mapping!");
            continue;
            }

        sql_query("INSERT INTO resource_node (resource, node, hit_count, new_hit_count)
                        VALUES ('{$resources_mapping[$src_rn["resource"]]}', '{$nodes_mapping[$src_rn["node"]]}', '{$src_rn["hit_count"]}', '{$src_rn["new_hit_count"]}')");
        $processed_resource_nodes[] = "{$src_rn["resource"]}_{$src_rn["node"]}";
        fwrite($progress_fh, "\$processed_resource_nodes[] = \"{$src_rn["resource"]}_{$src_rn["node"]}\";" . PHP_EOL);
        }
    unset($src_resource_nodes);


    # RESOURCE DATA
    ###############
    logScript("");
    logScript("Importing resource data...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_data = (isset($processed_resource_data) ? $processed_resource_data : array());
    $src_resource_data = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_data_export.json", "r+b"));
    foreach($src_resource_data as $src_rd)
        {
        $process_rd_value = "{$src_rd["resource"]}_{$src_rd["resource_type_field"]}_" . md5($src_rd["value"]);
        if(in_array($process_rd_value, $processed_resource_data))
            {
            continue;
            }

        logScript("Processing data for resource #{$src_rd["resource"]} | resource_type_field: #{$src_rd["resource_type_field"]}");

        if(!array_key_exists($src_rd["resource"], $resources_mapping))
            {
            logScript("WARNING: Unable to find a resource mapping. Skipping");
            continue;
            }

        if(in_array($src_rd["resource_type_field"], $resource_type_fields_not_created))
            {
            logScript("WARNING: Resource type field was not created. Skipping");
            continue;
            }

        db_begin_transaction(TX_SAVEPOINT);
        $rd_import_errors = array();
        $update_field = update_field(
            $resources_mapping[$src_rd["resource"]],
            $resource_type_fields_spec[$src_rd["resource_type_field"]]["ref"],
            $src_rd["value"],
            $rd_import_errors,
            true);

        if($update_field === false)
            {
            logScript("ERROR: unable to update field data! Found errors: " . implode(", " . PHP_EOL, $rd_import_errors));
            exit(1);
            }

        $processed_resource_data[] = $process_rd_value;
        fwrite($progress_fh, "\$processed_resource_data[] = \"{$process_rd_value}\";" . PHP_EOL);
        db_end_transaction(TX_SAVEPOINT);
        }
    unset($src_resource_data);


    # RESOURCE DIMENSIONS
    #####################
    logScript("");
    logScript("Importing resource dimensions...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_dimensions = (isset($processed_resource_dimensions) ? $processed_resource_dimensions : array());
    $src_resource_dimensions = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_dimensions_export.json", "r+b"));
    foreach($src_resource_dimensions as $src_rdms)
        {
        $process_rdms_value = "{$src_rdms["resource"]}_{$src_rdms["width"]}_{$src_rdms["height"]}_"
        . md5("{$src_rdms["resource"]}|{$src_rdms["width"]}|{$src_rdms["height"]}|{$src_rdms["file_size"]}|{$src_rdms["resolution"]}|{$src_rdms["unit"]}|{$src_rdms["page_count"]}");
        if(in_array($process_rdms_value, $processed_resource_dimensions))
            {
            continue;
            }

        logScript("Processing dimensions for resource #{$src_rdms["resource"]} | width: {$src_rdms["width"]} | height: {$src_rdms["height"]} | page_count: #{$src_rdms["page_count"]}");

        if(!array_key_exists($src_rdms["resource"], $resources_mapping))
            {
            logScript("WARNING: Unable to find a resource mapping. Skipping");
            continue;
            }

        $page_count = is_numeric($src_rdms["page_count"]) ? $src_rdms["page_count"] : "NULL";

        sql_query("INSERT INTO resource_dimensions (resource, width, height, file_size, resolution, unit, page_count)
                        VALUES ('{$resources_mapping[$src_rdms["resource"]]}',
                                '{$src_rdms["width"]}',
                                '{$src_rdms["height"]}',
                                '{$src_rdms["file_size"]}',
                                '{$src_rdms["resolution"]}',
                                '{$src_rdms["unit"]}',
                                {$page_count})");

        $processed_resource_dimensions[] = $process_rdms_value;
        fwrite($progress_fh, "\$processed_resource_dimensions[] = \"{$process_rdms_value}\";" . PHP_EOL);
        }
    unset($src_resource_dimensions);


    # RESOURCE RELATED
    ##################
    logScript("");
    logScript("Importing resource related...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_related = (isset($processed_resource_related) ? $processed_resource_related : array());
    $src_resource_related = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_related_export.json", "r+b"));
    foreach($src_resource_related as $src_rr)
        {
        if(in_array("{$src_rr["resource"]}_{$src_rr["related"]}", $processed_resource_related))
            {
            continue;
            }

        logScript("Processing resource related - resource: #{$src_rr["resource"]} | related: #{$src_rr["related"]}");

        if(
            !array_key_exists($src_rr["resource"], $resources_mapping)
            || !array_key_exists($src_rr["related"], $resources_mapping))
            {
            logScript("WARNING: Unable to find a resource mapping for either resource or related. Skipping");
            continue;
            }

        sql_query("INSERT INTO resource_related (resource, related) VALUES ('{$src_rr["resource"]}', '{$src_rr["related"]}')");

        $processed_resource_related[] = "{$src_rr["resource"]}_{$src_rr["related"]}";
        fwrite($progress_fh, "\$processed_resource_related[] = \"{$src_rr["resource"]}_{$src_rr["related"]}\";" . PHP_EOL);
        }
    unset($src_resource_related);


    # RESOURCE ALTERNATIVE FILES
    ############################
    logScript("");
    logScript("Importing resource alternative files...");
    fwrite($progress_fh, PHP_EOL . PHP_EOL);
    $processed_resource_alt_files = (isset($processed_resource_alt_files) ? $processed_resource_alt_files : array());
    $src_resource_alt_files = $json_decode_file_data($get_file_handler($folder_path . DIRECTORY_SEPARATOR . "resource_alt_files_export.json", "r+b"));
    foreach($src_resource_alt_files as $src_raf)
        {
        if(in_array("{$src_raf["resource"]}_{$src_raf["ref"]}", $processed_resource_alt_files))
            {
            continue;
            }

        logScript("Processing resource alternative file - resource: #{$src_raf["resource"]} | alternative: #{$src_raf["ref"]}");

        if(!array_key_exists($src_raf["resource"], $resources_mapping))
            {
            logScript("WARNING: Unable to find a resource mapping. Skipping");
            continue;
            }

        db_begin_transaction(TX_SAVEPOINT);
        $new_alternative_ref = add_alternative_file(
            $resources_mapping[$src_raf["resource"]],
            $src_raf["name"],
            $src_raf["description"],
            $src_raf["file_name"],
            $src_raf["file_extension"],
            $src_raf["file_size"],
            $src_raf["alt_type"]);

        // we don't want to extract, revert or autorotate. This is a basic file pull into the DEST system from a remote SRC
        $job_data = array(
            "resource" => $resources_mapping[$src_raf["resource"]],
            "extract" => false,
            "revert" => false,
            "autorotate" => false,
            "alternative" => $new_alternative_ref,
            "upload_file_by_url" => $src_raf["merge_rs_systems_file_url"],
            "extension" => $src_raf["file_extension"],
        );
        $job_code = "merge_rs_systems_{$src_raf["ref"]}_{$resources_mapping[$src_raf["resource"]]}_"
                    . md5("{$src_raf["ref"]}_{$resources_mapping[$src_raf["resource"]]}");
        $job_success_lang = "Merge RS systems - alternative upload processing success "
            . str_replace(
                array('%ref', '%title'),
                array($src_raf["ref"], ""),
                $lang["ref-title"]);
        $job_failure_lang = "Merge RS systems - alternative upload processing fail "
            . str_replace(
                array('%ref', '%title'),
                array($src_raf["ref"], ""),
                $lang["ref-title"]);

        $job_queue_added = job_queue_add("upload_processing", $job_data, $userref, "", $job_success_lang, $job_failure_lang, $job_code);
        if($job_queue_added === false)
            {
            logScript("ERROR: unable to create job queue for uploading (copying) resource alternative file from SRC system");
            exit(1);
            }
        else if(is_string($job_queue_added) && trim($job_queue_added) != "")
            {
            logScript("ERROR: unable to create job queue. Reason: '{$job_queue_added}'");
            exit(1);
            }

        $processed_resource_alt_files[] = "{$src_raf["resource"]}_{$src_raf["ref"]}";
        fwrite($progress_fh, "\$processed_resource_alt_files[] = \"{$src_raf["resource"]}_{$src_raf["ref"]}\";" . PHP_EOL);
        db_end_transaction(TX_SAVEPOINT);
        }
    unset($src_resource_alt_files);


    logScript("");
    logScript("Script ran successfully!");
    fclose($progress_fh);
    }
