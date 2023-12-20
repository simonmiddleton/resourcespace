<?php


$lang["checkmail_configuration"]='Nastavenie kontroly e-mailu.';
$lang["checkmail_install_php_imap_extension"]='Krok prvý: Nainštalujte rozšírenie php imap.';
$lang["checkmail_cronhelp"]='Tento plugin vyžaduje špeciálne nastavenie, aby systém mohol prihlásiť sa do e-mailového účtu určeného na prijímanie súborov určených na nahratie.<br /><br />Uistite sa, že je na účte povolený protokol IMAP. Ak používate účet Gmail, povolte IMAP v Nastavenia->POP/IMAP->Povoliť IMAP.<br /><br />
Pri prvotnom nastavení vám môže byť najviac užitočné spustiť plugin ručne z príkazového riadku pomocou súboru plugins/checkmail/pages/cron_check_email.php, aby ste pochopili, ako funguje.
Ak sa úspešne pripojíte a pochopíte, ako skript funguje, musíte nastaviť cron job, ktorý ho spustí každú minútu alebo dve.<br />Bude skenovať schránku a prečíta jednu neprečítanú e-mailovú správu za každý beh.<br /><br />
Príklad cron jobu, ktorý sa spúšťa každé dve minúty:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Váš IMAP účet bol naposledy skontrolovaný [lastcheck].';
$lang["checkmail_cronjobprob"]='Váš cronjob pre kontrolu emailov sa pravdepodobne nespúšťa správne, pretože uplynulo viac ako 5 minút od jeho posledného spustenia.<br /><br />
Príklad cronjobu, ktorý sa spúšťa každú minútu:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap Server<br />(gmail="imap.gmail.com:993/ssl")

Imap Server<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='E-mail';
$lang["checkmail_password"]='Heslo.';
$lang["checkmail_extension_mapping"]='Typ zdroja pomocou mapovania prípon súborov.';
$lang["checkmail_default_resource_type"]='Predvolený typ zdroja.';
$lang["checkmail_extension_mapping_desc"]='Po izbiri privzetega tipa vira je spodaj en vnos za vsak tip vira. <br />Če želite naložiti datoteke različnih tipov v določen vir, dodajte seznam ločenih vejic z razširitvami datotek (npr. jpg, gif, png).';
$lang["checkmail_subject_field"]='Pole predmetu';
$lang["checkmail_body_field"]='Pole Tela';
$lang["checkmail_purge"]='Vymazať e-maily po nahratí?';
$lang["checkmail_confirm"]='Poslať potvrdzovacie e-maily?';
$lang["checkmail_users"]='Povolení užívatelia.';
$lang["checkmail_blocked_users_label"]='Zablokovaní používatelia.';
$lang["checkmail_default_access"]='Predvolený prístup.';
$lang["checkmail_default_archive"]='Predvolený stav.';
$lang["checkmail_html"]='Povolit HTML obsah? (experimentální, nedoporučuje se)';
$lang["checkmail_mail_skipped"]='Preskočený e-mail.';
$lang["checkmail_allow_users_based_on_permission_label"]='Majú byť používateľom na základe povolenia umožnené nahrávať súbory?';
$lang["addresourcesviaemail"]='Pridať cez e-mail.';
$lang["uploadviaemail"]='Pridať cez e-mail.';
$lang["uploadviaemail-intro"]='Pre nahratie cez e-mail, priložte svoj(e) súbor(y) a adresujte e-mail na <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Uistite sa, že ho posielate z <b>[fromaddress]</b>, inak bude ignorovaný.</p><p>Všimnite si, že čokoľvek v PREDMETE e-mailu sa dostane do poľa [subjectfield] v %applicationname%.</p><p>Taktiež si všimnite, že čokoľvek v TELE e-mailu sa dostane do poľa [bodyfield] v %applicationname%.</p><p>Viaceré súbory budú zoskupené do kolekcie. Vaše zdroje budú mať predvolenú úroveň prístupu <b>\'[access]\'</b> a stav archivácie <b>\'[archive]\'</b>.</p><p>[confirmation]</p>';
$lang["checkmail_confirmation_message"]='Po úspešnom spracovaní Vášho e-mailu obdržíte potvrdzovací e-mail. Ak sa Váš e-mail z nejakého dôvodu (napríklad ak bol odoslaný z nesprávnej adresy) programovo preskočí, administrátor bude upozornený na e-mail, ktorý vyžaduje pozornosť.';
$lang["yourresourcehasbeenuploaded"]='Váš zdroj bol nahratý.';
$lang["yourresourceshavebeenuploaded"]='Vaše zdroje boli nahraté.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), s ID [user-ref] a e-mailom [user-email] nemá povolenie na nahrávanie cez e-mail (skontrolujte oprávnenia "c" alebo "d" alebo zablokovaných používateľov na stránke nastavenia checkmail). Zaznamenané dňa: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Vytvorené z doplnku Kontrola e-mailov.';