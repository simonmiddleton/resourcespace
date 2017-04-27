<?php
function HookPropose_changesUser_preferencesAdd_user_preference_page_def($page_def)
	{
	global $actions_propose_changes, $lang, $enable_disable_options;
    $lastactionkey = array_search('AFTER_ACTIONS_MARKER',$page_def);
    $addoption = config_add_boolean_select('actions_propose_changes', $lang['actions_propose_changes'], $enable_disable_options, 300, '', true);
    array_splice($page_def, $lastactionkey, 0,array($addoption));
	return $page_def;
	}
