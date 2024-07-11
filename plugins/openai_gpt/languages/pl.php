<?php


$lang["openai_gpt_title"]='Integracja OpenAI';
$lang["property-openai_gpt_prompt"]='Prośba o GPT';
$lang["property-openai_gpt_input_field"]='Pole wejściowe GPT';
$lang["openai_gpt_model"]='Nazwa modelu API do użycia (np. \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Prefiks podpowiedzi uzupełnienia';
$lang["openai_gpt_prompt_return_json"]='Przyrostek podpowiedzi uzupełnienia (do zwracania JSON dla pól z listą stałych wartości)';
$lang["openai_gpt_prompt_return_text"]='Przyrostek podpowiedzi uzupełnienia (do zwracania tekstu dla pól tekstowych)';
$lang["openai_gpt_temperature"]='Próbkowanie temperatury między 0 a 1 (wyższe wartości oznaczają, że model podejmie większe ryzyko)';
$lang["openai_gpt_max_tokens"]='Maksymalna liczba tokenów';
$lang["openai_gpt_advanced"]='OSTRZEŻENIE - Ta sekcja służy wyłącznie do celów testowych i nie powinna być zmieniana na żywych systemach. Zmiana jakiejkolwiek opcji wtyczki tutaj wpłynie na zachowanie wszystkich pól metadanych, które zostały skonfigurowane. Zmieniaj ostrożnie!';
$lang["openai_gpt_system_message"]='Początkowy tekst wiadomości systemowej. Miejsca %%IN_TYPE%% i %%OUT_TYPE%% zostaną zastąpione przez \'text\' lub \'json\' w zależności od typów pól źródłowych/docelowych';
$lang["openai_gpt_intro"]='Dodaje metadane generowane przez przekazanie istniejących danych do API OpenAI z konfigurowalnym promptem. Odwiedź <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> po więcej szczegółowych informacji.';
$lang["openai_gpt_api_key"]='Klucz API OpenAI. Uzyskaj swój klucz API z <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='Integracja z API GPT OpenAI';
$lang["plugin-openai_gpt-desc"]='OpenAI wygenerowane metadane. Przekazuje skonfigurowane dane pola do API OpenAI i przechowuje zwrócone informacje.';