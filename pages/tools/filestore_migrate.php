<?php
include "../../include/db.php";
command_line_only();

// This script moves resources from old locations to new locations if settings are changed after initial setup
// Useful in in the following situations :-
//
// 1) When adding or changing the $scramble_key (setting $migrating_scrambled = true and optionally $scramble_key_old)
// 2) When enabling $filestore_evenspread 
//
// Script will run in dry run mode by default. Supply "commit" as a parameter to the command to apply the changes in the filestore.
// e.g. filestore_migrate.php commit

$config_check = !($filestore_evenspread && $filestore_migrate && $migrating_scrambled) && (($filestore_evenspread && $filestore_migrate) || $migrating_scrambled);
if (!$config_check)
    {
    exit("You must manually enable this script by setting \$migrating_scrambled and optionally \$scramble_key_old or by setting \$filestore_evenspread and \$filestore_migrate." . PHP_EOL . 
    "Only one operation is possible at a time. " . PHP_EOL);
    }

if (isset($argv[1]) && $argv[1] == 'commit')
    {
    $dry_run = false;
    echo 'Script running without dry run - the following changes have been applied in the filestore.' . PHP_EOL;
    }
else
    {
    $dry_run = true;
    echo 'Script in dry run mode - changes to be made will be shown here but not applied to the filestore.' . PHP_EOL;
    echo 'To run the script and apply the changes add "commit" to the command e.g. filestore_migrate.php commit' . PHP_EOL;
    }

if (in_array("rse_version", $plugins)) 
    {
    # Don't run script with rse_version enabled - it'll filter out alt files from Replace file so they'll be left behind in the old location.
    $rse_version_found = array_search("rse_version",$plugins);
    if ($rse_version_found !== false)
        {
        unset($plugins[$rse_version_found]);
        }
    }

// Flag to set whether we are migrating to even out filestore distibution or because of scramble key change
$redistribute_mode = $filestore_migrate;

# Prevent get_resource_path() attempting to migrate the file as we'll do it in this script when not in dry run mode.
$filestore_migrate = false;
$migrating_scrambled = false;

function migrate_files($ref, $alternative, $extension, $sizes, $redistribute_mode, bool $dry_run = true)
    {
    global $scramble_key, $scramble_key_old, $migratedfiles, $filestore_evenspread, $syncdir, $filestore_migrate, $migrating_scrambled;
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
            if (file_exists($path) && !($sizes[$m]["id"] == "" && $syncdir != "" && strpos($path, $syncdir) !== false))
                {
                echo " - Found file at old path : " . $path . PHP_EOL;  
                if(!file_exists($newpath))
                    {
                    echo " - Moving resource file for resource #" . $ref  . " - old path= " . $path  . ", new path=" . $newpath . PHP_EOL;
                    if (!$dry_run)
                        {
                        if(!file_exists(dirname($newpath)))
                            {
                            mkdir(dirname($newpath),0777,true);
                            }
                        rename ($path,$newpath);
                        }
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
                }
            else
                {
                $scramble_key = $scramble_key_saved;
                }
            }
        }

    // Clear old directory if empty
    $delfolder = dirname($path);
    $newfolder = dirname($newpath);
    if(file_exists($delfolder) && $delfolder != $newfolder && count(scandir($delfolder))==2 && is_writable($delfolder))
        {       
        echo "Deleting folder $delfolder \n";
        if (!$dry_run)
            {
            rmdir($delfolder);
            }
        }

    }

set_time_limit(0);

$resources=ps_query("SELECT ref,file_extension FROM resource WHERE ref>0 ORDER BY ref DESC");
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
    
    migrate_files($ref, -1, $extension, $sizes, $redistribute_mode, $dry_run);
    
    // Migrate the alternatives
    $alternatives = get_alternative_files($ref);
    foreach($alternatives as $alternative)
        {
        $sizes=get_image_sizes($ref,true,$alternative["file_extension"],false);
        $sizes[] = array("id" => "", "extension" => $alternative["file_extension"]);
        migrate_files($ref, $alternative["ref"], $alternative["file_extension"], $sizes, $redistribute_mode, $dry_run);
        }
    }
    
exit("FINISHED. " . $migratedfiles . " files migrated for " . $totalresources . " resources" . PHP_EOL);
