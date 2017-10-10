<?php

include_once __DIR__ . '/../include/search_notifications_functions.php';

function HookRse_search_notificationsAllRender_search_actions_add_option($options)
	{
	global $lang,$watched_searches_url,$search,$restypes,$archive,$k;
	if ($k!="" || !isset($search) || $search=="")
	{				
		return array();		
	}	
	$option=array(
		'value' => 'watch_this_search',
		'label' => $lang['search_notifications_watch_this_search'],
		'data_attr' => array
			(
			'url' => "{$watched_searches_url}?callback=add&search={$search}&restypes={$restypes}&archive={$archive}"
			)
	);
	array_push($options,$option);
	return $options;
	}

function HookRse_search_notificationsAllRender_actions_add_collection_option($top_actions,array $options)
	{
	return (HookRse_search_notificationsAllRender_search_actions_add_option($options));
	}
