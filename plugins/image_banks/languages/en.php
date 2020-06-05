<?php
$lang["image_banks_configuration"] = "Image Banks";
$lang["image_banks_search_image_banks_label"] = "Search external image banks";
$lang["image_banks_search_image_banks_info_text"] = "Please specify which Image Bank you wish to search through";
$lang["image_banks_pixabay_api_key"] = "API key";
$lang["image_banks_image_bank"] = "Image Bank";
$lang["image_banks_create_new_resource"] = "Create new resource";

// Errors
$lang["image_banks_provider_unmet_dependencies"] = "Provider '%PROVIDER' has unmet dependencies!";
$lang["image_banks_provider_id_required"] = "Provider ID required to complete search";
$lang["image_banks_provider_not_found"] = "Provider could not be identified using ID";
$lang["image_banks_bad_request_title"] = "Bad Request";
$lang["image_banks_bad_request_detail"] = "Request could not be handled by '%FILE'";
$lang["image_banks_unable_to_create_resource"] = "Unable to create a new resource!";
$lang["image_banks_unable_to_upload_file"] = "Unable to upload file from external Image Bank for resource #%RESOURCE";
$lang["image_banks_try_again_later"] = "Please try again later!";
$lang["image_banks_warning"] = "WARNING: ";
$lang["image_banks_warning_rate_limit_almost_reached"] = "Provider '%PROVIDER' will only allow %RATE-LIMIT-REMAINING more searches. This limit will reset in %TIME";
$lang["image_banks_try_something_else"] = "Try something else.";
$lang["image_banks_pixabay_error_detail_curl"] = "php-curl package is not installed";

// Logs
$lang["image_banks_local_download_attempt"] = "User tried to download '%FILE' using the ImageBank plugin by pointing to a system which is not part of allowed providers";
$lang["image_banks_bad_file_create_attempt"] = "User tried to create a resource with '%FILE' file using the ImageBank plugin by pointing to a system which is not part of allowed providers";