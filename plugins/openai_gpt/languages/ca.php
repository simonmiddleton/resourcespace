<?php


$lang["openai_gpt_title"]='Integració amb OpenAI';
$lang["property-openai_gpt_prompt"]='Prompt de GPT';
$lang["property-openai_gpt_input_field"]='Camp d\'entrada GPT';
$lang["openai_gpt_model"]='Nom del model d\'API per utilitzar (per exemple, \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Prefix de la indicació de finalització';
$lang["openai_gpt_prompt_return_text"]='Sufix de la indicació de finalització (per retornar text per als camps de text)';
$lang["openai_gpt_temperature"]='Mostra de temperatura entre 0 i 1 (valors més alts significa que el model prendrà més riscos)';
$lang["openai_gpt_max_tokens"]='Màxim de tokens';
$lang["openai_gpt_advanced"]='ATENCIÓ - Aquesta secció és només per a propòsits de prova i no s\'hauria de modificar en sistemes en viu. El canvi de qualsevol opció del connector aquí afectarà el comportament de tots els camps de metadades que s\'hagin configurat. Altereu amb precaució!';
$lang["openai_gpt_prompt_return_json"]='Sufix de la indicació de finalització (per retornar JSON per als camps de llista fixa)';
$lang["openai_gpt_system_message"]='Text inicial del sistema. Els espais reservats %%IN_TYPE%% i %%OUT_TYPE%% seran substituïts per \'text\' o \'json\' depenent dels tipus de camp d\'origen/destinació';
$lang["openai_gpt_intro"]='Afegeix metadades generades passant dades existents a l\'API d\'OpenAI amb un missatge personalitzable. Consulteu <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> per obtenir informació més detallada.';
$lang["openai_gpt_api_key"]='Clau API d\'OpenAI. Obteniu la vostra clau API des de <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='Integració de l\'API GPT d\'OpenAI';
$lang["plugin-openai_gpt-desc"]='Metadades generades per OpenAI. Passa les dades del camp configurat a l\'API d\'OpenAI i emmagatzema la informació retornada.';