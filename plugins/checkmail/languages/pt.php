<?php


$lang["checkmail_configuration"]='Configuração do Checkmail.';
$lang["checkmail_install_php_imap_extension"]='Passo Um: Instale a extensão php imap.';
$lang["checkmail_cronhelp"]='Este plugin requer uma configuração especial para que o sistema possa fazer login em uma conta de e-mail dedicada a receber arquivos destinados ao upload.<br /><br />Certifique-se de que o IMAP esteja habilitado na conta. Se você estiver usando uma conta do Gmail, habilite o IMAP em Configurações->POP/IMAP->Habilitar IMAP<br /><br />
Na configuração inicial, pode ser mais útil executar manualmente o arquivo plugins/checkmail/pages/cron_check_email.php na linha de comando para entender como ele funciona.
Depois que você estiver conectando corretamente e entender como o script funciona, deverá configurar um trabalho cron para executá-lo a cada minuto ou dois.<br />Ele irá examinar a caixa de correio e ler um e-mail não lido por execução.<br /><br />
Um exemplo de trabalho cron que é executado a cada dois minutos:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Sua conta IMAP foi verificada pela última vez em [lastcheck].';
$lang["checkmail_cronjobprob"]='Seu cronjob de verificação de e-mail pode não estar sendo executado corretamente, pois já se passaram mais de 5 minutos desde a última execução.<br /><br />
Um exemplo de cron job que é executado a cada minuto:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Servidor Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='E-mail.';
$lang["checkmail_password"]='Senha';
$lang["checkmail_extension_mapping"]='Tipo de Recurso através do Mapeamento de Extensão de Arquivo.';
$lang["checkmail_default_resource_type"]='Tipo de Recurso Padrão.';
$lang["checkmail_extension_mapping_desc"]='Após o seletor de Tipo de Recurso Padrão, há uma entrada abaixo para cada um dos seus Tipos de Recurso. <br />Para forçar arquivos enviados de diferentes tipos em um Tipo de Recurso específico, adicione listas separadas por vírgulas de extensões de arquivo (ex. jpg, gif, png).';
$lang["checkmail_resource_type_population"]='<br />(de allowed_extensions)';
$lang["checkmail_subject_field"]='Campo de Assunto';
$lang["checkmail_body_field"]='Campo de Corpo.';
$lang["checkmail_purge"]='Excluir e-mails após o envio?';
$lang["checkmail_confirm"]='Enviar e-mails de confirmação?';
$lang["checkmail_users"]='Usuários Permitidos.';
$lang["checkmail_blocked_users_label"]='Usuários bloqueados.';
$lang["checkmail_default_access"]='Acesso Padrão.';
$lang["checkmail_default_archive"]='Estado Padrão.';
$lang["checkmail_html"]='Permitir conteúdo HTML? (experimental, não recomendado)';
$lang["checkmail_mail_skipped"]='E-mail ignorado.';
$lang["checkmail_allow_users_based_on_permission_label"]='Os usuários devem ser autorizados com base em permissão para fazer upload?';
$lang["addresourcesviaemail"]='Adicionar via e-mail.';
$lang["uploadviaemail"]='Adicionar via e-mail.';
$lang["uploadviaemail-intro"]='Para enviar por e-mail, anexe seu(s) arquivo(s) e envie para <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Certifique-se de enviar do endereço <b>[fromaddress]</b>, caso contrário, será ignorado.</p><p>Observe que qualquer coisa no ASSUNTO do e-mail será inserido no campo [subjectfield] no %applicationname%.</p><p>Observe também que qualquer coisa no CORPO do e-mail será inserido no campo [bodyfield] no %applicationname%.</p><p>Vários arquivos serão agrupados em uma coleção. Seus recursos terão como padrão um Nível de acesso <b>\'[access]\'</b> e um status de Arquivo morto <b>\'[archive]\'</b>.</p><p>[confirmation]</p>';
$lang["checkmail_confirmation_message"]='Você receberá um e-mail de confirmação quando seu e-mail for processado com sucesso. Se o seu e-mail for ignorado por qualquer motivo programático (como se ele for enviado do endereço errado), o administrador será notificado de que há um e-mail que requer atenção.';
$lang["yourresourcehasbeenuploaded"]='Seu recurso foi carregado.';
$lang["yourresourceshavebeenuploaded"]='Seus recursos foram carregados.';
$lang["checkmail_not_allowed_error_template"]='[user-fullname] ([username]), com ID [user-ref] e e-mail [user-email] não tem permissão para fazer upload por e-mail (verifique as permissões "c" ou "d" ou os usuários bloqueados na página de configuração de verificação de e-mail). Registrado em: [datetime].';
$lang["checkmail_createdfromcheckmail"]='Criado a partir do plugin Verificar Email.';