<?php
function HookImage_banksAllSearchfiltertop()
    {
    global $lang, $image_banks_loaded_providers, $clear_function;

    $providers = \ImageBanks\getProviders($image_banks_loaded_providers);

    foreach($providers as $provider_id => $provider)
        {
        if($provider->checkDependencies() !== true)
            {
            unset($providers[$provider_id]);
            }
        }

    if(count($providers) == 0)
        {
        return;
        }

    $search_image_banks_text = htmlspecialchars($lang["image_banks_search_image_banks_label"]);
    $search_image_banks_info_text = htmlspecialchars($lang["image_banks_search_image_banks_info_text"]);
    $image_bank_provider_id = getval("image_bank_provider_id", 0, true);
    ?>
    <div id="SearchImageBanksItem" class="SearchItem" title="">
        <label>
            <span><?php echo $search_image_banks_text; ?></span>
        </label>
        <select id="SearchImageBanks" class="SearchWidth" name="image_bank_provider_id" onchange="toggleUnwantedElementsFromSimpleSearch(jQuery(this));">
            <option value=""></option>
            <?php
            foreach($providers as $provider)
                {
                $selected = ($image_bank_provider_id == $provider->getId() ? "selected" : "");
                ?>
                <option value="<?php echo $provider->getId(); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($provider->getName()); ?></option>
                <?php
                }
                ?>
        </select>
        <script>
        function toggleUnwantedElementsFromSimpleSearch(selector)
            {
            var selected_option = selector.val();
            
            SetCookie("image_bank_provider_id",selected_option);
            
            var siblings = jQuery("#SearchImageBanksItem")
                .siblings()
                .not("#ssearchbox")
                .not(".tag-editor")
                .not("input[type=hidden]")
                .not("script")
                .not(".ui-widget")
                .not("#simplesearchbuttons");

            if(selected_option == "")
                {
                if(typeof search_show == 'undefined' || search_show)
                    {
                    siblings.each(function()
						{
                        qid = this.id.substring(13);
                        if(typeof clearhiddenfields == "object" && !clearhiddenfields.includes(qid))
                            {
                            jQuery(this).show();
                            }
						});
                    }
                return;
                }

            siblings.hide();

            return;
            }

        </script>
        <div class="clearerleft"></div>
    </div>
    <?php
    $clear_function .= 'jQuery("#SearchImageBanks").val([\'\']); search_show=true; toggleUnwantedElementsFromSimpleSearch(jQuery("#SearchImageBanks"));';

    return;
    }

function HookImage_banksAllAdd_folders_to_delete_from_temp(array $folders_scan_list)
    {
    global $image_banks_loaded_providers;

    $providers = \ImageBanks\getProviders($image_banks_loaded_providers);

    if(count($providers) == 0)
        {
        return false;
        }

    foreach($providers as $provider)
        {
        $tmp_dir = $provider->getTempDirPath();

        if($tmp_dir == "")
            {
            continue;
            }

        $folders_scan_list[] = $tmp_dir;
        }

    return $folders_scan_list;
    }

function HookImage_banksAllClearsearchcookies()
    {
    global $clear_function;
    $clear_function .= "SetCookie('image_bank_provider_id','');";
    return true;
    }

function HookImage_banksAllSimplesearchfieldsarehidden()
{
$hib_simpleSearchFieldsAreHidden = ( getval("image_bank_provider_id",0, true) > 0 );
return $hib_simpleSearchFieldsAreHidden;
}  
