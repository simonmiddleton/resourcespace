<?php
function HookImage_banksAllSearchfiltertop()
    {
    global $lang, $image_banks_loaded_providers, $clear_function;

    $providers = \ImageBanks\getProviders($image_banks_loaded_providers);

    foreach($providers as $provider_id => $provider)
        {
        if(!$provider->checkDependencies())
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
    <div id="SearchImageBanksItem" class="Question">
        <label>
            <span><?php echo $search_image_banks_text; ?></span>
        </label>
        <select id="SearchImageBanks" class="SearchWidth" name="image_bank_provider_id" onchange="toggleUnwantedElementsFromSimpleSearch(jQuery(this)); UpdateResultCount();">
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
            var siblings = jQuery("#SearchImageBanksItem")
                .siblings()
                .not("#ssearchbox")
                .not(".tag-editor")
                .not("input[type=hidden]")
                .not("script")
                .not(".ui-widget")
                .not("#ActiveFilters")
                .not("#FilterBarAdvancedSection")
                .not(".QuestionSubmit");

            if(selected_option == "")
                {
                if(typeof search_show == 'undefined' || search_show)
                    {
                    siblings.show();
                    }
                return;
                }

            siblings.hide();

            return;
            }

        jQuery(document).ready(function() {
            toggleUnwantedElementsFromSimpleSearch(jQuery("#SearchImageBanks"));
        });
        </script>
        <div class="clearerleft"></div>
    </div>
    <?php
    return;
    }

function HookImage_banksAllMoresearchcriteria()
    {
    global $extra_params;

    if(is_null($extra_params))
        {
        return false;
        }

    $search_image_banks = filter_var(getval("search_image_banks", false), FILTER_VALIDATE_BOOLEAN);
    $image_bank_provider_id = getval("image_bank_provider_id", 0, true);

    $per_page = getval("per_page", 0, true);
    $saved_offset = getval("saved_offset", 0, true);
    $offset = getval("offset", $saved_offset, true);
    $posting = filter_var(getval("posting", false), FILTER_VALIDATE_BOOLEAN);

    $extra_params = array_merge(
        $extra_params,
        array(
            "search_image_banks" => $search_image_banks,
            "image_bank_provider_id" => $image_bank_provider_id,
            "per_page" => $per_page,
            "saved_offset" => $saved_offset,
            "offset" => $offset,
            "posting" => $posting,
        ));

    return true;
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

function HookImage_banksAllClear_filter_bar_js()
    {
    ?>
    SetCookie('image_bank_provider_id','');
    <?php
    return;
    }

function HookImage_banksAllFb_modify_fields(array $fields)
    {
    $index = false;
    foreach($fields as $key => $field)
        {
        // At the end of Simple Search fields
        if($field["simple_search"] == 0 && $field["advanced_search"] == 1)
            {
            $index = $key;
            break;
            }
        }

    return array_merge(
        array_slice($fields, 0, $index),
        array(array(
            "ref" => null,
            "name" => "ImageBanks-field", # render_search_field() is globaling $fields for display conditions and this field is in between real RS fields
            "simple_search" => 1,
            "advanced_search" => 0,
            "fct_name" => "HookImage_banksAllSearchfiltertop",
            "fct_args" => array()
        )),
        array_slice($fields, $index, count($fields) - 1, true));
    }