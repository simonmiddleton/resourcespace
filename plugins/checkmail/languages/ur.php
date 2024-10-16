<?php


$lang["checkmail_configuration"]='چیک میل کنفیگریشن';
$lang["checkmail_install_php_imap_extension"]='پہلا مرحلہ: پی ایچ پی آئی ایم اے پی ایکسٹینشن انسٹال کریں۔';
$lang["checkmail_cronhelp"]='یہ پلگ ان سسٹم کو ایک ای میل اکاؤنٹ میں لاگ ان کرنے کے لیے کچھ خاص سیٹ اپ کی ضرورت ہوتی ہے جو اپلوڈ کے لیے فائلیں وصول کرنے کے لیے مختص ہے۔<br /><br />یقینی بنائیں کہ اکاؤنٹ پر IMAP فعال ہے۔ اگر آپ جی میل اکاؤنٹ استعمال کر رہے ہیں تو آپ IMAP کو Settings->POP/IMAP->Enable IMAP میں فعال کریں۔<br /><br />ابتدائی سیٹ اپ پر، آپ کو یہ سب سے زیادہ مددگار معلوم ہو سکتا ہے کہ plugins/checkmail/pages/cron_check_email.php کو کمانڈ لائن پر دستی طور پر چلائیں تاکہ یہ سمجھ سکیں کہ یہ کیسے کام کرتا ہے۔ جب آپ صحیح طریقے سے کنیکٹ ہو رہے ہوں اور اسکرپٹ کے کام کرنے کا طریقہ سمجھ لیں، تو آپ کو اسے ہر ایک یا دو منٹ میں چلانے کے لیے ایک کرون جاب سیٹ اپ کرنا ہوگا۔<br />یہ میل باکس کو اسکین کرے گا اور ہر رن میں ایک غیر پڑھی ہوئی ای میل پڑھے گا۔<br /><br />ہر دو منٹ میں چلنے والی کرون جاب کی ایک مثال:<br />*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='آپ کے IMAP اکاؤنٹ کو آخری بار [lastcheck] پر چیک کیا گیا تھا۔';
$lang["checkmail_cronjobprob"]='آپ کا چیک میل کرون جاب صحیح طریقے سے نہیں چل رہا ہو سکتا، کیونکہ یہ آخری بار چلنے کے بعد سے 5 منٹ سے زیادہ ہو چکے ہیں۔<br /><br />
ایک مثال کرون جاب جو ہر منٹ چلتا ہے:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap سرور<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='ای میل';
$lang["checkmail_password"]='پاس ورڈ';
$lang["checkmail_extension_mapping"]='فائل ایکسٹینشن میپنگ کے ذریعے وسائل کی قسم';
$lang["checkmail_default_resource_type"]='پہلے سے طے شدہ وسائل کی قسم';
$lang["checkmail_extension_mapping_desc"]='ڈیفالٹ ریسورس ٹائپ سلیکٹر کے بعد، آپ کے ہر ریسورس ٹائپ کے لیے نیچے ایک ان پٹ ہے۔ <br />مختلف اقسام کی اپ لوڈ کی گئی فائلوں کو کسی مخصوص ریسورس ٹائپ میں مجبور کرنے کے لیے، فائل ایکسٹینشنز کی کاما سے جدا فہرستیں شامل کریں (مثلاً jpg,gif,png)۔';
$lang["checkmail_resource_type_population"]='<br />(allowed_extensions سے)';
$lang["checkmail_subject_field"]='موضوع کا خانہ';
$lang["checkmail_body_field"]='باڈی فیلڈ';
$lang["checkmail_purge"]='اپ لوڈ کے بعد ای میلز کو حذف کریں؟';
$lang["checkmail_confirm"]='تصدیقی ای میلز بھیجیں؟';
$lang["checkmail_users"]='اجازت یافتہ صارفین';
$lang["checkmail_blocked_users_label"]='مسدود شدہ صارفین';
$lang["checkmail_default_access"]='پہلے سے طے شدہ رسائی';
$lang["checkmail_default_archive"]='پہلے سے طے شدہ حالت';
$lang["checkmail_html"]='کیا HTML مواد کی اجازت دی جائے؟ (تجرباتی، سفارش نہیں کی جاتی)';
$lang["checkmail_mail_skipped"]='چھوڑا گیا ای میل';
$lang["checkmail_allow_users_based_on_permission_label"]='کیا صارفین کو اجازت کی بنیاد پر اپ لوڈ کرنے کی اجازت دی جانی چاہیے؟';
$lang["addresourcesviaemail"]='ای میل کے ذریعے شامل کریں';
$lang["uploadviaemail"]='ای میل کے ذریعے شامل کریں';
$lang["uploadviaemail-intro"]='ای میل کے ذریعے اپ لوڈ کرنے کے لیے، اپنی فائل(فائلیں) منسلک کریں اور ای میل کو <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b> پر بھیجیں۔</p> <p>یقینی بنائیں کہ اسے <b>[fromaddress]</b> سے بھیجا گیا ہے، ورنہ اسے نظرانداز کر دیا جائے گا۔</p><p>نوٹ کریں کہ ای میل کے SUBJECT میں موجود کوئی بھی چیز [applicationname] میں [subjectfield] فیلڈ میں جائے گی۔ </p><p> نیز نوٹ کریں کہ ای میل کے BODY میں موجود کوئی بھی چیز [applicationname] میں [bodyfield] فیلڈ میں جائے گی۔ </p> <p>متعدد فائلیں ایک مجموعہ میں گروپ کی جائیں گی۔ آپ کے وسائل کی رسائی کی سطح <b>\'[access]\'</b> اور آرکائیو کی حیثیت <b>\'[archive]\'</b> پر ڈیفالٹ ہوگی۔</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='جب آپ کا ای میل کامیابی سے پروسیس ہو جائے گا تو آپ کو ایک تصدیقی ای میل موصول ہوگی۔ اگر آپ کا ای میل کسی بھی وجہ سے پروگرام کے ذریعے چھوڑ دیا جاتا ہے (جیسے کہ اگر یہ غلط ایڈریس سے بھیجا گیا ہو)، تو ایڈمنسٹریٹر کو مطلع کیا جائے گا کہ ایک ای میل توجہ کی ضرورت ہے۔';
$lang["yourresourcehasbeenuploaded"]='آپ کا وسیلہ اپ لوڈ ہو چکا ہے';
$lang["yourresourceshavebeenuploaded"]='آپ کے وسائل اپ لوڈ ہو چکے ہیں';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), جس کا ID [user-ref] ہے اور ای میل [user-email] ہے، کو ای میل کے ذریعے اپ لوڈ کرنے کی اجازت نہیں ہے (اجازت "c" یا "d" یا چیک میل سیٹ اپ صفحہ میں بلاک شدہ صارفین کو چیک کریں)۔ ریکارڈ کیا گیا: [datetime]۔';
$lang["checkmail_createdfromcheckmail"]='چیک میل پلگ ان سے تخلیق کیا گیا';