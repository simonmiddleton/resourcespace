<?php
include "../../include/db.php";
include "../../include/authenticate.php";

$msgto  = getval("msgto",0,true);
$msglimit   = getval("showcount",3,true);
$text       = getval("messagetext","");
if($msgto != 0)
    {
    $messages   = message_get_conversation($userref, array($msgto), array("limit"=>$msglimit,"sort_desc"=>true));
    $to_user = get_user($msgto);
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
    $to_username= "";
    }

    //print_r($messages);

include "../../include/header.php";

// The 'msgto' variable below is  used by message polling to detect and intercept messages from this user to show on this page
?>
<script>
msgto = <?php echo $msgto; ?>;
userimghtml =  jQuery("<img />", {
        title   : '<?php echo htmlspecialchars($userfullname); ?>',
        alt     : '<?php echo htmlspecialchars($userfullname); ?>',
        'class' : 'ProfileImage',
        src     : '<?php echo htmlspecialchars(get_profile_image($userref)); ?>'
        });

usertoimghtml =  jQuery("<img />", {
        id      : 'msgtoimage',
        title   : '<?php echo htmlspecialchars($to_username); ?>',
        alt     : '<?php echo htmlspecialchars($to_username); ?>',
        'class' : 'ProfileImage',
        src     : '<?php echo htmlspecialchars(get_profile_image($msgto)); ?>'
        });

function sendMessage()
    {
    messagetext = jQuery('#messagetext').val();
    users = document.getElementById('users').value.replace(/"/g, "").split(",");
    postdata = {
        'users': users,
        'text': messagetext,
        }
console.log(users);
    if(users.length == 1 && msgto == 0)
        {
        // Get details of selected recipient to show conversation
        touser = api("get_users",{'find':  users[0],'exact_username_match': true},function(response)
            {
            console.log(response);
            if(response.length == 1)
                {
                msgto = parseInt(response[0]['ref']);
                tofullname = response[0]['fullname'];
                if(response[0]['fullname'] != '')
                    {
                    tofullname = response[0]['username'];
                    }

                    // usertoimghtml
                // jQuery('#msgtoimage').attr('title',tofullname);
                // jQuery('#msgtoimage').attr('alt',tofullname);
                jQuery(usertoimghtml).attr('title',tofullname);
                jQuery(usertoimghtml).attr('alt',tofullname);
                touserid = response[0]['ref'];
                toimage = api("get_profile_image",{'user': touserid},function(imgresponse)
                    {
                    console.log(imgresponse);
                    if(imgresponse != "")
                        {
                        jQuery(usertoimghtml).attr("src",imgresponse);
                        }
                    });
                }
            });
        }
    api("send_user_message",postdata,showUserMessage(messagetext,true));
    // Speed up message checking whilst on this page
    message_timer = window.setTimeout(message_poll,5);
    }

function showUserMessage(message,fromself)
    {
    // show new message in line
    //msgtemp = document.getElementById("user_message_template").outerHTML;
    msgtemp = document.getElementById("user_message_template").innerHTML;

    console.log(usertoimghtml);
    
    if(fromself)
        {
        classtxt = "user_message own_message";
        msgtemp = msgtemp.replace("%%PROFILEIMAGE%%", userimghtml[0].outerHTML);
        //jQuery(msgtemp + ' >.profileimage').html(userimghtml[0]);

        jQuery('#messagetext').val('');
        }
    else
        {
        classtxt = "user_message";
        msgtemp = msgtemp.replace("%%PROFILEIMAGE%%", usertoimghtml[0].outerHTML);
        //jQuery(msgtemp + ' >.profileimage').html(usertoimghtml[0]);
        }

    console.log(msgtemp);
    
    msgtemp = msgtemp.replace("%%CLASSES%%", classtxt);
    msgtemp = msgtemp.replace("%%MESSAGE%%", message);
    console.log(msgtemp);

    jQuery('#message_conversation').append(msgtemp);
    
    //jQuery('.user_message').show();
    if(jQuery('.user_message').length > 3)
        {
        jQuery('.user_message').first().slideUp().remove();
        }
    }

</script>
<?php

$links_trail = array(
    array(
        'title' => $lang["mymessages"],
        'href'  => $baseurl_short . "pages/user/user_messages.php",
        )
    );
if($msgto != 0)
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

    if($msgto == 0)
        {
        render_user_select_question($lang["message_recipients"],"users","","","",array("input_class"=>array("stdwidth")));



        // echo "<div class='Question'><label>" . $lang["message_recipients"] . "</label>";
        // $user_select_internal = true;
        // include "../../include/user_select.php";
        // echo "<div class='clearerleft'> </div></div>";
        }
    else
        {
        echo "<input type='hidden' id='users' name='users' value='" . $msgto . "'/>";
        }
    

    // Show conversation
    echo "<div id='message_conversation' class='message_conversation'>";
    // Render in reverse order
    for($n=count($messages)-1;$n>=0;$n--)
        {
        render_message($messages[$n]);    
        }
    // Add template for new messages
    echo "\n<template id='user_message_template'>";
    render_message();
    echo "</template>";
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
