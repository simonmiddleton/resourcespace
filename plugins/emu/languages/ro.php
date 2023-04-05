<?php


$lang["emu_configuration"]='Configurarea EMu.';
$lang["emu_api_settings"]='Configurările serverului API.';
$lang["emu_api_server"]='Adresa serverului (de exemplu, http://[adresa.serverului])';
$lang["emu_api_server_port"]='Portul serverului.';
$lang["emu_resource_types"]='Selectați tipurile de resurse legate de EMu.';
$lang["emu_email_notify"]='Adresa de e-mail către care scriptul va trimite notificări. Lăsați gol pentru a folosi adresa implicită de notificare a sistemului.';
$lang["emu_script_failure_notify_days"]='Numărul de zile după care să afișeze alerta și să trimită un e-mail dacă scriptul nu a fost finalizat.';
$lang["emu_script_header"]='Permiteți scriptul care va actualiza automat datele EMu ori de câte ori ResourceSpace rulează sarcina programată (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Ultima rulare a scriptului';
$lang["emu_script_mode"]='Modul script.';
$lang["emu_script_mode_option_1"]='Importați metadatele din EMu.';
$lang["emu_script_mode_option_2"]='Extrageți toate înregistrările EMu și mențineți sincronizarea între RS și EMu.';
$lang["emu_enable_script"]='Permiteți scriptul EMu.';
$lang["emu_test_mode"]='Modul de testare - Setat la adevărat, scriptul va rula, dar nu va actualiza resursele.';
$lang["emu_interval_run"]='Rulați scriptul la următorul interval (de exemplu, +1 zi, +2 săptămâni, două săptămâni). Lăsați gol și se va rula de fiecare dată când cron_copy_hitcount.php rulează.';
$lang["emu_log_directory"]='Director pentru stocarea jurnalelor de scripturi. Dacă acest câmp este lăsat gol sau este invalid, atunci nu va avea loc nicio înregistrare a jurnalelor.';
$lang["emu_created_by_script_field"]='Câmpul de metadate folosit pentru a stoca dacă un resursă a fost creată prin intermediul unui script EMu.';
$lang["emu_settings_header"]='Setări EMu.';
$lang["emu_irn_field"]='Câmpul de metadate folosit pentru a stoca identificatorul EMu (IRN).';
$lang["emu_search_criteria"]='Criterii de căutare pentru sincronizarea EMu cu ResourceSpace.';
$lang["emu_rs_mappings_header"]='Reguli de mapare EMu - ResourceSpace.';
$lang["emu_module"]='Modul EMu.';
$lang["emu_column_name"]='Vă rugăm să traduceți: Coloană modul EMu.';
$lang["emu_rs_field"]='Câmp ResourceSpace.';
$lang["emu_add_mapping"]='Adăugați mapare.';
$lang["emu_confirm_upload_nodata"]='Vă rugăm să bifați caseta pentru a confirma că doriți să continuați încărcarea.';
$lang["emu_test_script_title"]='Testare/ Rulare script.';
$lang["emu_run_script"]='Proces.';
$lang["emu_script_problem"]='ATENȚIE - scriptul EMu nu a fost finalizat cu succes în ultimele %days% zile. Ultima rulare:';
$lang["emu_no_resource"]='Nu a fost specificat niciun ID resursă!';
$lang["emu_upload_nodata"]='Nu s-au găsit date EMu pentru acest IRN:';
$lang["emu_nodata_returned"]='Nu s-au găsit date EMu pentru IRN-ul specificat.';
$lang["emu_createdfromemu"]='Creat din modulul EMU.';