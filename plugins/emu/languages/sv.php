<?php


$lang["emu_configuration"]='EMu Konfiguration';
$lang["emu_api_settings"]='API-serverinställningar';
$lang["emu_api_server"]='Serveradress (t.ex. http://[server.address])';
$lang["emu_api_server_port"]='Serverport.';
$lang["emu_resource_types"]='Välj resurstyper som är länkade till EMu.';
$lang["emu_email_notify"]='E-postadress som skriptet kommer att skicka meddelanden till. Lämna tomt för att använda systemets standardmeddelandeadress.';
$lang["emu_script_failure_notify_days"]='Antal dagar efter vilka en varning ska visas och ett e-postmeddelande ska skickas om skriptet inte har slutförts.';
$lang["emu_script_header"]='Aktivera skriptet som automatiskt uppdaterar EMu-data varje gång ResourceSpace kör sin schemalagda uppgift (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Senaste körning av skript';
$lang["emu_script_mode"]='Skriptläge';
$lang["emu_script_mode_option_1"]='Importera metadata från EMu.';
$lang["emu_script_mode_option_2"]='Hämta alla EMu-poster och håll RS och EMu synkroniserade.';
$lang["emu_enable_script"]='Aktivera EMu-skript.';
$lang["emu_test_mode"]='Testläge - Sätt till sant och skriptet kommer att köras men inte uppdatera resurser.';
$lang["emu_interval_run"]='Kör skriptet med följande intervall (t.ex. +1 dag, +2 veckor, fjorton dagar). Lämna tomt så körs det varje gång cron_copy_hitcount.php körs.';
$lang["emu_log_directory"]='Katalog för att lagra skriptloggar. Om detta lämnas tomt eller är ogiltigt kommer ingen loggning att ske.';
$lang["emu_created_by_script_field"]='Metadatafält som används för att lagra om en resurs har skapats av EMu-skript.';
$lang["emu_settings_header"]='EMu inställningar';
$lang["emu_irn_field"]='Metadatafält som används för att lagra EMu-identifieraren (IRN).';
$lang["emu_search_criteria"]='Sök kriterier för synkronisering av EMu med ResourceSpace.';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace kartläggningsregler';
$lang["emu_module"]='EMu-modul';
$lang["emu_column_name"]='EMu modul kolumn';
$lang["emu_rs_field"]='ResourceSpace-fält';
$lang["emu_add_mapping"]='Lägg till kartläggning.';
$lang["emu_upload_emu_field_label"]='EMu IRN can be translated to EMu IRN (EMu Identifier Record Number) as it is an acronym and does not require translation. EMu is a collections management software used by museums, archives, and other cultural institutions to manage their collections, and IRN is a unique identifier assigned to each record in the system.';
$lang["emu_confirm_upload_nodata"]='Vänligen markera rutan för att bekräfta att du vill fortsätta med uppladdningen.';
$lang["emu_test_script_title"]='Testa/ Kör skript.';
$lang["emu_run_script"]='Bearbeta.';
$lang["emu_script_problem"]='VARNING - EMu-skriptet har inte slutförts framgångsrikt inom de senaste %dagar% dagarna. Senaste körningstid:';
$lang["emu_no_resource"]='Ingen resurs-ID angiven!';
$lang["emu_upload_nodata"]='Ingen EMu-data hittades för detta IRN:';
$lang["emu_nodata_returned"]='Ingen EMu-data hittades för det angivna IRN-numret.';
$lang["emu_createdfromemu"]='Skapad från EMU-tillägg.';