<?php

function HookDiscount_codePurchasePurchase_extra_options ()
	{
	global $lang;
	?>
	<p><?php echo $lang["enter-code"] ?><br>
	<?php echo $lang["discount_code"] .": " ?>
	<input type="text" name="discount_code" size="20">
	</p>
	
	<?php

	
	return true;
	}



function HookDiscount_codePurchaseAdjust_item_price ($origprice,$resource,$size)
	{
	global $discount_error,$discount_applied, $lang;
	
	# Discount pipeline support, allow multiple hook calls to modify the price multiple times
	global $purchase_pipeline_price;
	if (isset($purchase_pipeline_price[$resource][$size])) {$origprice=$purchase_pipeline_price[$resource][$size];}
	
	
	$discount_code=trim(strtoupper(getval("discount_code","")));
	if ($discount_code=="") {return $origprice;} # No code specified
	
	# Check that the discount code exists.
	$discount_info = ps_query("SELECT code, percent, expires FROM discount_code WHERE upper(code) = ?", array("s", $discount_code));
	if (count($discount_info)==0)
		{
		$discount_error=$lang["error-invalid-discount-code"];
		return false;
		}
	else
		{
		$discount_info=$discount_info[0];
		}	
	
	# Check that the user has not already used this discount code
	global $userref;
	$used = ps_value("SELECT count(*) value FROM discount_code_used WHERE user = ? AND upper(code) = ?", array("i", $userref, "s", $discount_code), 0);
	if ($used>0)		
		{
		$discount_error=$lang["error-discount-code-already-used"];
		return false;
		}
		
	$discount_applied=$discount_info["percent"];
	
	# Update collection with code, so it can be retrieved when we get the callback from PayPal and then insert a row into discount_code_used to mark that the user has used this discount code.
	global $usercollection;
	ps_query("UPDATE collection_resource SET discount_code = ? WHERE collection = ?", array("s", $discount_code, "i", $usercollection));
	
	$return=round(((100-$discount_info["percent"])/100) * $origprice,2);
	$purchase_pipeline_price[$resource][$size]=$return; # Use this price instead for future hook calls.
	return $return;
	}


function HookDiscount_codePurchasePrice_display_extras ()
	{
	global $discount_error,$discount_applied, $lang;
	
	if (isset($discount_error) && $discount_error!="")
		{
		?>
		<p><?php echo $discount_error ?></p>
		<?php
		}
	elseif (isset($discount_applied) && $discount_applied!="")
		{
		?>
		<p><?php echo $lang["discount_applied"] . ": " . $discount_applied . "%" ?></p>
		<?php	
		}
	}

?>