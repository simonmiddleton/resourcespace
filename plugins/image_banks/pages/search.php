<?php
$rs_root = dirname(dirname(dirname(__DIR__)));
include "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/general.php";
include "{$rs_root}/include/authenticate.php";

$search                 = getval('search', '');
$image_bank_provider_id = getval("image_bank_provider_id", 0, true);

if($image_bank_provider_id == 0)
    {
    trigger_error($lang["image_banks_provider_id_required"]);
    }

$providers = \ImageBanks\getProviders($image_banks_loaded_providers);

if(!array_key_exists($image_bank_provider_id, $providers))
    {
    trigger_error($lang["image_banks_provider_not_found"]);
    }

$provider = $providers[$image_bank_provider_id];

$results = $provider->search($search);

// TODO: add pager functionality
?>
<div class="BasicsBox">
    <div class="TopInpageNav">
        <div class="TopInpageNavLeft">
            <div id="SearchResultFound" class="InpageNavLeftBlock">
                <span class="Selected"><?php echo number_format($results->total); ?></span> <?php echo htmlspecialchars($lang["youfoundresults"]); ?>
            </div>
            <div id="SearchResultFound" class="InpageNavLeftBlock">
                <span class="Selected"><?php echo htmlspecialchars($lang["image_banks_image_bank"]); ?>: </span> <?php echo htmlspecialchars($provider->getName()); ?>
            </div>
        </div>
        <div class="clearerleft"></div>
    </div>
</div>
<?php
foreach($results as $result)
    {
    ?>
    <div class="ResourcePanel" style="height: 214px;">
        <a href="<?php echo $result->getProviderUrl(); ?>" target="_blank" class="ImageWrapper" title="<?php echo htmlspecialchars($result->getSource()); ?>">
            <img src="<?php echo $result->getPreviewUrl(); ?>" width="<?php echo $result->getPreviewWidth(); ?>" height="<?php echo $result->getPreviewHeight(); ?>" border="0">
        </a>
        <div class="ResourcePanelInfo">
            <!-- <a href="#" target="_blank" title="License">link</a> -->
        </div>
        <div class="clearer"></div>

        <div class="ResourcePanelIcons">
            <a href="#" class="fa fa-download" aria-hidden="true" title="Download resource" onclick="downloadFile(); return false;"></a>
            <a href="#" class="fa fa-file" aria-hidden="true" title="create new resource" onclick="createNewResource(); return false;"></a>
            <div class="clearer"></div>
        </div>
    </div>
    <?php
    }