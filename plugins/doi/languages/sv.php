<?php


$lang["status4"]='Omutlig.';
$lang["doi_info_link"]='på <a target="_blank" href="https://sv.wikipedia.org/wiki/Digital_Object_Identifier">DOI:er</a>.';
$lang["doi_info_metadata_schema"]='På DOI-registreringen på DataCite.org anges i <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Datacite Metadata Schema Documentation</a>.';
$lang["doi_info_mds_api"]='På DOI-API:et som används av denna plugin anges i <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API-dokumentationen</a>.';
$lang["doi_plugin_heading"]='Detta tillägg skapar <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> för oföränderliga objekt och samlingar innan de registreras på <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Ytterligare information.';
$lang["doi_setup_doi_prefix"]='Prefix för DOI-generering.';
$lang["doi_info_prefix"]='på <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">doi-prefix</a>.';
$lang["doi_setup_use_testmode"]='Använd <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testläge</a>.';
$lang["doi_info_testmode"]='på <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testläge</a>.';
$lang["doi_setup_use_testprefix"]='Använd <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testprefix (10.5072)</a> istället.';
$lang["doi_info_testprefix"]='på <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testprefixet</a>.';
$lang["doi_setup_publisher"]='Utgivare';
$lang["doi_info_publisher"]='på fältet för <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">utgivare</a>.';
$lang["doi_resource_conditions_title"]='En resurs måste uppfylla följande förutsättningar för att kvalificera sig för DOI-registrering:';
$lang["doi_resource_conditions"]='<li>Ditt projekt måste vara publikt, det vill säga ha ett offentligt område.</li>
<li>Resursen måste vara offentligt tillgänglig, det vill säga ha sin åtkomst inställd på <strong>öppen</strong>.</li>
<li>Resursen måste ha en <strong>titel</strong>.</li>
<li>Den måste vara markerad som {status}, det vill säga ha sitt tillstånd inställt på <strong>{status}</strong>.</li>
<li>Därefter är det endast en <strong>administratör</strong> som får initiera registreringsprocessen.</li>';
$lang["doi_setup_general_config"]='Allmän konfiguration.';
$lang["doi_setup_pref_fields_header"]='Föredragna sökfält för konstruktion av metadata.';
$lang["doi_setup_username"]='DataCite användarnamn.';
$lang["doi_setup_password"]='DataCite lösenord.';
$lang["doi_pref_publicationYear_fields"]='Sök efter <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Publiceringsår</a> i:<br>(Om ingen värde kunde hittas, kommer registreringsåret att användas.)';
$lang["doi_pref_creator_fields"]='Sök efter <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Skapare</a> i:';
$lang["doi_pref_title_fields"]='Sök efter <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Titel</a> i:';
$lang["doi_setup_default"]='Om inget värde kunde hittas, använd <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">standardkod</a>:';
$lang["doi_setup_test_plugin"]='Testa tillägg..';
$lang["doi_setup_test_succeeded"]='Testet lyckades!';
$lang["doi_setup_test_failed"]='Testet misslyckades!';
$lang["doi_alert_text"]='Observera! När DOI har skickats till DataCite kan registreringen inte ångras.';
$lang["doi_title_compulsory"]='Var god ange en titel innan du fortsätter med DOI-registreringen.';
$lang["doi_register"]='Registrera';
$lang["doi_cancel"]='Avbryt';
$lang["doi_sure"]='Observera! När DOI har skickats till DataCite kan registreringen inte ångras. Informationen som redan har registrerats i DataCites Metadata Store kan eventuellt skrivas över.';
$lang["doi_already_set"]='redan inställd';
$lang["doi_not_yet_set"]='inte satt ännu';
$lang["doi_already_registered"]='redan registrerad';
$lang["doi_not_yet_registered"]='inte registrerad än';
$lang["doi_successfully_registered"]='registrerades framgångsrikt.';
$lang["doi_successfully_registered_pl"]='Resurs(er) har registrerats framgångsrikt.';
$lang["doi_not_successfully_registered"]='kunde inte registreras korrekt';
$lang["doi_not_successfully_registered_pl"]='kunde inte registreras korrekt.';
$lang["doi_reload"]='Ladda om.';
$lang["doi_successfully_set"]='har ställts in.';
$lang["doi_not_successfully_set"]='har inte ställts in.';
$lang["doi_sum_of"]='av';
$lang["doi_sum_already_reg"]='Resursen/resurserna har redan en DOI.';
$lang["doi_sum_not_yet_archived"]='resurs(er) är inte markerad(e)';
$lang["doi_sum_not_yet_archived_2"]='Ännu är inte dess/deras åtkomst inställd på öppen.';
$lang["doi_sum_ready_for_reg"]='Resurs(er) är redo för registrering.';
$lang["doi_sum_no_title"]='resurs(er) behöver fortfarande en titel. Använder';
$lang["doi_sum_no_title_2"]='som en titel istället.';
$lang["doi_register_all"]='Registrera DOIs för alla resurser i denna samling.';
$lang["doi_sure_register_resource"]='Fortsätt att registrera x resurs(er)?';
$lang["doi_show_meta"]='Visa DOI-metadata.';
$lang["doi_hide_meta"]='Dölj DOI-metadata.';
$lang["doi_fetched_xml_from_MDS"]='Nuvarande XML-metadata kunde hämtas framgångsrikt från DataCites metadata-lagring.';