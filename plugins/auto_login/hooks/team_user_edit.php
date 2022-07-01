<?php

// Add 'Enable auto login' check box
function HookAuto_loginTeam_user_editAdditionaluserfields()
	{
	global $lang, $user;
	?>
	<div class="Question"><label><?php echo $lang["auto_login_enabled"]?></label>
		<input name="auto_login_enabled" type="checkbox" value="1" <?php
			if ($user['auto_login_enabled']==1) echo 'checked'; ?> />
		<div class="clearerleft"> </div></div>
	<div class="Question"><label><?php echo $lang["auto_login_ip"]?><br/>
		<?php echo $lang["wildcardpermittedeg"]?> 192.168.*</label>
		<input name="auto_login_ip" type="text" class="stdwidth" value="<?php
			echo $user["auto_login_ip"]?>">
		<div class="clearerleft"> </div></div>
	<?php
	}

// Save auto login setting
function HookAuto_loginTeam_user_editAftersaveuser()
    {
    global $ref, $error;

    $error  = '';

    $auto_login_enabled = getval('auto_login_enabled', 0);
    $auto_login_ip      = getval('auto_login_ip', '');

    // No reason to continue if we don't have an IP
    // Change save value to nothing so we remain on the same page and show an error to the user
    if(1 == $auto_login_enabled && '' == $auto_login_ip)
        {
        $error = 'IP address for automatic login is blank';
        // Change save value to nothing so we remain on the same page and show an error to the user
        $_POST['save'] = '';
        $_GET['save'] = '';
        $_COOKIE['save'] = '';
        $_REQUEST['save'] = '';

        return false;
        }

	// All OK, save the record.
    ps_query("UPDATE user SET auto_login_enabled = ?, auto_login_ip = ? WHERE ref = ?", ['i', $auto_login_enabled, 's', $auto_login_ip, 'i', $ref]);
	return true;
    }