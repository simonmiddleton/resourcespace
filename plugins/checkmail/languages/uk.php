<?php


$lang["checkmail_configuration"]='Конфігурація Checkmail';
$lang["checkmail_install_php_imap_extension"]='Крок перший: Встановіть розширення php imap.';
$lang["checkmail_cronhelp"]='Цей плагін вимагає спеціального налаштування для того, щоб система могла увійти до облікового запису електронної пошти, призначеного для отримання файлів, які мають бути завантажені.<br /><br />Переконайтеся, що IMAP увімкнено в обліковому записі. Якщо ви використовуєте обліковий запис Gmail, увімкніть IMAP у Налаштуваннях->POP/IMAP->Увімкнути IMAP<br /><br />
Під час початкового налаштування може бути корисно вручну запустити plugins/checkmail/pages/cron_check_email.php з командного рядка, щоб зрозуміти, як він працює.
Коли ви правильно підключитеся і зрозумієте, як працює скрипт, необхідно налаштувати cron-завдання для його запуску кожну хвилину або дві.<br />Він буде сканувати поштову скриньку і читати один непрочитаний електронний лист за запуск.<br /><br />
Приклад cron-завдання, яке запускається кожні дві хвилини:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Ваш IMAP обліковий запис було востаннє перевірено [lastcheck].';
$lang["checkmail_cronjobprob"]='Ваш cronjob перевірки пошти може не працювати належним чином, оскільки минуло більше 5 хвилин з моменту його останнього запуску.<br /><br />
Приклад cronjob, який запускається щохвилини:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Сервер Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Електронна пошта';
$lang["checkmail_password"]='Пароль';
$lang["checkmail_extension_mapping"]='Тип ресурсу через відображення розширення файлу';
$lang["checkmail_default_resource_type"]='Тип ресурсу за замовчуванням';
$lang["checkmail_extension_mapping_desc"]='Після селектора Типу Ресурсу за замовчуванням, нижче є одне поле введення для кожного з ваших Типів Ресурсів. <br />Щоб примусово завантажувати файли різних типів у певний Тип Ресурсу, додайте списки розширень файлів, розділені комами (наприклад, jpg,gif,png).';
$lang["checkmail_resource_type_population"]='(з allowed_extensions)';
$lang["checkmail_subject_field"]='Поле теми';
$lang["checkmail_body_field"]='Поле тіла';
$lang["checkmail_purge"]='Видалити електронні листи після завантаження?';
$lang["checkmail_confirm"]='Надіслати підтверджувальні електронні листи?';
$lang["checkmail_users"]='Дозволені користувачі';
$lang["checkmail_blocked_users_label"]='Заблоковані користувачі';
$lang["checkmail_default_access"]='Доступ за замовчуванням';
$lang["checkmail_default_archive"]='Статус за замовчуванням';
$lang["checkmail_html"]='Дозволити HTML вміст? (експериментально, не рекомендується)';
$lang["checkmail_mail_skipped"]='Пропущений електронний лист';
$lang["checkmail_allow_users_based_on_permission_label"]='Чи дозволити користувачам завантажувати на основі дозволу?';
$lang["addresourcesviaemail"]='Додати через електронну пошту';
$lang["uploadviaemail"]='Додати через електронну пошту';
$lang["uploadviaemail-intro"]='<br /><br />Щоб завантажити через електронну пошту, прикріпіть ваші файли та надішліть електронний лист на <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Переконайтеся, що ви надсилаєте його з <b>[fromaddress]</b>, інакше він буде проігнорований.</p><p>Зверніть увагу, що все, що знаходиться в ТЕМІ електронного листа, буде внесено в поле [subjectfield] у [applicationname]. </p><p> Також зверніть увагу, що все, що знаходиться в ТІЛІ електронного листа, буде внесено в поле [bodyfield] у [applicationname]. </p>  <p>Кілька файлів будуть згруповані в колекцію. Ваші ресурси за замовчуванням матимуть рівень доступу <b>\'[access]\'</b> та статус архіву <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Ви отримаєте підтвердження електронною поштою, коли ваша електронна пошта буде успішно оброблена. Якщо ваша електронна пошта буде програмно пропущена з будь-якої причини (наприклад, якщо вона надіслана з неправильної адреси), адміністратор буде повідомлений про те, що є електронна пошта, яка потребує уваги.';
$lang["yourresourcehasbeenuploaded"]='Ваш ресурс було завантажено';
$lang["yourresourceshavebeenuploaded"]='Ваші ресурси були завантажені';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), з ID [user-ref] та електронною поштою [user-email] не має дозволу на завантаження через електронну пошту (перевірте дозволи "c" або "d" або заблокованих користувачів на сторінці налаштувань checkmail). Записано: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Створено за допомогою плагіна Check Mail';