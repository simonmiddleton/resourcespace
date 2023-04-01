<?php


$lang["emu_configuration"]='Konfiguracja EMu';
$lang["emu_api_settings"]='Ustawienia serwera API.';
$lang["emu_api_server"]='Adres serwera (np. http://[adres.serwera])';
$lang["emu_api_server_port"]='Port serwera';
$lang["emu_resource_types"]='Wybierz typy zasobów powiązane z EMu.';
$lang["emu_email_notify"]='Adres e-mail, na który skrypt będzie wysyłał powiadomienia. Pozostaw puste, aby użyć domyślnego adresu powiadomień systemowych.';
$lang["emu_script_failure_notify_days"]='Liczba dni po których wyświetlić alert i wysłać e-mail, jeśli skrypt nie został ukończony.';
$lang["emu_script_header"]='Włącz skrypt, który automatycznie zaktualizuje dane EMu za każdym razem, gdy ResourceSpace uruchomi zaplanowane zadanie (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='<div class="Question">
	<label>
		<strong>Ostatnie uruchomienie skryptu</strong>
	</label>
	<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
	%scripts_test_functionality%
</div>
<div class="clearerleft"></div>';
$lang["emu_script_mode"]='Tryb skryptu.';
$lang["emu_script_mode_option_1"]='Zaimportuj metadane z EMu.';
$lang["emu_script_mode_option_2"]='Pobierz wszystkie rekordy EMu i utrzymaj synchronizację między RS i EMu.';
$lang["emu_enable_script"]='Włącz skrypt EMu.';
$lang["emu_test_mode"]='Tryb testowy - Ustaw na wartość "true", a skrypt będzie działał, ale nie będzie aktualizował zasobów.';
$lang["emu_interval_run"]='Uruchom skrypt w następujących odstępach czasu (np. +1 dzień, +2 tygodnie, dwa tygodnie). Pozostaw puste, a skrypt zostanie uruchomiony za każdym razem, gdy zostanie uruchomiony cron_copy_hitcount.php.';
$lang["emu_log_directory"]='Katalog do przechowywania logów skryptów. Jeśli zostanie to pozostawione puste lub będzie nieprawidłowe, to nie będzie prowadzone żadne logowanie.';
$lang["emu_created_by_script_field"]='Pole metadanych używane do przechowywania informacji, czy zasób został utworzony przez skrypt EMu.';
$lang["emu_settings_header"]='Ustawienia EMu';
$lang["emu_irn_field"]='Pole metadanych używane do przechowywania identyfikatora EMu (IRN).';
$lang["emu_search_criteria"]='Kryteria wyszukiwania dla synchronizacji EMu z ResourceSpace.';
$lang["emu_rs_mappings_header"]='Zasady mapowania między EMu a ResourceSpace.';
$lang["emu_module"]='Moduł EMu';
$lang["emu_column_name"]='Kolumna modułu EMu';
$lang["emu_rs_field"]='Pole ResourceSpace';
$lang["emu_add_mapping"]='Dodaj mapowanie.';
$lang["emu_upload_emu_field_label"]='EMu IRN should not be translated as it is an acronym and technical term specific to the EMu collections management software.';
$lang["emu_confirm_upload_nodata"]='Proszę zaznaczyć pole wyboru, aby potwierdzić, że chcesz kontynuować przesyłanie pliku.';
$lang["emu_test_script_title"]='Testuj/Uruchom skrypt.';
$lang["emu_run_script"]='Proces';
$lang["emu_script_problem"]='OSTRZEŻENIE - skrypt EMu nie został pomyślnie ukończony w ciągu ostatnich %days% dni. Ostatni czas uruchomienia:';
$lang["emu_no_resource"]='Nie określono identyfikatora zasobu!';
$lang["emu_upload_nodata"]='Nie znaleziono danych EMu dla tego IRN:';
$lang["emu_nodata_returned"]='Nie znaleziono danych EMu dla określonego numeru identyfikacyjnego IRN.';
$lang["emu_createdfromemu"]='Utworzono z wtyczki EMU.';