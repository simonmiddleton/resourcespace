<?php


$lang["status4"]='Neměnný';
$lang["doi_info_wikipedia"]='https://cs.wikipedia.org/wiki/Digital_Object_Identifier';
$lang["doi_info_link"]='na <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>.';
$lang["doi_info_metadata_schema"]='v registraci DOI na DataCite.org jsou uvedeny v <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">dokumentaci Datacite Metadata Schema</a>.';
$lang["doi_info_mds_api"]='v DOI-API používaném tímto pluginem jsou uvedeny v <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Dokumentaci API Datacite</a>.';
$lang["doi_plugin_heading"]='Tento plugin vytváří <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a> pro neměnné objekty a kolekce před jejich registrací na <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Další informace';
$lang["doi_setup_doi_prefix"]='<a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">Předpona</a> pro generování <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOI</a>';
$lang["doi_info_prefix"]='na <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_object_identifier#Nomenclature">předponách DOI</a>.';
$lang["doi_setup_use_testmode"]='Použijte <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testovací režim</a>';
$lang["doi_info_testmode"]='v <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testovacím režimu</a>.';
$lang["doi_setup_use_testprefix"]='Použijte <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testovací předponu (10.5072)</a> místo toho';
$lang["doi_info_testprefix"]='na <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">testovacím prefixu</a>.';
$lang["doi_setup_publisher"]='<a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Vydavatel</a>';
$lang["doi_info_publisher"]='v poli <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">vydavatel</a>.';
$lang["doi_resource_conditions_title"]='Pro registraci DOI musí zdroj splňovat následující předpoklady:';
$lang["doi_resource_conditions"]='<li>Váš projekt musí být veřejný, to znamená, že má veřejnou oblast.</li>
<li>Zdroj musí být veřejně přístupný, to znamená, že jeho přístup je nastaven na <strong>otevřený</strong>.</li>
<li>Zdroj musí mít <strong>název</strong>.</li>
<li>Musí být označen {status}, to znamená, že jeho stav je nastaven na <strong>{status}</strong>.</li>
<li>Poté může proces registrace zahájit pouze <strong>administrátor</strong>.</li>';
$lang["doi_setup_general_config"]='Obecná konfigurace';
$lang["doi_setup_pref_fields_header"]='Preferovaná vyhledávací pole pro konstrukci metadat';
$lang["doi_setup_username"]='Uživatelské jméno DataCite';
$lang["doi_setup_password"]='Heslo DataCite';
$lang["doi_pref_publicationYear_fields"]='Hledejte <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Rok vydání</a> v:<br>(V případě, že nebude nalezena žádná hodnota, bude použit rok registrace.)';
$lang["doi_pref_creator_fields"]='Hledejte <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Tvůrce</a> v:';
$lang["doi_pref_title_fields"]='Hledejte <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Název</a> v:';
$lang["doi_setup_default"]='Pokud nebyla nalezena žádná hodnota, použijte <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">standardní kód</a>:';
$lang["doi_setup_test_plugin"]='Testovací plugin.';
$lang["doi_setup_test_succeeded"]='Test úspěšný!';
$lang["doi_setup_test_failed"]='Test selhal!';
$lang["doi_alert_text"]='Pozor! Jakmile je DOI odesláno do DataCite, registrace nelze vrátit zpět.';
$lang["doi_title_compulsory"]='Prosím nastavte název před pokračováním v registraci DOI.';
$lang["doi_register"]='Registrovat se';
$lang["doi_cancel"]='Zrušit';
$lang["doi_sure"]='Pozor! Jakmile je DOI odesláno do DataCite, registrace nemůže být zrušena. Informace již zaregistrované v Metadata Store DataCite mohou být přepsány.';
$lang["doi_already_set"]='již nastaveno';
$lang["doi_not_yet_set"]='zatím nenastaveno';
$lang["doi_already_registered"]='již registrován';
$lang["doi_not_yet_registered"]='ještě nezaregistrován';
$lang["doi_successfully_registered"]='byl úspěšně zaregistrován';
$lang["doi_successfully_registered_pl"]='zdroj(e) byl(y) úspěšně zaregistrován(y).';
$lang["doi_not_successfully_registered"]='nemohlo být správně zaregistrováno';
$lang["doi_not_successfully_registered_pl"]='nemohlo být správně zaregistrováno';
$lang["doi_reload"]='Načíst znovu';
$lang["doi_successfully_set"]='bylo nastaveno.';
$lang["doi_not_successfully_set"]='nebylo nastaveno';
$lang["doi_sum_of"]='z';
$lang["doi_sum_already_reg"]='zdroj(e) již má(mají) DOI.';
$lang["doi_sum_not_yet_archived"]='zdroj(e) není/nejsou označený/é';
$lang["doi_sum_not_yet_archived_2"]='ještě nebo jejich přístup není nastaven na otevřený.';
$lang["doi_sum_ready_for_reg"]='zdroj(e) je/jsou připraven(y) k registraci.';
$lang["doi_sum_no_title"]='zdroj(e) stále potřebují název. Používání';
$lang["doi_sum_no_title_2"]='jako název místo toho.';
$lang["doi_register_all"]='Zaregistrujte DOI pro všechny zdroje v této kolekci';
$lang["doi_sure_register_resource"]='Pokračovat v registraci x zdrojů?';
$lang["doi_show_meta"]='Zobrazit metadata DOI';
$lang["doi_hide_meta"]='Skrýt metadata DOI';
$lang["doi_fetched_xml_from_MDS"]='Aktuální XML metadata byla úspěšně načtena z úložiště metadat DataCite.';