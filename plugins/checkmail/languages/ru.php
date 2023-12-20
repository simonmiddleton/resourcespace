<?php


$lang["checkmail_configuration"]='Настройка проверки почты.';
$lang["checkmail_install_php_imap_extension"]='Шаг первый: Установите расширение php imap.';
$lang["checkmail_cronhelp"]='Этот плагин требует специальной настройки для входа в почтовый ящик, предназначенный для получения файлов, предназначенных для загрузки.<br /><br />Убедитесь, что на аккаунте включен IMAP. Если вы используете учетную запись Gmail, вы можете включить IMAP в Настройки->POP/IMAP->Включить IMAP.<br /><br />
При первоначальной настройке вам может быть полезно запустить вручную плагин plugins/checkmail/pages/cron_check_email.php из командной строки, чтобы понять, как он работает.
Как только вы успешно подключитесь и поймете, как работает скрипт, вы должны настроить cron-задание для запуска его каждую минуту или две.<br />Он будет сканировать почтовый ящик и читать одно непрочитанное письмо за один запуск.<br /><br />
Пример cron-задания, которое запускается каждые две минуты:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Ваша учетная запись IMAP была проверена в последний раз [lastcheck].';
$lang["checkmail_cronjobprob"]='Ваша задача cronjob для проверки электронной почты может работать неправильно, потому что прошло более 5 минут с момента последнего запуска.<br /><br />
Пример cron job, который запускается каждую минуту:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Сервер Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Электронная почта';
$lang["checkmail_password"]='Пароль.';
$lang["checkmail_extension_mapping"]='Тип ресурса через сопоставление расширений файлов.';
$lang["checkmail_default_resource_type"]='Тип ресурса по умолчанию.';
$lang["checkmail_extension_mapping_desc"]='После селектора типа ресурса по умолчанию, ниже находится одно поле ввода для каждого из ваших типов ресурсов. <br />Чтобы принудительно загружать файлы разных типов в определенный тип ресурса, добавьте списки расширений файлов, разделенные запятыми (например, jpg, gif, png).';
$lang["checkmail_subject_field"]='Поле темы';
$lang["checkmail_body_field"]='Поле тела.';
$lang["checkmail_purge"]='Удалить электронные письма после загрузки?';
$lang["checkmail_confirm"]='Отправить письма с подтверждением?';
$lang["checkmail_users"]='Разрешенные пользователи.';
$lang["checkmail_blocked_users_label"]='Заблокированные пользователи.';
$lang["checkmail_default_access"]='Стандартный доступ.';
$lang["checkmail_default_archive"]='Статус по умолчанию.';
$lang["checkmail_html"]='Разрешить содержимое HTML? (экспериментальная функция, не рекомендуется)';
$lang["checkmail_mail_skipped"]='Пропущенное электронное письмо.';
$lang["checkmail_allow_users_based_on_permission_label"]='Следует ли разрешить пользователям загружать файлы на основе их прав доступа?';
$lang["addresourcesviaemail"]='Добавить через электронную почту.';
$lang["uploadviaemail"]='Добавить через электронную почту.';
$lang["uploadviaemail-intro"]='Для загрузки через электронную почту, прикрепите файл(ы) и отправьте письмо на адрес <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Убедитесь, что отправляете письмо с адреса <b>[fromaddress]</b>, иначе оно будет проигнорировано.</p><p>Обратите внимание, что все, что находится в ТЕМЕ письма, будет помещено в поле [subjectfield] в %applicationname%.</p><p>Также обратите внимание, что все, что находится в ТЕЛЕ письма, будет помещено в поле [bodyfield] в %applicationname%.</p><p>Несколько файлов будут сгруппированы в коллекцию. Ваши ресурсы по умолчанию будут иметь уровень доступа <b>\'[access]\'</b> и статус архива <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Вы получите подтверждение на электронную почту, когда ваше сообщение будет успешно обработано. Если ваше сообщение будет пропущено по какой-либо причине (например, если оно отправлено с неправильного адреса), администратор будет уведомлен о наличии сообщения, требующего внимания.';
$lang["yourresourcehasbeenuploaded"]='Ваш ресурс был загружен.';
$lang["yourresourceshavebeenuploaded"]='Ваши ресурсы были загружены.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), с ID [user-ref] и электронной почтой [user-email] не имеет разрешения на загрузку через электронную почту (проверьте разрешения "c" или "d" или заблокированных пользователей на странице настройки проверки почты). Записано: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Создано с помощью плагина "Проверить почту".';