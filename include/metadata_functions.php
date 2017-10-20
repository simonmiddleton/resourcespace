<?php
/**
* Metadata related functions
* 
* Functions related to resource metadata in general
* 
* @package ResourceSpace\Includes
*/


/**
* Run FITS on a file and get the output back
* 
* @uses get_utility_path()
* @uses run_command()
* 
* @param string $file_path Physical path to the file
* 
* @return SimpleXMLElement
*/
function runFitsForFile($file_path)
    {
    global $fits_path;

    $fits              = get_utility_path('fits');
    $fits_path_escaped = escapeshellarg($fits_path);
    $file              = escapeshellarg($file_path);

    if(false === $fits)
        {
        trigger_error('FITS library could not be located!');
        }

    putenv("LD_LIBRARY_PATH={$fits_path_escaped}/tools/mediainfo/linux");

    $return = run_command("{$fits} -i {$file} -xc");

    return new SimpleXMLElement($return);
    }


/**
* Get metadata value for a FITS field
* 
* @param SimpleXMLElement $xml  FITS metadata XML
* @param string $rs_fits_filter A ResourceSpace specific FITS filter which allows ResourceSpace to know exactly where
*                               to look for that value in XML by converting it to an XPath query string.
* Example:
* video.mimeType would point to
* 
* <metadata>
*   <video>
*     [...]
*     <mimeType toolname="MediaInfo" toolversion="0.7.75" status="SINGLE_RESULT">video/quicktime</mimeType>
*     [...]
*   </video>
* </metadata>
* 
* @return string
*/
function getFitsMetadataFieldValue(SimpleXMLElement $xml , $rs_fits_filter)
    {
    // IMPORTANT: Not using "fits" namespace (or any for that matter) will yield no results
    // TODO: since there can be multiple namespaces (especially if run with -xc options) we might need to implement the
    // ability to use namespaces directly from RS FITS filter.
    $xml->registerXPathNamespace('fits', 'http://hul.harvard.edu/ois/xml/ns/fits/fits_output');

    // Take out last filter as that is always going to be the actual property we are looking for
    $filter        = explode('.', $rs_fits_filter);
    $fits_property = array_pop($filter);
    $filter        = implode('/fits:', $filter);

    $result = $xml->xpath("//fits:{$filter}");

    if(!isset($result) || false === $result || 0 === count($result))
        {
        return '';
        }

    if(isset($result[0]->$fits_property) && !is_array($result[0]->$fits_property))
        {
        return $result[0]->$fits_property;
        }

    return '';
    }


/**
* Extract FITS metadata from a file for a specific resource.
* 
* @uses get_resource_data()
* @uses escape_check()
* @uses sql_query()
* @uses runFitsForFile()
* @uses getFitsMetadataFieldValue()
* @uses update_field()
* 
* @param string         $file_path Path to the file from which you will extract FITS metadata
* @param integer|array  $resource  Resource ID or resource array (as returned by get_resource_data())
* 
* @return boolean
*/
function extractFitsMetadata($file_path, $resource)
    {
    if(get_utility_path('fits') === false)
        {
        return false;
        }

    if(!file_exists($file_path))
        {
        return false;
        }

    if(!is_array($resource) && !is_numeric($resource))
        {
        return false;
        }

    if(!is_array($resource) && is_numeric($resource) && 0 < $resource)
        {
        $resource = get_resource_data($resource);
        }

    $resource_type = escape_check($resource['resource_type']);

    // Get a list of all the fields that have a FITS field set
    $rs_fields_to_read_for = sql_query("
           SELECT rtf.ref,
                  rtf.`type`,
                  rtf.`name`,
                  rtf.fits_field
             FROM resource_type_field AS rtf
            WHERE length(rtf.fits_field) > 0
              AND (rtf.resource_type = '{$resource_type}' OR rtf.resource_type = 0)
         ORDER BY fits_field;
    ");

    if(0 === count($rs_fields_to_read_for))
        {
        return false;
        }

    // Run FITS and extract metadata
    $fits_xml            = runFitsForFile($file_path);
    $fits_updated_fields = array();

    foreach($rs_fields_to_read_for as $rs_field)
        {
        $fits_fields = explode(',', $rs_field['fits_field']);

        foreach($fits_fields as $fits_field)
            {
            $fits_field_value = getFitsMetadataFieldValue($fits_xml, $fits_field);

            if('' == $fits_field_value)
                {
                continue;
                }

            update_field($resource['ref'], $rs_field['ref'], $fits_field_value);

            $fits_updated_fields[] = $rs_field['ref'];
            }
        }

    if(0 < count($fits_updated_fields))
        {
        return true;
        }

    return false;
    }
