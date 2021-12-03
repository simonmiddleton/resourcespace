<?php

DEFINE('WATCHED_SEARCHES_ITEMS_PER_PAGE',10);

include_once "../../../include/db.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/search_do.php";
include_once "../include/search_notifications_functions.php";

$plugin_name            = "rse_search_notifications";
$plugin_active          = in_array($plugin_name, $plugins); // is the plugin active?
if (!$plugin_active)
    {
    redirect("pages/".$default_home_page);
    }
    
$all_users_mode=getval("allusers",0)==1 && checkperm("a");
$find=getvalescaped("find","");
$callback=getvalescaped("callback","");
$orderby = getvalescaped("orderby",-1);

if ($callback!="")
	{
	$ref = getvalescaped("ref", -1,true);
	$search = getvalescaped("search", "");
	$restypes = getvalescaped("restypes", "");
	$archive = getvalescaped("archive", "");

	switch ($callback)
		{
		case "add":
			search_notification_add($search,$restypes,$archive);
			break;

		case "delete":
			search_notification_delete($ref,$all_users_mode);
			break;

		case "enable":
			search_notification_enable($ref,$all_users_mode);
			break;

		case "enable_all":
			search_notification_enable_all($all_users_mode);
			break;

		case "disable":
			search_notification_disable($ref,$all_users_mode);
			break;

		case "disable_all":
			search_notification_disable_all($all_users_mode);
			break;

		case "checknow":
			search_notification_process($userref,$ref);
			break;
		}
	}

include "../../../include/header.php";

$watched_searches=array();
$watched_searches_found=search_notifications_get($watched_searches,($all_users_mode ? "" : $userref),false,$find,abs($orderby),($orderby > 0 ? "ASC" : "DESC"));

// ----- Start of pager variables

$offset=getval('offset',0,true);
$totalpages=ceil(count($watched_searches)/WATCHED_SEARCHES_ITEMS_PER_PAGE);
$curpage=floor($offset/WATCHED_SEARCHES_ITEMS_PER_PAGE)+1;
$per_page=WATCHED_SEARCHES_ITEMS_PER_PAGE;
$jumpcount=1;

$url_set_params = array();
if($find != "")
    {
    $url_set_params["find"] = $find;
    }
if($all_users_mode)
    {
    $url_set_params["allusers"] = 1;
    }
$url = generateURL($watched_searches_url, array("offset" => $offset), $url_set_params);

// ----- End of pager variables

?>
<div class="BasicsBox">
	<h1><?php echo $lang["search_notifications_watched_searches"]; ?></h1>
	<p><?php echo $lang["search_notifications_introtext"]; ?></p>

	<div class="TopInpageNav">

		<form method="post" action="<?php echo $url; ?>" onsubmit="return CentralSpacePost(this,true);">
            <?php generateFormToken("rse_search_notifications_watched_searches"); ?>
			<div class="Question">
				<div class="tickset">
					<div class="Inline">
						<input type="text" name="find" id="find" value="<?php echo htmlspecialchars($find); ?>" maxlength="100" class="shrtwidth">
					</div>
					<input type="hidden" name="offset" id="offset" value="0" />
					<div class="Inline"><input name="Submit" type="submit" value="<?php echo $lang["searchbutton"]; ?>"></div>
					<div class="Inline"><input name="Clear" type="button" onclick="document.getElementById('find').value=''; return CentralSpacePost(this.form,true);" value="<?php echo $lang["clearbutton"]; ?>"></div>
				</div>
				<div class="clearerleft"> </div>
			</div>
		</form>

		<?php
			if (count($watched_searches) > WATCHED_SEARCHES_ITEMS_PER_PAGE)
			{
			?>
			<div class="TopInpageNavLeft">
				<?php pager(true) ?>
			</div>
			<?php
			}
		?><div class="clearerleft"></div>

	<?php

	if (checkperm("a"))
		{
		?><form action="<?php echo $watched_searches_url; ?>" onchange="CentralSpacePost(this,true);">
            <?php generateFormToken("rse_search_notifications_watched_searches"); ?>
			<input type="hidden" name="offset" id="offset" value="0" />
			<input type="hidden" name="find" id="find" value="<?php echo htmlspecialchars($find); ?>" >
			<label for="allusers"><?php echo $lang['search_notifications_show_for_all_users']; ?></label>
			<?php
			if ($all_users_mode)
				{
				?><input type="checkbox" name="allusers" id="allusers" value="0" checked="checked"><br /><?php
				$url.=strpos($url,'?')!==false?'&':'?'."allusers=1&";
				}
			else
				{
				?><input type="checkbox" name="allusers" id="allusers" value="1"><br /><?php
				}
			?><div class="clearerleft"></div>
		</form>
		<br />
		<?php
		}

	$any_enabled = false;
	$any_disabled = false;

	foreach ($watched_searches as $ws)		// if there are unread messages show option to mark all as read
		{
		if ($ws['enabled']==1)
			{
			$any_enabled=true;
			}
		else
			{
			$any_disabled=true;
			}
		if ($any_enabled && $any_disabled)
			{
			break;
			}
		}

	if ($any_enabled)
		{
		?><a href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>callback=disable_all" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php
		echo $lang['disable_all']; ?></a>
		<?php
		}

	if ($any_enabled && $any_disabled)
		{
		?><br /><?php
		}

	if ($any_disabled)
		{
		?><a href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>callback=enable_all" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php
		echo $lang['enable_all']; ?></a>
		<?php
		}

	function render_sortable_header($title,$col_number)
		{
		global $orderby,$url;
		if ($orderby==$col_number || $orderby==-$col_number) { ?><span class="Selected"><?php }
		?><a href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>offset=0&orderby=<?php if($orderby==$col_number) { ?>-<?php } echo $col_number; ?>" onclick="return CentralSpaceLoad(this);" ><?php echo $title; ?></a><?php
		if ($orderby==$col_number) { ?><div class="ASC">&nbsp;</div><?php }
		if ($orderby==-$col_number) { ?><div class="DESC">&nbsp;</div><?php }
		if ($orderby==$col_number || $orderby==-$col_number) { ?></span><?php }
		}

?></div> <!-- end of TopInpageNav -->

<?php
	if(!$watched_searches_found)
	{
		echo $lang['search_notifications_no_watched_searches'];
        ?>
        </div> <!-- end of BasicsBox -->
        <?php
		include "../../../include/footer.php";
		return;
	}
?>

	<div class="Listview">
		<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
			<tr class="ListviewTitleStyle">
				<td><?php render_sortable_header($lang["created"],2); ?></td>
				<td><?php render_sortable_header($lang["username"],3); ?></td>
				<td><?php render_sortable_header($lang["columnheader-title"],4); ?></td>
				<td><?php render_sortable_header($lang["columnheader-last-found"],11); ?></td>
				<td><?php render_sortable_header($lang["columnheader-enabled"],8); ?></td>
				<td><div class="ListTools"><?php echo $lang["tools"]; ?></div></td>
			</tr>
			<?php
			for ($i=$offset; $i<$offset + WATCHED_SEARCHES_ITEMS_PER_PAGE; $i++)
				{
				if(!isset($watched_searches[$i]))
					{
					break;
					}
				$ws = $watched_searches[$i];
				$view_search_url = search_notification_make_url($ws);
				?><tr>
					<td><?php echo nicedate(htmlspecialchars($ws["created"]), true, true, true); ?></td>
					<td><?php echo highlightkeywords(htmlspecialchars($ws["username"]),$find); ?></td>
					<td><a href="<?php echo $view_search_url; ?>"><?php echo highlightkeywords(htmlspecialchars($ws["title"]),$find); ?></a></td>
					<td><a href="<?php echo $view_search_url; ?>"><?php echo htmlspecialchars($ws["checksum_matches"]); ?></a></td>
					<td><?php
						if ($ws["enabled"])
							{
							echo $lang["yes"];
							?></td>
							<td>
								<div class="ListTools">
									<a href="<?php echo $view_search_url; ?>" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php echo $lang["searchbutton"]; ?></a>
									<?php
										if($ws['owner']==$userref)
											{
											?><a
												href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>callback=checknow&ref=<?php echo $ws["ref"]; ?>"
												onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php
											echo $lang["checknow"]; ?></a>
											<?php
											}
									?><a href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>callback=disable&ref=<?php echo $ws["ref"]; ?>" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php
										echo $lang['disable']; ?></a>
							<?php
							}
						else
							{
							echo $lang["no"];
							?></td>
							<td>
								<div class="ListTools">
									<a href="<?php echo $view_search_url; ?>" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php echo $lang["searchbutton"]; ?></a>
									<a href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>callback=enable&ref=<?php echo $ws["ref"]; ?>" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php
										echo $lang['enable']; ?></a>
							<?php
							}
						?>
									<a href="<?php echo $url; echo strpos($url,'?')!==false?'&':'?'; ?>callback=delete&ref=<?php echo $ws["ref"]; ?>" onclick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php
										echo $lang['action-delete']; ?></a>
								</div>
					</td>
				</tr>
				<?php
			}
			?>
		</table>
	</div>	<!-- end Listview -->

	<?php
	if (count($watched_searches) > WATCHED_SEARCHES_ITEMS_PER_PAGE)
		{
		?>
		<div class="BottomInpageNav">
			<?php pager(false) ?>
		</div>
		<?php
		}
	?>

</div> <!-- end of BasicsBox -->

<?php

include "../../../include/footer.php";
