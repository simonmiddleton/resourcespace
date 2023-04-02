<?php


$lang["museumplus_configuration"]='Configuração do MuseumPlus.';
$lang["museumplus_top_menu_title"]='MuseumPlus: associações inválidas.';
$lang["museumplus_api_settings_header"]='Detalhes da API.';
$lang["museumplus_host"]='Anfitrião.';
$lang["museumplus_host_api"]='API Host (apenas para chamadas de API; geralmente o mesmo que acima)';
$lang["museumplus_application"]='Nome da aplicação.';
$lang["user"]='Usuário.';
$lang["museumplus_api_user"]='Usuário.';
$lang["password"]='Senha';
$lang["museumplus_api_pass"]='Senha';
$lang["museumplus_RS_settings_header"]='Configurações do ResourceSpace.';
$lang["museumplus_mpid_field"]='Campo de metadados usado para armazenar o identificador do MuseumPlus (MpID).';
$lang["museumplus_module_name_field"]='Campo de metadados usado para armazenar o nome dos módulos para os quais o MpID é válido. Se não estiver definido, o plugin usará a configuração do módulo "Objeto" como alternativa.';
$lang["museumplus_secondary_links_field"]='Campo de metadados usado para armazenar os links secundários para outros módulos. O ResourceSpace irá gerar um URL do MuseumPlus para cada um dos links. Os links terão um formato de sintaxe especial: nome_do_módulo:ID (por exemplo, "Objeto:1234").';
$lang["museumplus_object_details_title"]='Detalhes do MuseumPlus.';
$lang["museumplus_script_header"]='Configurações do script.';
$lang["museumplus_last_run_date"]='<div class="Questão">
    <label>
        <strong>Última execução do script</strong>
    </label>
    <input name="script_last_ran" type="text" value="%script_last_ran" disabled style="width: 420px;">
</div>
<div class="clearerleft"></div>';
$lang["museumplus_enable_script"]='Ativar script do MuseumPlus.';
$lang["museumplus_interval_run"]='Executar script no seguinte intervalo (por exemplo, +1 dia, +2 semanas, quinzenal). Deixe em branco e ele será executado toda vez que cron_copy_hitcount.php for executado.';
$lang["museumplus_log_directory"]='Diretório para armazenar registros de script. Se isso for deixado em branco ou for inválido, nenhum registro será feito.';
$lang["museumplus_integrity_check_field"]='Por favor, traduza: Campo de verificação de integridade.';
$lang["museumplus_modules_configuration_header"]='Configuração de módulos.';
$lang["museumplus_module"]='Módulo.';
$lang["museumplus_add_new_module"]='Adicionar novo módulo do MuseumPlus.';
$lang["museumplus_mplus_field_name"]='Nome do campo do MuseumPlus.';
$lang["museumplus_rs_field"]='Campo do ResourceSpace.';
$lang["museumplus_view_in_museumplus"]='Visualizar no MuseumPlus.';
$lang["museumplus_confirm_delete_module_config"]='Você tem certeza de que deseja excluir esta configuração de módulo? Esta ação não pode ser desfeita!';
$lang["museumplus_module_name"]='Nome do módulo MuseumPlus.';
$lang["museumplus_mplus_id_field"]='Nome do campo ID do MuseumPlus.';
$lang["museumplus_mplus_id_field_helptxt"]='Deixe vazio para usar o ID técnico \'__id\' (padrão)';
$lang["museumplus_rs_uid_field"]='Campo UID do ResourceSpace.';
$lang["museumplus_applicable_resource_types"]='Tipos de recurso(s) aplicáveis.';
$lang["museumplus_field_mappings"]='Mapeamentos de campos do MuseumPlus para o ResourceSpace.';
$lang["museumplus_add_mapping"]='Adicionar mapeamento.';
$lang["museumplus_error_bad_conn_data"]='Dados de conexão do MuseumPlus inválidos.';
$lang["museumplus_error_unexpected_response"]='Resposta inesperada do MuseumPlus recebida - %code.';
$lang["museumplus_error_no_data_found"]='Nenhum dado encontrado no MuseumPlus para este MpID - %mpid.';
$lang["museumplus_warning_script_not_completed"]='AVISO: O script do MuseumPlus não foi concluído desde \'%script_last_ran\'.
Você pode ignorar com segurança este aviso somente se posteriormente receber uma notificação de conclusão bem-sucedida do script.';
$lang["museumplus_error_script_failed"]='O script do MuseumPlus falhou ao ser executado porque um bloqueio de processo estava em vigor. Isso indica que a execução anterior não foi concluída.
Se você precisar limpar o bloqueio após uma execução falha, execute o script da seguinte forma:
php museumplus_script.php --clear-lock';
$lang["museumplus_php_utility_not_found"]='A opção de configuração $php_path DEVE ser definida para que a funcionalidade do cron seja executada com sucesso!';
$lang["museumplus_error_not_deleted_module_conf"]='Não é possível excluir a configuração do módulo solicitado.';
$lang["museumplus_error_unknown_type_saved_config"]='O \'museumplus_modules_saved_config\' é de um tipo desconhecido!';
$lang["museumplus_error_invalid_association"]='Associação de módulo(s) inválida. Por favor, certifique-se de que o módulo correto e/ou o ID do registro tenham sido inseridos!';
$lang["museumplus_id_returns_multiple_records"]='Vários registros encontrados - por favor, insira o ID técnico em vez disso.';
$lang["museumplus_error_module_no_field_maps"]='Não é possível sincronizar dados do MuseumPlus. Motivo: o módulo \'%name\' não possui mapeamentos de campos configurados.';
$lang["museumplus_module_setup"]='Configuração do módulo.';