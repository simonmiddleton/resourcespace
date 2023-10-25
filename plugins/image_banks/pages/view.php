<?php

use ImageBanks\NoProvider;

use function ImageBanks\getProviders;
use function ImageBanks\getProviderSelectInstance;
use function ImageBanks\providersCheckedAndActive;

$rs_root = dirname(__DIR__, 3);
include_once "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/authenticate.php";

/** @var string Remote Provider ID */
$id = getval('id', '');

[$providers,] = getProviders($image_banks_loaded_providers);
$providers_select_list = providersCheckedAndActive($providers);
$image_bank_provider_id = (int) getval('image_bank_provider_id', 0, true);

if($image_bank_provider_id === 0)
    {
    error_alert($lang['image_banks_provider_id_required'], false);
    exit();
    }
else if(!array_key_exists($image_bank_provider_id, $providers_select_list))
    {
    error_alert($lang['image_banks_provider_not_found'], false);
    exit();
    }

$provider = getProviderSelectInstance($providers, $image_bank_provider_id);
if ($provider instanceof NoProvider)
    {
    error_alert($lang['image_banks_provider_not_found'], false);
    exit();
    }
$provider_name = $providers_select_list[$image_bank_provider_id] ?? $provider->getName();


/* 
TODO
====
- Large remote image display;
- Clicking on the preview image will take the user to the Image Banks Providers' website (as it currently does from the search result page).
- Image Bank tools with following options (same as on the search result page):
    - Download
    - Create new resource
- Provider details - this section will contain any useful metadata we can get from the API (e.g, username of the contributor with a link to their user page)
*/

include_once "{$rs_root}/include/header.php";
?>
<div class="RecordBox">
    <div class="RecordPanel RecordPanelLarge">
        <div class="RecordHeader">
            <div class="backtoresults">
                <a href="#" onclick="ModalClose();" class="closeLink fa fa-times" title="<?php echo escape_quoted_data($lang['close']); ?>"></a>
            </div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
        </div>

        <?php render_top_page_error_style($error ?? ''); ?>
        <div class="RecordResource">
            <div id="previewimagewrapper">
                <img id="previewimage" class="Picture" src="<?php echo $preview ?>" alt="<?php echo escape_quoted_data($lang['fullscreenpreview']); ?>" galleryimg="no">
            </div>
            <div class="RecordDownload" id="RecordDownload">
                <div class="RecordDownloadSpace">
                    <h2 id="resourcetools"><?php echo htmlspecialchars($lang["resourcetools"]); ?></h2>
                    <table cellpadding="0" cellspacing="0" id="ResourceDownloadOptions">
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($lang["fileinformation"]); ?></td>
                                <td class="textcenter"><?php echo htmlspecialchars($lang["options"]); ?></td>
                            </tr>
                            <tr class="DownloadDBlend" id="DownloadBox0">
                                <td class="DownloadFileName">
                                    <h2><?php echo htmlspecialchars($filename); ?></h2>
                                </td>
                                <td class="DownloadButton">
                                    <a id="downloadlink" href="<?php escape_quoted_data($download_link); ?>" target="_blank"><?php echo htmlspecialchars($lang["download"]); ?></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="clearerleft"></div>
            </div>
        </div>
    </div>
</div>
<?php
include_once "{$rs_root}/include/footer.php";
