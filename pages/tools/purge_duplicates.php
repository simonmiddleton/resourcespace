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
$dry_run_text="";
$manage_method = 'LIFO';
$manage_method_text = 'Keep earliest resource found and remove later duplicates';
$order_by = 'r.ref ASC'; # depends on the $manage_method value
$delete_permanently = false;
$collections = [];

// Option logging
$logScriptTexts = [];

foreach(getopt($cli_short_options, $cli_long_options) as $option_name => $option_value)
    {
    if(in_array($option_name, ['h', 'help']))
        {
        ob_clean();
        echo $help_text;
        exit(0);
        }
    elseif($option_name == 'dry-run')
        {
        $dry_run = true;
        $dry_run_text=strtoupper($option_name)." ";
        $logScriptTexts[]="Script running";
        }
    elseif($option_name == 'delete-permanently')
        {
        $delete_permanently = true;
        $logScriptTexts[]="Script running with '{$option_name}' option enabled";
        }
    elseif($option_name == 'manage-method' && is_string($option_value))
        {
        $manage_method = $option_value;
        if($manage_method == 'FIFO')
            {
            $manage_method_text = 'Keep latest resource found and remove earlier duplicates';
            $order_by = 'r.ref DESC';
            }
        }
    elseif(in_array($option_name, ['c', 'collection']))
        {
        $collections = array_values(array_filter(is_array($option_value) ? $option_value : [$option_value], 'is_int_loose'));
        $logScriptTexts[]="Script running for following collections: " . implode(', ', $collections);
        }
    }

// Reject invalid parameters and combinations
if($dry_run && $delete_permanently)
    {
    logScript("ERROR: Script terminated; options --dry-run and --delete-permanently are mutually exclusive");
    exit(0);
    }

if(!in_array($manage_method, array('FIFO', 'LIFO')))
    {
    logScript("ERROR: Script terminated; option --manage-method={$manage_method} is invalid");
    exit(0);
    }

$logScriptTexts[]="Script running with the '{$manage_method}' method; ".$manage_method_text;

// Log the options in effect for this run
foreach($logScriptTexts as $logScriptText)
    {
    logScript($dry_run_text.$logScriptText);
    }

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

$count_matching_checksums=count($duplicates);
$count_permanent_deletions=0;
$count_marked_deletions=0;
$count_unchanged=0;
logScript($dry_run_text."STARTING SUMMARY");
logscript($dry_run_text."STARTING Count of candidate resources with matching checksums is {$count_matching_checksums}");
logScript($dry_run_text."RESOURCE DETAILS");

$saved_duplicates = []; # Key is the resource ID and value is the file checksum
foreach($duplicates as $duplicate)
    {
    logScript($dry_run_text."Processing resource #{$duplicate['ref']} with checksum '{$duplicate['file_checksum']}'");

    // We keep one resource based on the method chosen to manage the purge (ie. FIFO/ LIFO)
    if(!in_array($duplicate['file_checksum'], $saved_duplicates))
        {
        logScript($dry_run_text."Resource #{$duplicate['ref']} kept");
        $count_unchanged+=1;
        $saved_duplicates[$duplicate['ref']] = $duplicate['file_checksum'];
        continue;
        }

    if($delete_permanently)
        {
        // Option delete-permanently and dry-run are mutually exclusive
        // Option dry-run will never be true and the associated text is always blank at this point; this is just a belt and braces check
        logScript($dry_run_text."Resource #{$duplicate['ref']} deleted permanently");
        $count_permanent_deletions+=1;
        if(!$dry_run)
            {
            unset($resource_deletion_state);
            delete_resource($duplicate['ref']);
            }
        }
    else
        {
        logScript($dry_run_text."Resource #{$duplicate['ref']} deleted logically; marked archive state '{$resource_deletion_state}'");
        $count_marked_deletions+=1;
        if(!$dry_run)
            {
            update_archive_status($duplicate['ref'], $resource_deletion_state);
            }
        }
    }

logScript($dry_run_text."ENDING SUMMARY");
$count_processed_resources=0;
logscript($dry_run_text."ENDING Count of resources which are kept ............... {$count_unchanged}");

if ($delete_permanently) {
    logscript($dry_run_text."ENDING Count of resources permanently deleted .......... {$count_permanent_deletions}");
    $count_processed_resources = $count_unchanged + $count_permanent_deletions;
}
else {
    logscript($dry_run_text."ENDING Count of resources marked as deleted ............ {$count_marked_deletions}");
    $count_processed_resources = $count_unchanged + $count_marked_deletions;
}

// Report whether or not ending counts are as expected
if ($count_matching_checksums == $count_processed_resources) {
    logScript($dry_run_text."ENDING Count of processed resources with matching checksums is {$count_processed_resources} as expected");
}
else {
    logScript($dry_run_text."ERROR - Count of processed resources with matching checksums is {$count_processed_resources} which is unexpected");
}
logScript($dry_run_text."Script completed!");
