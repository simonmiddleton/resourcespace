<?php


$lang["checkmail_configuration"]='تكوين البريد الإلكتروني للتحقق';
$lang["checkmail_install_php_imap_extension"]='الخطوة الأولى: تثبيت امتداد php imap.';
$lang["checkmail_cronhelp"]='يتطلب هذا الملحق إعدادًا خاصًا للنظام لتسجيل الدخول إلى حساب بريد إلكتروني مخصص لاستلام الملفات المخصصة للتحميل.<br /><br />تأكد من تمكين IMAP على الحساب. إذا كنت تستخدم حساب Gmail ، فيمكنك تمكين IMAP في الإعدادات-> POP / IMAP-> تمكين IMAP<br /><br />
عند الإعداد الأولي ، قد تجد أن تشغيل plugins/checkmail/pages/cron_check_email.php يدويًا على سطر الأوامر هو الأكثر مساعدة لفهم كيفية عمله.
بمجرد الاتصال بشكل صحيح وفهم كيفية عمل البرنامج النصي ، يجب عليك إعداد وظيفة cron لتشغيله كل دقيقة أو دقيقتين.<br />سيقوم بفحص صندوق البريد الوارد وقراءة بريد إلكتروني غير مقروء لكل تشغيلة.<br /><br />
وظيفة cron مثالية تعمل كل دقيقتين:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='تم فحص حساب IMAP الخاص بك في آخر مرة في [lastcheck].';
$lang["checkmail_cronjobprob"]='قد لا يتم تشغيل مهمة الكرون الخاصة بالتحقق من البريد الإلكتروني بشكل صحيح، لأنه مضى أكثر من 5 دقائق منذ آخر تشغيل لها. <br /><br />
مثال على مهمة الكرون التي تعمل كل دقيقة: <br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='خادم Imap <br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='البريد الإلكتروني';
$lang["checkmail_password"]='كلمة المرور';
$lang["checkmail_extension_mapping"]='نوع المورد عبر تعيين امتداد الملفات';
$lang["checkmail_default_resource_type"]='نوع المصدر الافتراضي';
$lang["checkmail_extension_mapping_desc"]='بعد محدد نوع المورد الافتراضي، يوجد مدخل واحد أدناه لكل من أنواع الموارد الخاصة بك. <br /> لإجبار الملفات التي تم تحميلها من أنواع مختلفة إلى نوع مورد محدد، أضف قوائم مفصولة بفواصل للامتدادات (مثال: jpg، gif، png).';
$lang["checkmail_subject_field"]='حقل الموضوع';
$lang["checkmail_body_field"]='حقل الجسم';
$lang["checkmail_purge"]='هل تريد حذف الرسائل الإلكترونية بعد الرفع؟';
$lang["checkmail_confirm"]='إرسال رسائل البريد الإلكتروني للتأكيد؟';
$lang["checkmail_users"]='المستخدمون المسموح بهم';
$lang["checkmail_blocked_users_label"]='المستخدمون المحظورون';
$lang["checkmail_default_access"]='الوصول الافتراضي';
$lang["checkmail_default_archive"]='الحالة الافتراضية';
$lang["checkmail_html"]='السماح بمحتوى HTML؟ (تجريبي، غير موصى به)';
$lang["checkmail_mail_skipped"]='البريد الإلكتروني المتخطى';
$lang["checkmail_allow_users_based_on_permission_label"]='هل يجب السماح للمستخدمين بالرفع بناءً على الأذونات؟';
$lang["addresourcesviaemail"]='إضافة عبر البريد الإلكتروني';
$lang["uploadviaemail"]='إضافة عبر البريد الإلكتروني';
$lang["uploadviaemail-intro"]='للرفع عبر البريد الإلكتروني، يرجى إرفاق الملفات وإرسالها إلى العنوان التالي <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>تأكد من إرسالها من <b>[fromaddress]</b>، وإلا سيتم تجاهلها.</p><p>يرجى ملاحظة أن أي شيء في عنوان البريد الإلكتروني سيتم وضعه في حقل [subjectfield] في %applicationname%.</p><p> كما يرجى ملاحظة أن أي شيء في جسم البريد الإلكتروني سيتم وضعه في حقل [bodyfield] في %applicationname%.</p>  <p>سيتم تجميع الملفات المتعددة في مجموعة. ستكون مواردك الافتراضية على مستوى الوصول <b>\'[access]\'</b>، وحالة الأرشيف <b>\'[archive]\'</b>.</p><p> [confirmation]</p>';
$lang["checkmail_confirmation_message"]='ستتلقى رسالة تأكيد عبر البريد الإلكتروني عندما يتم معالجة بريدك الإلكتروني بنجاح. إذا تم تخطي بريدك الإلكتروني بشكل برمجي لأي سبب (مثل إرساله من عنوان خاطئ)، سيتم إخطار المسؤول بأن هناك بريدًا إلكترونيًا يتطلب اهتمامًا.';
$lang["yourresourcehasbeenuploaded"]='تم تحميل موردك.';
$lang["yourresourceshavebeenuploaded"]='تم تحميل مواردك.';
$lang["checkmail_not_allowed_error_template"]='المستخدم [user-fullname] ([username])، برقم [user-ref] والبريد الإلكتروني [user-email] غير مسموح له بالرفع عبر البريد الإلكتروني (يرجى التحقق من الأذونات "c" أو "d" أو المستخدمين المحظورين في صفحة إعدادات البريد الإلكتروني). تم تسجيل هذا في: [datetime].';
$lang["checkmail_createdfromcheckmail"]='تم إنشاؤها من مكوّن إضافة تحقق البريد الإلكتروني.';