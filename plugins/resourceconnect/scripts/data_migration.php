<?php
/**
* @package ResourceConnect
* 
* Script used to easily migrate data between two systems that are connected via ResourceConnect.
* It allows migrating collections and their resources from one system to another while keeping the ResourceConnect 
* association.
*/
if('cli' != PHP_SAPI)
    {
    http_response_code(401);
    exit('Access denied - Command line only!');
    }

// @todo: once ResourceSpace supports a higher version of PHP, replace dirname(dirname()) with the use of "levels" parameter
$webroot = dirname(dirname(dirname(__DIR__)));
include_once "{$webroot}/include/db.php";

include_once "{$webroot}/plugins/resourceconnect/include/resourceconnect_functions.php";
include_once "{$webroot}/include/log_functions.php";

// Script options
$cli_short_options = "i:";
$cli_long_options  = array(
    "dry-run",
    "export-collections",
    "import-collections",
    "file:",
    "override-newuser-usergroup:",
);
$options = getopt($cli_short_options, $cli_long_options);

$dry_run = false;
$export_collections = false;
$import_collections = false;
$override_newuser_usergroup = 2; # Default to "General users"

foreach($options as $option_name => $option_value)
    {
    if($option_name == "i" && !is_array($option_value))
        {
        $input_fh = fopen($option_value, "r+b");
        if($input_fh === false)
            {
            logScript("ERROR: Unable to open input file '{$option_value}'!");
            exit(1);
            }
        }

    if(in_array(
        $option_name,
        array(
            "dry-run",
            "export-collections",
            "import-collections")))
        {
        $option_name = str_replace("-", "_", $option_name);
        $$option_name = true;
        }
    else if(in_array($option_name, array("override-newuser-usergroup")))
        {
        $option_name = str_replace("-", "_", $option_name);
        $$option_name = $option_value;
        }

    if($option_name == "file")
        {
        if(is_array($option_value))
            {
            logScript("ERROR: 'file' flag cannot be used more than once!");
            exit(1);
            }

        $file_h = fopen($option_value, "a+b");
        if($file_h === false)
            {
            logScript("ERROR: Unable to open output file '{$option_value}'!");
            exit(1);
            }
        }
    }

if($dry_run)
    {
    logScript("WARNING - Script running with DRY-RUN option enabled!");
    }


if($export_collections && isset($input_fh) && isset($file_h))
    {
    logScript("Exporting collections for list of users...");
    $input_lines = array();
    while(($line = fgets($input_fh)) !== false)
            {
            if(trim($line) != "" &&  mb_check_encoding($line, 'UTF-8'))
                {
                $input_lines[] = trim($line);
                }
            }
    fclose($input_fh);

    $exported_data = array();

    foreach($input_lines as $username)
        {
        logScript("");
        logScript("Checking username '{$username}'");
        if(trim($username) === "")
            {
            continue;
            }
        $original_username = $username;
        $username = escape_check($username);
        $user_select_sql = "AND u.username = '{$username}' AND usergroup IN (SELECT ref FROM usergroup)";
        $user_data = validate_user($user_select_sql, true);
        if(!is_array($user_data) || count($user_data) == 0)
            {
            logScript("Warning - Unable to validate user '{$username}'");
            continue;
            }
        $original_user_email = $user_data[0]["email"];
        setup_user($user_data[0]);
        logScript("Set up user '{$username}' (ID #{$userref})");

        $user_collections = get_user_collections($userref);

        // Switch over to the ResourceConnect user (to ensure permissions are honoured) before continuing
        if(!is_numeric($resourceconnect_user) || $resourceconnect_user <= 0)
                {
                logScript("ERROR - Invalid ResourceConnect user ID #{$resourceconnect_user}!");
                exit(1);
                }
        $resourceconnect_user_escaped = escape_check($resourceconnect_user);
        $user_data = validate_user("AND u.ref = '{$resourceconnect_user_escaped}'", true);
        if(!is_array($user_data) || count($user_data) == 0)
            {
            logScript("ERROR - Unable to validate ResourceConnect user ID #{$resourceconnect_user}!");
            exit(1);
            }
        setup_user($user_data[0]);
        logScript("Set up ResourceConnect user '{$username}' (ID #{$userref})");

        foreach($user_collections as $collection_data)
            {
            logScript("Checking user collection '{$collection_data["name"]}' (ID #{$collection_data["ref"]}) - with {$collection_data["count"]} resources");
            if($collection_data["count"] == 0)
                {
                logScript("Skipping");
                continue;
                }

            $collection_resources = get_collection_resources($collection_data["ref"]);
            foreach($collection_resources as $resource_ref)
                {
                if(get_resource_access($resource_ref) !== 0)
                    {
                    logScript("Warning - no full access by ResourceConnect user! Skipping");
                    continue;
                    }

                $resource_data = get_resource_data($resource_ref);
                $thumb = "{$baseurl}/gfx/" . get_nopreview_icon($resource_data["resource_type"], $resource_data["file_extension"], true);
                $large_thumb = "{$baseurl}/gfx/" . get_nopreview_icon($resource_data["resource_type"], $resource_data["file_extension"], false);
                $xl_thumb = "{$baseurl}/gfx/" . get_nopreview_icon($resource_data["resource_type"], $resource_data["file_extension"], false);
                if((bool) $resource_data["has_image"])
                    {
                    $thumb = get_resource_path($resource_ref, false, "col", false, "jpg");
                    $large_thumb = get_resource_path($resource_ref, false, "thm", false, "jpg");
                    $xl_thumb = get_resource_path($resource_ref, false, "pre", false, "jpg");
                    }
                $url = generateURL(
                    "{$baseurl}/pages/view.php",
                    array(
                        "ref"   => $resource_ref,
                        "k"     => ResourceConnect\generate_k_value($original_username, $resource_ref, $scramble_key),
                        "modal" => "true",
                    ));

                $exported_data[] = array(
                    "username"        => $original_username,
                    "user_email"      => $original_user_email,
                    "collection"      => $collection_data["ref"],
                    "collection_name" => $collection_data["name"],
                    "thumb"           => $thumb,
                    "large_thumb"     => $large_thumb,
                    "xl_thumb"        => $xl_thumb,
                    "url"             => $url,
                    "title"           => get_data_by_field($resource_ref, $view_title_field));
                }
            }
        }

    if(!empty($exported_data))
        {
        fwrite($file_h, json_encode($exported_data, JSON_NUMERIC_CHECK));
        $meta_data = stream_get_meta_data($file_h);
        logScript("Successfully exported data to '{$meta_data["uri"]}'");
        }
    fclose($file_h);
    }


if($import_collections && isset($input_fh))
    {
    logScript("Importing collections and their resources...");
    $input_lines = array();
    while(($line = fgets($input_fh)) !== false)
        {
        if(trim($line) != "" &&  mb_check_encoding($line, 'UTF-8'))
            {
            $input_lines[] = trim($line);
            }
        }
    fclose($input_fh);

    if(empty($input_lines))
        {
        logScript("ERROR - no data to import!");
        exit(1);
        }

    $import_data = json_decode($input_lines[0], true);
    if(json_last_error() !== JSON_ERROR_NONE)
        {
        logScript("ERROR - Unable to decode JSON because of the following error: " . json_last_error_msg());
        exit(1);
        }

    if(db_begin_transaction("resourceconnect_data_migration"))
        {
        logScript("MySQL - begin transaction...");
        }

    $valid_usernames = array();
    /**
    * @var array $collection_mapping Holds the mapping between the two systems' collections. This allows the script to 
    *                                add the resources to the correct collection on the new system
    */
    $collection_mapping = array();
    $last_user_setup = 0;
    $rollback_transaction = false;

    foreach($import_data as $collection_resource)
        {
        if(!array_key_exists($collection_resource["username"], $valid_usernames))
            {
            logScript("Validating username '{$collection_resource["username"]}'");
            $username_escaped = escape_check($collection_resource["username"]);
            $email_escaped = escape_check($collection_resource["user_email"]);
            $usergroup_escaped = escape_check($override_newuser_usergroup);
            $user_select_sql = "AND u.username = '{$username_escaped}' AND u.email = '{$email_escaped}'";
            $user_data = validate_user($user_select_sql, true);
            if(is_array($user_data) && count($user_data) > 0)
                {
                $user_data = $user_data[0];
                $valid_usernames[$collection_resource["username"]] = $user_data;
                }
            else
                {
                logScript("Warning - User not found! Creating one now...");
                $password = make_password();
                $password_hash = hash('sha256', md5("RS{$collection_resource["username"]}{$password}"));

                sql_query("
                    INSERT INTO user(username, password, fullname, email, usergroup, approved)
                    VALUES ('{$username_escaped}', '{$password_hash}', '{$username_escaped}', '{$email_escaped}', '{$usergroup_escaped}', 1)");
                $new_user_id = sql_insert_id();

                $user_data = validate_user($user_select_sql, true);
                if(is_array($user_data) && count($user_data) > 0 && $new_user_id == $user_data[0]["ref"])
                    {
                    $user_data = $user_data[0];
                    $valid_usernames[$collection_resource["username"]] = $user_data;
                    }
                else
                    {
                    logScript("Warning - Unable to create user '{$collection_resource["username"]}'. Skipping...");
                    $rollback_transaction = true;
                    continue;
                    }
                }
            }
        else
            {
            $user_data = $valid_usernames[$collection_resource["username"]];
            }

        if($last_user_setup != $user_data["ref"])
            {
            setup_user($user_data);
            $last_user_setup = $userref;
            logScript("Set up user '{$username}' (ID #{$userref})");
            }

        if(!array_key_exists($collection_resource["collection"], $collection_mapping))
            {
            logScript("Looking for collection '{$collection_resource["collection_name"]}'");
            $found_user_collections = get_user_collections($userref, $collection_resource["collection_name"]);
            $found_exact_match = array_search($collection_resource["collection_name"], array_column($found_user_collections, "name"));
            if(false === $found_exact_match)
                {
                $found_user_collections = array();
                }

            if(empty($found_user_collections))
                {
                $copy_suffix = ($collection_resource["collection_name"] == "New uploads" ? " - {$lang["copy"]}" : "");
                $new_collection_id = create_collection($userref, "{$collection_resource["collection_name"]}{$copy_suffix}");
                logScript("Created collection '{$collection_resource["collection_name"]}{$copy_suffix}'");
                $found_user_collections = array(array("ref" => $new_collection_id));
                }
            $collection_mapping[$collection_resource["collection"]] = $found_user_collections[0]["ref"];
            }

        logScript("Adding remote resource '{$collection_resource["url"]}' to collection");
        $rcr_insert_sql = sprintf("
            INSERT INTO resourceconnect_collection_resources(collection, thumb, large_thumb, xl_thumb, url, title)
                 VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
            escape_check($collection_mapping[$collection_resource["collection"]]),
            escape_check($collection_resource["thumb"]),
            escape_check($collection_resource["large_thumb"]),
            escape_check($collection_resource["xl_thumb"]),
            escape_check($collection_resource["url"]),
            escape_check($collection_resource["title"]));
        sql_query($rcr_insert_sql);
        }

    if(!($dry_run || $rollback_transaction) && db_end_transaction("resourceconnect_data_migration"))
        {
        logScript("MySQL - Commit transaction");
        }
    else if(db_rollback_transaction())
        {
        logScript("MySQL - Rollback Successful");
        }

    logScript("Successfully imported collection resources");
    }