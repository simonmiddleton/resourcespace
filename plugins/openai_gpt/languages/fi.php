<?php


$lang["openai_gpt_temperature"]='Näytteenotto lämpötila välillä 0 ja 1 (korkeammat arvot tarkoittavat sitä, että malli ottaa enemmän riskejä)';
$lang["openai_gpt_max_tokens"]='Maksimi merkkiavaimet';
$lang["openai_gpt_advanced"]='VAROITUS - Tämä osio on tarkoitettu vain testaustarkoituksiin eikä sitä tulisi muuttaa tuotantojärjestelmissä. Tämän osion plugin-asetusten muuttaminen vaikuttaa kaikkien määritettyjen metatietokenttien käyttäytymiseen. Muuta varoen!';
$lang["openai_gpt_system_message"]='Alkuperäinen järjestelmäviestin teksti. Paikkamerkit %%IN_TYPE%% ja %%OUT_TYPE%% korvataan \'text\' tai \'json\' riippuen lähde/kohdekentän tyypeistä';
$lang["openai_gpt_api_key"]='OpenAI API-avain. Hanki API-avaimesi osoitteesta <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='OpenAI API GPT -integraatio';
$lang["plugin-openai_gpt-desc"]='OpenAI luoma metadata. Välittää konfiguroidut kenttätiedot OpenAI API:lle ja tallentaa palautetut tiedot.';
$lang["openai_gpt_model_override"]='Malli on lukittu globaaliin kokoonpanoon: [model]';
$lang["openai_gpt_processing_multiple_resources"]='Useita resursseja';
$lang["openai_gpt_processing_resource"]='Resurssi [resource]';
$lang["openai_gpt_processing_field"]='AI-käsittely kentälle \'[field]\'';
$lang["openai_gpt_language"] = 'Tulostuskieli';
$lang["openai_gpt_language_user"] = 'Nykyisen käyttäjän kieli';
$lang["openai_gpt_overwrite_data"] = 'Korvataanko olemassa olevat tiedot määritetyissä kentissä?';