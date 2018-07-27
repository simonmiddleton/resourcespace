<?php
function HookImage_banksAllSearchfiltertop()
    {
    global $lang, $image_banks_loaded_providers, $clear_function;

    $providers = \ImageBanks\getProviders($image_banks_loaded_providers);

    if(count($providers) == 0)
        {
        return;
        }

    $search_image_banks_text = htmlspecialchars($lang["image_banks_search_image_banks_label"]);
    $search_image_banks_info_text = htmlspecialchars($lang["image_banks_search_image_banks_info_text"]);
    $image_bank_provider_id = getval("image_bank_provider_id", 0, true);
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
        <select id="SearchImageBanks" class="SearchWidth" name="image_bank_provider_id">
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
        <div class="clearerleft"></div>
    </div>
    <?php
    $clear_function .= 'jQuery("#SearchImageBanks").val([\'\']) ;';

    return;
    }