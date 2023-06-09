<?php
include __DIR__ . '/../../../include/db.php';

command_line_only();

if(!in_array("openai_gpt",$plugins))
    {
    exit("OpenAI GPT plugin not enabled. Exiting\n");
    }

$collections    = [];
$targetfield    = 0;
$overwrite      = false;
$collectionset  = false;

$help_text = "\n    NAME
    process_existing.php - update openai_gpt derived fields from existing data.

    SYNOPSIS
    php process_existing.php [OPTIONS...]

    DESCRIPTION
    Used to update fields that are populated by the openai_gpt data from existing data.

    OPTIONS SUMMARY

    --help          Display this help text and exit
     
    -c          
    --collection    Collection ID. Only resources in the specified collections will be updated
       
    -f
    --field         ID of metadata field to update

    -o
    --overwrite     Overwrite existing data in the field? Note that if overwrite is enabled and the input field
                    contains no data the target field will be cleared. False by default

    EXAMPLES

    php process_existing.php --field=18  --collection=56
    php process_existing.php --field=18  --collection=56 --overwrite
    php process_existing.php -f 18 -c56 -c77
    php process_existing.php -f 18 -c56 -c77 -o\n\n";

$cli_short_options = 'f:c::o::';
$cli_long_options  = array('field:','collection::','overwrite::');
$cli_options = getopt($cli_short_options, $cli_long_options);
if($cli_options !== false)
    {
    foreach($cli_options as $option_name => $option_value)
        {
        if(in_array($option_name, array('c', 'collection')))
            {
            if(is_array($option_value))
                {
                $collections = $option_value;
                continue;
                }
            else if((string)(int)$option_value == (string)$option_value)
                {
                $collections[] = $option_value;
                }
            $collectionset = true;
            }
        if(in_array($option_name, array('f', 'field')))
            {
            $targetfield = (int) $option_value;
            }
        if(in_array($option_name, array('o', 'overwrite')) && !in_array(strtolower($option_value),["false","no"]))
            {
            $overwrite=true;
            }
        }
    }

if($collectionset && empty($collections))
    {
    exit($help_text . "Invalid syntax. Please note that a collection ID must be specified immediately following the '-c' or '--collection' e.g -c55\n\n");
    }

if($targetfield==0)
    {
    exit($help_text);
    }

$collections = array_filter($collections,"is_int_loose");
$targetfield_data = get_resource_type_field($targetfield);

if(!$targetfield_data)
    {
    exit("Invalid field specified: # {$targetfield}\n\n");
    }
$input_field = $targetfield_data["openai_gpt_input_field"];
$input_field_data = get_resource_type_field($input_field);
if(!$input_field_data)
    {
    exit("Invalid input field for {$targetfield} : '{$input_field}'\n\n");
    }
$allstates = get_workflow_states();
$arr_toprocess = [];

echo"OpenAI GPT plugin - process_existing.php script...\n";
echo" - Overwrite existing data: " . ($overwrite ? "TRUE" : "FALSE") . "\n";
echo" - Target field : #" . $targetfield  . " - " . $targetfield_data["title"] . " (" . $targetfield_data["name"] . ")\n";
echo" - input field : #" . $input_field . " - " . $input_field_data["title"] . " (" . $input_field_data["name"] . ")\n";
echo" - Prompt : " . $targetfield_data["openai_gpt_prompt"] . "\n";
echo" - Collections : " . implode(",",$collections) . "\n";

if(!$overwrite)
    {
    $arr_allresources = do_search('!hasdata' . $input_field,'','',implode(",",$allstates),-1,'desc',true,NULL,true,false,'',false,false,true);
    }
else
    {
    // Need to process all resources, including those with no data in the source field
    $arr_allresources = do_search('','','',implode(",",$allstates),-1,'desc',true,NULL,true,false,'',false,false,true);
    }

if(empty($collections))
    {
    $arr_toprocess = array_column($arr_allresources,"ref");
    }
else
    {
    $resources = [];
    foreach($collections as $collection)
        {
        $collection_resources = get_collection_resources($collection);
        $resources = array_merge($resources, $collection_resources);
        }
    $arr_toprocess = array_intersect($resources,array_column($arr_allresources,"ref"));
    }

if(!$overwrite)
    {
    // Remove resources with data in the target field
    $arr_existingdata = do_search("!hasdata" . $targetfield,'','',implode(",",$allstates),-1,'desc',true,NULL,true,false,'',false,false,true);
    $arr_toprocess = array_diff($arr_toprocess,array_column($arr_existingdata,"ref"));
    }

echo "Found ". count($arr_toprocess) . " valid resource(s) to process\n";
flush();ob_flush();
// Sort into an array indexed by nodes so resources with the same data can be processed together
$nodegroups = [];
foreach($arr_toprocess as $resource)
    {
    $resnodes = get_resource_nodes($resource,$input_field,true,SORT_ASC);
    $nodehash = empty($resnodes) ? "BLANK" : md5(implode(",",array_column($resnodes,"ref")));
    if(!isset($nodegroups[$nodehash]))
        {
        $nodegroups[$nodehash] = [];
        $nodegroups[$nodehash]["resources"] = [];
        $nodegroups[$nodehash]["nodes"] = $resnodes;
        }
    $nodegroups[$nodehash]["resources"][] = $resource;
    }

$arr_success = [];
$arr_failure = [];
foreach($nodegroups as $nodehash=>$nodegroup)
    {
    echo "Processing resources: " . implode(",",$nodegroup["resources"]) . "\n";
    flush();ob_flush();
    $strings = ($nodehash != "BLANK" && count($nodegroup["nodes"]) > 0) ? get_node_strings($nodegroup["nodes"]) : [];
    $updated = openai_gpt_update_field($nodegroup["resources"],$targetfield_data,$strings);
    if($updated)
        {
        $arr_success = array_merge($arr_success,$nodegroup["resources"]);
        echo " - SUCCESS\n";
        }
    else
        {
        $arr_failure = array_merge($arr_failure,$nodegroup["resources"]);
        echo " - ERROR. None of the above resources were updated\n";
        }
    flush();ob_flush();
    }

$c_success = count($arr_success);
echo "\nScript finished\n";
if($c_success>0)
    {
    echo "   " . str_pad($c_success,6) .  " resources successfully updated\n";
    }

$c_failure = count($arr_failure);
if($c_failure>0)
    {
    echo "   " . str_pad($c_failure,6) .  " resources failed to update\n";
    echo "Failed resources: " . implode(",",$arr_failure);
    }