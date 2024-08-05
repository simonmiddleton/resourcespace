<?php


$lang["openai_gpt_title"]='OpenAI-integraatio';
$lang["property-openai_gpt_prompt"]='GPT-pyyntö';
$lang["property-openai_gpt_input_field"]='GPT-syötekenttä';
$lang["openai_gpt_model"]='Käytettävän API-mallin nimi (esim. \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Valmistumisen kehotteen etuliite';
$lang["openai_gpt_prompt_return_json"]='Valmistumisilmoituksen liiteosa (palauttaa JSON-muotoisen kiinteän listan kentät)';
$lang["openai_gpt_prompt_return_text"]='Valmistumisilmoituksen liite (palauttaa tekstin tekstikenttiin)';
$lang["openai_gpt_temperature"]='Näytteenotto lämpötila välillä 0 ja 1 (korkeammat arvot tarkoittavat sitä, että malli ottaa enemmän riskejä)';
$lang["openai_gpt_max_tokens"]='Maksimi merkkiavaimet';
$lang["openai_gpt_advanced"]='VAROITUS - Tämä osio on tarkoitettu vain testaustarkoituksiin eikä sitä tulisi muuttaa tuotantojärjestelmissä. Tämän osion plugin-asetusten muuttaminen vaikuttaa kaikkien määritettyjen metatietokenttien käyttäytymiseen. Muuta varoen!';
$lang["openai_gpt_system_message"]='Alkuperäinen järjestelmäviestin teksti. Paikkamerkit %%IN_TYPE%% ja %%OUT_TYPE%% korvataan \'text\' tai \'json\' riippuen lähde/kohdekentän tyypeistä';
$lang["openai_gpt_intro"]='Lisää metatietoja, jotka on luotu syöttämällä olemassa olevia tietoja OpenAI API:lle mukautettavalla kehotteella. Katso tarkempia tietoja osoitteesta <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a>.';
$lang["openai_gpt_api_key"]='OpenAI API-avain. Hanki API-avaimesi osoitteesta <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='OpenAI API GPT -integraatio';
$lang["plugin-openai_gpt-desc"]='OpenAI luoma metadata. Välittää konfiguroidut kenttätiedot OpenAI API:lle ja tallentaa palautetut tiedot.';
$lang["openai_gpt_model_override"]='Malli on lukittu globaaliin kokoonpanoon: [model]';