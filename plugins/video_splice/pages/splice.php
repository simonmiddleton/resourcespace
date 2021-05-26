<?php

include "../../../include/db.php";

include "../../../include/authenticate.php";
include "../include/splice_functions.php";

// Fetch videos and process...
$message = "";
$videos = do_search("!collection" . $usercollection, '', 'collection', 0, -1, "ASC", false, 0, false, false, '', false, true, true);
$videos_data = do_search("!collection" . $usercollection, '', 'collection', 0, -1, "ASC");
$offline = $offline_job_queue;
$splice_order = getval("splice_order", null);
$video_splice_video = getval("video_splice_video", null);
$video_splice_resolution = getval("video_splice_resolution", null);
$video_splice_frame_rate = getval("video_splice_frame_rate", null);
$video_splice_audio = getval("video_splice_audio", null);
$video_splice_type = getval("video_splice_type", null);
$description = getval("video_splice_new_desc", "");

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
    $videos_reordered = array();
    $videos_data_reordered = array();

    foreach($explode_splice as $key => $splice_ref)
        {
        $explode_splice[$key] = ltrim($splice_ref, 'splice_');
        $this_key = $explode_splice[$key];
        $the_key_i_need = array_search($this_key, array_column($videos, 'ref'));
        $videos_reordered[] = $videos[$the_key_i_need];
        $videos_data_reordered[] = $videos_data[$the_key_i_need];
        }
    
    # Reset $videos to the correct order from $videos_reordered
    $videos = $videos_reordered;
    $videos_data = $videos_data_reordered;

    if($video_splice_video!=null && $video_splice_resolution!=null && $video_splice_frame_rate!=null)
        {       
        // Build the chosen ffmpeg command as set in the config
        $target_video_command = $ffmpeg_std_video_options[$video_splice_video]["command"];
        $target_video_extension = $ffmpeg_std_video_options[$video_splice_video]["extension"];
        $target_audio = $ffmpeg_std_audio_options[$video_splice_audio]["command"];
        $target_width = $ffmpeg_std_resolution_options[$video_splice_resolution]["width"];
        $target_height = $ffmpeg_std_resolution_options[$video_splice_resolution]["height"];
        $target_frame_rate = $ffmpeg_std_frame_rate_options[$video_splice_frame_rate]["value"];
        
        if ($video_splice_type == "video_splice_save_new") 
            {
            if($offline)
                { 
                // Add this to the job queue for offline processing
                $generate_merged_video_job_data = array(
                    'videos' => $videos,
                    'video_splice_type' => $video_splice_type,
                    'target_video_command' => $target_video_command,
                    'target_video_extension' => $target_video_extension,
                    'target_audio' => $target_audio,
                    'target_width' => $target_width,
                    'target_height' => $target_height,
                    'target_frame_rate' => $target_frame_rate,
                    'description' => $description
                );
                $link_holder = '<a href="' . generateURL($baseurl . '/pages/view.php', array('ref' => $ref)) . '">' . $ref . '</a> ';
                $generate_merged_video_job_success_text = str_replace("%link", $link_holder, $lang["video_splice_new_completed"]);;
                $generate_merged_video_job_failure_text = "fail";

                $jobadded = job_queue_add('generate_merged_video', $generate_merged_video_job_data, '', '', $generate_merged_video_job_success_text, $generate_merged_video_job_failure_text);

                $message = str_replace("%job", $jobadded, $lang["video_splice_offline_notice"]);  
                }
            else
                {               
                $ref = generate_merged_video(
                    $videos,
                    $video_splice_type,
                    $target_video_command,
                    $target_video_extension,
                    $target_audio,
                    $target_width,
                    $target_height,
                    $target_frame_rate,
                    $description
                    ); 

                $link_holder = '<a href="' . generateURL($baseurl . '/pages/view.php', array('ref' => $ref)) . '">' . $ref . '</a> ';
                $message = str_replace("%link", $link_holder, $lang["video_splice_new_completed"]);         
                }
            }
        elseif($video_splice_type == "video_splice_save_export")
            {
            // If $video_export_folder is set use it.
            if(isset($video_export_folder))
                {
                // Make sure the video_export_folder dir exists.
                if(!is_dir($video_export_folder))
                    {
                    // If it does not exist, create it.
                    mkdir($video_export_folder, 0777);
                    }
                }
            else
                {
                return false;
                }

            if($offline)
                { 
                // Add this to the job queue for offline processing
                $generate_merged_video_job_data = array(
                    'videos' => $videos,
                    'video_splice_type' => $video_splice_type,
                    'target_video_command' => $target_video_command,
                    'target_video_extension' => $target_video_extension,
                    'target_audio' => $target_audio,
                    'target_width' => $target_width,
                    'target_height' => $target_height,
                    'target_frame_rate' => $target_frame_rate,
                    'description' => $description
                );
                $generate_merged_video_job_success_text = str_replace("%location", $video_export_folder, $lang["video_splice_export_completed"]);
                $generate_merged_video_job_failure_text = "fail";

                $jobadded = job_queue_add('generate_merged_video', $generate_merged_video_job_data, '', '', $generate_merged_video_job_success_text, $generate_merged_video_job_failure_text);

                $message = str_replace("%job", $jobadded, $lang["video_splice_offline_notice"]);  
                }
            else
                {               
                $success = generate_merged_video(
                    $videos,
                    $video_splice_type,
                    $target_video_command,
                    $target_video_extension,
                    $target_audio,
                    $target_width,
                    $target_height,
                    $target_frame_rate,
                    $description
                    ); 

                if($success)
                    {
                    $message = str_replace("%location", $video_export_folder, $lang["video_splice_export_completed"]);   
                    }      
                }
            }
        elseif($video_splice_type == "video_splice_download")
            {
            if($offline)
                { 
                // Add this to the job queue for offline processing
                $generate_merged_video_job_data = array(
                    'videos' => $videos,
                    'video_splice_type' => $video_splice_type,
                    'target_video_command' => $target_video_command,
                    'target_video_extension' => $target_video_extension,
                    'target_audio' => $target_audio,
                    'target_width' => $target_width,
                    'target_height' => $target_height,
                    'target_frame_rate' => $target_frame_rate,
                    'description' => $description
                );
                $generate_merged_video_job_success_text = str_replace("%location", $video_export_folder, $lang["video_splice_export_completed"]);
                $generate_merged_video_job_failure_text = "fail";

                $jobadded = job_queue_add('generate_merged_video', $generate_merged_video_job_data, '', '', $generate_merged_video_job_success_text, $generate_merged_video_job_failure_text);

                $message = str_replace("%job", $jobadded, $lang["video_splice_offline_notice"]);  
                }
            else
                {               
                $success = generate_merged_video(
                    $videos,
                    $video_splice_type,
                    $target_video_command,
                    $target_video_extension,
                    $target_audio,
                    $target_width,
                    $target_height,
                    $target_frame_rate,
                    $description
                    ); 

                if($success)
                    {
                    $message = str_replace("%location", $video_export_folder, $lang["video_splice_export_completed"]);   
                    }      
                }
            }
        }
    }

// Header and splice page
include "../../../include/header.php";

?>
<div class="BasicsBox">
<h1><?php echo $lang["video-splice"]; render_help_link("plugins/video-splice");?></h1>
<p><?php echo $lang["video-splice-intro"]?></p>
<?php
    if ($message!="")
        {
        echo "<div class=\"PageInformal\"><i class='fa fa-fw fa-check-square'></i>&nbsp;" . $message . "</div>";
        }
?>
<div class="RecordBox">
    <div class="RecordPanel RecordPanelLarge">
        <div class="RecordResource">
            <div id="splice_scroll">
                <div id="splice_reel" style="overflow: hidden; height: 200px !important; width:<?php echo ((count($videos)+2) * 180);?>px">
                <?php
                
                    foreach ($videos_data as $video_data)
                        {
                        if ($video_data["has_image"])
                            {
                            $img = get_resource_path($video_data["ref"], false, "thm", false, $video_data["preview_extension"], -1, 1, false, $video_data["file_modified"]);
                            }
                        else
                            {
                            $img = "../../../gfx/" . get_nopreview_icon($video_data["resource_type"], $video_data["file_extension"], true);
                            }
                            
                ?>
                <img src="<?php echo $img ?>" id="splice_<?php echo $video_data["ref"] ?>" class="splice_item">
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
        <div class="Question" id="question_video_splice_video">
            <label><?php echo $lang["video_splice_select_video"] ?></label>
            <select class="stdwidth" name="video_splice_video" id="video_splice_video" >
            <?php
            foreach ($ffmpeg_std_video_options as $video_output_video=>$video_splice_output_command)
                {
                echo "<option value='" . htmlspecialchars(trim($video_output_video)) . "' " . (isset($ffmpeg_std_video_options[$video_output_video]["default"])?"selected":"") . ">" . htmlspecialchars(trim($video_output_video)) . "</option>";
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
                echo "<option value='" . htmlspecialchars(trim($video_output_resolution)) . "' " . (isset($ffmpeg_std_resolution_options[$video_output_resolution]["default"])?"selected":"") . ">" . htmlspecialchars(trim($video_output_resolution)) . "</option>";
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
                echo "<option value='" . htmlspecialchars(trim($video_output_frame_rate)) . "' " . (isset($ffmpeg_std_frame_rate_options[$video_output_frame_rate]["default"])?"selected":"") . ">" . htmlspecialchars(trim($video_output_frame_rate)) . "</option>";
                }
                ?>
            </select>
        <div class="clearerleft"></div>
        </div>
        <div class="Question" id="question_video_splice_audio">
            <label><?php echo $lang["video_splice_select_audio"] ?></label>
            <select class="stdwidth" name="video_splice_audio" id="video_splice_audio" >
            <?php
            foreach ($ffmpeg_std_audio_options as $video_output_audio=>$video_splice_output_command)
                {
                echo "<option value='" . htmlspecialchars(trim($video_output_audio)) . "' " . (isset($ffmpeg_std_audio_options[$video_output_audio]["default"])?"selected":"") . ">" . htmlspecialchars(trim($video_output_audio)) . "</option>";
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
                    <label><?php echo $lang['video_splice_transcode_now_or_notify_me_label']; ?></label>
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
                                           for="transcode_now"><?php echo $lang['video_splice_transcode_now_label']; ?></label>
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
             <input name="splice_submit" class="spliceubmit" type="submit" value="<?php echo $lang["action-splice"]?>" onclick="CentralSpaceShowLoading();">
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