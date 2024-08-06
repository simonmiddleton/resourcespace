<?php


$lang["openai_gpt_title"]='Integracija OpenAI-ja';
$lang["property-openai_gpt_prompt"]='GPT uputa';
$lang["property-openai_gpt_input_field"]='Polje za unos GPT-a';
$lang["openai_gpt_model"]='Naziv API modela za upotrebu (npr. \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Prefiks upozorenja o dovršetku';
$lang["openai_gpt_prompt_return_json"]='Završni nastavak upute (za povrat JSON-a za polja s fiksnim popisom)';
$lang["openai_gpt_prompt_return_text"]='Završni nastavak upute (za vraćanje teksta za tekstualna polja)';
$lang["openai_gpt_temperature"]='Uzorkovanje temperature između 0 i 1 (više vrijednosti znači da će model preuzeti više rizika)';
$lang["openai_gpt_max_tokens"]='Maksimalni tokeni';
$lang["openai_gpt_advanced"]='UPOZORENJE - Ovaj odjeljak je namijenjen samo za testiranje i ne bi se trebao mijenjati na aktivnim sustavima. Promjena bilo koje opcije dodatka ovdje će utjecati na ponašanje svih polja metapodataka koja su konfigurirana. Promijenite s oprezom!';
$lang["openai_gpt_system_message"]='Početni tekst sistemske poruke. Zamjenski znakovi %%IN_TYPE%% i %%OUT_TYPE%% bit će zamijenjeni s \'text\' ili \'json\' ovisno o vrstama izvornog/ciljnog polja';
$lang["openai_gpt_intro"]='Dodaje metapodatke generirane prosljeđivanjem postojećih podataka OpenAI API-ju s prilagodljivim upitom. Pogledajte <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> za detaljnije informacije.';
$lang["openai_gpt_api_key"]='OpenAI API ključ. Nabavite svoj API ključ na <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='Integracija OpenAI API GPT';
$lang["plugin-openai_gpt-desc"]='OpenAI generirani metapodaci. Prosljeđuje konfigurirane podatke polja OpenAI API-ju i pohranjuje vraćene informacije.';
$lang["openai_gpt_model_override"]='Model je zaključan u globalnoj konfiguraciji na: [model]';
$lang["openai_gpt_processing_multiple_resources"]='Više resursa';
$lang["openai_gpt_processing_resource"]='Resurs [resource]';
$lang["openai_gpt_processing_field"]='AI obrada za polje \'[field]\'';
$lang["property-gpt_source"]='Izvor GPT';