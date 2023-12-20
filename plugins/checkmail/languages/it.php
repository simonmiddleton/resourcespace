<?php


$lang["checkmail_configuration"]='Configurazione Checkmail';
$lang["checkmail_install_php_imap_extension"]='Passo uno: Installare l\'estensione php imap.';
$lang["checkmail_cronhelp"]='Questo plugin richiede una configurazione speciale per il sistema per accedere ad un account e-mail dedicato alla ricezione di file destinati all\'upload.<br /><br />Assicurati che IMAP sia abilitato sull\'account. Se stai utilizzando un account Gmail, abilita IMAP in Impostazioni->POP/IMAP->Abilita IMAP<br /><br />
Durante la configurazione iniziale, potrebbe essere utile eseguire manualmente il file plugins/checkmail/pages/cron_check_email.php da riga di comando per capire come funziona.
Una volta che ti sei connesso correttamente e hai capito come funziona lo script, devi impostare un lavoro cron per eseguirlo ogni minuto o due.<br />Esso scannerizzerà la casella di posta elettronica e leggerà una e-mail non letta per ogni esecuzione.<br /><br />
Esempio di lavoro cron che viene eseguito ogni due minuti:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Il tuo account IMAP è stato controllato l\'ultima volta il [lastcheck].';
$lang["checkmail_cronjobprob"]='Il tuo cronjob di controllo della posta potrebbe non essere in esecuzione correttamente, poiché sono passati più di 5 minuti dall\'ultima esecuzione.<br /><br />
Esempio di cron job che viene eseguito ogni minuto:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Server Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Email';
$lang["checkmail_password"]='Password';
$lang["checkmail_extension_mapping"]='Tipo di risorsa tramite mappatura dell\'estensione del file.';
$lang["checkmail_default_resource_type"]='Tipo di risorsa predefinito.';
$lang["checkmail_extension_mapping_desc"]='Dopo il selettore del tipo di risorsa predefinito, c\'è un campo di input per ciascuno dei tuoi tipi di risorsa. <br />Per forzare i file caricati di tipi diversi in un tipo di risorsa specifico, aggiungi elenchi di estensioni di file separati da virgola (es. jpg, gif, png).';
$lang["checkmail_resource_type_population"]='<br />(da allowed_extensions)';
$lang["checkmail_subject_field"]='Campo Oggetto';
$lang["checkmail_body_field"]='Campo del corpo.';
$lang["checkmail_purge"]='Eliminare le e-mail dopo il caricamento?';
$lang["checkmail_confirm"]='Inviare e-mail di conferma?';
$lang["checkmail_users"]='Utenti autorizzati';
$lang["checkmail_blocked_users_label"]='Utenti bloccati.';
$lang["checkmail_default_access"]='Accesso predefinito.';
$lang["checkmail_default_archive"]='Stato predefinito.';
$lang["checkmail_html"]='Consentire contenuto HTML? (sperimentale, non consigliato)';
$lang["checkmail_mail_skipped"]='E-mail saltato.';
$lang["checkmail_allow_users_based_on_permission_label"]='Gli utenti dovrebbero essere autorizzati in base ai permessi a caricare?';
$lang["addresourcesviaemail"]='Aggiungi tramite E-mail.';
$lang["uploadviaemail"]='Aggiungi tramite E-mail.';
$lang["uploadviaemail-intro"]='Per caricare tramite e-mail, allegare il/i tuo/i file e indirizzare l\'e-mail a <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Assicurati di inviarlo da <b>[fromaddress]</b>, altrimenti verrà ignorato.</p><p>Nota che qualsiasi cosa nell\'OGGETTO dell\'e-mail andrà nel campo [subjectfield] in %applicationname%. </p><p> Nota anche che qualsiasi cosa nel CORPO dell\'e-mail andrà nel campo [bodyfield] in %applicationname%. </p>  <p>Più file saranno raggruppati in una collezione. Le tue risorse avranno come predefinito un livello di accesso <b>\'[access]\'</b>, e uno stato di archivio <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Riceverai una e-mail di conferma quando la tua e-mail verrà elaborata con successo. Se la tua e-mail viene saltata per qualsiasi motivo tramite programmazione (ad esempio se viene inviata dall\'indirizzo sbagliato), l\'amministratore verrà informato che c\'è una e-mail che richiede attenzione.';
$lang["yourresourcehasbeenuploaded"]='La tua risorsa è stata caricata.';
$lang["yourresourceshavebeenuploaded"]='Le tue risorse sono state caricate.';
$lang["checkmail_not_allowed_error_template"]='L\'utente [user-fullname] ([username]), con ID [user-ref] e e-mail [user-email], non è autorizzato a caricare tramite e-mail (verificare le autorizzazioni "c" o "d" o gli utenti bloccati nella pagina di configurazione di checkmail). Registrato il: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Creato dal plugin Check Mail.';