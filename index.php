<?php
include "include/db.php";

if (getval("rp","")!="")
	{
	# quick redirect to reset password
	$rp=getvalescaped("rp","");
	$topurl="pages/user/user_change_password.php?rp=" . $rp;
        redirect($topurl);
	}
        
# External access support (authenticate only if no key provided, or if invalid access key provided)
$k = getvalescaped('k', '');
if('' == $k || (!check_access_key_collection(getvalescaped('c', ''), $k) && !check_access_key(getvalescaped('r', ''), $k)))
    {
    include 'include/authenticate.php';
    }

if (!hook("replacetopurl"))
	{ 
	$topurl="pages/" . $default_home_page . "?login=true";
	if($use_theme_as_home) { $topurl = "pages/collections_featured.php"; }
	if ($use_recent_as_home) {$topurl="pages/search.php?search=" . urlencode("!last".$recent_search_quantity);}
	} /* end hook replacetopurl */ 


$c = trim(getval("c", ""));
if($c != "")
	{
    $collection = get_collection($c);
    if($collection === false)
        {
        exit($lang["error-collectionnotfound"]);
        }

    $topurl = "pages/search.php?search=" . urlencode("!collection" . $c) . "&k=" . $k;

    if(trim($k) != "")
        {
        $collection_resources = get_collection_resources($c);

        if($collection["type"] == COLLECTION_TYPE_FEATURED)
            {
            $collection["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);
            }

        if(is_featured_collection_category($collection))
            {
            $topurl = "pages/collections_featured.php?parent={$c}&k={$k}";
            }
        else if(is_array($collection_resources) && count($collection_resources) > 0 && $feedback_resource_select && $collection["request_feedback"])
            {
            $topurl = "pages/collection_feedback.php?collection={$c}&k={$k}";      
            }
        }
	}

if (getval("r","")!="")
	{
	# quick redirect to a resource (from e-mails)
	$r=getvalescaped("r","");
	$topurl="pages/view.php?ref=" . $r . "&k=" . $k;
	}

if (getval("u","")!="")
	{
	# quick redirect to a user (from e-mails)
	$u=getvalescaped("u","");
	$topurl="pages/team/team_user_edit.php?ref=" . $u;
	}
	
if (getval("q","")!="")
	{
	# quick redirect to a request (from e-mails)
	$q=getvalescaped("q","");
	$topurl="pages/team/team_request_edit.php?ref=" . $q;
	}

if (getval('ur', '') != '')
	{
	# quick redirect to periodic report unsubscriptions.
	$ur = getvalescaped('ur', '');

	$topurl = 'pages/team/team_report.php?unsubscribe=' . $ur;
	}

if(getval('dr', '') != '')
	{
	# quick redirect to periodic report deletion.
	$dr = getvalescaped('dr', '');

	$topurl = 'pages/team/team_report.php?delete=' . $dr;
    }
    
if (getval("upload","") != "")
	{
    # Redirect to upload page
    $topurl = get_upload_url($c,$k);
	}

# Redirect.
redirect($topurl);
