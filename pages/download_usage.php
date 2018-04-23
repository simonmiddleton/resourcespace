<?php
include "../include/db.php";
include_once "../include/collections_functions.php";
include_once "../include/general.php";

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k           = getvalescaped('k', '');
$ref         = getvalescaped('ref', '', true);
$col         = getvalescaped('collection', getvalescaped('col', -1, true), true);
$size        = getvalescaped('size', '');
$ext         = getvalescaped('ext', '');
$alternative = getvalescaped('alternative', -1);

if(-1 != $col)
    {
    $need_to_authenticate = !check_access_key_collection($col, $k);
    }
else
    {
    $need_to_authenticate = !check_access_key($ref, $k);
    }

if('' == $k || $need_to_authenticate)
    {
    include '../include/authenticate.php';
    }

hook("pageevaluation");

$download_url_suffix = hook("addtodownloadquerystring");

if (getval("save",'') != '' && enforcePostRequest(false))
    {
    $usage = getvalescaped("usage", '');
    $usagecomment = getvalescaped("usagecomment", '');

    $download_url_suffix .= ($download_url_suffix == '') ? '?' : '&';
    if($download_usage && -1 != $col) 
        {
        $download_url_suffix .= "collection=" . urlencode($col);
        $redirect_url = "pages/collection_download.php";
        } 
    else 
        {
        $download_url_suffix .= "ref=" . urlencode($ref);
        $redirect_url = "pages/download_progress.php";
        }
    $download_url_suffix .= "&size=" . urlencode($size) . 
                            "&ext=" . urlencode($ext) . 
                            "&k=" . urlencode($k) . 
                            "&alternative=" . urlencode($alternative) . 
                            "&usage=" . urlencode($usage) . 
                            "&usagecomment=" . urlencode($usagecomment) .
                            "&offset=" . urlencode(getval("saved_offset", getval("offset",''))) .
                            "&order_by=" . urlencode(getval("saved_order_by",getval("order_by",''))) . 
                            "&sort=" . urlencode(getval("saved_sort",getval("sort",''))) .
                            "&archive=" . urlencode(getval("saved_archive",getval("archive",'')));
    
    hook('before_usage_redirect');
    
    redirect($redirect_url . $download_url_suffix);
    }

include "../include/header.php";

if(isset($download_usage_prevent_options))
    { ?>
    <script>
        function checkvalidusage() {
            validoptions = new Array(<?php echo "'" . implode("','",$download_usage_prevent_options) . "'" ?>);
            if(jQuery.inArray( jQuery('#usage').val(), validoptions )!=-1) {
                jQuery('input[type="submit"]').attr('disabled','disabled')
                alert("<?php echo $lang["download_usage_option_blocked"] ?>");
            }
            else {
                jQuery('input[type="submit"]').removeAttr('disabled');
            }
        }
    </script>
    <?php
    } ?>

<div class="BasicsBox">

    <form method="post" action="<?php echo $baseurl_short?>pages/download_usage.php<?php echo $download_url_suffix ?>" onSubmit="if (  <?php if (!$usage_comment_blank) { ?>  (jQuery('#usagecomment').val()=='') ||<?php } ?>     (jQuery('#usage').val()=='')) {alert('<?php echo $lang["usageincorrect"] ?>');return false;} else {return CentralSpacePost(this,true);}">
        <?php
        generateFormToken("download_usage");

        if($download_usage && ($col != -1)) { ?>
        <input type="hidden" name="col" value="<?php echo htmlspecialchars($col) ?>" />
        <?php } ?>
        <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref) ?>" />
        <input type="hidden" name="size" value="<?php echo htmlspecialchars($size) ?>" />
        <input type="hidden" name="ext" value="<?php echo htmlspecialchars($ext) ?>" />
        <input type="hidden" name="alternative" value="<?php echo htmlspecialchars($alternative) ?>" />
        <input type="hidden" name="k" value="<?php echo htmlspecialchars($k) ?>" />
        <input type="hidden" name="save" value="true" />
        <h1><?php echo $lang["usage"]?></h1>
        <p><?php echo $lang["indicateusage"]?></p>

        <?php if(!$remove_usage_textbox && !$usage_textbox_below) { ?>
        <div class="Question">
            <label><?php echo $lang["usagecomments"]?></label>
            <textarea rows="5" name="usagecomment" id="usagecomment" type="text" class="stdwidth"></textarea>
            <div class="clearerleft"> </div>
        </div> 
        <?php } ?>

        <div class="Question"><label><?php echo $lang["indicateusagemedium"]?></label>
            <select class="stdwidth" name="usage" id="usage" <?php if(isset($download_usage_prevent_options)){ echo 'onchange="checkvalidusage();"';}?>>
                <option value=""><?php echo $lang["select"] ?></option>
                <?php 
                for ($n=0;$n<count($download_usage_options);$n++)
                    {
                    ?>
                    <option value="<?php echo $n; ?>"><?php echo htmlspecialchars($download_usage_options[$n]) ?></option>
                    <?php
                    } ?>
            </select>
            <div class="clearerleft"> </div>
        </div>

        <?php if ($usage_textbox_below && !$remove_usage_textbox) { ?>
        <div class="Question">
            <label><?php echo $lang["usagecomments"]?></label>
            <textarea rows="5" name="usagecomment" id="usagecomment" type="text" class="stdwidth"></textarea>
            <div class="clearerleft"> </div>
        </div>
        <?php } ?>

        <div class="QuestionSubmit">
            <label for="buttons"> </label>          
            <input name="submit" type="submit" id="submit" value="&nbsp;&nbsp;<?php echo $lang["action-download"]?>&nbsp;&nbsp;" />
        </div>

    </form>
</div>

<?php
include "../include/footer.php";
?>
