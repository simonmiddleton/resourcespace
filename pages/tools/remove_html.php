<?php
if('cli' != PHP_SAPI)
    {
    http_response_code(401);
    exit('Access denied - Command line only!');
    }

$webroot = dirname(__DIR__, 2);
include_once "{$webroot}/include/db.php";



// Script options @see https://www.php.net/manual/en/function.getopt.php
$cli_short_options = 'h';
$cli_long_options  = array(
    'help',
    'html-field:',
    'plaintext-field:',
);
$help_text = "NAME
    remove_html - a script to help administrators remove HTML from existing fields' values for all resources.

SYNOPSIS
    php /path/to/pages/tools/tools/remove_html.php [OPTION...]

DESCRIPTION
    A script to help administrators remove HTML from existing fields' values for all resources. 

    After processing, the plain text value will be saved in a new metadata field to help double check the tag removal worked as expected.

OPTIONS SUMMARY

    -h, --help          Display this help text and exit
    --html-field        Metadata field ID storing HTML content. Value must be a positive number. REQUIRED
    --plaintext-field   Metadata field ID to save the content after being processed. Value must be a positive number. REQUIRED

EXAMPLES
    php remove_html.php --html-field=\"87\" --plaintext-field=\"88\"
";
$options = getopt($cli_short_options, $cli_long_options);
foreach($options as $option_name => $option_value)
    {
    if(in_array($option_name, ['h', 'help']))
        {
        fwrite(STDOUT, $help_text . PHP_EOL);
        exit(0);
        }

    if(is_numeric($option_value) && (int) $option_value > 0)
        {
        $option_name = str_replace('-', '_', $option_name);
        $$option_name = $option_value;
        continue;
        }


    fwrite(
        STDERR,
        sprintf('ERROR: Option - %s - Invalid value provided, received type "%s"%s',
            $option_name,
            gettype($option_value),
            PHP_EOL
        )
    );
    fwrite(STDOUT, $help_text . PHP_EOL);
    exit(1);
    }



// Make sure we have everything we need before moving forward
if(!isset($html_field, $plaintext_field))
    {
    fwrite(STDERR, 'ERROR: Missing mandatory options!' . PHP_EOL . PHP_EOL);
    fwrite(STDOUT, $help_text . PHP_EOL);
    exit(1);
    }

fwrite(STDOUT, "Removing HTML from field #{$html_field} and saving result in field #{$plaintext_field}" . PHP_EOL);

$html_data = array_column(get_data_by_field(null, $html_field), 'value', 'resource');
// print_r($html_data);
// die;
foreach($html_data as $resource_ref => $html_value)
    {
    // echo "======\nResource #$resource_ref\n";
    // printf('HTML = %s %s', $html_value, PHP_EOL);
    $plaintxt_val = strip_tags($html_value);
    // printf('plain text = %s %s', $plaintxt_val, PHP_EOL);

    if($html_value === $plaintxt_val)
        {
        continue;
        }

    if(trim($plaintxt_val) === '')
        {
        fwrite(STDERR, "WARNING: Removing HTML for resource #{$resource_ref} results with no plain text data." . PHP_EOL);
        continue;
        }

    $update_err = [];
    if(update_field($resource_ref, $plaintext_field, $plaintxt_val, $update_err))
        {
        fwrite(STDOUT, "Updated resource #{$resource_ref}" . PHP_EOL);
        }
    else
        {
        fwrite(
            STDERR,
            "ERROR: Failed to update resource #{$resource_ref} with plain text value. Reason(s): " . implode('; ', $update_err) . PHP_EOL
        );
        }
    }