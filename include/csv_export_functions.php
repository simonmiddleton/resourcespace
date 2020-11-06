<?php
/**
 * Functions used (mostly) to generate the content needed for CSV files
 */

/**
* Generates the CSV content of the metadata for resources passed in the array
*
* @param array $resources (either an array of resource ids or an array returned from search results)
* @return string
*/
function generateResourcesMetadataCSV(array $resources,$personal=false,$alldata=false,$outputfile="")
    {
    global $lang, $csv_export_add_original_size_url_column, $file_checksums, $k;
    $return                 = '';
    $csv_field_headers      = array();
    $resources_fields_data  = array();
    $csvoptions = array("csvexport"=>true,"personal"=>$personal,"alldata"=>$alldata);
    $allfields = get_resource_type_fields("","order_by","asc");

    $restypearr = get_resource_types();
    $resource_types = array();
    // Sort into array with ids as keys
    foreach($restypearr as $restype)
        {
        $resource_types[$restype["ref"]] = $restype;
        }

    // Break resources up into smaller arrays to avoid hitting memory limits
    $resourcebatches = array_chunk($resources, 2000);

    $csv_field_headers["resource_type"] = $lang["resourcetype"];
    $csv_field_headers["created_by"] = $lang["contributedby"];
    $csv_field_headers["file_checksum"] = $lang["filechecksum"];
    // Add original size URL column
    if($csv_export_add_original_size_url_column)
        {
        $csv_field_headers['original_link'] = $lang['collection_download_original'];
        }

    // Array to store fields that have data, if no data we won't include it
    $include_fields = array();

    foreach ($resourcebatches as $resourcebatch)
        {
        memdebug();       

        $fullresdata = get_resource_field_data_batch($resourcebatch,true,$k != '',true,$csvoptions);

        foreach($resourcebatch as $resource)
            {
            $resdata = $resource;
            debug("BANG processing resource - " . $resource["ref"]);
            if(checkperm("T" . $resdata["resource_type"]))
                {
                continue;
                }

            // Add resource type
            $restype = get_resource_type_name($resdata["resource_type"]);
            $resources_fields_data[$resource['ref']]["resource_type"] = $restype;
            
            // Add contributor
            $udata=get_user($resdata["created_by"]);
            if ($udata!==false)
                {
                $resources_fields_data[$resource['ref']]["created_by"] = (trim($udata["fullname"]) != "" ? $udata["fullname"] :  $udata["username"]);
                }

            if ($alldata && $file_checksums)
                {
                $resources_fields_data[$resource['ref']]["file_checksum"] = $resdata["file_checksum"];
                }       
            foreach($allfields as $restypefield)
                {
                if  (
                    metadata_field_view_access($restypefield["ref"])  
                    && 
                        (!$personal || $restypefield["personal_data"])
                    && 
                        ($alldata || $restypefield["include_in_csv_export"])
                    && 
                        !(checkperm("T" . $restypefield["resource_type"]))
                    &&
                        (
                        $restypefield["resource_type"] == $resource["resource_type"]
                        ||
                        ($restypefield["resource_type"] == 0 && (bool)$resource_types[$resource["resource_type"]]["inherit_global_fields"])
                        ||
                        ($restypefield["resource_type"] == 999 && $resource["archive"] == 2)
                        )
                    )
                    {
                    $csv_field_headers[$restypefield["ref"]] = $restypefield['title'];
                    // Check if the resource has a value for this field in the data retrieved
                    $resdataidx =array_search($restypefield["ref"], array_column($fullresdata[$resource['ref']], 'ref'));
                    $fieldvalue = ($resdataidx !== false) ? $fullresdata[$resource['ref']][$resdataidx]["value"] : "";
                    $resources_fields_data[$resource['ref']][$restypefield['ref']] = $fieldvalue;
                    }
                }

            // // Add original size URL column values
            // if(!$csv_export_add_original_size_url_column)
            //     {
            //     continue;
            //     }

            /*Provide the original URL only if we have access to the resource or the user group
            doesn't have restricted access to the original size*/
            $access = get_resource_access($resource);
            if(0 != $access || checkperm("T{$resource['resource_type']}_"))
                {
                continue;
                }
            if($csv_export_add_original_size_url_column)
                {
                $filepath      = get_resource_path($resource['ref'], true, '', false, $resource['file_extension'], -1, 1, false, '', -1, false);
                $original_link = get_resource_path($resource['ref'], false, '', false, $resource['file_extension'], -1, 1, false, '', -1, false);
                if(file_exists($filepath))
                    {
                    $resources_fields_data[$resource['ref']]['original_link'] = $original_link;
                    }
                }
            }
        }
   
    $csv_field_headers = array_unique($csv_field_headers);

    // Header
    $return = '"' . $lang['resourceids'] . '","' . implode('","', $csv_field_headers) . "\"\n";

    // Results
    $csv_row = '';
    foreach($resources_fields_data as $resource_id => $resource_fields)
        {
        // First column will always be Resource ID
        $csv_row = $resource_id . ',';

        // Field values
        foreach($csv_field_headers as $column_header => $column_header_title)
            {
            if(!array_key_exists($column_header, $resource_fields))
                {
                $csv_row .= '"",';
                continue;
                }

            foreach($resource_fields as $field_name => $field_value)
                {
                if($column_header == $field_name)
                    {
                    $csv_row .= '"' . str_replace(array("\n","\r","\""),array("","","\"\""),i18n_get_translated($field_value)) . '",';
                    }
                }
            }
        
        $csv_row = rtrim($csv_row, ',');
        $csv_row .= "\n";
        $return  .= $csv_row;
        }
        
    if($outputfile != "")
        {
        file_put_contents($outputfile, $return);
        return true;
        }

    return $return;
    }


/**
* Generates the file content when exporting nodes
* 
* @param array   $field        Array containing field information (as retrieved by get_field)
* @param boolean $send_headers If true, function sends headers used for downloading content. Default is set to false
* 
* @return mixed
*/
function generateNodesExport(array $field, $parent = null, $send_headers = false)
    {
    global $lang;

    if(0 === count($field) || !isset($field['ref']) || !isset($field['type']))
        {
        trigger_error('Field array cannot be empty. generateNodesExport() requires at least "ref" and "type" indexes!');
        }

    $return = '';
    $nodes  = get_nodes($field['ref'], $parent);

    foreach($nodes as $node)
        {
        $return .= "{$node['name']}\r\n";
        }

    log_activity("{$lang['export']} metadata field options - field {$field['ref']}", LOG_CODE_DOWNLOADED);
    
    if($send_headers)
        {
        header('Content-type: application/octet-stream');
        header("Content-disposition: attachment; filename=field{$field['ref']}_nodes_export.txt");

        echo $return;

        ob_flush();
        exit();
        }

    return $return;
    }


function memdebug()
    {
     debug("BANG: Memory usage from <em>memory_get_usage()</em>: " . round(memory_get_usage(true) / 1024) . "KB");
     debug("BANG: Memory usage from <em>tasklist</em>: " . round(memory_get_process_usage(true) / 1024) . "KB");

    }

 
/**
 * Returns memory usage from /proc<PID>/status in bytes.
 *
 * @return int|bool sum of VmRSS and VmSwap in bytes. On error returns false.
 */
function memory_get_process_usage()
{
    $status = file_get_contents('/proc/' . getmypid() . '/status');
    
    $matchArr = array();
    preg_match_all('~^(VmRSS|VmSwap):\s*([0-9]+).*$~im', $status, $matchArr);
    
    if(!isset($matchArr[2][0]) || !isset($matchArr[2][1]))
    {
        return false;
    }
    
    return intval($matchArr[2][0]) + intval($matchArr[2][1]);
}