<?php


$lang["openai_gpt_title"]='Integração com o OpenAI';
$lang["property-openai_gpt_prompt"]='Prompt do GPT';
$lang["property-openai_gpt_input_field"]='Campo de entrada GPT';
$lang["openai_gpt_model"]='Nome do modelo de API a ser utilizado (por exemplo, \'text-davinci-003\')';
$lang["openai_gpt_prompt_prefix"]='Prefixo de prompt de conclusão';
$lang["openai_gpt_prompt_return_json"]='Sufixo de prompt de conclusão (para retornar JSON para campos de lista fixa)';
$lang["openai_gpt_prompt_return_text"]='Sufixo de prompt de conclusão (para retornar texto para campos de texto)';
$lang["openai_gpt_temperature"]='Amostragem de temperatura entre 0 e 1 (valores mais altos significam que o modelo assumirá mais riscos)';
$lang["openai_gpt_max_tokens"]='Máximo de tokens';
$lang["openai_gpt_advanced"]='ATENÇÃO - Esta seção é apenas para fins de teste e não deve ser alterada em sistemas ativos. Alterar qualquer uma das opções do plugin aqui afetará o comportamento de todos os campos de metadados que foram configurados. Altere com cautela!';
$lang["openai_gpt_system_message"]='Texto inicial da mensagem do sistema. Os espaços reservados %%IN_TYPE%% e %%OUT_TYPE%% serão substituídos por \'texto\' ou \'json\' dependendo dos tipos de campo de origem/destino';
$lang["openai_gpt_intro"]='Adiciona metadados gerados ao passar dados existentes para a API OpenAI com um prompt personalizável. Consulte <a href=\'https://platform.openai.com/docs/introduction\' target=\'_blank\'>https://platform.openai.com/docs/introduction</a> para mais informações detalhadas.';
$lang["openai_gpt_api_key"]='Chave da API OpenAI. Obtenha sua chave da API em <a href=\'https://openai.com/api\' target=\'_blank\' >https://openai.com/api</a>';
$lang["plugin-openai_gpt-title"]='Integração com a API GPT da OpenAI';
$lang["plugin-openai_gpt-desc"]='Metadados gerados pelo OpenAI. Passa os dados do campo configurado para a API do OpenAI e armazena as informações retornadas.';