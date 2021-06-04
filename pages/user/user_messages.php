<?php

include "../../include/db.php";
include "../../include/authenticate.php";

$offset=getvalescaped("offset",0,true);
$msg_order_by = getvalescaped("msg_order_by",getvalescaped("saved_msg_order_by", "created"));rs_setcookie('saved_msg_order_by', $msg_order_by);
$sort = getvalescaped("sort",getvalescaped("saved_msg_sort", "DESC"));rs_setcookie('saved_msg_sort', $sort);
$revsort = ($sort=="ASC") ? "DESC" : "ASC";
$per_page = getvalescaped("per_page_list", $default_perpage_list, true);rs_setcookie('per_page_list', $per_page);

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
        <a id="messages-delete-selected" class="DisabledLink">
            <i class="fas fa-trash-alt"></i><?php echo $lang["action-delete"]; ?>
        </a>
        <a id="messages-mark-selected-read" class="DisabledLink">
            <i class="fas fa-envelope-open"></i><?php echo $lang["mymessages_markread"]; ?>
        </a>
        <a id="messages-mark-selected-unread" class="DisabledLink">
            <i class="fas fa-envelope"></i><?php echo $lang["mymessages_markunread"]; ?>
        </a>
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
                $fullmessage = escape_check(strip_tags_and_attributes($messages[$n]["message"],array("table","tbody","th","tr","td","a"),array("href","target","width","border")));
                $fullmessage = htmlspecialchars($fullmessage,ENT_QUOTES);
                $message = strip_tags_and_attributes($messages[$n]["message"]);
                $message = nl2br($message,ENT_QUOTES);
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
                    <td><input type="checkbox" class="message-checkbox" data-message="<?php echo $messages[$n]['ref'];?>" id="message-checkbox-<?php echo $messages[$n]['ref'];?>"></td>
                    <td class="SingleLine<?php echo $unread_css; ?>"><?php echo nicedate($messages[$n]["created"],true); ?></td>
                    <td class="<?php echo $unread_css; ?>"><?php echo $messages[$n]["owner"]; ?></td>
                    <?php if ($messages_actions_fullname) { ?>
                        <td class="SingleLine<?php echo $unread_css; ?>"><?php echo strip_tags_and_attributes($user['fullname']); ?></td>
                    <?php } ?>
                    <?php if ($messages_actions_usergroup) { ?>
                        <td class="<?php echo $unread_css; ?>"><?php echo $user['groupname']; ?></td>
                    <?php } ?>
                    <td class="<?php echo $unread_css; ?>">
                        <a href="#Header" onclick="message_modal('<?php echo $fullmessage; ?>','<?php echo $url_encoded; ?>',<?php echo $messages[$n]["ref"]; ?>,'<?php echo $messages[$n]["owner"] ?>');"><?php echo $message; ?></a>
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
                                <a href="<?php echo $replyurl; ?>"><?php echo LINK_CARET ?><?php echo $lang["reply"]; ?></a>
                                <?php
                                }

                            if ($messages[$n]["url"]!="")
                                { ?>
                                <a href="<?php echo $messages[$n]["url"]; ?>"><?php echo LINK_CARET ?><?php echo $lang["link"]; ?></a>
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
            jQuery(".ListViewBulkActions a").attr("href", "<?php echo $baseurl_short; ?>pages/user/user_messages.php");
            jQuery(".ListViewBulkActions a").removeClass("DisabledLink");
 
            jQuery("#messages-delete-selected").attr("onclick", "jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?deleteselusrmsg=" + JSON.stringify(selected_messages) + "',function() { message_poll(); return CentralSpaceLoad(this,true);});");
            jQuery("#messages-mark-selected-read").attr("onclick", "jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?selectedseen="  + JSON.stringify(selected_messages) + "',function() { message_poll(); return CentralSpaceLoad(this,true);});");
            jQuery("#messages-mark-selected-unread").attr("onclick", "jQuery.get('<?php echo $baseurl; ?>/pages/ajax/message.php?selectedunseen="  + JSON.stringify(selected_messages) + "',function() { message_poll(); return CentralSpaceLoad(this,true);});");
            }
        else
            {
            jQuery(".ListViewBulkActions a").removeAttr("href");
            jQuery(".ListViewBulkActions a").addClass("DisabledLink");
            jQuery(".ListViewBulkActions a").removeAttr("onclick");
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
 
</script>

<?php

include "../../include/footer.php";
