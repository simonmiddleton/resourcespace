<?php


$lang["status4"]='Onveranderlijk.';
$lang["doi_info_link"]='op <a target="_blank" href="https://nl.wikipedia.org/wiki/Digital_Object_Identifier">DOI\'s</a>.';
$lang["doi_info_metadata_schema"]='Op de DOI-registratie bij DataCite.org staan ​​de gegevens vermeld in de <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Documentatie van het Datacite Metadata-schema</a>.';
$lang["doi_info_mds_api"]='De DOI-API die door deze plugin wordt gebruikt, wordt beschreven in de <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API-documentatie</a>.';
$lang["doi_plugin_heading"]='Deze plugin creëert <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> voor onveranderlijke objecten en collecties voordat ze worden geregistreerd bij <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Verdere informatie.';
$lang["doi_setup_doi_prefix"]='Voorvoegsel voor DOI-generatie.';
$lang["doi_info_prefix"]='over doi-voorvoegsels.';
$lang["doi_setup_use_testmode"]='Gebruik <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmodus</a>.';
$lang["doi_info_testmode"]='in de <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testmodus</a>.';
$lang["doi_setup_use_testprefix"]='Gebruik in plaats daarvan <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test prefix (10.5072)</a>.';
$lang["doi_info_testprefix"]='op de <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test prefix</a>.';
$lang["doi_setup_publisher"]='Uitgever';
$lang["doi_info_publisher"]='op het veld <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">uitgever</a>.';
$lang["doi_resource_conditions_title"]='Een resource moet aan de volgende voorwaarden voldoen om in aanmerking te komen voor DOI-registratie:';
$lang["doi_resource_conditions"]='<li>Je project moet openbaar zijn, dat wil zeggen dat het een openbaar gebied heeft.</li>
<li>De bron moet publiekelijk toegankelijk zijn, dat wil zeggen dat de toegang is ingesteld op <strong>open</strong>.</li>
<li>De bron moet een <strong>titel</strong> hebben.</li>
<li>Het moet worden gemarkeerd als {status}, dat wil zeggen dat de status is ingesteld op <strong>{status}</strong>.</li>
<li>Alleen een <strong>beheerder</strong> mag het registratieproces starten.</li>';
$lang["doi_setup_general_config"]='Algemene configuratie.';
$lang["doi_setup_pref_fields_header"]='Voorkeurszoekvelden voor de constructie van metadata.';
$lang["doi_setup_username"]='DataCite gebruikersnaam.';
$lang["doi_setup_password"]='DataCite wachtwoord';
$lang["doi_pref_publicationYear_fields"]='Zoek naar <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Publicatiejaar</a> in:<br>(Als er geen waarde gevonden kan worden, zal het jaar van registratie gebruikt worden.)';
$lang["doi_pref_creator_fields"]='Zoek naar <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Maker</a> in:';
$lang["doi_pref_title_fields"]='Zoek naar <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Titel</a> in:';
$lang["doi_setup_default"]='Indien er geen waarde gevonden kan worden, gebruik dan <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">standaardcode</a>:';
$lang["doi_setup_test_plugin"]='Test plugin.. 

Test plugin.. (Nederlands)';
$lang["doi_setup_test_succeeded"]='Test geslaagd!';
$lang["doi_setup_test_failed"]='Mislukt!';
$lang["doi_alert_text"]='Let op! Zodra de DOI naar DataCite is verzonden, kan de registratie niet ongedaan worden gemaakt.';
$lang["doi_title_compulsory"]='Stel alstublieft een titel in voordat u doorgaat met de DOI-registratie.';
$lang["doi_register"]='Registreren';
$lang["doi_cancel"]='Annuleren';
$lang["doi_sure"]='Let op! Zodra de DOI naar DataCite is verzonden, kan de registratie niet ongedaan worden gemaakt. Informatie die al is geregistreerd in de Metadata Store van DataCite kan mogelijk worden overschreven.';
$lang["doi_already_set"]='reeds ingesteld';
$lang["doi_not_yet_set"]='nog niet ingesteld';
$lang["doi_already_registered"]='reeds geregistreerd';
$lang["doi_not_yet_registered"]='nog niet geregistreerd';
$lang["doi_successfully_registered"]='Is succesvol geregistreerd.';
$lang["doi_successfully_registered_pl"]='Resource(s) is/zijn succesvol geregistreerd.';
$lang["doi_not_successfully_registered"]='Kon niet correct worden geregistreerd.';
$lang["doi_not_successfully_registered_pl"]='Kon niet correct worden geregistreerd.';
$lang["doi_reload"]='Herladen.';
$lang["doi_successfully_set"]='is ingesteld.';
$lang["doi_not_successfully_set"]='Is niet ingesteld.';
$lang["doi_sum_already_reg"]='Hulpbron(nen) heeft/hebben al een DOI.';
$lang["doi_sum_not_yet_archived"]='bron(nen) is/zijn niet gemarkeerd';
$lang["doi_sum_not_yet_archived_2"]='Maar de toegang is niet ingesteld op open.';
$lang["doi_sum_ready_for_reg"]='Bron(nen) is/zijn gereed voor registratie.';
$lang["doi_sum_no_title"]='bron(nen) hebben nog steeds een titel nodig. Gebruik';
$lang["doi_sum_no_title_2"]='Als titel in plaats daarvan.';
$lang["doi_register_all"]='Registreer DOIs voor alle bronnen in deze collectie.';
$lang["doi_sure_register_resource"]='Doorgaan met het registreren van x resource(s)?';
$lang["doi_show_meta"]='Toon DOI-metadata.';
$lang["doi_hide_meta"]='Verberg DOI-metadata.';
$lang["doi_fetched_xml_from_MDS"]='Huidige XML-metadata konden succesvol worden opgehaald uit de metadata-opslag van DataCite.';