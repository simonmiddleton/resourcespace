<?php
/**
 * User management start page (part of team center)
 * 
 * @Package ResourceSpace
 * @Subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php";if (!checkperm("u")) {exit ("Permission denied.");}

$offset=getvalescaped("offset",0,true);
$find=getvalescaped("find","");
$order_by=getvalescaped("order_by","u.username");
$group=getvalescaped("group",0);
$approval_state_text = array(0 => $lang["notapproved"],1 => $lang["approved"], 2 => $lang["disabled"]);
$backurl = getvalescaped("backlink", "");

# Pager
$per_page=getvalescaped("per_page_list",$default_perpage_list);rs_setcookie('per_page_list', $per_page);


if (array_key_exists("find",$_POST)) {$offset=0;} # reset page counter when posting

if (getval("newuser","")!="" && !hook("replace_create_user_save") && enforcePostRequest(getval("ajax", false)))
	{
	$new=new_user(getvalescaped("newuser",""));
	if ($new===false)
		{
		$error=$lang["useralreadyexists"];
		}
	else
		{
		hook("afterusercreated");
		redirect($baseurl_short."pages/team/team_user_edit.php?ref=" . $new);
		}
	}

function show_team_user_filter_search(){
	global $baseurl_short,$lang,$group,$find;
	$groups=get_usergroups(true);
	?>
	<div class="BasicsBox">
		<form method="get" action="<?php echo $baseurl_short?>pages/team/team_user.php">
			<div class="Question">  
				<label for="group"><?php echo $lang["group"]; ?></label>
				<?php if (!hook('replaceusergroups')) { ?>
					<div class="tickset">
						<div class="Inline">
							<select name="group" id="group" onChange="this.form.submit();">
								<option value="0"<?php if ($group == 0) { echo " selected"; } ?>><?php echo $lang["all"]; ?></option>
								<?php
								for($n=0;$n<count($groups);$n++){
									?>
									<option value="<?php echo $groups[$n]["ref"]; ?>"<?php if ($group == $groups[$n]["ref"]) { echo " selected"; } ?>><?php echo $groups[$n]["name"]; ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
				<?php } ?>
				<div class="clearerleft"> </div>
			</div>
		</form>
	</div>

	<div class="BasicsBox">
		<form method="get" action="<?php echo $baseurl_short?>pages/team/team_user.php">
			<div class="Question">
				<label for="find"><?php echo $lang["searchusers"]?></label>
				<div class="tickset">
				 <div class="Inline"><input type=text name="find" id="find" value="<?php echo $find?>" maxlength="100" class="shrtwidth" /></div>
				 <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["searchbutton"]?>&nbsp;&nbsp;" /></div>
				</div>
				<div class="clearerleft"> </div>
			</div>
		</form>
	</div>
	<?php
}

include "../../include/header.php";
?>
<div class="BasicsBox"> 
	<?php
	// Breadcrumbs links
	if (strpos($backurl, "pages/admin/admin_group_management.php") !== false)
		{
		// Came from Manage user groups page
		$links_trail = array(
		    array(
		        'title' => $lang["systemsetup"],
		        'href'  => $baseurl_short . "pages/admin/admin_home.php"
		    ),
		    array(
		        'title' => $lang["page-title_user_group_management"],
				'href'  => $backurl
		    )
	    );
		}
	else
		{
		// Assume we came from Manage users page
		$links_trail = array(
	        array(
	            'title' => $lang["teamcentre"],
	            'href'  => $baseurl_short . "pages/team/team_home.php"
	        )
		);
		}

	$links_trail[] = array(
        'title' => $lang["manageusers"],
    );

	renderBreadcrumbs($links_trail);
	?>
	
	<p class="PageIntrotext"><?php echo text("introtext");render_help_link('systemadmin/creating-users');?></p>

	<?php if (isset($error)) { ?>
		<div class="FormError">!! <?php echo $error?> !!</div>
	<?php } ?>

	<?php if($team_user_filter_top){show_team_user_filter_search();}?>

	<?php 
	hook('modifyusersearch');

	# Fetch rows
	$users=get_users($group,$find,$order_by,true,$offset+$per_page);
	$groups=get_usergroups(true);
	$results=count($users);
	$totalpages=ceil($results/$per_page);
	$curpage=floor($offset/$per_page)+1;

	$url=$baseurl_short."pages/team/team_user.php?group=" . $group . "&order_by=" . $order_by . "&find=" . urlencode($find);
	$jumpcount=1;

	# Create an a-z index
	$atoz="<div class=\"InpageNavLeftBlock\">";
	if ($find=="") {$atoz.="<span class='Selected'>";}
	$atoz.="<a href=\"" . $baseurl . "/pages/team/team_user.php?order_by=u.username&group=" . $group . "&find=\" onClick=\"return CentralSpaceLoad(this);\">" . $lang["viewall"] . "</a>";
	if ($find=="") {$atoz.="</span>";}
	$atoz.="&nbsp;&nbsp;";
	for ($n=ord("A");$n<=ord("Z");$n++)
		{
		if ($find==chr($n)) {$atoz.="<span class='Selected'>";}
		$atoz.="<a href=\"" . $baseurl . "/pages/team/team_user.php?order_by=u.username&group=" . $group . "&find=" . chr($n) . "\" onClick=\"return CentralSpaceLoad(this);\">&nbsp;" . chr($n) . "&nbsp;</a> ";
		if ($find==chr($n)) {$atoz.="</span>";}
		$atoz.=" ";
		}
	$atoz.="</div>";

	?>

	<div class="TopInpageNav"><div class="TopInpageNavLeft"><?php echo $atoz?>	<div class="InpageNavLeftBlock"><?php echo $lang["resultsdisplay"]?>:
		<?php 
		for($n=0;$n<count($list_display_array);$n++){?>
		<?php if ($per_page==$list_display_array[$n]){?><span class="Selected"><?php echo $list_display_array[$n]?></span><?php } else { ?><a href="<?php echo $url; ?>&per_page_list=<?php echo $list_display_array[$n]?>" onClick="return CentralSpaceLoad(this);"><?php echo $list_display_array[$n]?></a><?php } ?>&nbsp;|
		<?php } ?>
		<?php if ($per_page==99999){?><span class="Selected"><?php echo $lang["all"]?></span><?php } else { ?><a href="<?php echo $url; ?>&per_page_list=99999" onClick="return CentralSpaceLoad(this);"><?php echo $lang["all"]?></a><?php } ?>
		</div></div> <?php pager(false); ?><div class="clearerleft"></div></div>

	<div class="Listview">
	<?php if(!hook('overrideuserlist')):

	function addColumnHeader($orderName, $labelKey)
	{
		global $baseurl, $group, $order_by, $find, $lang;

		if ($order_by == $orderName)
			$image = '<span class="ASC"></span>';
		else if ($order_by == $orderName . ' desc')
			$image = '<span class="DESC"></span>';
		else
			$image = '';

		?><td><a href="<?php echo $baseurl ?>/pages/team/team_user.php?offset=0&group=<?php
				echo $group; ?>&order_by=<?php echo $orderName . ($order_by==$orderName ? '+desc' : '');
				?>&find=<?php echo urlencode($find)?>" onClick="return CentralSpaceLoad(this);"><?php
				echo $lang[$labelKey] . $image ?></a></td>
		<?php
	}

	?>
	<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
	<tr class="ListviewTitleStyle">
	<?php
		addColumnHeader('u.username', 'username');
		if (!hook("replacefullnameheader"))
			addColumnHeader('u.fullname', 'fullname');
		if (!hook("replacegroupnameheader"))
			addColumnHeader('g.name', 'group');
		if (!hook("replaceemailheader"))
			addColumnHeader('email', 'email');
		addColumnHeader('created', 'created');
		addColumnHeader('approved', 'status');
		addColumnHeader('last_active', 'lastactive');
		hook("additional_user_column_header");
	?>
	<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
	</tr>
	<?php
    // Parse $url var as this is being manipulated by the pager(). This allows us to build correct URLs later on (e.g for team_user_edit_url)
    $url_parse = parse_url($url);
    $url_qs = [];
    if(isset($url_parse['query']))
        {
        parse_str($url_parse['query'], $url_qs);
        }

	for ($n=$offset;(($n<count($users)) && ($n<($offset+$per_page)));$n++)
		{
        $team_user_edit_params = array(
            'ref' => $users[$n]["ref"],
            'backurl' => generateURL($url_parse['path'], $url_qs, ['offset' => $offset]),
        );
        $team_user_edit_url = generateURL("{$baseurl}/pages/team/team_user_edit.php", $team_user_edit_params);

        $team_user_log_params = array(
            'actasuser' => $users[$n]["ref"],
            'backurl' => generateURL($url_parse['path'], $url_qs, ['offset' => $offset]),
        );
        $team_user_log_url = generateURL("{$baseurl}/pages/admin/admin_system_log.php", $team_user_log_params);

		?>
		<tr>
	        <td>
	            <div class="ListTitle">
	                <a href="<?php echo $team_user_edit_url; ?>" onClick="return CentralSpaceLoad(this, true);"><?php echo htmlspecialchars($users[$n]["username"]); ?></a>
	            </div>
	        </td>
		<?php if (!hook("replacefullnamerow")){?>
		<td><?php echo htmlspecialchars($users[$n]["fullname"])?></td>
		<?php } ?>
		<?php if (!hook("replacegroupnamerow")){?>
		<td><?php echo $users[$n]["groupname"]?></td>
		<?php } ?>
		<?php if (!hook("replaceemailrow")){?>
		<td><?php echo htmlentities($users[$n]["email"])?></td>
		<?php } ?>
		<td><?php echo nicedate($users[$n]["created"]) ?></td>
		<td><?php echo $approval_state_text[$users[$n]["approved"]] ?></td>
		<td><?php echo nicedate($users[$n]["last_active"],true) ?></td>
		<?php hook("additional_user_column");?>
		<td><?php if (($usergroup==3) || ($users[$n]["usergroup"]!=3)) { ?><div class="ListTools">
		<a href="<?php echo $team_user_log_url; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["log"]?></a>
		&nbsp;
		<a href="<?php echo $team_user_edit_url; ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?></a>
        <?php
        if($userref != $users[$n]["ref"])
            {
            // Add message link
            echo '<a href="' . $baseurl_short . 'pages/user/user_message.php?msgto=' . $users[$n]["ref"] . '"  onClick="return CentralSpaceLoad(this,true);">' .  LINK_CARET . $lang["message"] . '</a>';
            }       
		hook("usertool")?>
		</div><?php } ?>
		</td>
		</tr>
		<?php
		}
	?>

	</table>
	<?php endif; // hook overrideuserlist ?>
	</div>
	<div class="BottomInpageNav">
	<div class="BottomInpageNavLeft">
	<strong><?php echo $lang["total"] . ": " . count($users); ?> </strong><?php echo $lang["users"]; ?>
	</div>

	<?php pager(false); ?></div>
</div>




<?php if(!$team_user_filter_top){show_team_user_filter_search();}?>

<?php
if(!hook("replace_create_user"))
    {
    ?>
    <div class="BasicsBox">
        <form id="new_user_form" method="post" action="<?php echo $baseurl_short?>pages/team/team_user.php">
            <?php generateFormToken("create_new_user"); ?>
    		<div class="Question">
    			<label for="newuser"><?php echo $lang["createuserwithusername"]?></label>
    			<div class="tickset">
    			 <div class="Inline"><input type=text name="newuser" id="newuser" maxlength="50" class="shrtwidth" /></div>
    			 <div class="Inline"><input name="Submit" id="create_user_button" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"]?>&nbsp;&nbsp;" /></div>
    			</div>
    			<div class="clearerleft"> </div>
    		</div>
    	</form>
    </div>
    <?php
    }

    hook('render_options_to_create_users');
    
if ($user_purge)
	{
	?>
	<div class="BasicsBox">
	<div class="Question"><label><?php echo $lang["purgeusers"]?></label>
	<div class="Fixed"><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl ?>/pages/team/team_user_purge.php"><?php echo LINK_CARET ?><?php echo $lang["purgeusers"]?></a></div>
	<div class="clearerleft"> </div></div>
	</div>
	<?php
	}
?>

<?php if (!hook("replaceusersonline")) { ?>
<div class="BasicsBox">
<div class="Question"><label><?php echo $lang["usersonline"]?></label>
<div class="Fixed">
<?php
$active=get_active_users();
for ($n=0;$n<count($active);$n++) {if($n>0) {echo", ";}echo "<b><a href='" . $baseurl . "/pages/team/team_user_edit.php?ref=" . $active[$n]["ref"] . "&backurl=" . urlencode($url . "&offset=" . $offset) . "' onClick='return CentralSpaceLoad(this,true);'>" . htmlspecialchars($active[$n]["username"]) . "</a></b> (" . $active[$n]["t"] . ")";}
?>
</div><div class="clearerleft"> </div></div></div>	
<?php } // end hook("replaceusersonline")
?>


<?php
include "../../include/footer.php";
?>
