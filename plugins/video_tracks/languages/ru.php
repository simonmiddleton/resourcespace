<?php


$lang["video_tracks_title"]='Конфигурация видео-треков';
$lang["video_tracks_intro"]='Этот плагин позволяет использовать альтернативные файлы субтитров и аудиодорожек для создания пользовательских видеофайлов';
$lang["video_tracks_convert_vtt"]='Автоматически конвертировать файлы субтитров SubRip (.srt) в VTT для возможности их отображения в видеопревью';
$lang["video_tracks_audio_extensions"]='Список альтернативных расширений аудиофайлов (разделенных запятой), которые могут быть использованы для звуковой дорожки';
$lang["video_tracks_subtitle_extensions"]='Список разрешенных расширений файлов субтитров (разделенных запятой). Должны поддерживаться ffmpeg';
$lang["video_tracks_permitted_video_extensions"]='Показать опцию настраиваемого видео для этих расширений файлов';
$lang["video_tracks_create_video_link"]='Создать индивидуальное видео';
$lang["video_tracks_select_output"]='Выберите формат';
$lang["video_tracks_select_subtitle"]='Субтитры';
$lang["video_tracks_select_audio"]='Аудио';
$lang["video_tracks_invalid_resource"]='Недействительный ресурс';
$lang["video_tracks_invalid_option"]='Выбраны неверные параметры';
$lang["video_tracks_save_to"]='Сохранить в';
$lang["video_tracks_save_alternative"]='Альтернативный файл';
$lang["video_tracks_generate"]='Создать (Sozdat\')';
$lang["video_tracks_options"]='Доступные варианты вывода файлов. Их следует протестировать на сервере, чтобы убедиться, что синтаксис правильный для вашей установки ffmpeg/avconv';
$lang["video_tracks_command"]='команда ffmpeg/avconv';
$lang["video_tracks_option_name"]='Код формата вывода';
$lang["video_tracks_process_size_limit"]='Максимальный размер файла ресурса, который будет обработан немедленно (МБ). Более крупные файлы будут обработаны в автономном режиме, и пользователь будет уведомлен о завершении';
$lang["video_tracks_offline_notice"]='Ваш запрос был поставлен в очередь. Вы будете уведомлены, когда новый файл будет создан';
$lang["video_tracks_download_export"]='Когда файлы создаются в папке экспорта в автономном режиме, добавьте ссылку на уведомления, позволяющую загружать экспортированные файлы через веб-интерфейс';
$lang["video_tracks_config_blocked"]='Конфигурация форматов видеовыхода заблокирована. Пожалуйста, свяжитесь с вашим системным администратором';
$lang["video_tracks_command_missing"]='Доступные варианты вывода файлов неполные. Если эта ошибка продолжается, пожалуйста, свяжитесь с вашим системным администратором';
$lang["video_tracks_generate_label"]='Создать (Sozdat\')';
$lang["video_tracks_custom_video_formats_label"]='Пользовательские форматы';
$lang["video_tracks_use_for_custom_video_formats_of_original_label"]='Разрешить использование доступных опций вывода файла для создания пользовательских форматов видео для исходного файла';
$lang["video_tracks_transcode_now_or_notify_me_label"]='Отметьте, чтобы начать транскодирование сейчас. Если не отмечено, вы получите уведомление, когда файл будет готов';
$lang["video_tracks_transcode_now_label"]='Транскодировать сейчас';
$lang["video_tracks_select_generate_opt"]='Пожалуйста, выберите вариант генерации';
$lang["video_tracks_save_alt_not_perm"]='Невозможно сохранить альтернативу без разрешения';
$lang["video_tracks_upgrade_msg_deprecated_output_format"]='ВАЖНО! Плагин Video Tracks устарел настройки форматов вывода. Их можно установить только в config.php. Плагин не будет работать должным образом, пока параметр конфигурации не будет скопирован. Пожалуйста, скопируйте следующее:- %nl%####%nl%[output_formats_config]####%nl%';