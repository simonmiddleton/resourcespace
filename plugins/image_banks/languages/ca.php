<?php


$lang["image_banks_configuration"]='Bancs d\'Imatges';
$lang["image_banks_search_image_banks_label"]='Cerca en bancs d\'imatges externs';
$lang["image_banks_pixabay_api_key"]='Clau d\'API';
$lang["image_banks_image_bank"]='Banc d\'Imatges';
$lang["image_banks_create_new_resource"]='Crear un nou recurs';
$lang["image_banks_provider_unmet_dependencies"]='El proveïdor \'%PROVIDER\' té dependències no satisfetes!';
$lang["image_banks_provider_id_required"]='Identificador del proveïdor necessari per completar la cerca';
$lang["image_banks_provider_not_found"]='No s\'ha pogut identificar el proveïdor utilitzant l\'ID';
$lang["image_banks_bad_request_title"]='Sol·licitud incorrecta';
$lang["image_banks_bad_request_detail"]='No s\'ha pogut processar la sol·licitud per \'%FILE\'';
$lang["image_banks_unable_to_create_resource"]='No es pot crear un recurs nou!';
$lang["image_banks_unable_to_upload_file"]='No es pot carregar el fitxer des de l\'Imatge Bank extern per al recurs #%RESOURCE';
$lang["image_banks_try_again_later"]='Si us plau, intenta-ho més tard!';
$lang["image_banks_warning"]='AVÍS:';
$lang["image_banks_warning_rate_limit_almost_reached"]='El proveïdor \'%PROVIDER\' només permetrà %RATE-LIMIT-REMAINING cerques més. Aquest límit es reiniciarà en %TIME';
$lang["image_banks_try_something_else"]='Prova alguna cosa diferent.';
$lang["image_banks_error_detail_curl"]='El paquet php-curl no està instal·lat';
$lang["image_banks_local_download_attempt"]='L\'usuari ha intentat descarregar \'%FILE\' utilitzant el connector ImageBank apuntant a un sistema que no forma part dels proveïdors permesos';
$lang["image_banks_bad_file_create_attempt"]='L\'usuari ha intentat crear un recurs amb el fitxer \'%FILE\' utilitzant el connector ImageBank, apuntant a un sistema que no forma part dels proveïdors permesos';
$lang["image_banks_shutterstock_token"]='Token de Shutterstock (<a href=\'https://www.shutterstock.com/account/developers/apps\' target=\'_blank\'>generar</a>)';
$lang["image_banks_shutterstock_result_limit"]='Límit de resultats (màxim 1000 per a comptes gratuïts)';
$lang["image_banks_shutterstock_id"]='Identificador d\'imatge de Shutterstock';
$lang["image_banks_createdfromimagebanks"]='Creat a partir del connector de bancs d\'imatges';
$lang["image_banks_image_bank_source"]='Font de la Banc d\'Imatges';
$lang["image_banks_label_resourcespace_instances_cfg"]='Accés a instàncies (format: nom i18n|baseURL|nom d\'usuari|clau|configuració)';
$lang["image_banks_resourcespace_file_information_description"]='ResourceSpace mida %SIZE_CODE';
$lang["image_banks_label_select_providers"]='Selecciona proveïdors actius';
$lang["image_banks_view_on_provider_system"]='Veure al sistema de %PROVIDER';
$lang["image_banks_system_unmet_dependencies"]='El complement ImageBanks té dependències del sistema no satisfetes!';
$lang["image_banks_error_generic_parse"]='No es pot analitzar la configuració dels proveïdors (per a múltiples instàncies)';
$lang["image_banks_error_resourcespace_invalid_instance_cfg"]='Format de configuració no vàlid per a la instància de \'%PROVIDER\' (proveïdor)';
$lang["image_banks_error_bad_url_scheme"]='S\'ha trobat un esquema d\'URL no vàlid per a la instància de \'%PROVIDER\' (proveïdor)';
$lang["image_banks_error_unexpected_response"]='Ho sentim, hem rebut una resposta inesperada del proveïdor. Si us plau, contacteu amb l\'administrador del sistema per a una investigació més detallada (vegeu el registre de depuració).';
$lang["plugin-image_banks-title"]='Bancs d\'Imatges';
$lang["plugin-image_banks-desc"]='Permet als usuaris seleccionar un Banc d\'Imatges extern per cercar-hi. Els usuaris poden descarregar o crear nous recursos basats en els resultats obtinguts.';