<?php


$lang["csv_user_import_batch_user_import"]='Импорт пользователей пакетом';
$lang["csv_user_import_import"]='Импорт';
$lang["csv_user_import"]='Импорт пользователей из CSV';
$lang["csv_user_import_intro"]='Используйте эту функцию для импорта пакета пользователей в ResourceSpace. Обратите внимание на формат вашего CSV-файла и следуйте нижеприведенным стандартам:';
$lang["csv_user_import_upload_file"]='Выбрать файл';
$lang["csv_user_import_processing_file"]='ОБРАБОТКА ФАЙЛА...';
$lang["csv_user_import_error_found"]='Обнаружены ошибки - прерывание';
$lang["csv_user_import_move_upload_file_failure"]='Произошла ошибка при перемещении загруженного файла. Пожалуйста, попробуйте еще раз или свяжитесь с администраторами.';
$lang["csv_user_import_condition1"]='Убедитесь, что файл CSV закодирован с использованием <b>UTF-8</b>';
$lang["csv_user_import_condition2"]='CSV-файл должен содержать строку заголовка';
$lang["csv_user_import_condition3"]='Столбцы, которые будут содержать значения, содержащие <b>запятые (,)</b>, убедитесь, что вы форматируете их как тип <b>текст</b>, чтобы не приходилось добавлять кавычки (""). При сохранении в файл .csv убедитесь, что вы выбрали опцию цитирования ячеек типа текст';
$lang["csv_user_import_condition4"]='Разрешенные столбцы: *имя пользователя, *электронная почта, пароль, полное имя, срок действия учетной записи, комментарии, ограничение доступа по IP, язык. Примечание: обязательные поля отмечены знаком *';
$lang["csv_user_import_condition5"]='Язык пользователя будет возвращаться к значению, установленному с помощью опции конфигурации "$defaultlanguage", если столбец "lang" не найден или не имеет значения';
$lang["plugin-csv_user_import-title"]='Импорт пользователей из CSV';
$lang["plugin-csv_user_import-desc"]='[Расширенный] Предоставляет возможность импортировать пакет пользователей на основе предварительно отформатированного CSV файла';