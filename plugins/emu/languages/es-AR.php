<?php


$lang["emu_configuration"]='Configuración de EMu.';
$lang["emu_api_settings"]='Configuraciones del servidor API.';
$lang["emu_api_server"]='Dirección del servidor (por ejemplo, http://[dirección.del.servidor])';
$lang["emu_api_server_port"]='Puerto del servidor.';
$lang["emu_resource_types"]='Seleccionar tipos de recursos vinculados a EMu.';
$lang["emu_email_notify"]='Dirección de correo electrónico a la que el script enviará notificaciones. Dejar en blanco para utilizar la dirección de correo electrónico de notificación del sistema por defecto.';
$lang["emu_script_failure_notify_days"]='Número de días después de los cuales mostrar una alerta y enviar un correo electrónico si el script no se ha completado.';
$lang["emu_script_header"]='Habilitar el script que actualizará automáticamente los datos de EMu cada vez que ResourceSpace ejecute su tarea programada (cron_copy_hitcount.php).';
$lang["emu_last_run_date"]='Última ejecución del script';
$lang["emu_script_mode"]='Modo de script.';
$lang["emu_script_mode_option_1"]='Importar metadatos desde EMu.';
$lang["emu_script_mode_option_2"]='Extraer todos los registros de EMu y mantener sincronizados RS y EMu.';
$lang["emu_enable_script"]='Habilitar script EMu.';
$lang["emu_test_mode"]='Modo de prueba - Si se establece en verdadero, el script se ejecutará pero no actualizará los recursos.';
$lang["emu_interval_run"]='Ejecutar script en el siguiente intervalo (por ejemplo, +1 día, +2 semanas, quincenal). Dejar en blanco y se ejecutará cada vez que cron_copy_hitcount.php se ejecute.';
$lang["emu_log_directory"]='Directorio para almacenar los registros de scripts. Si se deja en blanco o es inválido, no se realizará ningún registro.';
$lang["emu_created_by_script_field"]='Campo de metadatos utilizado para almacenar si un recurso ha sido creado por un script de EMu.';
$lang["emu_settings_header"]='Configuraciones de EMu.';
$lang["emu_irn_field"]='Campo de metadatos utilizado para almacenar el identificador EMu (IRN).';
$lang["emu_search_criteria"]='Criterios de búsqueda para sincronizar EMu con ResourceSpace.';
$lang["emu_rs_mappings_header"]='Reglas de mapeo EMu - ResourceSpace.';
$lang["emu_module"]='Módulo EMu.';
$lang["emu_column_name"]='Columna del módulo EMu.';
$lang["emu_rs_field"]='Campo de ResourceSpace.';
$lang["emu_add_mapping"]='Agregar mapeo.';
$lang["emu_upload_emu_field_label"]='EMu IRN se puede traducir como "Número de Registro de Identificación" en español.';
$lang["emu_confirm_upload_nodata"]='Por favor, marque la casilla para confirmar que desea continuar con la carga.';
$lang["emu_test_script_title"]='Prueba/Ejecutar script.';
$lang["emu_run_script"]='Proceso';
$lang["emu_script_problem"]='ADVERTENCIA - el script EMu no se ha completado con éxito en los últimos %días% días. Última hora de ejecución:';
$lang["emu_no_resource"]='¡No se especificó el ID del recurso!';
$lang["emu_upload_nodata"]='No se encontraron datos de EMu para este IRN:';
$lang["emu_nodata_returned"]='No se encontraron datos de EMu para el IRN especificado.';
$lang["emu_createdfromemu"]='Creado desde el plugin EMU.';