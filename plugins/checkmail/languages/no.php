<?php


$lang["checkmail_configuration"]='Konfigurasjon av Sjekk e-post.';
$lang["checkmail_install_php_imap_extension"]='Trinn én: Installer php imap-utvidelsen.';
$lang["checkmail_cronhelp"]='Dette tillegget krever noen spesielle innstillinger for at systemet skal kunne logge inn på en e-postkonto som er dedikert til å motta filer som er ment for opplasting.<br /><br />Sørg for at IMAP er aktivert på kontoen. Hvis du bruker en Gmail-konto, kan du aktivere IMAP i Innstillinger->POP/IMAP->Aktiver IMAP<br /><br />
Ved første gangs oppsett kan det være nyttig å kjøre plugins/checkmail/pages/cron_check_email.php manuelt på kommandolinjen for å forstå hvordan det fungerer.
Når du har koblet til riktig og forstår hvordan skriptet fungerer, må du sette opp en cron-jobb for å kjøre den hvert minutt eller to.<br />Den vil skanne postkassen og lese én uleste e-post per kjøring.<br /><br />
Et eksempel på en cron-jobb som kjører hvert andre minutt:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Din IMAP-konto ble sist sjekket på [lastcheck].';
$lang["checkmail_cronjobprob"]='Din cronjob for sjekk av e-post kan være feilaktig, fordi det har gått mer enn 5 minutter siden den sist kjørte.<br /><br />
Et eksempel på en cronjob som kjører hvert minutt:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap-server<br />(gmail="imap.gmail.com:993/ssl")<br />
(Imap-server<br />(gmail="imap.gmail.com:993/ssl"))';
$lang["checkmail_email"]='E-post.';
$lang["checkmail_password"]='Passord';
$lang["checkmail_extension_mapping"]='Ressurstype via filtypekartlegging.';
$lang["checkmail_default_resource_type"]='Standard ressurstype';
$lang["checkmail_extension_mapping_desc"]='Etter standard ressurstypevelgeren er det en inndatafelt for hver av ressurstypene dine. <br />For å tvinge opplastede filer av forskjellige typer til en bestemt ressurstype, legg til komma-separerte lister over filtyper (f.eks. jpg, gif, png).';
$lang["checkmail_subject_field"]='Emnefelt';
$lang["checkmail_body_field"]='Kroppsfelt';
$lang["checkmail_purge"]='Slette e-poster etter opplasting?';
$lang["checkmail_confirm"]='Send bekreftelses-e-poster?';
$lang["checkmail_users"]='Tillatte brukere.';
$lang["checkmail_blocked_users_label"]='Blokkerte brukere.';
$lang["checkmail_default_access"]='Standard tilgang.';
$lang["checkmail_default_archive"]='Standardstatus';
$lang["checkmail_html"]='Tillat HTML-innhold? (eksperimentelt, ikke anbefalt)';
$lang["checkmail_mail_skipped"]='Hoppet over e-post.';
$lang["checkmail_allow_users_based_on_permission_label"]='Skal brukere få lov til å laste opp basert på tillatelse?';
$lang["addresourcesviaemail"]='Legg til via e-post.';
$lang["uploadviaemail"]='Legg til via e-post.';
$lang["uploadviaemail-intro"]='For å laste opp via e-post, legg ved filen(e) og send e-posten til <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Sørg for å sende den fra <b>[fromaddress]</b>, ellers vil den bli ignorert.</p><p>Merk at alt i EMNEfeltet i e-posten vil gå inn i feltet [subjectfield] i %applicationname%.</p><p>Legg også merke til at alt i KROPPEN av e-posten vil gå inn i feltet [bodyfield] i %applicationname%.</p><p>Flere filer vil bli gruppert i en samling. Ressursene dine vil som standard ha tilgangsnivå <b>\'[access]\'</b>, og arkivstatus <b>\'[archive]\'</b>.</p><p> [confirmation]</p>';
$lang["checkmail_confirmation_message"]='Du vil motta en bekreftelses-e-post når din e-post er behandlet vellykket. Hvis din e-post blir programmert til å hoppe over av en eller annen grunn (for eksempel hvis den er sendt fra feil adresse), vil administrator bli varslet om at det er en e-post som krever oppmerksomhet.';
$lang["yourresourcehasbeenuploaded"]='Ditt ressurs er lastet opp.';
$lang["yourresourceshavebeenuploaded"]='Dine ressurser har blitt lastet opp.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), med ID [user-ref] og e-post [user-email] har ikke tillatelse til å laste opp via e-post (sjekk tillatelser "c" eller "d" eller blokkerte brukere på siden for e-postkontroll). Registrert på: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Opprettet fra Sjekk e-post-tillegget.';