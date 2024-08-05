<?php


$lang["openai_gpt_title"]='OpenAI integration';
$lang["property-openai_gpt_prompt"]='GPT Prompt kan oversættes til "GPT-anmodning"';
$lang["property-openai_gpt_input_field"]='GPT Indtastningsfelt';
$lang["openai_gpt_model"]='Navn på API-model til brug (f.eks. \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Fuldførelsesprompt-præfiks';
$lang["openai_gpt_prompt_return_json"]='Afslutningsprompt-suffix (til at returnere JSON for faste liste-felter)';
$lang["openai_gpt_prompt_return_text"]='Afslutningsprompt-suffix (til at returnere tekst til tekstfelter)';
$lang["openai_gpt_temperature"]='Prøvetagnings temperatur mellem 0 og 1 (højere værdier betyder, at modellen vil tage flere risici)';
$lang["openai_gpt_max_tokens"]='Maksimum antal tokens';
$lang["openai_gpt_advanced"]='ADVARSEL - Denne sektion er kun til testformål og bør ikke ændres på live systemer. Ændring af nogen af plugin-indstillingerne her vil påvirke adfærden af alle metadatafelter, der er blevet konfigureret. Ændr med forsigtighed!';
$lang["openai_gpt_system_message"]='Indledende systembeskedtekst. Pladsholdere %%IN_TYPE%% og %%OUT_TYPE%% vil blive erstattet af \'tekst\' eller \'json\' afhængigt af kilde-/mål-felttyperne';
$lang["openai_gpt_intro"]='Tilføjer metadata genereret ved at sende eksisterende data til OpenAI API med en tilpasselig prompt. Se <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> for mere detaljeret information.';
$lang["openai_gpt_api_key"]='OpenAI API-nøgle. Få din API-nøgle fra <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='OpenAI API GPT integration';
$lang["plugin-openai_gpt-desc"]='OpenAI genererede metadata. Sender konfigurerede feltdata til OpenAI API\'et og gemmer de returnerede oplysninger.';
$lang["openai_gpt_model_override"]='Modellen er blevet låst i den globale konfiguration til: [model]';