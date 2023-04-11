<?php


$lang["emu_configuration"]='EMu kokoonpano.';
$lang["emu_api_settings"]='API-palvelimen asetukset';
$lang["emu_api_server"]='Palvelimen osoite (esim. http://[palvelimen.osoite])';
$lang["emu_api_server_port"]='Palvelimen portti';
$lang["emu_resource_types"]='Valitse EMu:un linkitettyjen resurssityyppien joukosta.';
$lang["emu_email_notify"]='Sähköpostiosoite, johon skripti lähettää ilmoituksia. Jätä tyhjäksi käyttääksesi järjestelmän oletussähköpostiosoitetta.';
$lang["emu_script_failure_notify_days"]='Päivien määrä, jonka jälkeen hälytys näytetään ja sähköposti lähetetään, jos skriptiä ei ole suoritettu loppuun.';
$lang["emu_script_header"]='Ota käyttöön skripti, joka päivittää EMu-tiedot automaattisesti aina kun ResourceSpace suorittaa ajastetun tehtävänsä (cron_copy_hitcount.php).';
$lang["emu_script_mode"]='Skriptitila';
$lang["emu_script_mode_option_1"]='Tuo metatiedot EMu:sta.';
$lang["emu_script_mode_option_2"]='Vedä kaikki EMu-tietueet ja pidä RS ja EMu synkronoituna.';
$lang["emu_enable_script"]='Ota EMu-skripti käyttöön.';
$lang["emu_test_mode"]='Testitila - Aseta arvoksi "true" ja skripti suoritetaan, mutta resursseja ei päivitetä.';
$lang["emu_interval_run"]='Suorita skripti seuraavassa aikavälissä (esim. +1 päivä, +2 viikkoa, kahden viikon välein). Jätä tyhjäksi, niin se suoritetaan joka kerta, kun cron_copy_hitcount.php suoritetaan.';
$lang["emu_log_directory"]='Hakemisto, johon skriptien lokit tallennetaan. Jos tämä jätetään tyhjäksi tai se on virheellinen, lokitusta ei tapahdu.';
$lang["emu_created_by_script_field"]='Metatietokenttä, jota käytetään tallentamaan, onko resurssi luotu EMu-skriptillä.';
$lang["emu_settings_header"]='EMu-asetukset';
$lang["emu_irn_field"]='Metatietokenttä, jota käytetään EMu-tunnisteen (IRN) tallentamiseen.';
$lang["emu_search_criteria"]='Etsintäkriteerit EMu:n synkronointiin ResourceSpacen kanssa.';
$lang["emu_rs_mappings_header"]='EMu - ResourceSpace kartoitussäännöt';
$lang["emu_module"]='EMu-moduuli';
$lang["emu_column_name"]='EMu-moduulin sarake';
$lang["emu_rs_field"]='ResourceSpace-kenttä';
$lang["emu_add_mapping"]='Lisää kartoitus.';
$lang["emu_upload_emu_field_label"]='EMu IRN:n suomennos on EMu-tunnusnumero.';
$lang["emu_confirm_upload_nodata"]='Ole hyvä ja merkitse ruutu, jotta voit vahvistaa haluavasi jatkaa tiedoston lataamista.';
$lang["emu_test_script_title"]='Testaa / Suorita skripti';
$lang["emu_run_script"]='Prosessi';
$lang["emu_script_problem"]='VAROITUS - EMu-skriptiä ei ole suoritettu onnistuneesti viimeisen %days% päivän aikana. Viimeinen suoritusaika:';
$lang["emu_no_resource"]='Resurssin tunnusta ei ole määritetty!';
$lang["emu_upload_nodata"]='Tälle IRN:lle ei löytynyt EMu-tietoja.';
$lang["emu_nodata_returned"]='Määriteltyä IRN-tunnusta vastaavia EMu-tietoja ei löytynyt.';
$lang["emu_createdfromemu"]='Luotu EMU-liitännäisestä.';
$lang["emu_last_run_date"]='Skripti viimeksi suoritettu';