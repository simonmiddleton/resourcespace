<?php


$lang["status4"]='Imutabil.';
$lang["doi_info_link"]='pe <a target="_blank" href="https://ro.wikipedia.org/wiki/Identificator_de_Obiect_Digital">Identificatorii de Obiecte Digitale (DOI)</a>.';
$lang["doi_info_metadata_schema"]='Înregistrarea DOI la DataCite.org este descrisă în <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Documentația Schema Metadata Datacite</a>.';
$lang["doi_info_mds_api"]='Pe API-DOI utilizat de acest plugin sunt specificate în <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Documentația API Datacite</a>.';
$lang["doi_plugin_heading"]='Acest plugin creează <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> pentru obiecte și colecții imutabile înainte de a le înregistra la <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Mai multe informații.';
$lang["doi_setup_doi_prefix"]='Prefix pentru generarea DOI (Identificator Obiect Digital).';
$lang["doi_info_prefix"]='pe prefixurile <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">doi</a>.';
$lang["doi_setup_use_testmode"]='Folosește <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">modul de testare</a>.';
$lang["doi_info_testmode"]='în modul de testare <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmode</a>.';
$lang["doi_setup_use_testprefix"]='Folosește prefixul de testare <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">(10.5072)</a> în loc.';
$lang["doi_info_testprefix"]='pe prefixul de test <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9"></a>.';
$lang["doi_setup_publisher"]='Editură.';
$lang["doi_info_publisher"]='vă rugăm să traduceți: câmpul <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">editorului</a>.';
$lang["doi_resource_conditions_title"]='Un resurs trebuie să îndeplinească următoarele condiții prealabile pentru a se califica pentru înregistrarea DOI:';
$lang["doi_resource_conditions"]='<li>Proiectul tău trebuie să fie public, adică să aibă o zonă publică.</li>
<li>Resursa trebuie să fie accesibilă public, adică să aibă setat accesul la <strong>deschis</strong>.</li>
<li>Resursa trebuie să aibă un <strong>titlu</strong>.</li>
<li>Trebuie să fie marcată ca {status}, adică starea să fie setată la <strong>{status}</strong>.</li>
<li>Apoi, doar un <strong>administrator</strong> are permisiunea să inițieze procesul de înregistrare.</li>';
$lang["doi_setup_general_config"]='Configurare generală.';
$lang["doi_setup_pref_fields_header"]='Câmpurile de căutare preferate pentru construcția metadatelor.';
$lang["doi_setup_username"]='Nume de utilizator DataCite.';
$lang["doi_setup_password"]='Parolă DataCite.';
$lang["doi_pref_publicationYear_fields"]='Căutați <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">AnulPublicării</a> în:<br>(În cazul în care nu se poate găsi nicio valoare, va fi utilizat anul înregistrării.)';
$lang["doi_pref_creator_fields"]='Căutați <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creator</a> în:';
$lang["doi_pref_title_fields"]='Căutați <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Titlu</a> în:';
$lang["doi_setup_default"]='Dacă nu s-a putut găsi nicio valoare, utilizați <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">codul standard</a>:';
$lang["doi_setup_test_plugin"]='Plugin de testare.';
$lang["doi_setup_test_succeeded"]='Testul a fost realizat cu succes!';
$lang["doi_setup_test_failed"]='Testul a eșuat!';
$lang["doi_alert_text"]='Atenție! Odată ce DOI-ul este trimis către DataCite, înregistrarea nu poate fi anulată.';
$lang["doi_title_compulsory"]='Vă rugăm să setați un titlu înainte de a continua înregistrarea DOI.';
$lang["doi_register"]='Înregistrare.';
$lang["doi_cancel"]='Anulați.';
$lang["doi_sure"]='Atenție! Odată ce DOI-ul este trimis către DataCite, înregistrarea nu poate fi anulată. Informațiile deja înregistrate în Magazinul de Metadate DataCite vor fi posibil suprascrise.';
$lang["doi_already_set"]='deja setat';
$lang["doi_not_yet_set"]='încă nu a fost setat.';
$lang["doi_already_registered"]='deja înregistrat';
$lang["doi_not_yet_registered"]='încă neînregistrat';
$lang["doi_successfully_registered"]='a fost înregistrat cu succes';
$lang["doi_successfully_registered_pl"]='Resursa/resursele au fost înregistrate cu succes.';
$lang["doi_not_successfully_registered"]='nu a putut fi înregistrat corect';
$lang["doi_not_successfully_registered_pl"]='Nu a putut fi înregistrat corect.';
$lang["doi_reload"]='Reîncarcă.';
$lang["doi_successfully_set"]='a fost setat.';
$lang["doi_not_successfully_set"]='Nu a fost setat.';
$lang["doi_sum_of"]='a (preposition)';
$lang["doi_sum_already_reg"]='Resursa/resursele au deja un DOI.';
$lang["doi_sum_not_yet_archived"]='resursa/resursele nu sunt marcate.';
$lang["doi_sum_not_yet_archived_2"]='Încă accesul său/lor nu este setat ca fiind deschis.';
$lang["doi_sum_ready_for_reg"]='Resursele sunt pregătite pentru înregistrare.';
$lang["doi_sum_no_title"]='resursele încă au nevoie de un titlu. Utilizând...';
$lang["doi_sum_no_title_2"]='Vă rog să traduceți: "as a title instead then."';
$lang["doi_register_all"]='Înregistrați DOI-uri pentru toate resursele din această colecție.';
$lang["doi_sure_register_resource"]='Continuați înregistrarea a x resurse?';
$lang["doi_show_meta"]='Afișează metadatele DOI.';
$lang["doi_hide_meta"]='Ascundeți metadatele DOI.';
$lang["doi_fetched_xml_from_MDS"]='Metadatele XMl curente au fost preluate cu succes din depozitul de metadate DataCite.';