<?php

function HookDiscount_codePurchase_callbackPayment_complete ()
	{
	$custom = getval("custom", "");

	# Find out the discount code applied to this collection.
	$code = ps_value("SELECT discount_code value FROM collection_resource WHERE collection = ? limit 1", array("i", $custom), "");
	
	# Find out the purchasing user
	# As this is a callback script being called by PayPal, there is no login/authentication and we can't therefore simply use $userref.
	$user = ps_value("SELECT ref value FROM user WHERE current_collection = ?", array("i", $custom), 0);
	
	# Insert used discount code row
	ps_query("INSERT INTO discount_code_used (code, user) VALUES (?, ?)", array("s", $code, "i", $user));
	}
