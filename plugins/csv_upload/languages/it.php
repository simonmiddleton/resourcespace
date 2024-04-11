<?php


$lang["csv_upload_nav_link"]='Caricamento CSV';
$lang["csv_upload_intro"]='Questo plugin consente di creare o aggiornare risorse caricando un file CSV. Il formato del CSV è importante';
$lang["csv_upload_condition1"]='<li>Assicurati che il file CSV sia codificato utilizzando <b>UTF-8 senza BOM</b>.</li>';
$lang["csv_upload_condition2"]='Il file CSV deve avere una riga di intestazione';
$lang["csv_upload_condition3"]='<li>Per poter caricare i file di risorse in seguito utilizzando la funzionalità di sostituzione batch, dovrebbe esserci una colonna chiamata \'Nome file originale\' e ogni file dovrebbe avere un nome univoco</li>';
$lang["csv_upload_condition4"]='Tutti i campi obbligatori per le nuove risorse create devono essere presenti nel file CSV';
$lang["csv_upload_condition5"]='<li>Per le colonne che hanno valori contenenti <b>virgole (,)</b>, assicurati di formattarle come tipo <b>testo</b> in modo da non dover aggiungere virgolette (""). Quando salvi come file csv, assicurati di selezionare l\'opzione di citazione delle celle di tipo testo</li>';
$lang["csv_upload_condition6"]='Puoi scaricare un esempio di file CSV cliccando su <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Per aggiornare i dati di una risorsa esistente, puoi scaricare un file CSV con i metadati esistenti cliccando sull\'opzione "Esporta CSV - metadati" dal menu azioni della collezione o dei risultati di ricerca';
$lang["csv_upload_condition8"]='Puoi riutilizzare un file di mappatura CSV configurato in precedenza cliccando su \'Carica file di configurazione CSV\'';
$lang["csv_upload_error_no_permission"]='Non hai le autorizzazioni corrette per caricare un file CSV';
$lang["check_line_count"]='Sono state trovate almeno due righe nel file CSV';
$lang["csv_upload_file"]='Seleziona file CSV';
$lang["csv_upload_default"]='Predefinito';
$lang["csv_upload_error_no_header"]='Nessuna riga di intestazione trovata nel file';
$lang["csv_upload_update_existing"]='Aggiornare le risorse esistenti? Se non selezionato, verranno create nuove risorse in base ai dati CSV';
$lang["csv_upload_update_existing_collection"]='Aggiornare solo le risorse in una collezione specifica?';
$lang["csv_upload_process"]='Elaborazione';
$lang["csv_upload_add_to_collection"]='Aggiungere le risorse appena create alla collezione corrente?';
$lang["csv_upload_step1"]='Passo 1 - Seleziona il file';
$lang["csv_upload_step2"]='Passo 2 - Opzioni predefinite delle risorse';
$lang["csv_upload_step3"]='Passo 3 - Mappa le colonne ai campi di metadati';
$lang["csv_upload_step4"]='Passo 4 - Verifica dei dati CSV';
$lang["csv_upload_step5"]='Passo 5 - Elaborazione CSV';
$lang["csv_upload_update_existing_title"]='Aggiorna le risorse esistenti';
$lang["csv_upload_update_existing_notes"]='Selezionare le opzioni necessarie per aggiornare le risorse esistenti';
$lang["csv_upload_create_new_title"]='Creare nuove risorse';
$lang["csv_upload_create_new_notes"]='Selezionare le opzioni necessarie per creare nuove risorse';
$lang["csv_upload_map_fields_notes"]='Abbinare le colonne nel CSV ai campi di metadati richiesti. Cliccando su \'Avanti\' verrà verificato il CSV senza modificare effettivamente i dati';
$lang["csv_upload_map_fields_auto_notes"]='I campi di metadati sono stati preselezionati in base ai nomi o ai titoli, ma per favore controlla che siano corretti';
$lang["csv_upload_workflow_column"]='Seleziona la colonna che contiene l\'ID dello stato del flusso di lavoro';
$lang["csv_upload_workflow_default"]='Stato predefinito del flusso di lavoro se nessuna colonna è selezionata o se non viene trovato uno stato valido nella colonna';
$lang["csv_upload_access_column"]='Seleziona la colonna che contiene il livello di accesso (0=Aperto, 1=Ristretto, 2=Confidenziale)';
$lang["csv_upload_access_default"]='Livello di accesso predefinito se nessuna colonna è selezionata o se non viene trovato alcun accesso valido nella colonna';
$lang["csv_upload_resource_type_column"]='Seleziona la colonna che contiene l\'identificatore del tipo di risorsa';
$lang["csv_upload_resource_type_default"]='Tipo di risorsa predefinito se nessuna colonna è selezionata o se non viene trovato alcun tipo valido nella colonna';
$lang["csv_upload_resource_match_column"]='Seleziona la colonna che contiene l\'identificatore della risorsa';
$lang["csv_upload_match_type"]='Abbinare la risorsa in base all\'ID della risorsa o al valore del campo di metadati?';
$lang["csv_upload_multiple_match_action"]='Azione da intraprendere se vengono trovate più risorse corrispondenti';
$lang["csv_upload_validation_notes"]='Controlla i messaggi di validazione qui sotto prima di procedere. Clicca su Processa per confermare le modifiche';
$lang["csv_upload_upload_another"]='Carica un altro file CSV';
$lang["csv_upload_mapping config"]='Impostazioni di mappatura delle colonne CSV';
$lang["csv_upload_download_config"]='Scarica le impostazioni di mappatura CSV come file';
$lang["csv_upload_upload_config"]='Carica file di mappatura CSV';
$lang["csv_upload_upload_config_question"]='Caricare il file di mappatura CSV? Utilizzare questo se hai già caricato un file CSV simile in precedenza e hai salvato la configurazione';
$lang["csv_upload_upload_config_set"]='Impostazione di configurazione CSV';
$lang["csv_upload_upload_config_clear"]='Cancellare la configurazione di mappatura CSV';
$lang["csv_upload_mapping_ignore"]='NON UTILIZZARE';
$lang["csv_upload_mapping_header"]='Intestazione di colonna';
$lang["csv_upload_mapping_csv_data"]='Dati di esempio da CSV';
$lang["csv_upload_using_config"]='Utilizzando la configurazione CSV esistente';
$lang["csv_upload_process_offline"]='Elaborare il file CSV offline? Questo dovrebbe essere utilizzato per file CSV di grandi dimensioni. Riceverai una notifica tramite un messaggio di ResourceSpace una volta completata l\'elaborazione';
$lang["csv_upload_oj_created"]='Caricamento CSV creato con ID lavoro # %%JOBREF%%. <br/> Riceverai un messaggio di sistema di ResourceSpace una volta completato il lavoro';
$lang["csv_upload_oj_complete"]='Caricamento CSV completato. Clicca sul link per visualizzare il file di registro completo';
$lang["csv_upload_oj_failed"]='Caricamento CSV non riuscito';
$lang["csv_upload_processing_x_meta_columns"]='Elaborazione di %count colonne di metadati';
$lang["csv_upload_processing_complete"]='Elaborazione completata alle %%TIME%% (%%HOURS%% ore, %%MINUTES%% minuti, %%SECONDS%% secondi)';
$lang["csv_upload_error_in_progress"]='Elaborazione annullata - questo file CSV è già in fase di elaborazione';
$lang["csv_upload_error_file_missing"]='Errore - File CSV mancante: %%FILE%%';
$lang["csv_upload_full_messages_link"]='Mostrando solo le prime 1000 righe, per scaricare l\'intero file di registro clicca <a href=\'%%LOG_URL%%\' target=\'_blank\'>qui</a>';
$lang["csv_upload_ignore_errors"]='Ignora gli errori e elabora il file comunque';
$lang["csv_upload_process_offline_quick"]='Saltare la convalida e processare il file CSV offline? Questo dovrebbe essere utilizzato solo per file CSV di grandi dimensioni quando i test su file più piccoli sono stati completati. Riceverai una notifica tramite un messaggio di ResourceSpace una volta completato il caricamento';
$lang["csv_upload_force_offline"]='Questo grande file CSV potrebbe richiedere molto tempo per essere elaborato, quindi verrà eseguito offline. Riceverai una notifica tramite un messaggio di ResourceSpace una volta completata l\'elaborazione';
$lang["csv_upload_recommend_offline"]='Questa grande CSV potrebbe richiedere molto tempo per essere elaborata. Si consiglia di abilitare i lavori offline se si necessita di elaborare grandi CSV';
$lang["csv_upload_createdfromcsvupload"]='Creato tramite il plugin di caricamento CSV';