<?php
$rs_root = dirname(dirname(dirname(__DIR__)));
include_once "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/general.php";
include_once "{$rs_root}/include/authenticate.php";
include_once "{$rs_root}/include/render_functions.php";

$search                 = getval("search", "");
$image_bank_provider_id = getval("image_bank_provider_id", 0, true);

$search_params = array(
    "search"                 => $search,
    "image_bank_provider_id" => $image_bank_provider_id,
    "search_image_banks"     => true,
);

// Paging functionality
$url = generateURL("{$baseurl_short}pages/search.php", $search_params);

$offset = (int) getval("offset", 0, true);

$per_page = (int) getval("per_page", $default_perpage, true);
rs_setcookie("per_page", $per_page, 0, "", "", false, false);

$curpage = floor($offset / $per_page) + 1;
// End of Paging functionality

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

$results = $provider->search($search, $per_page, $curpage);

$totalpages  = ceil($results->total / $per_page);
?>
<div class="BasicsBox">
    <div class="TopInpageNav">
        <div class="TopInpageNavLeft">
            <div id="SearchResultFound" class="InpageNavLeftBlock">
                <span class="Selected"><?php echo number_format($results->total); ?></span> <?php echo htmlspecialchars($lang["youfoundresults"]); ?>
            </div>
            <div class="InpageNavLeftBlock">
                <span class="Selected"><?php echo htmlspecialchars($lang["image_banks_image_bank"]); ?>: </span> <?php echo htmlspecialchars($provider->getName()); ?>
            </div>
            <div class="InpageNavLeftBlock">
                <select name="per_page" onchange="CentralSpaceLoad(this.value, true);">
                    <?php
                    foreach($results_display_array as $results_display_per_page)
                        {
                        $value = generateURL(
                            "{$baseurl_short}pages/search.php",
                            $search_params,
                            array(
                                "per_page" => $results_display_per_page
                            )
                        );
                        $label = str_replace("?", $results_display_per_page, $lang["perpage_option"]);
                        $extra_attributes = "";

                        if($results_display_per_page === $per_page)
                            {
                            $extra_attributes = " selected";
                            }

                        echo render_dropdown_option($value, $label, array(), $extra_attributes);
                        }                    
                        ?>
                </select>
            </div>
        </div>
        <div class="TopInpageNavRight">
            <?php pager(false); ?>
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
            <img src="<?php echo $result->getPreviewUrl(); ?>"
                 width="<?php echo $result->getPreviewWidth(); ?>"
                 height="<?php echo $result->getPreviewHeight(); ?>"
                 border="0">
        </a>
        <div class="ResourcePanelInfo">
            <!-- <a href="#" target="_blank" title="License">link</a> -->
        </div>
        <div class="clearer"></div>

        <div class="ResourcePanelIcons">
            <a href="<?php echo $result->getOriginalFileUrl(); ?>"
               class="fa fa-download"
               aria-hidden="true"
               title="Download resource"
               data-id="<?php echo $result->getId(); ?>"
               onclick="downloadImageBankFile(this);"></a>

            <!-- based on permissions "c" and "d" -->
            <a href="<?php echo $result->getOriginalFileUrl(); ?>"
               class="fa fa-file"
               aria-hidden="true"
               title="create new resource"
               onclick="createNewResource(this);"></a>
            <div class="clearer"></div>
        </div>
    </div>
    <?php
    }
    ?>
<script>
    function downloadImageBankFile(element)
        {
        event.preventDefault();

        var form = jQuery('<form id="downloadImageBankFile"></form>')
            .attr("action", "<?php echo $baseurl; ?>/plugins/image_banks/pages/download.php")
            .attr("method", "get");

        form.append(jQuery("<input></input>").attr("type", "hidden").attr("name", "file").attr("value", element.href));
        form.append(jQuery("<input></input>").attr("type", "hidden").attr("name", "id").attr("value", jQuery(element).data("id")));

        form.appendTo('body').submit().remove();
        }
</script>