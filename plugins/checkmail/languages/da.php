<?php


$lang["checkmail_configuration"]='Konfiguration af Checkmail';
$lang["checkmail_install_php_imap_extension"]='Trin Et: Installer php imap-udvidelsen.';
$lang["checkmail_cronhelp"]='Dette plugin kræver en særlig opsætning for, at systemet kan logge ind på en e-mailkonto, der er dedikeret til at modtage filer, der er beregnet til upload.<br /><br />Sørg for, at IMAP er aktiveret på kontoen. Hvis du bruger en Gmail-konto, kan du aktivere IMAP i Indstillinger->POP/IMAP->Aktivér IMAP<br /><br />
Ved den første opsætning kan det være mest hjælpsomt at køre plugins/checkmail/pages/cron_check_email.php manuelt på kommandolinjen for at forstå, hvordan det fungerer.
Når du er tilsluttet korrekt og forstår, hvordan scriptet fungerer, skal du opsætte en cron-job for at køre det hvert minut eller to.<br />Det vil scanne postkassen og læse en ulæst e-mail pr. kørsel.<br /><br />
Et eksempel på et cron-job, der kører hvert andet minut:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Din IMAP-konto blev sidst tjekket den [lastcheck].';
$lang["checkmail_cronjobprob"]='Din checkmail cronjob kører måske ikke korrekt, fordi det er mere end 5 minutter siden, det sidst kørte.<br /><br />
Et eksempel på en cron job, der kører hvert minut:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap Server<br />(gmail="imap.gmail.com:993/ssl")

Imap Server<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='E-mail.';
$lang["checkmail_password"]='Adgangskode';
$lang["checkmail_extension_mapping"]='Ressourcetype via filtypemapping.';
$lang["checkmail_default_resource_type"]='Standard ressourcetype.';
$lang["checkmail_extension_mapping_desc"]='Efter standardressourcetype-vælgeren er der én indtastning nedenfor for hver af dine ressourcetyper. <br />For at tvinge uploadede filer af forskellige typer ind i en bestemt ressourcetype, tilføj kommaseparerede lister af filudvidelser (f.eks. jpg, gif, png).';
$lang["checkmail_resource_type_population"]='<br />(fra allowed_extensions)';
$lang["checkmail_subject_field"]='Emnefelt';
$lang["checkmail_body_field"]='Kropfelt (or Kropsfelt)';
$lang["checkmail_purge"]='Slette e-mails efter upload?';
$lang["checkmail_confirm"]='Send bekræftelses-e-mails?';
$lang["checkmail_users"]='Tilladte brugere.';
$lang["checkmail_blocked_users_label"]='Blokerede brugere.';
$lang["checkmail_default_access"]='Standardadgang.';
$lang["checkmail_default_archive"]='Standardstatus.';
$lang["checkmail_html"]='Tillad HTML-indhold? (eksperimentelt, ikke anbefalet)';
$lang["checkmail_mail_skipped"]='Oversprungen e-mail.';
$lang["checkmail_allow_users_based_on_permission_label"]='Skal brugere have tilladelse til at uploade baseret på tilladelser?';
$lang["addresourcesviaemail"]='Tilføj via e-mail.';
$lang["uploadviaemail"]='Tilføj via e-mail.';
$lang["uploadviaemail-intro"]='For at uploade via e-mail, vedhæft din(e) fil(er) og adressér e-mailen til <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Sørg for at sende den fra <b>[fromaddress]</b>, ellers vil den blive ignoreret.</p><p>Bemærk, at alt i EMNET på e-mailen vil blive placeret i feltet [subjectfield] i %applicationname%.</p><p>Bemærk også, at alt i INDHOLDET af e-mailen vil blive placeret i feltet [bodyfield] i %applicationname%.</p><p>Flere filer vil blive grupperet i en samling. Dine ressourcer vil som standard have en adgangsniveau på <b>\'[access]\'</b> og en arkiveringsstatus på <b>\'[archive]\'</b>.</p><p>[confirmation]</p>';
$lang["checkmail_confirmation_message"]='Du vil modtage en bekræftelses-e-mail, når din e-mail er blevet behandlet succesfuldt. Hvis din e-mail af en eller anden grund bliver sprunget over (fx hvis den er sendt fra en forkert adresse), vil administrator blive underrettet om, at der er en e-mail, der kræver opmærksomhed.';
$lang["yourresourcehasbeenuploaded"]='Din fil er blevet uploadet.';
$lang["yourresourceshavebeenuploaded"]='Dine ressourcer er blevet uploadet.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), med ID [user-ref] og e-mail [user-email], har ikke tilladelse til at uploade via e-mail (kontroller tilladelserne "c" eller "d" eller de blokerede brugere på siden for checkmail opsætning). Registreret den: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Oprettet fra Check Mail-plugin.';