<?php
include "../include/boot.php";

if (!$allow_password_reset) {exit("Password requests have been disabled.");} # User should never see this.

if (getval("save","")!="" && enforcePostRequest(false))
    {
    if (email_reset_link(getval("email","")) || $hide_failed_reset_text)
        {
        redirect("pages/done.php?text=user_password_link_sent");
        }
    else
        {
        $error=true;
        }
    }
include "../include/header.php";

include "../include/login_background.php";
?>

    <h1><?php echo escape($lang["requestnewpassword"]); ?></h1>
    <p><?php echo text("introtextreset")?></p>
    
      
    <form method="post" action="<?php echo $baseurl_short?>pages/user_password.php">  
    <?php generateFormToken("user_password"); ?>
    <div class="Question">
    <label for="email"><?php echo escape($lang["youremailaddress"]); ?></label>
    <input type=text name="email" id="email" class="stdwidth" value="<?php echo escape(getval("email",""))?>">
    <?php if (isset($error) && !$hide_failed_reset_text) { ?><div class="FormError">!! <?php echo escape($lang["emailnotfound"]); ?> !!</div><?php hook("userpasswdextramsg"); ?><?php } ?>
    <div class="clearerleft"> </div>
    </div>
    
    <div class="QuestionSubmit">    
    <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo escape($lang["sendnewpassword"]); ?>&nbsp;&nbsp;" />
    </div>
    </form>
    
    <div> <!-- end of login_box -->
    <?php
include "../include/footer.php";
?>
