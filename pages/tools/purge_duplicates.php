<?php
$webroot = dirname(__DIR__, 2);
include_once "{$webroot}/include/db.php";
include_once "{$webroot}/include/log_functions.php";
command_line_only();
set_time_limit(0);
logScript(sprintf('Started script - %s', __FILE__));

$help_text = <<<'HELP'
NAME
    purge_duplicates - remove duplicate resources.

SYNOPSIS
    php /path/to/pages/tools/purge_duplicates.php [OPTIONS]

DESCRIPTION
    A tool to help administrators remove duplicate resources faster. If desired, duplicates can be fully deleted from 
    the system.

OPTIONS SUMMARY

    -h, --help              Display this help text and exit.
    --dry-run               Perform a trial run with no changes made.
    --manage-method         Determines how the script will deal with the duplicates removal. Default: LIFO.
                            Available options are:-
                                * FIFO (First In, First Out) - will remove old resources and keep the last one found
                                * LIFO (Last In, First Out) - will remove new duplicate resources and keep the first one found
    --delete-permanently    Delete duplicate resources permanently. Default off - resources move to the configured resource_deletion_state
    -c, --collection        Collection ID to limit the duplicates removal within a range (ie resources in the specified
                            collection). You don't need to have all duplicates in the collection, just one of them will 
                            be enough. Allowed multiple instances.

EXAMPLES
    # Testing script - always use dry-run to avoid silly mistakes (e.g typos)
    php purge_duplicates.php --dry-run

    # To remove all duplicates in the system (dropping the most recent duplicates)
    php purge_duplicates.php
    php purge_duplicates.php --manage-method=LIFO

    # To remove all duplicates in the system (keeping the most recent duplicates)
    php purge_duplicates.php --manage-method=FIFO

    # To remove duplicates (keeping the most recent) in the system but only within a selected subset
    php purge_duplicates.php --manage-method=FIFO --collection=50
    php purge_duplicates.php --manage-method=FIFO -c=50 -c=100 -c=200

HELP;

// Script options @see https://www.php.net/manual/en/function.getopt.php
$cli_short_options = 'hc:';
$cli_long_options  = array(
    'help',
    'dry-run',
    'manage-method:',
    'delete-permanently',
    'collection:'
);

// Defaults
$dry_run = false;
$manage_method = 'LIFO';
$order_by = 'r.ref ASC'; # depends on the $manage_method value
$delete_permanently = false;
$collections = [];

foreach(getopt($cli_short_options, $cli_long_options) as $option_name => $option_value)
    {
    if(in_array($option_name, ['h', 'help']))
        {
        ob_clean();
        echo $help_text;
        exit(0);
        }
    else if(in_array($option_name, ['dry-run', 'delete-permanently']))
        {
        logScript("Script running with '{$option_name}' option enabled!");
        $option_name = str_replace("-", "_", $option_name);
        $$option_name = true;
        }
    else if($option_name == 'manage-method' && is_string($option_value) && in_array($option_value, array('FIFO', 'LIFO')))
        {
        $manage_method = $option_value;
        if($manage_method == 'FIFO')
            {
            $order_by = 'r.ref DESC';
            }
        }
    else if(in_array($option_name, ['c', 'collection']))
        {
        $collections = array_values(array_filter(is_array($option_value) ? $option_value : [$option_value], 'is_int_loose'));
        logScript("Script running within subset(s) range. Collections received: " . implode(', ', $collections));
        }
    }
logScript("Script running with the '{$manage_method}' method!");


$collections_limit_sql = empty($collections) 
        ? ''
        : "-- IF file checksum is within a specified range
       AND file_checksum IN (
                SELECT DISTINCT r.file_checksum
                  FROM resource AS r
            INNER JOIN collection_resource AS cr ON r.ref = cr.resource
                 WHERE cr.collection IN (" . ps_param_insert(count($collections)) . ")
                   AND (file_checksum IS NOT NULL AND file_checksum <> '')
              GROUP BY r.file_checksum
           )
";
$duplicates = ps_query("
        SELECT r.ref, r.file_extension, r.file_checksum
          FROM resource AS r
         WHERE length(r.file_extension) > 0
           AND (r.file_checksum IS NOT NULL AND r.file_checksum <> '')
           {$collections_limit_sql}
           -- IF duplicate file checksum (ie. more than one resource has it)
           AND file_checksum IN (
                    SELECT file_checksum
                      FROM resource
                     WHERE (file_checksum IS NOT NULL AND file_checksum <> '')
                  GROUP BY file_checksum HAVING count(file_checksum) > 1
               )
      ORDER BY {$order_by}
    ",
    ps_param_fill($collections, 'i')
);
logScript('Found #' . count($duplicates) . ' duplicates');
logScript("");

$saved_duplicates = []; # Key is the resource ID and value is the file checksum
foreach($duplicates as $duplicate)
    {
    logScript("Processing resource #{$duplicate['ref']} having checksum '{$duplicate['file_checksum']}'");

    // We keep one resource based on the method chosen to manage the purge (ie. FIFO/ LIFO)
    if(!in_array($duplicate['file_checksum'], $saved_duplicates))
        {
        logScript("Resource #{$duplicate['ref']} kept");
        $saved_duplicates[$duplicate['ref']] = $duplicate['file_checksum'];
        continue;
        }

    if($delete_permanently)
        {
        logScript("Deleting permanently resource #{$duplicate['ref']}");
        if(!$dry_run)
            {
            unset($resource_deletion_state);
            delete_resource($duplicate['ref']);
            }
        }
    else
        {
        logScript("Moving duplicate resource #{$duplicate['ref']} to archive state #{$resource_deletion_state}");
        if(!$dry_run)
            {
            update_archive_status($duplicate['ref'], $resource_deletion_state);
            }
        }
    }

logScript("Script completed!");