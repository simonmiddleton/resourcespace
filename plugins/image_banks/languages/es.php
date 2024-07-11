<?php


$lang["image_banks_configuration"]='Bancos de imágenes';
$lang["image_banks_search_image_banks_label"]='Buscar bancos de imágenes externos';
$lang["image_banks_search_image_banks_info_text"]='Por favor, especifique a través de cuál Banco de Imágenes desea realizar la búsqueda';
$lang["image_banks_pixabay_api_key"]='Clave de API';
$lang["image_banks_image_bank"]='Banco de imágenes';
$lang["image_banks_create_new_resource"]='Crear nuevo recurso';
$lang["image_banks_provider_unmet_dependencies"]='¡El proveedor \'%PROVIDER\' tiene dependencias no satisfechas!';
$lang["image_banks_provider_id_required"]='Se requiere el ID del proveedor para completar la búsqueda';
$lang["image_banks_provider_not_found"]='No se pudo identificar al proveedor utilizando el ID';
$lang["image_banks_bad_request_title"]='Petición incorrecta';
$lang["image_banks_bad_request_detail"]='La solicitud no pudo ser procesada por \'%FILE\'';
$lang["image_banks_unable_to_create_resource"]='¡No se puede crear un nuevo recurso!';
$lang["image_banks_unable_to_upload_file"]='No se puede cargar el archivo desde el banco de imágenes externo para el recurso #%RESOURCE';
$lang["image_banks_try_again_later"]='¡Por favor, inténtalo de nuevo más tarde!';
$lang["image_banks_warning"]='ADVERTENCIA:';
$lang["image_banks_warning_rate_limit_almost_reached"]='El proveedor \'%PROVIDER\' solo permitirá %RATE-LIMIT-REMAINING búsquedas más. Este límite se restablecerá en %TIME';
$lang["image_banks_try_something_else"]='Por favor, prueba algo más.';
$lang["image_banks_error_detail_curl"]='El paquete php-curl no está instalado';
$lang["image_banks_local_download_attempt"]='El usuario intentó descargar \'%FILE\' utilizando el complemento ImageBank apuntando a un sistema que no forma parte de los proveedores permitidos';
$lang["image_banks_bad_file_create_attempt"]='El usuario intentó crear un recurso con el archivo \'%FILE\' utilizando el plugin ImageBank, apuntando a un sistema que no forma parte de los proveedores permitidos';
$lang["image_banks_shutterstock_token"]='Token de Shutterstock (<a href=\'https://www.shutterstock.com/account/developers/apps\' target=\'_blank\'>generar</a>)';
$lang["image_banks_shutterstock_result_limit"]='Límite de resultados (máximo 1000 para cuentas gratuitas)';
$lang["image_banks_shutterstock_id"]='Identificación de imagen de Shutterstock';
$lang["image_banks_createdfromimagebanks"]='Creado desde el plugin de Bancos de Imágenes';
$lang["image_banks_image_bank_source"]='Fuente del Banco de Imágenes';
$lang["image_banks_label_resourcespace_instances_cfg"]='Acceso a instancias (formato: nombre i18n|URL base|nombre de usuario|clave|configuración)';
$lang["image_banks_resourcespace_file_information_description"]='ResourceSpace tamaño %SIZE_CODE';
$lang["image_banks_label_select_providers"]='Seleccionar proveedores activos';
$lang["image_banks_view_on_provider_system"]='Ver en el sistema de %PROVIDER';
$lang["image_banks_system_unmet_dependencies"]='¡El complemento ImageBanks tiene dependencias del sistema no satisfechas!';
$lang["image_banks_error_generic_parse"]='No se puede analizar la configuración de los proveedores (para múltiples instancias)';
$lang["image_banks_error_resourcespace_invalid_instance_cfg"]='Formato de configuración inválido para la instancia de \'%PROVIDER\' (proveedor)';
$lang["image_banks_error_bad_url_scheme"]='Esquema de URL no válido encontrado para la instancia de \'%PROVIDER\' (proveedor)';
$lang["image_banks_error_unexpected_response"]='Lo sentimos, se recibió una respuesta inesperada del proveedor. Por favor, contacte a su administrador del sistema para una investigación más detallada (vea el registro de depuración).';
$lang["plugin-image_banks-title"]='Bancos de Imágenes';
$lang["plugin-image_banks-desc"]='Permite a los usuarios seleccionar un Banco de Imágenes externo para buscar. Los usuarios pueden luego descargar o crear nuevos recursos basados en los resultados obtenidos.';