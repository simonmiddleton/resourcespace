<?php
include "../../include/db.php";
include "../../include/authenticate.php";

//$recipient  = getval("recipient",0,true);
$users      = getval("users","");
$msglimit   = getval("showcount",3,true);
$text       = getval("messagetext","");
if($users != "")
    {
    $ulist=trim_array(explode(",",$users));
    foreach($ulist as $userto)
        {
        if(is_int_loose($users[$n]))
            {
            $msgto[] =  $userto;
            }
        else
            {  
            $uref = get_user_by_username($userto);
            if (!$uref)
                {
                $msgto[] = $uref;
                }
            }
        }
    array_filter($msgto,"is_int_loose");
    $messages   = message_get_conversation($userref, $msgto, array("limit"=>$msglimit));
    }
else
    {
    $messages   = array();
    }

    //print_r($messages);

include "../../include/header.php";

$links_trail = array(
    array(
        'title' => $lang["mymessages"],
        'href'  => $baseurl_short . "pages/user/user_messages.php",
        ),
    array(
        'title' => $lang["new_message"],
        'help'  => "resourceadmin/user-communication",
        )
    );
 
?>
<div class="BasicsBox">
<?php
renderBreadcrumbs($links_trail);

echo "<div id='message_conversation' class='message_conversation'>";
foreach($messages as $message)
    {
    render_message($message);    
    }

// Create template for new messages
render_message();

echo "<div class='clearer'> </div>";
echo "</div>";
?>


<form id="myform" method="post" class = "FormWide" action="<?php echo $baseurl_short?>pages/user/user_message.php">
    <?php
    generateFormToken("myform");

    if (isset($error)) { ?><div class="FormError"><?php echo $error?></div><?php } ?>

    <?php 

    echo "<div class='Question'><label>" . $lang["message_recipients"] . "</label>";
    if(count($msgto) != 1)
        {
        //render_user_select_question($lang["message_recipients"],"users","","","",array("input_class"=>array("stdwidth")));
        $user_select_internal = true;
        include "../../include/user_select.php";
        }
    else
        {
        $to_user = get_user($msgto[0]);
        if(!$to_user)
            {
            error_alert($lang["error_invalid_user"],true,200);
            exit();
            }
        else
            {
            $to_username = isset($to_user["fullname"]) ? $to_user["fullname"] : $to_user["username"]; 
            echo "<div class='Fixed'>" . htmlspecialchars($to_username)  . "</div>";  
            echo "<input type='hidden' id='users' name='users' value='" . htmlspecialchars($to_user["ref"])  . "'/>"; 
            }
        }
    
        echo "<div class='clearerleft'> </div></div>";
    ?>
    <div class="Question"><label><?php echo $lang["message"]?></label>
        <textarea id="messagetext" name="messagetext" class="stdwidth Inline required" rows=15 cols=50></textarea>
        <div class="clearerleft"> </div></div>
    <div class="QuestionSubmit">
    <label for="buttons"> </label>			
    <input name="send" type="submit" value="&nbsp;&nbsp;<?php echo $lang["send"]?>&nbsp;&nbsp;" onclick="sendMessage();return false;"/>
    </div>
    </form>
</div>
<script>
function sendMessage()
    {
        //$users,$text,$url="",$owner=null,$ttl_seconds=MESSAGE_DEFAULT_TTL_SECONDS, $related_activity=0, $related_ref=0)
    messagetext = jQuery('#messagetext').val();
    users = jQuery('#users').val().split();
    postdata = {
            'users': users,
            'text': messagetext,
        }
        console.log(postdata);
    api("send_user_message",postdata,showMessage(messagetext,'<?php echo $userref ?>'));
    }

function showMessage(message, user)
    {
    // show new message in line
    jQuery('#messagetext').val('');
    alert("Show message");
    msgtemp = jQuery('#user_message_template');
    msgtemp = msgtemp.replace("%%PROFILEIMAGE%%", node.drop ? "BrowseBarDroppable" : "");
    brwstmplt = brwstmplt.replace("%BROWSE_NAME%",node.name);
                                  
                                  
    brwstmplt = brwstmplt.replace("%BROWSE_LEVEL%",newlevel);
    brwstmplt = brwstmplt.replace("%BROWSE_INDENT%",rowindent);    
    
    brwstmplt = brwstmplt.replace("%BROWSE_EXPAND%",expand);
    jQuery('#message_conversation').append(msgtemp);


    }


</script>

<?php		
include "../../include/footer.php";
?>
