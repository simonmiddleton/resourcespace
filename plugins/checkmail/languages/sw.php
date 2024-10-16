<?php


$lang["checkmail_configuration"]='Mipangilio ya Checkmail';
$lang["checkmail_install_php_imap_extension"]='Hatua ya Kwanza: Sakinisha kiendelezi cha php imap.';
$lang["checkmail_cronhelp"]='Programu-jalizi hii inahitaji usanidi maalum ili mfumo uweze kuingia kwenye akaunti ya barua pepe iliyotengwa kwa kupokea faili zinazokusudiwa kupakiwa.<br /><br />Hakikisha kwamba IMAP imewezeshwa kwenye akaunti. Ikiwa unatumia akaunti ya Gmail, wezesha IMAP katika Mipangilio->POP/IMAP->Washa IMAP<br /><br />Wakati wa usanidi wa awali, unaweza kuona ni muhimu zaidi kuendesha plugins/checkmail/pages/cron_check_email.php kwa mkono kwenye mstari wa amri ili kuelewa jinsi inavyofanya kazi. Mara unapounganisha vizuri na kuelewa jinsi script inavyofanya kazi, lazima uweke kazi ya cron kuendesha kila dakika moja au mbili.<br />Itachunguza kisanduku cha barua na kusoma barua pepe moja isiyosomwa kwa kila uendeshaji.<br /><br />Mfano wa kazi ya cron inayofanya kazi kila dakika mbili:<br />*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Akaunti yako ya IMAP ilikaguliwa mara ya mwisho mnamo [lastcheck].';
$lang["checkmail_cronjobprob"]='Kazi yako ya cron ya kuangalia barua pepe inaweza kuwa haifanyi kazi ipasavyo, kwa sababu imepita zaidi ya dakika 5 tangu ilipokimbia mara ya mwisho.<br /><br />
Mfano wa kazi ya cron inayokimbia kila dakika:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Seva ya Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Barua pepe';
$lang["checkmail_password"]='Nenosiri';
$lang["checkmail_extension_mapping"]='Aina ya Rasilimali kupitia Ulinganifu wa Kiendelezi cha Faili';
$lang["checkmail_default_resource_type"]='Aina ya Rasilimali Chaguomsingi';
$lang["checkmail_extension_mapping_desc"]='Baada ya kiongeza chaguo la Aina ya Rasilimali Chaguo-msingi, kuna ingizo moja chini kwa kila moja ya Aina zako za Rasilimali. <br />Ili kulazimisha faili zilizopakiwa za aina tofauti kuingia katika Aina maalum ya Rasilimali, ongeza orodha za viendelezi vya faili vilivyotenganishwa kwa koma (mf. jpg,gif,png).';
$lang["checkmail_resource_type_population"]='<br />(kutoka allowed_extensions)';
$lang["checkmail_subject_field"]='Sehemu ya Mada';
$lang["checkmail_body_field"]='Sehemu ya Mwili';
$lang["checkmail_purge"]='Futa barua pepe baada ya kupakia?';
$lang["checkmail_confirm"]='Tuma barua pepe za uthibitisho?';
$lang["checkmail_users"]='Watumiaji Walioruhusiwa';
$lang["checkmail_blocked_users_label"]='Watumiaji waliopigwa marufuku';
$lang["checkmail_default_access"]='Ufikiaji Chaguomsingi';
$lang["checkmail_default_archive"]='Hali Chaguomsingi';
$lang["checkmail_html"]='Ruhusu Maudhui ya HTML? (ya majaribio, hayapendekezwi)';
$lang["checkmail_mail_skipped"]='Barua pepe iliyopitishwa';
$lang["checkmail_allow_users_based_on_permission_label"]='Je, watumiaji wanapaswa kuruhusiwa kulingana na ruhusa kupakia?';
$lang["addresourcesviaemail"]='Ongeza kupitia Barua pepe';
$lang["uploadviaemail"]='Ongeza kupitia Barua pepe';
$lang["uploadviaemail-intro"]='Ili kupakia kupitia barua pepe, ambatanisha faili zako na uelekeze barua pepe kwa <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>. Hakikisha unatuma kutoka <b>[fromaddress]</b>, la sivyo itapuuzwa. Kumbuka kwamba chochote kilicho kwenye KICHWA cha barua pepe kitaingia kwenye sehemu ya [subjectfield] katika [applicationname]. Pia kumbuka kwamba chochote kilicho kwenye MWILI wa barua pepe kitaingia kwenye sehemu ya [bodyfield] katika [applicationname]. Faili nyingi zitawekwa pamoja katika mkusanyiko. Rasilimali zako zitakuwa na kiwango cha Ufikiaji cha chaguo-msingi <b>\'[access]\'</b>, na Hali ya Kumbukumbu <b>\'[archive]\'</b>. [confirmation]';
$lang["checkmail_confirmation_message"]='Utapokea barua pepe ya uthibitisho wakati barua pepe yako itakapochakatwa kwa mafanikio. Ikiwa barua pepe yako itarukwa kiotomatiki kwa sababu yoyote (kama vile ikiwa imetumwa kutoka kwa anwani isiyo sahihi), msimamizi ataarifiwa kwamba kuna barua pepe inayohitaji umakini.';
$lang["yourresourcehasbeenuploaded"]='Rasilimali yako imepakiwa juu';
$lang["yourresourceshavebeenuploaded"]='Rasilimali zako zimepakiwa juu';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), mwenye kitambulisho [user-ref] na barua pepe [user-email] haruhusiwi kupakia kupitia barua pepe (angalia ruhusa "c" au "d" au watumiaji waliopigwa marufuku kwenye ukurasa wa mipangilio ya checkmail). Imeandikwa: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Imeundwa kutoka kwa programu-jalizi ya Angalia Barua pepe';