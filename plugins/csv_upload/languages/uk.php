<?php


$lang["csv_upload_nav_link"]='Завантаження CSV';
$lang["csv_upload_intro"]='Цей плагін дозволяє створювати або оновлювати ресурси шляхом завантаження файлу CSV. Формат CSV є важливим';
$lang["csv_upload_condition1"]='Переконайтеся, що файл CSV закодовано за допомогою <b>UTF-8 без BOM</b>.';
$lang["csv_upload_condition2"]='CSV-файл повинен мати рядок заголовка';
$lang["csv_upload_condition3"]='Щоб мати можливість завантажувати файли ресурсів пізніше, використовуючи функцію пакетної заміни, повинна бути колонка з назвою \'Original filename\', і кожен файл повинен мати унікальну назву файлу';
$lang["csv_upload_condition4"]='Усі обов\'язкові поля для будь-яких новостворених ресурсів повинні бути присутніми в CSV';
$lang["csv_upload_condition5"]='Для стовпців, які містять значення з <b>комами( , )</b>, переконайтеся, що ви форматували їх як тип "текст", щоб вам не довелося додавати лапки (""). При збереженні у файл csv, переконайтеся, що ви вибрали опцію цитування текстових комірок';
$lang["csv_upload_condition6"]='Ви можете завантажити приклад файлу CSV, натиснувши на <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Щоб оновити існуючі дані ресурсу, ви можете завантажити CSV з існуючими метаданими, натиснувши на опцію \'Експорт CSV - метадані\' в меню дій колекції або результатів пошуку';
$lang["csv_upload_condition8"]='Ви можете повторно використовувати раніше налаштований файл зіставлення CSV, натиснувши на \'Завантажити файл конфігурації CSV\'';
$lang["csv_upload_error_no_permission"]='У вас немає відповідних дозволів для завантаження файлу CSV';
$lang["check_line_count"]='Знайдено принаймні два рядки у файлі CSV';
$lang["csv_upload_file"]='Виберіть файл CSV';
$lang["csv_upload_default"]='За замовчуванням';
$lang["csv_upload_error_no_header"]='У файлі не знайдено заголовкового рядка';
$lang["csv_upload_update_existing"]='Оновити існуючі ресурси? Якщо цей параметр не вибрано, то нові ресурси будуть створені на основі даних CSV';
$lang["csv_upload_update_existing_collection"]='Оновлювати ресурси лише в конкретній колекції?';
$lang["csv_upload_process"]='Процес';
$lang["csv_upload_add_to_collection"]='Додати новостворені ресурси до поточної колекції?';
$lang["csv_upload_step1"]='Крок 1 - Виберіть файл';
$lang["csv_upload_step2"]='Крок 2 - Параметри ресурсу за замовчуванням';
$lang["csv_upload_step3"]='Крок 3 - Відповідність стовпців полям метаданих';
$lang["csv_upload_step4"]='Крок 4 - Перевірка даних CSV';
$lang["csv_upload_step5"]='Крок 5 - Обробка CSV';
$lang["csv_upload_update_existing_title"]='Оновити існуючі ресурси';
$lang["csv_upload_update_existing_notes"]='Виберіть необхідні параметри для оновлення існуючих ресурсів';
$lang["csv_upload_create_new_title"]='Створити нові ресурси';
$lang["csv_upload_create_new_notes"]='Виберіть необхідні параметри для створення нових ресурсів';
$lang["csv_upload_map_fields_notes"]='Зіставте стовпці у CSV з необхідними полями метаданих. Натискання \'Далі\' перевірить CSV без фактичної зміни даних';
$lang["csv_upload_map_fields_auto_notes"]='Поля метаданих були попередньо вибрані на основі імен або назв, але, будь ласка, перевірте, чи вони правильні';
$lang["csv_upload_workflow_column"]='Виберіть стовпець, який містить ідентифікатор стану робочого процесу';
$lang["csv_upload_workflow_default"]='Стан робочого процесу за замовчуванням, якщо не вибрано стовпець або не знайдено дійсний стан у стовпці';
$lang["csv_upload_access_column"]='Виберіть стовпець, який містить рівень доступу (0=Відкритий, 1=Обмежений, 2=Конфіденційний)';
$lang["csv_upload_access_default"]='Рівень доступу за замовчуванням, якщо не вибрано жодної колонки або якщо в колонці не знайдено дійсного доступу';
$lang["csv_upload_resource_type_column"]='Виберіть стовпець, який містить ідентифікатор типу ресурсу';
$lang["csv_upload_resource_type_default"]='Тип ресурсу за замовчуванням, якщо не вибрано стовпець або якщо в стовпці не знайдено дійсний тип';
$lang["csv_upload_resource_match_column"]='Виберіть стовпець, який містить ідентифікатор ресурсу';
$lang["csv_upload_match_type"]='Зіставити ресурс за ідентифікатором ресурсу або значенням поля метаданих?';
$lang["csv_upload_multiple_match_action"]='Дія, яку слід виконати, якщо знайдено кілька відповідних ресурсів';
$lang["csv_upload_validation_notes"]='Перевірте повідомлення про перевірку нижче перед продовженням. Натисніть Обробити, щоб застосувати зміни';
$lang["csv_upload_upload_another"]='Завантажити інший CSV';
$lang["csv_upload_mapping config"]='Налаштування відповідності стовпців CSV';
$lang["csv_upload_download_config"]='Завантажити налаштування мапування CSV як файл';
$lang["csv_upload_upload_config"]='Завантажити файл зіставлення CSV';
$lang["csv_upload_upload_config_question"]='Завантажити файл зіставлення CSV? Використовуйте це, якщо ви раніше завантажували подібний CSV і зберегли конфігурацію';
$lang["csv_upload_upload_config_set"]='Набір конфігурації CSV';
$lang["csv_upload_upload_config_clear"]='Очистити конфігурацію відображення CSV';
$lang["csv_upload_mapping_ignore"]='НЕ ВИКОРИСТОВУВАТИ';
$lang["csv_upload_mapping_header"]='Заголовок стовпця';
$lang["csv_upload_mapping_csv_data"]='Зразок даних з CSV';
$lang["csv_upload_using_config"]='Використання існуючої конфігурації CSV';
$lang["csv_upload_process_offline"]='Обробити CSV файл офлайн? Це слід використовувати для великих CSV файлів. Ви отримаєте повідомлення через ResourceSpace, коли обробка буде завершена';
$lang["csv_upload_oj_created"]='Створено завдання завантаження CSV з ідентифікатором завдання # [jobref]. <br/>Ви отримаєте системне повідомлення ResourceSpace після завершення завдання';
$lang["csv_upload_oj_complete"]='Завантаження CSV завершено. Натисніть посилання, щоб переглянути повний журнал файлів';
$lang["csv_upload_oj_failed"]='Завантаження CSV не вдалося. Натисніть на посилання, щоб переглянути повний журнал і перевірити наявність помилок';
$lang["csv_upload_processing_x_meta_columns"]='Обробка %count стовпців метаданих';
$lang["csv_upload_processing_complete"]='Обробка завершена о [time] ([hours] годин, [minutes] хвилин, [seconds] секунд';
$lang["csv_upload_error_in_progress"]='Обробку перервано - цей файл CSV вже обробляється';
$lang["csv_upload_error_file_missing"]='Помилка - Відсутній файл CSV: [file]';
$lang["csv_upload_full_messages_link"]='Показано лише перші 1000 рядків, щоб завантажити повний файл журналу, будь ласка, натисніть <a href=\'[log_url]\' target=\'_blank\'>тут</a>';
$lang["csv_upload_ignore_errors"]='Ігнорувати помилки та все одно обробити файл';
$lang["csv_upload_process_offline_quick"]='Пропустити перевірку та обробити CSV файл офлайн? Це слід використовувати лише для великих CSV файлів після завершення тестування на менших файлах. Ви отримаєте повідомлення через ResourceSpace, коли завантаження буде завершено';
$lang["csv_upload_force_offline"]='Цей великий CSV може зайняти багато часу для обробки, тому буде виконаний офлайн. Ви отримаєте повідомлення через ResourceSpace, коли обробка буде завершена';
$lang["csv_upload_recommend_offline"]='Цей великий CSV може зайняти дуже багато часу для обробки. Рекомендується увімкнути офлайн-завдання, якщо вам потрібно обробляти великі CSV.';
$lang["csv_upload_createdfromcsvupload"]='Створено за допомогою плагіна завантаження CSV';