<?php

include "../../include/boot.php";
include "../../include/authenticate.php";
include "../../include/header.php";

if (isset($anonymous_login) && $anonymous_login == $username) {
    die($lang["error-permissions-login"]);
}

$messages = array();

message_get($messages, $userref, true, "DESC", "created", 10);


?>
<div id="messages-list" class="messages-modal">
    <h1><a href="<?php echo generateURL($baseurl . '/pages/user/user_messages.php'); ?>" onClick="return CentralSpaceLoad(this, true);"><?php echo escape($lang["mymessages"])?></a></h1>
    <div class="message-list">
    <?php if (count($messages) > 0): ?>
        <?php foreach ($messages as $message): ?>
        <?php

            $unread_css = ($message["seen"] == 0 ? " unread" : "");            

            $userbyname = get_user_by_username($message["owner"]);
            $user = get_user($userbyname);
            if (!$user) {
                $user = array('fullname' => $applicationname,'groupname' => '');
                $user_profile_image = "";
            } else {
                $user_profile_image = get_profile_image($user['ref'], false);
            }            

            $message_text = $message["message"];
                // Full message is retrieved via api to avoid long messages killing the page
                if (mb_strlen($message_text) > 100) {
                    $message_text = mb_strcut($message["message"], 0, 70) . "...";
                }
            $message_text = strip_tags_and_attributes($message_text);
            $message_text = nl2br($message_text);

            $reply = (bool)($message["type"] & MESSAGE_ENUM_NOTIFICATION_TYPE_USER_MESSAGE);
        ?>
        <div id="message<?php echo (int)$message['message_id']; ?>" class="message<?php echo $unread_css; ?>"  onclick="show_quick_message(<?php echo (int)$message['message_id'] . ', ' . (int)$message['ref'] . ', '. (int)$reply; ?>)">
            <div class="message-icon">
                <?php if ($user_profile_image != "") { ?>
                    <img src='<?php echo $user_profile_image; ?>' alt='Profile icon' class="ProfileImage" id='UserProfileImage'>
                <?php } else { ?>
                    <span class="fa fa-solid fa-gears"></span>
                <?php } ?>
            </div>
            <div class="message-container">
                <div class="message-username">
                    <?php echo escape((isset($user['fullname']) && trim($user['fullname']) != "") ? $user['fullname'] : $user['username']); ?>
                </div>
                <div class="message-age"><?php echo escape(date_to_age($message["created"])); ?></div>
                <div class="message-text"><?php echo($message_text); ?></div>
            </div>
        </div>
        <div class="message-divider"></div>
        <?php endforeach; ?>
        <div class="message-viewall">
            <a id="viewall-link" href="<?php echo generateURL($baseurl . '/pages/user/user_messages.php'); ?>" onClick="return CentralSpaceLoad(this, true);">
                <i class="fa-solid fa-envelope"></i> <?php echo escape($lang["viewallmessages"])?>
            </a>
        </div>
        <?php else: ?>
        <div class="message-nomessages">
            <?php echo escape($lang['mymessages_youhavenomessages']); ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<div id="message-detail" class="messages-modal" style="display: none;">
    <h2><a href="#" onclick="show_list();"><i class="fa-solid fa-caret-left"></i>&nbsp;<?php echo escape($lang["backtomessages"])?></a></h2>
    <div class="message-body">
        <div class="message-full-text"></div>
        <div class="message-reply">
            <a id="reply-link" href="">
                <i class="fa-solid fa-reply"></i> <?php echo escape($lang["reply"]); ?>
            </a>
        </div>
    </div>
</div>
<script>
    function show_quick_message(message_ref, ref, reply) {

        // Show full message in modal
        api("get_user_message",{'ref': message_ref}, function(response)
            {
            console.debug(response);
            if(response.length != false) {
                msgtext   = response['message'];
                msgurl    = response['url'];
                msgowner  = response['owner'];

                if (typeof msgurl === "undefined") {
                    msgurl = "";
                }

                if (msgurl != "") {
                    msgurl = decodeURIComponent(msgurl);
                    msgurl = DOMPurify.sanitize(msgurl);
                    msgurl = "<br /><br /><a class='message_link' href='" + msgurl + "'><?php echo escape($lang['link']); ?></a>";
                }

                jQuery(".message-full-text").html(nl2br(DOMPurify.sanitize(msgtext)) + msgurl);

                if (reply === 1) {
                    jQuery("#reply-link").attr('href','<?php echo $baseurl_short . "pages/user/user_message.php?msgto="; ?>' + msgowner);
                    jQuery(".message-reply").show();
                } else {
                    jQuery("#reply-link").attr('href','');
                    jQuery(".message-reply").hide();
                }

                jQuery('#messages-list').fadeOut(200, function() {
                    jQuery('#message-detail').fadeIn(200, function() {
                        jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?ajax=true&seen=' + ref);
                        jQuery('#message' + message_ref).removeClass("unread");                        
                    });
                });

            }
            },
            <?php echo generate_csrf_js_object('get_user_message'); ?>
        );
    }

    function show_list() {
        jQuery('#message-detail').fadeOut(200, function() {
            jQuery('#messages-list').fadeIn(200, function() {
                jQuery(".message-full-text").html("");
            });
        });
        
    }
 
</script>

<?php

include "../../include/footer.php";
