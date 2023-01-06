<?php
// Command line only script to process smart collections when $smart_collections_async is enabled.


include dirname(__FILE__)."/../../include/db.php";
command_line_only();

if (empty($_SERVER['argv'][1]))
	{
	exit();
	}

$smartsearch_ref = (int) $_SERVER['argv'][1];
update_smart_collection($smartsearch_ref);


