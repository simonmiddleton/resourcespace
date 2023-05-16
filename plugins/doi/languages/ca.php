<?php


$lang["status4"]='No modificable.';
$lang["doi_info_link"]='en <a target="_blank" href="https://ca.wikipedia.org/wiki/Digital_Object_Identifier">Identificadors d\'Objectes Digitals (DOI)</a>.';
$lang["doi_info_metadata_schema"]='Les instruccions per a la registració de DOI a DataCite.org estan especificades a la <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Documentació de l\'Esquema de Metadades de Datacite</a>.';
$lang["doi_info_mds_api"]='Les especificacions de l\'API DOI utilitzada per aquest connector es troben a la <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Documentació de l\'API Datacite</a>.';
$lang["doi_plugin_heading"]='Aquest connector crea <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> per a objectes i col·leccions immutables abans de registrar-los a <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Més informació.';
$lang["doi_setup_doi_prefix"]='Prefix per a la generació de DOI.';
$lang["doi_info_prefix"]='sobre els prefixos <a target="_blank" href="https://ca.wikipedia.org/wiki/Identificador_d%27objecte_digital#Nomenclatura">DOI</a>.';
$lang["doi_setup_use_testmode"]='Utilitza el <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">mode de prova</a>.';
$lang["doi_info_testmode"]='en mode de prova.';
$lang["doi_setup_use_testprefix"]='Utilitza el prefix de prova <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">(10.5072)</a> en lloc de...';
$lang["doi_info_testprefix"]='a la <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">prefix de prova</a>.';
$lang["doi_setup_publisher"]='Editor';
$lang["doi_info_publisher"]='en el camp <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">editor</a>.';
$lang["doi_resource_conditions_title"]='Un recurs ha de complir les següents precondicions per qualificar per a la registració DOI:';
$lang["doi_resource_conditions"]='<li>El teu projecte ha de ser públic, és a dir, ha de tenir una àrea pública.</li>
<li>El recurs ha de ser accessible públicament, és a dir, ha de tenir el seu accés establert com a <strong>obert</strong>.</li>
<li>El recurs ha de tenir un <strong>títol</strong>.</li>
<li>Ha de ser marcat com a {status}, és a dir, ha de tenir el seu estat establert com a <strong>{status}</strong>.</li>
<li>Després, només un <strong>administrador</strong> té permís per iniciar el procés de registre.</li>';
$lang["doi_setup_general_config"]='Configuració general.';
$lang["doi_setup_pref_fields_header"]='Camps de cerca preferits per a la construcció de metadades.';
$lang["doi_setup_username"]='Nom d\'usuari de DataCite.';
$lang["doi_setup_password"]='Contrasenya de DataCite.';
$lang["doi_pref_publicationYear_fields"]='Cerca l\'<a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Any de publicació</a> a:<br>(En cas que no es pugui trobar cap valor, s\'utilitzarà l\'any de registre.)';
$lang["doi_pref_creator_fields"]='Cerca el <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creador</a> a:';
$lang["doi_pref_title_fields"]='Cerca el <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Títol</a> a:';
$lang["doi_setup_default"]='Si no es pot trobar cap valor, utilitza el <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">codi estàndard</a>:';
$lang["doi_setup_test_plugin"]='Prova del connector complementari...';
$lang["doi_setup_test_succeeded"]='Prova satisfactòria!';
$lang["doi_setup_test_failed"]='Prova fallida!';
$lang["doi_alert_text"]='Atenció! Una vegada que el DOI s\'envia a DataCite, la inscripció no es pot desfer.';
$lang["doi_title_compulsory"]='Si us plau, establiu un títol abans de continuar amb el registre del DOI.';
$lang["doi_register"]='Registre';
$lang["doi_cancel"]='Cancel·lar';
$lang["doi_sure"]='Atenció! Un cop enviat el DOI a DataCite, la inscripció no es pot desfer. La informació ja registrada en el Magatzem de Metadades de DataCite possiblement serà sobreescrita.';
$lang["doi_already_set"]='ja establert';
$lang["doi_not_yet_set"]='Encara no establert.';
$lang["doi_already_registered"]='ja registrat';
$lang["doi_not_yet_registered"]='Encara no està registrat.';
$lang["doi_successfully_registered"]='va ser registrat amb èxit';
$lang["doi_successfully_registered_pl"]='El/Les recurs(sos) s\'han registrat amb èxit.';
$lang["doi_not_successfully_registered"]='No s\'ha pogut registrar correctament.';
$lang["doi_not_successfully_registered_pl"]='No s\'ha pogut registrar correctament.';
$lang["doi_reload"]='Recarregar';
$lang["doi_successfully_set"]='s\'ha establert.';
$lang["doi_not_successfully_set"]='no ha estat establert.';
$lang["doi_sum_of"]='de';
$lang["doi_sum_already_reg"]='El recurs/recursos ja té/tenen un DOI.';
$lang["doi_sum_not_yet_archived"]='el/els recurs(sos) no estan marcats.';
$lang["doi_sum_not_yet_archived_2"]='Encara no s\'ha establert l\'accés com a obert.';
$lang["doi_sum_ready_for_reg"]='Els recursos estan llestos per a la seva registre.';
$lang["doi_sum_no_title"]='recurs(sos) encara necessiten un títol. Utilitzant';
$lang["doi_sum_no_title_2"]='Com a títol en lloc.';
$lang["doi_register_all"]='Registrar DOIs per a tots els recursos d\'aquesta col·lecció.';
$lang["doi_sure_register_resource"]='Voleu continuar registrant x recurs(sos)?';
$lang["doi_show_meta"]='Mostra les metadades del DOI.';
$lang["doi_hide_meta"]='Amaga les metadades DOI.';
$lang["doi_fetched_xml_from_MDS"]='Les metadades XML actuals s\'han pogut obtenir correctament del magatzem de metadades de DataCite.';