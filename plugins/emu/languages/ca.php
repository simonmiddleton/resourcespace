<?php


$lang["emu_configuration"]='Configuració d\'EMu';
$lang["emu_api_settings"]='Configuració del servidor API.';
$lang["emu_api_server"]='Adreça del servidor (per exemple, http://[adreça.del.servidor])';
$lang["emu_api_server_port"]='Port del servidor.';
$lang["emu_resource_types"]='Seleccione els tipus de recursos vinculats a EMu.';
$lang["emu_email_notify"]='Adreça de correu electrònic a la qual el script enviarà les notificacions. Deixeu-ho en blanc per defecte a l\'adreça de notificació del sistema.';
$lang["emu_script_failure_notify_days"]='Nombre de dies després dels quals mostrar una alerta i enviar un correu electrònic si l\'script no s\'ha completat.';
$lang["emu_script_header"]='Habilitar l\'script que actualitzarà automàticament les dades d\'EMu sempre que ResourceSpace executi la seva tasca programada (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Última execució del script';
$lang["emu_script_mode"]='Mode d\'script.';
$lang["emu_script_mode_option_1"]='Importar metadades des de EMu.';
$lang["emu_script_mode_option_2"]='Recupera tots els registres d\'EMu i manté RS i EMu sincronitzats.';
$lang["emu_enable_script"]='Habilitar l\'script EMu.';
$lang["emu_test_mode"]='Mode de prova - Si es configura com a cert, l\'script s\'executarà però no actualitzarà els recursos.';
$lang["emu_interval_run"]='Executar l\'script en l\'interval següent (per exemple, +1 dia, +2 setmanes, quinzenal). Deixeu-ho en blanc i s\'executarà cada vegada que s\'executi cron_copy_hitcount.php.';
$lang["emu_log_directory"]='Directori per emmagatzemar els registres dels scripts. Si això es deixa en blanc o és invàlid, no es produirà cap registre.';
$lang["emu_created_by_script_field"]='Camp de metadades utilitzat per emmagatzemar si un recurs ha estat creat per un script EMu.';
$lang["emu_settings_header"]='Configuració d\'EMu';
$lang["emu_irn_field"]='Camp de metadades utilitzat per emmagatzemar l\'identificador EMu (IRN).';
$lang["emu_search_criteria"]='Criteris de cerca per sincronitzar EMu amb ResourceSpace.';
$lang["emu_rs_mappings_header"]='Regles de mapeig EMu - ResourceSpace.';
$lang["emu_module"]='Mòdul EMu.';
$lang["emu_column_name"]='Columna del mòdul EMu.';
$lang["emu_rs_field"]='Camp de ResourceSpace';
$lang["emu_add_mapping"]='Afegir mapeig.';
$lang["emu_confirm_upload_nodata"]='Si us plau, marqueu la casella per confirmar que voleu continuar amb la càrrega.';
$lang["emu_test_script_title"]='Prova / Executa l\'script.';
$lang["emu_run_script"]='Procés';
$lang["emu_script_problem"]='ATENCIÓ - L\'script EMu no s\'ha completat amb èxit en els últims %dies% dies. Última hora d\'execució:';
$lang["emu_no_resource"]='No s\'ha especificat cap ID de recurs!';
$lang["emu_upload_nodata"]='No s\'ha trobat cap dada EMu per a aquest IRN:';
$lang["emu_nodata_returned"]='No s\'ha trobat cap dada EMu per a l\'IRN especificat.';
$lang["emu_createdfromemu"]='Creat per al connector EMU.';