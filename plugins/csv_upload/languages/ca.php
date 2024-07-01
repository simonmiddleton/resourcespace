<?php


$lang["csv_upload_nav_link"]='Pujada de CSV';
$lang["csv_upload_intro"]='Aquest connector et permet crear o actualitzar recursos pujant un fitxer CSV. El format del CSV és important';
$lang["csv_upload_condition1"]='<li>Assegureu-vos que el fitxer CSV estigui codificat amb <b>UTF-8 sense BOM</b>.</li>';
$lang["csv_upload_condition2"]='<li>El fitxer CSV ha de tenir una fila d\'encapçalament</li>';
$lang["csv_upload_condition3"]='Per poder pujar fitxers de recursos més tard utilitzant la funcionalitat de substitució per lots, ha d\'haver-hi una columna anomenada \'Nom original\' i cada fitxer ha de tenir un nom únic';
$lang["csv_upload_condition4"]='Tots els camps obligatoris per a qualsevol recurs creat recentment han d\'estar presents al CSV';
$lang["csv_upload_condition5"]='<li>Per a les columnes que continguin valors amb <b>comes (,)</b>, assegureu-vos de formatar-les com a tipus <b>text</b> perquè no hàgiu d\'afegir cometes (""). En desar com a fitxer CSV, assegureu-vos de marcar l\'opció de posar entre cometes les cel·les de tipus text</li>';
$lang["csv_upload_condition6"]='Podeu descarregar un exemple de fitxer CSV fent clic a <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='Per actualitzar les dades d\'un recurs existent, podeu descarregar un CSV amb les metadades existents fent clic a l\'opció \'Exportació CSV - metadades\' del menú d\'accions dels resultats de la cerca o de la col·lecció';
$lang["csv_upload_condition8"]='Podeu reutilitzar un fitxer de mapeig CSV configurat anteriorment fent clic a "Pujar fitxer de configuració CSV"';
$lang["csv_upload_error_no_permission"]='No teniu els permisos adequats per carregar un fitxer CSV';
$lang["check_line_count"]='S\'han trobat com a mínim dues files en el fitxer CSV';
$lang["csv_upload_file"]='Selecciona el fitxer CSV';
$lang["csv_upload_default"]='Per defecte';
$lang["csv_upload_error_no_header"]='No s\'ha trobat cap fila d\'encapçalament al fitxer';
$lang["csv_upload_update_existing"]='Actualitzar recursos existents? Si això no està marcat, es crearan nous recursos basats en les dades CSV';
$lang["csv_upload_update_existing_collection"]='Només actualitzar recursos en una col·lecció específica?';
$lang["csv_upload_process"]='Procés';
$lang["csv_upload_add_to_collection"]='Afegir els recursos creats recentment a la col·lecció actual?';
$lang["csv_upload_step1"]='Pas 1 - Selecciona el fitxer';
$lang["csv_upload_step2"]='Pas 2 - Opcions per defecte del recurs';
$lang["csv_upload_step3"]='Pas 3 - Assigna les columnes als camps de metadades';
$lang["csv_upload_step4"]='Pas 4 - Comprovació de les dades CSV';
$lang["csv_upload_step5"]='Pas 5 - Processant CSV';
$lang["csv_upload_update_existing_title"]='Actualitzar recursos existents';
$lang["csv_upload_update_existing_notes"]='Seleccione les opcions necessàries per actualitzar els recursos existents';
$lang["csv_upload_create_new_title"]='Crear nous recursos';
$lang["csv_upload_create_new_notes"]='Seleccione les opcions necessàries per crear nous recursos';
$lang["csv_upload_map_fields_notes"]='Alinea les columnes del CSV als camps de metadades requerits. Clicar a "Següent" comprovarà el CSV sense canviar les dades realment';
$lang["csv_upload_map_fields_auto_notes"]='Els camps de metadades s\'han preseleccionat en funció dels noms o títols, però si us plau, comproveu que són correctes';
$lang["csv_upload_workflow_column"]='Seleccioneu la columna que conté l\'ID d\'estat del flux de treball';
$lang["csv_upload_workflow_default"]='Estat de flux de treball per defecte si no s\'ha seleccionat cap columna o si no s\'ha trobat cap estat vàlid a la columna';
$lang["csv_upload_access_column"]='Seleccioneu la columna que conté el nivell d\'accés (0=Obert, 1=Restringit, 2=Confidencial)';
$lang["csv_upload_access_default"]='Nivell d\'accés per defecte si no s\'ha seleccionat cap columna o si no s\'ha trobat cap accés vàlid a la columna';
$lang["csv_upload_resource_type_column"]='Seleccioneu la columna que conté l\'identificador del tipus de recurs';
$lang["csv_upload_resource_type_default"]='Tipus de recurs per defecte si no s\'ha seleccionat cap columna o si no s\'ha trobat cap tipus vàlid a la columna';
$lang["csv_upload_resource_match_column"]='Seleccioneu la columna que conté l\'identificador del recurs';
$lang["csv_upload_match_type"]='Coincidir recurs basat en l\'ID del recurs o el valor del camp de metadades?';
$lang["csv_upload_multiple_match_action"]='Acció a prendre si es troben diversos recursos coincidents';
$lang["csv_upload_validation_notes"]='Comproveu els missatges de validació a continuació abans de continuar. Feu clic a Processar per confirmar els canvis';
$lang["csv_upload_upload_another"]='Pujar un altre CSV';
$lang["csv_upload_mapping config"]='Configuració de mapeig de columnes CSV';
$lang["csv_upload_download_config"]='Descarregar la configuració de mapeig CSV com a fitxer';
$lang["csv_upload_upload_config"]='Pujar fitxer de mapeig CSV';
$lang["csv_upload_upload_config_question"]='Pujar fitxer de mapeig CSV? Utilitzeu això si heu pujat un CSV similar abans i heu desat la configuració';
$lang["csv_upload_upload_config_set"]='Conjunt de configuració CSV';
$lang["csv_upload_upload_config_clear"]='Netejar la configuració de mapeig CSV';
$lang["csv_upload_mapping_ignore"]='NO UTILITZAR';
$lang["csv_upload_mapping_header"]='Capçalera de columna';
$lang["csv_upload_mapping_csv_data"]='Dades de mostra des d\'un fitxer CSV';
$lang["csv_upload_using_config"]='Utilitzant la configuració CSV existent';
$lang["csv_upload_process_offline"]='Processar fitxer CSV sense connexió? Això s\'hauria d\'utilitzar per a fitxers CSV grans. Se us notificarà a través d\'un missatge de ResourceSpace una vegada que el processament hagi finalitzat';
$lang["csv_upload_oj_created"]='S\'ha creat una tasca de càrrega de CSV amb l\'ID de tasca # [jobref]. <br/>Rebràs un missatge del sistema de ResourceSpace una vegada que la tasca s\'hagi completat';
$lang["csv_upload_oj_complete"]='Càrrega de fitxers CSV completa. Feu clic al link per veure el registre complet';
$lang["csv_upload_oj_failed"]='La càrrega de treball de pujada de CSV ha fallat';
$lang["csv_upload_processing_x_meta_columns"]='Processant %count columnes de metadades';
$lang["csv_upload_processing_complete"]='Processament completat a les [time] ([hours] hores, [minutes] minuts, [seconds] segons)';
$lang["csv_upload_error_in_progress"]='Processament avortat - aquest fitxer CSV ja està sent processat';
$lang["csv_upload_error_file_missing"]='Error - Fitxer CSV absent: [file]';
$lang["csv_upload_full_messages_link"]='Mostrant només les primeres 1000 línies, per descarregar el fitxer de registre complet feu clic <a href=\'[log_url]\' target=\'_blank\'>aquí</a>';
$lang["csv_upload_ignore_errors"]='Ignora els errors i processa el fitxer de tota manera';
$lang["csv_upload_process_offline_quick"]='Ometre la validació i processar el fitxer CSV sense connexió? Això només s\'hauria d\'utilitzar per a fitxers CSV grans quan s\'hagi completat la prova en fitxers més petits. Se us notificarà a través d\'un missatge de ResourceSpace una vegada que s\'hagi completat la càrrega';
$lang["csv_upload_force_offline"]='Aquest gran fitxer CSV pot trigar molt de temps a processar, per la qual cosa s\'executarà en línia. Seràs notificat a través d\'un missatge de ResourceSpace una vegada que el processament hagi finalitzat';
$lang["csv_upload_recommend_offline"]='Aquest gran CSV pot trigar molt de temps a processar. Es recomana habilitar les tasques fora de línia si necessiteu processar grans CSVs';
$lang["csv_upload_createdfromcsvupload"]='Creat per l\'extensió de càrrega de CSV';