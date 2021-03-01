<?php
include "../../include/db.php";

if (php_sapi_name() != "cli")
    {
    exit("Permission denied");
    }


// This script moves resources from old locations to new locations if settings are changed after initial setup
// Useful in in the following situations :-
//
// 1) When adding or changing the $scramble_key (setting $migrating_scrambled = true and optionally $scramble_key_old)
// 2) When enabling $filestore_evenspread 

if(!$filestore_evenspread && !$migrating_scrambled)
    {
    exit("You must manually enable this script by setting \$migrating_scrambled and optionally \$scramble_key_old." . PHP_EOL);
    }
elseif(!$filestore_evenspread || !$filestore_migrate)
    {
    exit("\$filestore_evenspread and filestore_migrate must be enabled. Set \$filestore_evenspread=true; and \$filestore_migrate=true; in config.php" . PHP_EOL);     
    }

// Flag to set whether we are migrating to even out filestore distibution or because of scramble key change
$redistribute_mode = $filestore_migrate;

function migrate_files($ref, $alternative, $extension, $sizes, $redistribute_mode)
    {
    global $scramble_key, $scramble_key_old, $migratedfiles, $filestore_evenspread, $syncdir;
    echo "Checking Resource ID: " . $ref . ", alternative: " . $alternative . PHP_EOL;
	$resource_data=get_resource_data($ref);
    $pagecount=get_page_count($resource_data,$alternative);
    for($page=1;$page<=$pagecount;$page++)
		{
        for ($m=0;$m<count($sizes);$m++)
            {
            // Get the new path for each file
            $newpath=get_resource_path($ref,true,$sizes[$m]["id"],true,$sizes[$m]["extension"],true,$page,false,'',$alternative);

            // Use old settings to get old path before migration and migrate if found. Save the current key/option first
            if($redistribute_mode)
                {
                $filestore_evenspread = false;
                }
            else
                {
                $scramble_key_saved = $scramble_key;
                $scramble_key = isset($scramble_key_old) ? $scramble_key_old : "";
                }        
                
            $path = get_resource_path($ref,true,$sizes[$m]["id"],false,$sizes[$m]["extension"],true,$page,false,'',$alternative);
            echo " - Size: " . $sizes[$m]["id"] . ", extension: " . $sizes[$m]["extension"] . " Snew path: " . $newpath . PHP_EOL;
            echo " - Checking old path: " . $path . PHP_EOL;
            if (file_exists($path) && !($sizes[$m]["id"] == "" && strpos($path, $syncdir)!==false))
                {
                echo " - Found file at old path : " . $path . PHP_EOL;	
                if(!file_exists($newpath))
                    {
                    echo " - Moving resource file for resource #" . $ref  . " - old path= " . $path  . ", new path=" . $newpath . PHP_EOL;
                    if(!file_exists(dirname($newpath)))
                        {
                        mkdir(dirname($newpath),0777,true);
                        }
                    rename ($path,$newpath);
                    $migratedfiles++;
                    }
                else
                    {
                    echo " - Resource file for resource #" . $ref  . " - already exists at new path= " . $newpath  . PHP_EOL;
                    }
                }
                
            // Reset key/evenspread value before next 
            if($redistribute_mode)
                {
                $filestore_evenspread = true;
                $filestore_migrate = true;
                }
            else
                {
                $scramble_key = $scramble_key_saved;
                $migrating_scrambled = true;
                }
            }
        }

    // Clear old directory if empty
    $delfolder = dirname($path);
    $newfolder = dirname($newpath);
    if(file_exists($delfolder) && $delfolder != $newfolder && count(scandir($delfolder))==2 && is_writable($delfolder))
        {       
        echo "Deleting folder $delfolder \n";
        rmdir($delfolder);
        }

    }

set_time_limit(0);

$resources=sql_query("SELECT ref,file_extension FROM resource WHERE ref>0 ORDER BY ref DESC");
$migratedfiles = 0;
$totalresources = count($resources);
for ($n=0;$n<$totalresources;$n++)
	{
	$ref=$resources[$n]["ref"];
	$extension=$resources[$n]["file_extension"];
	if ($extension=="") {$extension="jpg";}
	$sizes=get_image_sizes($ref,true,$extension,false);
    
    // Add in original resource files, jpg preview, ffmpeg previews and other non-size files
    $sizes[] = array("id" => "", "extension" => $extension);
    $sizes[] = array("id" => "pre", "extension" => $ffmpeg_preview_extension);
    $sizes[] = array("id" => "", "extension" => "jpg");
    $sizes[] = array("id" => "", "extension" => "xml");
    $sizes[] = array("id" => "", "extension" => "icc");
    
    migrate_files($ref, -1, $extension, $sizes, $redistribute_mode);
    
    // Migrate the alternatives
    $alternatives = get_alternative_files($ref);
    foreach($alternatives as $alternative)
        {
        $sizes=get_image_sizes($ref,true,$alternative["file_extension"],false);
        $sizes[] = array("id" => "", "extension" => $alternative["file_extension"]);
        migrate_files($ref, $alternative["ref"], $alternative["file_extension"], $sizes, $redistribute_mode);
        }
    }
    
exit("FINISHED. " . $migratedfiles . " files migrated for " . $totalresources . " resources" . PHP_EOL);
