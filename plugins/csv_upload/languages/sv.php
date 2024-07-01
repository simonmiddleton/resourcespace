<?php


$lang["csv_upload_nav_link"]='CSV-uppladdning';
$lang["csv_upload_intro"]='Detta tillägg gör det möjligt att skapa eller uppdatera resurser genom att ladda upp en CSV-fil. Formatet på CSV-filen är viktigt';
$lang["csv_upload_condition1"]='Säkerställ att CSV-filen är kodad med <b>UTF-8 utan BOM</b>.';
$lang["csv_upload_condition2"]='CSV-filen måste ha en rubrikrad';
$lang["csv_upload_condition3"]='För att kunna ladda upp resursfiler senare med hjälp av funktionen för batchutbyte, bör det finnas en kolumn som heter "Ursprungligt filnamn" och varje fil bör ha ett unikt filnamn.';
$lang["csv_upload_condition4"]='Alla obligatoriska fält för nyskapade resurser måste finnas i CSV-filen';
$lang["csv_upload_condition5"]='För kolumner som har värden som innehåller <b>kommor (,)</b>, se till att du formaterar det som typen <b>text</b> så att du inte behöver lägga till citattecken (""). När du sparar som en csv-fil, se till att markera alternativet att citera celler av typen text';
$lang["csv_upload_condition6"]='Du kan ladda ner ett exempel på en CSV-fil genom att klicka på <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='För att uppdatera befintliga resursdata kan du ladda ner en CSV-fil med befintlig metadata genom att klicka på alternativet \'CSV-export - metadata\' från samlingens eller sökresultatens åtgärdsmeny.';
$lang["csv_upload_condition8"]='Du kan återanvända en tidigare konfigurerad CSV-mappningsfil genom att klicka på \'Ladda upp CSV-konfigurationsfil\'';
$lang["csv_upload_error_no_permission"]='Du har inte tillräckliga behörigheter för att ladda upp en CSV-fil';
$lang["check_line_count"]='Minst två rader hittades i CSV-filen';
$lang["csv_upload_file"]='Välj CSV-fil';
$lang["csv_upload_default"]='Standard';
$lang["csv_upload_error_no_header"]='Ingen rubrikrad hittades i filen';
$lang["csv_upload_update_existing"]='Uppdatera befintliga resurser? Om detta inte är markerat kommer nya resurser att skapas baserat på CSV-data';
$lang["csv_upload_update_existing_collection"]='Bara uppdatera resurser i en specifik samling?';
$lang["csv_upload_process"]='Bearbeta';
$lang["csv_upload_add_to_collection"]='Lägg till nyskapade resurser till nuvarande samling?';
$lang["csv_upload_step1"]='Steg 1 - Välj fil';
$lang["csv_upload_step2"]='Steg 2 - Standardalternativ för resurser';
$lang["csv_upload_step3"]='Steg 3 - Kartlägg kolumner till metadatafält';
$lang["csv_upload_step4"]='Steg 4 - Kontrollera CSV-data';
$lang["csv_upload_step5"]='Steg 5 - Bearbetar CSV';
$lang["csv_upload_update_existing_title"]='Uppdatera befintliga resurser';
$lang["csv_upload_update_existing_notes"]='Välj de alternativ som krävs för att uppdatera befintliga resurser';
$lang["csv_upload_create_new_title"]='Skapa nya resurser';
$lang["csv_upload_create_new_notes"]='Välj de alternativ som krävs för att skapa nya resurser';
$lang["csv_upload_map_fields_notes"]='Matcha kolumnerna i CSV-filen med de krävda metadatafälten. Klicka på "Nästa" för att kontrollera CSV-filen utan att faktiskt ändra data';
$lang["csv_upload_map_fields_auto_notes"]='Metadatafälten har förvalts baserat på namn eller titlar, men vänligen kontrollera att dessa är korrekta';
$lang["csv_upload_workflow_column"]='Välj kolumnen som innehåller arbetsflödesstatus-ID';
$lang["csv_upload_workflow_default"]='Standard arbetsflödesstatus om ingen kolumn har valts eller om ingen giltig status har hittats i kolumnen';
$lang["csv_upload_access_column"]='Välj kolumnen som innehåller åtkomstnivån (0=Öppen, 1=Begränsad, 2=Konfidentiell)';
$lang["csv_upload_access_default"]='Standard åtkomstnivå om ingen kolumn är vald eller om ingen giltig åtkomst hittas i kolumnen';
$lang["csv_upload_resource_type_column"]='Välj kolumnen som innehåller resurstypens identifierare';
$lang["csv_upload_resource_type_default"]='Standard resurstyp om ingen kolumn har valts eller om ingen giltig typ hittas i kolumnen';
$lang["csv_upload_resource_match_column"]='Välj kolumnen som innehåller resursidentifikatorn';
$lang["csv_upload_match_type"]='Matcha resurs baserat på resurs-ID eller värde i metadatafält?';
$lang["csv_upload_multiple_match_action"]='Åtgärd att vidta om flera matchande resurser hittas';
$lang["csv_upload_validation_notes"]='Kontrollera valideringsmeddelandena nedan innan du fortsätter. Klicka på Process för att genomföra ändringarna';
$lang["csv_upload_upload_another"]='Ladda upp en annan CSV-fil';
$lang["csv_upload_mapping config"]='CSV kolumnmappningsinställningar';
$lang["csv_upload_download_config"]='Ladda ner CSV-mappningsinställningar som fil';
$lang["csv_upload_upload_config"]='Ladda upp CSV-mappningsfilen';
$lang["csv_upload_upload_config_question"]='Ladda upp CSV-mappningsfil? Använd detta om du har laddat upp en liknande CSV tidigare och har sparat konfigurationen';
$lang["csv_upload_upload_config_set"]='CSV konfigurationsuppsättning';
$lang["csv_upload_upload_config_clear"]='Rensa CSV-mappningskonfigurationen';
$lang["csv_upload_mapping_ignore"]='ANVÄND INTE';
$lang["csv_upload_mapping_header"]='Kolumnrubrik';
$lang["csv_upload_mapping_csv_data"]='Exempeldata från CSV';
$lang["csv_upload_using_config"]='Använda befintlig CSV-konfiguration';
$lang["csv_upload_process_offline"]='Bearbeta CSV-fil offline? Detta bör användas för stora CSV-filer. Du kommer att meddelas via ett ResourceSpace-meddelande när bearbetningen är klar';
$lang["csv_upload_oj_created"]='CSV-uppladdningsjobb skapat med jobb-ID # [jobref]. <br/>Du kommer att få ett systemmeddelande från ResourceSpace när jobbet är klart';
$lang["csv_upload_oj_complete"]='CSV-uppladdningsjobbet är klart. Klicka på länken för att visa hela loggfilen';
$lang["csv_upload_oj_failed"]='CSV-uppladdningsjobbet misslyckades';
$lang["csv_upload_processing_x_meta_columns"]='Bearbetar %count metadatakolumner';
$lang["csv_upload_processing_complete"]='Bearbetning slutförd vid [time] ([hours] timmar, [minutes] minuter, [seconds] sekunder)';
$lang["csv_upload_error_in_progress"]='Bearbetning avbruten - denna CSV-fil bearbetas redan';
$lang["csv_upload_error_file_missing"]='Fel - CSV-fil saknas: [file]';
$lang["csv_upload_full_messages_link"]='Visar endast de första 1000 raderna, för att ladda ner hela loggfilen klicka <a href=\'[log_url]\' target=\'_blank\'>här</a>';
$lang["csv_upload_ignore_errors"]='Ignorera fel och bearbeta filen ändå';
$lang["csv_upload_process_offline_quick"]='Hoppa över validering och bearbeta CSV-fil offline? Detta bör endast användas för stora CSV-filer när testning på mindre filer har slutförts. Du kommer att meddelas via ett ResourceSpace-meddelande när uppladdningen är klar';
$lang["csv_upload_force_offline"]='Denna stora CSV-fil kan ta lång tid att bearbeta, så den kommer att köras offline. Du kommer att meddelas via ett ResourceSpace-meddelande när bearbetningen är klar';
$lang["csv_upload_recommend_offline"]='Denna stora CSV-fil kan ta mycket lång tid att bearbeta. Det rekommenderas att offline-jobb aktiveras om du behöver bearbeta stora CSV-filer';
$lang["csv_upload_createdfromcsvupload"]='Skapad från CSV Uppladdningsplugin';