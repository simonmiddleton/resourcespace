<?php


$lang["openai_gpt_title"]='OpenAI-integrasjon';
$lang["property-openai_gpt_prompt"]='GPT-prompt';
$lang["property-openai_gpt_input_field"]='GPT inntastingsfelt';
$lang["openai_gpt_model"]='Navn på API-modell som skal brukes (f.eks. \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Fullføringsprompt-prefiks';
$lang["openai_gpt_prompt_return_json"]='Fullføringsprompt-suffiks (for å returnere JSON for fikserte liste-felt)';
$lang["openai_gpt_prompt_return_text"]='Avslutningstekst for utfylling (for å returnere tekst til tekstfelt)';
$lang["openai_gpt_temperature"]='Prøvetakingstemperatur mellom 0 og 1 (høyere verdier betyr at modellen vil ta flere risikoer)';
$lang["openai_gpt_max_tokens"]='Maksimum antall tokens';
$lang["openai_gpt_advanced"]='ADVARSEL - Denne seksjonen er kun for testformål og bør ikke endres på live systemer. Å endre noen av plugin-alternativene her vil påvirke oppførselen til alle metadatafeltene som er konfigurert. Endre med forsiktighet!';
$lang["openai_gpt_system_message"]='Opprinnelig systemmeldingstekst. Plassholdere %%IN_TYPE%% og %%OUT_TYPE%% vil bli erstattet med \'tekst\' eller \'json\' avhengig av kilde-/mål-felttypene';
$lang["openai_gpt_intro"]='Legger til metadata generert ved å sende eksisterende data til OpenAI API med en tilpassbar prompt. Se <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> for mer detaljert informasjon.';
$lang["openai_gpt_api_key"]='OpenAI API-nøkkel. Få din API-nøkkel fra <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='OpenAI API GPT-integrasjon';
$lang["plugin-openai_gpt-desc"]='OpenAI genererte metadata. Sender konfigurert feltdata til OpenAI API og lagrer den returnerte informasjonen.';