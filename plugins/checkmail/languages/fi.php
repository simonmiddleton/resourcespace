<?php


$lang["checkmail_configuration"]='Tarkista sähköpostin asetukset.';
$lang["checkmail_install_php_imap_extension"]='Vaihe yksi: Asenna php imap -laajennus.';
$lang["checkmail_cronhelp"]='Tämä lisäosa vaatii erityisasetuksia, jotta järjestelmä voi kirjautua sähköpostitiliin, joka on omistettu tiedostojen vastaanottamiseen ladattavaksi.<br /><br />Varmista, että IMAP on käytössä tilillä. Jos käytät Gmail-tiliä, voit ottaa IMAP:n käyttöön Asetukset->POP/IMAP->Ota IMAP käyttöön<br /><br />
Alustavan asennuksen yhteydessä on hyödyllistä ajaa plugins/checkmail/pages/cron_check_email.php manuaalisesti komentoriviltä, jotta ymmärrät, miten se toimii.
Kun olet yhdistänyt oikein ja ymmärrät, miten skripti toimii, sinun on asetettava cron-tehtävä ajamaan sitä joka minuutti tai kaksi.<br />Se skannaa postilaatikon ja lukee yhden lukemattoman sähköpostin joka ajolla.<br /><br />
Esimerkki cron-tehtävästä, joka ajetaan joka toinen minuutti:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='IMAP-tilisi tarkistettiin viimeksi [lastcheck].';
$lang["checkmail_cronjobprob"]='Tarkista sähköpostisi cronjob, sillä siitä voi olla ongelmia. Viimeisimmästä ajosta on kulunut yli 5 minuuttia.<br /><br />
Esimerkki cronjobista, joka ajetaan joka minuutti:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Imap-palvelin<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Sähköposti';
$lang["checkmail_password"]='Salasana';
$lang["checkmail_extension_mapping"]='Resurssityyppi tiedostopäätekartan avulla.';
$lang["checkmail_default_resource_type"]='Oletusarvoisen resurssityypin.';
$lang["checkmail_extension_mapping_desc"]='Oletusresurssityypin valitsimen jälkeen on yksi syötekenttä jokaiselle resurssityypillesi. <br />Jos haluat pakottaa eri tiedostotyypit latautumaan tiettyyn resurssityyppiin, lisää pilkulla erotettu luettelo tiedostopäätteistä (esim. jpg,gif,png).';
$lang["checkmail_subject_field"]='Aihe-kenttä';
$lang["checkmail_body_field"]='Kehon kenttä.';
$lang["checkmail_purge"]='Poistetaanko sähköpostit latauksen jälkeen?';
$lang["checkmail_confirm"]='Lähetä vahvistusviestit sähköpostitse?';
$lang["checkmail_users"]='Sallitut käyttäjät.';
$lang["checkmail_blocked_users_label"]='Estetty käyttäjät';
$lang["checkmail_default_access"]='Oletusarvoinen käyttöoikeus';
$lang["checkmail_default_archive"]='Oletusarvo tila';
$lang["checkmail_html"]='Salli HTML-sisältö? (kokeellinen, ei suositeltavaa)';
$lang["checkmail_mail_skipped"]='Ohitettu sähköposti.';
$lang["checkmail_allow_users_based_on_permission_label"]='Pitäisikö käyttäjien saada ladata tiedostoja käyttöoikeuksien perusteella?';
$lang["addresourcesviaemail"]='Lisää sähköpostitse.';
$lang["uploadviaemail"]='Lisää sähköpostitse.';
$lang["uploadviaemail-intro"]='Lähettääksesi tiedostoja sähköpostitse, liitä tiedostot viestiin ja lähetä se osoitteeseen <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p> Varmista, että lähetät viestin osoitteesta <b>[fromaddress]</b>, muuten se jätetään huomiotta.</p><p>Huomaa, että viestin AIHE-kenttään kirjoittamasi tulee menemään [subjectfield] kenttään %applicationname%. </p><p> Huomaa myös, että viestin SISÄLTÖ-kenttään kirjoittamasi tulee menemään [bodyfield] kenttään %applicationname%. </p>  <p>Useat tiedostot ryhmitellään kokoelmaksi. Tiedostosi oletusarvoinen käyttöoikeustaso on <b>\'[access]\'</b>, ja arkistointitila <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Saat vahvistussähköpostin, kun sähköpostisi on käsitelty onnistuneesti. Jos sähköpostisi ohitetaan ohjelmallisesti mistä tahansa syystä (kuten jos se lähetetään väärästä osoitteesta), järjestelmänvalvoja saa ilmoituksen huomiota vaativasta sähköpostista.';
$lang["yourresourcehasbeenuploaded"]='Resurssisi on ladattu palvelimelle.';
$lang["yourresourceshavebeenuploaded"]='"Resurssisi on ladattu."';
$lang["checkmail_not_allowed_error_template"]='Käyttäjä [user-fullname] ([username]), tunnisteella [user-ref] ja sähköpostilla [user-email], ei ole lupa lähettää tiedostoja sähköpostitse (tarkista käyttöoikeudet "c" tai "d" tai estetyt käyttäjät Checkmail-asetussivulla). Kirjattu: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Luotu Tarkista sähköposti -liitännäisestä.';