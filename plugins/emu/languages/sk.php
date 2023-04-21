<?php


$lang["emu_configuration"]='Konfigurácia EMu.';
$lang["emu_api_settings"]='Nastavitve strežnika API.';
$lang["emu_api_server"]='Adresa servera (napr. http://[adresa.servera])';
$lang["emu_api_server_port"]='Port servera.';
$lang["emu_resource_types"]='Vyberte typy zdrojov spojené s EMu.';
$lang["emu_email_notify"]='E-mailová adresa, na ktorú skript bude posielať upozornenia. Ak chcete použiť predvolenú e-mailovú adresu systému, ponechajte prázdne.';
$lang["emu_script_failure_notify_days"]='Po koliko dneh naj se prikaže opozorilo in pošlje e-pošta, če skript ni dokončan.';
$lang["emu_script_header"]='Povolite skriptu, ki bo samodejno posodabljala EMu podatke, kadar ResourceSpace zažene svojo načrtovano nalogo (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Posledný beh skriptu';
$lang["emu_script_mode"]='Režim skripta.';
$lang["emu_script_mode_option_1"]='Importovať metadáta z EMu.';
$lang["emu_script_mode_option_2"]='Stiahnite všetky záznamy z EMu a udržujte RS a EMu synchronizované.';
$lang["emu_enable_script"]='Povoliť skript EMu.';
$lang["emu_test_mode"]='Testovací režim - Nastavením na hodnotu true se skript spustí, ale neaktualizuje zdroje.';
$lang["emu_interval_run"]='Spustiť skript v nasledujúcom intervale (napr. +1 deň, +2 týždne, dva týždne). Ak chcete, aby sa spustil pri každom behu cron_copy_hitcount.php, ponechajte prázdne.';
$lang["emu_log_directory"]='Adresár pre ukladanie záznamov skriptov. Ak je toto pole prázdne alebo neplatné, záznamy nebudú vytvárané.';
$lang["emu_created_by_script_field"]='Metadatové pole používané na ukladanie informácie, či bol zdroj vytvorený pomocou EMu skriptu.';
$lang["emu_settings_header"]='Nastavitve EMu.';
$lang["emu_irn_field"]='Metadatové pole používané na ukladanie identifikátora EMu (IRN).';
$lang["emu_search_criteria"]='Kritériá vyhľadávania pre synchronizáciu EMu s ResourceSpace.';
$lang["emu_rs_mappings_header"]='Pravidlá mapovania medzi EMu a ResourceSpace.';
$lang["emu_module"]='Modul EMu';
$lang["emu_column_name"]='Stĺpec modulu EMu';
$lang["emu_rs_field"]='Pole ResourceSpace.';
$lang["emu_add_mapping"]='Pridať mapovanie.';
$lang["emu_upload_emu_field_label"]='EMu IRN cannot be translated without additional context. EMu is a collections management software, and IRN is an acronym for "Internal Record Number". If you provide more information about the specific context in which this term is used, I can provide a more accurate translation.';
$lang["emu_confirm_upload_nodata"]='Prosím, zaškrtnite políčko, aby ste potvrdili, že chcete pokračovať s nahrávaním.';
$lang["emu_test_script_title"]='Test/Spusti skript.';
$lang["emu_run_script"]='Spracovanie';
$lang["emu_script_problem"]='UPOZORENIE - EMu skript nije uspješno završen u posljednjih %days% dana. Zadnje vrijeme pokretanja:';
$lang["emu_no_resource"]='Nie je určené ID zdroja!';
$lang["emu_upload_nodata"]='Pre tento IRN sa nenašli žiadne dáta v EMu.';
$lang["emu_nodata_returned"]='Neboli nájdené žiadne EMu dáta pre zadané IRN.';
$lang["emu_createdfromemu"]='Vytvorené z EMU pluginu.';