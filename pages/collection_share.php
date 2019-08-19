<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php";
include "../include/search_functions.php";
include "../include/resource_functions.php";
include_once "../include/collections_functions.php";
include_once '../include/render_functions.php';

// Fetch vars
$ref        = getvalescaped('ref', '', true);
$user_group = getvalescaped('usergroup', '', true);

# if bypass sharing page option is on, redirect to e-mail
if ($bypass_share_screen)
	{
	redirect('pages/collection_email.php?ref='.$ref ) ;
	}

$collection=get_collection($ref);

# Check access
if (!collection_readable($ref)) {exit($lang["no_access_to_collection"]);}

#Check if sharing allowed
if (checkperm("b") || !$allow_share) {
        $show_error=true;
        $error=$lang["error-permissiondenied"];
        }

$internal_share_only=checkperm("noex");

# Check if editing existing external share
$editaccess=getvalescaped("editaccess","");
($editaccess=="")?$editing=false:$editing=true;

$editexternalurl = (getval("editexternalurl","")!="");
	
#Check if any resources are not active
$collectionstates=is_collection_approved($ref);
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

# Get min access to this collection
$minaccess=collection_min_access($ref);

if (($minaccess>=1 && !$restricted_share)) # Minimum access is restricted or lower and sharing of restricted resources is not allowed. The user cannot share this collection.
        {
        $show_error=true;
        $error=$lang["restrictedsharecollection"];
        }
		
if (!$collection_allow_empty_share && count(get_collection_resources($ref))==0) # Sharing an empty collection?
    {
    $show_error=true;
    $error=$lang["cannotshareemptycollection"];
    }

if(!$allow_custom_access_share && isset($customgroupaccess) && isset($customuseraccess)  && ($customgroupaccess || $customuseraccess))
	{ 
	$show_error=true;
	$error=$lang["customaccesspreventshare"];
	}
		


# Process deletion of access keys
if (getval("deleteaccess","")!="" && !isset($show_error) && enforcePostRequest(getval("ajax", false)))
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
	<input type="hidden" name="generateurl" id="generateurl" value="">
    <?php generateFormToken("collectionform"); ?>
	<h1><?php echo $lang["sharecollection"]; if($editing && !$editexternalurl){echo " - ".$lang["editingexternalshare"]." ".$editaccess;};render_help_link("user/sharing-resources");?></h1>
	<?php
	if(isset($warningtext))
		{
		echo "<div class='PageInformal'>" . $warningtext . "</div>";
		}?>
	
	<div class="VerticalNav">
	<ul>
	<?php
	
	if(!$editing || $editexternalurl)
		{?>
		<?php if ($email_sharing) { ?><li><i aria-hidden="true" class="fa fa-fw fa-envelope"></i>&nbsp;<a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_email.php?ref=<?php echo urlencode($ref) ?>"><?php echo $lang["emailcollectiontitle"]?></a></li><?php } ?>

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
	if (!$internal_share_only && ($editing || getval("generateurl","")!=""))
		{
			global $ignore_collection_access;
		if (!($hide_internal_sharing_url) && (!$editing || $editexternalurl) && $collection["public"]==1 || $ignore_collection_access)
			{
			?>
			<p><?php echo $lang["generateurlinternal"]?></p>
			
			<p><input class="URLDisplay" type="text" value="<?php echo $baseurl?>/?c=<?php echo urlencode($ref) ?>">
			<?php
			}
			
		$access=getvalescaped("access","");
		$expires=getvalescaped("expires","");
        $sharepwd = getvalescaped('sharepassword', '');
		if ($access=="" || ($editing && !$editexternalurl))
			{
			?>
			<p><?php if (!$editing || $editexternalurl){echo $lang["selectgenerateurlexternal"];} ?></p>
			
			<?php
            render_share_options(true, $ref);
            
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
        else if('' == getvalescaped('editaccess', ''))
            {
            // Access has been selected. Generate a new URL.
            $generated_access_key = '';

            if(empty($allowed_external_share_groups) || (!empty($allowed_external_share_groups) && in_array($user_group, $allowed_external_share_groups)))
                {
                $generated_access_key = generate_collection_access_key($ref, 0, 'URL', $access, $expires, $user_group, $sharepwd);
                }
            else if (!empty($allowed_external_share_groups) && !in_array($usergroup, $allowed_external_share_groups))
                        {
                        // Not allowed to select usergroup but this usergroup can not be used, default to the first entry in allowed_external_share_groups
                        $generated_access_key = generate_collection_access_key($ref, 0, 'URL', $access, $expires, $allowed_external_share_groups[0], $sharepwd);
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
		$keys=get_collection_external_access($ref);
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
				<td><?php echo htmlspecialchars(resolve_users($keys[$n]["users"]))?></td>
				<td><?php echo htmlspecialchars($keys[$n]["emails"]) ?></td>
				<td><?php echo htmlspecialchars(nicedate($keys[$n]["maxdate"],true));	?></td>
				<td><?php echo htmlspecialchars(nicedate($keys[$n]["lastused"],true)); ?></td>
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
				<?php hook("additionalcolexternalsharerecord");?>
				<td><div class="ListTools">
				<a href="#" onClick="if (confirm('<?php echo $lang["confirmdeleteaccess"]?>')) {document.getElementById('deleteaccess').value='<?php echo htmlspecialchars($keys[$n]["access_key"]) ?>';document.getElementById('collectionform').submit(); return false;}"><?php echo LINK_CARET ?><?php echo $lang["action-delete"]?></a>
				<a href="#" onClick="document.getElementById('editaccess').value='<?php echo htmlspecialchars($keys[$n]["access_key"]) ?>';document.getElementById('editexpiration').value='<?php echo htmlspecialchars($keys[$n]["expires"]) ?>';document.getElementById('editaccesslevel').value='<?php echo htmlspecialchars($keys[$n]["access"]) ?>';document.getElementById('editgroup').value='<?php echo htmlspecialchars($keys[$n]["usergroup"]) ?>';CentralSpacePost(document.getElementById('collectionform'),true);return false;"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?></a>
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
