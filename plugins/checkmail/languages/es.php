<?php


$lang["checkmail_configuration"]='Configuración de Checkmail';
$lang["checkmail_install_php_imap_extension"]='Paso uno: Instalar la extensión php imap.';
$lang["checkmail_cronhelp"]='Este plugin requiere una configuración especial para que el sistema pueda iniciar sesión en una cuenta de correo electrónico dedicada a recibir archivos destinados a la carga.<br /><br />Asegúrese de que IMAP esté habilitado en la cuenta. Si está utilizando una cuenta de Gmail, habilite IMAP en Configuración->POP/IMAP->Habilitar IMAP.<br /><br />
En la configuración inicial, puede resultarle más útil ejecutar manualmente el archivo plugins/checkmail/pages/cron_check_email.php en la línea de comandos para comprender cómo funciona.
Una vez que se conecte correctamente y comprenda cómo funciona el script, debe configurar una tarea cron para que se ejecute cada uno o dos minutos.<br />Escaneará el buzón y leerá un correo electrónico no leído por ejecución.<br /><br />
Un ejemplo de tarea cron que se ejecuta cada dos minutos:<br />
*/2 * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_lastcheck"]='Su cuenta IMAP fue revisada por última vez en [lastcheck].';
$lang["checkmail_cronjobprob"]='Su trabajo cron de verificación de correo electrónico puede no estar funcionando correctamente, ya que han pasado más de 5 minutos desde la última vez que se ejecutó.<br /><br />
Un ejemplo de trabajo cron que se ejecuta cada minuto:<br />
* * * * * cd /var/www/resourcespace/plugins/checkmail/pages; php ./cron_check_email.php >> /var/log/cron.log 2>&1<br /><br />';
$lang["checkmail_imap_server"]='Servidor Imap<br />(gmail="imap.gmail.com:993/ssl")';
$lang["checkmail_email"]='Correo electrónico.';
$lang["checkmail_password"]='Contraseña';
$lang["checkmail_extension_mapping"]='Tipo de recurso mediante asignación de extensión de archivo.';
$lang["checkmail_default_resource_type"]='Tipo de recurso predeterminado.';
$lang["checkmail_extension_mapping_desc"]='Después del selector de Tipo de Recurso Predeterminado, hay una entrada debajo para cada uno de sus Tipos de Recurso. <br />Para forzar que los archivos cargados de diferentes tipos se conviertan en un Tipo de Recurso específico, agregue listas separadas por comas de extensiones de archivo (por ejemplo, jpg, gif, png).';
$lang["checkmail_subject_field"]='Campo de Asunto';
$lang["checkmail_body_field"]='Campo de cuerpo.';
$lang["checkmail_purge"]='¿Eliminar correos electrónicos después de cargarlos?';
$lang["checkmail_confirm"]='¿Enviar correos electrónicos de confirmación?';
$lang["checkmail_users"]='Usuarios permitidos.';
$lang["checkmail_blocked_users_label"]='Usuarios bloqueados.';
$lang["checkmail_default_access"]='Acceso predeterminado.';
$lang["checkmail_default_archive"]='Estado predeterminado.';
$lang["checkmail_html"]='¿Permitir contenido HTML? (experimental, no recomendado)';
$lang["checkmail_mail_skipped"]='Correo electrónico omitido.';
$lang["checkmail_allow_users_based_on_permission_label"]='¿Debería permitirse a los usuarios subir archivos en función de sus permisos?';
$lang["addresourcesviaemail"]='Añadir por correo electrónico.';
$lang["uploadviaemail"]='Añadir por correo electrónico.';
$lang["uploadviaemail-intro"]='Para subir archivos por correo electrónico, adjunte su(s) archivo(s) y envíe el correo electrónico a <b><a href=\'mailto:[toaddress]\'>[toaddress]</a></b>.</p> <p>Asegúrese de enviarlo desde <b>[fromaddress]</b>, o será ignorado.</p><p>Tenga en cuenta que cualquier cosa en el ASUNTO del correo electrónico se colocará en el campo [subjectfield] en %applicationname%. </p><p> También tenga en cuenta que cualquier cosa en el CUERPO del correo electrónico se colocará en el campo [bodyfield] en %applicationname%. </p>  <p>Varios archivos se agruparán en una colección. Sus recursos tendrán un nivel de acceso predeterminado de <b>\'[access]\'</b>, y un estado de archivo de <b>\'[archive]\'</b>.</p><p> [confirmation]';
$lang["checkmail_confirmation_message"]='Recibirá un correo electrónico de confirmación cuando su correo electrónico sea procesado correctamente. Si su correo electrónico es omitido por razones programáticas (como si se envía desde una dirección incorrecta), el administrador será notificado de que hay un correo electrónico que requiere atención.';
$lang["yourresourcehasbeenuploaded"]='Su recurso ha sido cargado.';
$lang["yourresourceshavebeenuploaded"]='Tus recursos han sido subidos.';
$lang["checkmail_not_allowed_error_template"]='[Nombre completo del usuario] ([nombre de usuario]), con ID [referencia de usuario] y correo electrónico [correo electrónico del usuario] no tiene permiso para cargar archivos por correo electrónico (verifique los permisos "c" o "d" o los usuarios bloqueados en la página de configuración de checkmail). Registrado en: [fecha y hora].';
$lang["checkmail_createdfromcheckmail"]='Creado desde el plugin de Revisión de Correo.';