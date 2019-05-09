<?php

	global $baseurl,$watched_searches_url;

	$watched_searches_url = $baseurl . '/plugins/rse_search_notifications/pages/watched_searches.php';

	function search_notifications_get(&$results, $user="",$enabled_only=true,$search="",$orderby=1,$orderbydirection="DESC")
		{
		$results=sql_query(
			"SELECT search_saved.*,u.username FROM search_saved JOIN `user` u ON search_saved.owner=u.ref " .
			($user=="" ? "" : " WHERE `owner`='{$user}'") .
			($enabled_only ? ($user=="" ? " WHERE `enabled`=1" : " AND `enabled`=1" ) : "") .
			($search=="" ? "" : " AND (title LIKE '%{$search}%' OR u.username LIKE '%{$search}%')") .
			" ORDER BY '{$orderby}' '{$orderbydirection}'"
		);
		return count($results) > 0;
		}

	function search_notification_add($search,$restypes,$archive)
		{
		global $userref;
		if (sql_value("SELECT COUNT(*) AS value FROM search_saved WHERE owner='{$userref}' AND search='" . escape_check($search) . "' AND restypes='" . escape_check($restypes) . "'  ",0)!=0)
			{
			return;		// we do not want dupes or empty searches
			}
		if ($archive=="")
			{
			$archive=0;
			}
		$restypes_names = sql_value("SELECT GROUP_CONCAT(name SEPARATOR ', ') AS value FROM resource_type WHERE ref IN('" .
		implode("','",explode(",",$restypes))
		. "')","");

        // Because search can contain direct node IDs searches like @@257
        // We resolve them back into node names
        $rebuilt_search  = $search;
        $node_bucket     = array();
        $node_bucket_not = array();
        $searched_nodes  = resolve_given_nodes($rebuilt_search, $node_bucket, $node_bucket_not);

        foreach($node_bucket as $searched_nodes)
            {
            $searched_node_data = array();

            foreach($searched_nodes as $searched_node)
                {
                if(!get_node($searched_node, $searched_node_data))
                    {
                    continue;
                    }

                $rebuilt_search .= rebuild_specific_field_search_from_node($searched_node_data);
                }
            }
        $rebuilt_search = str_replace('"', '', $rebuilt_search);

		sql_query("INSERT INTO search_saved(created,owner,title,search,restypes,archive,enabled) VALUES (
			NOW(),
			'{$userref}',
			'\"" . escape_check($rebuilt_search) . '"' . ($restypes_names == "" ? "" : " (" . escape_check(i18n_get_translated($restypes_names)) . ")") . "',
			'" . escape_check($search) . "',
			'" . escape_check($restypes) . "',
			'" . escape_check($archive) . "',
			1
		)");
		search_notification_process($userref,sql_insert_id());
		}

	function search_notification_delete($ref,$force=false)
		{
		if ($force)
			{
			sql_query("DELETE FROM search_saved WHERE ref='{$ref}'");
			}
		else
			{
			global $userref;
			sql_query("DELETE FROM search_saved WHERE ref='{$ref}' AND owner='{$userref}'");
			}
		}

	function search_notification_enable($ref,$force=false)
		{
		if ($force)
			{
			sql_query("UPDATE search_saved SET enabled=1 WHERE ref='{$ref}'");
			}
		else
			{
			global $userref;
			sql_query("UPDATE search_saved SET enabled=1 WHERE ref='{$ref}' AND owner='{$userref}'");
			}
		}

	function search_notification_disable($ref,$force=false)
		{
		if ($force)
			{
			sql_query("UPDATE search_saved SET enabled=0 WHERE ref='{$ref}'");
			}
		else
			{
			global $userref;
			sql_query("UPDATE search_saved SET enabled=0 WHERE ref='{$ref}' AND owner='{$userref}'");
			}
		}

	function search_notification_enable_all($force=false)
		{
		if ($force)
			{
			sql_query("UPDATE search_saved SET enabled=1");
			}
		else
			{
			global $userref;
			sql_query("UPDATE search_saved SET enabled=1 WHERE owner='{$userref}'");
			}
		}

	function search_notification_disable_all($force=false)
		{
		if ($force)
			{
			sql_query("UPDATE search_saved SET enabled=0");
			}
		else
			{
			global $userref;
			sql_query("UPDATE search_saved SET enabled=0 WHERE owner='{$userref}'");
			}
		}

	function search_notification_process($owner=-1,$search_saved=-1)
		{

		global $lang,$baseurl,$search_notification_max_thumbnails;

		$saved_searches=sql_query("SELECT * FROM search_saved WHERE enabled=1" .
			($owner==-1 ? "" : " AND owner='{$owner}'") .
			($search_saved==-1 ? "" : " AND ref='{$search_saved}'") .
			" ORDER BY owner"
		);

		if (!function_exists("do_search"))
			{
			include __DIR__ . "/../../../include/search_functions.php";
			}

		foreach ($saved_searches as $search)
			{
			$results=do_search($search['search'],$search['restypes'],'resourceid',$search['archive']);
			$resources_found=array();

			if (is_array($results) && count($results) > 0)
				{
				foreach ($results as $result)
					{
					array_push($resources_found, $result['ref']);
					}
				}

			$checksum_data=implode(',',$resources_found);
			$checksum=sha1('#' . $checksum_data);		// the '#' avoids blank checksum if no resources found
			$checksum_matches=count($resources_found);

			if ($checksum==$search['checksum'])		// nothing has changed so process the next saved search
				{
				continue;
				}

			if ($search['checksum'] != "")		// this search has been run before so work out differences
				{

				$resources_existing=$search['checksum_data']=='' ? array() : explode(',',$search['checksum_data']);		// ensure empty resource list produces zero array entries

				$resources_subtracted=array_diff($resources_existing,$resources_found);
				$resources_added=array_diff($resources_found,$resources_existing);
				$resources_added_count=count($resources_added);
				$resources_subtracted_count=count($resources_subtracted);

				$message=
				($resources_added_count == 1 ? "{$resources_added_count} {$lang['search_notifications_new match_found']}" : "") .
				($resources_added_count > 1 ? "{$resources_added_count} {$lang['search_notifications_new matches_found']}" : "") .
				($resources_added_count > 0 && $resources_subtracted_count > 0 ? " {$lang['and']} " : "") .
				($resources_subtracted_count == 1 ? "{$resources_subtracted_count} {$lang['search_notifications_resource_no_longer_matches']}" : "") .
				($resources_subtracted_count > 1 ? "{$resources_subtracted_count} {$lang['search_notifications_resources_no_longer_match']}" : "") .
				" {$lang['search_notifications_for_watch_search']} " . escape_check($search['title']) . "<br />";

				$added_to_message_count = 0;

				foreach ($resources_added as $resource_added)
					{
					if ($added_to_message_count == $search_notification_max_thumbnails)
						{
						break;
						}

					$thumb_file=get_resource_path($resource_added,true,'col');
					if (file_exists($thumb_file))
						{
						$thumb_url=get_resource_path($resource_added,false,'col');
						$message.="<a href='{$baseurl}/pages/view.php?ref={$resource_added}&search={$search['search']}&restypes={$search['restypes']}&archive={$search['archive']}'";
						$message.=" onclick='return ModalLoad(this,true);'>";
						$message.="<img src='{$thumb_url}' >";
						$message.="</a>";
						$added_to_message_count++;
						}
					}

				message_add(
					$search['owner'],
					$message,
					search_notification_make_url($search['search'],$search['restypes'],$search['archive'])
				);

				}

			// finally update with the new checksum, timestamp and resources
			sql_query("UPDATE search_saved SET checksum='{$checksum}',checksum_matches='{$checksum_matches}',checksum_when=NOW(),checksum_data='" . escape_check($checksum_data) . "' WHERE ref='{$search['ref']}'");

			}		// end for each saved search
		}

	function search_notification_make_url($search,$restypes,$archive)
		{
		global $baseurl;
		return $baseurl . "/pages/search.php?search=" . urlencode($search) . "&restypes=" . urlencode($restypes) . "&archive=" . $archive;
		}
