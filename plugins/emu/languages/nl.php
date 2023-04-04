<?php


$lang["emu_configuration"]='EMu-configuratie';
$lang["emu_api_settings"]='API server instellingen.';
$lang["emu_api_server"]='Serveradres (bijv. http://[server.adres])';
$lang["emu_api_server_port"]='Server poort';
$lang["emu_resource_types"]='Selecteer bron types die gekoppeld zijn aan EMu.';
$lang["emu_email_notify"]='E-mailadres waarheen het script meldingen zal sturen. Laat leeg om het standaardadres voor systeemmeldingen te gebruiken.';
$lang["emu_script_failure_notify_days"]='Aantal dagen na welke een waarschuwing moet worden weergegeven en een e-mail moet worden verzonden als het script niet is voltooid.';
$lang["emu_script_header"]='Activeer het script dat automatisch de EMu-gegevens bijwerkt telkens wanneer ResourceSpace zijn geplande taak (cron_copy_hitcount.php) uitvoert.';
$lang["emu_last_run_date"]='<div class="Question">
		<label>
			<strong>Laatste uitvoering script</strong>
		</label>
		<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
		%scripts_test_functionality%
	</div>
	<div class="clearerleft"></div>';
$lang["emu_script_mode"]='Scriptmodus';
$lang["emu_script_mode_option_1"]='Importeer metadata vanuit EMu.';
$lang["emu_script_mode_option_2"]='Haal alle EMu-records op en houd RS en EMu gesynchroniseerd.';
$lang["emu_enable_script"]='Inschakelen van EMu-script.';
$lang["emu_test_mode"]='Testmodus - Als deze op \'waar\' staat, zal het script worden uitgevoerd maar worden er geen resources bijgewerkt.';
$lang["emu_interval_run"]='Voer script uit op het volgende interval (bijv. +1 dag, +2 weken, veertien dagen). Laat leeg en het zal elke keer worden uitgevoerd wanneer cron_copy_hitcount.php wordt uitgevoerd.';
$lang["emu_log_directory"]='Map om scriptlogs op te slaan. Als dit leeg blijft of ongeldig is, wordt er geen logging uitgevoerd.';
$lang["emu_created_by_script_field"]='Metadataveld gebruikt om op te slaan of een resource is aangemaakt door een EMu-script.';
$lang["emu_settings_header"]='EMu-instellingen';
$lang["emu_irn_field"]='Metadataveld gebruikt om de EMu-identificatie (IRN) op te slaan.';
$lang["emu_search_criteria"]='Zoekcriteria voor het synchroniseren van EMu met ResourceSpace.';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace toewijzingsregels.';
$lang["emu_module"]='EMu module can be translated to "EMu module". Since it is a technical term and refers to a specific module within the EMu software, it is not necessary to translate it into Nederlands.';
$lang["emu_column_name"]='EMu module kolom';
$lang["emu_rs_field"]='ResourceSpace-veld';
$lang["emu_add_mapping"]='Toevoegen van mapping.';
$lang["emu_upload_emu_field_label"]='EMu IRN can be translated to "EMu identificatienummer".';
$lang["emu_confirm_upload_nodata"]='Vink het vakje aan om te bevestigen dat je de upload wilt voortzetten.';
$lang["emu_test_script_title"]='Test/ Uitvoeren script';
$lang["emu_run_script"]='Verwerken';
$lang["emu_script_problem"]='WAARSCHUWING - het EMu-script is niet succesvol voltooid binnen de laatste %dagen% dagen. Laatste uitvoeringstijd:';
$lang["emu_no_resource"]='Geen resource-ID opgegeven!';
$lang["emu_upload_nodata"]='Geen EMu-gegevens gevonden voor dit IRN:';
$lang["emu_nodata_returned"]='Geen EMu-gegevens gevonden voor het opgegeven IRN.';
$lang["emu_createdfromemu"]='Gemaakt met EMU-plugin.';