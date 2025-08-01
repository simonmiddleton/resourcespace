<?php
function HookLicensemanagerUser_preferencesAdd_user_preference_page_def($page_def)
{
    global $user_pref_license_notifications, $lang, $enable_disable_options;
    
    $lastactionkey = array_search('AFTER_MESSAGES_MARKER',$page_def);
    $addoption = config_add_boolean_select('user_pref_license_notifications', $lang['user_pref_license_notifications'], $enable_disable_options, 300, '', true);
    array_splice($page_def, $lastactionkey, 0, array($addoption));

    return $page_def;
}