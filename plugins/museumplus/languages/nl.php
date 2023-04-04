<?php


$lang["museumplus_configuration"]='MuseumPlus Configuratie';
$lang["museumplus_top_menu_title"]='MuseumPlus: ongeldige associaties.';
$lang["museumplus_api_settings_header"]='API details in Nederlands is "API-details".';
$lang["museumplus_host"]='Host (Nederlands): Gastheer/Gastvrouw';
$lang["museumplus_host_api"]='API-host (alleen voor API-oproepen; meestal hetzelfde als hierboven)';
$lang["museumplus_application"]='Toepassingsnaam';
$lang["user"]='Gebruiker';
$lang["museumplus_api_user"]='Gebruiker';
$lang["password"]='Wachtwoord';
$lang["museumplus_api_pass"]='Wachtwoord';
$lang["museumplus_RS_settings_header"]='ResourceSpace instellingen.';
$lang["museumplus_mpid_field"]='Metadataveld gebruikt om de MuseumPlus-identificatie (MpID) op te slaan.';
$lang["museumplus_module_name_field"]='Metagegevensveld dat wordt gebruikt om de naam van de modules op te slaan waarvoor de MpID geldig is. Als dit niet is ingesteld, zal de plugin terugvallen op de configuratie van de "Object" module.';
$lang["museumplus_secondary_links_field"]='Metadataveld gebruikt om secundaire links naar andere modules op te slaan. ResourceSpace genereert een MuseumPlus-URL voor elk van de links. Links hebben een speciale syntaxis-indeling: module_naam:ID (bijv. "Object:1234").';
$lang["museumplus_object_details_title"]='MuseumPlus details = MuseumPlus details';
$lang["museumplus_script_header"]='Script instellingen.';
$lang["museumplus_last_run_date"]='<div class="Vraag">
    <label>
        <strong>Laatste uitvoering van script</strong>
    </label>
    <input name="script_last_ran" type="text" value="%script_last_ran" disabled style="width: 420px;">
</div>
<div class="clearerleft"></div>';
$lang["museumplus_enable_script"]='Inschakelen van MuseumPlus-script.';
$lang["museumplus_interval_run"]='Voer script uit op het volgende interval (bijv. +1 dag, +2 weken, veertien dagen). Laat leeg en het zal elke keer worden uitgevoerd wanneer cron_copy_hitcount.php wordt uitgevoerd.';
$lang["museumplus_log_directory"]='Map om scriptlogs op te slaan. Als dit leeg blijft of ongeldig is, wordt er geen logging uitgevoerd.';
$lang["museumplus_integrity_check_field"]='Integriteitscontrole veld.';
$lang["museumplus_modules_configuration_header"]='Moduleconfiguratie';
$lang["museumplus_module"]='Module. (The word "module" is the same in Dutch as it is in English.)';
$lang["museumplus_add_new_module"]='Nieuwe MuseumPlus module toevoegen.';
$lang["museumplus_mplus_field_name"]='MuseumPlus veldnaam.';
$lang["museumplus_rs_field"]='ResourceSpace-veld';
$lang["museumplus_view_in_museumplus"]='Bekijken in MuseumPlus.';
$lang["museumplus_confirm_delete_module_config"]='Weet u zeker dat u deze module configuratie wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt!';
$lang["museumplus_module_setup"]='Module-instellingen';
$lang["museumplus_module_name"]='Naam van de MuseumPlus module.';
$lang["museumplus_mplus_id_field"]='MuseumPlus ID veldnaam.';
$lang["museumplus_mplus_id_field_helptxt"]='Laat leeg om de technische ID \'__id\' te gebruiken (standaard).';
$lang["museumplus_rs_uid_field"]='ResourceSpace UID-veld';
$lang["museumplus_applicable_resource_types"]='Toepasbare bron type(n)';
$lang["museumplus_field_mappings"]='MuseumPlus - ResourceSpace veldkoppelingen.';
$lang["museumplus_add_mapping"]='Toevoegen van mapping.';
$lang["museumplus_error_bad_conn_data"]='Ongeldige MuseumPlus-verbindinggegevens.';
$lang["museumplus_error_unexpected_response"]='Onverwachte MuseumPlus responscode ontvangen - %code.';
$lang["museumplus_error_no_data_found"]='Geen gegevens gevonden in MuseumPlus voor deze MpID - %mpid.';
$lang["museumplus_warning_script_not_completed"]='WAARSCHUWING: Het MuseumPlus-script is niet voltooid sinds \'%script_last_ran\'.
U kunt deze waarschuwing veilig negeren als u vervolgens een melding hebt ontvangen van een succesvolle scriptvoltooiing.';
$lang["museumplus_error_script_failed"]='Het MuseumPlus-script kon niet worden uitgevoerd omdat er een procesvergrendeling actief was. Dit geeft aan dat de vorige uitvoering niet is voltooid. Als u de vergrendeling na een mislukte uitvoering wilt wissen, voert u het script als volgt uit: php museumplus_script.php --clear-lock.';
$lang["museumplus_php_utility_not_found"]='De configuratieoptie $php_path MOET worden ingesteld om de cron-functionaliteit succesvol te laten werken!';
$lang["museumplus_error_not_deleted_module_conf"]='Kan de gevraagde module configuratie niet verwijderen.';
$lang["museumplus_error_unknown_type_saved_config"]='De \'museumplus_modules_saved_config\' is van een onbekend type!';
$lang["museumplus_error_invalid_association"]='Ongeldige module(s) associatie. Zorg ervoor dat de juiste Module en/of Record ID zijn ingevoerd!';
$lang["museumplus_id_returns_multiple_records"]='Meerdere records gevonden - voer in plaats daarvan de technische ID in.';
$lang["museumplus_error_module_no_field_maps"]='Kan geen gegevens synchroniseren vanuit MuseumPlus. Reden: module \'%name\' heeft geen veldtoewijzingen geconfigureerd.';