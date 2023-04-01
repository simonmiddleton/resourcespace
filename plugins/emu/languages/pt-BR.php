<?php


$lang["emu_configuration"]='Configuração do EMu.';
$lang["emu_api_settings"]='Configurações do servidor de API.';
$lang["emu_api_server"]='Endereço do servidor (por exemplo, http://[endereço.do.servidor])';
$lang["emu_api_server_port"]='Porta do servidor.';
$lang["emu_resource_types"]='Selecionar tipos de recursos vinculados ao EMu.';
$lang["emu_email_notify"]='Endereço de e-mail para o qual o script enviará notificações. Deixe em branco para usar o endereço padrão de notificação do sistema.';
$lang["emu_script_failure_notify_days"]='Número de dias após os quais exibir alerta e enviar e-mail se o script não foi concluído.';
$lang["emu_script_header"]='Habilitar script que atualizará automaticamente os dados do EMu sempre que o ResourceSpace executar sua tarefa agendada (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='<div class="Question">
	<label>
		<strong>Última execução do script</strong>
	</label>
	<input name="script_last_ran" type="text" value="%script_last_ran%" disabled style="width: 300px;">
	%scripts_test_functionality%
</div>
<div class="clearerleft"></div>';
$lang["emu_script_mode"]='Modo de script.';
$lang["emu_script_mode_option_1"]='Importar metadados do EMu.';
$lang["emu_script_mode_option_2"]='Recuperar todos os registros do EMu e manter o RS e o EMu sincronizados.';
$lang["emu_enable_script"]='Habilitar script EMu.';
$lang["emu_test_mode"]='Modo de teste - Defina como verdadeiro e o script será executado, mas não atualizará os recursos.';
$lang["emu_interval_run"]='Executar script no seguinte intervalo (por exemplo, +1 dia, +2 semanas, quinzenal). Deixe em branco e ele será executado toda vez que cron_copy_hitcount.php for executado.';
$lang["emu_log_directory"]='Diretório para armazenar registros de script. Se isso for deixado em branco ou for inválido, nenhum registro será feito.';
$lang["emu_created_by_script_field"]='Campo de metadados usado para armazenar se um recurso foi criado por um script EMu.';
$lang["emu_settings_header"]='Configurações do EMu.';
$lang["emu_irn_field"]='Campo de metadados usado para armazenar o identificador EMu (IRN)';
$lang["emu_search_criteria"]='Critérios de busca para sincronização do EMu com o ResourceSpace.';
$lang["emu_rs_mappings_header"]='Regras de mapeamento EMu - ResourceSpace.';
$lang["emu_module"]='Módulo EMu.';
$lang["emu_column_name"]='Coluna do módulo EMu.';
$lang["emu_rs_field"]='Campo do ResourceSpace.';
$lang["emu_add_mapping"]='Adicionar mapeamento.';
$lang["emu_upload_emu_field_label"]='Nº de referência do EMu.';
$lang["emu_confirm_upload_nodata"]='Por favor, marque a caixa para confirmar que deseja prosseguir com o envio.';
$lang["emu_test_script_title"]='Testar/Executar script.';
$lang["emu_run_script"]='Processo';
$lang["emu_script_problem"]='ATENÇÃO - o script EMu não foi concluído com sucesso nos últimos %dias% dias. Último horário de execução:';
$lang["emu_no_resource"]='ID de recurso não especificado!';
$lang["emu_upload_nodata"]='Não foram encontrados dados do EMu para este IRN:';
$lang["emu_nodata_returned"]='Nenhum dado do EMu encontrado para o IRN especificado.';
$lang["emu_createdfromemu"]='Criado a partir do plugin EMU.';