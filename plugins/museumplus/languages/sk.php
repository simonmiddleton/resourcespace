<?php


$lang["museumplus_configuration"]='Konfigurácia MuseumPlus.';
$lang["museumplus_top_menu_title"]='MuseumPlus: neplatné asociácie.';
$lang["museumplus_api_settings_header"]='Podrobnosti API-ja.';
$lang["museumplus_host"]='Host - Hostiteľ';
$lang["museumplus_host_api"]='API host (len pre volania API; zvyčajne rovnaké ako vyššie)';
$lang["museumplus_application"]='Názov aplikácie.';
$lang["user"]='Používateľ.';
$lang["museumplus_api_user"]='Používateľ.';
$lang["password"]='Heslo.';
$lang["museumplus_api_pass"]='Heslo.';
$lang["museumplus_RS_settings_header"]='Nastavitve sistema ResourceSpace.';
$lang["museumplus_mpid_field"]='Metadátové pole používané na ukladanie identifikátora MuseumPlus (MpID).';
$lang["museumplus_module_name_field"]='Metadátové pole používané na uchovanie názvu modulov, pre ktoré je platné MpID. Ak nie je nastavené, plugin sa vráti k nastaveniam modulu "Objekt".';
$lang["museumplus_secondary_links_field"]='Metadátové pole používané na uchovávanie sekundárnych odkazov na iné moduly. ResourceSpace pre každý odkaz vygeneruje URL pre MuseumPlus. Odkazy budú mať špeciálny formát syntaxe: názov_modulu:ID (napr. "Object:1234").';
$lang["museumplus_object_details_title"]='Podrobnosti o MuseumPlus.';
$lang["museumplus_script_header"]='Nastavitve skripta.';
$lang["museumplus_last_run_date"]='<div class="Question">
    <label>
        <strong>Posledný beh skriptu</strong>
    </label>
    <input name="script_last_ran" type="text" value="%script_last_ran" disabled style="width: 420px;">
</div>
<div class="clearerleft"></div>';
$lang["museumplus_enable_script"]='Povolit skript MuseumPlus.';
$lang["museumplus_interval_run"]='Spustiť skript v nasledujúcom intervale (napr. +1 deň, +2 týždne, dva týždne). Ak chcete, aby sa spustil pri každom behu cron_copy_hitcount.php, ponechajte prázdne.';
$lang["museumplus_log_directory"]='Adresár pre ukladanie záznamov skriptov. Ak je toto pole prázdne alebo neplatné, záznamy nebudú vytvorené.';
$lang["museumplus_integrity_check_field"]='Kontrola integrity poľa.';
$lang["museumplus_modules_configuration_header"]='Konfigurácia modulov.';
$lang["museumplus_module"]='Modul';
$lang["museumplus_add_new_module"]='Pridať nový modul MuseumPlus.';
$lang["museumplus_mplus_field_name"]='Názov pola v MuseumPlus.';
$lang["museumplus_rs_field"]='Pole ResourceSpace.';
$lang["museumplus_view_in_museumplus"]='Zobraziť v MuseumPlus.';
$lang["museumplus_confirm_delete_module_config"]='Ste si istý/á, že chcete vymazať konfiguráciu tohto modulu? Táto akcia sa nedá vrátiť späť!';
$lang["museumplus_module_setup"]='Nastavenie modulu.';
$lang["museumplus_module_name"]='Názov modulu MuseumPlus.';
$lang["museumplus_mplus_id_field"]='Názov poľa pre identifikátor v MuseumPlus.';
$lang["museumplus_mplus_id_field_helptxt"]='Nechajte prázdne, ak chcete použiť technické ID \'__id\' (predvolené).';
$lang["museumplus_applicable_resource_types"]='Použiteľný typ(y) zdroja.';
$lang["museumplus_field_mappings"]='MuseumPlus - mapovanie polí ResourceSpace';
$lang["museumplus_add_mapping"]='Pridať mapovanie.';
$lang["museumplus_error_bad_conn_data"]='Neplatné pripojenie k dátam v MuseumPlus.';
$lang["museumplus_error_unexpected_response"]='Nepredviden odziv kode MuseumPlus prejet - %code.';
$lang["museumplus_error_no_data_found"]='Neboli nájdené žiadne údaje v MuseumPlus pre toto MpID - %mpid.';
$lang["museumplus_warning_script_not_completed"]='UPOZORNENIE: Skript MuseumPlus sa neukončil od \'%script_last_ran\'.
Toto upozornenie môžete bezpečne ignorovať len v prípade, že ste neskôr dostali oznámenie o úspešnom dokončení skriptu.';
$lang["museumplus_error_script_failed"]='Skript MuseumPlus sa nepodarilo spustiť, pretože bola v mieste procesu uzamknutá. To naznačuje, že predchádzajúci beh nebol dokončený. Ak chcete po neúspešnom behu odstrániť uzamknutie, spustite skript nasledovne: php museumplus_script.php --clear-lock.';
$lang["museumplus_php_utility_not_found"]='Možnost konfiguracije $php_path MORA biti nastavljena, da bi funkcionalnost cron-a uspešno delovala!';
$lang["museumplus_error_not_deleted_module_conf"]='Nemožno odstrániť požadovanú konfiguráciu modulu.';
$lang["museumplus_error_unknown_type_saved_config"]='"museumplus_modules_saved_config" je neznanega tipa!';
$lang["museumplus_error_invalid_association"]='Neplatné prepojenie modulu. Uistite sa, že ste zadali správne ID modulu a / alebo záznamu!';
$lang["museumplus_id_returns_multiple_records"]='Nájdených viacero záznamov - prosím, zadajte technické ID namiesto toho.';
$lang["museumplus_error_module_no_field_maps"]='Nemožné synchronizovať dáta z MuseumPlus. Dôvod: modul \'%name\' nemá nakonfigurované mapovanie polí.';