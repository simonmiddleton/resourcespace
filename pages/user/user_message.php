<?php
include "../../include/db.php";
include "../../include/authenticate.php";

$recipient  = getval("recipient",0,true);
$users      = getval("users","");
$msglimit   = getval("showcount",3,true);
$text       = getval("messagetext","");
//exit(print_r($messages));
if($users != "")
    {
    $ulist=trim_array(explode(",",$users));
    foreach($ulist as $userto)
        {
        $msgto[] = get_user_by_username($userto);
        }
    array_filter($msgto,"is_int_loose");
    $messages   = message_get_conversation($userref, $msgto, array("limit"=>$msglimit));
    }
else
    {
    $messages   = array();
    }

if (getval("send","") != "" && trim($users) != "" && trim($text) != "" && enforcePostRequest(false))
    {
    $msgto = array();
    $result=message_add($msgto,$text,"",$userref,MESSAGE_TYPE_USER_MESSAGE,30*24*60*60);
    
    if ($result=="")
        {
        $error=$lang["message_sent"];
        }
    else
        {
        $error = $result;
        }
    }
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

foreach($messages as $message)
    {
    render_message($message);    
    }
?>


<form id="myform" method="post" action="<?php echo $baseurl_short?>pages/user/user_message.php">
    <?php
    generateFormToken("myform");

    if (isset($error)) { ?><div class="FormError"><?php echo $error?></div><?php } ?>

    <?php 

    if($recipient == 0)
        {
        render_user_select_question($lang["message_recipients"],"users","","","",array("input_class"=>array("stdwidth")));
        $user_select_internal = true;
        include "../../include/user_select.php";
        }
    else
        {
        $to_user = get_user($recipient);
        if(!$to_user)
            {
            error_alert($lang["error_invalid_user"],true,200);
            exit();
            }
        else
            {
            echo "<div class='Question'><label>" . $lang["message_recipients"] . "</label>";
            $to_username = isset($to_user["fullname"]) ? $to_user["fullname"] : $to_user["username"]; 
            echo "<div class='Fixed'>" . htmlspecialchars($to_username)  . "</div>";
            echo "<div class='clearerleft'> </div></div>";
 
            }
        }
    ?>
    <div class="Question"><label><?php echo $lang["text"]?></label>
        <textarea id="messagetext" name="messagetext" class="stdwidth Inline required" rows=15 cols=50>
        <?php echo htmlspecialchars(getval("text",""))?>
        </textarea>
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
    users = jQuery('#users').val();
    postdata = {
            'users': users,
            'text': messagetext,
        }
        console.log(postdata);
    api("message_add",postdata,"showMessage");
    }

function showMessage(message)
    {
    // show new message in line
    alert("Show message");
    }


</script>

<?php		
include "../../include/footer.php";
?>
