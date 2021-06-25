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
    'stripped-field:',
);
$help_text = "NAME
    remove_html - a script to help administrators remove HTML from existing fields' values for all resources.

SYNOPSIS
    php /path/to/pages/tools/tools/remove_html.php [OPTION...]

DESCRIPTION
    A script to help administrators remove HTML from existing fields' values for all resources. 

    After processing, the stripped value will be saved in a new metadata field to help double check the tag removal worked as expected.

OPTIONS SUMMARY

    -h, --help              display this help text and exit
    --html-field            Metadata field ID storing HTML content. Value must be a positive number. REQUIRED
    --stripped-field        Metadata field ID to save the content after being processed. Value must be a positive number. REQUIRED

EXAMPLES
    php remove_html.php --html-field=\"87\" --stripped-field=\"88\"
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
if(!isset($html_field, $stripped_field))
    {
    fwrite(STDERR, "Missing mandatory options!" . PHP_EOL);
    fwrite(STDOUT, $help_text . PHP_EOL);
    exit(1);
    }

fwrite(STDOUT, "Removing HTML from field #{$html_field} and saving result in field #{$stripped_field}" . PHP_EOL);

$data = get_data_by_field(null, $html_field);
print_r($data);