<?php

include "../../../include/db.php";

include "../../../include/authenticate.php";
include "../../../include/image_processing.php";

// Fetch videos and process...
$videos = do_search("!collection" . $usercollection, '', 'collection', 0, -1, "ASC");
$offline = $offline_job_queue;
$splice_order = getval("splice_order", null);
$video_splice_format = getval("video_splice_format", null);
$video_splice_resolution = getval("video_splice_resolution", null);
$video_splice_frame_rate = getval("video_splice_frame_rate", null);
$video_splice_type = getval("video_splice_type", null);
$description = getval("question_description", null);

// The user can decide if he/ she wants to wait for the file to be transcoded or be notified when ready
$transcode_now = (getval('transcode_now', '') == 'yes' ? true : false);
if($offline && $transcode_now)
    {
    $offline = false;
    }

if (getval("splice_submit","") != "" && count($videos) > 1 && enforcePostRequest(false))
    {
    // Lets get the correct splice_order and put it into an array '$videos_reordered'
    $explode_splice = explode(",", $splice_order);
    $videos_reordered = array();#

    foreach($explode_splice as $key => $splice_ref)
        {
        $explode_splice[$key] = ltrim($splice_ref, 'splice_');
        $this_key = $explode_splice[$key];
        $the_key_i_need = array_search($this_key, array_column($videos, 'ref'));
        $videos_reordered[] = $videos[$the_key_i_need];
        }
    
    # Reset $videos to the correct order from $videos_reordered
    $videos = $videos_reordered;
    # Below works as before
    $ref = copy_resource($videos[0]["ref"]); # Base new resource on first video (top copy metadata).

    if($video_splice_format!=null && $video_splice_resolution!=null && $video_splice_frame_rate!=null)
        {       
        // Build up the ffmpeg command
        $ffmpeg_fullpath = get_utility_path("ffmpeg");
        $ffprobe_fullpath = get_utility_path("ffprobe");
        $randstring=md5(rand() . microtime());

        // Build the chosen ffmpeg command as set in the config
        $target_format_command = $ffmpeg_std_output_options[$video_splice_format]["command"];
        $target_format_extension = $ffmpeg_std_output_options[$video_splice_format]["extension"];
        $target_width = $ffmpeg_std_resolution_options[$video_splice_resolution]["width"];
        $target_height = $ffmpeg_std_resolution_options[$video_splice_resolution]["height"];
        $target_height = $ffmpeg_std_resolution_options[$video_splice_resolution]["height"];
        $target_frame_rate = $ffmpeg_std_frame_rate_options[$video_splice_frame_rate];
        $target_temp_location = get_temp_dir(false,"splice/" . $ref . "_" . md5($username . $randstring . $scramble_key));
        $target_order_count = 0;
        $target_completed_locations = array();

        foreach ($videos as $video) 
            {
            $filesource = get_resource_path($video["ref"],true,"",false,$video["file_extension"]);
            $has_no_audio = empty(run_command($ffprobe_fullpath . " -i " . escapeshellarg($filesource) . " -show_streams -select_streams a -loglevel error", true))?"_noaudio":"";  
            $target_completed_location = $target_temp_location . "/" . $target_order_count . $has_no_audio . "." . $target_format_extension; 

            $video_splice_options = $target_format_command . ' -vf "fps=' . $target_frame_rate . ',scale=' . $target_width . ':' . $target_height . ':force_original_aspect_ratio=decrease,pad=' . $target_width . ':' . $target_height . ':(ow-iw)/2:(oh-ih)/2" -sws_flags lanczos';
            $video_splice_command = $ffmpeg_fullpath . " " . $ffmpeg_global_options . " -i " . escapeshellarg($filesource) . " " . $video_splice_options . " " . $target_completed_location;

            $output=run_command($video_splice_command);

            if(!empty($has_no_audio))
                {
                $no_audio_command = $ffmpeg_fullpath . " -i " . escapeshellarg($target_completed_location) . " -f lavfi -i anullsrc -vcodec copy -acodec aac -b:a 32k -ar 22050 -ac 1 -shortest " . str_replace("_noaudio","",$target_completed_location);
                $output=run_command($no_audio_command);
                unlink($target_completed_location);
                $target_completed_location = str_replace("_noaudio","",$target_completed_location);
                }
            $target_completed_locations[] = $target_completed_location;
            $target_order_count++;
            }

        $list_file_command = "";

        foreach ($target_completed_locations as $target_completed_location) 
            {
            // Build list file contents
            $list_file_command .= "file '" . $target_completed_location . "'\n";
            }

        file_put_contents($target_temp_location . "/list.txt", $list_file_command);

        $merge_command = "ffmpeg -f concat -safe 0 -i '" . $target_temp_location . "/list.txt" . "' -c copy '" . $target_temp_location . "/merged." . $target_format_extension . "'";

        $output=run_command($merge_command);

        //echo '<pre>';echo print_r($merge_command, true);echo '</pre>';die('Died at line ' . __LINE__ . ' in ' . __FILE__);
        }
    }

// Header and splice page
include "../../../include/header.php";

?>
<div class="BasicsBox">
<h1><?php echo $lang["video-splice"]; render_help_link("plugins/video-splice");?></h1>
<p><?php echo $lang["video-splice-intro"]?></p>
<div class="RecordBox">
    <div class="RecordPanel RecordPanelLarge">
        <div class="RecordResource">
            <div id="splice_scroll">
                <div id="splice_reel" style="overflow: hidden; height: 200px !important; width:<?php echo ((count($videos)+2) * 180);?>px">
                <?php
                
                    foreach ($videos as $video)
                        {
                        if ($video["has_image"])
                            {
                            $img = get_resource_path($video["ref"], false, "thm", false, $video["preview_extension"], -1, 1, false, $video["file_modified"]);
                            }
                        else
                            {
                            $img = "../../../gfx/" . get_nopreview_icon($video["resource_type"], $video["file_extension"], true);
                            }
                            
                ?>
                <img src="<?php echo $img ?>" id="splice_<?php echo $video["ref"] ?>" class="splice_item">
                <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $form_action = generateURL($baseurl_short . "plugins/video_splice/pages/splice.php");
    ?>  
    <form method="post"
          action="<?php echo $form_action; ?>"
          id="spliceform"
          onsubmit="
            return Post(this, true);">
            <?php generateFormToken("spliceform"); ?>
            <div class="Question" id="video_splice_tool">
            <label><?php echo $lang["video_splice_order"]?></label>
            <p id="ids_in_order"></p>
            <div class="clearerleft"> </div>
            </div>
        <div class="Question" id="question_video_splice_format">
            <label><?php echo $lang["video_splice_select_output"] ?></label>
            <select class="stdwidth" name="video_splice_format" id="video_splice_format" >
            <?php
            foreach ($ffmpeg_std_output_options as $video_output_format=>$video_splice_output_command)
                {
                echo "<option value='" . htmlspecialchars(trim($video_output_format)) . "' >" . htmlspecialchars(trim($video_output_format)) . "</option>";
                }
                ?>
            </select>
        <div class="clearerleft"></div>
        </div>
        <div class="Question" id="question_video_splice_resolution">
            <label><?php echo $lang["video_splice_select_resolution"] ?></label>
            <select class="stdwidth" name="video_splice_resolution" id="video_splice_resolution" >
            <?php
            foreach ($ffmpeg_std_resolution_options as $video_output_resolution=>$video_splice_output_command)
                {
                echo "<option value='" . htmlspecialchars(trim($video_output_resolution)) . "' >" . htmlspecialchars(trim($video_output_resolution)) . "</option>";
                }
                ?>
            </select>
        <div class="clearerleft"></div>
        </div>
        <div class="Question" id="question_video_splice_frame_rate">
            <label><?php echo $lang["video_splice_select_frame_rate"] ?></label>
            <select class="stdwidth" name="video_splice_frame_rate" id="video_splice_frame_rate" >
            <?php
            foreach ($ffmpeg_std_frame_rate_options as $video_output_frame_rate=>$video_splice_output_command)
                {
                echo "<option value='" . htmlspecialchars(trim($video_output_frame_rate)) . "' >" . htmlspecialchars(trim($video_output_frame_rate)) . "</option>";
                }
                ?>
            </select>
        <div class="clearerleft"></div>
        </div>
        <div class="Question" id="question_video_save_to">
            <label><?php echo $lang["video_splice_save_to"] ?></label>
            <table cellpadding="5" cellspacing="0">
                <tbody>
                    <tr>
                        <td>
                            <input type="radio" 
                                   id="video_splice_save_new" 
                                   class="Inline video_splice_save_option" 
                                   name="video_splice_type" 
                                   value="video_splice_save_new" 
                                   checked
                                   onClick="
                                        jQuery('#video_splice_download').prop('checked', false);
                                        jQuery('#video_splice_save_export').prop('checked', false);
                                        jQuery('#question_transcode_now_or_notify_me').slideUp();
                                        jQuery('#question_description').slideDown();
                            ">
                            <label class="customFieldLabel Inline"
                                   for="video_splice_save_new"><?php echo $lang['video_splice_create_new']; ?></label>
                        </td>
                        <?php
                        ?>
                        <td>
                            <input type="radio"
                                   id="video_splice_save_export"
                                   class="Inline video_splice_save_option"
                                   name="video_splice_type"
                                   value="video_splice_save_export"
                                   onClick="
                                        jQuery('#video_splice_save_new').prop('checked', false);
                                        jQuery('#video_splice_download').prop('checked', false);
                                        jQuery('#question_description').slideUp();
                                        jQuery('#question_transcode_now_or_notify_me').slideUp();
                            ">
                            <label class="customFieldLabel Inline"
                                   for="video_splice_save_export"><?php echo $lang['video_splice_save_export']; ?></label>
                        </td>
                        <td>
                            <input type="radio"
                                   id="video_splice_download"
                                   class="Inline video_splice_save_option"
                                   name="video_splice_type"
                                   value="video_splice_download"
                                   onClick="
                                        jQuery('#video_splice_save_export').prop('checked', false);
                                        jQuery('#video_splice_save_new').prop('checked', false);
                                        jQuery('#question_description').slideUp();
                                        jQuery('#question_transcode_now_or_notify_me').slideDown();
                            ">
                            <label class="customFieldLabel Inline"
                                   for="video_splice_download"><?php echo $lang['download']; ?></label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="clearerleft"></div>
        </div>
        <div class="Question" id="question_description">
            <label for="video_splice_new_desc" ><?php echo $lang["description"]; ?></label>
            <input type="text" class="stdwidth" id="video_splice_new_desc" name="video_splice_new_desc" value="" />
            <div class="clearerleft"></div>
        </div>
            <?php
            if($offline)
                {
                ?>
                <div class="Question" id="question_transcode_now_or_notify_me" style="display:none;">
                    <label><?php echo $lang['video_splices_transcode_now_or_notify_me_label']; ?></label>
                    <table cellpadding="5" cellspacing="0">
                        <tbody>
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           id="transcode_now"
                                           class="Inline"
                                           name="transcode_now"
                                           value="yes"
                                           ">
                                    <label class="customFieldLabel Inline"
                                           for="transcode_now"><?php echo $lang['video_splices_transcode_now_label']; ?></label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="clearerleft"></div>
                </div>
                <?php
                }
                ?>
        <input type="hidden" name="splice_order" id="splice_reel_order" />
        <div class="QuestionSubmit">
             <input name="splice_submit" class="splicesubmit" type="submit" value="<?php echo $lang["action-splice"]?>" onclick="CentralSpaceShowLoading();">
             <br />
             <div class="clearerleft"> </div>
        </div>
    </form>
</div>
<script type="text/javascript">

    function ReorderResourcesInCollectionSplice(idsInOrder)
        {
        var newOrder = [];
        
        jQuery.each(idsInOrder, function() 
            {
            newOrder.push(this.substring(7));
            });
        
        jQuery.ajax(
            {
            type: 'POST',
            url: '<?php echo $baseurl_short?>pages/collections.php?collection=<?php echo urlencode($usercollection) ?>&reorder=true',
            data: 
                {
                order:JSON.stringify(newOrder),
                <?php echo generateAjaxToken('ReorderResourcesInCollectionSplice'); ?>
                },
            success: function() 
                {
                var results = new RegExp('[\\?&amp;]' + 'search' + '=([^&amp;#]*)').exec(window.location.href);
                var ref = new RegExp('[\\?&amp;]' + 'ref' + '=([^&amp;#]*)').exec(window.location.href);
                if ((ref==null)&&(results!== null)&&('<?php echo urlencode("!collection" . $usercollection); ?>' === results[1])) CentralSpaceLoad('<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!collection" . $usercollection); ?>',true);
                }
            });
        }

    function generateOrderString(orderArray){
        console.log(orderArray);
        var orderString = "";
        orderArray.forEach((ref) => { orderString = orderString + ref.substring(7) + ", "});
        // remove final ", "
        orderString = orderString.substring(0, orderString.length - 2);
        document.getElementById("ids_in_order").innerHTML = orderString;
        }

    /* Start splice reel sortable */
    jQuery("#splice_reel").sortable({ axis: "x" });

    /* Re-order collections */
    jQuery(document).ready(function() 
        {
        var idsInOrder = jQuery('#splice_reel').sortable("toArray");
        jQuery('#splice_reel_order').val(idsInOrder);
        var collection = <?php echo $usercollection; ?>;
        var k = <?php echo $k? $k : "''"; ?>;
        generateOrderString(idsInOrder);
        jQuery('#splice_reel').sortable(
            {
            axis: "x",
            helper:"clone",
            items: ".splice_item",
            stop: function(event, ui) 
                {
                var idsInOrder = jQuery('#splice_reel').sortable("toArray");
                jQuery('#splice_reel_order').val(idsInOrder);
                ReorderResourcesInCollectionSplice(idsInOrder);
                generateOrderString(idsInOrder);
                ChangeCollection(collection,k);
                }
            });
        jQuery('.CollectionPanelShell').disableSelection();
        jQuery("#CollectionDiv").on("click",".CollectionResourceRemove",function() 
            {
            var splice_id = "#splice_"+jQuery(this).closest(".CollectionPanelShell").attr("id").replace(/[^0-9]/gi,"");
            jQuery(splice_id).remove();
            });
        });

</script>

<?php include "../../../include/footer.php";