<?php
include "../include/db.php";

$error=false;
$error_extra="";
$completed = false;

$user_email=getval("email","");
hook("preuserrequest");

if (getval("save","")!="")
    {
    # Check for required fields

    # Required fields (name, email) not set?
    $missingFields = hook('replacemainrequired');
    if (!is_array($missingFields))
        {
        $missingFields = array();
        if (getval("name","")=="") { $missingFields[] = $lang["yourname"]; }
        if (getval("email","")=="") { $missingFields[] = $lang["youremailaddress"]; }
        }

    # Add custom fields
    $customContents="";
    if (isset($custom_registration_fields))
        {
        $custom=explode(",",$custom_registration_fields);

        # Required fields?
        if (isset($custom_registration_required)) {$required=explode(",",$custom_registration_required);}

        # Loop through custom fields
        for ($n=0;$n<count($custom);$n++)
            {
            $custom_field_value = getval("custom" . $n,"");
            $custom_field_sub_value_list = "";

            for ($i=1; $i<=1000; $i++)		# check if there are sub values, i.e. custom<n>_<n> form fields, for example a bunch of checkboxes if custom type is set to "5"
                {
                $custom_field_sub_value = getval("custom" . $n . "_" . $i, "");
                if ($custom_field_sub_value == "") continue;
                $custom_field_sub_value_list .= ($custom_field_sub_value_list == "" ? "" : ", ") . $custom_field_sub_value;		# we have found a sub value so append to the list
                }

            if ($custom_field_sub_value_list != "")		# we found sub values
                {
                $customContents.=i18n_get_translated($custom[$n]) . ": " . i18n_get_translated($custom_field_sub_value_list) . "\n\n";		# append with list of all sub values found
                }
            elseif ($custom_field_value != "")		# if no sub values found then treat as normal field
                {
                $customContents.=i18n_get_translated($custom[$n]) . ": " . i18n_get_translated($custom_field_value) . "\n\n";		# there is a value so append it
                }
            elseif (isset($required) && in_array($custom[$n],$required))		# if the field was mandatory and a value or sub value(s) not set then we return false
                {
                $missingFields[] = $custom[$n];
                }
            }
        }

    $spamcode = getval("antispamcode","");
    $usercode = getval("antispam","");
    $spamtime = getval("antispamtime",0);

    if (!empty($missingFields))
        {
        $error=$lang["requiredfields"] . '<br><br> ' . i18n_get_translated(implode(', ', $missingFields));
        }
    # Check the anti-spam time is recent
    elseif(getval("antispamtime",0)<(time()-180) ||  getval("antispamtime",0)>time())
        {
        $error=$lang["expiredantispam"];    
        }
    # Check the anti-spam code is correct
    elseif (!hook('replaceantispam_check') && !verify_antispam($spamcode, $usercode, $spamtime))
        {
        $error=$lang["requiredantispam"];
        }
    # Check that the e-mail address doesn't already exist in the system
    elseif (user_email_exists($user_email) && $account_email_exists_note)
        {
        # E-mail already exists
        $error=$lang["accountemailalreadyexists"];$error_extra="<br/><a href=\"".$baseurl_short."pages/user_password.php?email=" . urlencode($user_email) . "\">" . $lang["forgottenpassword"] . "</a>";
        }
    else if(getval("login_opt_in", "") != "yes" && $user_registration_opt_in)
        {
        $error = $lang["error_user_registration_opt_in"];
        }
    else
        {
        # E-mail is unique

        if ($user_account_auto_creation)
            {	
            # Automatically create a new user account
            $success=auto_create_user_account(md5($usercode . $spamtime));
            if($success!==true && !$account_email_exists_note)
                {
                // send an email about the user request
                $success=email_user_request();
                }
            }
        else
            {
            $success=email_user_request();
            }
			
        if ($success!==true)
            {
            $error=$success;
            }
        else
            {
            $completed = true;  
            }
        }
	}
include "../include/header.php";

include "../include/login_background.php";
?>
<h1><?php echo $lang["requestuserlogin"]?></h1>
<p><?php echo text("introtext")?></p>

<form method="post" id='mainform' action="<?php echo $baseurl_short?>pages/user_request.php">  

<?php if ($error) { ?><div class="FormError"><?php echo $error . ' ' . $error_extra?></div><?php } ?>
<?php
if (!hook("replacemain"))
    { /* BEGIN hook Replacemain */ 
    
    $name = getvalescaped("name","");
    $name = is_array($name) ? "" : htmlspecialchars($name);
    
    $email = getvalescaped("email","");
    $email = is_array($email) ? "" : htmlspecialchars($email);
    
    ?>
    <div class="Question">
    <label for="name"><?php echo $lang["yourname"]?> <sup>*</sup></label>
    <input type=text name="name" id="name" class="stdwidth" value="<?php echo $name ?>">
    <div class="clearerleft"> </div>
    </div>

    <div class="Question">
    <label for="email"><?php echo $lang["youremailaddress"]?> <sup>*</sup></label>
    <input type=text name="email" id="email" class="stdwidth" value="<?php echo $email ?>">
    <div class="clearerleft"> </div>
    </div>
    <?php
    if($user_registration_opt_in)
        {
        ?>
        <div class="Question">
            <input type="checkbox" id="login_opt_in" name="login_opt_in" value="yes">
            <label for="login_opt_in" style="margin-top:0;"><?php echo htmlspecialchars($lang['user_registration_opt_in_message']); ?></label>
            <div class="clearer"></div>
        </div>
        <?php
        }
    } /* END hook Replacemain */ ?>

<?php # Add custom fields 
if (isset($custom_registration_fields))
	{
	$custom=explode(",",$custom_registration_fields);
	
	if (isset($custom_registration_required))
	{
		$required=explode(",",$custom_registration_required);
		}
	
	for ($n=0;$n<count($custom);$n++)
		{
		$type=1;
		
		# Support different question types for the custom fields.
		if (isset($custom_registration_types[$custom[$n]])) {$type=$custom_registration_types[$custom[$n]];}
		
		if ($type==4)
			{
			# HTML type - just output the HTML.
			$html = $custom_registration_html[$custom[$n]];
			if (is_string($html))
				echo $html;
			else if (isset($html[$language]))
				echo $html[$language];
			else if (isset($html[$defaultlanguage]))
				echo $html[$defaultlanguage];
			}
		else
			{
			?>
			<div class="Question" id="Question<?php echo $n?>">
			<label for="custom<?php echo $n?>"><?php echo htmlspecialchars(i18n_get_translated($custom[$n]))?>
			<?php if (isset($required))
			{
				if (in_array($custom[$n],$required)) { ?><sup>*</sup><?php }
				}
				 ?>
			</label>
			
			<?php if ($type==1) {  # Normal text box
			?>
			<input type=text name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth" value="<?php echo htmlspecialchars(getvalescaped("custom" . $n,""))?>">
			<?php } ?>

			<?php if ($type==2) { # Large text box 
			?>
			<textarea name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth" rows="5"><?php echo htmlspecialchars(getvalescaped("custom" . $n,""))?></textarea>
			<?php } ?>

			<?php if ($type==3) { # Drop down box
			?>
			<select name="custom<?php echo $n?>" id="custom<?php echo $n?>" class="stdwidth">
			<?php foreach ($custom_registration_options[$custom[$n]] as $option)
				{
				?>
				<option><?php echo htmlspecialchars(i18n_get_translated($option));?></option>
				<?php
				}
			?>
			</select>
			<?php } ?>
			
			<?php if ($type==5) { # checkbox
				?>
				<div class="stdwidth">			
					<table>
						<tbody>
						<?php								
						$i=0;
						foreach ($custom_registration_options[$custom[$n]] as $option)		# display each checkbox
							{
							$i++;
							$option_exploded = explode (":",$option);
							if (count($option_exploded) == 2)		# there are two fields, the first indicates if checked by default, the second is the name
								{
								$option_checked = ($option_exploded[0] == "1");
								$option_label = htmlspecialchars(i18n_get_translated(trim($option_exploded[1])));
								}
							else		# there are not two fields so treat the whole string as the name and set to unchecked
								{
								$option_checked = false;
								$option_label = htmlspecialchars(i18n_get_translated(trim($option)));
								}
							$option_field_name = "custom" . $n . "_" . $i;		# same format as all custom fields, but with a _<n> indicating sub field number
							?>
							<tr>
								<td>
									<input name="<?php echo $option_field_name; ?>" id="<?php echo $option_field_name; ?>" type="checkbox" <?php if ($option_checked) { ?> checked="checked"<?php } ?> value="<?php echo $option_label; ?>"></input>
								</td>
								<td>
									<label for="<?php echo $option_field_name; ?>" class="InnerLabel"><?php echo $option_label;?></label>												
								</td>
							</tr>
							<?php					
							}			
						?>				
						</tbody>
					</table>
				</div>			
			<?php } ?>
			
			<div class="clearerleft"> </div>
			</div>
			<?php
			}
		}
	}
?>

<?php if (!hook("replacegroupselect")) { /* BEGIN hook Replacegroupselect */ ?>
<?php if ($registration_group_select) {
# Allow users to select their own group
$groups=get_registration_selectable_usergroups();
?>
<div class="Question">
<label for="usergroup"><?php echo $lang["group"]?></label>
<select name="usergroup" id="usergroup" class="stdwidth">
<?php for ($n=0;$n<count($groups);$n++)
	{
	?>
	<option value="<?php echo $groups[$n]["ref"] ?>"><?php echo htmlspecialchars($groups[$n]["name"]) ?></option>
	<?php
	}
?>
</select>
<div class="clearerleft"> </div>
</div>	
<?php } ?>
<?php } /* END hook Replacegroupselect */ ?>

<?php if (!hook("replaceuserrequestcomment")){ 
    $userrequestcomment = getvalescaped("userrequestcomment","");
    $userrequestcomment = is_array($userrequestcomment) ? "" : htmlspecialchars($userrequestcomment);
    
    
    ?>
<div class="Question">
<label for="userrequestcomment"><?php echo $lang["userrequestcomment"]?></label>
<textarea name="userrequestcomment" id="userrequestcomment" class="stdwidth"><?php echo $userrequestcomment ?></textarea>
<div class="clearerleft"> </div>
</div>	
<?php } /* END hook replaceuserrequestcomment */

hook("userrequestadditional");

if(!hook("replaceantispam"))
	{
    render_antispam_question();
	}
?>
<script>
<?php
if($completed)
    {
    echo "jQuery(document).ready(function(){
            ModalLoad('" . $baseurl . "/pages/done.php?text=user_request',false,true);
        });
        ";
    }?>

function submitForm() 
    {
    document.getElementById("user_submit").disabled = true;
    CentralSpacePost(document.getElementById('mainform'),true,false,false,'CentralSpaceLogin');
    }
</script>

<div class="QuestionSubmit">
<label for="buttons"> </label>			
<input name='save' value='yes' type='hidden'>
<input name="user_save" id="user_submit" onclick="submitForm()" type="button" value="&nbsp;&nbsp;<?php echo $lang["requestuserlogin"]?>&nbsp;&nbsp;" />
</div>
</form>

<?php
if(!hook("replace_user_request_required_key"))
	{
	?>
	<p><sup>*</sup> <?php echo $lang["requiredfield"] ?></p>
	<?php
	}
?>
<div> <!-- end of login_box -->
<?php
include "../include/footer.php";