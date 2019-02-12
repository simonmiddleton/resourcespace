<?php

# This script is useful if you've added an exiftool field mapping and would like to update RS fields with the original file information 
# for all your resources.

include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/resource_functions.php";
include "../../include/image_processing.php";

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) != 'cli')
	{
	include "../../include/authenticate.php";
	if (!checkperm("a")) {exit("Permission denied");}
    header("Content-type: text/plain");
    set_time_limit(0);
    # ex. pages/tools/update_exiftool_field.php?fieldrefs=75,3&blanks=true
    $fieldrefs = getvalescaped("fieldrefs",0);
    if ($fieldrefs==0)
        {
        echo "Please add a list of field IDs to the fieldrefs url parameter, which are the ref numbers of the fields that you would like exiftool to extract from." . PHP_EOL . PHP_EOL;
        echo "Examples:-". PHP_EOL. PHP_EOL;
        echo "   pages/tools/update_exiftool_field.php?fieldrefs=18" . PHP_EOL;
        echo "   - This will update field 18 (usually description/caption) for all resources." . PHP_EOL;
        echo "     If metadata is already present for a resource it will be left unchanged." . PHP_EOL . PHP_EOL;
        echo "   pages/tools/update_exiftool_field.php?fieldrefs=8&col=678&blanks=true&overwrite " . PHP_EOL;
        echo "   - This will update field 8 (usually title) for resources in collection 678. Existing values will be overwritten." . PHP_EOL;
        echo "     If no embedded metadata is present the field will be cleared." . PHP_EOL . PHP_EOL;
        echo "   pages/tools/update_exiftool_field.php?fieldrefs=75,3&blanks=false&overwrite=true " . PHP_EOL;
        echo "   - This will update fields 3 and 75 for all resources. Existing values will be overwritten only if there is embedded metadata present." . PHP_EOL;
        exit();
        }
    $blanks = getval("blanks","true") == "true"; // if new value is blank, it will replace the old value.
    $fieldrefs = explode(",",$fieldrefs);
    $collectionid = getvalescaped("col", 0, true);
    $overwrite = getvalescaped("overwrite","") != "";  // If true and field already has value it will overwrite the existing value
	}
else
	{
    $shortopts = "f:c:b:o";
    $longopts = array("fieldrefs:","blanks::","col::","overwrite");
    $clargs = getopt($shortopts,$longopts);
    //exit(print_r($clargs));
    
    if (!isset($clargs["fieldrefs"]) && !isset($clargs["f"]))
        {
        echo "Usage: php update_exiftool_field.php [FIELD REFS] [OPTIONS]" . PHP_EOL . PHP_EOL;
        echo "Required arguments" . PHP_EOL;
        echo "-f, --fieldrefs         A list of field IDs as the fieldrefs arguments i.e. --fieldrefs <comma separated list of numbers>". PHP_EOL;
        echo "                        These are the ref numbers of the fields that you would like exiftool to extract from." . PHP_EOL;
        echo "Optional arguments:-" . PHP_EOL;
        echo "-c, --col               ID of collection. If specified Only resouces in this collection will be updated". PHP_EOL;
        echo "-b, --blanks            true|false. Should existing data be wiped for resources where the file has no metadata present for the associated tag?" . PHP_EOL;
        echo "-o, --overwrite         Overwrite existing data by embedded metadata? Wil be false if not passed" . PHP_EOL . PHP_EOL;
        echo "Examples:-". PHP_EOL;
        echo "   php update_exiftool_field.php --fieldrefs 18" . PHP_EOL;
        echo "   - This will update field 18 (usually description/caption) for all resources." . PHP_EOL;
        echo "     If metadata is already present for a resource it will be left unchanged." . PHP_EOL . PHP_EOL;
        echo "   php update_exiftool_field.php --fieldrefs 8 --col=678 --blanks=true --overwrite " . PHP_EOL;
        echo "   - This will update field 8 (usually title) for resources in collection 678. Existing values will be overwritten." . PHP_EOL;
        echo "     If no embedded metadata is present the field will be cleared." . PHP_EOL . PHP_EOL;
        echo "   php update_exiftool_field.php --fieldrefs 75,3 --blanks=false --overwrite " . PHP_EOL;
        echo "   - This will update fields 3 and 75 for all resources. Existing values will be overwritten only if there is embedded metadata present." . PHP_EOL;
        exit();
        }
        
    $fieldrefs = isset($clargs["fieldrefs"]) ? explode(",",$clargs["fieldrefs"]) : explode(",",$clargs["f"]);
    $collectionid = (isset($clargs["col"]) && is_numeric($clargs["col"])) ? $clargs["col"] : ((isset($clargs["c"]) && is_numeric($clargs["c"])) ? $clargs["c"] : 0);
    $blanks = ((isset($clargs["blanks"]) && strtolower($clargs["blanks"])=="false") || isset($clargs["b"]) && strtolower($clargs["b"])=="false") ? FALSE : TRUE; 
    $overwrite = isset($clargs["overwrite"]) || isset($clargs["o"]); 
    }    

$exiftool_fullpath = get_utility_path("exiftool");
if ($exiftool_fullpath==false) {die ("Could not find Exiftool.");}


foreach ($fieldrefs as $fieldref)
    {
    $fieldref_info= sql_query("select exiftool_field,exiftool_filter,title,resource_type,name from resource_type_field where ref='$fieldref'");
    if (!isset($fieldref_info[0])){die("field $fieldref doesn't exist");}
    $title=$fieldref_info[0]["title"];
    $name=$fieldref_info[0]["name"];
    $exiftool_filter=$fieldref_info[0]["exiftool_filter"];
    $exiftool_tag=$fieldref_info[0]["exiftool_field"];
    $field_resource_type=$fieldref_info[0]["resource_type"];
    
    if ($exiftool_tag=="")
        {
        die ("Please add an exiftool mapping to your $title Field");
        }
    
    echo PHP_EOL . "Updating RS Field " . $fieldref . " - " . $title . ", with exiftool extraction of: " . $exiftool_tag . PHP_EOL;
    
    $join="";
    $condition = "";
    $conditionand = "";
    if ($collectionid != 0)
        {
        $join=" inner join collection_resource on collection_resource.resource=resource.ref "; 
        $condition = "where collection_resource.collection = '$collectionid' ";
        $conditionand = "and collection_resource.collection = '$collectionid' ";
        }
    
    if($field_resource_type==0)
        {
        $rd=sql_query("select ref,file_extension from resource $join $condition order by ref");
        }
    else
        {
        $rd=sql_query("select ref,file_extension from resource $join where resource_type=$field_resource_type $conditionand order by ref");
        }	
    $exiftool_tags=explode(",",$exiftool_tag);
    for ($n=0;$n<count($rd);$n++)
        {
        $ref=$rd[$n]['ref'];
        $extension=$rd[$n]['file_extension'];
        
        $image=get_resource_path($ref,true,"",false,$extension);
        if (file_exists($image))
            {
            echo "Checking Resource " . $ref . PHP_EOL;
            if(!$overwrite)
                {
                $existing = get_data_by_field($ref,$fieldref);
                if(trim($existing) != "")
                    {
                    echo "Resource $ref already has data present in the field $fieldref : $existing. Skipping.." . PHP_EOL;
                    continue;    
                    }
                }
                
            foreach ($exiftool_tags as  $exiftool_tag) 
                {	
                $command = $exiftool_fullpath . " -s -s -s -" . $exiftool_tag . " " . escapeshellarg($image);
                echo $command . PHP_EOL;
                $value = iptc_return_utf8(trim(run_command($command)));
                
                $plugin="../../plugins/exiftool_filter_" . $name . ".php";
                if ($exiftool_filter!="")
                    {
                    eval($exiftool_filter);
                    }
                if (file_exists($plugin))
                    {
                    include $plugin;
                    }
                
                if ($blanks)
                    {
                    update_field($ref,$fieldref,$value);
                    echo ("Updated Resource " . $ref . PHP_EOL . "-Exiftool found \"" . $value . "\" embedded in the -" . $exiftool_tag . " tag and applied it to ResourceSpace Field " . $fieldref . PHP_EOL);
                    }
                else
                    {
                    if (trim($value) != "")
                        {
                        update_field($ref,$fieldref,$value);
                        echo ("Updated Resource " . $ref . PHP_EOL . "-Exiftool found \"" . $value . "\" embedded in the -" . $exiftool_tag . " tag and applied it to ResourceSpace Field " . $fieldref . PHP_EOL . PHP_EOL);	
                        }
                    }
                }
            }
        echo PHP_EOL;
        }
    }
echo "...done.". PHP_EOL;


