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


// TODO: add results count
// TODO: add pager functionality

// TODO: add Image Bank Provider name that user searched through
?>
<div class="BasicsBox">
    <div class="TopInpageNav">
        <div class="TopInpageNavLeft">

            <div class="ResponsiveResultDisplayControls">
                <div id="ResponsiveResultCount">
                    <span class="Selected">49 </span>resources
                </div>
            </div>
            <div id="SearchResultFound" class="InpageNavLeftBlock">
                <span class="Selected">49 </span>resources
            </div>
            <div class="clearerleft"></div>
        </div>
    </div>
</div>
<?php
foreach($results as $result)
    {
    if(!($result instanceof \ImageBanks\ProviderResult))
        {
        continue;
        }

    ?>
    <div class="ResourcePanel" style="height: 214px;">
        <a href="<?php echo $result->getProviderUrl(); ?>" target="_blank" class="ImageWrapper" title="<?php echo htmlspecialchars($result->getSource()); ?>">
            <img border="0" width="106" height="150" style="margin-top:auto;" src="<?php echo $result->getPreviewUrl(); ?>" alt="Brochure">
        </a>
        <div class="ResourcePanelInfo">
        <a href="/trunk/pages/view.php?search=%21last1000&amp;k=&amp;modal=&amp;display=thumbs&amp;order_by=resourceid&amp;offset=0&amp;per_page=48&amp;archive=&amp;sort=DESC&amp;restypes=&amp;recentdaylimit=&amp;foredit=&amp;ref=150" onclick="return ModalLoad(this,true);" title="Brochure">
        <?php echo htmlspecialchars($result->getId()); ?></a>
        &nbsp;
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