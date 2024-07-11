<?php


$lang["checkmail_configuration"]='Checkmail Yapılandırması';
$lang["checkmail_install_php_imap_extension"]='Birinci Adım: php imap uzantısını yükleyin.';
$lang["checkmail_lastcheck"]='IMAP hesabınız en son [lastcheck] tarihinde kontrol edildi.';
$lang["checkmail_cronjobprob"]='Checkmail cronjob\'unuz düzgün çalışmıyor olabilir, çünkü son çalıştırılmasından bu yana 5 dakikadan fazla zaman geçti.<br /><br />
Her dakika çalışan bir cron job örneği:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap Sunucusu<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='E-posta';
$lang["checkmail_password"]='Şifre';
$lang["checkmail_extension_mapping"]='Dosya Uzantısı Eşlemesi ile Kaynak Türü';
$lang["checkmail_default_resource_type"]='Varsayılan Kaynak Türü';
$lang["checkmail_extension_mapping_desc"]='Varsayılan Kaynak Türü seçicisinin ardından, her bir Kaynak Türünüz için aşağıda bir giriş bulunmaktadır. <br />Farklı türdeki yüklenen dosyaları belirli bir Kaynak Türüne zorlamak için, virgülle ayrılmış dosya uzantıları listeleri ekleyin (ör. jpg,gif,png).';
$lang["checkmail_resource_type_population"]='<br />(izin verilen uzantılardan)';
$lang["checkmail_subject_field"]='Konu Alanı';
$lang["checkmail_body_field"]='Gövde Alanı';
$lang["checkmail_purge"]='Yüklemeden sonra e-postaları temizle?';
$lang["checkmail_confirm"]='Onay e-postaları gönderilsin mi?';
$lang["checkmail_users"]='İzin Verilen Kullanıcılar';
$lang["checkmail_blocked_users_label"]='Engellenmiş kullanıcılar';
$lang["checkmail_default_access"]='Varsayılan Erişim';
$lang["checkmail_default_archive"]='Varsayılan Durum';
$lang["checkmail_html"]='HTML İçeriğine İzin Ver? (deneysel, önerilmez)';
$lang["checkmail_mail_skipped"]='Atlanan e-posta';
$lang["checkmail_allow_users_based_on_permission_label"]='Kullanıcıların yükleme iznine göre izin verilmesi gerekiyor mu?';
$lang["addresourcesviaemail"]='E-posta ile Ekle';
$lang["uploadviaemail"]='E-posta ile Ekle';
$lang["uploadviaemail-intro"]='E-posta yoluyla yüklemek için, dosyanızı/dosyalarınızı ekleyin ve e-postayı <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b> adresine gönderin. E-postayı <b>[fromaddress]</b> adresinden gönderdiğinizden emin olun, aksi takdirde göz ardı edilecektir. E-postanın KONUSUNDA yer alan her şeyin [applicationname] içindeki [subjectfield] alanına gideceğini unutmayın. Ayrıca, e-postanın GÖVDESİNDE yer alan her şeyin [applicationname] içindeki [bodyfield] alanına gideceğini unutmayın. Birden fazla dosya bir koleksiyonda gruplandırılacaktır. Kaynaklarınız varsayılan olarak <b>\'[access]\'</b> Erişim seviyesine ve <b>\'[archive]\'</b> Arşiv durumuna sahip olacaktır. [confirmation]';
$lang["checkmail_confirmation_message"]='E-postanız başarıyla işlendiğinde bir onay e-postası alacaksınız. E-postanız herhangi bir nedenle (örneğin yanlış bir adresten gönderilmişse) programatik olarak atlanırsa, yönetici dikkat gerektiren bir e-posta olduğunu bildiren bir bildirim alacaktır.';
$lang["yourresourcehasbeenuploaded"]='Kaynağınız yüklendi';
$lang["yourresourceshavebeenuploaded"]='Kaynaklarınız yüklendi';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), kimlik numarası [user-ref] ve e-posta adresi [user-email] olan kullanıcının e-posta ile yükleme yapmasına izin verilmiyor (izinleri "c" veya "d" veya checkmail ayar sayfasındaki engellenen kullanıcıları kontrol edin). Kaydedildiği tarih: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Check Mail eklentisinden oluşturuldu';
$lang["checkmail_cronhelp"]='Bu eklenti, yükleme amacıyla dosya almak için ayrılmış bir e-posta hesabına sistemin giriş yapabilmesi için özel bir kurulum gerektirir.<br /><br />Hesapta IMAP\'in etkinleştirildiğinden emin olun. Gmail hesabı kullanıyorsanız, IMAP\'i Ayarlar->POP/IMAP->IMAP\'i Etkinleştir bölümünden etkinleştirin.<br /><br />
İlk kurulumda, nasıl çalıştığını anlamak için komut satırında plugins/checkmail/pages/cron_check_email.php dosyasını manuel olarak çalıştırmanız en faydalı yöntem olabilir.
Bağlantıyı doğru bir şekilde kurduktan ve betiğin nasıl çalıştığını anladıktan sonra, her bir veya iki dakikada bir çalıştırmak için bir cron işi ayarlamanız gerekecektir.<br />Bu işlem posta kutusunu tarayacak ve her çalıştırmada bir okunmamış e-postayı okuyacaktır.<br /><br />
Her iki dakikada bir çalışan bir cron işi örneği:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["plugin-checkmail-title"]='Postayı Kontrol Et';
$lang["plugin-checkmail-desc"]='[Gelişmiş] E-posta ile gönderilen eklerin alınmasına izin verir';