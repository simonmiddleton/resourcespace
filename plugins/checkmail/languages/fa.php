<?php


$lang["checkmail_configuration"]='پیکربندی Checkmail';
$lang["checkmail_install_php_imap_extension"]='مرحله اول: نصب افزونه php imap.';
$lang["checkmail_cronhelp"]='این افزونه نیاز به تنظیمات خاصی دارد تا سیستم بتواند به یک حساب ایمیل اختصاصی برای دریافت فایل‌های مورد نظر برای بارگذاری وارد شود.<br /><br />اطمینان حاصل کنید که IMAP در حساب فعال است. اگر از حساب Gmail استفاده می‌کنید، IMAP را در تنظیمات->POP/IMAP->فعال کردن IMAP فعال کنید.<br /><br />در تنظیمات اولیه، ممکن است مفیدترین کار این باشد که plugins/checkmail/pages/cron_check_email.php را به صورت دستی در خط فرمان اجرا کنید تا بفهمید چگونه کار می‌کند. هنگامی که به درستی متصل شدید و فهمیدید که اسکریپت چگونه کار می‌کند، باید یک کار کرون تنظیم کنید تا هر یک یا دو دقیقه آن را اجرا کند.<br />این کار صندوق پستی را اسکن کرده و در هر اجرا یک ایمیل خوانده نشده را می‌خواند.<br /><br />یک مثال از کار کرون که هر دو دقیقه اجرا می‌شود:<br />*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='حساب IMAP شما آخرین بار در [lastcheck] بررسی شد.';
$lang["checkmail_cronjobprob"]='کرون‌جاب بررسی ایمیل شما ممکن است به درستی اجرا نشود، زیرا بیش از ۵ دقیقه از آخرین اجرای آن گذشته است.<br /><br />
یک مثال از کرون‌جاب که هر دقیقه اجرا می‌شود:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='سرور Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='ایمیل';
$lang["checkmail_password"]='رمز عبور';
$lang["checkmail_extension_mapping"]='نوع منبع از طریق نگاشت پسوند فایل';
$lang["checkmail_default_resource_type"]='نوع منبع پیش‌فرض';
$lang["checkmail_extension_mapping_desc"]='پس از انتخابگر نوع منبع پیش‌فرض، برای هر یک از انواع منبع شما یک ورودی در زیر وجود دارد. <br />برای وادار کردن فایل‌های آپلود شده از انواع مختلف به یک نوع منبع خاص، لیست‌های جدا شده با کاما از پسوندهای فایل (مثلاً jpg,gif,png) اضافه کنید.';
$lang["checkmail_resource_type_population"]='<br />(از allowed_extensions)';
$lang["checkmail_subject_field"]='فیلد موضوع';
$lang["checkmail_body_field"]='فیلد بدنه';
$lang["checkmail_purge"]='پاک کردن ایمیل‌ها پس از بارگذاری؟';
$lang["checkmail_confirm"]='ارسال ایمیل‌های تأیید؟';
$lang["checkmail_users"]='کاربران مجاز';
$lang["checkmail_blocked_users_label"]='کاربران مسدود شده';
$lang["checkmail_default_access"]='دسترسی پیش‌فرض';
$lang["checkmail_default_archive"]='وضعیت پیش‌فرض';
$lang["checkmail_html"]='اجازه به محتوای HTML؟ (آزمایشی، توصیه نمی‌شود)';
$lang["checkmail_mail_skipped"]='ایمیل رد شده';
$lang["checkmail_allow_users_based_on_permission_label"]='آیا باید به کاربران بر اساس مجوز اجازه داده شود که بارگذاری کنند؟';
$lang["addresourcesviaemail"]='افزودن از طریق ایمیل';
$lang["uploadviaemail"]='اضافه کردن از طریق ایمیل';
$lang["uploadviaemail-intro"]='برای بارگذاری از طریق ایمیل، فایل(ها)یتان را پیوست کرده و ایمیل را به <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b> ارسال کنید.</p> <p>اطمینان حاصل کنید که آن را از <b>[fromaddress]</b> ارسال می‌کنید، در غیر این صورت نادیده گرفته خواهد شد.</p><p>توجه داشته باشید که هر چیزی در موضوع ایمیل باشد به فیلد [subjectfield] در [applicationname] منتقل خواهد شد. </p><p> همچنین توجه داشته باشید که هر چیزی در بدنه ایمیل باشد به فیلد [bodyfield] در [applicationname] منتقل خواهد شد. </p> <p>چندین فایل به یک مجموعه گروه‌بندی خواهند شد. منابع شما به طور پیش‌فرض به سطح دسترسی <b>\'[access]\'</b> و وضعیت آرشیو <b>\'[archive]\'</b> تنظیم خواهند شد.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='شما یک ایمیل تأیید دریافت خواهید کرد زمانی که ایمیل شما با موفقیت پردازش شود. اگر ایمیل شما به هر دلیلی به صورت برنامه‌ریزی شده نادیده گرفته شود (مانند ارسال از آدرس اشتباه)، به مدیر اطلاع داده خواهد شد که ایمیلی نیاز به توجه دارد.';
$lang["yourresourcehasbeenuploaded"]='منبع شما بارگذاری شده است';
$lang["yourresourceshavebeenuploaded"]='منابع شما بارگذاری شده‌اند';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username])، با شناسه [user-ref] و ایمیل [user-email] مجاز به بارگذاری از طریق ایمیل نیست (مجوزهای "c" یا "d" یا کاربران مسدود شده در صفحه تنظیمات checkmail را بررسی کنید). ثبت شده در: [datetime].';
$lang["checkmail_createdfromcheckmail"]='ایجاد شده از افزونه بررسی ایمیل';