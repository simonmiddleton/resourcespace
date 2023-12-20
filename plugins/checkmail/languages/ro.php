<?php


$lang["checkmail_configuration"]='Configurare verificare email.';
$lang["checkmail_install_php_imap_extension"]='Pasul unu: Instalați extensia php imap.';
$lang["checkmail_cronhelp"]='Acest plugin necesită o configurare specială pentru ca sistemul să se conecteze la un cont de e-mail dedicat primirii fișierelor destinate încărcării.<br /><br />Asigurați-vă că IMAP este activat pe cont. Dacă utilizați un cont Gmail, activați IMAP în Setări->POP/IMAP->Activare IMAP<br /><br />
La configurarea inițială, este posibil să fie util să rulați manual plugins/checkmail/pages/cron_check_email.php în linia de comandă pentru a înțelege cum funcționează.<br />Odată ce vă conectați corect și înțelegeți cum funcționează scriptul, trebuie să configurați o sarcină cron pentru a-l rula la fiecare minut sau la fiecare două minute.<br /><br />
Un exemplu de sarcină cron care rulează la fiecare două minute:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Contul tău IMAP a fost verificat ultima dată la data de [lastcheck].';
$lang["checkmail_cronjobprob"]='Cronjob-ul tău de verificare a poștei poate să nu ruleze corect, deoarece au trecut mai mult de 5 minute de la ultima rulare.<br /><br />
Un exemplu de cronjob care rulează la fiecare minut:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Server Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Email - Poștă electronică';
$lang["checkmail_password"]='Parolă.';
$lang["checkmail_extension_mapping"]='Tipul de resursă prin maparea extensiei de fișier.';
$lang["checkmail_default_resource_type"]='Tip de resursă implicit.';
$lang["checkmail_extension_mapping_desc"]='După selectorul Tip resursă implicit, există un câmp de introducere pentru fiecare dintre Tipurile tale de resurse. <br />Pentru a forța fișierele încărcate de tipuri diferite într-un anumit Tip de resursă, adaugă liste separate prin virgulă de extensii de fișiere (ex. jpg, gif, png).';
$lang["checkmail_resource_type_population"]='<br />(din allowed_extensions)';
$lang["checkmail_subject_field"]='Câmpul Subiect.';
$lang["checkmail_body_field"]='Câmpul Corp.';
$lang["checkmail_purge"]='Ștergeți e-mailurile după încărcare?';
$lang["checkmail_confirm"]='Trimiteți e-mailuri de confirmare?';
$lang["checkmail_users"]='Utilizatori permisi.';
$lang["checkmail_blocked_users_label"]='Utilizatori blocați.';
$lang["checkmail_default_access"]='Acces implicit.';
$lang["checkmail_default_archive"]='Stare implicită.';
$lang["checkmail_html"]='Permiteți conținutul HTML? (experimental, nu este recomandat)';
$lang["checkmail_mail_skipped"]='E-mail omis';
$lang["checkmail_allow_users_based_on_permission_label"]='Ar trebui să li se permită utilizatorilor să încarce fișiere în funcție de permisiunea acordată?';
$lang["addresourcesviaemail"]='Adaugă prin e-mail.';
$lang["uploadviaemail"]='Adaugă prin e-mail.';
$lang["uploadviaemail-intro"]='Pentru a încărca prin e-mail, atașați fișierul(dosarele) și adresați e-mailul către <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Asigurați-vă că îl trimiteți de la <b>[fromaddress]</b>, altfel va fi ignorat.</p><p>Rețineți că orice în SUBIECTUL e-mailului va fi introdus în câmpul [subjectfield] din %applicationname%.</p><p> De asemenea, rețineți că orice în CORPUL e-mailului va fi introdus în câmpul [bodyfield] din %applicationname%.</p>  <p>Mai multe fișiere vor fi grupate într-o colecție. Resursele dvs. vor fi setate implicit la nivelul de acces <b>\'[access]\'</b>, și statusul de arhivă <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Veți primi un e-mail de confirmare atunci când e-mailul dumneavoastră este procesat cu succes. Dacă e-mailul dumneavoastră este omis din motive programatice (cum ar fi dacă este trimis de la o adresă greșită), administratorul va fi notificat că există un e-mail care necesită atenție.';
$lang["yourresourcehasbeenuploaded"]='Resursa ta a fost încărcată.';
$lang["yourresourceshavebeenuploaded"]='Resursele tale au fost încărcate.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), cu ID-ul [user-ref] și adresa de e-mail [user-email] nu are permisiunea de a încărca prin e-mail (verificați permisiunile "c" sau "d" sau utilizatorii blocați în pagina de configurare a verificării prin e-mail). Înregistrat la data de: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Creat din pluginul Verificare poștă electronică.';