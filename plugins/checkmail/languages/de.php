<?php


$lang["checkmail_configuration"]='Konfiguration der E-Mail-Überprüfung';
$lang["checkmail_install_php_imap_extension"]='Schritt Eins: Installieren Sie die PHP IMAP-Erweiterung.';
$lang["checkmail_cronhelp"]='Dieses Plugin erfordert eine spezielle Einrichtung, damit das System sich bei einem E-Mail-Konto anmelden kann, das für den Empfang von Dateien vorgesehen ist, die hochgeladen werden sollen.<br /><br />Stellen Sie sicher, dass IMAP für das Konto aktiviert ist. Wenn Sie ein Gmail-Konto verwenden, aktivieren Sie IMAP in Einstellungen->POP/IMAP->IMAP aktivieren.<br /><br />
Bei der ersten Einrichtung kann es hilfreich sein, das Skript plugins/checkmail/pages/cron_check_email.php manuell auf der Befehlszeile auszuführen, um zu verstehen, wie es funktioniert.
Sobald Sie eine erfolgreiche Verbindung hergestellt haben und verstehen, wie das Skript funktioniert, müssen Sie einen Cron-Job einrichten, um es alle ein oder zwei Minuten auszuführen.<br />Es wird das Postfach durchsuchen und bei jedem Durchlauf eine ungelesene E-Mail lesen.<br /><br />
Ein Beispiel für einen Cron-Job, der alle zwei Minuten ausgeführt wird:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Ihr IMAP-Konto wurde zuletzt am [lastcheck] abgerufen.';
$lang["checkmail_cronjobprob"]='Ihr Checkmail-Cronjob läuft möglicherweise nicht ordnungsgemäß, da seit dem letzten Lauf mehr als 5 Minuten vergangen sind.<br /><br />
Ein Beispiel-Cronjob, der jede Minute ausgeführt wird:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='IMAP-Server<br />(Gmail="imap.gmail.com:993/SSL")';
$lang["checkmail_email"]='E-Mail';
$lang["checkmail_password"]='Passwort';
$lang["checkmail_extension_mapping"]='Ressourcentyp über Dateierweiterungszuordnung.';
$lang["checkmail_default_resource_type"]='Standard Ressourcentyp';
$lang["checkmail_extension_mapping_desc"]='Nach dem Standard-Ressourcentyp-Auswahlfeld gibt es darunter eine Eingabe für jeden Ihrer Ressourcentypen. <br />Um hochgeladene Dateien unterschiedlicher Typen in einen bestimmten Ressourcentyp zu zwingen, fügen Sie kommagetrennte Listen von Dateierweiterungen hinzu (z.B. jpg, gif, png).';
$lang["checkmail_resource_type_population"]='<br />(von allowed_extensions)';
$lang["checkmail_subject_field"]='Betreff-Feld';
$lang["checkmail_body_field"]='Körperfeld';
$lang["checkmail_purge"]='E-Mails nach dem Hochladen bereinigen?';
$lang["checkmail_confirm"]='E-Mails zur Bestätigung senden?';
$lang["checkmail_users"]='Erlaubte Benutzer';
$lang["checkmail_blocked_users_label"]='Blockierte Benutzer';
$lang["checkmail_default_access"]='Standardzugriff';
$lang["checkmail_default_archive"]='Standardstatus';
$lang["checkmail_html"]='HTML-Inhalte zulassen? (experimentell, nicht empfohlen)';
$lang["checkmail_mail_skipped"]='Übersetzung: Übersprungene E-Mail';
$lang["checkmail_allow_users_based_on_permission_label"]='Sollen Benutzer aufgrund von Berechtigungen zum Hochladen zugelassen werden?';
$lang["addresourcesviaemail"]='Hinzufügen per E-Mail.';
$lang["uploadviaemail"]='Hinzufügen per E-Mail.';
$lang["uploadviaemail-intro"]='Um per E-Mail hochzuladen, fügen Sie Ihre Datei(en) als Anhang hinzu und senden Sie die E-Mail an <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Stellen Sie sicher, dass Sie sie von <b>[fromaddress]</b> senden, sonst wird sie ignoriert.</p><p>Beachten Sie, dass alles im BETREFF der E-Mail in das Feld [subjectfield] in %applicationname% übernommen wird. </p><p>Beachten Sie auch, dass alles im TEXTKÖRPER der E-Mail in das Feld [bodyfield] in %applicationname% übernommen wird. </p>  <p>Mehrere Dateien werden zu einer Sammlung gruppiert. Ihre Ressourcen werden standardmäßig auf den Zugriffslevel <b>\'[access]\'</b> und den Archivstatus <b>\'[archive]\'</b> gesetzt.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Sie erhalten eine Bestätigungs-E-Mail, wenn Ihre E-Mail erfolgreich verarbeitet wurde. Falls Ihre E-Mail aus irgendeinem Grund programmatisch übersprungen wird (zum Beispiel, wenn sie von der falschen Adresse gesendet wird), wird der Administrator benachrichtigt, dass eine E-Mail Aufmerksamkeit erfordert.';
$lang["yourresourcehasbeenuploaded"]='Ihre Ressource wurde hochgeladen.';
$lang["yourresourceshavebeenuploaded"]='Ihre Ressourcen wurden hochgeladen.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), mit der ID [user-ref] und der E-Mail-Adresse [user-email], ist nicht berechtigt, per E-Mail hochzuladen (überprüfen Sie die Berechtigungen "c" oder "d" oder die blockierten Benutzer auf der Checkmail-Setup-Seite). Aufgezeichnet am: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Erstellt vom Check Mail-Plugin.';