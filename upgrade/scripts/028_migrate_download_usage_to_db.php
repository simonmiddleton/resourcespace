<?php 

global $download_usage_options;

$default_usage_options = ['Press','Print','Web','TV','Other'];

$existing_values = ps_query("SELECT name FROM download_usage_options ORDER BY order_by");
$existing_values = array_column($existing_values, 'name');

if (
    $existing_values !== $download_usage_options 
    && $download_usage_options !== $default_usage_options
    && count($download_usage_options) > 0
) {
    ps_query('DELETE FROM download_usage_options');
    ps_query('ALTER TABLE download_usage_options AUTO_INCREMENT = 1');

    ps_query('INSERT INTO download_usage_options (name, enabled) 
                VALUES ' . trim(str_repeat('(?, 1),', count($download_usage_options)), ','), 
                ps_param_fill($download_usage_options, 's'));
    ps_query('UPDATE download_usage_options SET order_by = ref');
}
