<?php
include "../include/db.php";
include "../include/authenticate.php";


$collection_url	= getvalescaped('collection', '', true);
$col_order_by	= getvalescaped('col_order_by', '', true);
$find			= getvalescaped('find', '', true);
$offset			= getvalescaped('offset', '', true);
$order_by		= getvalescaped('order_by', '', true);
$ref			= getvalescaped('ref', '', true);
$restypes		= getvalescaped('restypes', '', true);
$search			= getvalescaped('search', '', true);
$sort			= getvalescaped('sort', '', true);
$starsearch		= getvalescaped('starsearch', '', true);
$user_group		= getvalescaped('usergroup', '', true);
$backurl        = getvalescaped('backurl', '');

// Check if editing existing external share
$editaccess     = trim(getvalescaped("editaccess", ""));
$editing        = ($editaccess != "");

$editexternalurl    = (getval("editexternalurl","")!="");
$deleteaccess       = (getval("deleteaccess", "") != "");
$generateurl        = (getval("generateurl", "") != "");

// Share options
if($editing)
    {
    $shareinfo      = get_external_shares(array("share_collection"=>$ref, "access_key"=>$editaccess));
    if(isset($shareinfo[0]))
        {
        $shareinfo  = $shareinfo[0];
        }
    else
        {
        error_alert($lang["error_invalid_key"],true);
        exit();        
        }
    $expires        = getvalescaped("expires",$shareinfo["expires"]);
    $access         = getval("access",$shareinfo["access"], true);	
    $group          = getval("usergroup",$shareinfo["usergroup"],true);
    $sharepwd       = getvalescaped('sharepassword', ($shareinfo["password_hash"] != "" ? "true" : ""));
    }
else
    {
    $expires        = getvalescaped("expires","");
    $access         = getval("access",-1, true);	
    $group          = getval("usergroup",0,true);
    $sharepwd       = getvalescaped('sharepassword', '');
    }

$collection = get_collection($ref);
if ($collection===false)
    {
    $error = $lang['error-collectionnotfound'];
    if(getval("ajax","") != "")
        {
        error_alert($error, false, 404);
        }
    else
        {
        include "../include/header.php";
        $onload_message = array("title" => $lang["error"],"text" => $error);
        include "../include/footer.php";
        }
    exit();
    }

if($collection["type"] == COLLECTION_TYPE_FEATURED)
    {
    $collection_resources = get_collection_resources($collection["ref"]);
    $collection["has_resources"] = (is_array($collection_resources) && !empty($collection_resources) ? 1 : 0);
    }
if($bypass_share_screen && $collection["type"] != COLLECTION_TYPE_SELECTION)
    {
    redirect('pages/collection_email.php?ref='.$ref ) ;
    }

// Check access controls
if(!collection_readable($ref))
    {
    exit($lang["no_access_to_collection"]);
    }
else if(
    $collection["type"] == COLLECTION_TYPE_FEATURED
    && !featured_collection_check_access_control((int) $collection["ref"])
    && !allow_featured_collection_share($collection)
)
    {
    exit(error_alert($lang["error-permissiondenied"], true, 403));
    }
if(!$allow_share || checkperm("b"))
    {
    $show_error = true;
    $error = $lang["error-permissiondenied"];
    }

$internal_share_only = checkperm("noex") || (isset($user_dl_limit) && intval($user_dl_limit) > 0);

// Special collection being shared - we need to make a copy of it and disable internal access
$share_selected_resources = false;
if($collection["type"] == COLLECTION_TYPE_SELECTION)
    {
    $share_selected_resources = true;

    // disable a few options
    $hide_internal_sharing_url = true;
    $email_sharing = false;
    $home_dash = false;

    // Prevent users from sharing the real collection. Copy it instead
    if(($generateurl && !$editing) || $editexternalurl || $deleteaccess)
        {
        $ref = create_collection($userref, $collection["name"]);
        copy_collection($collection["ref"], $ref);
        $collection = get_collection($ref);
        }
    }
// Special collection being shared. Ensure certain features are enabled/disabled
else if(is_featured_collection_category($collection))
    {
    // Check this is not an empty FC category
    $fc_resources = get_featured_collection_resources($collection, array("limit" => 1));
    if(empty($fc_resources))
        {
        exit(error_alert($lang["cannotshareemptythemecategory"], true, 200));
        }

    // Further checks at collection-resource level. Recurse through category's sub FCs
    $collection["sub_fcs"] = get_featured_collection_categ_sub_fcs($collection);
    $collectionstates = false;
    $sub_fcs_resources_states = array();
    $sub_fcs_resources_minaccess = array();
    foreach($collection["sub_fcs"] as $sub_fc)
        {
        // Check all featured collections contain only active resources
        $collectionstates = is_collection_approved($sub_fc);
        if(!$collection_allow_not_approved_share && $collectionstates === false)
            {
            break;
            }
        else if(is_array($collectionstates))
            {
            $sub_fcs_resources_states = array_unique(array_merge($sub_fcs_resources_states, $collectionstates));
            }

        // Check minimum access is restricted or lower and sharing of restricted resources is not allowed
        $sub_fcs_resources_minaccess[] = collection_min_access($sub_fc);
        }
    $collectionstates = (!empty($sub_fcs_resources_states) ? $sub_fcs_resources_states : $collectionstates);

    if(!empty($sub_fcs_resources_minaccess))
        {
        $minaccess = max(array_unique($sub_fcs_resources_minaccess));
        }

    // To keep it in line with the legacy theme_category_share.php page, disable these features (home_dash, hide_internal_sharing_url)
    $home_dash = false;
    $hide_internal_sharing_url = true;

    // Beyond this point mark accordingly any validations that have been enforced specifically for Featured Collections
    // (categories or otherwise) type in a different way than for a normal collection
    // IMPORTANT: make sure there's code above this point (within this block) dealing with these validations.
    $collection_allow_empty_share = true;
    }

// Sharing an empty collection?
if (!$collection_allow_empty_share && count(get_collection_resources($ref))==0)
    {
    $show_error=true;
    $error=$lang["cannotshareemptycollection"];
    }
	
#Check if any resources are not active
$collectionstates = (isset($collectionstates) ? $collectionstates : is_collection_approved($ref));
if (!$collection_allow_not_approved_share && $collectionstates==false) {
        $show_error=true;
        $error=$lang["notapprovedsharecollection"];
        }
if(is_array($collectionstates) && (count($collectionstates)>1 || !in_array(0,$collectionstates)))
	{
	$warningtext=$lang["collection_share_status_warning"];
	foreach($collectionstates as $collectionstate)
		{
		$warningtext.="<br />" . $lang["status" . $collectionstate];
		}
	}

# Minimum access is restricted or lower and sharing of restricted resources is not allowed. The user cannot share this collection.
$minaccess = (isset($minaccess) ? $minaccess : collection_min_access($ref));
if(!$restricted_share && $minaccess >= RESOURCE_ACCESS_RESTRICTED)
    {
    $show_error = true;
    $error = $lang["restrictedsharecollection"];
    }

# Should those that have been granted open access to an otherwise restricted resource be able to share the resource? - as part of a collection
if(!$allow_custom_access_share && isset($customgroupaccess) && isset($customuseraccess)  && ($customgroupaccess || $customuseraccess))
	{ 
	$show_error=true;
	$error=$lang["customaccesspreventshare"];
	}

# Process deletion of access keys
if($deleteaccess && !isset($show_error) && enforcePostRequest(getval("ajax", false)))
    {
    delete_collection_access_key($ref,getvalescaped("deleteaccess",""));
    }

include "../include/header.php";
?>


<?php if (isset($show_error)){?>
    <script type="text/javascript">
    alert('<?php echo $error;?>');
        history.go(-1);
    </script><?php
    exit();}
?>
	<div class="BasicsBox"> 	
	<form method=post id="collectionform" action="<?php echo $baseurl_short?>pages/collection_share.php?ref=<?php echo urlencode($ref)?>">
	<input type="hidden" name="ref" id="ref" value="<?php echo htmlspecialchars($ref) ?>">
	<input type="hidden" name="deleteaccess" id="deleteaccess" value="">
	<input type="hidden" name="editaccess" id="editaccess" value="<?php echo htmlspecialchars($editaccess)?>">
	<input type="hidden" name="editexpiration" id="editexpiration" value="">
	<input type="hidden" name="editaccesslevel" id="editaccesslevel" value="">
	<input type="hidden" name="editgroup" id="editgroup" value="">
    <?php generateFormToken("collectionform");

    $page_header = $lang["sharecollection"];
    if ($editing && !$editexternalurl)
        {
        $page_header .= " - {$lang["editingexternalshare"]} $editaccess";
        }

    if (strpos($backurl, "/pages/team/team_external_shares.php") !== false)
        {
        $links_trail = array(
            array(
                'title' => $lang["teamcentre"],
                'href'  => $baseurl_short . "pages/team/team_home.php"
            ),
            array(
                'title' => $lang["manage_external_shares"],
                'href'  => $baseurl . $backurl
            ),
            array(
                'title' => $page_header,
                'help'  => "user/sharing-resources"
            )
        );

        renderBreadcrumbs($links_trail);
        }
    else
        {
        ?><h1><?php echo $page_header; render_help_link("user/sharing-resources");?></h1><?php
        }

	if(isset($warningtext))
		{
		echo "<div class='PageInformal'>" . $warningtext . "</div>";
		}

    if($collection["type"] == COLLECTION_TYPE_FEATURED && is_featured_collection_category($collection))
        {
        echo "<p>" . htmlspecialchars($lang["share_fc_warning"]) . "</p>";
        }
    ?>
	<div class="VerticalNav">
	<ul>
	<?php
	
	if(!$editing || $editexternalurl)
		{?>
		<?php if ($email_sharing) { ?><li><i aria-hidden="true" class="fa fa-fw fa-envelope"></i>&nbsp;<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_email.php?ref=<?php echo urlencode($ref); ?>&search=<?php echo urlencode($search); ?>&collection=<?php echo urlencode($collection_url); ?>&restypes=<?php echo urlencode($restypes); ?>&starsearch=<?php echo urlencode($starsearch); ?>&order_by=<?php echo urlencode($order_by); ?>&col_order_by=<?php echo urlencode($col_order_by); ?>&sort=<?php echo urlencode($sort); ?>&offset=<?php echo urlencode($offset); ?>&find=<?php echo urlencode($find); ?>&k=<?php echo urlencode($k); ?>"><?php echo $lang["emailcollectiontitle"]?></a></li><?php } ?>

		<?php
		# Share as a dash tile.
		global $home_dash,$anonymous_login,$username;

		if($home_dash && checkPermission_dashcreate() && !hook('replace_share_dash_create'))
			{?>
			<li><i aria-hidden="true" class="fa fa-fw fa-th"></i>&nbsp;<a href="<?php echo $baseurl_short;?>pages/dash_tile.php?create=true&tltype=srch&promoted_resource=true&freetext=true&all_users=1&link=/pages/search.php?search=!collection<?php echo $ref?>&order_by=relevance&sort=DESC"  onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["createnewdashtile"];?></a></li>
			<?php
		}
		?>
		
		<?php 
		if(!$internal_share_only && $hide_collection_share_generate_url==false)
			{ ?>
			<li><i aria-hidden="true" class="fa fa-fw fa-link"></i>&nbsp;<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_share.php?ref=<?php echo urlencode($ref) ?>&generateurl=true"><?php echo $lang["generateurl"]?></a></li> <?php 
			} 
		else // Just show the internal share URL straight away as there is no generate link
			{ ?>
			<h2><?php echo $lang["generateurlinternal"]; ?></h2><br />
			<p><input class="URLDisplay" type="text" value="<?php echo $baseurl?>/?c=<?php echo urlencode($ref) ?>">
			<?php
			}?>

		<?php hook("extra_share_options");
		}
	if (!$internal_share_only && ($editing || $generateurl))
		{
        if (!($hide_internal_sharing_url) && (!$editing || $editexternalurl) && $collection["public"]==1 || $ignore_collection_access)
			{
			?>
			<p><?php echo $lang["generateurlinternal"]?></p>
			
			<p><input class="URLDisplay" type="text" value="<?php echo $baseurl?>/?c=<?php echo urlencode($ref) ?>">
			<?php
			}
			
		if ($access==-1 || ($editing && !$editexternalurl))
			{
			?>
			<p><?php if (!$editing || $editexternalurl){echo $lang["selectgenerateurlexternal"];} ?></p>
			<?php
            if($editing)
                {
                echo "<div class='Question'><label>" . $lang["collectionname"]  . "</label><div class='Fixed'>" . i18n_get_collection_name($collection) . "</div><div class='clearerleft'></div></div>";
                }
            $shareoptions = array(
                "password"          => ($sharepwd != "" ? true : false),
                "editaccesslevel"   => $access,
                "editexpiration"    => $expires,
                "editgroup"         => $group,
                );

            render_share_options($shareoptions);
            
			hook("additionalcollectionshare");?>
			
			<div class="QuestionSubmit">
			<label for="buttons"> </label>
			<?php 
			if ($editing  && !$editexternalurl)
				{?>
				<input name="editexternalurl" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
				<?php
				}
			else
				{?>
				<input name="generateurl" type="submit" value="&nbsp;&nbsp;<?php echo $lang["generateexternalurl"]?>&nbsp;&nbsp;" />
				<?php 
				}
				?>
			</div>
			<?php
			}
        else if($editaccess == "" && !($editing && $editexternalurl))
            {
            // Access has been selected. Generate a new URL.
            $generated_access_key = '';

            if(empty($allowed_external_share_groups) || (!empty($allowed_external_share_groups) && in_array($user_group, $allowed_external_share_groups)))
                {
                $generated_access_key = generate_collection_access_key($collection, 0, 'URL', $access, $expires, $user_group, $sharepwd);
                }
            else if (!empty($allowed_external_share_groups) && !in_array($usergroup, $allowed_external_share_groups))
                {
                // Not allowed to select usergroup but this usergroup can not be used, default to the first entry in allowed_external_share_groups
                $generated_access_key = generate_collection_access_key($collection, 0, 'URL', $access, $expires, $allowed_external_share_groups[0], $sharepwd);
                }

            if('' != $generated_access_key)
                {
                ?>
                <p><?php echo $lang['generateurlexternal']; ?></p>
                <p>
                    <input class="URLDisplay" type="text" value="<?php echo $baseurl?>/?c=<?php echo urlencode($ref) ?>&k=<?php echo $generated_access_key; ?>">
                </p>
                <?php
                }
            else
                {
                ?>
                <div class="PageInformal"><?php echo $lang['error_generating_access_key']; ?></div>
                <?php
                }
            }

		# Process editing of external share
		if ($editexternalurl)
			{
			$editsuccess=edit_collection_external_access($editaccess,$access,$expires,getvalescaped("usergroup",""),$sharepwd);
			if($editsuccess){echo "<span style='font-weight:bold;'>".$lang['changessaved']." - <em>".$editaccess."</em>";}
			}
		}


?>
<?php hook("collectionshareoptions") ?>
</ul>
</div>

<?php if (collection_writeable($ref)||
	(isset($collection['savedsearch']) && $collection['savedsearch']!=null && ($userref==$collection["user"] || checkperm("h"))))
	{
	if (!($hide_internal_sharing_url) && (!$editing || $editexternalurl))
		{
		?>
		<h2><?php echo $lang["internalusersharing"]?></h2>
		<div class="Question">
		<label for="users"><?php echo $lang["attachedusers"]?></label>
		<div class="Fixed"><?php echo (($collection["users"]=="")?$lang["noattachedusers"]:htmlspecialchars($collection["users"])); ?><br /><br />
		<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_edit.php?ref=<?php echo urlencode($ref); ?>"><?php echo LINK_CARET ?><?php echo $lang["action-edit"];?></a>
		</div>
		<div class="clearerleft"> </div>
		</div>
		
		<p>&nbsp;</p>
		<?php
		}
	if(!$internal_share_only)
		{?>
		<h2><?php echo $lang["externalusersharing"]?></h2>

		<?php
        $keys=get_external_shares(array("share_collection"=>$ref));
		if (count($keys)==0)
			{
			?>
			<p><?php echo $lang["noexternalsharing"] ?></p>
			<?php
			}
		else
			{
			?>
			<div class="Listview">
			<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
			<tr class="ListviewTitleStyle">
			<td><?php echo $lang["accesskey"];?></td>
			<td><?php echo $lang["sharedby"];?></td>
			<td><?php echo $lang["sharedwith"];?></td>
			<td><?php echo $lang["lastupdated"];?></td>
			<td><?php echo $lang["lastused"];?></td>
			<td><?php echo $lang["expires"];?></td>
			<td><?php echo $lang["access"];?></td>
			<?php
			global $social_media_links;
			if (!empty($social_media_links))
				{
				?>
				<td><?php echo $lang['social_media']; ?></td>
				<?php
				}
			?>
			<?php hook("additionalcolexternalshareheader");?>
			<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
			</tr>
			<?php
			for ($n=0;$n<count($keys);$n++)
				{
				?>
				<tr>
				<td><div class="ListTitle"><a target="_blank" href="<?php echo $baseurl . "?c=" . urlencode($ref) . "&k=" . urlencode($keys[$n]["access_key"]) ?>"><?php echo htmlspecialchars($keys[$n]["access_key"]) ?></a></div></td>
				<td><?php echo htmlspecialchars(resolve_users($keys[$n]["user"]))?></td>
				<td><?php echo htmlspecialchars($keys[$n]["email"]) ?></td>
				<td><?php echo htmlspecialchars(nicedate($keys[$n]["date"],true, true, true));	?></td>
				<td><?php echo htmlspecialchars(nicedate($keys[$n]["lastused"],true, true, true)); ?></td>
				<td><?php echo htmlspecialchars(($keys[$n]["expires"]=="")?$lang["never"]:nicedate($keys[$n]["expires"],false)) ?></td>
				<td><?php echo htmlspecialchars(($keys[$n]["access"]==-1)?"":$lang["access" . $keys[$n]["access"]]); ?></td>
				<?php
				if (!empty($social_media_links))
					{
					?>
            		<td><?php renderSocialMediaShareLinksForUrl(generateURL($baseurl, array('c' => $ref, 'k' => $keys[$n]['access_key']))); ?></td>
					<?php
					}
				?>
                <?php hook("additionalcolexternalsharerecord");
                $editlink = generateurl($baseurl . "/pages/collection_share.php", 
                    array(
                        "ref"               => $keys[$n]["collection"],
                        "editaccess"        => $keys[$n]["access_key"],
                    ));
                ?>                
				<td><div class="ListTools">
				<a href="#" onClick="if (confirm('<?php echo $lang["confirmdeleteaccess"]?>')) {document.getElementById('deleteaccess').value='<?php echo htmlspecialchars($keys[$n]["access_key"]) ?>';document.getElementById('collectionform').submit(); return false;}"><?php echo LINK_CARET ?><?php echo $lang["action-delete"]?></a>
				<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $editlink; ?>"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?></a>
				</div></td>
				</tr>
				<?php
				}
			?>
			</table>
			</div>
			<?php
			}
		?>
		
		<?php
		}
	}
?>

</form>
</div>

<?php
include "../include/footer.php";
?>
