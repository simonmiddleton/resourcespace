<?php
include __DIR__ . '/../../../include/db.php';

command_line_only();

logScript("OpenAI GPT plugin - process_existing.php script...");

$collections    = [];
$field          = 0;

$cli_short_options = 'c:f:';
$cli_long_options  = array('collection:','field:');
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

            $collections[] = $option_value;
            }
        if(in_array($option_name, array('f', 'field')))
            {
            $field = (int) $option_value;
            }
        }
    }

$collections = array_filter($collections,"is_int_loose");
$fielddata = get_resource_type_field($field);
$source_field = $fielddata["openai_gpt_input_field"];
$allstates = get_workflow_states();
$toprocess = [];
$allresources = do_search("!hasdata" . $field,'','',implode(",",$allstates),-1,'desc',true,NULL,true,false,'',false,false,true);
if(empty($collections))
    {
    $toprocess = array_column($allresources,"ref");
    }
else
    {
    $resources = [];
    foreach($collections as $collection)
        {
        $collection_resources = get_collection_resources($collection);
        $resources = array_merge($resources, $collection_resources);
        }
        
    $toprocess = array_intersect(array_unique($resources),array_column($allresources,"ref"));
    }
$nodegroups = [];
foreach($toprocess as $resource)
    {
    echo ("Processing resource #{$resource}");
    $sourceval = get_data_by_field($resource,$source_field);

   
    $updated = openai_gpt_update_field($resource,$targetfield,$updated_resources[$ref][$field["ref"]]);
    }
foreach($nodegroups as $noderesources)
    {
    echo ("Processing resource #{$resource}");
    $sourceval = get_data_by_field($resource,$source_field);

   
    $updated = openai_gpt_update_field($resource,$targetfield,$updated_resources[$ref][$field["ref"]]);
    }