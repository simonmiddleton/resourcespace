<?php
include "../include/db.php";

include "../include/authenticate.php";

$ref        = getvalescaped('ref', '', true);
$user_group = getvalescaped('usergroup', '', true);

# fetch the current search (for finding simlar matches)
$search       = getvalescaped("search", "");
$order_by     = getvalescaped("order_by", "relevance");
$offset       = getvalescaped("offset", 0, true);
$restypes     = getvalescaped("restypes", "");
if (strpos($search,"!") !== false) { $restypes = ""; }
$archive      = getvalescaped("archive", 0, true);
$starsearch   = getvalescaped("starsearch", "");
$default_sort_direction = (substr($order_by,0,5) == "field") ? "ASC" : "DESC";
$sort         = getval("sort", $default_sort_direction);
$ajax         = filter_var(getval("ajax", false), FILTER_VALIDATE_BOOLEAN);
$modal = (getval("modal", "") == "true");

# Check if editing existing external share
$editaccess   = getvalescaped("editaccess", "");
$editing      = ($editaccess=="") ? false : true;

$generateurl  = getval("generateurl","") != "";
$editexternalurl = (getval("editexternalurl","") != "");

$access       = getvalescaped("access","",true);
$expires      = getvalescaped("expires","");
$sharepwd     = getvalescaped('sharepassword', '');
$backurl      = getvalescaped('backurl', '');

$minaccess=get_resource_access($ref);

# Check if sharing permitted
if (!can_share_resource($ref,$minaccess)) 
    {
    $show_error = true;
    $error      = $lang["error-permissiondenied"];
    }
	
$internal_share_only = checkperm("noex") || (isset($user_dl_limit) && intval($user_dl_limit) > 0);
        
# Process deletion of access keys
$deleteaccess = getvalescaped('deleteaccess', '');
if ('' != $deleteaccess && enforcePostRequest($ajax))
    {
    delete_resource_access_key($ref, $deleteaccess);
    resource_log($ref, LOG_CODE_SYSTEM, '', '', '', str_replace('%access_key', $deleteaccess, $lang['access_key_deleted']));
    }

# Process deletion of custom user access
$deleteusercustomaccess = getvalescaped('deleteusercustomaccess', '');
$user = getvalescaped('user', '');
if ($deleteusercustomaccess=='yes' && checkperm('v') && enforcePostRequest($ajax))
    {
    delete_resource_custom_user_access($ref, $user);
    resource_log($ref,'a', '', $lang['log-removedcustomuseraccess'] . $user);
    }
	
include "../include/header.php";
hook("resource_share_afterheader");

if (isset($show_error))
    { ?>
    <script type="text/javascript">
        alert('<?php echo $error;?>');
        history.go(-1);
    </script>
    <?php
    exit();
    }

$query_string = 'ref=' . urlencode($ref) . '&search=' . urlencode($search) . '&offset=' . urlencode($offset) . '&order_by=' . urlencode($order_by) . '&sort=' .urlencode($sort) . '&archive=' . urlencode($archive);

$page_header = $lang["share-resource"]; 
if($editing && !$editexternalurl)
    {
    $page_header .= " - {$lang["editingexternalshare"]} $editaccess";
    }
    ?>
<div class="BasicsBox">
    <div class="RecordHeader">
        <div class="BackToResultsContainer">
            <div class="backtoresults">
            <?php
            if($modal)
                {
                ?>
                <a href="#" class="closeLink fa fa-times" onclick="ModalClose();"></a>
                <?php
                }
                ?>
            </div>
        </div>
        <?php
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
                    'help'  => "user/resource-sharing"
                )
            );

            renderBreadcrumbs($links_trail);
            }
        else
            {
            ?><h1><?php echo $page_header; render_help_link("user/resource-sharing");?></h1>
            <p>
                <a href="<?php echo $baseurl_short . 'pages/view.php?' . $query_string ?>" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?>
                </a>
            </p><?php
            }
        ?>
    </div>
        <form method="post" id="resourceshareform" action="<?php echo $baseurl_short?>pages/resource_share.php?ref=<?php echo urlencode($ref)?>">
            <input type="hidden" name="ref" id="ref" value="<?php echo htmlspecialchars($ref) ?>">
            <input type="hidden" name="generateurl" id="generateurl" value="<?php echo $generateurl ? "true" :"" ?> ">
            <input type="hidden" name="deleteaccess" id="deleteaccess" value="">
            <input type="hidden" name="editaccess" id="editaccess" value="<?php echo htmlspecialchars($editaccess)?>">
            <input type="hidden" name="editexpiration" id="editexpiration" value="">
            <input type="hidden" name="editgroup" id="editgroup" value="">
            <input type="hidden" name="editaccesslevel" id="editaccesslevel" value="">
            <input type="hidden" name="editexternalurl" id="editexternalurl" value="">
			<input type="hidden" name="user" id="user" value="">
			<input type="hidden" name="deleteusercustomaccess" id="deleteusercustomaccess" value="">
            <?php
            if($modal)
                {
                ?>
                <input type="hidden" name="modal" value="true">
                <?php
                }
            generateFormToken("resourceshareform");
            ?>
            <div class="VerticalNav">
                <ul>
                <?php
                if(!$editing || $editexternalurl)
                    {
                    if ($email_sharing) 
                        { ?>
                        <li><i aria-hidden="true" class="fa fa-fw fa-envelope"></i>&nbsp;<a href="<?php echo $baseurl_short . 'pages/resource_email.php?' . $query_string ?>" onclick="return CentralSpaceLoad(this, true);"><?php echo $lang["emailresourcetitle"]?></a></li> 
                        <?php 
                        }
                    }
                if(!$editing)
                    { ?>
                    <p><?php echo $lang["generateurlinternal"];?></p>
                    <p><input class="URLDisplay" type="text" value="<?php echo $baseurl?>/?r=<?php echo $ref?>"></p>
                    <?php
                    }

                if (($editing || (getval("deleteaccess","") == "")))
                    {
                    if (($access=="" || ($editing && !$editexternalurl)) && !$internal_share_only)
                        {
                        ?>                    
                        <p><?php if (!$editing || $editexternalurl){ echo $lang["selectgenerateurlexternal"]; } ?></p>
                        <?php
                        render_share_options(false, $ref);
                        ?>
                        <div class="QuestionSubmit" s]]>
                            <label>&nbsp;</label>
                            <?php
                            if ($editing  && !$editexternalurl)
                                { ?>
                                <input name="editexternalurl" type="button" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;"
                                onclick="
                                document.getElementById('editexternalurl').value = '<?php echo $lang["save"]; ?>';
                                return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(document.getElementById('resourceshareform'), true);">
                                <?php
                                }
                            else
                                { ?>
                                <input name="generateurl" type="button" value="&nbsp;&nbsp;<?php echo $lang["generateexternalurl"]?>&nbsp;&nbsp;"
                                onclick="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(document.getElementById('resourceshareform'), true);">
                                <?php 
                                }
                            ?>
                        </div>
                        <?php
                        }
                    else if('' == getvalescaped('editaccess', '') && !$internal_share_only)
                        {
                        // Access has been selected. Generate a new URL.
                        $generated_access_key = '';

                        if(empty($allowed_external_share_groups) || (!empty($allowed_external_share_groups) && in_array($user_group, $allowed_external_share_groups)))
                            {
                            $generated_access_key = generate_resource_access_key($ref, $userref, $access, $expires, 'URL', $user_group, $sharepwd);
                            }
                        else if (!empty($allowed_external_share_groups) && !in_array($usergroup, $allowed_external_share_groups))
                        	{
                        	// Not allowed to select usergroup but this usergroup can not be used, default to the first entry in allowed_external_share_groups
                        	$generated_access_key = generate_resource_access_key($ref, $userref, $access, $expires, 'URL', $allowed_external_share_groups[0], $sharepwd);
                        	}

                        if('' != $generated_access_key)
                            {
                            ?>
                            <p><?php echo $lang['generateurlexternal']; ?></p>
                            <p>
                                <input class="URLDisplay" type="text" value="<?php echo $baseurl?>/?r=<?php echo urlencode($ref) ?>&k=<?php echo $generated_access_key; ?>">
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
                        $editsuccess = edit_resource_external_access($editaccess,$access,$expires,$user_group,$sharepwd);
                        if($editsuccess)
                            {
                            echo "<span style='font-weight:bold;'>".$lang['changessaved']." - <em>".$editaccess."</em>";
                            }
                        }
                    }
                    ?>
                </ul>
            
        <?php 
        # Do not allow access to the existing shares if the user has restricted access to this resource.
        if (!$internal_share_only && $minaccess==0)
            {
            ?>
            <h2><?php echo $lang["externalusersharing"]?></h2>
            <?php
            $keys = get_resource_external_access($ref);
            if (count($keys) == 0)
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
                            <td><?php echo $lang["accesskey"];    ?></td>
                            <td><?php echo $lang["type"];         ?></td>
                            <td><?php echo $lang["sharedby"];     ?></td>
                            <td><?php echo $lang["sharedwith"];   ?></td>
                            <td><?php echo $lang["lastupdated"];  ?></td>
                            <td><?php echo $lang["lastused"];     ?></td>
                            <td><?php echo $lang["expires"];      ?></td>
                            <td><?php echo $lang["access"];       ?></td>
                            <?php
                            global $social_media_links;
                            if (!empty($social_media_links))
                                {
                                ?>
                                <td><?php echo $lang['social_media']; ?></td>
                                <?php
                                }
                            ?>
                            <?php hook("additionalresourceexternalshareheader");?>
                            <td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
                        </tr>
                <?php
                foreach ($keys as $key)
                    {
                    if(!$resource_share_filter_collections || in_array($userref,explode(",",$key["users"])))
                        {
                        $collection_share = is_numeric($key["collection"]);
                        if ($collection_share) 
                        	{
                        	$url = $baseurl . "?c=" . urlencode($key["collection"]);
                        	}
                        else
                        	{
                        	$url = $baseurl . "?r=" . urlencode($ref);
                        	}                                                  
                        $url    .= "&k=" . urlencode($key["access_key"]);
                        $type    = ($collection_share)     ? $lang["sharecollection"] : $lang["share-resource"];
                        $keyexpires = ($key["expires"] == "") ? $lang["never"] : nicedate($key["expires"],false);
                        $keyaccess  = ($key["access"] == -1)  ? "" : $lang["access" . $key["access"]];
                        ?>
                        <tr>
                            <td><div class="ListTitle"><a target="_blank" href="<?php echo $url ?>"><?php echo htmlspecialchars($key["access_key"]) ?></a></div></td>
                            <td><?php echo $type                                              ?></td>
                            <td><?php echo htmlspecialchars(resolve_users($key["users"]))     ?></td>
                            <td><?php echo htmlspecialchars($key["emails"])                   ?></td>
                            <td><?php echo htmlspecialchars(nicedate($key["maxdate"],true));  ?></td>
                            <td><?php echo htmlspecialchars(nicedate($key["lastused"],true)); ?></td>
                            <td><?php echo htmlspecialchars($keyexpires)                         ?></td>
                            <td><?php echo htmlspecialchars($keyaccess);                         ?></td>
                            <?php
                            if (!empty($social_media_links))
                                {
                                ?>
                                <td><?php renderSocialMediaShareLinksForUrl($url);                ?></td>
                                <?php
                                }
                            ?>
                            <?php hook("additionalresourceexternalsharerecord");?>
                            <td>
                                <div class="ListTools">
                                <?php 
                                if ($collection_share)
                                    {
                                    ?>
                                    <a onClick="return CentralSpaceLoad(this,true);" href="collection_share.php?ref=<?php echo $key["collection"] ?>"><?php echo LINK_CARET ?><?php echo $lang["viewcollection"]?></a>
                                    <?php
                                    }
                                else
                                    {
                                    ?>
                                    <a href="#" onClick="return resourceShareDeleteShare('<?php echo $key["access_key"] ?>');"><?php echo LINK_CARET ?><?php echo $lang["action-delete"]?></a>      
                                    <a href="#" onClick="return resourceShareEditShare(<?php echo "'{$key["access_key"]}', '{$key["expires"]}', '{$key["access"]}', '{$key["usergroup"]}'" ?>);"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?></a>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                        }
                    }
                    ?>
                    </table>
                <?php
                }
                ?>
            </div>
			<script type="text/javascript">
			    function resourceShareDeleteShare(access_key) {
			        if (confirm('<?php echo $lang["confirmdeleteaccessresource"]?>')) {
			            document.getElementById('deleteaccess').value = access_key;
                        <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(document.getElementById('resourceshareform'),true);
			        }
			        return false;
			    }
			    function resourceShareEditShare(access_key, expires, access, user_group) {
			        document.getElementById('editaccess').value = access_key;
			        document.getElementById('editexpiration').value = expires;
			        document.getElementById('editaccesslevel').value = access;
			        document.getElementById('editgroup').value = user_group;
			        <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(document.getElementById('resourceshareform'),true);
			        return false;
			    }
				function resourceShareDeleteUserCustomAccess(user) {
			        if (confirm('<?php echo $lang["confirmdeleteusercustomaccessresource"] ?>')) {
			            document.getElementById('deleteusercustomaccess').value = 'yes';
						document.getElementById('user').value = user;
			            document.getElementById('resourceshareform').submit(); 
			        }
			        return false;
			    }
			</script>
            <?php
            }
            
	    ?>
	    
	    
	    <h2><?php echo $lang["custompermissions"]?></h2>
            <?php
            $custom_access_rows = get_resource_custom_access_users_usergroups($ref);
            if (count($custom_access_rows) == 0)
                {
                ?>
                <p><?php echo $lang["remove_custom_access_no_users_found"] ?></p>
                <?php
                }
            elseif ( (count($custom_access_rows) > 0) && checkperm('v') )
                {
                ?>
                <div class="Listview">
					<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
						<tr class="ListviewTitleStyle">
							<td><?php echo $lang["user"];   ?></td>
							<td><?php echo $lang["property-user_group"];        ?></td>
							<td><?php echo $lang["expires"];  ?></td>
							<td><?php echo $lang["access"];    ?></td>
							<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
						</tr>
					<?php
						foreach ($custom_access_rows as $ca)
						{
						$custexpires = ($ca["expires"] == "") ? $lang["never"] : nicedate($ca["expires"],false);
						$custaccess  = ($ca["access"] == -1)  ? "" : $lang["access" . $ca["access"]];
						?><tr>
							<td><?php echo htmlspecialchars($ca["user"]); ?></td>
							<td><?php echo htmlspecialchars($ca["usergroup"]); ?></td>
							<td><?php echo htmlspecialchars($custexpires); ?></td>
							<td><?php echo htmlspecialchars($custaccess); ?></td>
							<td><div class="ListTools"><a href="#" onClick="return resourceShareDeleteUserCustomAccess(<?php echo get_user_by_username($ca["user"]) ?>);"><?php echo LINK_CARET ?><?php echo $lang["action-delete"]?></a></div></td>
						</tr>
						<?php
						}
					?></table>
				</div> <!-- end Listview --><?php
				}
		    ?>
        </div>
    </form>
</div> <!-- BasicsBox -->

<?php
include "../include/footer.php";