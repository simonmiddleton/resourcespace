<?php


$lang["checkmail_configuration"]='Controleer e-mail configuratie.';
$lang["checkmail_install_php_imap_extension"]='Stap één: Installeer de PHP IMAP-extensie.';
$lang["checkmail_cronhelp"]='Deze plugin vereist een speciale configuratie om het systeem in te laten loggen op een e-mailaccount dat is toegewijd aan het ontvangen van bestanden die bedoeld zijn voor uploaden.<br /><br />Zorg ervoor dat IMAP is ingeschakeld op het account. Als u een Gmail-account gebruikt, schakelt u IMAP in via Instellingen->POP/IMAP->IMAP inschakelen.<br /><br />
Bij de initiële configuratie kan het handig zijn om plugins/checkmail/pages/cron_check_email.php handmatig op de opdrachtregel uit te voeren om te begrijpen hoe het werkt.
Zodra u correct verbinding maakt en begrijpt hoe het script werkt, moet u een cron-taak instellen om het elke minuut of twee uit te voeren.<br />Het zal de mailbox scannen en één ongelezen e-mail per uitvoering lezen.<br /><br />
Een voorbeeld cron-taak die elke twee minuten wordt uitgevoerd:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Je IMAP-account is voor het laatst gecontroleerd op [lastcheck].';
$lang["checkmail_cronjobprob"]='Uw checkmail cronjob functioneert mogelijk niet correct, omdat het meer dan 5 minuten geleden is sinds de laatste uitvoering.<br /><br />
Een voorbeeld cron job die elke minuut wordt uitgevoerd:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap Server<br />(gmail="imap.gmail.com:993/ssl")

Imap Server<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='E-mail.';
$lang["checkmail_password"]='Wachtwoord';
$lang["checkmail_extension_mapping"]='Bron Type via Bestandsextensie Mapping';
$lang["checkmail_default_resource_type"]='Standaard bron type.';
$lang["checkmail_extension_mapping_desc"]='Na de standaard bron type selector, is er één invoerveld voor elk van uw bron types. <br />Om geüploade bestanden van verschillende typen in een specifiek bron type te dwingen, voegt u komma gescheiden lijsten van bestandsextensies toe (bijv. jpg, gif, png).';
$lang["checkmail_subject_field"]='Onderwerpveld';
$lang["checkmail_body_field"]='Lichaamsveld.';
$lang["checkmail_purge"]='E-mails verwijderen na uploaden?';
$lang["checkmail_confirm"]='Verstuur bevestigingsmails?';
$lang["checkmail_users"]='Toegestane gebruikers';
$lang["checkmail_blocked_users_label"]='Geblokkeerde gebruikers';
$lang["checkmail_default_access"]='Standaardtoegang';
$lang["checkmail_default_archive"]='Standaardstatus';
$lang["checkmail_html"]='Toestaan van HTML-inhoud? (experimenteel, niet aanbevolen)';
$lang["checkmail_mail_skipped"]='Overgeslagen e-mail.';
$lang["checkmail_allow_users_based_on_permission_label"]='Moeten gebruikers op basis van toestemming worden toegestaan om te uploaden?';
$lang["addresourcesviaemail"]='Toevoegen via e-mail.';
$lang["uploadviaemail"]='Toevoegen via e-mail.';
$lang["uploadviaemail-intro"]='Om te uploaden via e-mail, voeg uw bestand(en) toe als bijlage en stuur de e-mail naar <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Zorg ervoor dat u het verstuurt vanaf <b>[fromaddress]</b>, anders wordt het genegeerd.</p><p>Houd er rekening mee dat alles in het ONDERWERP van de e-mail in het veld [subjectfield] in %applicationname% terechtkomt. </p><p> Let ook op dat alles in de TEKST van de e-mail in het veld [bodyfield] in %applicationname% terechtkomt. </p>  <p>Meerdere bestanden worden gegroepeerd in een collectie. Uw bronnen worden standaard ingesteld op een toegangsniveau <b>\'[access]\'</b>, en archiefstatus <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Je ontvangt een bevestigingsmail wanneer je e-mail succesvol is verwerkt. Als je e-mail om welke reden dan ook programmatisch wordt overgeslagen (bijvoorbeeld als deze vanaf het verkeerde adres is verzonden), wordt de beheerder op de hoogte gesteld dat er een e-mail aandacht vereist.';
$lang["yourresourcehasbeenuploaded"]='Je bronbestand is geüpload.';
$lang["yourresourceshavebeenuploaded"]='Uw bronnen zijn geüpload.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), met ID [user-ref] en e-mail [user-email] is niet toegestaan om via e-mail te uploaden (controleer de machtigingen "c" of "d" of de geblokkeerde gebruikers in de checkmail-instellingenpagina). Opgenomen op: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Gemaakt vanuit de Check Mail plugin.';