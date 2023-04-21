<?php


$lang["emu_configuration"]='Konfiguracija EMu-a';
$lang["emu_api_settings"]='Postavke API poslužitelja.';
$lang["emu_api_server"]='Adresa poslužitelja (npr. http://[adresa.poslužitelja])';
$lang["emu_api_server_port"]='Port poslužitelja.';
$lang["emu_resource_types"]='Odaberite vrste resursa povezane s EMu.';
$lang["emu_email_notify"]='Adresa e-pošte na koju će skripta slati obavijesti. Ostavite prazno za zadani sustav adresiranja obavijesti.';
$lang["emu_script_failure_notify_days"]='Broj dana nakon kojih će se prikazati upozorenje i poslati e-mail ako skripta nije dovršena.';
$lang["emu_script_header"]='Omogućite skriptu koja će automatski ažurirati EMu podatke kada ResourceSpace pokrene svoj zakazani zadatak (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Zadnje pokretanje skripte';
$lang["emu_script_mode"]='Način skripte.';
$lang["emu_script_mode_option_1"]='Uvoz metapodataka iz EMu-a.';
$lang["emu_script_mode_option_2"]='Izvucite sve EMu zapise i održavajte sinkronizaciju između RS i EMu.';
$lang["emu_enable_script"]='Omogući EMu skriptu.';
$lang["emu_test_mode"]='Testni način rada - Postavite na "true" i skripta će se izvršiti, ali neće ažurirati resurse.';
$lang["emu_interval_run"]='Pokreni skriptu u sljedećem intervalu (npr. +1 dan, +2 tjedna, dva tjedna). Ostavi prazno i pokrenut će se svaki put kada se cron_copy_hitcount.php pokrene.';
$lang["emu_log_directory"]='Mapa za pohranu zapisa skripti. Ako ostane prazno ili je nevažeće, neće se vršiti nikakvo evidentiranje.';
$lang["emu_created_by_script_field"]='Polje metapodataka koje se koristi za pohranjivanje informacije je li resurs stvoren pomoću EMu skripte.';
$lang["emu_settings_header"]='Postavke EMu-a.';
$lang["emu_irn_field"]='Polje metapodataka koje se koristi za pohranu EMu identifikatora (IRN).';
$lang["emu_search_criteria"]='Kriteriji pretrage za sinkronizaciju EMu-a s ResourceSpaceom.';
$lang["emu_rs_mappings_header"]='Pravila mapiranja EMu - ResourceSpace.';
$lang["emu_module"]='EMu modul';
$lang["emu_column_name"]='EMu modul stupac';
$lang["emu_rs_field"]='Polje ResourceSpace-a';
$lang["emu_add_mapping"]='Dodaj mapiranje.';
$lang["emu_upload_emu_field_label"]='EMu IRN (Internal Registration Number) - EMu IRN (Interni registracijski broj)';
$lang["emu_confirm_upload_nodata"]='Molimo označite okvir kako biste potvrdili da želite nastaviti s prijenosom.';
$lang["emu_test_script_title"]='Testiraj/ Pokreni skriptu.';
$lang["emu_run_script"]='Obrada';
$lang["emu_script_problem"]='UPOZORENJE - EMu skripta nije uspješno završila u posljednjih %days% dana. Vrijeme posljednjeg pokretanja:';
$lang["emu_no_resource"]='Nije naveden ID resursa!';
$lang["emu_upload_nodata"]='Nisu pronađeni EMu podaci za ovaj IRN:';
$lang["emu_nodata_returned"]='Nema EMu podataka pronađenih za određeni IRN.';
$lang["emu_createdfromemu"]='Stvoreno pomoću EMU dodatka.';