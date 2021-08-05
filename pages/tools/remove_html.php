<?php
if('cli' != PHP_SAPI)
    {
    http_response_code(401);
    exit('Access denied - Command line only!');
    }

$webroot = dirname(__DIR__, 2);
include_once "{$webroot}/include/db.php";



// Script options @see https://www.php.net/manual/en/function.getopt.php
$cli_short_options = 'cdhn';
$cli_long_options  = array(
    'help',
    'html-field:',
    'plaintext-field:',
    'encoding:',
    'html-entity-decode',
    'copy-all',
    'newlines'
);
$help_text = "NAME
    remove_html - a script to help administrators remove HTML from existing fields' values for all resources.

SYNOPSIS
    php /path/to/pages/tools/tools/remove_html.php [OPTION...]

DESCRIPTION
    A script to help administrators remove HTML from existing fields' values for all resources. 

    After processing, the plain text value will be saved in a new metadata field to help double check the tag removal worked as expected.

OPTIONS SUMMARY

    -h, --help                 Display this help text and exit
    -d, --html-entity-decode   Optional parameter. If specified, html encoded characters will be decoded. This will apply PHP's default_charset value, normally UTF 8.
    --html-field               Metadata field ID storing HTML content. Value must be a positive number. REQUIRED
    --plaintext-field          Metadata field ID to save the content after being processed. Value must be a positive number. REQUIRED
    --encoding                 Optional parameter. If -d is included, an encoding value can be specified e.g. --encoding:\"ISO-8859-1\" For values available 
                               see https://www.php.net/manual/en/function.html-entity-decode Use with caution as may cause errors if incorrect encoding is specified.
    -c, --copy-all             Optional parameter. By default this script will only output to the --plaintext-field if html was found in the --html-field. Adding -c will
                               force the script to copy the data regardless of if any change has occurred. Useful if the --plaintext-field is to replace the --html-field.
    -n, --newlines             Attempt to preserve any new lines in the html value to keep similar line spacing in the output plain text value.

EXAMPLES
    php remove_html.php --html-field=\"87\" --plaintext-field=\"88\"
    php remove_html.php -d --html-field=\"87\" --plaintext-field=\"88\"
    php remove_html.php -d --html-field=\"87\" --plaintext-field=\"88\" --encoding:\"ISO-8859-1\"
";
$options = getopt($cli_short_options, $cli_long_options);
$html_decode = false;
$copy_all = false;
$preserve_newlines = false;
foreach($options as $option_name => $option_value)
    {
    if(in_array($option_name, ['h', 'help']))
        {
        fwrite(STDOUT, $help_text . PHP_EOL);
        exit(0);
        }
    
    if(in_array($option_name, ['d','html-entity-decode']))
        {
        $html_decode = true;
        continue;
        }
    
    if(in_array($option_name, ['encoding']))
        {
        $$option_name = $option_value[0];
        continue;
        }

    if(in_array($option_name, ['c','copy-all']))
        {
        $copy_all = true;
        continue;
        }
    
    if(in_array($option_name, ['n','newlines']))
        {
        $preserve_newlines = true;
        continue;
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

$html_rtf = get_resource_type_field($html_field);
if($html_rtf === false)
    {
    fwrite(STDERR, 'ERROR: Invalid metadata field for html-field option!' . PHP_EOL);
    exit(1);
    }

$plain_rtf = get_resource_type_field($plaintext_field);
if($plain_rtf === false || !in_array($plain_rtf["type"],$TEXT_FIELD_TYPES))
    {
    fwrite(STDERR, 'ERROR: Invalid metadata field for plaintext-field option!' . PHP_EOL);
    exit(1);
    }

if(in_array($html_rtf['type'], $FIXED_LIST_FIELD_TYPES))
    {
    $html_rtf_ref = escape_check($html_field);
    $q = "  SELECT rn.resource,
                   group_concat(n.`name` SEPARATOR ', ') AS `value`
              FROM resource_node AS rn
        INNER JOIN node AS n ON rn.node = n.ref
             WHERE n.resource_type_field = '{$html_rtf_ref}'
          GROUP BY rn.resource";
    $html_data = sql_query($q);
    }
else
    {
    $html_data = get_data_by_field(null, $html_field);
    }
$results = array_column($html_data, 'value', 'resource');

foreach($results as $resource_ref => $html_value)
    {
    if ($preserve_newlines)
        {
        $html_value = str_replace('<br />', '\\n', $html_value);
        }

    $plaintxt_val = strip_tags($html_value);

    if ($html_decode)
        {
        if (isset($encoding))
            {
            $plaintxt_val = html_entity_decode($plaintxt_val, ENT_QUOTES, $encoding);    
            }
        else
            {
            $plaintxt_val = html_entity_decode($plaintxt_val, ENT_QUOTES);
            }
        }

    if (!$copy_all)
        {
        if($html_value === $plaintxt_val)
            {
            continue;
            }
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

fwrite(STDOUT, 'Successfully processed all records.' . PHP_EOL);
