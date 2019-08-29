<?php
function isValidVimeoURL($url)
    {
    // Check if the video exists
    global $vm2rs_videoId;
    // Vimeo url?
    if (preg_match("/vimeo.com\/[a-z1-9.-_]+/", $url))
        {
        preg_match("/vimeo.com\/([a-z1-9.-_]+)/", $url, $matches);
        }
    else if (preg_match("/vimeo.com(.+)v=([^&]+)/", $url))
        {
        preg_match("/v=([^&]+)/", $url, $matches);
        }

    if (!empty($matches))
        {
        $vm2rs_videoId = $matches[1];

        if (!$fp = curl_init($url))
            {
            return false;
            }

        return true;
        }
    }

function Hookvm2rsViewrenderinnerresourcepreview()
    {
    // Replace preview if it's a valid Vimeo URL
    global $ref, $ffmpeg_preview_max_width, $ffmpeg_preview_max_height, $vm2rs_field_id, $vm2rs_videoId;

    $width = $ffmpeg_preview_max_width;
    $height = $ffmpeg_preview_max_height;
    $vimeo_url = get_data_by_field($ref, $vm2rs_field_id);

    if ($vimeo_url == "" || !isValidVimeoURL($vimeo_url))
        {
        return false;
        }
    else
        {
        $vimeo_url_emb = "https://player.vimeo.com/video/" . "$vm2rs_videoId";
        ?>
        <div id="previewimagewrapper">
            <div class="Picture" id="videoContainer" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;">
                <iframe title="Vimeo video player" class="vimeo-player" type="text/html" width="<?php echo $width; ?>" height="<?php echo $height; ?>" src="<?php echo $vimeo_url_emb; ?>" frameborder="0" frameborder="0" allowFullScreen>
                </iframe>
            </div>
        </div>
        <?php
        }
    return true;
    }

function Hookvm2rsViewreplacedownloadoptions()
    {
    // Replace download options
    global $ref, $vm2rs_field_id, $baseurl_short, $lang;

    $vimeo_url = get_data_by_field($ref, $vm2rs_field_id);

    if ($vimeo_url !== "" && isValidVimeoURL($vimeo_url))
        {
        ?>
        <table cellpadding="0" cellspacing="0">
            <tr >
                <td>File Information</td>
                <td>File Size </td>
                <td>Options</td>
            </tr>
            <tr class="DownloadDBlend">
                <td><h2>Online Preview</h2><p>Vimeo Video</p></td>
                <td>N/A</td>
                <td class="DownloadButton HorizontalWhiteNav">
                    <a href="<?php echo $baseurl_short; ?>pages/resource_request.php?ref=<?php echo urlencode($ref); ?>&k=<?php echo getval("k", ""); ?>" onClick="return CentralSpaceLoad(this,true);">
                <?php echo $lang["action-request"] ?>
                </td>
            </tr>
        </table>
        <?php
        return true;
        }
    else
        {
        return false;
        }
    }

