<?php
function HookImage_banksAllSearchfiltertop()
    {
    global $lang, $image_banks_loaded_providers;

    // TODO: by the end of dev, this should return provider objects
    // make sure to extract the id and title/ name of the provider and use them in the option tags
    $providers = \ImageBanks\getProviders($image_banks_loaded_providers);

    echo "<p>TODO: check " . __FILE__ . " at line " . __LINE__ . "</p>";return;

    if(count($providers) == 0)
        {
        return;
        }

    $search_image_banks_text = htmlspecialchars($lang["image_banks_search_image_banks_label"]);
    $search_image_banks_info_text = htmlspecialchars($lang["image_banks_search_image_banks_info_text"]);
    ?>
    <div id="" class="SearchItem" title="">
        <label>
            <span><?php echo $search_image_banks_text; ?></span>
            <a href="#"
               onclick="styledalert('<?php echo $search_image_banks_text; ?>','<?php echo $search_image_banks_info_text; ?>');"
               title="<?php echo $search_image_banks_info_text; ?>">
               <i class="fa fa-info-circle"></i>
           </a>
        </label>
        <!-- TODO: change name and onchange attributes -->
        <select id="SearchImageBanks"
                class="SearchWidth"
                name="nodes_searched[89]"
                onchange="FilterBasicSearchOptions('fixedlistwithautocom',0);">
            <option value=""></option>
            <?php
            foreach($providers as $provider_value => $provider_title)
                {
                ?>
                <option value="<?php echo $provider_value; ?>"><?php echo htmlspecialchars($provider_title); ?></option>
                <?php
                }
                ?>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    return;
    }