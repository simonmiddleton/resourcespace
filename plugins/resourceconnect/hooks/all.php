<?php

# Check access keys
function HookResourceconnectAllCheck_access_key($resource,$key)
	{
	# Generate access key and check that the key is correct for this resource.
	global $scramble_key;
	$access_key=md5("resourceconnect" . $scramble_key);

	if ($key!=substr(md5($access_key . $resource),0,10)) {return false;} # Invalid access key. Fall back to user logins.

	global $resourceconnect_user; # Which user to use for remote access?
	$userdata=validate_user("u.ref='$resourceconnect_user'");
	setup_user($userdata[0]);
	
	
	
	# Set that we're being accessed via resourceconnect.
	global $is_resourceconnect;
	$is_resourceconnect=true;

	global $collections_footer;	
	$collections_footer=false;
	
	return true;
	}

function HookResourceConnectAllInitialise()
	{
	# Work out the current affiliate
	global $lang,$language,$resourceconnect_affiliates,$baseurl,$resourceconnect_selected,$resourceconnect_this;

	# Work out which affiliate this site is
	$resourceconnect_this="";
	for ($n=0;$n<count($resourceconnect_affiliates);$n++)			
		{
		if ($resourceconnect_affiliates[$n]["baseurl"]==$baseurl) {$resourceconnect_this=$n;break;}
		}
	if ($resourceconnect_this==="") {exit($lang["resourceconnect_error-affiliate_not_found"]);}
	
	$resourceconnect_selected=getval("resourceconnect_selected","");
	if ($resourceconnect_selected=="" || !isset($resourceconnect_affiliates[$resourceconnect_selected]))
		{
		# Not yet set, default to this site
		$resourceconnect_selected=$resourceconnect_this;
		}
#	setcookie("resourceconnect_selected",$resourceconnect_selected);
	setcookie("resourceconnect_selected",$resourceconnect_selected,0,"/",'',false,true);
	}

function HookResourceConnectAllSearchfiltertop()
	{
	# Option to search affiliate systems in the basic search panel
	global $lang,$language,$resourceconnect_affiliates,$baseurl,$resourceconnect_selected;
	if (!checkperm("resourceconnect")) {return false;}
	?>

	<div class="SearchItem"><?php echo $lang["resourceconnect_affiliate"];?><br />
	<select class="SearchWidth" name="resourceconnect_selected">
	
	<?php for ($n=0;$n<count($resourceconnect_affiliates);$n++)
		{
		?>
		<option value="<?php echo $n ?>" <?php if ($resourceconnect_selected==$n) { ?>selected<?php } ?>><?php echo i18n_get_translated($resourceconnect_affiliates[$n]["name"]) ?></option>
		<?php		
		}
	?>
	</select>
	</div>
	<?php
	}


function HookResourceConnectAllGenerate_collection_access_key($collection,$k,$userref,$feedback,$email,$access,$expires)
	{
	# When sharing externally, add the external access key to an empty row if the collection is empty, so the key still validates.
	$c=sql_value("select count(*) value from collection_resource where collection='$collection'",0);
	if ($c>0) {return false;} # Contains resources, key already present
	
	sql_query("insert into external_access_keys(resource,access_key,collection,user,request_feedback,email,date,access,expires) values (-1,'$k','$collection','$userref','$feedback','" . escape_check($email) . "',now(),$access," . (($expires=="")?"null":"'" . $expires . "'"). ");");
	
	}

function HookResourceconnectAllGenerateurl($url)
	{
	# Always use complete URLs when accessing a remote system. This ensures the user stays on the affiliate system and doesn't get diverted back to the base system.
	global $baseurl,$baseurl_short,$pagename,$resourceconnect_fullredir_pages;
	
	if (!in_array($pagename,$resourceconnect_fullredir_pages)) {return $url;} # Only fire for certain pages as needed.
	
	# Trim off the short base URL if it's been set.
	if (substr($url,0,strlen($baseurl_short))==$baseurl_short) {$url=substr($url,strlen($baseurl_short));}
																			
	return ($baseurl . "/" . $url);
	}
	
/*
function HookResourceConnectAllAdvancedsearchlink()
	{
	global $resourceconnect_selected,$resourceconnect_this;
	if (!checkperm("resourceconnect")) {return false;}

	# Hide 'advanced search' link when current affiliate not selected.
	return ($resourceconnect_selected!=$resourceconnect_this);
		
	}
*/
