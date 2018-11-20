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
$results_error = $results->getError();
$results_warning = $results->getWarning();

$totalpages  = ceil($results->total / $per_page);

include_once "{$rs_root}/include/header.php";
?>
<div class="BasicsBox">
    <div class="TopInpageNav">
        <div class="TopInpageNavLeft">
            <div id="SearchResultFound" class="InpageNavLeftBlock">
                <span class="Selected"><?php echo number_format($results->total); ?></span> <?php echo htmlspecialchars($lang["youfoundresults"]); ?>
            </div>
            <div class="InpageNavLeftBlock AlignLeftBlockText">
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
            <?php
            if($results_warning !== "")
                {
                ?>
                <div class="InpageNavLeftBlock AlignLeftBlockText WarningBox">
                    <span class="Selected RedText"><?php echo htmlspecialchars($lang["image_banks_warning"]); ?></span><span><?php echo htmlspecialchars($results_warning); ?></span>
                </div>
                <?php
                }
                ?>
        </div>
        <div class="TopInpageNavRight">
            <?php pager(false); ?>
        </div>
        <div class="clearerleft"></div>
    </div>
</div>
<div id="CentralSpaceResources">
<?php
if($results_error !== "")
    {
    ?>
    <div id="CentralSpaceResources">
        <div class="BasicsBox"> 
            <div class="NoFind">
                <p><?php echo htmlspecialchars($lang["searchnomatches"]); ?></p>
                <p><?php echo htmlspecialchars($results_error); ?></p>
            </div>
        </div>
    </div>
    <?php
    }

foreach($results as $result)
    {
    $title = $result->getTitle();

    $image_data = array(
        "thumb_width"  => $result->getPreviewWidth(),
        "thumb_height" => $result->getPreviewHeight(),
        "field{$view_title_field}" => $title,
    );

    $title_link_text = highlightkeywords(tidy_trim(tidylist(strip_tags_and_attributes($title)), $search_results_title_trim), $search);
    ?>
    <div class="ResourcePanel ImageBanksResourcePanel">
        <a href="<?php echo $result->getProviderUrl(); ?>" target="_blank" class="ImageWrapper" title="<?php echo htmlspecialchars($title); ?>">
            <?php render_resource_image($image_data, $result->getPreviewUrl(), "thumbs"); ?>
        </a>
        <div class="ResourcePanelInfo">
            <a href="<?php echo $result->getProviderUrl(); ?>" target="_blank" title="<?php echo htmlspecialchars($title); ?>"><?php echo $title_link_text; ?></a>
        </div>
        <div class="clearer"></div>

        <div class="ResourcePanelIcons">
            <a href="<?php echo $result->getOriginalFileUrl(); ?>"
               class="fa fa-download"
               aria-hidden="true"
               title="Download resource"
               data-id="<?php echo $result->getId(); ?>"
               onclick="downloadImageBankFile(this);"></a>

        <?php
        if(checkperm("c") || checkperm("d"))
            {
            ?>
            <a href="<?php echo $result->getOriginalFileUrl(); ?>"
               class="fa fa-files-o"
               aria-hidden="true"
               title="<?php echo htmlspecialchars($lang["image_banks_create_new_resource"]); ?>"
               onclick="createNewResource(event, this);"></a>
            <?php
            }
            ?>
            <div class="clearer"></div>
        </div>
    </div>
    <?php
    }
    ?>
</div>
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

function createNewResource(event, element)
    {
    event.preventDefault();

    CentralSpaceShowLoading();

    jQuery.ajax(
        {
        type: 'POST',
        url: "<?php echo $baseurl; ?>/plugins/image_banks/pages/ajax.php",
        data: {
            ajax: true,
            original_file_url: element.href,
            <?php echo generateAjaxToken("ImageBanks_createNewResource"); ?>
        },
        dataType: "json"
        }).done(function(response, textStatus, jqXHR) {
            var view_page_anchor = document.createElement("a");
            view_page_anchor.setAttribute("href", baseurl_short + "?r=" + response.data.new_resource_ref);
            CentralSpaceLoad(view_page_anchor, true, false);
        }).fail(function(data, textStatus, jqXHR) {
            if(data.status == 500)
                {
                styledalert(data.status, data.statusText);
                return;
                }

            styledalert(data.responseJSON.error.title, data.responseJSON.error.detail);
        }).always(function() {
            CentralSpaceHideLoading();
        });
    }
</script>
<?php
include_once "{$rs_root}/include/footer.php";