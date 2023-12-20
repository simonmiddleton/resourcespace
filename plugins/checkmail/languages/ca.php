<?php


$lang["checkmail_configuration"]='Configuració de correu electrònic de comprovació.';
$lang["checkmail_install_php_imap_extension"]='Pas 1: Instal·la l\'extensió php imap.';
$lang["checkmail_cronhelp"]='Aquest connector requereix una configuració especial perquè el sistema pugui iniciar sessió en un compte de correu electrònic dedicat a rebre fitxers destinats a pujar.<br /><br />Assegureu-vos que IMAP estigui habilitat en el compte. Si esteu utilitzant un compte de Gmail, podeu habilitar IMAP a Configuració->POP/IMAP->Habilita IMAP<br /><br />
En la configuració inicial, pot resultar útil executar manualment el fitxer plugins/checkmail/pages/cron_check_email.php a través de la línia de comandes per entendre com funciona.<br />Un cop connectat correctament i entès com funciona l\'script, heu de configurar una tasca cron per executar-lo cada minut o dos.<br />Escanejarà el correu i llegirà un correu electrònic no llegit per cada execució.<br /><br />
Un exemple de tasca cron que s\'executa cada dos minuts:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='El teu compte IMAP va ser revisat per última vegada el [lastcheck].';
$lang["checkmail_cronjobprob"]='El vostre cronjob de comprovació de correu pot no estar funcionant correctament, ja que ha passat més de 5 minuts des de l\'última execució.<br /><br />
Un exemple de cron job que s\'executa cada minut:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Servidor Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Correu electrònic.';
$lang["checkmail_password"]='Contrasenya';
$lang["checkmail_extension_mapping"]='Tipus de recurs a través de la correspondència d\'extensió de fitxer.';
$lang["checkmail_default_resource_type"]='Tipus de recurs per defecte.';
$lang["checkmail_extension_mapping_desc"]='Després del selector de Tipus de Recurs per defecte, hi ha una entrada per a cada un dels Tipus de Recurs. <br />Per forçar que els fitxers pujats de diferents tipus es converteixin en un Tipus de Recurs específic, afegiu llistes separades per comes d\'extensions de fitxers (ex. jpg, gif, png).';
$lang["checkmail_subject_field"]='Camp d\'assumpte';
$lang["checkmail_body_field"]='Camp del cos.';
$lang["checkmail_purge"]='Eliminar els correus electrònics després de pujar-los?';
$lang["checkmail_confirm"]='Enviar correus electrònics de confirmació?';
$lang["checkmail_users"]='Usuaris permesos';
$lang["checkmail_blocked_users_label"]='Usuaris bloquejats.';
$lang["checkmail_default_access"]='Accés per defecte.';
$lang["checkmail_default_archive"]='Estat per defecte.';
$lang["checkmail_html"]='Permetre contingut HTML? (experimental, no recomanat)';
$lang["checkmail_mail_skipped"]='Correu electrònic saltat.';
$lang["checkmail_allow_users_based_on_permission_label"]='Haurien els usuaris tenir permisos per carregar contingut?';
$lang["addresourcesviaemail"]='Afegir per correu electrònic.';
$lang["uploadviaemail"]='Afegir per correu electrònic.';
$lang["uploadviaemail-intro"]='Per pujar per correu electrònic, adjunteu el(s) vostre(s) fitxer(s) i adreceu el correu electrònic a <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Assegureu-vos d\'enviar-ho des de <b>[fromaddress]</b>, o serà ignorat.</p><p>Tingueu en compte que qualsevol cosa al SUBJECT del correu electrònic anirà al camp [subjectfield] a %applicationname%.</p><p>També tingueu en compte que qualsevol cosa al BODY del correu electrònic anirà al camp [bodyfield] a %applicationname%.</p><p>Múltiples fitxers seran agrupats en una col·lecció. Els vostres recursos per defecte tindran un nivell d\'accés <b>\'[access]\'</b>, i un estat d\'arxiu <b>\'[archive]\'</b>.</p><p>[confirmation]</p>';
$lang["checkmail_confirmation_message"]='Rebràs un correu electrònic de confirmació quan el teu correu electrònic sigui processat amb èxit. Si el teu correu electrònic és saltat programàticament per qualsevol raó (com ara si és enviat des de l\'adreça equivocada), l\'administrador serà notificat que hi ha un correu electrònic que requereix atenció.';
$lang["yourresourcehasbeenuploaded"]='El teu recurs ha estat pujat.';
$lang["yourresourceshavebeenuploaded"]='Els teus recursos han estat pujats.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), amb ID [user-ref] i correu electrònic [user-email], no té permisos per carregar per correu electrònic (comproveu els permisos "c" o "d" o els usuaris bloquejats a la pàgina de configuració de comprovació de correu electrònic). Enregistrat el: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Creat a partir del connector "Comprova el correu".';