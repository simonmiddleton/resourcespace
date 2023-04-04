<?php


$lang["emu_configuration"]='EMu-konfigurasjon';
$lang["emu_api_settings"]='Innstillinger for API-server';
$lang["emu_api_server"]='Serveradresse (f.eks. http://[server.adresse])';
$lang["emu_api_server_port"]='Serverport.';
$lang["emu_resource_types"]='Velg ressurstype koblet til EMu.';
$lang["emu_email_notify"]='E-postadresse som skriptet vil sende varsler til. La stå tomt for å bruke standard systemvarsler.';
$lang["emu_script_failure_notify_days"]='Antall dager etter hvilket varsel skal vises og e-post sendes hvis skriptet ikke er fullført.';
$lang["emu_script_header"]='Aktiver skriptet som automatisk oppdaterer EMu-dataene hver gang ResourceSpace kjører sin planlagte oppgave (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='<div class="Question">
	<label>
		<strong>Skript sist kjørt</strong>
	</label>
	<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
	%scripts_test_functionality%
</div>
<div class="clearerleft"></div>';
$lang["emu_script_mode"]='Skriptmodus';
$lang["emu_script_mode_option_1"]='Importer metadata fra EMu.';
$lang["emu_script_mode_option_2"]='Hent alle EMu-poster og hold RS og EMu synkronisert.';
$lang["emu_enable_script"]='Aktiver EMu-skript.';
$lang["emu_test_mode"]='Testmodus - Sett til sann og skriptet vil kjøre, men ikke oppdatere ressurser.';
$lang["emu_interval_run"]='Kjør skriptet med følgende intervall (f.eks. +1 dag, +2 uker, fjorten dager). La feltet stå tomt og det vil kjøre hver gang cron_copy_hitcount.php kjøres.';
$lang["emu_log_directory"]='Mappe for å lagre skriptlogger. Hvis dette feltet er tomt eller ugyldig, vil ingen logging skje.';
$lang["emu_created_by_script_field"]='Metadatafelt brukt til å lagre om en ressurs er opprettet av EMu-skript.';
$lang["emu_settings_header"]='EMu-innstillinger';
$lang["emu_irn_field"]='Metadatafelt brukt til å lagre EMu-identifikatoren (IRN)';
$lang["emu_search_criteria"]='Søkekriterier for synkronisering av EMu med ResourceSpace.';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace kartleggingsregler.';
$lang["emu_module"]='EMu-modul';
$lang["emu_column_name"]='EMu-modul kolonne.';
$lang["emu_rs_field"]='ResourceSpace-felt';
$lang["emu_add_mapping"]='Legg til kartlegging.';
$lang["emu_upload_emu_field_label"]='EMu IRN can be translated to "EMu identifikasjonsnummer".';
$lang["emu_confirm_upload_nodata"]='Vennligst sjekk boksen for å bekrefte at du ønsker å fortsette opplastingen.';
$lang["emu_test_script_title"]='Test/Kjør skript';
$lang["emu_run_script"]='Behandling';
$lang["emu_script_problem"]='ADVARSEL - EMu-skriptet har ikke fullført vellykket innen de siste %days% dagene. Siste kjøretid:';
$lang["emu_no_resource"]='Ingen ressurs-ID spesifisert!';
$lang["emu_upload_nodata"]='Ingen EMu-data funnet for denne IRN-en:';
$lang["emu_nodata_returned"]='Ingen EMu-data funnet for angitt IRN.';
$lang["emu_createdfromemu"]='Opprettet fra EMU-tillegget.';