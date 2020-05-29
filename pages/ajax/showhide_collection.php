<?php
# AJAX ratings save

include "../../include/db.php";
include "../../include/authenticate.php";

if(getvalescaped("action","")=="showcollection")
	{
	show_hide_collection(getvalescaped("collection","",true), true, $userref);
	exit("UNHIDDEN");
	}
	
if(getvalescaped("action","")=="hidecollection")
	{
	show_hide_collection(getvalescaped("collection","",true), false, $userref);
	exit("HIDDEN");
	}
	
exit("no action specified");

?>
