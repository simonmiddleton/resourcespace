<?php


$lang["checkmail_configuration"]='Konfigurace Checkmail';
$lang["checkmail_install_php_imap_extension"]='Krok jedna: Nainstalujte rozšíření php imap.';
$lang["checkmail_cronhelp"]='Tento plugin vyžaduje speciální nastavení, aby se systém mohl přihlásit k e-mailovému účtu určenému pro přijímání souborů určených k nahrání.<br /><br />Ujistěte se, že je na účtu povolen IMAP. Pokud používáte účet Gmail, povolte IMAP v Nastavení->POP/IMAP->Povolit IMAP<br /><br />
Při počátečním nastavení může být nejvhodnější spustit plugins/checkmail/pages/cron_check_email.php ručně z příkazového řádku, abyste pochopili, jak funguje.
Jakmile se správně připojíte a pochopíte, jak skript funguje, musíte nastavit cron úlohu, aby se spouštěla každou minutu nebo dvě.<br />Bude prohledávat schránku a číst jeden nepřečtený e-mail při každém spuštění.<br /><br />
Příklad cron úlohy, která se spouští každé dvě minuty:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Váš IMAP účet byl naposledy zkontrolován [lastcheck].';
$lang["checkmail_cronjobprob"]='Váš cronjob pro kontrolu pošty nemusí běžet správně, protože uplynulo více než 5 minut od jeho posledního spuštění.<br /><br />
Příklad cronjobu, který běží každou minutu:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap Server<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='E-mail';
$lang["checkmail_password"]='Heslo';
$lang["checkmail_extension_mapping"]='Typ zdroje podle mapování přípony souboru';
$lang["checkmail_default_resource_type"]='Výchozí typ zdroje';
$lang["checkmail_extension_mapping_desc"]='Po výběru Výchozího typu zdroje je níže jedno pole pro každý z vašich typů zdrojů. <br />Chcete-li přinutit nahrané soubory různých typů do konkrétního typu zdroje, přidejte čárkou oddělené seznamy přípon souborů (např. jpg,gif,png).';
$lang["checkmail_resource_type_population"]='(z povolených přípon)';
$lang["checkmail_subject_field"]='Předmětové pole';
$lang["checkmail_body_field"]='Pole těla';
$lang["checkmail_purge"]='Vymazat e-maily po nahrání?';
$lang["checkmail_confirm"]='Odeslat potvrzovací e-maily?';
$lang["checkmail_users"]='Povolení uživatelé';
$lang["checkmail_blocked_users_label"]='Blokovaní uživatelé';
$lang["checkmail_default_access"]='Výchozí přístup';
$lang["checkmail_default_archive"]='Výchozí stav';
$lang["checkmail_html"]='Povolit obsah HTML? (experimentální, nedoporučuje se)';
$lang["checkmail_mail_skipped"]='Přeskočený e-mail';
$lang["checkmail_allow_users_based_on_permission_label"]='Měli by mít uživatelé povoleno nahrávat na základě oprávnění?';
$lang["addresourcesviaemail"]='Přidat přes e-mail';
$lang["uploadviaemail"]='Přidat přes e-mail';
$lang["uploadviaemail-intro"]='<br /><br />Pro nahrání prostřednictvím e-mailu připojte svůj(e) soubor(y) a adresujte e-mail na <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Ujistěte se, že jej posíláte z <b>[fromaddress]</b>, jinak bude ignorován.</p><p>Všimněte si, že cokoliv v PŘEDMĚTU e-mailu bude vloženo do pole [subjectfield] v [applicationname]. </p><p> Také si všimněte, že cokoliv v TĚLE e-mailu bude vloženo do pole [bodyfield] v [applicationname]. </p>  <p>Více souborů bude seskupeno do kolekce. Vaše zdroje budou mít výchozí úroveň přístupu <b>\'[access]\'</b> a stav archivu <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Obdržíte potvrzovací e-mail, jakmile bude váš e-mail úspěšně zpracován. Pokud bude váš e-mail z jakéhokoli důvodu programově přeskočen (například pokud je odeslán z nesprávné adresy), administrátor bude upozorněn, že je třeba věnovat pozornost e-mailu.';
$lang["yourresourcehasbeenuploaded"]='Váš zdroj byl nahrán';
$lang["yourresourceshavebeenuploaded"]='Vaše zdroje byly nahrány';
$lang["checkmail_not_allowed_error_template"]='[uživatel-fullname] ([uživatelské jméno]), s ID [uživatel-ref] a e-mailem [uživatel-email] nemá povoleno nahrávat přes e-mail (zkontrolujte oprávnění "c" nebo "d" nebo blokované uživatele na stránce nastavení checkmail). Zaznamenáno: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Vytvořeno z pluginu Check Mail';