<?php
include "../../include/db.php";
include "../../include/authenticate.php";

$recipient  = getval("recipient",0,true);
$msglimit   = getval("showcount",3,true);
$text       = getval("messagetext","");
if($recipient != 0)
    {
    $messages   = message_get_conversation($userref, array($recipient), array("limit"=>$msglimit,"sort_desc"=>true));
    $to_user = get_user($recipient);
    if(!$to_user)
        {
        error_alert($lang["error_invalid_user"],true,200);
        exit();
        }
    $to_username = isset($to_user["fullname"]) ? $to_user["fullname"] : $to_user["username"];
    $to_username = htmlspecialchars($to_username);
    }
else
    {
    $messages   = array();
    }

    //print_r($messages);

include "../../include/header.php";

// The 'recipient' variable below is  used by message polling to detect and intercept messages from this user to show on this page
?>
<script>
recipient = <?php echo $recipient; ?>;
function sendMessage()
    {        
    messagetext = jQuery('#messagetext').val();
    users = jQuery('#users').val().split();
    postdata = {
        'users': users,
        'text': messagetext,
        }
    //console.log(postdata);
    api("send_user_message",postdata,showUserMessage(messagetext,true));
    }

function showUserMessage(message,fromself)
    {
    // show new message in line
    msgtemp = document.getElementById("user_message_template").outerHTML;
    
    if(fromself)
        {
        classtxt = "user_message own_message";
        imagehtml = "<img title='<?php echo htmlspecialchars($userfullname); ?>' alt='<?php echo htmlspecialchars($userfullname); ?>' class='ProfileImage' src='<?php echo htmlspecialchars(get_profile_image($userref)); ?>'>";
        jQuery('#messagetext').val('');
        }
    else
        {
        classtxt = "user_message";
        imagehtml = "<img title='<?php echo $to_username; ?>' alt='<?php echo $to_username; ?>' class='ProfileImage' src='<?php echo htmlspecialchars(get_profile_image($recipient)); ?>'>";
        }

    msgtemp = msgtemp.replace("%%PROFILEIMAGE%%", imagehtml);
    console.log(msgtemp);

    msgtemp = msgtemp.replace("%%CLASSES%%", classtxt);
    msgtemp = msgtemp.replace("%%MESSAGE%%", message);
    console.log(msgtemp);

    jQuery('#message_conversation').append(msgtemp);
    
    jQuery('.user_message').show();
    jQuery('.user_message').first().slideUp().remove();

    }

</script>
<?php

$links_trail = array(
    array(
        'title' => $lang["mymessages"],
        'href'  => $baseurl_short . "pages/user/user_messages.php",
        )
    );
if($recipient != 0)
    {
    $links_trail_user = array("title" => $to_username,"href" => $baseurl_short . "pages/user/user_profile.php?username=" . htmlspecialchars($to_user["username"]));
    $links_trail[] = $links_trail_user;
    }

$links_trail[] = array(
    'title' => $lang["new_message"],
    'help'  => "resourceadmin/user-communication",
    )
 
?>
<div class="BasicsBox">
<?php
renderBreadcrumbs($links_trail);

?>
<form id="myform" method="post" class = "FormWide" action="<?php echo $baseurl_short?>pages/user/user_message.php">
    <?php
    generateFormToken("myform");

    if (isset($error)) { ?><div class="FormError"><?php echo $error?></div><?php } ?>

    <?php 

    if($recipient == 0)
        {
        //render_user_select_question($lang["message_recipients"],"users","","","",array("input_class"=>array("stdwidth")));
        echo "<div class='Question'><label>" . $lang["message_recipients"] . "</label>";
        $user_select_internal = true;
        include "../../include/user_select.php";
        echo "<div class='clearerleft'> </div></div>";
        }
    else
        {
        echo "<input type='hidden' id='users' name='users' value='" . $recipient . "'/>";
        }
    

    // Show conversation
    echo "<div id='message_conversation' class='message_conversation'>";
    // Render in reverse order
    for($n=count($messages)-1;$n>=0;$n--)
        {
        render_message($messages[$n]);    
        }
    // Add hidden template for new messages
    render_message();
    echo "<div class='clearer'> </div>";
    echo "</div>";
    ?>


    <div class="Question"><label><?php echo $lang["message"]?></label>
        <textarea id="messagetext" name="messagetext" class="stdwidth Inline required" rows=5 cols=50></textarea>
        <div class="clearerleft"> </div></div>
    <div class="QuestionSubmit">
    <label for="buttons"> </label>			
    <input name="send" type="submit" value="&nbsp;&nbsp;<?php echo $lang["send"]?>&nbsp;&nbsp;" onclick="sendMessage();return false;"/>
    </div>
    </form>
</div>

<?php		
include "../../include/footer.php";
?>
