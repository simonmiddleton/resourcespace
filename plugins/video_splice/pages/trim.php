<?php
include "../../../include/db.php";

include "../../../include/authenticate.php";
include "../../../include/image_processing.php";

$ref=getvalescaped("ref","",true);
$alt=getvalescaped("alternative","",true);

$search=getvalescaped("search","");
$offset=getvalescaped("offset",0,true);
$order_by=getvalescaped("order_by","");
$archive=getvalescaped("archive","",true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$starsearch=getvalescaped("starsearch","");
$modal = (getval("modal", "") == "true");

$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$curpos=getvalescaped("curpos","");
$go=getval("go","");

$urlparams= array(
    'resource' => $ref,
    'ref' => $ref,
    'search' => $search,
    'order_by' => $order_by,
    'offset' => $offset,
    'restypes' => $restypes,
    'starsearch' => $starsearch,
    'archive' => $archive,
    'default_sort_direction' => $default_sort_direction,
    'sort' => $sort,
    'curpos' => $curpos,
    "modal" => ($modal ? "true" : ""),
);

# Fetch resource data.
$resource=get_resource_data($ref);

$editaccess = get_edit_access($ref,$resource["archive"], false,$resource);

# Not allowed to edit this resource?
if (!($editaccess || checkperm('A')) && $ref>0) {exit ("Permission denied.");}

if($resource["lock_user"] > 0 && $resource["lock_user"] != $userref)
    {
    $error = get_resource_lock_message($resource["lock_user"]);
    http_response_code(403);
    exit($error);
    }
?>

<div class="BasicsBox">
<?php
if (getval("context",false) == 'Modal'){$previous_page_modal = true;}
else {$previous_page_modal = false;}
if(!$modal)
    {
    ?>
    <p>
    <a onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateurl($baseurl . "/pages/view.php",$urlparams); ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    </p>
    <?php
    }
elseif($previous_page_modal)
    {
    $urlparams["context"]='Modal';
    ?>
    <p>
    <a onClick="return ModalLoad(this,true);" href="<?php echo generateurl($baseurl . "/pages/view.php",$urlparams); ?>"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a>
    </p>
    <?php
    }
    ?>
    <div class="RecordHeader">
        <div class="BackToResultsContainer">
            <div class="backtoresults"> 
            <?php
            if($modal)
                {
                ?>
                <a class="maxLink fa fa-expand" href="<?php echo generateURL($baseurl . "/plugins/video_splice/pages/trim.php", $urlparams, array("modal" => "")); ?>" onclick="return CentralSpaceLoad(this);"></a>
                &nbsp;<a href="#" class="closeLink fa fa-times" onclick="ModalClose();"></a>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
<?php
if(isset($resource['field'.$view_title_field]))
    {
    echo "<h2>" . htmlspecialchars(i18n_get_translated($resource['field'.$view_title_field])) . "</h2><br/>";
    }
    ?>
<h1><?php echo $lang["video-trim"]; render_help_link('plugins/video-splice');?></h1>
<div class="RecordBox">
    <div class="RecordPanel RecordPanelLarge">
        <div class="RecordResource">
        <?php
        global $video_preview_original;

        # Try to find a preview file.
        $video_preview_file = get_resource_path(
            $ref,
            true,
            'pre',
            false,
            (1 == $video_preview_hls_support || 2 == $video_preview_hls_support) ? 'm3u8' : $ffmpeg_preview_extension
        );
        # Get original file to find full duration
        $video_original_file = get_resource_path(
            $ref,
            true,
            '',
            false,
            $resource["file_extension"]
        );

        $preview_duration = get_video_duration($video_preview_file);
        $original_duration = get_video_duration($video_original_file);

        $preview_cap = $original_duration;
        if(!$video_preview_original && ($preview_duration < $original_duration))
            {
            $preview_cap = $preview_duration;
            }

        if ((!(isset($resource['is_transcoding']) && $resource['is_transcoding']!=0) && file_exists($video_preview_file)))
            {
            # Include the player if a video preview file exists for this resource.
            ?>
            <div id="previewimagewrapper">
                <?php 
                include dirname (__FILE__, 4) . "/pages/video_player.php";;
                ?>
            </div>
            <?php    
            }    

            global $context, $display;
        ?>
            <div class="video-trim-tool">
                <input type="range" id="input-start" min="0" max="<?php echo $original_duration ?>" value="0">
                <input type="range" id="input-end" min="0" max="<?php echo $original_duration ?>" value="<?php echo $original_duration ?>">

                <div class="video-trim-slider">
                    <p id="start-timestamp">00:00:00</p>
                    <div class="bar"></div>
                    <div class="selected">
                        <p id="duration-timestamp">00:00:00</p>
                    </div>
                    <div class="handle start"></div>
                    <div class="handle end"></div>
                    <p id="end-timestamp">00:00:00</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
var inputStart = document.getElementById("input-start");
var inputEnd = document.getElementById("input-end");
var handleStart = document.querySelector(".video-trim-slider > .handle.start");
var handleEnd = document.querySelector(".video-trim-slider > .handle.end");
var selected = document.querySelector(".video-trim-slider > .selected");

function setStartValue() {
    var thisInput = inputStart,
        min = parseInt(thisInput.min),
        max = parseInt(thisInput.max);
console.log(thisInput.max);
    thisInput.value = Math.min(parseInt(thisInput.value), parseInt(inputEnd.value) - 1);

    var percent = ((thisInput.value - min) / (max - min)) * 100;

    handleStart.style.left = percent + "%";
    selected.style.left = percent + "%";
    generateTimestamps();
}
setStartValue();

function setEndValue() {
    var thisInput = inputEnd,
        min = parseInt(thisInput.min),
        max = parseInt(thisInput.max);

    thisInput.value = Math.max(parseInt(thisInput.value), parseInt(inputStart.value) + 1);

    var percent = ((thisInput.value - min) / (max - min)) * 100;

    handleEnd.style.right = (100 - percent) + "%";
    selected.style.right = (100 - percent) + "%";
    generateTimestamps();
}
setEndValue();

inputStart.addEventListener("input", setStartValue);
inputEnd.addEventListener("input", setEndValue);

inputStart.addEventListener("mouseover", function() {
    handleStart.classList.add("hover");
});
inputStart.addEventListener("mouseout", function() {
    handleStart.classList.remove("hover");
});
inputStart.addEventListener("mousedown", function() {
    handleStart.classList.add("active");
    setHandlePriority(inputStart);
});
inputStart.addEventListener("mouseup", function() {
    handleStart.classList.remove("active");
    startCalculatedPreviewPlayback(inputStart.value, inputEnd.value);
    console.log("Start: " + inputStart.value + " End: " + inputEnd.value);
});

inputEnd.addEventListener("mouseover", function() {
    handleEnd.classList.add("hover");
});
inputEnd.addEventListener("mouseout", function() {
    handleEnd.classList.remove("hover");
});
inputEnd.addEventListener("mousedown", function() {
    handleEnd.classList.add("active");
    setHandlePriority(inputEnd);
});
inputEnd.addEventListener("mouseup", function() {
    handleEnd.classList.remove("active");
    startCalculatedPreviewPlayback(inputStart.value, inputEnd.value);
    console.log("Start: " + inputStart.value + " End: " + inputEnd.value);
});

function startCalculatedPreviewPlayback(start, end){
    var preview = document.getElementById("<?php echo $context ?>_<?php echo $display ?>_introvideo<?php echo $ref?>_html5_api");
    preview.currentTime = start;

    // if preview file duration smaller then original file the start or end of trim preview needs to be capped
    if (start > <?php echo $preview_cap ?> || end > <?php echo $preview_cap ?>) 
      {
      preview.currentTime = 0;
      preview.pause();
      alert("Your start or end trim point/s proceeds the video preview length.\n\nPlease consider increasing your video preview length and recreating preview files should you wish to preview the timmed outcome.\n\nThe preview provided will not fully represent the final outcome.");
      }
    else
        {
        preview.play();
        setInterval(function()
          {
          if(preview.currentTime > end)
              {
              preview.currentTime = start;
              }
          else if(preview.currentTime < start)
              {
              preview.currentTime = start;
              }
          });
        }
    }

function generateTimestamps(){
      document.getElementById("start-timestamp").innerHTML = secondsToHHMMSS(inputStart.value);
      document.getElementById("end-timestamp").innerHTML = secondsToHHMMSS(inputEnd.value);
      document.getElementById("duration-timestamp").innerHTML = secondsToHHMMSS(inputEnd.value-inputStart.value);
    }

function secondsToHHMMSS(seconds){
    var timeSeconds = new Date(0);
    timeSeconds.setSeconds(seconds);
    var timeHHMMSS = timeSeconds.toISOString().substr(11, 8);
    console.log(timeHHMMSS)
    return timeHHMMSS;
    }

function setHandlePriority(lastHandleTouched){
    //to help prevent overlap at the extremities leaving unable to move handle back
    inputStart.style.zIndex = "";
    inputEnd.style.zIndex = "";
    lastHandleTouched.style.zIndex = "4";
    }
</script>
