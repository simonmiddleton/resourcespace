<?php


$lang["image_banks_configuration"]='Банки изображений';
$lang["image_banks_search_image_banks_label"]='Поиск изображений во внешних банках изображений';
$lang["image_banks_search_image_banks_info_text"]='Пожалуйста, укажите, в каком Банке изображений вы хотите выполнить поиск';
$lang["image_banks_pixabay_api_key"]='Ключ API';
$lang["image_banks_image_bank"]='Банк изображений';
$lang["image_banks_create_new_resource"]='Создать новый ресурс';
$lang["image_banks_provider_unmet_dependencies"]='Поставщик \'%PROVIDER\' имеет невыполненные зависимости!';
$lang["image_banks_provider_id_required"]='Требуется идентификатор провайдера для завершения поиска';
$lang["image_banks_provider_not_found"]='Поставщик не может быть идентифицирован с использованием ID';
$lang["image_banks_bad_request_title"]='Неверный запрос';
$lang["image_banks_bad_request_detail"]='Запрос не может быть обработан файлом \'%FILE\'';
$lang["image_banks_unable_to_create_resource"]='Невозможно создать новый ресурс!';
$lang["image_banks_unable_to_upload_file"]='Невозможно загрузить файл из внешнего банка изображений для ресурса #%RESOURCE';
$lang["image_banks_try_again_later"]='Пожалуйста, попробуйте позже!';
$lang["image_banks_warning"]='ПРЕДУПРЕЖДЕНИЕ:';
$lang["image_banks_warning_rate_limit_almost_reached"]='Провайдер \'%PROVIDER\' разрешает выполнение еще %RATE-LIMIT-REMAINING поисковых запросов. Этот лимит будет сброшен через %TIME';
$lang["image_banks_try_something_else"]='Попробуйте что-то другое.';
$lang["image_banks_error_detail_curl"]='Пакет php-curl не установлен';
$lang["image_banks_local_download_attempt"]='Пользователь попытался загрузить \'%FILE\' с помощью плагина ImageBank, указав систему, которая не является одним из разрешенных провайдеров';
$lang["image_banks_bad_file_create_attempt"]='Пользователь попытался создать ресурс с файлом \'%FILE\', используя плагин ImageBank, указав систему, которая не является одним из разрешенных провайдеров';
$lang["image_banks_shutterstock_token"]='Токен Shutterstock (<a href=\'https://www.shutterstock.com/account/developers/apps\' target=\'_blank\'>создать</a>)';
$lang["image_banks_shutterstock_result_limit"]='Ограничение результата (максимум 1000 для бесплатных аккаунтов)';
$lang["image_banks_shutterstock_id"]='Идентификатор изображения Shutterstock';
$lang["image_banks_createdfromimagebanks"]='Создано с помощью плагина "Банки изображений"';
$lang["image_banks_image_bank_source"]='Источник банка изображений';
$lang["image_banks_label_resourcespace_instances_cfg"]='Доступ к экземплярам (формат: i18n имя|базовый URL|имя пользователя|ключ|конфигурация)';
$lang["image_banks_resourcespace_file_information_description"]='ResourceSpace размер %SIZE_CODE';
$lang["image_banks_label_select_providers"]='Выберите активных поставщиков';
$lang["image_banks_view_on_provider_system"]='Просмотр в системе %PROVIDER';
$lang["image_banks_system_unmet_dependencies"]='Плагин ImageBanks имеет неудовлетворенные системные зависимости!';
$lang["image_banks_error_generic_parse"]='Не удалось разобрать конфигурацию провайдеров (для нескольких экземпляров)';
$lang["image_banks_error_resourcespace_invalid_instance_cfg"]='Неверный формат конфигурации для экземпляра \'%PROVIDER\' (поставщик)';
$lang["image_banks_error_bad_url_scheme"]='Обнаружена недопустимая схема URL для экземпляра \'%PROVIDER\' (поставщик)';
$lang["image_banks_error_unexpected_response"]='Извините, получен неожиданный ответ от провайдера. Пожалуйста, свяжитесь с системным администратором для дальнейшего расследования (см. журнал отладки).';
$lang["plugin-image_banks-title"]='Банки изображений';
$lang["plugin-image_banks-desc"]='Позволяет пользователям выбирать внешнюю Базу Изображений для поиска. Пользователи могут затем загружать или создавать новые ресурсы на основе полученных результатов.';