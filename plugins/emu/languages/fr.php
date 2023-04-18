<?php


$lang["emu_configuration"]='Configuration EMu';
$lang["emu_api_settings"]='Paramètres du serveur API.';
$lang["emu_api_server"]='Adresse du serveur (par exemple http://[adresse.du.serveur])';
$lang["emu_api_server_port"]='Port du serveur';
$lang["emu_resource_types"]='Sélectionnez les types de ressources liés à EMu.';
$lang["emu_email_notify"]='Adresse e-mail à laquelle le script enverra les notifications. Laissez vide pour utiliser l\'adresse de notification système par défaut.';
$lang["emu_script_failure_notify_days"]='Nombre de jours après lesquels afficher une alerte et envoyer un e-mail si le script n\'a pas été complété.';
$lang["emu_script_header"]='Activer le script qui mettra automatiquement à jour les données EMu chaque fois que ResourceSpace exécute sa tâche planifiée (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Dernière exécution du script';
$lang["emu_script_mode"]='Mode script.';
$lang["emu_script_mode_option_1"]='Importer les métadonnées depuis EMu.';
$lang["emu_script_mode_option_2"]='Extraire tous les enregistrements EMu et maintenir la synchronisation entre RS et EMu.';
$lang["emu_enable_script"]='Activer le script EMu.';
$lang["emu_test_mode"]='Mode de test - Si défini sur vrai, le script s\'exécutera mais ne mettra pas à jour les ressources.';
$lang["emu_interval_run"]='Exécuter le script à l\'intervalle suivant (par exemple, +1 jour, +2 semaines, quinzaine). Laissez vide et il s\'exécutera à chaque fois que cron_copy_hitcount.php s\'exécute.';
$lang["emu_log_directory"]='Répertoire pour stocker les journaux de script. Si cela est laissé vide ou est invalide, aucun enregistrement ne sera effectué.';
$lang["emu_created_by_script_field"]='Champ de métadonnées utilisé pour stocker si une ressource a été créée par un script EMu.';
$lang["emu_settings_header"]='Paramètres EMu';
$lang["emu_irn_field"]='Champ de métadonnées utilisé pour stocker l\'identifiant EMu (IRN).';
$lang["emu_search_criteria"]='Critères de recherche pour synchroniser EMu avec ResourceSpace.';
$lang["emu_rs_mappings_header"]='Règles de mappage EMu - ResourceSpace.';
$lang["emu_module"]='Module EMu.';
$lang["emu_column_name"]='Colonne du module EMu.';
$lang["emu_rs_field"]='Champ ResourceSpace';
$lang["emu_add_mapping"]='Ajouter une correspondance.';
$lang["emu_upload_emu_field_label"]='Please provide more context or information about the term "EMu IRN" so that I can provide an accurate translation.';
$lang["emu_confirm_upload_nodata"]='Veuillez cocher la case pour confirmer que vous souhaitez poursuivre le téléchargement.';
$lang["emu_test_script_title"]='Tester/Exécuter le script';
$lang["emu_run_script"]='Veuillez traduire : Processus.';
$lang["emu_script_problem"]='AVERTISSEMENT - le script EMu n\'a pas été exécuté avec succès au cours des %jours% derniers jours. Dernière heure d\'exécution :';
$lang["emu_no_resource"]='Identifiant de ressource non spécifié !';
$lang["emu_upload_nodata"]='Aucune donnée EMu trouvée pour cet IRN :';
$lang["emu_nodata_returned"]='Aucune donnée EMu trouvée pour l\'IRN spécifié.';
$lang["emu_createdfromemu"]='Créé à partir du plugin EMU.';