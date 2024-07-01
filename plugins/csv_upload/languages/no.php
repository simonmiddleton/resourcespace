<?php


$lang["csv_upload_nav_link"]='CSV-opplasting';
$lang["csv_upload_intro"]='Dette tillegget lar deg opprette eller oppdatere ressurser ved å laste opp en CSV-fil. Formatet på CSV-filen er viktig';
$lang["csv_upload_condition1"]='Sørg for at CSV-filen er kodet med <b>UTF-8 uten BOM</b>.';
$lang["csv_upload_condition2"]='CSV-filen må ha en overskriftsrad';
$lang["csv_upload_condition3"]='For å kunne laste opp ressursfiler senere ved hjelp av funksjonaliteten for masseerstattning, bør det være en kolonne som heter \'Opprinnelig filnavn\', og hver fil bør ha et unikt filnavn.';
$lang["csv_upload_condition4"]='Alle obligatoriske felt for nyopprettede ressurser må være til stede i CSV-filen';
$lang["csv_upload_condition5"]='For kolonner som har verdier som inneholder <b>kommategn (,)</b>, sørg for å formatere det som type <b>tekst</b> slik at du ikke trenger å legge til anførselstegn (""). Når du lagrer som en csv-fil, må du sørge for å sjekke alternativet for å sitere celler av teksttype.';
$lang["csv_upload_condition6"]='Du kan laste ned et CSV-fil eksempel ved å klikke på <a href="../downloads/csv_upload_example.csv">csv-upload-example.csv</a>';
$lang["csv_upload_condition7"]='For å oppdatere eksisterende ressursdata kan du laste ned en CSV-fil med eksisterende metadata ved å klikke på \'CSV-eksport - metadata\'-alternativet fra samlingen eller søkeresultatene i handlingsmenyen';
$lang["csv_upload_condition8"]='Du kan gjenbruke en tidligere konfigurert CSV-mappingfil ved å klikke på \'Last opp CSV-konfigurasjonsfil\'';
$lang["csv_upload_error_no_permission"]='Du har ikke tilstrekkelige tillatelser til å laste opp en CSV-fil';
$lang["check_line_count"]='Minst to rader funnet i CSV-filen';
$lang["csv_upload_file"]='Velg CSV-fil';
$lang["csv_upload_default"]='Standardinnstilling';
$lang["csv_upload_error_no_header"]='Ingen overskriftsrad funnet i filen';
$lang["csv_upload_update_existing"]='Oppdatere eksisterende ressurser? Hvis dette ikke er huket av vil nye ressurser bli opprettet basert på CSV-dataen';
$lang["csv_upload_update_existing_collection"]='Bare oppdater ressurser i en spesifikk samling?';
$lang["csv_upload_process"]='Behandling';
$lang["csv_upload_add_to_collection"]='Legg til nylig opprettede ressurser i gjeldende samling?';
$lang["csv_upload_step1"]='Trinn 1 - Velg fil';
$lang["csv_upload_step2"]='Trinn 2 - Standard ressursalternativer';
$lang["csv_upload_step3"]='Trinn 3 - Kartlegg kolonner til metadatafelt';
$lang["csv_upload_step4"]='Trinn 4 - Kontrollere CSV-data';
$lang["csv_upload_step5"]='Trinn 5 - Behandling av CSV';
$lang["csv_upload_update_existing_title"]='Oppdater eksisterende ressurser';
$lang["csv_upload_update_existing_notes"]='Velg alternativene som kreves for å oppdatere eksisterende ressurser';
$lang["csv_upload_create_new_title"]='Opprett nye ressurser';
$lang["csv_upload_create_new_notes"]='Velg alternativene som kreves for å opprette nye ressurser';
$lang["csv_upload_map_fields_notes"]='Sammenlign kolonnene i CSV-filen med de nødvendige metadatafeltene. Klikk på "Neste" for å sjekke CSV-filen uten å endre dataene faktisk';
$lang["csv_upload_map_fields_auto_notes"]='Metadatafeltene er forhåndsvalgt basert på navn eller titler, men vennligst sjekk at disse er korrekte';
$lang["csv_upload_workflow_column"]='Velg kolonnen som inneholder arbeidsflytstatus-IDen';
$lang["csv_upload_workflow_default"]='Standard arbeidsflytstatus hvis ingen kolonne er valgt eller hvis ingen gyldig status er funnet i kolonnen';
$lang["csv_upload_access_column"]='Velg kolonnen som inneholder tilgangsnivået (0=Åpen, 1=Begrenset, 2=Konfidensielt)';
$lang["csv_upload_access_default"]='Standard tilgangsnivå hvis ingen kolonne er valgt eller hvis ingen gyldig tilgang er funnet i kolonnen';
$lang["csv_upload_resource_type_column"]='Velg kolonnen som inneholder ressurstypeidentifikatoren';
$lang["csv_upload_resource_type_default"]='Standard ressurstype hvis ingen kolonne er valgt eller hvis ingen gyldig type finnes i kolonnen';
$lang["csv_upload_resource_match_column"]='Velg kolonnen som inneholder ressursidentifikatoren';
$lang["csv_upload_match_type"]='Sammenlign ressurs basert på ressurs-ID eller verdi i metadatafelt?';
$lang["csv_upload_multiple_match_action"]='Handling av flere treffende ressurser';
$lang["csv_upload_validation_notes"]='Sjekk valideringsmeldingene nedenfor før du fortsetter. Klikk på Prosesser for å bekrefte endringene';
$lang["csv_upload_upload_another"]='Last opp en annen CSV-fil';
$lang["csv_upload_mapping config"]='CSV kolonnekartleggingsinnstillinger';
$lang["csv_upload_download_config"]='Last ned CSV-mappinginnstillinger som fil';
$lang["csv_upload_upload_config"]='Last opp CSV-mappingfil';
$lang["csv_upload_upload_config_question"]='Last opp CSV-mappingfil? Bruk dette hvis du har lastet opp en lignende CSV tidligere og har lagret konfigurasjonen';
$lang["csv_upload_upload_config_set"]='CSV konfigurasjonssett';
$lang["csv_upload_upload_config_clear"]='Tøm CSV-mapping konfigurasjonen';
$lang["csv_upload_mapping_ignore"]='IKKE BRUK';
$lang["csv_upload_mapping_header"]='Kolonnetittel';
$lang["csv_upload_mapping_csv_data"]='Eksempeldata fra CSV';
$lang["csv_upload_using_config"]='Bruk av eksisterende CSV-konfigurasjon';
$lang["csv_upload_process_offline"]='Behandle CSV-fil offline? Dette bør brukes for store CSV-filer. Du vil bli varslet via en ResourceSpace-melding når behandlingen er fullført';
$lang["csv_upload_oj_created"]='CSV-opplastingsjobb opprettet med jobb-ID # [jobref]. <br/>Du vil motta en ResourceSpace-systemmelding når jobben er fullført';
$lang["csv_upload_oj_complete"]='CSV-opplastingsjobb fullført. Klikk på lenken for å se hele loggfilen';
$lang["csv_upload_oj_failed"]='CSV-opplastingsjobb mislyktes';
$lang["csv_upload_processing_x_meta_columns"]='Behandler %count metadatakolonner';
$lang["csv_upload_processing_complete"]='Behandling fullført kl. [time] ([hours] timer, [minutes] minutter, [seconds] sekunder)';
$lang["csv_upload_error_in_progress"]='Behandling avbrutt - denne CSV-filen blir allerede behandlet';
$lang["csv_upload_error_file_missing"]='Feil - CSV-fil mangler: [file]';
$lang["csv_upload_full_messages_link"]='Viser kun de første 1000 linjene. For å laste ned hele loggfilen, klikk <a href=\'[log_url]\' target=\'_blank\'>her</a>';
$lang["csv_upload_ignore_errors"]='Ignorer feil og prosesser filen uansett';
$lang["csv_upload_process_offline_quick"]='Hopp over validering og prosesser CSV-filen offline? Dette bør kun brukes for store CSV-filer når testing på mindre filer er fullført. Du vil bli varslet via en ResourceSpace-melding når opplastingen er fullført';
$lang["csv_upload_force_offline"]='Denne store CSV-filen kan ta lang tid å behandle, så den vil bli kjørt offline. Du vil bli varslet via en ResourceSpace-melding når behandlingen er fullført';
$lang["csv_upload_recommend_offline"]='Denne store CSV-filen kan ta veldig lang tid å behandle. Det anbefales å aktivere offline-jobber hvis du trenger å behandle store CSV-filer';
$lang["csv_upload_createdfromcsvupload"]='Opprettet fra CSV-opplastingsprogramtillegget';