<?php


$lang["checkmail_configuration"]='ਚੈਕਮੇਲ ਸੰਰਚਨਾ';
$lang["checkmail_install_php_imap_extension"]='ਪਹਿਲਾ ਕਦਮ: php imap ਐਕਸਟੈਂਸ਼ਨ ਇੰਸਟਾਲ ਕਰੋ।';
$lang["checkmail_lastcheck"]='ਤੁਹਾਡਾ IMAP ਖਾਤਾ ਆਖਰੀ ਵਾਰ [lastcheck] ਨੂੰ ਚੈੱਕ ਕੀਤਾ ਗਿਆ ਸੀ।';
$lang["checkmail_cronjobprob"]='ਤੁਹਾਡਾ ਚੈਕਮੇਲ ਕ੍ਰੋਨਜੌਬ ਸਹੀ ਤਰੀਕੇ ਨਾਲ ਨਹੀਂ ਚੱਲ ਰਿਹਾ ਹੋ ਸਕਦਾ, ਕਿਉਂਕਿ ਇਸ ਨੂੰ ਆਖਰੀ ਵਾਰ ਚੱਲੇ 5 ਮਿੰਟ ਤੋਂ ਵੱਧ ਸਮਾਂ ਹੋ ਗਿਆ ਹੈ।<br /><br />
ਹਰ ਮਿੰਟ ਚੱਲਣ ਵਾਲੇ ਕ੍ਰੋਨਜੌਬ ਦਾ ਉਦਾਹਰਨ:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap ਸਰਵਰ<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='ਈਮੇਲ';
$lang["checkmail_password"]='ਪਾਸਵਰਡ';
$lang["checkmail_extension_mapping"]='ਫਾਈਲ ਐਕਸਟੈਂਸ਼ਨ ਮੈਪਿੰਗ ਰਾਹੀਂ ਸਰੋਤ ਕਿਸਮ';
$lang["checkmail_default_resource_type"]='ਡਿਫਾਲਟ ਸਰੋਤ ਕਿਸਮ';
$lang["checkmail_extension_mapping_desc"]='ਡਿਫਾਲਟ ਰਿਸੋਰਸ ਕਿਸਮ ਚੋਣਕਰਤਾ ਤੋਂ ਬਾਅਦ, ਹਰੇਕ ਰਿਸੋਰਸ ਕਿਸਮ ਲਈ ਹੇਠਾਂ ਇੱਕ ਇਨਪੁਟ ਹੈ। <br />ਵੱਖ-ਵੱਖ ਕਿਸਮਾਂ ਦੀਆਂ ਅਪਲੋਡ ਕੀਤੀਆਂ ਫਾਈਲਾਂ ਨੂੰ ਇੱਕ ਖਾਸ ਰਿਸੋਰਸ ਕਿਸਮ ਵਿੱਚ ਜ਼ਬਰਦਸਤੀ ਕਰਨ ਲਈ, ਫਾਈਲ ਐਕਸਟੈਂਸ਼ਨਾਂ ਦੀ ਕਾਮਾ ਨਾਲ ਵੱਖ ਕੀਤੀ ਗਈ ਸੂਚੀ ਸ਼ਾਮਲ ਕਰੋ (ਜਿਵੇਂ jpg,gif,png)।';
$lang["checkmail_resource_type_population"]='(ਅਨੁਮਤ ਫਾਈਲ ਐਕਸਟੈਂਸ਼ਨ ਤੋਂ)';
$lang["checkmail_subject_field"]='ਵਿਸ਼ਾ ਖੇਤਰ';
$lang["checkmail_body_field"]='ਬਾਡੀ ਫੀਲਡ';
$lang["checkmail_purge"]='ਅਪਲੋਡ ਤੋਂ ਬਾਅਦ ਈ-ਮੇਲਾਂ ਨੂੰ ਹਟਾਉਣਾ?';
$lang["checkmail_confirm"]='ਪੁਸ਼ਟੀਕਰਨ ਈਮੇਲ ਭੇਜੋ?';
$lang["checkmail_users"]='ਇਜਾਜ਼ਤ ਪ੍ਰਾਪਤ ਯੂਜ਼ਰਜ਼';
$lang["checkmail_blocked_users_label"]='ਅਵਰੁੱਧ ਉਪਭੋਗਤਾ';
$lang["checkmail_default_access"]='ਡਿਫਾਲਟ ਐਕਸੈਸ';
$lang["checkmail_default_archive"]='ਡਿਫਾਲਟ ਸਥਿਤੀ';
$lang["checkmail_html"]='ਕੀ HTML ਸਮੱਗਰੀ ਦੀ ਆਗਿਆ ਦਿਓ? (ਪ੍ਰਯੋਗਾਤਮਕ, ਸਿਫਾਰਸ਼ੀ ਨਹੀਂ)';
$lang["checkmail_mail_skipped"]='ਛੱਡਿਆ ਗਿਆ ਈ-ਮੇਲ';
$lang["checkmail_allow_users_based_on_permission_label"]='ਕੀ ਉਪਭੋਗਤਾਵਾਂ ਨੂੰ ਅਨੁਮਤੀ ਦੇ ਆਧਾਰ \'ਤੇ ਅਪਲੋਡ ਕਰਨ ਦੀ ਆਗਿਆ ਹੋਣੀ ਚਾਹੀਦੀ ਹੈ?';
$lang["addresourcesviaemail"]='ਈ-ਮੇਲ ਰਾਹੀਂ ਸ਼ਾਮਲ ਕਰੋ';
$lang["uploadviaemail"]='ਈ-ਮੇਲ ਰਾਹੀਂ ਸ਼ਾਮਲ ਕਰੋ';
$lang["uploadviaemail-intro"]='<br /><br />ਈ-ਮੇਲ ਰਾਹੀਂ ਅਪਲੋਡ ਕਰਨ ਲਈ, ਆਪਣੀ ਫਾਈਲ(ਜ਼) ਨੂੰ ਅਟੈਚ ਕਰੋ ਅਤੇ ਈ-ਮੇਲ ਨੂੰ <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b> \'ਤੇ ਭੇਜੋ।</p> <p>ਇਹ ਯਕੀਨੀ ਬਣਾਓ ਕਿ ਇਹ <b>[fromaddress]</b> ਤੋਂ ਭੇਜਿਆ ਗਿਆ ਹੈ, ਨਹੀਂ ਤਾਂ ਇਸਨੂੰ ਅਣਡਿੱਠਾ ਕਰ ਦਿੱਤਾ ਜਾਵੇਗਾ।</p><p>ਨੋਟ ਕਰੋ ਕਿ ਈ-ਮੇਲ ਦੇ ਵਿਸ਼ੇ ਵਿੱਚ ਜੋ ਕੁਝ ਵੀ ਹੋਵੇਗਾ, ਉਹ [applicationname] ਵਿੱਚ [subjectfield] ਖੇਤਰ ਵਿੱਚ ਜਾਵੇਗਾ। </p><p> ਇਹ ਵੀ ਨੋਟ ਕਰੋ ਕਿ ਈ-ਮੇਲ ਦੇ ਬਾਡੀ ਵਿੱਚ ਜੋ ਕੁਝ ਵੀ ਹੋਵੇਗਾ, ਉਹ [applicationname] ਵਿੱਚ [bodyfield] ਖੇਤਰ ਵਿੱਚ ਜਾਵੇਗਾ। </p> <p>ਕਈ ਫਾਈਲਾਂ ਨੂੰ ਇੱਕ ਕਲੈਕਸ਼ਨ ਵਿੱਚ ਗਰੁੱਪ ਕੀਤਾ ਜਾਵੇਗਾ। ਤੁਹਾਡੇ ਸਰੋਤਾਂ ਦੀ ਪਹੁੰਚ ਪੱਧਰ ਮੂਲ ਰੂਪ ਵਿੱਚ <b>\'[access]\'</b> ਹੋਵੇਗੀ, ਅਤੇ ਆਰਕਾਈਵ ਸਥਿਤੀ <b>\'[archive]\'</b> ਹੋਵੇਗੀ।</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='ਜਦੋਂ ਤੁਹਾਡਾ ਈ-ਮੇਲ ਸਫਲਤਾਪੂਰਵਕ ਪ੍ਰਕਿਰਿਆ ਕੀਤੀ ਜਾਂਦੀ ਹੈ, ਤਾਂ ਤੁਹਾਨੂੰ ਇੱਕ ਪੁਸ਼ਟੀਕਰਨ ਈ-ਮੇਲ ਪ੍ਰਾਪਤ ਹੋਵੇਗੀ। ਜੇ ਕਿਸੇ ਕਾਰਨ ਕਰਕੇ ਤੁਹਾਡਾ ਈ-ਮੇਲ ਪ੍ਰੋਗਰਾਮਮੈਟਿਕ ਤੌਰ \'ਤੇ ਛੱਡ ਦਿੱਤਾ ਜਾਂਦਾ ਹੈ (ਜਿਵੇਂ ਕਿ ਜੇ ਇਹ ਗਲਤ ਪਤੇ ਤੋਂ ਭੇਜਿਆ ਗਿਆ ਹੈ), ਤਾਂ ਪ੍ਰਸ਼ਾਸਕ ਨੂੰ ਸੂਚਿਤ ਕੀਤਾ ਜਾਵੇਗਾ ਕਿ ਧਿਆਨ ਦੀ ਲੋੜ ਵਾਲਾ ਇੱਕ ਈ-ਮੇਲ ਹੈ।';
$lang["yourresourcehasbeenuploaded"]='ਤੁਹਾਡਾ ਸਰੋਤ ਅਪਲੋਡ ਹੋ ਗਿਆ ਹੈ';
$lang["yourresourceshavebeenuploaded"]='ਤੁਹਾਡੇ ਸਰੋਤ ਅਪਲੋਡ ਕਰ ਦਿੱਤੇ ਗਏ ਹਨ';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), ਜਿਸਦਾ ID [user-ref] ਹੈ ਅਤੇ ਈ-ਮੇਲ [user-email] ਹੈ, ਨੂੰ ਈ-ਮੇਲ ਰਾਹੀਂ ਅਪਲੋਡ ਕਰਨ ਦੀ ਆਗਿਆ ਨਹੀਂ ਹੈ (ਅਧਿਕਾਰ "c" ਜਾਂ "d" ਜਾਂ ਚੈਕਮੇਲ ਸੈਟਅੱਪ ਪੇਜ ਵਿੱਚ ਰੋਕੇ ਗਏ ਯੂਜ਼ਰਾਂ ਦੀ ਜਾਂਚ ਕਰੋ)। ਦਰਜ ਕੀਤਾ ਗਿਆ: [datetime].';
$lang["checkmail_createdfromcheckmail"]='ਚੈਕ ਮੇਲ ਪਲੱਗਇਨ ਤੋਂ ਬਣਾਇਆ ਗਿਆ';
$lang["checkmail_cronhelp"]='ਇਹ ਪਲੱਗਇਨ ਨੂੰ ਸਿਸਟਮ ਨੂੰ ਇੱਕ ਈ-ਮੇਲ ਖਾਤੇ ਵਿੱਚ ਲੌਗਇਨ ਕਰਨ ਲਈ ਕੁਝ ਵਿਸ਼ੇਸ਼ ਸੈਟਅਪ ਦੀ ਲੋੜ ਹੈ ਜੋ ਅਪਲੋਡ ਲਈ ਫਾਈਲਾਂ ਪ੍ਰਾਪਤ ਕਰਨ ਲਈ ਸਮਰਪਿਤ ਹੈ।<br /><br />ਸੁਨਿਸ਼ਚਿਤ ਕਰੋ ਕਿ ਖਾਤੇ \'ਤੇ IMAP ਸਚਾਲਿਤ ਹੈ। ਜੇ ਤੁਸੀਂ ਇੱਕ Gmail ਖਾਤਾ ਵਰਤ ਰਹੇ ਹੋ ਤਾਂ ਤੁਸੀਂ IMAP ਨੂੰ ਸੈਟਿੰਗਸ->POP/IMAP->IMAP ਸਚਾਲਿਤ ਕਰੋ ਵਿੱਚ ਸਚਾਲਿਤ ਕਰਦੇ ਹੋ।<br /><br />ਪ੍ਰਾਰੰਭਿਕ ਸੈਟਅਪ \'ਤੇ, ਤੁਸੀਂ ਕਮਾਂਡ ਲਾਈਨ \'ਤੇ plugins/checkmail/pages/cron_check_email.php ਨੂੰ ਹੱਥੋਂ ਚਲਾਉਣ ਨੂੰ ਸਭ ਤੋਂ ਮਦਦਗਾਰ ਪਾ ਸਕਦੇ ਹੋ ਤਾਂ ਜੋ ਇਹ ਸਮਝ ਸਕੋ ਕਿ ਇਹ ਕਿਵੇਂ ਕੰਮ ਕਰਦਾ ਹੈ। ਜਦੋਂ ਤੁਸੀਂ ਠੀਕ ਤਰ੍ਹਾਂ ਕਨੈਕਟ ਕਰ ਰਹੇ ਹੋ ਅਤੇ ਸਮਝਦੇ ਹੋ ਕਿ ਸਕ੍ਰਿਪਟ ਕਿਵੇਂ ਕੰਮ ਕਰਦਾ ਹੈ, ਤਾਂ ਤੁਹਾਨੂੰ ਇਸਨੂੰ ਹਰ ਇੱਕ ਜਾਂ ਦੋ ਮਿੰਟ ਵਿੱਚ ਚਲਾਉਣ ਲਈ ਇੱਕ ਕ੍ਰੋਨ ਜੌਬ ਸੈਟਅਪ ਕਰਨੀ ਚਾਹੀਦੀ ਹੈ।<br />ਇਹ ਮੇਲਬਾਕਸ ਨੂੰ ਸਕੈਨ ਕਰੇਗਾ ਅਤੇ ਹਰ ਚਲਾਉਣ \'ਤੇ ਇੱਕ ਨਾ ਪੜ੍ਹੀ ਗਈ ਈ-ਮੇਲ ਪੜ੍ਹੇਗਾ।<br /><br />ਹਰ ਦੋ ਮਿੰਟ ਵਿੱਚ ਚਲਣ ਵਾਲੇ ਕ੍ਰੋਨ ਜੌਬ ਦਾ ਇੱਕ ਉਦਾਹਰਨ:<br />*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';