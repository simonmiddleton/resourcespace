<?php
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php';
if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit($lang['error-permissiondenied']);
    }

$plugin_name = 'image_banks';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

$page_def = array();

$providers = \ImageBanks\getProviders($image_banks_loaded_providers);
foreach($providers as $provider)
    {
    if(!$provider::checkDependencies())
        {
        $error = str_replace('%PROVIDER', $provider->getName(), $lang['image_banks_provider_unmet_dependencies']);

        break;
        }

    $page_def = $provider->buildConfigPageDefinition($page_def);
    }

// Render setup page ritual
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(isset($error))
    {
    ?>
    <div class="PageInformal"><?php echo htmlspecialchars($error); ?></div>
    <?php
    }
config_gen_setup_html($page_def, $plugin_name, $upload_status, $lang['image_banks_configuration']);
include '../../../include/footer.php';