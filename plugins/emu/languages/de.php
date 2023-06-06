<?php


$lang["emu_configuration"]='EMu-Konfiguration';
$lang["emu_api_settings"]='API-Server-Einstellungen';
$lang["emu_api_server"]='Server-Adresse (z.B. http://[server.address])';
$lang["emu_api_server_port"]='Server-Port';
$lang["emu_resource_types"]='Wählen Sie Ressourcentypen aus, die mit EMu verknüpft sind.';
$lang["emu_email_notify"]='E-Mail-Adresse, an die das Skript Benachrichtigungen senden wird. Lassen Sie das Feld leer, um die Standardbenachrichtigungsadresse des Systems zu verwenden.';
$lang["emu_script_failure_notify_days"]='Anzahl der Tage, nach denen ein Alarm angezeigt und eine E-Mail gesendet werden soll, wenn das Skript nicht abgeschlossen wurde.';
$lang["emu_script_header"]='Aktivieren Sie das Skript, das automatisch die EMu-Daten aktualisiert, wenn ResourceSpace seine geplante Aufgabe (cron_copy_hitcount.php) ausführt.';
$lang["emu_last_run_date"]='Letzte Ausführung des Skripts';
$lang["emu_script_mode"]='Skript-Modus';
$lang["emu_script_mode_option_1"]='Metadaten aus EMu importieren.';
$lang["emu_script_mode_option_2"]='Alle EMu-Datensätze abrufen und RS und EMu synchronisieren.';
$lang["emu_enable_script"]='Aktiviere EMu-Skript.';
$lang["emu_test_mode"]='Testmodus - Wenn auf "true" gesetzt, wird das Skript ausgeführt, aber die Ressourcen werden nicht aktualisiert.';
$lang["emu_interval_run"]='Skript in folgendem Intervall ausführen (z.B. +1 Tag, +2 Wochen, vierzehn Tage). Wenn leer gelassen, wird es jedes Mal ausgeführt, wenn cron_copy_hitcount.php ausgeführt wird.';
$lang["emu_log_directory"]='Verzeichnis zum Speichern von Skript-Logs. Wenn dies leer bleibt oder ungültig ist, werden keine Protokolle erstellt.';
$lang["emu_created_by_script_field"]='Metadatenfeld, das verwendet wird, um zu speichern, ob eine Ressource durch ein EMu-Skript erstellt wurde.';
$lang["emu_settings_header"]='EMu-Einstellungen';
$lang["emu_irn_field"]='Metadatenfeld zur Speicherung der EMu-Kennung (IRN) verwendet.';
$lang["emu_search_criteria"]='Suchkriterien für die Synchronisierung von EMu mit ResourceSpace.';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace Zuordnungsregeln';
$lang["emu_module"]='EMu-Modul';
$lang["emu_column_name"]='EMu-Modulspalte';
$lang["emu_rs_field"]='ResourceSpace-Feld';
$lang["emu_add_mapping"]='Hinzufügen von Zuordnung.';
$lang["emu_confirm_upload_nodata"]='Bitte setzen Sie das Kästchen, um zu bestätigen, dass Sie mit dem Upload fortfahren möchten.';
$lang["emu_test_script_title"]='Test/Skript ausführen';
$lang["emu_run_script"]='Prozess';
$lang["emu_script_problem"]='WARNUNG - Das EMu-Skript wurde in den letzten %days% Tagen nicht erfolgreich abgeschlossen. Letzte Ausführungszeit:';
$lang["emu_no_resource"]='Keine Ressourcen-ID angegeben!';
$lang["emu_upload_nodata"]='Keine EMu-Daten für diese IRN gefunden:';
$lang["emu_nodata_returned"]='Keine EMu-Daten für die angegebene IRN gefunden.';
$lang["emu_createdfromemu"]='Erstellt mit dem EMU-Plugin.';