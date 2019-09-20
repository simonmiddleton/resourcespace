<?php

function HookResourceconnectPreviewAfterheader()
	{
	?>
	<!-- START GRAB -->
	<?php
	}
	

function HookResourceconnectPreviewBeforefooter()
	{
	?>
	<!-- END GRAB -->
	<?php
	}

function HookResourceconnectPreviewFullpreviewresultnav()
    {
    # Remove nav links for large preview.
    global $is_resourceconnect, $lang;
    if (isset($is_resourceconnect)) {
        ?><br />
        <a href="javascript:history.go(-1)"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresults"]?></a>
        <?php return true; 
        }    
    }
    
    
function HookResourceconnectPreviewNextpreviousextraurl()
	{
	if (getval("resourceconnect_source","")=="") {return false;} # Not a ResourceConnect result set. 

	# Forward the resourceconnect source.

	global $baseurl;
	echo "resourceconnect_source=" .$baseurl;
	}
	
function HookResourceconnectPreviewViewextraurl()
	{
	if (getval("resourceconnect_source","")=="") {return false;} # Not a ResourceConnect result set. 

	# Forward the resourceconnect source.

	global $baseurl;
	echo "resourceconnect_source=" .$baseurl;
	}
	
function HookResourceconnectPreviewSearchextraurl()
	{
	if (getval("resourceconnect_source","")=="") {return false;} # Not a ResourceConnect result set. 

	# Forward the resourceconnect source.

	global $baseurl;
	echo "resourceconnect_source=" .$baseurl;
	}
	
function HookResourceconnectPreviewNextpreviewregeneratekey()
	{	
	if (getval("resourceconnect_source","")=="") {return false;} # Not a ResourceConnect result set. 
	
	global $ref,$k,$scramble_key;
	
	# Create a new key when moving next/back for a given result set.
	
	$access_key=md5("resourceconnect" . $scramble_key);
	$k=substr(md5($access_key . $ref),0,10);
	
	return $k;
	}	