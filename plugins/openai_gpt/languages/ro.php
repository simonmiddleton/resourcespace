<?php


$lang["openai_gpt_title"]='Integrarea OpenAI';
$lang["property-openai_gpt_prompt"]='Sugestie GPT';
$lang["property-openai_gpt_input_field"]='Câmp de intrare GPT';
$lang["openai_gpt_model"]='Numele modelului API de utilizat (de exemplu, \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Prefix pentru prompt-ul de finalizare';
$lang["openai_gpt_prompt_return_json"]='Sufix prompt de finalizare (pentru a returna JSON pentru câmpurile cu liste fixe)';
$lang["openai_gpt_prompt_return_text"]='Sufix prompt finalizare (pentru a returna text pentru câmpurile de text)';
$lang["openai_gpt_temperature"]='Temperatura de eșantionare între 0 și 1 (valori mai mari înseamnă că modelul va lua mai multe riscuri)';
$lang["openai_gpt_max_tokens"]='Număr maxim de etichete (Maxim de etichete permise)';
$lang["openai_gpt_advanced"]='ATENȚIE - Această secțiune este destinată doar pentru teste și nu ar trebui să fie modificată în sistemele live. Schimbarea oricăror opțiuni ale plugin-ului aici va afecta comportamentul tuturor câmpurilor de metadate care au fost configurate. Modificați cu prudență!';
$lang["openai_gpt_system_message"]='Textul inițial al mesajului sistemului. Locurile rezervate %%IN_TYPE%% și %%OUT_TYPE%% vor fi înlocuite cu \'text\' sau \'json\' în funcție de tipurile de câmp sursă/țintă';
$lang["openai_gpt_intro"]='Adaugă metadate generate prin trimiterea datelor existente către API-ul OpenAI cu un prompt personalizabil. Consultați <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> pentru informații mai detaliate.';
$lang["openai_gpt_api_key"]='Cheie API OpenAI. Obțineți cheia API de la <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='Integrare API GPT OpenAI';
$lang["plugin-openai_gpt-desc"]='Metadate generate de OpenAI. Transmite datele câmpului configurat către API-ul OpenAI și stochează informațiile returnate.';