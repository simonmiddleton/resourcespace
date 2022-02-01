<?php

function HookGrant_editEditeditbeforeheader()
	{
	global $ref, $baseurl, $usergroup, $grant_edit_groups, $collection;
	
	// Do we have access to do any of this, or is it a template
	if(!in_array($usergroup, $grant_edit_groups) || $ref<0){return;}
		
	// Check for Ajax POST to delete users
	$grant_edit_action=getvalescaped("grant_edit_action","");
	if($grant_edit_action!="")
		{
		if($grant_edit_action=="delete")
			{
			$remove_user=escape_check(getvalescaped("remove_user","",TRUE));
			if($remove_user!="")
				{
				ps_query("delete from grant_edit where resource = ? and user = ?", array("i",$ref,"i",$remove_user));
				exit ("SUCCESS");
				}
			}
		exit("FAILED");
		}
	
	# If 'users' is specified (i.e. access is private) then rebuild users list
	
	$users=getvalescaped("users",false);
	if ($users!=false)
		{
		
		# Build a new list and insert
		$users=resolve_userlist_groups($users);
		$ulist=array_unique(trim_array(explode(",",$users)));
        $ulist = array_map("escape_check",$ulist);
		$urefs = ps_array("select ref value from user where username in (" . ps_param_insert(count($ulist)) . ")", ps_param_fill($ulist,"s"));
		
		if (count($urefs)>0)
			{
			$grant_edit_expiry=getvalescaped("grant_edit_expiry","");
			if ((int)$collection > 0)
				{
				global $items;							
				foreach ($items as $collection_resource)
					{
					$parameters = array();
					$insertvalue = array();
					foreach ($urefs as $uref)
						{
						$insertvalue[] = "(? ,? ,?)";
						$expiry = ($grant_edit_expiry == "") ? NULL : $grant_edit_expiry;
						$parameters = array_merge($parameters, array("i",$collection_resource,"i",$uref,"s",$expiry));
						}
					ps_query("delete from grant_edit where resource = ? and user in (" . ps_param_insert(count($urefs)) . ")", array_merge(array("i",$collection_resource), ps_param_fill($urefs,"i")));
					ps_query("insert into grant_edit(resource,user,expiry) values " . implode(",", $insertvalue), $parameters);
					#log this
					global $lang;
					resource_log($collection_resource,'s',"","Grant Edit -  " . $users . " - " . $lang['expires'] . ": " . (($grant_edit_expiry!="")?nicedate($grant_edit_expiry):$lang['never']));
					}
				}
			else
				{
				$parameters = array();
				foreach ($urefs as $uref)
					{
					$insertvalue[] = "(? ,? ,?)";
					$expiry = ($grant_edit_expiry == "") ? NULL : $grant_edit_expiry;
					$parameters = array_merge($parameters, array("i",$ref,"i",$uref,"s",$expiry));
					}
				ps_query("delete from grant_edit where resource = ? and user in (" . ps_param_insert(count($urefs)) . ")", array_merge(array("i",$ref), ps_param_fill($urefs,"i")));
				ps_query("insert into grant_edit(resource,user,expiry) values " . implode(",", $insertvalue), $parameters);
				#log this
				global $lang;
				resource_log($ref,'s',"","Grant Edit -  " . $users . " - " . $lang['expires'] . ": " . (($grant_edit_expiry!="")?nicedate($grant_edit_expiry):$lang['never']));	
				}					
			}
		}
	
	
	
	return true;
	}

function HookGrant_editEditEditstatushide()
	{
	// Needed to prevent user changing the archive state, otherwise a user with temporary edit access to an active resource could change it from active to pending submission
	global $status, $resource;
	if(!checkperm("e" . $resource["archive"]))
		{return true;}
	return false;
	}
	


function HookGrant_editEditAppendcustomfields()
	{
	global $ref,$lang,$baseurl,$grant_editusers, $multiple, $usergroup, $grant_edit_groups, $collapsible_sections;
	
	// Do we have access to see this?
	if(!in_array($usergroup, $grant_edit_groups) || $ref<0){return;}
	
	$grant_editusers=ps_query("select ea.user, u.fullname, u.username, ea.expiry from grant_edit ea left join user u on u.ref = ea.user where ea.resource = ? and (ea.expiry is NULL or ea.expiry >= NOW()) order by expiry, u.username", array("i",$ref));
	//print_r($grant_editusers);
	//exit();
	?>
    <h2 id="resource_custom_access" <?php echo ($collapsible_sections) ? ' class="CollapsibleSectionHead"' : ''; ?>><?php echo $lang["grant_edit_title"]?></h2>
    <?php
   
	if ($multiple)
        { ?>
        <div class="Question" id="editmultiple_grant_edit">
            <input name="editthis_grant_edit" id="editthis_grant_edit" value="yes" type="checkbox" onClick="var q=document.getElementById('grant_edit_fields');if (q.style.display!='block') {q.style.display='block';} else {q.style.display='none';}">
            <label id="editthis_grant_edit_label" for="editthisenhancedaccess>"><?php echo $lang["grant_edit_title"]?></label>
        </div><?php
        }
	
	if(count($grant_editusers)>0 && !$multiple)
		{
		?>	
		
		<div class="Question" id="question_grant_edit" <?php if ($multiple) {?>style="display:none;"<?php } ?>>
			<label><?php echo $lang["grant_edit_list"]?></label>
			<table cellpadding=3 cellspacing=3 class="ListviewStyle">
			<tr class="ListviewTitleStyle">
			<td><?php echo $lang['user'];?></td>
			<td><?php echo $lang['expires'];?></td>
			</tr>
			<?php
			foreach($grant_editusers as $grant_edituser)
				{
				//print_r($grant_edituser);
				echo "<tr id='grant_edit" . $grant_edituser['user'] . "'>
						<td>" . (($grant_edituser['fullname']!="")?$grant_edituser['fullname']:$grant_edituser['username']) . "</td>
						<td>" . (($grant_edituser['expiry']!="")?nicedate($grant_edituser['expiry']):$lang['never'])  . "</td>
						<td><a href='#' onclick='if (confirm(\"" . $lang['grant_edit_delete_user'] . " " . (($grant_edituser['fullname']!="")?$grant_edituser['fullname']:$grant_edituser['username']) . "\")){remove_grant_edit(" . $grant_edituser['user'] . ");}'>&gt;&nbsp;" . $lang['action-delete']  . "</a></td>
					  </tr>
					";
				}		
			?> 
			</table>
		</div>
		<script>
		function remove_grant_edit(user)
			{
			jQuery.ajax({
				async: true,
				url: '<?php echo $baseurl ?>/pages/edit.php',
				type: 'POST',
				data: {
                    ref:'<?php echo $ref ?>',
                    grant_edit_action:'delete',
                    remove_user:user,
                    <?php echo generateAjaxToken('remove_grant_edit'); ?>
                },
				timeout: 4000,
				success: function(result) {
					if(result='deleted')
						{
						jQuery('#grant_edit' + user).remove();
						}
					},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					response = "err--" + XMLHttpRequest.status + " -- " + XMLHttpRequest.statusText;
					},
			});
			}
		
		</script>
		<?php
		}
	
	$sharing_userlists=false;
	?>
	<div id="grant_edit_fields" <?php if ($multiple) {?>style="display:none;"<?php } ?>>
		<div class="Question" id="grant_edit_select" >
			<label for="users"><?php echo $lang["grant_edit_add"]?></label><?php include "../include/user_select.php"; ?>
			<div class="clearerleft"> </div>
		</div>
				
		<div class="Question">
			<label><?php echo $lang["grant_edit_date"]?></label>
			<select name="grant_edit_expiry" class="stdwidth">
			<option value=""><?php echo $lang["never"]?></option>
			<?php for ($n=1;$n<=150;$n++)
				{
				$date=time()+(60*60*24*$n);
				?><option <?php $d=date("D",$date);if (($d=="Sun") || ($d=="Sat")) { ?>style="background-color:#cccccc"<?php } ?> value="<?php echo date("Y-m-d",$date)?>" <?php if(substr(getvalescaped("editexpiration",""),0,10)==date("Y-m-d",$date)){echo "selected";}?>><?php echo nicedate(date("Y-m-d",$date),false,true)?></option>
				<?php
				}
			?>
			</select>
			<div class="clearerleft"> </div>
		</div>
	</div>
	
	<?php	
	return false;
	}
	
