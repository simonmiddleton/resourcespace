<?php


$lang["checkmail_configuration"]='Configuration de vérification de courrier électronique.';
$lang["checkmail_install_php_imap_extension"]='Étape 1 : Installer l\'extension php imap.';
$lang["checkmail_cronhelp"]='Ce plugin nécessite une configuration spéciale pour que le système puisse se connecter à un compte e-mail dédié à la réception de fichiers destinés à être téléchargés.<br /><br />Assurez-vous que l\'IMAP est activé sur le compte. Si vous utilisez un compte Gmail, vous pouvez activer l\'IMAP dans Paramètres->POP/IMAP->Activer l\'IMAP.<br /><br />
Lors de la configuration initiale, il peut être utile d\'exécuter manuellement le fichier plugins/checkmail/pages/cron_check_email.php en ligne de commande pour comprendre son fonctionnement.
Une fois que vous êtes connecté correctement et que vous comprenez comment le script fonctionne, vous devez configurer une tâche cron pour l\'exécuter toutes les minutes ou toutes les deux minutes.<br />Il va scanner la boîte aux lettres et lire un e-mail non lu par exécution.<br /><br />
Voici un exemple de tâche cron qui s\'exécute toutes les deux minutes :<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Votre compte IMAP a été vérifié pour la dernière fois le [lastcheck].';
$lang["checkmail_cronjobprob"]='Votre tâche cron checkmail pourrait ne pas fonctionner correctement, car cela fait plus de 5 minutes depuis sa dernière exécution.<br /><br />
Un exemple de tâche cron qui s\'exécute toutes les minutes:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Serveur Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Email (Courriel)';
$lang["checkmail_password"]='Mot de passe.';
$lang["checkmail_extension_mapping"]='Type de ressource via la correspondance d\'extension de fichier.';
$lang["checkmail_default_resource_type"]='Type de ressource par défaut.';
$lang["checkmail_extension_mapping_desc"]='Après le sélecteur de type de ressource par défaut, il y a une entrée ci-dessous pour chacun de vos types de ressources. <br />Pour forcer les fichiers téléchargés de différents types dans un type de ressource spécifique, ajoutez des listes de extensions de fichiers séparées par des virgules (ex. jpg, gif, png).';
$lang["checkmail_subject_field"]='Champ Sujet';
$lang["checkmail_body_field"]='Champ de corps.';
$lang["checkmail_purge"]='Supprimer les e-mails après téléchargement ?';
$lang["checkmail_confirm"]='Envoyer des e-mails de confirmation ?';
$lang["checkmail_users"]='Utilisateurs autorisés';
$lang["checkmail_blocked_users_label"]='Utilisateurs bloqués.';
$lang["checkmail_default_access"]='Accès par défaut';
$lang["checkmail_default_archive"]='Statut par défaut.';
$lang["checkmail_html"]='Autoriser le contenu HTML ? (expérimental, non recommandé)';
$lang["checkmail_mail_skipped"]='E-mail ignoré.';
$lang["checkmail_allow_users_based_on_permission_label"]='Les utilisateurs devraient-ils être autorisés à télécharger en fonction de leurs permissions ?';
$lang["addresourcesviaemail"]='Ajouter par e-mail.';
$lang["uploadviaemail"]='Ajouter par e-mail.';
$lang["uploadviaemail-intro"]='Pour télécharger par e-mail, attachez votre ou vos fichier(s) et envoyez-le(s) à l\'adresse e-mail <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Assurez-vous de l\'envoyer depuis <b>[fromaddress]</b>, sinon il sera ignoré.</p><p>Notez que tout ce qui est dans l\'OBJET de l\'e-mail sera placé dans le champ [subjectfield] de %applicationname%. </p><p> Notez également que tout ce qui est dans le CORPS de l\'e-mail sera placé dans le champ [bodyfield] de %applicationname%. </p>  <p>Plusieurs fichiers seront regroupés dans une collection. Vos ressources auront un niveau d\'accès par défaut de <b>\'[access]\'</b>, et un statut d\'archivage de <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Vous recevrez un e-mail de confirmation lorsque votre e-mail sera traité avec succès. Si votre e-mail est ignoré pour une raison quelconque (par exemple, s\'il est envoyé depuis une adresse incorrecte), l\'administrateur sera informé qu\'un e-mail nécessite une attention particulière.';
$lang["yourresourcehasbeenuploaded"]='Votre ressource a été téléchargée.';
$lang["yourresourceshavebeenuploaded"]='Vos ressources ont été téléchargées.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), avec l\'ID [user-ref] et l\'e-mail [user-email], n\'est pas autorisé à télécharger par e-mail (vérifiez les autorisations "c" ou "d" ou les utilisateurs bloqués dans la page de configuration de vérification des e-mails). Enregistré le : [datetime].';
$lang["checkmail_createdfromcheckmail"]='Créé à partir du plugin Check Mail.';