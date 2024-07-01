<?php


$lang["csv_upload_nav_link"]='Téléchargement CSV';
$lang["csv_upload_intro"]='Ce plugin vous permet de créer ou de mettre à jour des ressources en téléchargeant un fichier CSV. Le format du CSV est important';
$lang["csv_upload_condition1"]='Assurez-vous que le fichier CSV est encodé en utilisant <b>UTF-8 sans BOM</b>.';
$lang["csv_upload_condition2"]='Le CSV doit avoir une ligne d\'en-tête';
$lang["csv_upload_condition3"]='Pour pouvoir télécharger des fichiers de ressources ultérieurement en utilisant la fonctionnalité de remplacement par lots, il doit y avoir une colonne nommée "Nom de fichier d\'origine" et chaque fichier doit avoir un nom de fichier unique';
$lang["csv_upload_condition4"]='Tous les champs obligatoires pour toutes les ressources nouvellement créées doivent être présents dans le CSV';
$lang["csv_upload_condition5"]='Pour les colonnes qui contiennent des valeurs avec des <b>virgules (,)</b>, assurez-vous de les formater en tant que type <b>texte</b> afin de ne pas avoir à ajouter des guillemets (""). Lors de l\'enregistrement en tant que fichier CSV, assurez-vous de cocher l\'option de citation des cellules de type texte.';
$lang["csv_upload_condition6"]='Vous pouvez télécharger un exemple de fichier CSV en cliquant sur <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Pour mettre à jour les données d\'une ressource existante, vous pouvez télécharger un fichier CSV contenant les métadonnées existantes en cliquant sur l\'option "Export CSV - métadonnées" dans le menu des actions de la collection ou des résultats de recherche.';
$lang["csv_upload_condition8"]='Vous pouvez réutiliser un fichier de mappage CSV précédemment configuré en cliquant sur "Télécharger le fichier de configuration CSV"';
$lang["csv_upload_error_no_permission"]='Vous n\'avez pas les permissions nécessaires pour télécharger un fichier CSV';
$lang["check_line_count"]='Au moins deux lignes trouvées dans le fichier CSV';
$lang["csv_upload_file"]='Sélectionner le fichier CSV';
$lang["csv_upload_default"]='Par défaut';
$lang["csv_upload_error_no_header"]='Aucune ligne d\'en-tête trouvée dans le fichier';
$lang["csv_upload_update_existing"]='Mettre à jour les ressources existantes ? Si cette option n\'est pas cochée, de nouvelles ressources seront créées en fonction des données CSV';
$lang["csv_upload_update_existing_collection"]='Mettre à jour uniquement les ressources dans une collection spécifique ?';
$lang["csv_upload_process"]='Processus';
$lang["csv_upload_add_to_collection"]='Ajouter les ressources nouvellement créées à la collection actuelle ?';
$lang["csv_upload_step1"]='Étape 1 - Sélectionner le fichier';
$lang["csv_upload_step2"]='Étape 2 - Options de ressource par défaut';
$lang["csv_upload_step3"]='Étape 3 - Associer les colonnes aux champs de métadonnées';
$lang["csv_upload_step4"]='Étape 4 - Vérification des données CSV';
$lang["csv_upload_step5"]='Étape 5 - Traitement du CSV';
$lang["csv_upload_update_existing_title"]='Mettre à jour les ressources existantes';
$lang["csv_upload_update_existing_notes"]='Sélectionnez les options requises pour mettre à jour les ressources existantes';
$lang["csv_upload_create_new_title"]='Créer de nouvelles ressources';
$lang["csv_upload_create_new_notes"]='Sélectionnez les options requises pour créer de nouvelles ressources';
$lang["csv_upload_map_fields_notes"]='Faites correspondre les colonnes du CSV aux champs de métadonnées requis. Cliquer sur "Suivant" vérifiera le CSV sans modifier les données réellement';
$lang["csv_upload_map_fields_auto_notes"]='Les champs de métadonnées ont été pré-sélectionnés en fonction des noms ou des titres, mais veuillez vérifier que ceux-ci sont corrects';
$lang["csv_upload_workflow_column"]='Sélectionnez la colonne qui contient l\'identifiant d\'état de flux de travail';
$lang["csv_upload_workflow_default"]='État de flux de travail par défaut si aucune colonne n\'est sélectionnée ou si aucun état valide n\'est trouvé dans la colonne';
$lang["csv_upload_access_column"]='Sélectionnez la colonne qui contient le niveau d\'accès (0=Ouvert, 1=Restreint, 2=Confidentiel)';
$lang["csv_upload_access_default"]='Niveau d\'accès par défaut si aucune colonne n\'est sélectionnée ou si aucun accès valide n\'est trouvé dans la colonne';
$lang["csv_upload_resource_type_column"]='Sélectionnez la colonne qui contient l\'identifiant du type de ressource';
$lang["csv_upload_resource_type_default"]='Type de ressource par défaut si aucune colonne n\'est sélectionnée ou si aucun type valide n\'est trouvé dans la colonne';
$lang["csv_upload_resource_match_column"]='Sélectionnez la colonne qui contient l\'identifiant de la ressource';
$lang["csv_upload_match_type"]='Correspondance de ressource basée sur l\'ID de ressource ou la valeur de champ de métadonnées ?';
$lang["csv_upload_multiple_match_action"]='Action à prendre si plusieurs ressources correspondantes sont trouvées';
$lang["csv_upload_validation_notes"]='Veuillez vérifier les messages de validation ci-dessous avant de continuer. Cliquez sur "Processus" pour enregistrer les modifications';
$lang["csv_upload_upload_another"]='Télécharger un autre fichier CSV';
$lang["csv_upload_mapping config"]='Paramètres de mappage de colonnes CSV';
$lang["csv_upload_download_config"]='Télécharger les paramètres de mappage CSV en tant que fichier';
$lang["csv_upload_upload_config"]='Importer le fichier de mappage CSV';
$lang["csv_upload_upload_config_question"]='Télécharger le fichier de mappage CSV ? Utilisez ceci si vous avez déjà téléchargé un fichier CSV similaire auparavant et avez sauvegardé la configuration';
$lang["csv_upload_upload_config_set"]='Ensemble de configuration CSV';
$lang["csv_upload_upload_config_clear"]='Effacer la configuration de mappage CSV';
$lang["csv_upload_mapping_ignore"]='NE PAS UTILISER';
$lang["csv_upload_mapping_header"]='En-tête de colonne';
$lang["csv_upload_mapping_csv_data"]='Données d\'exemple à partir d\'un fichier CSV';
$lang["csv_upload_using_config"]='Utilisation de la configuration CSV existante';
$lang["csv_upload_process_offline"]='Traiter le fichier CSV hors ligne ? Ceci devrait être utilisé pour les fichiers CSV volumineux. Vous serez notifié(e) via un message ResourceSpace une fois que le traitement sera terminé';
$lang["csv_upload_oj_created"]='Tâche de téléchargement CSV créée avec l\'ID de tâche # %%JOBREF%%. <br/>Vous recevrez un message système de ResourceSpace une fois que la tâche sera terminée';
$lang["csv_upload_oj_complete"]='Tâche d\'importation CSV terminée. Cliquez sur le lien pour afficher le fichier journal complet';
$lang["csv_upload_oj_failed"]='Le téléchargement du fichier CSV a échoué';
$lang["csv_upload_processing_x_meta_columns"]='Traitement de %count colonnes de métadonnées';
$lang["csv_upload_processing_complete"]='Traitement terminé à [time] (%%HOURS%% heures, %%MINUTES%% minutes, %%SECONDS%% secondes)';
$lang["csv_upload_error_in_progress"]='Traitement annulé - ce fichier CSV est déjà en cours de traitement';
$lang["csv_upload_error_file_missing"]='Erreur - Fichier CSV manquant : %%FILE%%';
$lang["csv_upload_full_messages_link"]='Affichage des 1000 premières lignes seulement, pour télécharger le fichier journal complet, veuillez cliquer <a href=\'%%LOG_URL%%\' target=\'_blank\'>ici</a>';
$lang["csv_upload_ignore_errors"]='Ignorer les erreurs et traiter le fichier quand même';
$lang["csv_upload_process_offline_quick"]='Ignorer la validation et traiter le fichier CSV hors ligne ? Ceci ne doit être utilisé que pour les fichiers CSV volumineux une fois que les tests sur des fichiers plus petits ont été effectués. Vous serez informé(e) via un message ResourceSpace une fois que le téléchargement sera terminé';
$lang["csv_upload_force_offline"]='Ce grand fichier CSV peut prendre beaucoup de temps à être traité, il sera donc exécuté hors ligne. Vous serez informé(e) via un message ResourceSpace une fois que le traitement sera terminé';
$lang["csv_upload_recommend_offline"]='Ce grand fichier CSV peut prendre beaucoup de temps à traiter. Il est recommandé d\'activer les tâches hors ligne si vous avez besoin de traiter des fichiers CSV volumineux';
$lang["csv_upload_createdfromcsvupload"]='Créé à partir du plugin de téléchargement CSV';