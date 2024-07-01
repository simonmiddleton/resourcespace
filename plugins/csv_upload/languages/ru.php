<?php


$lang["csv_upload_nav_link"]='Загрузка CSV';
$lang["csv_upload_intro"]='Этот плагин позволяет создавать или обновлять ресурсы, загружая файл CSV. Формат CSV имеет важное значение';
$lang["csv_upload_condition1"]='Убедитесь, что файл CSV закодирован с использованием <b>UTF-8 без BOM</b>.';
$lang["csv_upload_condition2"]='CSV-файл должен содержать строку заголовка';
$lang["csv_upload_condition3"]='Чтобы в дальнейшем можно было загружать файлы ресурсов с помощью функции пакетной замены, должен быть столбец с названием "Оригинальное имя файла", и каждый файл должен иметь уникальное имя';
$lang["csv_upload_condition4"]='Все обязательные поля для любых новых создаваемых ресурсов должны присутствовать в CSV';
$lang["csv_upload_condition5"]='Для столбцов, значения которых содержат <b>запятые (,)</b>, убедитесь, что вы форматируете их как тип <b>текст</b>, чтобы не приходилось добавлять кавычки (""). При сохранении в файл csv убедитесь, что вы выбрали опцию цитирования ячеек типа текст';
$lang["csv_upload_condition6"]='Вы можете скачать пример файла CSV, нажав на <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Чтобы обновить существующие данные ресурса, вы можете загрузить CSV-файл с существующей метаданными, нажав на опцию "CSV-экспорт - метаданные" в меню действий коллекции или результатов поиска';
$lang["csv_upload_condition8"]='Вы можете повторно использовать ранее настроенный файл отображения CSV, нажав на кнопку "Загрузить файл конфигурации CSV"';
$lang["csv_upload_error_no_permission"]='У вас нет прав для загрузки файла CSV';
$lang["check_line_count"]='Найдено как минимум две строки в CSV файле';
$lang["csv_upload_file"]='Выберите файл CSV';
$lang["csv_upload_default"]='По умолчанию';
$lang["csv_upload_error_no_header"]='Заголовочная строка не найдена в файле';
$lang["csv_upload_update_existing"]='Обновить существующие ресурсы? Если это не отмечено, то новые ресурсы будут созданы на основе данных CSV';
$lang["csv_upload_update_existing_collection"]='Обновить только ресурсы в определенной коллекции?';
$lang["csv_upload_process"]='Обработка';
$lang["csv_upload_add_to_collection"]='Добавить только что созданные ресурсы в текущую коллекцию?';
$lang["csv_upload_step1"]='Шаг 1 - Выберите файл';
$lang["csv_upload_step2"]='Шаг 2 - Опции ресурса по умолчанию';
$lang["csv_upload_step3"]='Шаг 3 - Сопоставление столбцов с полями метаданных';
$lang["csv_upload_step4"]='Шаг 4 - Проверка данных CSV';
$lang["csv_upload_step5"]='Шаг 5 - Обработка CSV';
$lang["csv_upload_update_existing_title"]='Обновить существующие ресурсы';
$lang["csv_upload_update_existing_notes"]='Выберите необходимые опции для обновления существующих ресурсов';
$lang["csv_upload_create_new_title"]='Создать новые ресурсы';
$lang["csv_upload_create_new_notes"]='Выберите необходимые опции для создания новых ресурсов';
$lang["csv_upload_map_fields_notes"]='Сопоставьте столбцы в CSV с требуемыми полями метаданных. Нажатие кнопки "Далее" проверит CSV, не изменяя данные';
$lang["csv_upload_map_fields_auto_notes"]='Поля метаданных были предварительно выбраны на основе имен или заголовков, но, пожалуйста, проверьте, что они верны';
$lang["csv_upload_workflow_column"]='Выберите столбец, содержащий идентификатор состояния рабочего процесса';
$lang["csv_upload_workflow_default"]='Стандартное состояние рабочего процесса, если не выбран столбец или если в столбце не найдено допустимое состояние';
$lang["csv_upload_access_column"]='Выберите столбец, содержащий уровень доступа (0=Открытый, 1=Ограниченный, 2=Конфиденциальный)';
$lang["csv_upload_access_default"]='Уровень доступа по умолчанию, если не выбран столбец или если в столбце не найден допустимый уровень доступа';
$lang["csv_upload_resource_type_column"]='Выберите столбец, содержащий идентификатор типа ресурса';
$lang["csv_upload_resource_type_default"]='Тип ресурса по умолчанию, если не выбран столбец или если в столбце не найден допустимый тип';
$lang["csv_upload_resource_match_column"]='Выберите столбец, содержащий идентификатор ресурса';
$lang["csv_upload_match_type"]='Сопоставить ресурс на основе ID ресурса или значения поля метаданных?';
$lang["csv_upload_multiple_match_action"]='Действие, которое следует выполнить, если найдено несколько соответствующих ресурсов';
$lang["csv_upload_validation_notes"]='Пожалуйста, проверьте сообщения об ошибках ниже перед продолжением. Нажмите "Обработать", чтобы сохранить изменения';
$lang["csv_upload_upload_another"]='Загрузить другой CSV файл';
$lang["csv_upload_mapping config"]='Настройки сопоставления столбцов CSV';
$lang["csv_upload_download_config"]='Скачать настройки сопоставления CSV в виде файла';
$lang["csv_upload_upload_config"]='Загрузить файл сопоставления CSV';
$lang["csv_upload_upload_config_question"]='Загрузить файл сопоставления CSV? Используйте это, если вы уже загружали похожий CSV ранее и сохранили конфигурацию';
$lang["csv_upload_upload_config_set"]='CSV настройки набора';
$lang["csv_upload_upload_config_clear"]='Очистить конфигурацию сопоставления CSV';
$lang["csv_upload_mapping_ignore"]='НЕ ИСПОЛЬЗОВАТЬ';
$lang["csv_upload_mapping_header"]='Заголовок столбца';
$lang["csv_upload_mapping_csv_data"]='Образец данных из CSV';
$lang["csv_upload_using_config"]='Использование существующей конфигурации CSV';
$lang["csv_upload_process_offline"]='Обработать CSV-файл офлайн? Это следует использовать для больших CSV-файлов. Вы будете уведомлены сообщением в ResourceSpace, когда обработка будет завершена';
$lang["csv_upload_oj_created"]='Задание на загрузку CSV-файла создано с идентификатором задания # %%JOBREF%%. <br/>Вы получите сообщение от системы ResourceSpace, когда задание будет завершено';
$lang["csv_upload_oj_complete"]='Загрузка задания CSV завершена. Нажмите на ссылку, чтобы просмотреть полный файл журнала';
$lang["csv_upload_oj_failed"]='Задание загрузки CSV не удалось';
$lang["csv_upload_processing_x_meta_columns"]='Обработка %count столбцов метаданных';
$lang["csv_upload_processing_complete"]='Обработка завершена в [time] (%%HOURS%% часов, %%MINUTES%% минут, %%SECONDS%% секунд)';
$lang["csv_upload_error_in_progress"]='Обработка прервана - этот файл CSV уже обрабатывается';
$lang["csv_upload_error_file_missing"]='Ошибка - отсутствует файл CSV: %%FILE%%';
$lang["csv_upload_full_messages_link"]='Показываются только первые 1000 строк, чтобы загрузить полный файл журнала, пожалуйста, нажмите <a href=\'%%LOG_URL%%\' target=\'_blank\'>здесь</a>';
$lang["csv_upload_ignore_errors"]='Игнорировать ошибки и обработать файл в любом случае';
$lang["csv_upload_process_offline_quick"]='Пропустить проверку и обработать CSV-файл в автономном режиме? Это следует использовать только для больших CSV-файлов, когда тестирование на более маленьких файлах было завершено. Вы получите уведомление через сообщение ResourceSpace, когда загрузка будет завершена';
$lang["csv_upload_force_offline"]='Этот большой CSV файл может занять много времени на обработку, поэтому он будет обработан в автономном режиме. Вы будете уведомлены через сообщение в ResourceSpace, когда обработка будет завершена';
$lang["csv_upload_recommend_offline"]='Этот большой CSV файл может занять очень много времени на обработку. Рекомендуется включить задания в автономном режиме, если вам нужно обрабатывать большие CSV файлы';
$lang["csv_upload_createdfromcsvupload"]='Создано с помощью плагина загрузки CSV';