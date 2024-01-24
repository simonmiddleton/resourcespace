<?php

include_once __DIR__ . "/../../../include/db.php";
include_once __DIR__ . "/../../../include/authenticate.php";
include_once __DIR__ . "/../../../include/image_processing.php";

if(!$cropper_transform_original)
    {
    exit(escape($lang['error-permissiondenied']));
    }

# Locate imagemagick.
if (!isset($imagemagick_path))
    {
    echo escape($lang['error-crop-imagemagick-not-configured']);
    exit;
    }

// Verify that the requested collection is valid
$collection = getval('collection',0,true);
$usercollections=get_user_collections($userref);
$errormessages = [];

if (!in_array($collection,array_column($usercollections,"ref")))
    {
    exit(escape( $lang["error-collectionnotfound"]));
    }

// Retrieve a list of all resources in the collection:
$resources = get_collection_resources($collection);
if (count($resources) == 0)
    {
    $errormessages[] = $lang['no_resources_found'];
    }

$doit = getval('doit',0,true) != 0;
if ($doit && enforcePostRequest(TRUE) && count($resources) > 0)
    {
    $rotation = getval('rotation',0,true) % 360;
    $tfactions = ["r" . $rotation]; // Single rotation only enabled

    $successcount = 0;
    $failcount = 0;

    $batchtempdir = get_temp_dir(false,'') . "/transform_batch/";
    if(!is_dir($batchtempdir))
        {
        // If it does not exist, create it.
        mkdir($batchtempdir, 0777);
        }

    foreach($resources as $resource)
        {
        $edit_access=get_edit_access($resource);
        if (!$edit_access)
            {
            $errormessages[] = $lang["resourceid"] . " " . $resource . ": " . $lang["error-permissiondenied"];
            $failcount++;
            continue;
            }
        else
            {
            $resdata = get_resource_data($resource);
            if(!in_array(strtoupper($resdata["file_extension"]),$cropper_formatarray))
                {
                $failcount++;
                $errormessages[] = $lang["resourceid"] . " " . $resource . ": " . str_replace("%EXTENSION",strtoupper($resdata["file_extension"]),$lang["filetypenotsupported"]);
                continue;
                }
            $origpath = get_resource_path($resource, true,'',false,$resdata["file_extension"]);
            $crop_temp_file = $batchtempdir . $resource . "_" . md5($resource . $userref . date("Ymd",time()) . $scramble_key) . "." . $resdata["file_extension"];

            // Perform the actual transformation to create the new preview source
            $generated = transform_file($origpath,$crop_temp_file, ["tfactions"=>$tfactions]);

            if($generated)
                {
                debug("Saving original file and renaming " . $crop_temp_file . " to " . $origpath);
                save_original_file_as_alternative($resource);
                $copied = rename($crop_temp_file,$origpath);
                if($copied)
                    {
                    debug("Renamed " . $crop_temp_file . " to " . $origpath);
                    create_previews($resource,false,$resdata["file_extension"]);
                    }
                $successcount++;
                }
            else
                {
                $errormessages[] = $lang["resourceid"] . " " . $resource . ": " . $lang['not-transformed'];
                $failcount++;
                }
            }
        }

    if ($successcount > 0)
        {
        collection_log($collection,'b',''," ($successcount)");
        }

    $qty_total = count($resources);
    switch ($qty_total)
        {
        case 1:
            $messages[] =  $lang['resources_in_collection-1'];
            break;
        default:
            $messages[] =  str_replace("%qty", $qty_total, $lang['resources_in_collection-2']);
            break;
        }
    switch ($successcount)
        {
        case 0:
            $messages[] =  $lang['resources_transformed_successfully-0'];
            break;
        case 1:
            $messages[] =  $lang['resources_transformed_successfully-1'];
            break;
        default:
            $messages[] =  str_replace("%qty", $successcount, $lang['resources_transformed_successfully-2']);
            break;
        }
    switch ($failcount)
        {
        case 0:
            break;
        case 1:
            $messages[] =  $lang['errors-1'];
            break;
        default:
            $messages[] =  str_replace("%qty", $failcount, $lang['errors-2']);
            break;
        }
    exit(json_encode(array_merge($messages,$errormessages)));
    }

include __DIR__ . "/../../../include/header.php";
?>

<script>
    function batch_rotate(collection,rotation)
        {
        CentralSpaceShowProcessing();
        jQuery.ajax({
            type: 'POST',
            url: '<?php echo $baseurl_short; ?>plugins/transform/pages/collection_transform.php',
            data: {
                ajax : true,
                collection : collection,
                rotation : rotation,
                doit : 1,
                <?php echo generateAjaxToken("processTileChange"); ?>
                },
            })
        .done(function(data, textStatus, jqXHR )
            {
            CentralSpaceHideProcessing();
            CollectionDivLoad(baseurl_short + 'pages/collections.php');
            response = jqXHR.responseText;
            if(isJson(response))
                {
                response = JSON.parse(response).join("<br/>");
                }
            jQuery('#batch_transform_log').html(response).show();
            })
        .fail(function(jqXHR, textStatus, errorThrown)
            {
            let response = typeof jqXHR.responseJSON.data.message !== 'undefined'
                ? jqXHR.responseJSON.data.message
                : textStatus;
            styledalert('<?php echo escape($lang['error']); ?>',errorThrown + response);
            });
        }
</script>
<div class="BasicsBox">
    <h1><?php echo escape($lang['batchtransform']); ?></h1>
    <p><strong><?php echo escape($lang['batchtransform-introtext']); ?></strong></p>
    <form name='batchtransform' onsubmit='batch_rotate(<?php echo (int) $collection ?>,jQuery("#rotation").val());return false;' action='<?php echo $baseurl_short?>plugins/transform/pages/collection_transform.php' >
        <input type='hidden' name='doit' value='1' />
        <input type='hidden' name='collection' value='<?php echo (int) $collection ?>' />
        <?php generateFormToken("batchtransform"); ?>

        <?php echo escape($lang['rotation']); ?>:<br />
        <select id='rotation' name='rotation'>
            <option value='90'><?php echo escape($lang['rotation90']); ?></option>
            <option value='180'><?php echo escape($lang['rotation180']); ?></option>
            <option value='270'><?php echo escape($lang['rotation270']); ?></option>
        </select>
        <br /><br />
        <input type="submit" value="<?php echo escape($lang['transform']) ?>" />
    </form>
</div>
<div class="MessageBox" style="display:none;" id="batch_transform_log"></div>


<?php
include "../../../include/footer.php";
