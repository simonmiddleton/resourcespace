<?php


$lang["csv_upload_nav_link"]='Încărcare CSV';
$lang["csv_upload_intro"]='Acest plugin vă permite să creați sau să actualizați resurse prin încărcarea unui fișier CSV. Formatul CSV este important';
$lang["csv_upload_condition1"]='Asigurați-vă că fișierul CSV este codificat folosind <b>UTF-8 fără BOM</b>';
$lang["csv_upload_condition2"]='CSV-ul trebuie să aibă o linie antet';
$lang["csv_upload_condition3"]='Pentru a putea încărca fișiere de resurse ulterior folosind funcționalitatea de înlocuire în lot, trebuie să existe o coloană numită "Numele original al fișierului" și fiecare fișier trebuie să aibă un nume unic';
$lang["csv_upload_condition4"]='Toate câmpurile obligatorii pentru orice resursă nou creată trebuie să fie prezente în fișierul CSV';
$lang["csv_upload_condition5"]='Pentru coloanele care au valori care conțin <b>virgule (,)</b>, asigurați-vă că le formatați ca tip <b>text</b>, astfel încât să nu fie necesar să adăugați ghilimele (""). Când salvați ca fișier csv, asigurați-vă că verificați opțiunea de citare a celulelor de tip text';
$lang["csv_upload_condition6"]='Puteți descărca un exemplu de fișier CSV făcând clic pe <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Pentru a actualiza datele existente ale resurselor, puteți descărca un fișier CSV cu metadatele existente făcând clic pe opțiunea „Export CSV - metadate” din meniul acțiunilor rezultatelor de căutare sau din colecție';
$lang["csv_upload_condition8"]='Puteți reutiliza un fișier de mapare CSV configurat anterior făcând clic pe "Încărcați fișierul de configurare CSV"';
$lang["csv_upload_error_no_permission"]='Nu aveți permisiunile corecte pentru a încărca un fișier CSV';
$lang["check_line_count"]='Au fost găsite cel puțin două rânduri în fișierul CSV';
$lang["csv_upload_file"]='Selectați fișierul CSV';
$lang["csv_upload_default"]='Valoare implicită';
$lang["csv_upload_error_no_header"]='Nu a fost găsită nicio linie antet în fișier';
$lang["csv_upload_update_existing"]='Actualizați resursele existente? Dacă aceasta nu este bifată, atunci noile resurse vor fi create pe baza datelor CSV';
$lang["csv_upload_update_existing_collection"]='Actualizează doar resursele dintr-o colecție specifică?';
$lang["csv_upload_process"]='Proces';
$lang["csv_upload_add_to_collection"]='Adăugați resursele nou create la colecția curentă?';
$lang["csv_upload_step1"]='Pasul 1 - Selectați fișierul';
$lang["csv_upload_step2"]='Pasul 2 - Opțiuni implicite ale resurselor';
$lang["csv_upload_step3"]='Pasul 3 - Asociați coloanele cu câmpurile de metadate';
$lang["csv_upload_step4"]='Pasul 4 - Verificarea datelor CSV';
$lang["csv_upload_step5"]='Pasul 5 - Procesarea fișierului CSV';
$lang["csv_upload_update_existing_title"]='Actualizare resurse existente';
$lang["csv_upload_update_existing_notes"]='Selectați opțiunile necesare pentru actualizarea resurselor existente';
$lang["csv_upload_create_new_title"]='Creați resurse noi';
$lang["csv_upload_create_new_notes"]='Selectați opțiunile necesare pentru a crea resurse noi';
$lang["csv_upload_map_fields_notes"]='Asociați coloanele din fișierul CSV cu câmpurile de metadate necesare. Apăsarea butonului "Următorul" va verifica fișierul CSV fără a schimba efectiv datele';
$lang["csv_upload_map_fields_auto_notes"]='Câmpurile de metadate au fost preselectate în funcție de nume sau titluri, dar vă rugăm să verificați dacă acestea sunt corecte';
$lang["csv_upload_workflow_column"]='Selectați coloana care conține ID-ul stării fluxului de lucru';
$lang["csv_upload_workflow_default"]='Starea implicită a fluxului de lucru dacă nu este selectată nicio coloană sau dacă nu este găsită nicio stare validă în coloană';
$lang["csv_upload_access_column"]='Selectați coloana care conține nivelul de acces (0=Deschis, 1=Restricționat, 2=Confidențial)';
$lang["csv_upload_access_default"]='Nivelul implicit de acces dacă nu este selectată nicio coloană sau dacă nu se găsește niciun acces valid în coloană';
$lang["csv_upload_resource_type_column"]='Selectați coloana care conține identificatorul tipului de resursă';
$lang["csv_upload_resource_type_default"]='Tipul implicit de resursă dacă nu este selectată nicio coloană sau dacă nu este găsit niciun tip valid în coloană';
$lang["csv_upload_resource_match_column"]='Selectați coloana care conține identificatorul resursei';
$lang["csv_upload_match_type"]='Potriviți resursa pe baza ID-ului resursei sau a valorii câmpului de metadate?';
$lang["csv_upload_multiple_match_action"]='Acțiunea de luat în cazul în care sunt găsite mai multe resurse potrivite';
$lang["csv_upload_validation_notes"]='Verificați mesajele de validare de mai jos înainte de a continua. Apăsați Procesare pentru a confirma modificările';
$lang["csv_upload_upload_another"]='Încărcați un alt fișier CSV';
$lang["csv_upload_mapping config"]='Setările de mapare a coloanelor CSV';
$lang["csv_upload_download_config"]='Descarcă setările de mapare CSV ca fișier';
$lang["csv_upload_upload_config"]='Încărcați fișierul de mapare CSV';
$lang["csv_upload_upload_config_question"]='Încărcați fișierul de mapare CSV? Utilizați aceasta dacă ați încărcat anterior un fișier CSV similar și ați salvat configurația';
$lang["csv_upload_upload_config_set"]='Configurare set CSV';
$lang["csv_upload_upload_config_clear"]='Configurarea clară a mapării CSV';
$lang["csv_upload_mapping_ignore"]='NU UTILIZAȚI';
$lang["csv_upload_mapping_header"]='Antetul coloanei';
$lang["csv_upload_mapping_csv_data"]='Date de exemplu din CSV';
$lang["csv_upload_using_config"]='Utilizând configurația CSV existentă';
$lang["csv_upload_process_offline"]='Procesați fișierul CSV offline? Aceasta ar trebui să fie utilizată pentru fișierele CSV mari. Veți fi notificat printr-un mesaj ResourceSpace odată ce procesarea este completă';
$lang["csv_upload_oj_created"]='Jobul de încărcare CSV a fost creat cu ID-ul de job # %%JOBREF%%. <br/>Veți primi un mesaj de sistem ResourceSpace odată ce jobul va fi finalizat';
$lang["csv_upload_oj_complete"]='Încărcarea fișierului CSV s-a încheiat. Apăsați pe link pentru a vizualiza fișierul complet de jurnal';
$lang["csv_upload_oj_failed"]='Încărcarea fișierului CSV a eșuat';
$lang["csv_upload_processing_x_meta_columns"]='Procesarea a %count coloane de metadate';
$lang["csv_upload_processing_complete"]='Procesarea s-a încheiat la [time] (%%HOURS%% ore, %%MINUTES%% minute, %%SECONDS%% secunde)';
$lang["csv_upload_error_in_progress"]='Procesarea a fost anulată - acest fișier CSV este deja în curs de procesare';
$lang["csv_upload_error_file_missing"]='Eroare - Fișierul CSV lipsește: %%FILE%%';
$lang["csv_upload_full_messages_link"]='Afișare doar a primelor 1000 de linii, pentru a descărca fișierul complet de jurnal, faceți clic <a href=\'%%LOG_URL%%\' target=\'_blank\'>aici</a>';
$lang["csv_upload_ignore_errors"]='Ignoră erorile și procesează fișierul oricum';
$lang["csv_upload_process_offline_quick"]='Săriți peste validare și procesați fișierul CSV offline? Această opțiune ar trebui utilizată doar pentru fișierele CSV mari, după ce testarea pe fișiere mai mici a fost finalizată. Veți fi notificat printr-un mesaj ResourceSpace odată ce încărcarea este finalizată';
$lang["csv_upload_force_offline"]='Acest fișier CSV mare poate dura mult timp pentru a fi procesat, așa că va fi rulat offline. Veți fi notificat printr-un mesaj ResourceSpace odată ce procesarea este completă';
$lang["csv_upload_recommend_offline"]='Acest fișier CSV mare poate dura foarte mult timp pentru a fi procesat. Se recomandă activarea sarcinilor offline dacă trebuie să procesați fișiere CSV mari';
$lang["csv_upload_createdfromcsvupload"]='Creat prin intermediul plugin-ului de încărcare CSV';