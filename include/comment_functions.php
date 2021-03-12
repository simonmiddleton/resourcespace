<?php

/**
 * Write comments to the database, also deals with hiding and flagging comments
 *
 * @return void
 */
function comments_submit() 
	{		
	global $username, $anonymous_login, $userref, $regex_email, $comments_max_characters, $lang, $email_notify, $comments_email_notification_address;
	
	if ($username == $anonymous_login && (getvalescaped("fullname","") == "" || preg_match ("/${regex_email}/", getvalescaped("email","")) === false)) return;
	
	$comment_to_hide = getvalescaped("comment_to_hide",0,true);
	
	if (($comment_to_hide != 0) && (checkPerm("o"))) {	
		$sql = "update comment set hide=1 where ref='$comment_to_hide'";
		sql_query ($sql);		
		return;
	}
	
	$comment_flag_ref = getvalescaped("comment_flag_ref",0,true);	
	
	// --- process flag request
	
	if ($comment_flag_ref != 0) 
		{	
		$comment_flag_reason = getvalescaped("comment_flag_reason","");		
		$comment_flag_url = getvalescaped("comment_flag_url","");
		
		if ($comment_flag_reason == "" || $comment_flag_url == "") return;

		# the following line can be simplified using strstr (with before_needle boolean) but not supported < PHP 5.3.0		
		if (!strpos ($comment_flag_url, "#") === false) $comment_flag_url = substr ($comment_flag_url, 0, strpos ($comment_flag_url, "#")-1);
		
		$comment_flag_url .= "#comment${comment_flag_ref}";		// add comment anchor to end of URL
		
		$comment_body = sql_query("select body from comment where ref='$comment_flag_ref'");		
		$comment_body = (!empty($comment_body[0]['body'])) ? $comment_body[0]['body'] : "";
		
		if ($comment_body == "") return;
		
		$email_subject = (text("comments_flag_notification_email_subject")!="") ?
			text("comments_flag_notification_email_subject") : $lang['comments_flag-email-default-subject'];
			
		$email_body = (text("comments_flag_notification_email_body")!="") ?
			text("comments_flag_notification_email_body") : $lang['comments_flag-email-default-body'];
		
		$email_body .=	"\r\n\r\n\"${comment_body}\"";
		$email_body .= "\r\n\r\n${comment_flag_url}";		
		$email_body .= "\r\n\r\n${lang['comments_flag-email-flagged-by']} ${username}";		
		$email_body .= "\r\n\r\n${lang['comments_flag-email-flagged-reason']} \"${comment_flag_reason}\"";
		
		$email_to = (
				empty ($comments_email_notification_address)
				
				// (preg_match ("/${regex_email}/", $comments_email_notification_address) === false)		// TODO: make this regex better
			) ? $email_notify : $comments_email_notification_address;
		
		rs_setcookie("comment${comment_flag_ref}flagged", "true");				
		$_POST["comment${comment_flag_ref}flagged"] = "true";		// we set this so that the subsequent getval() function will pick up this comment flagged in the show comments function (headers have already been sent before cookie set)
		
		send_mail ($email_to, $email_subject, $email_body);
		return;
	}
	
	// --- process comment submission
	if (											// we don't want to insert an empty comment or an orphan
		(getvalescaped("body","") == "") ||
		((getvalescaped("collection_ref","") == "") && (getvalescaped("resource_ref","") == "") && (getvalescaped("ref_parent","") == ""))
		)
		return;
		
	if ($username == $anonymous_login)	// anonymous user		
		{				
			$sql_fields = "fullname, email, website_url";				
			$sql_values = "'" . getvalescaped("fullname", "") . "','" . getvalescaped("email", "") . "','" . getvalescaped("website_url", "") . "'";													
		}
	else
		{
			$sql_fields = "user_ref";
			$sql_values = "'" . $userref . "'";
		}

	$body = getvalescaped("body", "");		
	if (strlen ($body) > $comments_max_characters) $body = substr ($body, 0, $comments_max_characters);		// just in case not caught in submit form
	
	$parent_ref =  getvalescaped("ref_parent", 0,true);
	$collection_ref =  getvalescaped("collection_ref", 0,true);
	$resource_ref =  getvalescaped("resource_ref", 0,true);
	
	$sql = "insert into comment (ref_parent, collection_ref, resource_ref, {$sql_fields}, body) values ("	.
				($parent_ref == 0 ? "NULL" : "'$parent_ref'") . "," .
				($collection_ref == 0 ? "NULL" : "'$collection_ref'") . "," .
				($resource_ref == 0 ? "NULL" : "'$resource_ref'") . "," .	
				$sql_values . "," .					
				"'${body}'" .							
			")";
	sql_query($sql);

	// Notify anyone tagged.
	comments_notify_tagged($body,$userref,$resource_ref,$collection_ref);
	}

/**
 * Parse a comment and replace and add links to any user, resource and collection tags
 *
 * @param  string  $text                 The input text e.g. the body of the comment
 *
 * @return void
 */
function comments_tags_to_links($text)
	{
	global $baseurl_short;
    $text=preg_replace('/@(\S+)/s', '<a href="' . $baseurl_short . 'pages/user/user_profile.php?username=$1">@$1</a>', $text);

    $text=preg_replace('/r([0-9]{1,})/si', '<a href="' . $baseurl_short . '?r=$1">r$1</a>', $text); # r12345 to resource link

    $text=preg_replace('/c([0-9]{1,})/si', '<a href="' . $baseurl_short . '?c=$1">c$1</a>', $text); # c12345 to collection link

	return $text;
	}



/**
 * Display all comments for a resource or collection
 *
 * @param  integer $ref                 The reference of the resource, collection or the comment (if called from itself recursively)
 * @param  boolean $bcollection_mode    false == show comments for resources, true == show comments for collection
 * @param  boolean $bRecursive          Recursively show comments, defaults to true, will be set to false if depth limit reached
 * @param  integer $level               Used for recursion for display indentation etc.
 *
 * @return void
 */
function comments_show($ref, $bcollection_mode = false, $bRecursive = true, $level = 1) 
	{	
	if(!is_numeric($ref))
		{
		return false;
		}
		
	global $baseurl, $username, $anonymous_login, $lang, $comments_max_characters, $comments_flat_view, $regex_email, $comments_show_anonymous_email_address;
	
	$anonymous_mode = (empty ($username) || $username == $anonymous_login);		// show extra fields if commenting anonymously
	
	if ($comments_flat_view) $bRecursive = false;	
			
	$bRecursive = $bRecursive && ($level < $GLOBALS['comments_responses_max_level']);				
	
	// set 'name' to either user.fullname, comment.fullname or default 'Anonymous'
	
	$sql = 	"select c.ref thisref, c.ref_parent, c.hide, c.created, c.body, c.website_url, c.email, u.username, u.ref, u.profile_image, parent.created 'responseToDateTime', " .			
			"IFNULL(IFNULL(c.fullname, u.fullname), '" . $lang['comments_anonymous-user'] . "') 'name' ," .  			
			"IFNULL(IFNULL(parent.fullname, uparent.fullname), '" . $lang['comments_anonymous-user'] . "') 'responseToName' " .  			
			"from comment c left join (user u) on (c.user_ref = u.ref) left join (comment parent) on (c.ref_parent = parent.ref) left join (user uparent) on (parent.user_ref = uparent.ref) ";		
	$collection_ref = ($bcollection_mode) ? $ref : "";
	$resource_ref = ($bcollection_mode) ? "" : $ref;
	
	$collection_mode = $bcollection_mode ? "collection_mode=true" : "";		
			
	if ($level == 1) 
		{		
		
		// pass this JS function the "this" from the submit button in a form to post it via AJAX call, then refresh the "comments_container"
		
		echo<<<EOT

		<script src="${baseurl}/lib/js/tagging.js"></script>
		<script type="text/javascript">
		
			var regexEmail = new RegExp ("${regex_email}");
		
			function validateAnonymousComment(obj) {							
				return (
					regexEmail.test (String(obj.email.value).trim()) &&
					String(obj.fullname.value).trim() != "" &&
					validateComment(obj)
				)				
			}
			
			function validateComment(obj) {
				return (String(obj.body.value).trim() != "");
			}
			
			function validateAnonymousFlag(obj) {
				return (
					regexEmail.test (String(obj.email.value).trim()) &&
					String(obj.fullname.value).trim() != "" &&
					validateFlag(obj)
				)
			}
			
			function validateFlag(obj) {
				return (String(obj.comment_flag_reason.value).trim() != "");				
			}
		
			function submitForm(obj) {				
				jQuery.post(
					'${baseurl}/pages/ajax/comments_handler.php?ref={$ref}&collection_mode={$collection_mode}',
					jQuery(obj).serialize(),
					function(data)
					{
					jQuery('#comments_container').replaceWith(data);
					}
				);
			}
		</script>		
		
		<div id="comments_container">				
		<div id="comment_form">
			<form class="comment_form" action="javascript:void(0);" method="">
EOT;
        generateFormToken("comment_form");
        hook("beforecommentbody");
        echo <<<EOT
				<input id="comment_form_collection_ref" type="hidden" name="collection_ref" value="${collection_ref}"></input>
				<input id="comment_form_resource_ref" type="hidden" name="resource_ref" value="${resource_ref}"></input>				
				<textarea class="CommentFormBody" id="comment_form_body" name="body" maxlength="${comments_max_characters}" placeholder="${lang['comments_body-placeholder']}" onkeyup="TaggingProcess(this)"></textarea>
				
EOT;
		
		if ($anonymous_mode)			
			{
			echo <<<EOT
				<br />
				<input class="CommentFormFullname" id="comment_form_fullname" type="text" name="fullname" placeholder="${lang['comments_fullname-placeholder']}"></input>
				<input class="CommentFormEmail" id="comment_form_email" type="text" name="email" placeholder="${lang['comments_email-placeholder']}"></input>
				<input class="CommentFormWebsiteURL" id="comment_form_website_url" type="text" name="website_url" placeholder="${lang['comments_website-url-placeholder']}"></input>
				
EOT;
			}		
			
		$validateFunction = $anonymous_mode ? "if (validateAnonymousComment(this.parentNode))" : "if (validateComment(this.parentNode))";
			
		echo<<<EOT
				<br />				
				<input class="CommentFormSubmit" type="submit" value="${lang['comments_submit-button-label']}" onClick="${validateFunction} { submitForm(this.parentNode) } else { alert ('${lang['comments_validation-fields-failed']}'); } ;"></input>
			</form>	
		</div> 	<!-- end of comment_form -->	
		
EOT;
	
		$sql .= $bcollection_mode ? "where c.collection_ref=${ref}" : "where c.resource_ref=${ref}";  // first level will look for either collection or resource comments		
		if (!$comments_flat_view) $sql .= " and c.ref_parent is null";				
		}			
	else 
		{
		$sql .= "where c.ref_parent=${ref}";		// look for child comments, regardless of what type of comment
		}
	
	$sql .= " order by c.created desc";	
	$found_comments = sql_query($sql);
	
	foreach ($found_comments as $comment) 			
		{						
			
			$thisRef = $comment['thisref'];
			
			echo "<div class='CommentEntry' id='comment${thisRef}' style='margin-left: " . ($level-1)*50 . "px;'>";	// indent for levels - this will always be zero if config $comments_flat_view=true						
			
			# ----- Information line
			hook("beforecommentinfo", "all",array("ref"=>$comment["ref"]));
   
			echo "<div class='CommentEntryInfoContainer'>";			
			echo "<div class='CommentEntryInfo'>";
			if ($comment['profile_image'] != "" && $anonymous_mode != true)
			    {
				echo "<div><img src='" . get_profile_image("",$comment['profile_image']). "' id='CommentProfileImage'></div>";
			    }
			echo "<div class='CommentEntryInfoCommenter'>";						
			

			if (empty($comment['name'])) $comment['name'] = $comment['username'];
			if (!hook("commentername", "all",array("ref"=>$comment["ref"])))
			
			echo "<div class='CommentEntryInfoCommenterName'>" . htmlspecialchars($comment['name']) . "</div>";		
			
			if ($comments_show_anonymous_email_address && !empty($comment['email']))
				{
				echo "<div class='CommentEntryInfoCommenterEmail'>" . htmlspecialchars ($comment['email']) . "</div>";
				}
			if  (!empty ($comment['website_url']))
				{
				echo "<div class='CommentEntryInfoCommenterWebsite'>" . htmlspecialchars ($comment['website_url']) . "</div>";
				}			
									
			echo "</div>";			


			echo "<div class='CommentEntryInfoDetails'>" . strftime('%a',strtotime($comment["created"])) . " " . nicedate($comment["created"], true, true, true). " ";			
			if ($comment['responseToDateTime']!="")
				{
				$responseToName = htmlspecialchars ($comment['responseToName']);
				$responseToDateTime =  strftime('%a',strtotime($comment["responseToDateTime"])) . " " . nicedate($comment['responseToDateTime'], true, true, true);						
				$jumpAnchorID = "comment" . $comment['ref_parent'];								
				echo $lang['comments_in-response-to'] . "<br /><a class='.smoothscroll' rel='' href='#${jumpAnchorID}'>${responseToName} " . $lang['comments_in-response-to-on'] . " ${responseToDateTime}</a>";				
				}						
			echo "</div>";	// end of CommentEntryInfoDetails		
			echo "</div>";	// end of CommentEntryInfoLine			
			echo "</div>";	// end CommentEntryInfoContainer
			
			echo "<div class='CommentBody'>";			
			if ($comment['hide'])
			{
			if (text("comments_removal_message")!="")
				{
					echo text("comments_removal_message");
				}
			else
				{
					echo $lang["hidden"];
				}
			}
			else
				{
				echo comments_tags_to_links(htmlspecialchars ($comment['body']));
				}
			echo "</div>";			
			
			# ----- Form area
			
			$validateFunction = $anonymous_mode ? "if (validateAnonymousFlag(this.parentNode))" : "if (validateFlag(this.parentNode))";
			
			if (!getval("comment${thisRef}flagged",""))
				{
				echo<<<EOT
					
					<div id="CommentFlagContainer${thisRef}" style="display: none;">
						<form class="comment_form" action="javascript:void(0);" method="">
							<input type="hidden" name="comment_flag_ref" value="${thisRef}"></input>														
							<input type="hidden" name="comment_flag_url" value=""></input>														
													
EOT;
                hook("beforecommentflagreason");
                generateFormToken("comment_form");
                echo <<<EOT
				    <textarea class="CommentFlagReason" maxlength="${comments_max_characters}" name="comment_flag_reason" placeholder="${lang['comments_flag-reason-placeholder']}"></textarea><br />	
EOT;

				if ($anonymous_mode) echo<<<EOT
							
							<input class="CommentFlagFullname" id="comment_flag_fullname" type="text" name="fullname" placeholder="${lang['comments_fullname-placeholder']}"></input>
							<input class="CommentFlagEmail" id="comment_flag_email" type="text" name="email" placeholder="${lang['comments_email-placeholder']}"></input><br />							

EOT;
				echo<<<EOT
							<input class="CommentFlagSubmit" type="submit" value="${lang['comments_submit-button-label']}" onClick="comment_flag_url.value=document.URL; ${validateFunction} { submitForm(this.parentNode); } else { alert ('${lang['comments_validation-fields-failed']}') }"></input>
						</form>
					</div>				
EOT;

				}			
			
			$respond_div_id = "comment_respond_" . $thisRef;
			
			echo "<div id='${respond_div_id}' class='CommentRespond'>";		// start respond div
			echo "<a href='javascript:void(0)' onClick='
				jQuery(\"#{$respond_div_id}\").replaceWith(jQuery(\"#comment_form\").clone().attr(\"id\",\"${respond_div_id}\")); 
				jQuery(\"<input>\").attr({type: \"hidden\", name: \"ref_parent\", value: \"$thisRef\"}).appendTo(\"#${respond_div_id} .comment_form\");				
			'>" . '<i aria-hidden="true" class="fa fa-reply"></i>&nbsp;' . $lang['comments_respond-to-this-comment'] . "</a>";			
			echo "</div>";		// end respond

			echo "<div class='CommentEntryInfoFlag'>";		
			if (getval("comment${thisRef}flagged","") || $comment['hide'])
				{
					echo "<div class='CommentFlagged'><i aria-hidden='true' class='fa fa-fw fa-flag'>&nbsp;</i>${lang['comments_flag-has-been-flagged']}</div>";			
				} else {				
					echo<<<EOT
					<div class="CommentFlag">
						<a href="javascript:void(0)" onclick="jQuery('#CommentFlagContainer${thisRef}').toggle('fast');" ><i aria-hidden="true" class="fa fa-fw fa-flag">&nbsp;</i>${lang['comments_flag-this-comment']}</a>
					</div>
EOT;

				}							
			
            if(checkPerm("o"))
                {
                ?>
                <form class="comment_removal_form">
                    <?php generateFormToken("comment_removal_form"); ?>
                    <input type="hidden" name="comment_to_hide" value="<?php echo htmlspecialchars($thisRef); ?>"></input>                 
                    <a href="javascript:void(0)" onclick="if (confirm ('<?php echo htmlspecialchars($lang['comments_hide-comment-text-confirm']); ?>')) submitForm(this.parentNode);"><?php echo '<i aria-hidden="true" class="fa fa-trash-alt"></i>&nbsp;' . $lang['comments_hide-comment-text-link']; ?></a>
                </form>
                <?php
                }
			
			echo "</div>";		// end of CommentEntryInfoFlag
							
			echo "</div>";		// end of CommentEntry
			
			if ($bRecursive) comments_show($thisRef, $bcollection_mode, true, $level+1);				

			
		}			
		if ($level == 1)  echo "</div>";  // end of comments_container
	}

/**
 * Notify anyone tagged when a new comment is posted
 *
 * @param  string $comment       The comment body
 * @param  integer $from_user    Who posted the comment
 * @param  integer $resource     If commenting on a resource, the resource ID
 * @param  integer $collection   If commenting on a collection, the collection ID
 *
 * @return void
 */
function comments_notify_tagged($comment,$from_user,$resource=null,$collection=null)
	{
	// Find tagged users.
	$success=preg_match_all("/@.*? /",$comment . " ", $tagged, PREG_PATTERN_ORDER);
	if (!$success) {return true;} // Nothing to do, return out.
    foreach ($tagged[0] as $tag)
    	{
		$tag=substr($tag,1);$tag=trim($tag); // Get just the username.
		$user=get_user_by_username($tag); // Find the matching user ID
		// No match but there's an underscore? Try replacing the underscore with a space and search again. Spaces are swapped to underscores when tagging.
		if ($user===false) {$user=get_user_by_username(str_replace("_"," ",$tag));}

		if ($user>0)
			{
			// Notify them.

			// Build a URL based on whether this is a resource or collection
			global $baseurl,$userref,$lang;
			$url=$baseurl . "/?" . (is_null($resource)?"c":"r") . "=" . (is_null($resource)?$collection:$resource);

			// Send the message.
			message_add(array($user),$lang["tagged_notification"] . " " . $comment,$url,$userref);
			}

		}
	return true;
	}
