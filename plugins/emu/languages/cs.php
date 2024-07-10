<?php


$lang["emu_configuration"]='Konfigurace EMu';
$lang["emu_api_settings"]='Nastavení serveru API';
$lang["emu_api_server"]='Adresa serveru (např. http://[server.address])';
$lang["emu_api_server_port"]='Port serveru';
$lang["emu_resource_types"]='Vyberte typy zdrojů propojené s EMu';
$lang["emu_email_notify"]='E-mailová adresa, na kterou skript bude odesílat oznámení. Nechte prázdné pro výchozí systémovou adresu pro oznámení';
$lang["emu_script_failure_notify_days"]='Počet dní, po kterých se zobrazí upozornění a odešle e-mail, pokud skript nebyl dokončen';
$lang["emu_script_header"]='Povolit skript, který automaticky aktualizuje data EMu, kdykoli ResourceSpace spustí svůj naplánovaný úkol (cron_copy_hitcount.php)';
$lang["emu_last_run_date"]='Skript naposledy spuštěn';
$lang["emu_script_mode"]='Režim skriptu';
$lang["emu_script_mode_option_1"]='Importovat metadata z EMu';
$lang["emu_script_mode_option_2"]='Stáhněte všechny záznamy EMu a udržujte RS a EMu synchronizované';
$lang["emu_enable_script"]='Povolit skript EMu';
$lang["emu_test_mode"]='Testovací režim - Nastavte na pravda a skript poběží, ale neaktualizuje zdroje';
$lang["emu_interval_run"]='Spusťte skript v následujícím intervalu (např. +1 den, +2 týdny, čtrnáct dní). Nechte prázdné a bude se spouštět pokaždé, když se spustí cron_copy_hitcount.php)';
$lang["emu_log_directory"]='Adresář pro ukládání protokolů skriptů. Pokud je toto pole prázdné nebo neplatné, nebude probíhat žádné protokolování.';
$lang["emu_created_by_script_field"]='Pole metadat používané k uložení informace, zda byl zdroj vytvořen skriptem EMu';
$lang["emu_settings_header"]='Nastavení EMu';
$lang["emu_irn_field"]='Pole metadat používané k uložení identifikátoru EMu (IRN)';
$lang["emu_search_criteria"]='Kritéria vyhledávání pro synchronizaci EMu s ResourceSpace';
$lang["emu_rs_mappings_header"]='EMu - pravidla mapování ResourceSpace';
$lang["emu_module"]='Modul EMu';
$lang["emu_column_name"]='Sloupec modulu EMu';
$lang["emu_rs_field"]='Pole ResourceSpace';
$lang["emu_add_mapping"]='Přidat mapování';
$lang["emu_confirm_upload_nodata"]='Zaškrtněte políčko, abyste potvrdili, že chcete pokračovat v nahrávání';
$lang["emu_test_script_title"]='Test/ Spustit skript';
$lang["emu_run_script"]='Proces';
$lang["emu_script_problem"]='VAROVÁNÍ - skript EMu nebyl úspěšně dokončen za posledních %days% dní. Poslední čas spuštění:';
$lang["emu_no_resource"]='Není zadáno ID zdroje!';
$lang["emu_upload_nodata"]='Nebyly nalezeny žádné údaje EMu pro toto IRN:';
$lang["emu_nodata_returned"]='Nebyla nalezena žádná data EMu pro zadané IRN.';
$lang["emu_createdfromemu"]='Vytvořeno z pluginu EMU';