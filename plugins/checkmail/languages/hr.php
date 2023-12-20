<?php


$lang["checkmail_configuration"]='Konfiguracija provjere e-pošte.';
$lang["checkmail_install_php_imap_extension"]='Korak jedan: Instalirajte php imap ekstenziju.';
$lang["checkmail_cronhelp"]='Ovaj dodatak zahtijeva posebnu konfiguraciju sustava kako bi se prijavio na e-mail račun namijenjen primanju datoteka namijenjenih za prijenos.<br /><br />Provjerite je li IMAP omogućen na računu. Ako koristite Gmail račun, omogućite IMAP u Postavkama->POP/IMAP->Omogući IMAP<br /><br />
Prilikom početne konfiguracije, najkorisnije bi bilo pokrenuti plugins/checkmail/pages/cron_check_email.php ručno u naredbenom retku kako biste razumjeli kako radi.
Kada se uspješno povežete i razumijete kako skripta radi, morate postaviti cron posao da ga pokreće svaku minutu ili dvije.<br />Skenirat će poštu i čitati jednu nepročitanu e-poštu po pokretanju.<br /><br />
Primjer cron posla koji se pokreće svake dvije minute:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Vaš IMAP račun je posljednji put provjeren [lastcheck].';
$lang["checkmail_cronjobprob"]='Vaš cronjob za provjeru e-pošte možda ne radi ispravno, jer je prošlo više od 5 minuta od posljednjeg pokretanja.<br /><br />
Primjer cron joba koji se pokreće svaku minutu:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap poslužitelj<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Email (Elektronička pošta)';
$lang["checkmail_password"]='Lozinka';
$lang["checkmail_extension_mapping"]='Vrsta resursa putem mapiranja ekstenzija datoteka.';
$lang["checkmail_default_resource_type"]='Zadani tip resursa.';
$lang["checkmail_extension_mapping_desc"]='Nakon odabira zadane vrste resursa, ispod se nalazi jedan unos za svaku vrstu resursa. <br />Da biste prisilili prenesene datoteke različitih vrsta u određenu vrstu resursa, dodajte popise razdvojene zarezima ekstenzija datoteka (npr. jpg, gif, png).';
$lang["checkmail_subject_field"]='Polje predmeta';
$lang["checkmail_body_field"]='Polje Tijela';
$lang["checkmail_purge"]='Izbriši e-poštu nakon prijenosa?';
$lang["checkmail_confirm"]='Poslati potvrdu putem e-pošte?';
$lang["checkmail_users"]='Dopušteni korisnici.';
$lang["checkmail_blocked_users_label"]='Blokirani korisnici.';
$lang["checkmail_default_access"]='Zadani pristup.';
$lang["checkmail_default_archive"]='Zadani status.';
$lang["checkmail_html"]='Dozvoliti HTML sadržaj? (eksperimentalno, nije preporučljivo)';
$lang["checkmail_mail_skipped"]='Preskočena e-pošta.';
$lang["checkmail_allow_users_based_on_permission_label"]='Treba li korisnicima biti dopušteno preuzimanje na temelju dozvole?';
$lang["addresourcesviaemail"]='Dodaj putem e-pošte.';
$lang["uploadviaemail"]='Dodaj putem e-pošte.';
$lang["uploadviaemail-intro"]='Za prijenos putem e-pošte, priložite datoteku(e) i adresirajte e-poštu na <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Obavezno je poslati s <b>[fromaddress]</b>, inače će biti ignorirano.</p><p>Imajte na umu da će sve što je u NASLOVU e-pošte biti u polju [subjectfield] u %applicationname%.</p><p>Također imajte na umu da će sve što je u SADRŽAJU e-pošte biti u polju [bodyfield] u %applicationname%.</p><p>Višestruke datoteke bit će grupirane u kolekciju. Vaši resursi će prema zadanim postavkama biti s pristupom <b>\'[access]\'</b> i statusom arhive <b>\'[archive]\'</b>.</p><p> [confirmation]</p>';
$lang["checkmail_confirmation_message"]='Primit ćete potvrdni e-mail kada se vaš e-mail uspješno obradi. Ako se vaš e-mail programski preskoči iz bilo kojeg razloga (npr. ako je poslan s krive adrese), administrator će biti obaviješten da postoji e-mail koji zahtijeva pažnju.';
$lang["yourresourcehasbeenuploaded"]='Vaš resurs je prenesen.';
$lang["yourresourceshavebeenuploaded"]='Vaši resursi su učitani.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), s ID-om [user-ref] i e-mailom [user-email], nema dopuštenje za slanje datoteka e-poštom (provjerite dozvole "c" ili "d" ili blokirane korisnike na stranici za provjeru e-pošte). Zabilježeno: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Stvoreno iz dodatka Provjeri poštu.';