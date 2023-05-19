<?php


$lang["status4"]='Inmutable.';
$lang["doi_info_link"]='en <a target="_blank" href="https://es.wikipedia.org/wiki/Identificador_de_objeto_digital">Identificadores de Objetos Digitales (DOI)</a>.';
$lang["doi_info_metadata_schema"]='En la registración de DOI en DataCite.org se detallan en la <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Documentación del Esquema de Metadatos de Datacite</a>.';
$lang["doi_info_mds_api"]='Las especificaciones del DOI-API utilizado por este plugin se encuentran en la <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Documentación de la API de Datacite</a>.';
$lang["doi_plugin_heading"]='Este plugin crea <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> para objetos y colecciones inmutables antes de registrarlos en <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Más información.';
$lang["doi_setup_doi_prefix"]='Prefijo para la generación de DOI.';
$lang["doi_info_prefix"]='sobre los prefijos de <a target="_blank" href="https://es.wikipedia.org/wiki/Identificador_de_objeto_digital#Nomenclatura">DOI</a>.';
$lang["doi_setup_use_testmode"]='Utilice <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">modo de prueba</a>.';
$lang["doi_info_testmode"]='en el <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">modo de prueba</a>.';
$lang["doi_setup_use_testprefix"]='Utilice el prefijo de prueba (10.5072) en su lugar <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9"> (enlace)</a>.';
$lang["doi_info_testprefix"]='en el <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">prefijo de prueba</a>.';
$lang["doi_setup_publisher"]='Editorial';
$lang["doi_info_publisher"]='en el campo <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">editorial</a>.';
$lang["doi_resource_conditions_title"]='Un recurso debe cumplir las siguientes condiciones previas para calificar para el registro DOI:';
$lang["doi_resource_conditions"]='<li>Tu proyecto debe ser público, es decir, tener un área pública.</li>
<li>El recurso debe ser accesible públicamente, es decir, tener su acceso configurado como <strong>abierto</strong>.</li>
<li>El recurso debe tener un <strong>título</strong>.</li>
<li>Debe estar marcado como {status}, es decir, tener su estado configurado como <strong>{status}</strong>.</li>
<li>Luego, solo un <strong>administrador</strong> está autorizado para iniciar el proceso de registro.</li>';
$lang["doi_setup_general_config"]='Configuración General.';
$lang["doi_setup_pref_fields_header"]='Campos de búsqueda preferidos para la construcción de metadatos.';
$lang["doi_setup_username"]='Nombre de usuario de DataCite.';
$lang["doi_setup_password"]='Contraseña de DataCite.';
$lang["doi_pref_publicationYear_fields"]='Busque <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">PublicationYear</a> en:<br>(En caso de que no se encuentre ningún valor, se utilizará el año de registro.)';
$lang["doi_pref_creator_fields"]='Buscar <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Creador</a> en:';
$lang["doi_pref_title_fields"]='Buscar <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Título</a> en:';
$lang["doi_setup_default"]='Si no se pudo encontrar ningún valor, use el <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">código estándar</a>:';
$lang["doi_setup_test_plugin"]='Plugin de prueba.';
$lang["doi_setup_test_succeeded"]='¡Prueba exitosa!';
$lang["doi_setup_test_failed"]='¡Prueba fallida!';
$lang["doi_alert_text"]='¡Atención! Una vez que el DOI se envía a DataCite, el registro no se puede deshacer.';
$lang["doi_title_compulsory"]='Por favor, establezca un título antes de continuar con el registro del DOI.';
$lang["doi_register"]='Registrar';
$lang["doi_cancel"]='Cancelar';
$lang["doi_sure"]='¡Atención! Una vez que el DOI se envía a DataCite, el registro no se puede deshacer. La información ya registrada en el Almacenamiento de Metadatos de DataCite posiblemente será sobrescrita.';
$lang["doi_already_set"]='Ya establecido.';
$lang["doi_not_yet_set"]='Aún no establecido.';
$lang["doi_already_registered"]='Ya registrado.';
$lang["doi_not_yet_registered"]='Aún no registrado.';
$lang["doi_successfully_registered"]='Se registró correctamente.';
$lang["doi_successfully_registered_pl"]='El/Los recurso(s) se registró/registraron correctamente.';
$lang["doi_not_successfully_registered"]='No se pudo registrar correctamente.';
$lang["doi_not_successfully_registered_pl"]='No se pudo registrar correctamente.';
$lang["doi_reload"]='Recargar.';
$lang["doi_successfully_set"]='se ha establecido.';
$lang["doi_not_successfully_set"]='No ha sido establecido.';
$lang["doi_sum_of"]='de';
$lang["doi_sum_already_reg"]='El recurso ya tiene un DOI. (singular)
Los recursos ya tienen un DOI. (plural)';
$lang["doi_sum_not_yet_archived"]='El recurso o los recursos no están marcados.';
$lang["doi_sum_not_yet_archived_2"]='Aún así, su acceso no está configurado como abierto.';
$lang["doi_sum_ready_for_reg"]='El/Los recurso(s) está(n) listo(s) para ser registrado(s).';
$lang["doi_sum_no_title"]='Los recursos todavía necesitan un título. Usando:';
$lang["doi_register_all"]='Registrar DOIs para todos los recursos en esta colección.';
$lang["doi_sure_register_resource"]='¿Desea proceder con el registro de x recurso(s)?';
$lang["doi_show_meta"]='Mostrar metadatos de DOI.';
$lang["doi_hide_meta"]='Ocultar metadatos DOI.';
$lang["doi_fetched_xml_from_MDS"]='Los metadatos XML actuales se pudieron obtener correctamente del almacén de metadatos de DataCite.';