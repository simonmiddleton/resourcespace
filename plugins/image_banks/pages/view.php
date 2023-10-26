<?php

use ImageBanks\NoProvider;

use function ImageBanks\getProviders;
use function ImageBanks\getProviderSelectInstance;
use function ImageBanks\providersCheckedAndActive;
use function ImageBanks\render_provider_search_result_link;

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

$record = $provider->findById($id);

/* 
TODO
====
- Image Bank tools with following options (same as on the search result page):
    - Download
    - Create new resource
- Provider details - this section will contain any useful metadata we can get from the API (e.g, username of the contributor with a link to their user page)
*/

$modal = getval("modal", "") === "true";

include_once "{$rs_root}/include/header.php";
?>
<div class="RecordBox">
    <div class="RecordPanel RecordPanelLarge">
        <div class="RecordHeader">
            <div class="backtoresults">
                <a href="#" onclick="ModalClose();" class="closeLink fa fa-times" title="<?php echo escape_quoted_data($lang['close']); ?>"></a>
            </div>
            <h1><?php echo htmlspecialchars($record->getTitle()); ?></h1>
        </div>

        <?php render_top_page_error_style($error ?? ''); ?>
        <div class="RecordResource">
            <div id="previewimagewrapper">
                <?php
                render_provider_search_result_link(
                    $record,
                    function() use ($record, $lang)
                        {
                        ?>
                        <img
                            id="previewimage"
                            class="Picture"
                            src="<?php echo $record->getPreviewUrl(); ?>"
                            alt="<?php echo escape_quoted_data($lang['fullscreenpreview']); ?>"
                            galleryimg="no">
                        <?php
                        },
                    [
                        'class' => ['enterLink'],
                        'title' => str_replace('%PROVIDER', $provider_name, $lang["image_banks_view_on_provider_system"]),
                        'force_provider_url' => true,
                    ]
                );
                ?>
            </div>





            <div class="RecordDownload" id="RecordDownloadTabContainer">
                <div class="TabBar" id="RecordDownloadTabButtons">
                    <div class="Tab TabSelected" id="DownloadsTabButton">
                        <a href="#" onclick="selectDownloadTab('DownloadsTab',<?php echo $modal ? 'true' : 'false'; ?>);">
                            <?php echo htmlspecialchars($lang["resourcetools"]); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="RecordDownloadSpace" id="DownloadsTab">
                <table cellpadding="0" cellspacing="0" id="ResourceDownloadOptions">
                    <tr id="ResourceDownloadOptionsHeader">
                    </tr>
                </table>
            </div>






            <!-- <div class="RecordDownload" id="RecordDownload">
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
            </div> -->
        </div>
    </div>
</div>
<?php
include_once "{$rs_root}/include/footer.php";
