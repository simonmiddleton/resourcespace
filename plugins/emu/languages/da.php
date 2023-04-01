<?php


$lang["emu_configuration"]='EMu Konfiguration.';
$lang["emu_api_settings"]='API server indstillinger.';
$lang["emu_api_server"]='Serveradresse (f.eks. http://[server.adresse])';
$lang["emu_api_server_port"]='Server port can be translated to "Serverport".';
$lang["emu_resource_types"]='Vælg ressourcetyper, der er linket til EMu.';
$lang["emu_email_notify"]='E-mailadresse, som scriptet vil sende notifikationer til. Lad være blank for at bruge standardnotifikationsadressen i systemet.';
$lang["emu_script_failure_notify_days"]='Antal dage efter hvilke en advarsel skal vises og en e-mail skal sendes, hvis scriptet ikke er fuldført.';
$lang["emu_script_header"]='Aktivér scriptet, der automatisk opdaterer EMu-dataene, når ResourceSpace kører sin planlagte opgave (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='<div class="Question">
	<label>
		<strong>Script sidst kørt</strong>
	</label>
	<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
	%scripts_test_functionality%
</div>
<div class="clearerleft"></div>';
$lang["emu_script_mode"]='Skripttilstand';
$lang["emu_script_mode_option_1"]='Importer metadata fra EMu.';
$lang["emu_script_mode_option_2"]='Hent alle EMu-poster og hold RS og EMu synkroniseret.';
$lang["emu_enable_script"]='Aktivér EMu-script.';
$lang["emu_test_mode"]='Testtilstand - Sæt til sandt, og scriptet vil køre, men ikke opdatere ressourcer.';
$lang["emu_interval_run"]='Kør scriptet med følgende interval (f.eks. +1 dag, +2 uger, fjorten dage). Lad feltet stå tomt, og det vil køre hver gang, cron_copy_hitcount.php køres).';
$lang["emu_log_directory"]='Mappe til at gemme scriptlogs i. Hvis dette efterlades tomt eller er ugyldigt, vil der ikke blive logget noget.';
$lang["emu_created_by_script_field"]='Metadatafelt brugt til at gemme om en ressource er blevet oprettet af EMu-script.';
$lang["emu_settings_header"]='EMu indstillinger';
$lang["emu_irn_field"]='Metadatafelt brugt til at gemme EMu identifikatoren (IRN).';
$lang["emu_search_criteria"]='Søgekriterier for synkronisering af EMu med ResourceSpace.';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace kortlægningsregler';
$lang["emu_module"]='EMu-modul.';
$lang["emu_column_name"]='EMu modul kolonne.';
$lang["emu_rs_field"]='ResourceSpace felt.';
$lang["emu_add_mapping"]='Tilføj kortlægning.';
$lang["emu_upload_emu_field_label"]='EMu IRN could be translated to "EMu identifikationsnummer".';
$lang["emu_confirm_upload_nodata"]='Venligst markér afkrydsningsfeltet for at bekræfte, at du ønsker at fortsætte med uploaden.';
$lang["emu_test_script_title"]='Test/ Kør script.';
$lang["emu_run_script"]='Behandling';
$lang["emu_script_problem"]='ADVARSEL - EMu-scriptet er ikke blevet fuldført med succes inden for de sidste %days% dage. Sidste kørselstidspunkt:';
$lang["emu_no_resource"]='Ingen ressource-ID angivet!';
$lang["emu_upload_nodata"]='Ingen EMu-data fundet for denne IRN:';
$lang["emu_nodata_returned"]='Ingen EMu-data fundet for den angivne IRN.';
$lang["emu_createdfromemu"]='Oprettet fra EMU-plugin.';