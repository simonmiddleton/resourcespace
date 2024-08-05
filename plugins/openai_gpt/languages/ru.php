<?php


$lang["openai_gpt_title"]='Интеграция OpenAI';
$lang["property-openai_gpt_input_field"]='Поле ввода GPT';
$lang["openai_gpt_model"]='Название модели API для использования (например, \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Префикс подсказки завершения';
$lang["openai_gpt_prompt_return_json"]='Суффикс подсказки завершения (для возврата JSON для полей со списком фиксированных значений)';
$lang["openai_gpt_prompt_return_text"]='Суффикс завершения (для возврата текста в текстовых полях)';
$lang["openai_gpt_temperature"]='Выбор температуры от 0 до 1 (более высокие значения означают, что модель будет принимать больше рисков)';
$lang["openai_gpt_max_tokens"]='Максимальное количество токенов';
$lang["openai_gpt_advanced"]='ПРЕДУПРЕЖДЕНИЕ - Этот раздел предназначен только для тестирования и не должен изменяться на рабочих системах. Изменение любой из опций плагина здесь повлияет на поведение всех полей метаданных, которые были настроены. Изменяйте с осторожностью!';
$lang["openai_gpt_system_message"]='Начальный текст системного сообщения. Заполнители %%IN_TYPE%% и %%OUT_TYPE%% будут заменены на \'text\' или \'json\' в зависимости от типов исходного/целевого поля';
$lang["openai_gpt_intro"]='Добавляет метаданные, сгенерированные путем передачи существующих данных в OpenAI API с настраиваемым запросом. Обратитесь к <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> для получения более подробной информации.';
$lang["openai_gpt_api_key"]='Ключ API OpenAI. Получите свой ключ API на <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["property-openai_gpt_prompt"]='Подсказка GPT';
$lang["plugin-openai_gpt-title"]='Интеграция с OpenAI API GPT';
$lang["plugin-openai_gpt-desc"]='OpenAI сгенерированные метаданные. Передает настроенные данные полей в API OpenAI и сохраняет полученную информацию.';
$lang["openai_gpt_model_override"]='Модель была заблокирована в глобальной конфигурации на: [model]';