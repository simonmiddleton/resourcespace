<?php

include "../../include/db.php";
include "../../include/authenticate.php";

if (isset($anonymous_login) && $anonymous_login == $username)
    {
    die($lang["error-permissions-login"]);
    }

$offset=getval("offset",0,true);
$msg_order_by = getval("msg_order_by",getval("saved_msg_order_by", "created"));rs_setcookie('saved_msg_order_by', $msg_order_by);
$sort = getval("sort",getval("saved_msg_sort", "DESC"));rs_setcookie('saved_msg_sort', $sort);
$revsort = ($sort=="ASC") ? "DESC" : "ASC";
$per_page = getval("per_page_list", $default_perpage_list, true);rs_setcookie('per_page_list', $per_page);

global $user_preferences;

if (getval("allseen","")!="")
    {
    // Acknowledgement all messages
    message_seen_all($userref);
    }

include "../../include/header.php";
?>
<div class="BasicsBox">
    <div class="VerticalNav">
        <ul>
            <h1><?php echo $lang["mymessages"]?></h1>
            <p><?php echo $lang["mymessages_introtext"];render_help_link('user/messages'); ?></p>

            <?php if ($user_preferences) { ?>
                <li>
                    <a href="<?php echo $baseurl_short?>pages/user/user_preferences.php" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo LINK_CARET . "&nbsp;" . $lang["userpreferences"];?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseurl_short?>pages/user/user_message.php" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo LINK_CARET . "&nbsp;" . $lang["new_message"];?>
                    </a>
                </li>
            <?php }

            $messages=array();
            // If no messages get out of here with a message
            if (!message_get($messages,$userref,true,$sort,$msg_order_by))		
                {
                ?>
                </ul>
                </div> <!-- End of VerticalNav -->
                </div> <!-- End of BasicsBox -->
                <?php
                echo $lang['mymessages_youhavenomessages'];
                include "../../include/footer.php";
                return;
                }

            $results = count($messages);
            $totalpages = ceil($results / $per_page);
            $curpage = floor($offset / $per_page) + 1;
            $jumpcount = 1;
            
            $unread = false;

            // If there are unread messages show option to mark all as read
            foreach ($messages as $message)		
                {
                if ($message['seen']==0)
                    {
                    $unread=true;
                    break;
                    }
                }

            if ($unread) { ?>
                <li>
                    <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?allseen=<?php echo $userref; ?>" onclick="return CentralSpaceLoad(this,true);">    <?php echo LINK_CARET . "&nbsp;" . $lang['mymessages_markallread']; ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div> <!-- End of VerticalNav -->

    <?php 
    $url = $baseurl_short . "pages/user/user_messages.php?paging=true&msg_order_by=" . urlencode($msg_order_by) . "&sort=". urlencode($sort);
    ?>
 
    <div class="TopInpageNav">
        <div class="TopInpageNavLeft">
            <div class="InpageNavLeftBlock"><?php echo $lang["resultsdisplay"]?>:
                <?php 
                for ($n=0; $n<count($list_display_array); $n++)
                    {
                    if ($per_page == $list_display_array[$n])
                        { ?>
                        <span class="Selected"><?php echo htmlspecialchars($list_display_array[$n]) ?></span>
                        <?php
                        }
                    else
                        { ?>
                        <a href="<?php echo $url; ?>&per_page_list=<?php echo urlencode($list_display_array[$n])?>" onClick="return CentralSpaceLoad(this);">
                            <?php echo htmlspecialchars($list_display_array[$n]) ?>
                        </a>
                        <?php
                        } ?>&nbsp;|
                    <?php
                    }
 
                if ($per_page==99999)
                    { ?>
                    <span class="Selected"><?php echo $lang["all"]?></span>
                    <?php
                    }
                else
                    { ?>
                    <a href="<?php echo $url; ?>&per_page_list=99999" onClick="return CentralSpaceLoad(this);">
                        <?php echo $lang["all"]?>
                    </a>
                    <?php
                    }
                ?>
            </div>
        </div>
        <?php pager(false); ?>
        <div class="clearerleft"></div>
    </div>

    <div class="ListViewBulkActions">
        <span id="messages-delete-selected" class="DisabledLink">
            <i class="fas fa-trash-alt"></i><?php echo $lang["action-delete"]; ?>
                </span>
        <span id="messages-mark-selected-read" class="DisabledLink">
            <i class="fas fa-envelope-open"></i><?php echo $lang["mymessages_markread"]; ?>
                </span>
        <span id="messages-mark-selected-unread" class="DisabledLink">
            <i class="fas fa-envelope"></i><?php echo $lang["mymessages_markunread"]; ?>
                </span>
    </div>

    <div class="Listview" id="user_messages">
        <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
            <tr class="ListviewTitleStyle">
                <td><input type="checkbox" id="messages-select-all"></td>
                <td>
                    <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?offset=0&msg_order_by=created&sort=<?php echo urlencode($revsort)?>" onClick="return CentralSpaceLoad(this);">
                        <?php echo $lang["created"]?>
                    </a>
                    <?php if ($msg_order_by == "created") { ?>
                        <div class="<?php echo urlencode($sort)?>">&nbsp;</div>
                    <?php } ?>
                </td>
                <td>
                    <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?offset=0&msg_order_by=from&sort=<?php echo urlencode($revsort)?>" onClick="return CentralSpaceLoad(this);">
                        <?php echo $lang["from"]?>
                    </a>
                    <?php if ($msg_order_by == "from") { ?>
                        <div class="<?php echo urlencode($sort)?>">&nbsp;</div>
                    <?php } ?>
                </td>
                <?php if ($messages_actions_fullname) { ?>
                    <td>
                        <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?offset=0&msg_order_by=fullname&sort=<?php echo urlencode($revsort)?>" onClick="return CentralSpaceLoad(this);">
                            <?php echo $lang["fullname"]?>
                        </a>
                        <?php if ($msg_order_by == "fullname") { ?>
                            <div class="<?php echo urlencode($sort)?>">&nbsp;</div>
                        <?php } ?>
                    </td>
                <?php } ?>
                <?php if ($messages_actions_usergroup) { ?>
                    <td><?php echo $lang["property-user_group"]; ?></td>
                <?php } ?>
                <td>
                    <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?offset=0&msg_order_by=message&sort=<?php echo urlencode($revsort)?>" onClick="return CentralSpaceLoad(this);">
                        <?php echo $lang["message"]?>
                    </a>
                    <?php if ($msg_order_by == "message") { ?>
                        <div class="<?php echo urlencode($sort)?>">&nbsp;</div>
                    <?php } ?>
                </td>
                <td>
                    <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?offset=0&msg_order_by=expires&sort=<?php echo urlencode($revsort)?>" onClick="return CentralSpaceLoad(this);">
                        <?php echo $lang["expires"]?>
                    </a>
                    <?php if ($msg_order_by == "expires") { ?>
                        <div class="<?php echo urlencode($sort)?>">&nbsp;</div>
                    <?php } ?>
                </td>
                <td>
                    <a href="<?php echo $baseurl_short?>pages/user/user_messages.php?offset=0&msg_order_by=seen&sort=<?php echo urlencode($revsort)?>" onClick="return CentralSpaceLoad(this);">
                        <?php echo $lang["seen"]?>
                    </a>
                    <?php if ($msg_order_by == "seen") { ?>
                        <div class="<?php echo urlencode($sort)?>">&nbsp;</div>
                    <?php } ?>
                </td>
                <td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
            </tr>
            <?php
            for ($n = $offset; (($n < count($messages)) && ($n < ($offset + $per_page))); $n++)
                {
                $message = $messages[$n]["message"]; 
                // Full message is retrieved via api to avoid long messages killing the page
                if(mb_strlen($message) > 100)
                    {
                    $message = mb_strcut($messages[$n]["message"],0,70) . "...";
                    }
                $message = strip_tags_and_attributes($message); 
                $message = nl2br($message);
                $url_encoded = urlencode($messages[$n]["url"]);
                $unread_css = ($messages[$n]["seen"] == 0 ? " MessageUnread" : "");
                $userbyname = get_user_by_username($messages[$n]["owner"]);
                $user = get_user($userbyname);
                if(!$user)
                    {
                    $user = array('fullname'=> $applicationname,'groupname'=>'');
                    }
                ?>
                <tr>
                    <td><input type="checkbox" class="message-checkbox" data-message="<?php echo (int)$messages[$n]['ref'];?>" id="message-checkbox-<?php echo (int)$messages[$n]['ref'];?>"></td>
                    <td class="SingleLine<?php echo $unread_css; ?>"><?php echo nicedate($messages[$n]["created"],true); ?></td>
                    <td class="<?php echo $unread_css; ?>"><?php echo htmlspecialchars((string)$messages[$n]["owner"]); ?></td>
                    <?php if ($messages_actions_fullname) { ?>
                        <td class="SingleLine<?php echo $unread_css; ?>"><?php echo strip_tags_and_attributes($user['fullname']); ?></td>
                    <?php } ?>
                    <?php if ($messages_actions_usergroup) { ?>
                        <td class="<?php echo $unread_css; ?>"><?php echo $user['groupname']; ?></td>
                    <?php } ?>
                    <td class="<?php echo $unread_css; ?>">
                        <a href="#Header" onclick="show_message(<?php echo (int)$messages[$n]['message_id'] ?>)"><?php echo $message; ?></a>
                    </td>
                    <td class="SingleLine<?php echo $unread_css; ?>"><?php echo nicedate($messages[$n]["expires"]); ?></td>
                    <td class="<?php echo $unread_css; ?>"><?php echo ($messages[$n]["seen"]==0 ? '<i class="fas fa-envelope"></i>' : '<i class="far fa-envelope-open"></i>'); ?></td>
                    <td>
                        <div class="ListTools">
                            <?php
                            if ($messages[$n]["type"] & MESSAGE_ENUM_NOTIFICATION_TYPE_USER_MESSAGE)
                                {
                                $replyurl = $baseurl_short . "pages/user/user_message.php?msgto=" . (int)$messages[$n]["ownerid"];
                                ?>
                                <a href="<?php echo $replyurl; ?>"><?php echo '<i class="fas fa-reply"></i>&nbsp;' . htmlspecialchars($lang["reply"]); ?></a>
                                <?php
                                }

                            if ($messages[$n]["url"]!="")
                                { ?>
                                <a href="<?php echo escape_quoted_data($messages[$n]["url"]); ?>"><?php echo '<i class="fas fa-link"></i>&nbsp;' . htmlspecialchars($lang["link"]); ?></a>
                                <?php
                                } ?> 
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div> <!-- End of BasicsBox -->

<script>
    // Array of selected message refs
    if (typeof selected_messages == 'undefined')
        {
        var selected_messages = [];
        }
 
    // Select all messages ticked
    if (typeof select_all_checkbox == 'undefined')
        {
        var select_all_checkbox = false;
        }
 
    jQuery(document).ready(function()
        {
        // Selecting and deselecting all messages
        jQuery("#messages-select-all").click(function(e)
            {
            var input = e.target;
            var input_checked = jQuery(input).prop("checked");
            
            if (!input_checked)
                {
                jQuery('.message-checkbox').prop('checked', false);
                selected_messages = [];
                select_all_checkbox = false;
                display_message_actions(false);
                }
            else
                {
                jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?getrefs=<?php echo $userref; ?>',function(data) {
                    var json_refs = JSON.parse(data);
                    for (var i = 0; i < json_refs.length; i++)
                        {
                        var message_ref = parseInt(json_refs[i].ref);
                        var message_array_index = selected_messages.indexOf(message_ref);
                        if (message_array_index < 0)
                            {
                            selected_messages.push(message_ref);
                            }
                        }
                    display_message_actions(selected_messages.length > 0);
                    });
                jQuery('.message-checkbox').prop('checked', true);
                select_all_checkbox = true;
                }
            });
 
        // Selecting and deselecting a single message
        jQuery(".message-checkbox").click(function(e)
            {
            var input = e.target;
            var input_checked = jQuery(input).prop("checked");
            var message_ref = jQuery(input).data("message");
            var message_array_index = selected_messages.indexOf(message_ref);
 
            if (!input_checked)
                {
                // Unticked, remove from array
                if (message_array_index >= 0)
                    {
                    selected_messages.splice(message_array_index,1);
                    }
                }
            else
                {
                // Ticked, add to array
                if (message_array_index < 0)
                    {
                    selected_messages.push(message_ref);
                    }
                }
 
            display_message_actions(selected_messages.length > 0);
            });
        
        jQuery("#messages-select-all").prop("checked", select_all_checkbox);
        tick_selected_messages();
        });
      
    function display_message_actions(show)
        {
        if (show == true)
            {
            jQuery(".ListViewBulkActions span").removeClass("DisabledLink"); 
            jQuery('.ListViewBulkActions').children().click(function(e){
                
                if ((jQuery(e.target).index()==0)){
                    jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?deleteselusrmsg='  + JSON.stringify(selected_messages), 
                        function() {message_poll();CentralSpaceLoad('',true);});
                } else if ((jQuery(e.target).index()==1)) {
                    jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?selectedseen='  + JSON.stringify(selected_messages), 
                        function() {message_poll();CentralSpaceLoad('',true);
                    });
                } else if ((jQuery(e.target).index()==2)) {
                    jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?selectedunseen='  + JSON.stringify(selected_messages), 
                        function() {
                        message_poll();CentralSpaceLoad('',true);
                    });
                } 
                jQuery('.message-checkbox').prop('checked', false);
                selected_messages = [];
                select_all_checkbox = false;
                display_message_actions(false);
                window.history.pushState('/pages/user/user_messages.php', 'user_messages.php', '<?php echo $baseurl?>/pages/user/user_messages.php');
                e.stopImmediatePropagation(); 
            });  
           }
        else
            {
           jQuery(".ListViewBulkActions span").addClass("DisabledLink");
            }
        }
 
    function tick_selected_messages()
        {
        // Tick messages that have been selected
        for (i = 0; i < selected_messages.length; i++)
            {
            var tickbox = "message-checkbox-" + selected_messages[i];
            if (document.getElementById(tickbox) != null)
                {
                document.getElementById(tickbox).checked = true;
                }
            }
        display_message_actions(selected_messages.length > 0);
        }

    function show_message(ref)
        {
        // Show full message in modal
        api("get_user_message",{'ref': ref},function(response)
            {
            console.debug(response);
            if(response.length != false)
                {
                msgtext   = response['message'];
                msgurl    = response['url'];
                msgowner  = response['msgowner'];
                message_modal(msgtext,msgurl,ref,msgowner);
                }
            });
        }
 
</script>

<?php

include "../../include/footer.php";
