<?php


$lang["vr_view_configuration"]='Configuración de vista de realidad virtual de Google VR.';
$lang["vr_view_google_hosted"]='¿Usar la biblioteca de JavaScript VR View alojada en Google?';
$lang["vr_view_js_url"]='URL a la biblioteca de JavaScript VR View (sólo si lo anterior es falso). Si es local al servidor, utilice una ruta relativa, por ejemplo /vrview/build/vrview.js.';
$lang["vr_view_restypes"]='Tipos de recursos para mostrar usando VR View.';
$lang["vr_view_autopan"]='Habilitar Autopan.';
$lang["vr_view_vr_mode_off"]='Desactivar botón de modo VR';
$lang["vr_view_condition"]='Por favor, traducir: Condición de vista VR.';
$lang["vr_view_condition_detail"]='Si se selecciona un campo a continuación, se puede verificar el valor establecido para el campo y utilizarlo para determinar si se debe o no mostrar la vista previa de VR View. Esto le permite determinar si usar el complemento basado en datos EXIF incrustados mediante la asignación de campos de metadatos. Si esto no está configurado, siempre se intentará mostrar la vista previa, incluso si el formato no es compatible. <br /><br />NB Google requiere imágenes y videos con formato panorámico equirectangular. <br />La configuración sugerida es asignar el campo \'ProjectionType\' de exiftool a un campo llamado \'Tipo de proyección\' y utilizar ese campo.';
$lang["vr_view_projection_field"]='Campo Tipo de Proyección de Vista VR.';
$lang["vr_view_projection_value"]='Valor requerido para habilitar la vista de RV.';
$lang["vr_view_additional_options"]='Opciones adicionales.';
$lang["vr_view_additional_options_detail"]='Lo siguiente te permite controlar el complemento por recurso mediante la asignación de campos de metadatos para controlar los parámetros de vista VR.<br />Consulta <a href =\'https://developers.google.com/vr/concepts/vrview-web\' target=\'+blank\'>https://developers.google.com/vr/concepts/vrview-web</a> para obtener información más detallada.';
$lang["vr_view_stereo_field"]='Campo utilizado para determinar si la imagen/video es estéreo (opcional, el valor predeterminado es falso si no se establece).';
$lang["vr_view_stereo_value"]='Valor a comprobar. Si se encuentra, el estéreo se establecerá en verdadero.';
$lang["vr_view_yaw_only_field"]='Campo utilizado para determinar si se debe evitar el balanceo/inclinación (opcional, el valor predeterminado es falso si no se establece).';
$lang["vr_view_yaw_only_value"]='Valor a comprobar. Si se encuentra, la opción is_yaw_only se establecerá en verdadero.';
$lang["vr_view_orig_image"]='¿Usar el archivo de recurso original como fuente para la vista previa de la imagen?';
$lang["vr_view_orig_video"]='¿Usar el archivo de recurso original como fuente para la vista previa del video?';