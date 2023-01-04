<?php
# Feeder page for AJAX user/group search for the user selection include file

include "../../include/db.php";

include "../../include/authenticate.php";

$userstring=getval("userstring","");
$userstring=resolve_userlist_groups($userstring);
$userstring=array_unique(trim_array(explode(",",$userstring)));
sort($userstring);
$userstring=implode(", ",$userstring);

$groups=resolve_userlist_groups_smart($userstring);
if($groups!="")
	{
	$userstring.=",".$groups;
	}

echo $userstring;
