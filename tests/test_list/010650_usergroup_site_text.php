<?php

command_line_only();

# Testing of load_site_text_for_usergroup() for loading and refreshing site text for specified user groups.

$params = array('s', '', 's', 'newlogindetails', 's', 'Text for General Users only', 's', 'en', 'i', 2);
ps_query("INSERT INTO site_text (page, name, text, language, specific_to_group) VALUES (?,?,?,?,?)", $params);
$site_text_row[] = sql_insert_id();

$params = array('s', '', 's', 'newlogindetails', 's', 'Text for Super Admin only', 's', 'en', 'i', 3);
ps_query("INSERT INTO site_text (page, name, text, language, specific_to_group) VALUES (?,?,?,?,?)", $params);
$site_text_row[] = sql_insert_id();

clear_query_cache("sitetext");
$original_language = $language;
$GLOBALS['language'] = 'en';

# Clearing cache for the test - it'll only be set once for the duration of the testing.
if (isset($GLOBALS['load_site_text_for_usergroup'])) {
    $copy_original_cache = $GLOBALS['load_site_text_for_usergroup'];
    unset($GLOBALS['load_site_text_for_usergroup']);
}

$original_newlogindetails_value = $lang['newlogindetails'];

# Check user group specific site text is applied. This will also cache the original value for the lang string.
load_site_text_for_usergroup(2);
if ($lang['newlogindetails'] != 'Text for General Users only') {
    echo 'User group specific site text was not loaded. ';
    return false;
}

# Check user group specific site text is applied. This should not change the cached value.
load_site_text_for_usergroup(3);
if ($lang['newlogindetails'] != 'Text for Super Admin only') {
    echo 'User group specific site text was not loaded (2). ';
    return false;
}

$original_usergroup = $usergroup;
unset($GLOBALS['usergroup']);

# Restore the cached value for the lang string. For scenarios where we haven't previously loaded a specific user group.
# For example, after user requests password reset - we load their user group only for the email then reset to null.
# Cache is created for the first call to this function during the first test.
load_site_text_for_usergroup(null);
if ($original_newlogindetails_value != $lang['newlogindetails']) {
    echo 'Default site text value was not returned from cache. ';
    return false;
}

# Tear down
if (isset($copy_original_cache)) {
    $GLOBALS['load_site_text_for_usergroup'] = $copy_original_cache;
} else {
    unset($GLOBALS['load_site_text_for_usergroup']);
}

ps_query("DELETE FROM site_text WHERE ref IN (" . ps_param_insert(count($site_text_row)) .");", ps_param_fill($site_text_row, 'i'));

$GLOBALS['usergroup'] = $original_usergroup;
$GLOBALS['language'] = $original_language;
