<?php


$lang["status4"]='Nepromjenjiv.';
$lang["doi_info_link"]='na <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI-ima</a>.';
$lang["doi_info_metadata_schema"]='Na registraciji DOI-a na DataCite.org navedeno je u <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Dokumentaciji o shemi metapodataka Datacite</a>.';
$lang["doi_info_mds_api"]='Na DOI-API koju koristi ovaj dodatak navedeno je u <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Datacite API dokumentaciji</a>.';
$lang["doi_plugin_heading"]='Ovaj dodatak stvara <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI-ove</a> za nepromjenjive objekte i zbirke prije njihove registracije na <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Dodatne informacije.';
$lang["doi_setup_doi_prefix"]='Prefiks za generiranje DOI-a.';
$lang["doi_info_prefix"]='o <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">DOI prefiksi</a>.';
$lang["doi_setup_use_testmode"]='Koristite <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testni način</a>.';
$lang["doi_info_testmode"]='u <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test modu</a>.';
$lang["doi_setup_use_testprefix"]='Koristite <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testni prefiks (10.5072)</a> umjesto.';
$lang["doi_info_testprefix"]='na <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">test prefiksu</a>.';
$lang["doi_setup_publisher"]='Izdavač';
$lang["doi_info_publisher"]='na polju <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">izdavača</a>.';
$lang["doi_resource_conditions_title"]='Resurs mora ispuniti sljedeće preduvjete kako bi se kvalificirao za registraciju DOI-a:';
$lang["doi_resource_conditions"]='<li>Vaš projekt mora biti javan, odnosno imati javno područje.</li>
<li>Resurs mora biti javno dostupan, odnosno njegov pristup postavljen na <strong>otvoreno</strong>.</li>
<li>Resurs mora imati <strong>naziv</strong>.</li>
<li>Mora biti označen kao {status}, odnosno njegovo stanje postavljeno na <strong>{status}</strong>.</li>
<li>Tada samo <strong>administrator</strong> ima dopuštenje pokrenuti postupak registracije.</li>';
$lang["doi_setup_general_config"]='Opća konfiguracija';
$lang["doi_setup_pref_fields_header"]='Preferirana polja pretrage za izgradnju metapodataka.';
$lang["doi_setup_username"]='Korisničko ime DataCite.';
$lang["doi_setup_password"]='DataCite lozinka.';
$lang["doi_pref_publicationYear_fields"]='Traži <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Godinu objave</a> u:<br>(U slučaju da vrijednost nije pronađena, bit će korištena godina registracije.)';
$lang["doi_pref_creator_fields"]='Traži <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Autora</a> u:';
$lang["doi_pref_title_fields"]='Traži <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Naslov</a> u:';
$lang["doi_setup_default"]='Ako vrijednost nije pronađena, koristite <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">standardni kod</a>:';
$lang["doi_setup_test_plugin"]='Testni dodatak.';
$lang["doi_setup_test_succeeded"]='Test uspješan!';
$lang["doi_setup_test_failed"]='Test nije uspio!';
$lang["doi_alert_text"]='Pozor! Jednom kada se DOI pošalje na DataCite, registracija se ne može poništiti.';
$lang["doi_title_compulsory"]='Molimo postavite naslov prije nastavka registracije DOI-a.';
$lang["doi_register"]='Registracija';
$lang["doi_cancel"]='Otkaži';
$lang["doi_sure"]='Pažnja! Jednom kada se DOI pošalje na DataCite, registracija se ne može poništiti. Informacije koje su već registrirane u DataCite-ovoj Metadata Store moguće je prebrisati.';
$lang["doi_already_set"]='već postavljeno';
$lang["doi_not_yet_set"]='još nije postavljeno';
$lang["doi_already_registered"]='već registriran';
$lang["doi_not_yet_registered"]='još nije registriran';
$lang["doi_successfully_registered"]='uspješno je registriran';
$lang["doi_successfully_registered_pl"]='Resurs(i) su uspješno registrirani.';
$lang["doi_not_successfully_registered"]='Nije se moglo registrirati ispravno.';
$lang["doi_not_successfully_registered_pl"]='Nije se moglo registrirati ispravno.';
$lang["doi_reload"]='Ponovno učitaj';
$lang["doi_successfully_set"]='postavljeno je.';
$lang["doi_not_successfully_set"]='nije postavljeno.';
$lang["doi_sum_already_reg"]='Resurs(i) već ima/ju DOI.';
$lang["doi_sum_not_yet_archived"]='resurs(i) nisu označeni';
$lang["doi_sum_not_yet_archived_2"]='Još uvijek ili njihov pristup nije postavljen na otvoreno.';
$lang["doi_sum_ready_for_reg"]='Resurs(i) su spremni za registraciju.';
$lang["doi_sum_no_title"]='resurs(i) još uvijek trebaju naslov. Upotreba:';
$lang["doi_sum_no_title_2"]='kao naslov umjesto toga.';
$lang["doi_register_all"]='Registrirajte DOI-ove za sve resurse u ovoj kolekciji.';
$lang["doi_sure_register_resource"]='Nastaviti s registracijom x resurs(a)?';
$lang["doi_show_meta"]='Prikaži DOI metapodatke.';
$lang["doi_hide_meta"]='Sakrij DOI metapodatke.';
$lang["doi_fetched_xml_from_MDS"]='Trenutni XML metapodaci uspješno su dohvaćeni iz DataCite-ove pohrane metapodataka.';