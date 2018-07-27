<?php
function HookImage_banksSearchMoresearchcriteria()
    {
    $image_bank_provider_id = getval("image_bank_provider_id", 0, true);
    rs_setcookie("image_bank_provider_id", $image_bank_provider_id);

    return;
    }

function HookImage_banksSearchSearchaftersearchcookie()
    {
    $image_bank_provider_id = getval("image_bank_provider_id", 0, true);
    $search_image_banks = filter_var(getval("search_image_banks", false), FILTER_VALIDATE_BOOLEAN);

    if(!$search_image_banks)
        {
        rs_setcookie("image_bank_provider_id", 0);

        return;
        }

    if($image_bank_provider_id == 0)
        {
        return;
        }

    global $baseurl_short, $searchparams;

    redirect(
        generateURL(
            "{$baseurl_short}plugins/image_banks/pages/search.php",
            $searchparams,
            array(
                "image_bank_provider_id" => $image_bank_provider_id,
            )
        )
    );

    return;
    }