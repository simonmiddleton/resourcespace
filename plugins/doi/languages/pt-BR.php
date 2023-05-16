<?php


$lang["status4"]='Imutável.';
$lang["doi_info_link"]='em <a target="_blank" href="https://pt.wikipedia.org/wiki/Identificador_de_Objeto_Digital">Identificadores de Objeto Digital (DOI)</a>.';
$lang["doi_info_metadata_schema"]='As informações sobre o registro DOI no DataCite.org estão descritas na <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf">Documentação do Esquema de Metadados do Datacite</a>.';
$lang["doi_info_mds_api"]='As informações sobre a DOI-API utilizada por este plugin estão descritas na <a target="_blank" href="https://support.datacite.org/docs/mds-api-guide">Documentação da API Datacite</a>.';
$lang["doi_plugin_heading"]='Este plugin cria <a target="_blank" href="https://en.wikipedia.org/wiki/Digital_Object_Identifier">DOIs</a> para objetos e coleções imutáveis antes de registrá-los no <a target="_blank" href="https://www.datacite.org/about-datacite">DataCite</a>.';
$lang["doi_further_information"]='Mais informações.';
$lang["doi_setup_doi_prefix"]='Prefixo para geração de DOI (Identificador de Objeto Digital).';
$lang["doi_info_prefix"]='sobre os prefixos <a target="_blank" href="https://pt.wikipedia.org/wiki/Identificador_de_Objeto_Digital#Nomenclatura">doi</a>.';
$lang["doi_setup_use_testmode"]='Utilize o <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">modo de teste</a>.';
$lang["doi_info_testmode"]='no modo de teste.';
$lang["doi_setup_use_testprefix"]='Utilize o prefixo de teste <a target="_blank" href="https://mds.datacite.org/static/apidoc#tocAnchor-9">(10.5072)</a> ao invés disso.';
$lang["doi_info_testprefix"]='no prefixo de teste.';
$lang["doi_setup_publisher"]='Editora.';
$lang["doi_info_publisher"]='no campo <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">editora</a>.';
$lang["doi_resource_conditions_title"]='Um recurso precisa cumprir as seguintes condições prévias para se qualificar para o registro de DOI:';
$lang["doi_resource_conditions"]='<li>Seu projeto precisa ser público, ou seja, ter uma área pública.</li>
<li>O recurso deve ser acessível publicamente, ou seja, ter seu acesso definido como <strong>aberto</strong>.</li>
<li>O recurso deve ter um <strong>título</strong>.</li>
<li>Ele deve ser marcado como {status}, ou seja, ter seu estado definido como <strong>{status}</strong>.</li>
<li>Então, somente um <strong>administrador</strong> está autorizado a iniciar o processo de registro.</li>';
$lang["doi_setup_general_config"]='Configuração Geral.';
$lang["doi_setup_pref_fields_header"]='Campos de busca preferidos para construção de metadados.';
$lang["doi_setup_username"]='Nome de usuário do DataCite.';
$lang["doi_setup_password"]='Senha do DataCite.';
$lang["doi_pref_publicationYear_fields"]='Procure o <a target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=10">Ano de Publicação</a> em:<br>(Caso nenhum valor seja encontrado, o ano de registro será utilizado.)';
$lang["doi_pref_creator_fields"]='Procure o <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Criador</a> em:';
$lang["doi_pref_title_fields"]='Procure <a style="font-style: italic" target="_blank" href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=9">Título</a> em:';
$lang["doi_setup_default"]='Se nenhum valor puder ser encontrado, use o <a href="https://schema.datacite.org/meta/kernel-3/doc/DataCite-MetadataKernel_v3.1.pdf#page=38" target="_blank">código padrão</a>:';
$lang["doi_setup_test_plugin"]='Plugin de teste...';
$lang["doi_setup_test_succeeded"]='Teste realizado com sucesso!';
$lang["doi_setup_test_failed"]='Teste falhou!';
$lang["doi_alert_text"]='Atenção! Uma vez que o DOI é enviado para o DataCite, o registro não pode ser desfeito.';
$lang["doi_title_compulsory"]='Por favor, defina um título antes de continuar o registro do DOI.';
$lang["doi_register"]='Registrar';
$lang["doi_cancel"]='Cancelar';
$lang["doi_sure"]='Atenção! Uma vez que o DOI é enviado para o DataCite, o registro não pode ser desfeito. As informações já registradas no DataCite Metadata Store possivelmente serão sobrescritas.';
$lang["doi_already_set"]='já definido';
$lang["doi_not_yet_set"]='Ainda não definido.';
$lang["doi_already_registered"]='já registrado';
$lang["doi_not_yet_registered"]='Ainda não registrado.';
$lang["doi_successfully_registered"]='foi registrado com sucesso';
$lang["doi_successfully_registered_pl"]='Recurso(s) foi/foram registrado(s) com sucesso.';
$lang["doi_not_successfully_registered"]='Não foi possível registrar corretamente.';
$lang["doi_not_successfully_registered_pl"]='Não foi possível registrar corretamente.';
$lang["doi_reload"]='Recarregar.';
$lang["doi_successfully_set"]='foi definido.';
$lang["doi_not_successfully_set"]='Não foi definido.';
$lang["doi_sum_of"]='de';
$lang["doi_sum_not_yet_archived"]='Recurso(s) não está(ão) marcado(s).';
$lang["doi_sum_not_yet_archived_2"]='Ainda assim, o acesso não está definido como aberto.';
$lang["doi_sum_ready_for_reg"]='Recurso(s) está/estão pronto(s) para registro.';
$lang["doi_sum_no_title"]='recursos ainda precisam de um título. Usando...';
$lang["doi_sum_no_title_2"]='como um título em vez disso.';
$lang["doi_register_all"]='Registrar DOIs para todos os recursos nesta coleção.';
$lang["doi_sure_register_resource"]='Prosseguir registrando x recurso(s)?';
$lang["doi_show_meta"]='Mostrar metadados DOI.';
$lang["doi_hide_meta"]='Ocultar metadados DOI.';
$lang["doi_fetched_xml_from_MDS"]='Os metadados XMl atuais foram obtidos com sucesso do repositório de metadados da DataCite.';
$lang["doi_sum_already_reg"]='O(s) recurso(s) já possui(em) um DOI.';