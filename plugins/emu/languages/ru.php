<?php


$lang["emu_configuration"]='Настройка EMu.';
$lang["emu_api_settings"]='Настройки сервера API.';
$lang["emu_api_server"]='Адрес сервера (например, http://[адрес.сервера])';
$lang["emu_api_server_port"]='Порт сервера.';
$lang["emu_resource_types"]='Выберите типы ресурсов, связанные с EMu.';
$lang["emu_email_notify"]='Адрес электронной почты, на который скрипт будет отправлять уведомления. Оставьте поле пустым, чтобы использовать адрес системы по умолчанию для уведомлений.';
$lang["emu_script_failure_notify_days"]='Количество дней, после которых отображать предупреждение и отправлять электронное письмо, если скрипт не завершился.';
$lang["emu_script_header"]='Включите скрипт, который автоматически обновляет данные EMu каждый раз, когда ResourceSpace запускает свою запланированную задачу (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Время последнего запуска скрипта';
$lang["emu_script_mode"]='Режим скрипта.';
$lang["emu_script_mode_option_1"]='Импортировать метаданные из EMu.';
$lang["emu_script_mode_option_2"]='Извлечь все записи EMu и поддерживать синхронизацию между RS и EMu.';
$lang["emu_enable_script"]='Включить скрипт EMu.';
$lang["emu_test_mode"]='Режим тестирования - Если установлено значение "true", скрипт будет запущен, но не будет обновлять ресурсы.';
$lang["emu_interval_run"]='Запускать скрипт через указанный интервал (например, +1 день, +2 недели, две недели). Оставьте поле пустым, и он будет запускаться каждый раз, когда запускается cron_copy_hitcount.php.';
$lang["emu_log_directory"]='Каталог для хранения журналов скриптов. Если это поле оставить пустым или ввести некорректные данные, то журналирование не будет осуществляться.';
$lang["emu_created_by_script_field"]='Поле метаданных, используемое для хранения информации о том, был ли ресурс создан с помощью скрипта EMu.';
$lang["emu_settings_header"]='Настройки EMu.';
$lang["emu_irn_field"]='Поле метаданных, используемое для хранения идентификатора EMu (IRN).';
$lang["emu_search_criteria"]='Критерии поиска для синхронизации EMu с ResourceSpace.';
$lang["emu_rs_mappings_header"]='Правила соответствия между EMu и ResourceSpace.';
$lang["emu_module"]='Модуль EMu.';
$lang["emu_column_name"]='Столбец модуля EMu.';
$lang["emu_rs_field"]='Поле ResourceSpace.';
$lang["emu_add_mapping"]='Добавить отображение.';
$lang["emu_confirm_upload_nodata"]='Пожалуйста, поставьте галочку, чтобы подтвердить, что вы хотите продолжить загрузку.';
$lang["emu_test_script_title"]='Тест/ Запустить скрипт.';
$lang["emu_run_script"]='Обработка';
$lang["emu_script_problem"]='ПРЕДУПРЕЖДЕНИЕ - скрипт EMu не был успешно завершен в течение последних %days% дней. Последнее время запуска:';
$lang["emu_no_resource"]='Не указан ID ресурса!';
$lang["emu_upload_nodata"]='Данные EMu не найдены для этого IRN:';
$lang["emu_nodata_returned"]='Данные EMu не найдены для указанного IRN.';
$lang["emu_createdfromemu"]='Создано с помощью плагина EMU.';