<?php


$lang["openai_gpt_temperature"]='Prøvetakingstemperatur mellom 0 og 1 (høyere verdier betyr at modellen vil ta flere risikoer)';
$lang["openai_gpt_max_tokens"]='Maksimum antall tokens';
$lang["openai_gpt_advanced"]='ADVARSEL - Denne seksjonen er kun for testformål og bør ikke endres på live systemer. Å endre noen av plugin-alternativene her vil påvirke oppførselen til alle metadatafeltene som er konfigurert. Endre med forsiktighet!';
$lang["openai_gpt_system_message"]='Opprinnelig systemmeldingstekst. Plassholdere %%IN_TYPE%% og %%OUT_TYPE%% vil bli erstattet med \'tekst\' eller \'json\' avhengig av kilde-/mål-felttypene';
$lang["openai_gpt_api_key"]='OpenAI API-nøkkel. Få din API-nøkkel fra <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='OpenAI API GPT-integrasjon';
$lang["plugin-openai_gpt-desc"]='OpenAI genererte metadata. Sender konfigurert feltdata til OpenAI API og lagrer den returnerte informasjonen.';
$lang["openai_gpt_model_override"]='Modellen har blitt låst i global konfigurasjon til: [model]';
$lang["openai_gpt_processing_multiple_resources"]='Flere ressurser';
$lang["openai_gpt_processing_resource"]='Ressurs [resource]';
$lang["openai_gpt_processing_field"]='AI-behandling for feltet \'[field]\'';
$lang["openai_gpt_language"] = 'Utdata språk';
$lang["openai_gpt_language_user"] = 'Språket til den nåværende brukeren';
$lang["openai_gpt_overwrite_data"] = 'Overskriv eksisterende data i konfigurerte felt?';