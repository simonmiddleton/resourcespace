<?php
/**
 * View resource page
 * 
 * @package ResourceSpace
 * @subpackage Pages
 */
include_once "../include/db.php";

$ref=getval("ref",0,true);

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getvalescaped("k","");if (($k=="") || (!check_access_key($ref,$k))) {include "../include/authenticate.php";}
include_once "../include/image_processing.php";


// Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
$internal_share_access = internal_share_access();

# Update hit count
update_hitcount($ref);
	
# fetch the current search (for finding similar matches)
$search=getvalescaped("search","");
$order_by=getvalescaped("order_by","relevance");

# add order_by check to filter values prefixed by 'field'
if(preg_match("/^field(.*)/", $order_by, $matches))
    {
    if (!in_array($matches[1],$sort_fields)) # check that field ref  is in config $sort_fields array
        {
        $order_by="relevance"; # if not, then sort by relevance
        }
    }

$offset=getvalescaped("offset",0,true);
$restypes=getvalescaped("restypes","");
$starsearch=getvalescaped("starsearch","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getvalescaped("archive","");
$per_page=getvalescaped("per_page",0,true);
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$modal=(getval("modal","")=="true");
$context=($modal?"Modal":"Root"); # Set a unique context, used for JS variable scoping so this page in a modal doesn't conflict with the same page open behind the modal.

# next / previous resource browsing
$curpos=getvalescaped("curpos","");
$go=getval("go","");

if ($go!="") 
	{
	$origref=$ref; # Store the reference of the resource before we move, in case we need to revert this.
	
	# Re-run the search and locate the next and previous records.
	$modified_result_set=hook("modifypagingresult"); 
	if ($modified_result_set){
		$result=$modified_result_set;
	} else {
		$result=do_search($search,$restypes,$order_by,$archive,-1,$sort,false,$starsearch,false,false,"", getvalescaped("go",""));
	}
	if (is_array($result))
		{
		# Locate this resource
		$pos=-1;
		for ($n=0;$n<count($result);$n++)
			{
			if ($result[$n]["ref"]==$ref) {$pos=$n;}
			}
		if ($pos!=-1)
			{
			if (($go=="previous") && ($pos>0)) {$ref=$result[$pos-1]["ref"];if (($pos-1)<$offset) {$offset=$offset-$per_page;}}
			if (($go=="next") && ($pos<($n-1))) {$ref=$result[$pos+1]["ref"];if (($pos+1)>=($offset+$per_page)) {$offset=$pos+1;}} # move to next page if we've advanced far enough
			}
		elseif($curpos!="")
			{
			if (($go=="previous") && ($curpos>0) && isset($result[$curpos-1]["ref"])) {$ref=$result[$curpos-1]["ref"];if (($pos-1)<$offset) {$offset=$offset-$per_page;}}
			if (($go=="next") && ($curpos<($n)) && isset($result[$curpos]["ref"])) {$ref=$result[$curpos]["ref"];if (($curpos)>=($offset+$per_page)) {$offset=$curpos+1;}}  # move to next page if we've advanced far enough
			}
		else
			{
			?>
			<script type="text/javascript">
			alert('<?php echo $lang["resourcenotinresults"] ?>');
			</script>
			<?php
 			}
		}
    # Option to replace the key via a plugin (used by resourceconnect plugin).
    $newkey = hook("nextpreviewregeneratekey");
    if (is_string($newkey)) {$k = $newkey;}

    # Check access permissions for this new resource, if an external user.
    if ($k!="" && !$internal_share_access && !check_access_key($ref, $k)) {$ref = $origref;} # Cancel the move.
	}


hook("chgffmpegpreviewext", "", array($ref));

# Load resource data
$resource=get_resource_data($ref);
if ($resource===false)
    {
    $error = $lang['resourcenotfound'];
    if(getval("ajax","") != "")
        {
        error_alert($error, false);
        }
    else
        {
        include "../include/header.php";
        $onload_message = array("title" => $lang["error"],"text" => $error);
        include "../include/footer.php";
        }
    exit();
    }

hook("aftergetresourcedataview","",array($ref,$resource));

# Allow alternative configuration settings for this resource type.
resource_type_config_override($resource["resource_type"]);

# get comments count
$resource_comments=0;
if($comments_resource_enable && $comments_view_panel_show_marker){
    $resource_comments=sql_value("select count(*) value from comment where resource_ref='" . escape_check($ref) . "'","0");
}

# Should the page use a larger resource preview layout?
$use_larger_layout = true;
if (isset($resource_view_large_ext))
    {
    if (!in_array($resource["file_extension"], $resource_view_large_ext))
        {
        $use_larger_layout = false;
        }
    }
else
    {
    if (isset($resource_view_large_orientation) && $resource_view_large_orientation == true)
        {
        if ($resource["has_image"] == 1 && ($resource["thumb_height"] >= $resource["thumb_width"]))
            {
            # Portrait or square image
            $use_larger_layout = false;
            }
        }
    }

// Set $use_mp3_player switch if appropriate
$use_mp3_player = (
    !(isset($resource['is_transcoding']) && 1 == $resource['is_transcoding'])
    && (
            (
                in_array($resource['file_extension'], $ffmpeg_audio_extensions) 
                || 'mp3' == $resource['file_extension']
            )
            && $mp3_player
        )
);

if($use_mp3_player)
    {
    $mp3realpath = get_resource_path($ref, true, '', false, 'mp3');
    if(file_exists($mp3realpath))
        {
        $mp3path = get_resource_path($ref, false, $hide_real_filepath ? 'videojs' : '', false, 'mp3');
        }
    }

# Load access level
$access=get_resource_access($resource);

if(isset($user_dl_limit) && intval($user_dl_limit) > 0)
    {
    $download_limit_check = get_user_downloads($userref,$user_dl_days);
    if($download_limit_check >= $user_dl_limit)
        {
        $access = 1;
        }
    }


hook("beforepermissionscheck");

# check permissions
if($access == 2) 
	{
	if(isset($anonymous_login) && isset($username) && $username==$anonymous_login)
		{
        redirect('login.php');
        exit();
		}
    $error = $lang['error-permissiondenied'];
    if(getval("ajax","") != "")
        {
        error_alert($error, false);
        }
    else
        {
        include "../include/header.php";
        $onload_message = array("title" => $lang["error"],"text" => $error);
        include "../include/footer.php";
        }
    exit();
	}
		
hook("afterpermissionscheck");
		
# Establish if this is a metadata template resource, so we can switch off certain unnecessary features
$is_template=(isset($metadata_template_resource_type) && $resource["resource_type"]==$metadata_template_resource_type);

$title_field=$view_title_field; 
# If this is a metadata template and we're using field data, change title_field to the metadata template title field
if (isset($metadata_template_resource_type) && ($resource["resource_type"]==$metadata_template_resource_type))
	{
	if (isset($metadata_template_title_field)){
		$title_field=$metadata_template_title_field;
		}
	else {$default_to_standard_title=true;}	
	}

if ($pending_review_visible_to_all && isset($userref) && $resource["created_by"]!=$userref && $resource["archive"]==-1 && !checkperm("e0"))
	{
	# When users can view resources in the 'User Contributed - Pending Review' state in the main search
	# via the $pending_review_visible_to_all option, set access to restricted.
	$access=1;
	}

# If requested, refresh the collection frame (for redirects from saves)
if (getval("refreshcollectionframe","")!="")
	{
	refresh_collection_frame();
	}

# Update the hitcounts for the search keywords (if search specified)
# (important we fetch directly from $_GET and not from a cookie
$usearch= isset($_GET["search"]) ? $_GET["search"] : "";
if ((strpos($usearch,"!")===false) && ($usearch!="")) {update_resource_keyword_hitcount($ref,$usearch);}

# Log this activity
daily_stat("Resource view",$ref);
if ($log_resource_views) {resource_log($ref,'v',0);}

# downloading a file from iOS should open a new window/tab to prevent a download loop
$iOS_save=false;
if (isset($_SERVER['HTTP_USER_AGENT']))
	{
	$iOS_save=((stripos($_SERVER['HTTP_USER_AGENT'],"iPod")!==false || stripos($_SERVER['HTTP_USER_AGENT'],"iPhone")!==false || stripos($_SERVER['HTTP_USER_AGENT'],"iPad")!==false) ? true : false);
	}

# Show the header/sidebar
include "../include/header.php";

if ($metadata_report && isset($exiftool_path))
	{
	?>
	<script src="<?php echo $baseurl ?>/lib/js/metadata_report.js?css_reload_key=<?php echo $css_reload_key; ?>" type="text/javascript"></script>
	<?php
	}

if(!$save_as)
    {
    ?>
    <iframe id="dlIFrm"
            frameborder=0
            scrolling="auto"
            <?php
            if($debug_direct_download)
                {
                ?>width="600" height="200" style="display:block;"
                <?php
                }
            else 
                {
                ?>style="display:none"
                <?php
                }
                ?>> This browser can not use IFRAME.</iframe>
    <?php
    }

if($resource_contact_link && ($k=="" || $internal_share_access))
		{?>
		<script>
		function showContactBox(){
				
				if(jQuery('#contactadminbox').length)
					{
					jQuery('#contactadminbox').slideDown();
					return false;
					}
				
				jQuery.ajax({
						type: "GET",
						url: baseurl_short+"pages/ajax/contactadmin.php?ref="+<?php echo $ref ?>+"&insert=true&ajax=true",
						success: function(html){
								jQuery('#RecordDownload li:last-child').after(html);
								document.getElementById('messagetext').focus();
								},
						error: function(XMLHttpRequest, textStatus, errorThrown) {
							alert('<?php echo $lang["error"] ?>\n' + textStatus);
							}
						});
				}				
		</script>
		<?php
		}
		
hook("pageevaluation");

# Load resource field data
$multi_fields = FALSE;
# Related resources with tabs need all fields (even the ones from other resource types):
if(isset($related_type_show_with_data)) {
	$multi_fields = TRUE;
}

// Get all fields without checking permissions (for later dependency checking)
$fields_all=get_resource_field_data($ref,$multi_fields,FALSE,NULL,($k!="" && !$internal_share_access),$use_order_by_tab_view);

# Load field data
$fields=get_resource_field_data($ref,$multi_fields,!hook("customgetresourceperms"),NULL,($k!="" && !$internal_share_access),$use_order_by_tab_view);
$modified_view_fields=hook("modified_view_fields","",array($ref,$fields));if($modified_view_fields){$fields=$modified_view_fields;}

# If no fields were found advise of conifguration issue and exit.
if (!$fields_all || !$fields)
    {
    error_alert($lang["error_no_metadata"], false);
    exit();
    }

# Load edit access level (checking edit permissions - e0,e-1 etc. and also the group 'edit filter')
$edit_access = get_edit_access($ref,$resource["archive"],$fields,$resource);

# Check if resource is locked
$resource_locked = (int)$resource["lock_user"] > 0;
$unlock_option = checkperm("a") || ($userref == $resource["lock_user"] && $userref != $anonymous_login);
$lock_details = get_resource_lock_message($resource["lock_user"]);

if ($k!="" && !$internal_share_access) {$edit_access=0;}

//Check if we want to use a specified field as a caption below the preview
if(isset($display_field_below_preview) && is_int($display_field_below_preview))
	{
	$df=0;
	foreach ($fields as $field)
		{
		if($field["fref"]==$display_field_below_preview)
			{
			$displaycondition=check_view_display_condition($fields,$df,$fields_all);
			if($displaycondition)
				{
				$previewcaption=$fields[$df];
				// Remove from the array so we don't display it twice
				unset($fields[$df]);
				//Reorder array 
				$fields=array_values($fields);				
				}
			}
		$df++;			
		}
	}


// Add custom CSS for external users: 
if($k !='' && !$internal_share_access && $custom_stylesheet_external_share) {
    $css_path = dirname(__FILE__) . '/..' . $custom_stylesheet_external_share_path;
    if(file_exists($css_path)) {
        echo '<link href="' . $baseurl . $custom_stylesheet_external_share_path . '" rel="stylesheet" type="text/css" media="screen,projection,print" />';
    }
}

?>
<script>
var resource_lock_status = <?php echo (int)$resource_locked ?>;
var lockmessage = new Array();
lockmessage[<?php echo $ref ?>] = '<?php echo htmlspecialchars($lock_details) ?>';

<?php 
if($resource_locked && $resource['lock_user'] != $userref)
    {?>
    jQuery(document).ready(function ()
        {
        jQuery('.LockedResourceAction').each(function(){
            jQuery(this).attr("title","<?php echo htmlspecialchars($lock_details); ?>");
            });
        });
        <?php
    }?>

function updateResourceLock(resource,lockstatus)
    {
    // Fire an ajax call to update the lock state and update resource tools if successful
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo $baseurl_short; ?>pages/ajax/user_action.php',
        data: {
            ajax: 'true',
            action: 'updatelock',
            ref: resource,
            lock: lockstatus,
            <?php echo generateAjaxToken('UpdateLock'); ?>
        },
        success: function(response,status,xhr)
            {
            jQuery('#lock_link_' + resource).toggleClass("ResourceLocked");
            jQuery('#lock_link_' + resource).toggleClass("ResourceUnlocked");
            if(lockstatus==1)
                {               
                jQuery('#lock_link_' + resource).html('&nbsp;<?php echo $lang["action_unlock"] ;?>');
                jQuery('#lock_link_' + resource).attr("title","<?php echo $lang["status_locked_self"]; ?>");
                lockmessage[resource] = '<?php echo $lang["status_locked_self"]; ?>';
                jQuery('#lock_details_link').show();
                }
            else
                {
                jQuery('#lock_link_' + resource).html('&nbsp;<?php echo $lang["action_lock"] ;?>');
                lockmessage[resource] = '';
                jQuery('#lock_details_link').hide();
                // Timeout added as title resists removal if cursor is hovering as it is removed
                setTimeout(function() {jQuery('#lock_link_' + resource).removeAttr("title");},1000);
                }
            resource_lock_status = !resource_lock_status;
            },
        error: function(xhr, status, error)
            {
            console.log(xhr);
            if(typeof xhr.responseJSON.message !== undefined)
                {
                styledalert('<?php echo $lang["error"]; ?>',xhr.responseJSON.message);
                }
            else
                {
                styledalert('<?php echo $lang["error"]; ?>',xhr.statusText);
                }
            }
        });
    }

<?php
if ($view_panels)
    {
    ?>
    jQuery(document).ready(function () {		
        
        var comments_marker='<?php echo $comments_view_panel_show_marker?>';
        var comments_resource_enable='<?php echo $comments_resource_enable?>';
        var resource_comments='<?php echo $resource_comments?>';
        
        jQuery("#Metadata").appendTo("#Panel1");
        jQuery("#Metadata").addClass("TabPanel");
        
        
        jQuery("#CommentsPanelHeaderRowTitle").children(".Title").attr("panel", "Comments").appendTo("#Titles1");
        jQuery("#CommentsPanelHeaderRowTitle").remove();
        jQuery("#CommentsPanelHeaderRowPolicyLink").css("width","300px").css("float","right");
        removePanel=jQuery("#Comments").parents(".RecordBox");
        jQuery("#Comments").appendTo("#Panel1").addClass("TabPanel").hide();
        removePanel.remove();
        if(comments_marker==true && comments_resource_enable==true && resource_comments>'0'){
            jQuery("[panel='Comments']").append("&#42;");
        }

        jQuery("#RelatedResources").children().children(".Title").attr("panel", "RelatedResources").addClass("Selected").appendTo("#Titles2");
        removePanel=jQuery("#RelatedResources").parents(".RecordBox");
        jQuery("#RelatedResources").appendTo("#Panel2").addClass("TabPanel");
        removePanel.remove();
        

        jQuery("#SearchSimilar").children().children(".Title").attr("panel", "SearchSimilar").appendTo("#Titles2");
        removePanel=jQuery("#SearchSimilar").parents(".RecordBox");
        jQuery("#SearchSimilar").appendTo("#Panel2").addClass("TabPanel").hide();
        removePanel.remove();
        // if there are no related resources
        if (jQuery("#RelatedResources").length==0) {
            jQuery("#SearchSimilar").show();
            jQuery("div[panel='SearchSimilar']").addClass("Selected"); 
        }    
        
        // if there are no collections and themes
        if (jQuery("#resourcecollections").is(':empty')) {
        jQuery("div[panel='CollectionsThemes']").addClass("Selected"); 
        jQuery("#CollectionsThemes").show(); 
        }
        
        jQuery(".ViewPanelTitles").children(".Title").click(function(){
        // function to switch tab panels
            jQuery(this).parent().parent().children(".TabPanel").hide();
            jQuery(this).parent().children(".Title").removeClass("Selected");
            jQuery(this).addClass("Selected");
            jQuery("#"+jQuery(this).attr("panel")).css("position", "relative").css("left","0px");
            jQuery("#"+jQuery(this).attr("panel")).show();
            if (jQuery(this).attr("panel")=="Comments") {
            jQuery("#CommentsContainer").load(
                "../pages/ajax/comments_handler.php?ref=<?php echo $ref;?>", 
                function() {
                if (jQuery.type(jQuery(window.location.hash)[0])!=="undefined")				
                    jQuery(window.location.hash)[0].scrollIntoView();
                }						
            );	
            }
        });
    });
    <?php
    }?>

</script>
<!--Panel for record and details-->
<div class="RecordBox">
<div class="RecordPanel<?php echo $use_larger_layout ? ' RecordPanelLarge' : ''; ?>">

<div class="RecordHeader">
<?php if (!hook("renderinnerresourceheader")) { ?>


<?php

$urlparams= array(
	'ref'				=> $ref,
    'search'			=> $search,
    'order_by'			=> $order_by,
    'offset'			=> $offset,
    'restypes'			=> $restypes,
    'starsearch'		=> $starsearch,
    'archive'			=> $archive,
    'per_page'			=> $per_page,
    'default_sort_direction' => $default_sort_direction,
    'sort'				=> $sort,
	'context'			=> $context,
	'k'					=> $k,
	'curpos'			=> $curpos
);



# Check if actually coming from a search, but not if a numeric search and config_search_for_number is set or if this is a direct request e.g. ?r=1234.
if (!hook("replaceviewnav") && isset($_GET["search"]) && !($config_search_for_number && is_numeric($usearch)) && !($k != "" && strpos($search,"!collection") === false)) { ?>
<div class="backtoresults">
<a class="prevLink fa fa-arrow-left" href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams, array("go"=>"previous")) . "&amp;" .  hook("nextpreviousextraurl") ?>" onClick="return <?php echo ($modal?"Modal":"CentralSpace") ?>Load(this);" title="<?php echo $lang["previousresult"]?>"></a>
<?php 
if (!hook("viewallresults")) 
	{
	?>
	<a class="upLink" href="<?php echo generateURL($baseurl . "/pages/search.php",$urlparams,array("go"=>"up")) . (($search_anchors)?"&place=" . $ref:"") ?>" onClick="return CentralSpaceLoad(this);"><?php echo $lang["viewallresults"]?></a>
	<?php 
	} ?>
<a class="nextLink fa fa-arrow-right" href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams, array("go"=>"next")) . "&amp;" .  hook("nextpreviousextraurl") ?>" onClick="return <?php echo ($modal?"Modal":"CentralSpace") ?>Load(this);" title="<?php echo $lang["nextresult"]?>"></a>

<?php
	if($modal)
		{
		?>
		<a href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams) ?>" onClick="return CentralSpaceLoad(this);" class="maxLink fa fa-expand" title="<?php echo $lang["maximise"]?>"></a>
		<a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo $lang["close"] ?>"></a>
		<?php
		}
	?>
</div>
<?php
}
else if($modal)
	{
	?>
	<div class="backtoresults">
		<?php if (!hook("replacemaxlink")) { ?>
		<a href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams) ?>" onClick="return CentralSpaceLoad(this);" class="maxLink fa fa-expand" title="<?php echo $lang["maximise"]?>"></a>
		<?php } ?>
		<a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo $lang["close"] ?>"></a>
	</div>
	<?php
    }
	?>

<h1><?php hook("beforeviewtitle");?><?php
# Display title prefix based on workflow state.
if (!hook("replacetitleprefix","",array($resource["archive"]))) { switch ($resource["archive"])
	{
	case -2:
	?><span class="ResourcePendingSubmissionTitle"><?php echo $lang["status-2"]?>:</span>&nbsp;<?php
	break;
	case -1:
	?><span class="ResourcePendingReviewTitle"><?php echo $lang["status-1"]?>:</span>&nbsp;<?php
	break;
	case 1:
	?><span class="ArchiveResourceTitle"><?php echo $lang["status1"]?>:</span>&nbsp;<?php
	break;
	case 2:
	?><span class="ArchiveResourceTitle"><?php echo $lang["status2"]?>:</span>&nbsp;<?php
	break;
	case 3:
	?><span class="DeletedResourceTitle"><?php echo $lang["status3"]?>:</span>&nbsp;<?php
	break;
	}
	
	#If additional archive states are set, put them next to the field used as title
	if ( isset($additional_archive_states) && count($additional_archive_states)!=0)
		{
		if(in_array($resource["archive"],$additional_archive_states))
			{?>
			<span class="ArchiveResourceTitle"><?php echo $lang["status{$resource['archive']}"]?>:</span>&nbsp;<?php	
			}
		}
	}

if(!hook('replaceviewtitle'))
    {
		echo highlightkeywords(htmlspecialchars(i18n_get_translated(get_data_by_field($resource['ref'], $title_field), false)), $search);
    } /* end hook replaceviewtitle */
    ?>&nbsp;</h1>
<?php } /* End of renderinnerresourceheader hook */ ?>
</div>

<?php if (!hook("replaceresourceistranscoding")){
    if (isset($resource['is_transcoding']) && $resource['is_transcoding']!=0) { ?><div class="PageInformal"><?php echo $lang['resourceistranscoding']?></div><?php }
    } //end hook replaceresourceistrancoding ?>

<?php hook('renderbeforeresourceview', '', array('resource' => $resource));
if (in_array($resource["file_extension"], config_merge_non_image_types()) && $non_image_types_generate_preview_only)
    {
    $download_multisize=false;
    }
else
    {
    $download_multisize=true;
    }
?>

<div class="RecordResource">
<?php if (!hook("renderinnerresourceview")) { ?>
<?php if (!hook("replacerenderinnerresourcepreview")) { ?>
<?php if (!hook("renderinnerresourcepreview")) { ?>
<?php

# Try to find a preview file.
$video_preview_file = get_resource_path(
    $ref,
    true,
    'pre',
    false,
    (1 == $video_preview_hls_support || 2 == $video_preview_hls_support) ? 'm3u8' : $ffmpeg_preview_extension
);

# Default use_watermark if required by related_resources
$use_watermark = false;

if (file_exists("../players/type" . $resource["resource_type"] . ".php"))
	{
	include "../players/type" . $resource["resource_type"] . ".php";
	}
elseif (hook("replacevideoplayerlogic","",array($video_preview_file))){ }
elseif ((!(isset($resource['is_transcoding']) && $resource['is_transcoding']!=0) && file_exists($video_preview_file)))
	{
	# Include the player if a video preview file exists for this resource.
	$download_multisize=false;
	?>
	<div id="previewimagewrapper">
	<?php 
    if(!hook("customflvplay")) // Note: Legacy hook name; video_player.php no longer deals with FLV files.
	    {
		include "video_player.php";
	    }
	if(isset($previewcaption))
		{				
		display_field_data($previewcaption, true);
		}
	?></div><?php	
	}
elseif ($use_mp3_player && file_exists($mp3realpath) && !hook("replacemp3player"))
	{?>
	<div id="previewimagewrapper">
	<?php 
	$thumb_path=get_resource_path($ref,true,"pre",false,"jpg");
	if(file_exists($thumb_path))
		{$thumb_url=get_resource_path($ref,false,"pre",false,"jpg"); }
	else
		{$thumb_url=$baseurl . "/gfx/" . get_nopreview_icon($resource["resource_type"],$resource["file_extension"],false);}
	include "mp3_play.php";
	if(isset($previewcaption))
		{				
		display_field_data($previewcaption, true);
		}
	?></div><?php
	}	
else if(1 == $resource['has_image'])
    {
    $use_watermark = check_use_watermark();
	$use_size="scr";
	$imagepath = "";

	# Obtain imagepath for 'scr' if permissions allow
	if (resource_download_allowed($ref, $use_size, $resource['resource_type']))
		{
		$imagepath = get_resource_path($ref, true, $use_size, false, $resource['preview_extension'], true, 1, $use_watermark);
		}

	# Note that retina mode uses 'scr' size which we have just obtained, so superfluous code removed

	# Obtain imagepath for 'pre' if 'scr' absent OR hide filepath OR force 'pre' on view page 
    if(!( isset($imagepath) && file_exists($imagepath) )
       || $hide_real_filepath
       || $resource_view_use_pre)
        {
		$use_size="pre";
		$imagepath = get_resource_path($ref, true, $use_size, false, $resource['preview_extension'], true, 1, $use_watermark);
		}

	# Imagepath is the actual file path and can point to 'scr' or 'pre' as a result of the above

	# Fall back to 'thm' if necessary
    if(!file_exists($imagepath))
        {
        $use_size="thm";
        }
	$imageurl = get_resource_path($ref, false, $use_size, false, $resource['preview_extension'], true, 1, $use_watermark);
	# Imageurl is the url version of the path and can point to 'scr' or 'pre' or 'thm' as a result of the above

    $previewimagelink = generateURL("{$baseurl}/pages/preview.php", $urlparams, array("ext" => $resource["preview_extension"])) . "&" . hook("previewextraurl");
    $previewimagelink_onclick = 'return CentralSpaceLoad(this);';

    // PDFjs works only for PDF files. Because this requires the PDF file itself, we can only use this mode if user has
    // full access to the resource.
    if($resource['file_extension'] == 'pdf' && $use_pdfjs_viewer && $access == 0)
        {
        // IMPORTANT: never show the real file path with this feature
        $hide_real_filepath_initial = $hide_real_filepath;
        $hide_real_filepath = true;
        $pdfjs_original_file_path = get_resource_path($ref, false, '', false, $resource['file_extension']);
        $hide_real_filepath = $hide_real_filepath_initial;

        $previewimagelink = generateURL(
            "{$baseurl}/lib/pdfjs/web/viewer.php",
            $urlparams,
            array(
                'ref'  => $ref,
                'file' => $pdfjs_original_file_path
            )
        );
        $previewimagelink_onclick = '';
        }

	if (!hook("replacepreviewlink"))
        {
        ?>
    <div id="previewimagewrapper">
        <a id="previewimagelink"
           class="enterLink"
           href="<?php echo $previewimagelink; ?>"
           title="<?php echo $lang["fullscreenpreview"]; ?>"
           style="position:relative;"
           onclick="<?php echo $previewimagelink_onclick; ?>">
        <?php
        } 

	// Below actually means if the 'scr' or the 'pre' file exists then display it
	// It checks imagepath but references imageurl as the image source which will only point to 'scr' or 'pre' 
    if(file_exists($imagepath))
		{
		// Imageurl will never point to 'thm' in this context because the imagepath file_exists	
        list($image_width, $image_height) = @getimagesize($imagepath);
        ?>
        <img id="previewimage"
             class="Picture"
             src="<?php echo $imageurl; ?>" 
             alt="<?php echo $lang['fullscreenpreview']; ?>" 
             GALLERYIMG="no"
        <?php
        if($annotate_enabled)
            {
            ?>
             data-original="<?php echo "{$baseurl}/annotation/resource/{$ref}"; ?>"
            <?php
            }

        if($retina_mode)
            {
            ?>
             onload="this.width/=1.8;this.onload=null;"
            <?php
            }
            ?>/>
        <?php 
        }

    hook('aftersearchimg', '', array($ref));
    ?>
        </a>
    <?php
    if(isset($previewcaption))
        {
        ?>
        <div class="clearerleft"></div>
        <?php
        @list($pw) = @getimagesize($imagepath);

        display_field_data($previewcaption, true, $pw);
        }

    hook('previewextras');

    if(canSeePreviewTools($edit_access))
        {
    	if($annotate_enabled)
    		{
			include_once '../include/annotation_functions.php';
    		}
        	?>
        <!-- Available tools to manipulate previews -->
        <div id="PreviewTools" onmouseenter="showHidePreviewTools();" onmouseleave="showHidePreviewTools();">
            <script>


		

            function showHidePreviewTools()
                {
                var tools_wrapper = jQuery('#PreviewToolsOptionsWrapper');
                var tools_options = tools_wrapper.find('.ToolsOptionLink');

				// move tool bar behind image
				jQuery("canvas").css("z-index", 1); // move image above preview tools bar so that bottom of image can be annotated
				jQuery("div#PreviewTools").css("z-index", 0); // move previewtools under resource image using z-index	

                tools_wrapper.toggleClass('Hidden');

                return false;
                }

            function toggleMode(element)
                {
                jQuery(element).toggleClass('Enabled');


                }

			// resets CSS properties to indicate RS Tagging is now available
			function unsetVisualIndicator(element)
				{
				element.css('opacity', 1);
				jQuery("#previewimagecopy").css('opacity', 1);
				}


			// sets css properties to indicate RS Tagging initalisation
			function setVisualIndicator(element)
				{
				element.css('opacity', '0.2'); // transparent resource image
				
				}

            </script>
            <div id="PreviewToolsOptionsWrapper" class="Hidden">
            <?php
            if($annotate_enabled && file_exists($imagepath))
                {
                ?>
                <a class="ToolsOptionLink AnnotationsOption" href="#" onclick="toggleAnnotationsOption(this); return false;">
                    <i class='fa fa-pencil-square-o' aria-hidden="true"></i>
                </a>
                <script>
                var rs_tagging_plugin_added = false;

                function toggleAnnotationsOption(element)
                    {
                    var option             = jQuery(element);
                    var preview_image      = jQuery('#previewimage');
                    var preview_image_link = jQuery('#previewimagelink');
                    var img_copy_id        = 'previewimagecopy';
                    var img_src            = preview_image.attr('src');

					
					
                    // Setup Annotorious (has to be done only once)
                    if(!rs_tagging_plugin_added)
                        {

                        anno.addPlugin('RSTagging',
                            {
                            annotations_endpoint: '<?php echo $baseurl; ?>/pages/ajax/annotations.php',
                            nodes_endpoint      : '<?php echo $baseurl; ?>/pages/ajax/get_nodes.php',
                            resource            : <?php echo (int) $ref; ?>,
                            read_only           : <?php echo ($annotate_read_only ? 'true' : 'false'); ?>,
                            // We pass CSRF token identifier separately in order to know what to get in the Annotorious plugin file
                            csrf_identifier: '<?php echo $CSRF_token_identifier; ?>',
                            <?php echo generateAjaxToken('RSTagging'); ?>
                            });

                <?php
                if($facial_recognition)
                    {
                    ?>
                        anno.addPlugin('RSFaceRecognition',
                            {
                            annotations_endpoint: '<?php echo $baseurl; ?>/pages/ajax/annotations.php',
                            facial_recognition_endpoint: '<?php echo $baseurl; ?>/pages/ajax/facial_recognition.php',
                            resource: <?php echo (int) $ref; ?>,
                            facial_recognition_tag_field: <?php echo $facial_recognition_tag_field; ?>,
                            // We pass CSRF token identifier separately in order to know what to get in the Annotorious plugin file
                            fr_csrf_identifier: '<?php echo $CSRF_token_identifier; ?>',
                            <?php echo generateAjaxToken('RSFaceRecognition'); ?>
                            });
                    <?php
                    }
                    ?>
			 rs_tagging_plugin_added = true;
			  // We have to wait for initialisation process to finish as this does ajax calls
                        // in order to set itself up
                        setTimeout(function ()
                            {
                            toggleAnnotationsOption(element);
                            }, 
                            1500);
						return false;
                        }

                    // Feature enabled? Then disable it.
                    if(option.hasClass('Enabled'))
                        {
                        anno.destroy(preview_image.data('original'));

                        // Remove the copy and show the linked image again
                        jQuery('#' + img_copy_id).remove();
                        preview_image_link.show();

                        toggleMode(element);

                        return false;
                        }

                    // Enable feature
                    // Hide the linked image for now and use a copy of it to annotate
                    var preview_image_copy = preview_image.clone(true);
                    preview_image_copy.prop('id', img_copy_id);
                    preview_image_copy.prop('src', img_src);

                    // Set the width and height of the image otherwise if the source of the file
                    // is fetched from download.php, Annotorious will not be able to determine its
                    // size
                    var preview_image_width=preview_image.width();
                    var preview_image_height=preview_image.height();
                    preview_image_copy.width( preview_image_width );
                    preview_image_copy.height( preview_image_height );

                    preview_image_copy.appendTo(preview_image_link.parent());
                    preview_image_link.hide();

                    anno.makeAnnotatable(document.getElementById(img_copy_id));

                    toggleMode(element);

					// reset image css to indicate tagging is now available
					unsetVisualIndicator(preview_image);
                    return false;
					}
					
					<?php
                if(checkPreviewToolsOptionUniqueness('annotate_enabled'))
                    {
                    ?>
                    jQuery('#PreviewToolsOptionsWrapper').on('readyToUseAnnotorious', function ()
                        {
                        toggleAnnotationsOption(jQuery('.AnnotationsOption'));
                        });
                    <?php
                    }
					?>
					
                </script>
                <?php
                }
			
			// Swap the image with the 'lpr' size when hoverable image zooming is enabled in config. If 'lpr' size not available then use the 'scr' size.
            if($image_preview_zoom)
                {
				$pathtofile = get_resource_path($ref, true,'lpr', false, $resource['preview_extension'], -1, 1, $use_watermark);
				if (file_exists($pathtofile))
				    {
				    $previewurl = get_resource_path($ref, false,'lpr', false, $resource['preview_extension'], -1, 1, $use_watermark);
					}
					else
					{
					$previewurl = get_resource_path($ref, false,'scr', false, $resource['preview_extension'], -1, 1, $use_watermark);
					}
                ?>
                <a class="ToolsOptionLink ImagePreviewZoomOption" href="#" onclick="toggleImagePreviewZoomOption(this); return false;">
                    <i class='fa fa-search-plus' aria-hidden="true"></i>
                </a>
                <script>
                function toggleImagePreviewZoomOption(element)
                    {
                    var option = jQuery(element);

                    // Feature enabled? Then disable it.
                    if(option.hasClass('Enabled'))
                        {
                        jQuery('#previewimage').trigger('zoom.destroy');

                        toggleMode(element);

                        return false;
                        }

                    // Enable
                    jQuery('#previewimage')
                        .wrap('<span style="display: inline-block;"></span>')
                        .css('display', 'block')
                        .parent()
                        .zoom({url: '<?php echo $previewurl; ?>'});

                    toggleMode(element);

                    return false;
                    }

                <?php
                if(checkPreviewToolsOptionUniqueness('image_preview_zoom'))
                    {
                    ?>
                    jQuery(document).ready(function ()
                        {
                        showHidePreviewTools();
                        toggleImagePreviewZoomOption(jQuery('.ImagePreviewZoomOption'));
                        });
                    <?php
                    }
                    ?>
                </script>
                <?php
                }
                ?>
            </div>
        </div>
        <?php
        } /* end of canSeePreviewTools() */
        ?>
    </div>
    <?php
    }
else
    {
    ?>
    <div id="previewimagewrapper">
        <img src="<?php echo $baseurl ?>/gfx/<?php echo get_nopreview_icon($resource["resource_type"],$resource["file_extension"],false)?>" alt="" class="Picture NoPreview" style="border:none;" id="previewimage" />
    <?php
    hook('aftersearchimg', '', array($ref));
    if(isset($previewcaption))
        {
        ?>
        <div class="clearerleft"></div>
        <?php
        display_field_data($previewcaption, true);
        }

    hook("previewextras");
    ?>
    </div>
    <?php
    }
?>
<?php } /* End of renderinnerresourcepreview hook */ ?>
<?php } /* End of replacerenderinnerresourcepreview hook */ ?>
<?php

$disable_flag = (hook('disable_flag_for_renderbeforerecorddownload') || ($use_pdfjs_viewer && $resource['file_extension'] == 'pdf') );
hook("renderbeforerecorddownload", '', array($disable_flag));

?>
<?php if (!hook("renderresourcedownloadspace")) { ?>
<div class="RecordDownload" id="RecordDownload">
<div class="RecordDownloadSpace">
<?php if (!hook("renderinnerresourcedownloadspace")) { 
	hook("beforeresourcetoolsheader");
	if (!hook('replaceresourcetoolsheader')) {
?>
<h2 id="resourcetools"><?php echo $lang["resourcetools"]?></h2>

<?php
	}

# DPI calculations
function compute_dpi($width, $height, &$dpi, &$dpi_unit, &$dpi_w, &$dpi_h)
	{
	global $lang, $imperial_measurements,$sizes,$n,$view_default_dpi;
	
    if (isset($sizes[$n]['resolution']) && $sizes[$n]['resolution']!=0 && is_int($sizes[$n]['resolution']))
        {
        $dpi=$sizes[$n]['resolution'];
        }
    else if (!isset($dpi) || $dpi==0)
        {
        $dpi=$view_default_dpi;
        }

	if (((isset($sizes[$n]['unit']) && trim(strtolower($sizes[$n]['unit']))=="inches")) || $imperial_measurements)
		{
		# Imperial measurements
		$dpi_unit=$lang["inch-short"];
		$dpi_w=round($width/$dpi,1);
		$dpi_h=round($height/$dpi,1);
		}
	else
		{
		$dpi_unit=$lang["centimetre-short"];
		$dpi_w=round(($width/$dpi)*2.54,1);
		$dpi_h=round(($height/$dpi)*2.54,1);
		}
	}

# MP calculation
function compute_megapixel($width, $height)
	{
	return round(($width * $height) / 1000000, 2);
	}

function get_size_info($size, $originalSize = null)
	{
    global $lang, $ffmpeg_supported_extensions;
    
    $newWidth  = intval($size['width']);
    $newHeight = intval($size['height']);

    if ($originalSize != null && $size !== $originalSize)
        {
        // Compute actual pixel size
        $imageWidth  = $originalSize['width'];
        $imageHeight = $originalSize['height'];
        if ($imageWidth > $imageHeight)
            {
            // landscape
            if ($imageWidth == 0) return '<p>&ndash;</p>';
            $newWidth = $size['width'];
            $newHeight = round(($imageHeight * $newWidth + $imageWidth - 1) / $imageWidth);
            }
        else
            {
            // portrait or square
            if ($imageHeight == 0) return '<p>&ndash;</p>';
            $newHeight = $size['height'];
            $newWidth = round(($imageWidth * $newHeight + $imageHeight - 1) / $imageHeight);
            }
        }

    $output = "<p>$newWidth &times; $newHeight {$lang["pixels"]}";

    if (!hook('replacemp'))
        {
        $mp = compute_megapixel($newWidth, $newHeight);
        if ($mp >= 0)
            {
            $output .= " ($mp {$lang["megapixel-short"]})";
            }
        }

    $output .= '</p>';

    if (!isset($size['extension']) || !in_array(strtolower($size['extension']), $ffmpeg_supported_extensions))
        {
        if (!hook("replacedpi"))
            {   
            # Do DPI calculation only for non-videos
            compute_dpi($newWidth, $newHeight, $dpi, $dpi_unit, $dpi_w, $dpi_h);
            $output .= "<p>$dpi_w $dpi_unit &times; $dpi_h $dpi_unit {$lang["at-resolution"]} $dpi {$lang["ppi"]}</p>";
            }
        }

    return $output;
}

# Get display price for basket request modes
function get_display_price($ref, $size)
{
	global $pricing, $currency_symbol;

	$price_id=$size["id"];
	if ($price_id=="") { $price_id="hpr"; }

	$price=999; # If price cannot be found
	if (array_key_exists($price_id,$pricing)) { $price=$pricing[$price_id]; }

	# Pricing adjustment hook (for discounts or other price adjustments plugin).
	$priceadjust=hook("adjust_item_price","",array($price,$ref,$size["id"]));
	if ($priceadjust!==false) { $price=$priceadjust; }

	return $currency_symbol . " " . number_format($price,2);
}

function make_download_preview_link($ref, $size, $label)
	{
	global $direct_link_previews_filestore, $baseurl;

	if ($direct_link_previews_filestore)
		$direct_link="" . get_resource_path($ref,false,$size['id'],false,$size['extension']);
	else
		$direct_link=$baseurl."/pages/download.php?direct=1&amp;ref=$ref&amp;size=" . $size['id'] . "&amp;ext=" . $size['extension'];

	return "<a href='$direct_link' target='dl_window_$ref'>$label</a>";
	}

function add_download_column($ref, $size_info, $downloadthissize)
	{
    global $save_as, $terms_download, $order_by, $lang, $baseurl, $k, $search, $request_adds_to_collection, $offset, $archive, $sort, $internal_share_access, $urlparams, $resource, $iOS_save,$download_usage;
	if ($downloadthissize)
		{
		?>
        <td class="DownloadButton">
        <?php
		global $size_info_array;
		$size_info_array = $size_info;
		if(!hook("downloadbuttonreplace"))
			{
			if($terms_download || $save_as)
				{
				?><a id="downloadlink" <?php
				if (!hook("downloadlink","",array("ref=" . $ref . "&k=" . $k . "&size=" . $size_info["id"]
							. "&ext=" . $size_info["extension"])))
						{
						echo "href=\"" . generateURL($baseurl . "/pages/terms.php",$urlparams,array("url"=> generateURL($baseurl . "/pages/download_progress.php",$urlparams,array("size"=>$size_info["id"],"ext"=> $size_info["extension"])))) . "\"";
						}
						if($iOS_save)
							{
							echo " target=\"_blank\"";
							}
						else
							{
							echo " onClick=\"return CentralSpaceLoad(this,true);\"";
							}
						?>><?php echo $lang["action-download"]?></a><?php
					
				}
			elseif ($download_usage)
				// download usage form displayed - load into main window
				{ ?>
							
				<a id="downloadlink" href="<?php
					echo $baseurl ?>/pages/download_usage.php?ref=<?php echo urlencode($ref) ?>&size=<?php
					echo $size_info['id']?>&ext=<?php echo $size_info['extension']?>&k=<?php
					echo urlencode($k)?>"><?php echo $lang["action-download"]?></a>							
				<?php 
				}
			else
				{
				?><a id="downloadlink" href="#" onclick="directDownload('<?php
						echo $baseurl ?>/pages/download_progress.php?ref=<?php echo urlencode($ref) ?>&size=<?php
						echo $size_info['id']?>&ext=<?php echo $size_info['extension']?>&k=<?php
						echo urlencode($k)?>')"><?php echo $lang["action-download"]?></a><?php
				}
				unset($size_info_array);
				?>
			</td>
			<?php
			}
		}
	else if (checkperm("q"))
		{
		if (!hook("resourcerequest"))
			{
			?><td class="DownloadButton"><?php
			if ($request_adds_to_collection && ($k=="" || $internal_share_access) && !checkperm('b')) // We can't add to a collection if we are accessing an external share, unless we are a logged in user
				{
				if (isset($size_info["id"])) 
					{
					echo add_to_collection_link($ref,$search,"alert('" . addslashes($lang["requestaddedtocollection"]) . "');",$size_info["id"]);
					}
				else
					{
					echo add_to_collection_link($ref,$search);
					}
				}
			else
				{
				?><a href="<?php echo generateURL($baseurl . "/pages/resource_request.php",$urlparams) ?>" onClick="return CentralSpaceLoad(this,true);"><?php
				}
			echo $lang["action-request"]?></a></td><?php
			}
		}
	else
		{
		# No access to this size, and the request functionality has been disabled. Show just 'restricted'.
		?><td class="DownloadButton DownloadDisabled"><?php echo $lang["access1"]?></td><?php
		}
	}


# Look for a viewer to handle the right hand panel. If not, display the standard photo download / file download boxes.
if (file_exists("../viewers/type" . $resource["resource_type"] . ".php"))
	{
	include "../viewers/type" . $resource["resource_type"] . ".php";
	}
elseif (hook("replacedownloadoptions"))
	{
	}
elseif ($is_template)
	{
	
	}
else
	{ 
	?>
<table cellpadding="0" cellspacing="0" id="ResourceDownloadOptions">
<tr <?php hook("downloadtableheaderattributes")?>>
<?php
$table_headers_drawn=false;
$nodownloads=false;$counter=0;$fulldownload=false;
$showprice=$userrequestmode==2 || $userrequestmode==3;
hook("additionalresourcetools");
if ($resource["has_image"]==1 && $download_multisize)
	{
	# Restricted access? Show the request link.

	# List all sizes and allow the user to download them
	$sizes=get_image_sizes($ref,false,$resource["file_extension"]);
	for ($n=0;$n<count($sizes);$n++)
		{
		# Is this the original file? Set that the user can download the original file
		# so the request box does not appear.
		$fulldownload=false;
		if ($sizes[$n]["id"]=="") {$fulldownload=true;}
		
		$counter++;

		# Should we allow this download?
		# If the download is allowed, show a download button, otherwise show a request button.
		$downloadthissize=resource_download_allowed($ref,$sizes[$n]["id"],$resource["resource_type"]);

		$headline=$sizes[$n]['id']=='' ? str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["originalfileoftype"])
				: $sizes[$n]["name"];
		$newHeadline=hook('replacesizelabel', '', array($ref, $resource, $sizes[$n]));
		if (!empty($newHeadline))
			$headline=$newHeadline;

		if ($direct_link_previews && $downloadthissize)
			$headline=make_download_preview_link($ref, $sizes[$n],$headline);
		if ($hide_restricted_download_sizes && !$downloadthissize && !checkperm("q"))
			continue;
		if(!hook("replacedownloadspacetableheaders")){
			if ($table_headers_drawn==false) { ?>
				<td><?php echo $lang["fileinformation"]?></td>
				<?php echo $use_larger_layout ? "<td>" . $lang["filedimensions"] . "</td>" : ''; ?>
				<td><?php echo $lang["filesize"]?></td>
				<?php if ($showprice) { ?><td><?php echo $lang["price"] ?></td><?php } ?>
				<td class="textcenter"><?php echo $lang["options"]?></td>
				</tr>
 				<?php
				$table_headers_drawn=true;
			} 
		} # end hook("replacedownloadspacetableheaders")?>
		<tr class="DownloadDBlend" id="DownloadBox<?php echo $n?>">
		<td class="DownloadFileName"><h2><?php echo $headline?></h2><?php

		echo $use_larger_layout ? '</td><td class="DownloadFileDimensions">' : '';

		if (is_numeric($sizes[$n]["width"]))
			{
			echo get_size_info($sizes[$n]);
			}
		?></td><td class="DownloadFileSize"><?php echo $sizes[$n]["filesize"]?></td>

		<?php if ($showprice) {
			?><td><?php echo get_display_price($ref, $sizes[$n]) ?></td>
		<?php } ?>

		<?php

		add_download_column($ref, $sizes[$n], $downloadthissize);
		?>
		</tr>
		<?php
		if (!hook("previewlinkbar")){
			if ($downloadthissize && $sizes[$n]["allow_preview"]==1)
				{ 
				# Add an extra line for previewing
				?> 
				<tr class="DownloadDBlend"><td class="DownloadFileName"><h2><?php echo $lang["preview"]?></h2><?php echo $use_larger_layout ? '</td><td class="DownloadFileDimensions">' : '';?><p><?php echo $lang["fullscreenpreview"]?></p></td><td class="DownloadFileSize"><?php echo $sizes[$n]["filesize"]?></td>
				<?php if ($userrequestmode==2 || $userrequestmode==3) { ?><td></td><?php } # Blank spacer column if displaying a price above (basket mode).
				?>
				<td class="DownloadButton">
				<a class="enterLink" id="previewlink" href="<?php echo generateURL($baseurl . "/pages/preview.php",$urlparams,array("ext"=>$resource["file_extension"])) . "&" . hook("previewextraurl") ?>"><?php echo $lang["action-view"]?></a>
				</td>
				</tr>
				<?php
				} 
			}
		} /* end hook previewlinkbar */
	}
elseif (strlen($resource["file_extension"])>0 && !($access==1 && $restricted_full_download==false))
	{
	# Files without multiple download sizes (i.e. no alternative previews generated).
	$path=get_resource_path($ref,true,"",false,$resource["file_extension"]);
	if (file_exists($path))
		{
		$counter++;
		hook("beforesingledownloadsizeresult");
		if(!hook("origdownloadlink"))
		{
		?>
		<tr class="DownloadDBlend">
		<td class="DownloadFileName" <?php echo $use_larger_layout ? ' colspan="2"' : ''; ?>><h2><?php echo (isset($original_download_name)) ? str_replace_formatted_placeholder("%extension", $resource["file_extension"], $original_download_name, true) : str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["originalfileoftype"]); ?></h2></td>
		<td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($path))?></td>
		
            <?php
                $size_info = array('id' => '', 'extension' => $resource['file_extension']);
                $downloadthissize=resource_download_allowed($ref,'',$resource["resource_type"]);
                add_download_column($ref,$size_info, $downloadthissize);
            ?>
			</td>
			</tr>
			<?php
            // add link to mp3 preview file if resource is a wav file
            render_audio_download_link($resource, $ref, $k, $ffmpeg_audio_extensions, $baseurl, $lang, $use_larger_layout);

			}
        }
	else
		{
		$nodownloads=true;
		}
	} 
elseif (strlen($resource["file_extension"])>0 && ($access==1 && $restricted_full_download==false))
    {
    # Files without multiple download sizes (i.e. no alternative previews generated).
    $path=get_resource_path($ref,true,"",false,$resource["file_extension"]);
    $downloadthissize=resource_download_allowed($ref,"",$resource["resource_type"]);
    if (file_exists($path))
        {
        $counter++;
        hook("beforesingledownloadsizeresult");
        if(!hook("origdownloadlink"))
            {
            ?>
            <tr class="DownloadDBlend">
            <td class="DownloadFileName"><h2><?php echo (isset($original_download_name)) ? str_replace_formatted_placeholder("%extension", $resource["file_extension"], $original_download_name, true) : str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["originalfileoftype"]); ?></h2></td>
            <td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($path))?></td>
            <?php
            $size_info = array('id' => '', 'extension' => $resource['file_extension']);
            add_download_column($ref, $size_info, $downloadthissize);
            ?>
            </tr>
            <?php # hook origdownloadlink
            }
        }
    else
        {
        $nodownloads=true;
        }
    } 

if(($nodownloads || $counter == 0) && !checkperm('T' . $resource['resource_type'] . '_'))
	{
	hook('beforenodownloadresult');

    $generate_data_only_pdf_file = false;
    $download_file_name          = (0 == $counter) ? $lang['offlineresource'] : $lang['access1'];

    if(in_array($resource['resource_type'], $data_only_resource_types) && array_key_exists($resource['resource_type'], $pdf_resource_type_templates))
        {
        $download_file_name          = get_resource_type_name($resource['resource_type']);
        $generate_data_only_pdf_file = true;
        }
	?>
	<tr class="DownloadDBlend">
	<td class="DownloadFileName"><h2><?php echo $download_file_name; ?></h2></td>
	<td class="DownloadFileSize"><?php echo $lang["notavailableshort"]?></td>

	<?php
    if($generate_data_only_pdf_file)
        {
        $generate_data_only_url_params = array(
            'ref'             => $ref,
            'download'        => 'true',
            'data_only'       => 'true',
            'k'               => $k
        );
        ?>
        <td <?php hook("modifydownloadbutton") ?> class="DownloadButton">
            <a href="<?php echo generateURL($baseurl . '/pages/metadata_download.php', $generate_data_only_url_params); ?>"><?php echo $lang['action-generate_pdf']; ?></a>
        </td>
        <?php
        }
    // No file. Link to request form.
	else if(checkperm('q'))
		{
		if(!hook('resourcerequest'))
            {
            ?>
            <td <?php hook("modifydownloadbutton") ?> class="DownloadButton">
                <a href="<?php echo generateURL($baseurl . "/pages/resource_request.php",$urlparams); ?>"  onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["action-request"]?></a>
            </td>
            <?php
            }
		}
    else
		{
		?>
		<td <?php hook("modifydownloadbutton") ?> class="DownloadButton DownloadDisabled"><?php echo $lang["access1"]?></td>
		<?php
		}
        ?>
	</tr>
	<?php
	}
	
if ($flv_preview_downloadable && isset($video_preview_file) && file_exists($video_preview_file) && resource_download_allowed($ref,"pre",$resource["resource_type"]))
    {
	# Allow the video preview to be downloaded.
	?>
	<tr class="DownloadDBlend">
	<td class="DownloadFileName" colspan="2"><h2><?php echo (isset($ffmpeg_preview_download_name)) ? $ffmpeg_preview_download_name : str_replace_formatted_placeholder("%extension", $ffmpeg_preview_extension, $lang["cell-fileoftype"]); ?></h2></td>
	<td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($video_preview_file))?></td>
	<td <?php hook("modifydownloadbutton") ?> class="DownloadButton">
	<?php if ($terms_download || $save_as){?>
		<a href="<?php echo generateURL($baseurl . "/pages/terms.php",$urlparams,array("url"=>generateURL($baseurl . "/pages/download_progress.php",$urlparams,array("ext"=>$ffmpeg_preview_extension,"size"=>"pre")))) ?>"  onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["action-download"] ?></a>
	<?php } 
	
	elseif ($download_usage)
	// download usage form displayed - load into main window
		{ ?>
			<a href="<?php echo $baseurl ?>/pages/download_progress.php?ref=<?php echo urlencode($ref)?>&ext=<?php echo $ffmpeg_preview_extension?>&size=pre&k=<?php echo urlencode($k) ?>"><?php echo $lang["action-download"]?></a>				
		<?php
		}
	else { ?>
		<a href="#" onclick="directDownload('<?php echo $baseurl ?>/pages/download_progress.php?ref=<?php echo urlencode($ref)?>&ext=<?php echo $ffmpeg_preview_extension?>&size=pre&k=<?php echo urlencode($k) ?>')"><?php echo $lang["action-download"]?></a>
	<?php } ?></td>
	</tr>
	<?php
	}

hook('additionalresourcetools2', '', array($resource, $access));
	
include "view_alternative_files.php";

?>



</table>

<?php
hook("additionalresourcetools3");
 } 
if(!hook("replaceactionslistopen")){?>
<ul id="ResourceToolsContainer">
<?php
} # end hook("replaceactionslistopen")

# ----------------------------- Resource Actions -------------------------------------
hook ("resourceactions") ?>
<?php if ($k=="" || $internal_share_access) { ?>
<?php if (!hook("replaceresourceactions")) {
	hook("resourceactionstitle");
	 if ($resource_contact_link)	
	 	{ ?>
		<li>
		<a href="<?php echo $baseurl ?>/pages/ajax/contactadmin.php?ref=<?php echo urlencode($ref)?>&amp;search=<?php echo urlencode($search)?>&amp;offset=<?php echo urlencode($offset)?>&amp;order_by=<?php echo urlencode($order_by)?>&amp;sort=<?php echo urlencode($sort)?>&amp;archive=<?php echo urlencode($archive)?>" onClick="showContactBox();return false;" >
		<?php echo "<i class='fa fa-fw fa-user'></i>&nbsp;" . $lang["contactadmin"]?>
		</a>
		</li>
		<?php 
		}
	if (!hook("replaceaddtocollection") && !checkperm("b")
			&& !(($userrequestmode==2 || $userrequestmode==3) && $basket_stores_size)
			&& !in_array($resource["resource_type"],$collection_block_restypes)) 
		{ 
		?>
		<li>
			<?php 
			echo add_to_collection_link($ref,$search);
			echo "<i class='fa fa-fw fa-plus-circle'></i>&nbsp;" .$lang["action-addtocollection"];
			?>
			</a>
		</li>
		<?php 
		if ($search=="!collection" . $usercollection) 
			{ 
			?>
			<li>
			<?php 
			echo remove_from_collection_link($ref,$search);
			echo "<i class='fa fa-fw fa-minus-circle'></i>&nbsp;" .$lang["action-removefromcollection"]?>
			</a>
			</li>
			<?php 
			}
		} 
	if (can_share_resource($ref,$access) && !$hide_resource_share_link) 
		{ 
		?>
		<li><a href="<?php echo generateurl($baseurl . "/pages/resource_share.php",$urlparams);?>" onclick="return ModalLoad(this, true);">
		<?php echo "<i class='fa fa-fw fa-share-alt'></i>&nbsp;" . $lang["share"];?>
		</a></li>
		<?php 
		hook('aftersharelink', '', array($ref, $search, $offset, $order_by, $sort, $archive));
		}
	if ($edit_access) 
		{
        echo "<li>";
        if($resource_locked && $resource['lock_user'] != $userref)
            {
            echo "<div class='DisabledLink LockedResourceAction'><i class='fa fa-fw fa-pencil'></i>&nbsp;" . $lang["action-editmetadata"] . "</div>";
            }
        else
            {
            echo "<a id='edit_link_" . $ref . "' href='" . generateURL($baseurl . "/pages/edit.php", $urlparams) . "' class='LockedResourceAction' onclick='return ModalLoad(this, true);' ><i class='fa fa-fw fa-pencil'></i>&nbsp;" . $lang["action-editmetadata"] . "</a>";
            }
        echo "</li>";
		if ((!checkperm("D") || hook('check_single_delete')) && !(isset($allow_resource_deletion) && !$allow_resource_deletion))
			{
            $deletetext = (isset($resource_deletion_state) && $resource["archive"] == $resource_deletion_state) ? $lang["action-delete_permanently"] : $lang["action-delete"];
            echo "<li>";
            if($resource_locked && $resource['lock_user'] != $userref)
                {
                echo "<div class='DisabledLink LockedResourceAction'><i class='fa fa-fw fa-trash'></i>&nbsp;" . $deletetext . "</div>";
                }
            else
                {
                echo "<a id='delete_link_" . $ref . "' href='" . generateURL($baseurl . "/pages/delete.php", $urlparams) . "' class='LockedResourceAction' onclick='return ModalLoad(this, true);' ><i class='fa fa-fw fa-trash'></i>&nbsp;" . $deletetext . "</a>";
                }
            echo "</li>";
            }
		if (!$disable_alternative_files && !checkperm('A')) 
			{ 
            echo "<li>";
            if($resource_locked && $resource['lock_user'] != $userref)
                {
                echo "<div class='DisabledLink LockedResourceAction'><i class='fa fa-fw fa-files-o'></i>&nbsp;" . $lang["managealternativefiles"] . "</div>";
                }
            else
                {
                echo "<a id='alternative_link_" . $ref . "' href='" . generateURL($baseurl . "/pages/alternative_files.php", $urlparams) . "' class='LockedResourceAction' onclick='return ModalLoad(this, true);' ><i class='fa fa-fw fa-files-o'></i>&nbsp;" . $lang["managealternativefiles"] . "</a>";
                }
            echo "</li>";
            }

        // Show the lock/unlock links only if edit access
        render_resource_lock_link($ref,$resource['lock_user'], true);

        // Show the replace file link
        if($top_nav_upload_type == 'local')
            {
            $replace_upload_type = 'plupload';
            }
        else 
            {
            $replace_upload_type=$top_nav_upload_type;
            }

        if (!(in_array($resource['resource_type'], $data_only_resource_types)) && !resource_file_readonly($ref) && (checkperm("c") || checkperm("d")))
            { ?>
            <li>
                <a href="<?php echo generateURL($baseurl_short . "pages/upload_" . $replace_upload_type . ".php", $urlparams, array("replace_resource"=>$ref, "resource_type"=>$resource['resource_type'])); ?>" onClick="return ModalLoad(this,true);">
                    <?php if ($resource["file_extension"] != "")
                        { ?>
                        <i class='fa fa-fw fa-file-import'></i>&nbsp;<?php echo $lang["replacefile"];
                        }
                    else
                        { ?>
                        <i class='fa fa-fw fa-upload'></i>&nbsp;<?php echo $lang["uploadafile"];
                        }
                    ?>
                </a>
            </li>
            <?php
            }

         if ($resource["file_extension"]!="") 
            {
            hook("afterreplacefile");
            } 
         else 
            {
            hook("afteruploadfile");
            }

        // Show the upload preview link
        if (!$disable_upload_preview && !resource_file_readonly($ref))
            { ?>
            <li>
                <a href="<?php echo generateURL($baseurl_short . "pages/upload_preview.php",$urlparams); ?>" onClick="return ModalLoad(this,true);">
                    <i class='fa fa-fw fa-upload'></i>&nbsp;<?php echo $lang["uploadpreview"]?>
                </a>
            </li>
            <?php
            }
		} 
	// At least one field should be visible to the user otherwise it makes no sense in using this feature
	$can_see_fields_individually = false;
	foreach ($fields as $field => $field_option_value) 
		{
		if(metadata_field_view_access($field_option_value['ref'])) 
			{
			$can_see_fields_individually = true;
			break;
			}
		}
	if ($metadata_download && (checkperm('f*') || $can_see_fields_individually))	
		{ ?>
		<li><a href="<?php echo generateurl($baseurl . "/pages/metadata_download.php",$urlparams);?>" onclick="return ModalLoad(this, true);">
		<?php echo "<i class='fa fa-fw fa-history'></i>&nbsp;" .$lang["downloadmetadata"]?>
		</a></li><?php 
		} 
	if (checkperm('v')) 
		{ ?>
		<li><a href="<?php echo generateurl($baseurl . "/pages/log.php",$urlparams);?>" onclick="return ModalLoad(this, true);">
		<?php echo "<i class='fa fa-fw fa-history'></i>&nbsp;" .$lang["log"]?>
		</a></li><?php 
		}
	if (checkperm("R") && $display_request_log_link) 
		{ ?>
		<li><a href="<?php echo generateurl($baseurl . "/pages/request_log.php",$urlparams);?>" onclick="return ModalLoad(this, true);">
		<?php echo "<i class='fa fa-fw fa-history'></i>&nbsp;" .$lang["requestlog"]?>
		</a></li><?php 
		}

    if($resource['file_extension'] == 'pdf' && $use_pdfjs_viewer && $access == 0)
        {
        $find_in_pdf_url = generateURL("{$baseurl}/pages/search_text_in_pdf.php", array( 'ref' => $ref));
        ?>
        <li>
            <a href="<?php echo $find_in_pdf_url; ?>" onclick="return ModalLoad(this, true, true);"><i class='fa fa-fw fa-search'></i>&nbsp;<?php echo $lang['findtextinpdf']; ?></a>
        </li>
        <?php 
        }
    } /* End replaceresourceactions */ 
hook("afterresourceactions");
hook("afterresourceactions2");
?>
<?php } /* End if ($k!="")*/ 
hook("resourceactions_anonymous");
?>
<?php } /* End of renderinnerresourcedownloadspace hook */ 
if(!hook('replaceactionslistclose')){
?>
</ul>
</div>
<?php } # end hook('replaceactionslistclose') ?>
<div class="clearerleft"> </div>

<?php
if (!hook("replaceuserratingsbox")){
# Include user rating box, if enabled and the user is not external.
if ($user_rating && ($k=="" || $internal_share_access)) { include "../include/user_rating.php"; }
} /* end hook replaceuserratingsbox */


?>


</div>
<?php } /* End of renderresourcedownloadspace hook */ ?>
<?php } /* End of renderinnerresourceview hook */

if ($download_summary) {include "../include/download_summary.php";}

hook("renderbeforeresourcedetails");


/* ---------------  Display metadata ----------------- */
if (!hook('replacemetadata')) {
?>
<div id="Panel1" class="ViewPanel">
    <div id="Titles1" class="ViewPanelTitles">
        <div class="Title Selected" panel="Metadata"><?php if (!hook("customdetailstitle")) echo $lang["resourcedetails"]?></div>
    </div>
</div>
<?php include "view_metadata.php";
} /* End of replacemetadata hook */ ?>
</div>

</div>

</div>

<?php
/*
 ----------------------------------
 Show "pushed" metadata - from related resources with push_metadata set on the resource type. Metadata for those resources
 appears here in the same style.
 
 */
$pushed=do_search("!relatedpushed" . $ref);
foreach ($pushed as $pushed_resource)
	{
	RenderPushedMetadata($pushed_resource);
	}

function RenderPushedMetadata($resource)
	{
	global $k,$view_title_field,$lang, $internal_share_access, $fields_all;
	$ref=$resource["ref"];
	$fields=get_resource_field_data($ref,false,!hook("customgetresourceperms"),NULL,($k!="" && !$internal_share_access),false);
	$access=get_resource_access($ref);
	?>
	<div class="RecordBox">
        <div class="RecordPanel">  <div class="backtoresults">&gt; <a href="view.php?ref=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["view"] ?></a></div>
        <div class="Title"><?php echo $resource["resource_type_name"] . " : " . $resource["field" . $view_title_field] ?></div>
        <?php include "view_metadata.php"; ?>
        </div>
        </div>
	<?php
	}
/*
End of pushed metadata support
------------------------------------------
*/ 
?>

<?php if ($view_panels) { ?>
<div class="RecordBox">
    <div class="RecordPanel">  
        <div id="Panel2" class="ViewPanel">
            <div id="Titles2" class="ViewPanelTitles"></div>
        </div>
    </div>
    
</div>
<?php if ($view_resource_collections){
	# only render this box when needed
?>
<div class="RecordBox">
    <div class="RecordPanel">  
        <div id="Panel3" class="ViewPanel">
            <div id="Titles3" class="ViewPanelTitles"></div>
        </div>
    </div>
    
</div>
<?php } ?>
<?php } 

// juggle $resource at this point as an unknown issue with render_actions used within a hook causes this variable to be reset
$resourcedata=$resource;?>
<?php hook("custompanels");//For custom panels immediately below resource display area 
$resource=$resourcedata;?>




<?php 
if (!$disable_geocoding) { 
  // only show this section if the resource is geocoded OR they have permission to do it themselves
  if ($edit_access||($resource["geo_lat"]!="" && $resource["geo_long"]!=""))
  		{
		include "../include/geocoding_view.php";
	  	} 
 	} 
?>

<?php 
	if ($comments_resource_enable && ($k=="" || $internal_share_access)) include_once ("../include/comment_resources.php");
?>
	  	  
<?php hook("w2pspawn");?>

<?php 
// include collections listing
if ($view_resource_collections && !checkperm('b')){ ?>
	<div id="resourcecollections"></div>
	<script type="text/javascript">
	jQuery("#resourcecollections").load('<?php echo $baseurl ?>/pages/resource_collection_list.php?ref=<?php echo urlencode($ref)?>&k=<?php echo urlencode($k)?>'
	<?php
	if ($view_panels) {
	?>
    	, function() {
    	
    	jQuery("#AssociatedCollections").children(".Title").attr("panel", "AssociatedCollections").addClass("Selected").appendTo("#Titles3");
    	removePanel=jQuery("#AssociatedCollections").parents(".RecordBox");
    	jQuery("#AssociatedCollections").appendTo("#Panel3").addClass("TabPanel");
    	removePanel.remove();
    	
    	jQuery("#CollectionsThemes").children().children(".Title").attr("panel", "CollectionsThemes").appendTo("#Titles3");
    	removePanel=jQuery("#CollectionsThemes").parents(".RecordBox");
    	jQuery("#CollectionsThemes").appendTo("#Panel3").addClass("TabPanel").hide();
    	removePanel.remove();
    	if (jQuery("#Titles2").children().length==0) jQuery("#Panel2").parent().parent().remove();
	if (jQuery("#Titles3").children().length==0) jQuery("#Panel3").parent().parent().remove();	
        jQuery(".ViewPanelTitles").children(".Title").click(function(){
        // function to switch tab panels
            jQuery(this).parent().parent().children(".TabPanel").hide();
            jQuery(this).parent().children(".Title").removeClass("Selected");
            jQuery(this).addClass("Selected");
            jQuery("#"+jQuery(this).attr("panel")).show();
        });
    	}
	<?php
	}
	?>); 
	</script>
	<?php }

if ($metadata_report && isset($exiftool_path) && ($k=="" || $internal_share_access))
    {
    ?>
    <div class="RecordBox">
        <div class="RecordPanel">  
            <h3 class="CollapsibleSectionHead collapsed"><?php echo $lang['metadata-report']; ?></h3>
            <div id="<?php echo $context; ?>MetadataReportSection" class="CollapsibleSection"></div>
            <script>
            jQuery("#<?php echo $context; ?>MetadataReportSection").on("ToggleCollapsibleSection", function(e, data)
                {
                if(data.state == "collapsed")
                    {
                    return false;
                    }

                // if report has already been generated, just show it
                if(jQuery.trim(jQuery(this).html()).length > 0)
                    {
                    return true;
                    }

                CentralSpaceShowLoading();
                metadataReport(<?php echo htmlspecialchars($ref); ?>, '<?php echo htmlspecialchars($context); ?>');

                return true;
                });
            </script>
        </div>
    </div>
    <?php
    }

hook("customrelations"); //For future template/spawned relations in Web to Print plugin

# -------- Related Resources (must be able to search for this to work)
if($enable_related_resources && !isset($relatedresources))
    {
    // $relatedresources should be defined when using tabs in related_resources.php otherwise we need to do it here
    $relatedresources = do_search("!related{$ref}");

    $related_restypes = array();
    for($n = 0; $n < count($relatedresources); $n++)
        {
        $related_restypes[] = $relatedresources[$n]['resource_type'];
        }
    $related_restypes = array_unique($related_restypes);

    $relatedtypes_shown = array();
    $related_resources_shown = 0;
    }
if(
    isset($relatedresources)
    && (count($relatedresources) > $related_resources_shown)
    && checkperm("s")
    && ($k == "" || $internal_share_access)
)
    {
$result=$relatedresources;
if (count($result)>0) 
	{
	# -------- Related Resources by File Extension
	if($sort_relations_by_filetype){	
		#build array of related resources' file extensions
		for ($n=0;$n<count($result);$n++){
			$related_file_extension=$result[$n]["file_extension"];
			$related_file_extensions[]=$related_file_extension;
			}
		#reduce extensions array to unique values
		$related_file_extensions=array_unique($related_file_extensions);
		$count_extensions=0;
		foreach($related_file_extensions as $rext){
		?><!--Panel for related resources-->
		<div class="RecordBox">
		<div class="RecordPanel">  
         <div id="RelatedResources">
		<div class="RecordResouce">
		<div class="Title"><?php echo str_replace_formatted_placeholder("%extension", $rext, $lang["relatedresources-filename_extension"]); ?></div>
		<?php
		# loop and display the results by file extension
		for ($n=0;$n<count($result);$n++)			
			{
			if(in_array($result[$n]["resource_type"],$relatedtypes_shown))
				{
				// Don't show this type again.
				continue;
				}			
			if ($result[$n]["file_extension"]==$rext){
				$rref=$result[$n]["ref"];
				$title=$result[$n]["field".$view_title_field];
				$access=get_resource_access($rref);
				$use_watermark=check_use_watermark();
				# swap title fields if necessary

				if (isset($metadata_template_title_field) && isset($metadata_template_resource_type))
					{
					if ($result[$n]['resource_type']==$metadata_template_resource_type)
						{
						$title=$result[$n]["field".$metadata_template_title_field];
						}	
					}	
						
				?>
				
				<!--Resource Panel-->
				<div class="CollectionPanelShell">
				<table border="0" class="CollectionResourceAlign"><tr><td>
				<a href="<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $rref?>&search=<?php echo urlencode("!related" . $ref)?>" onClick="return CentralSpaceLoad(this,true);"><?php if ($result[$n]["has_image"]==1) { ?><img border=0 src="<?php echo get_resource_path($rref,false,"col",false,$result[$n]["preview_extension"],-1,1,$use_watermark,$result[$n]["file_modified"])?>" class="CollectImageBorder"/><?php } else { ?><img border=0 src="../gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],true)?>"/><?php } ?></a></td>
				</tr></table>
				<div class="CollectionPanelInfo"><a href="<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $rref?>" onClick="return CentralSpaceLoad(this,true);"><?php echo tidy_trim(i18n_get_translated($title),$related_resources_title_trim)?></a>&nbsp;</div>
				<?php hook("relatedresourceaddlink");?>
				</div>
				<?php		
				}
			}
		?>
		<div class="clearerleft"> </div>
		<?php $count_extensions++; if ($count_extensions==count($related_file_extensions)){?><a href="<?php echo $baseurl ?>/pages/search.php?search=<?php echo urlencode("!related" . $ref) ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["clicktoviewasresultset"]?></a><?php }?>
		</div>
		</div>
		</div>
		
		</div><?php
		} #end of display loop by resource extension
	} #end of IF sorted relations
	
	elseif($sort_relations_by_restype){	
		$count_restypes=0;
		foreach($related_restypes as $rtype){
			if(in_array($rtype,$relatedtypes_shown))
				{
				// Don't show this type again.
				continue;
				}
        $restypename=sql_value("select name as value from resource_type where ref = '" . escape_check($rtype) . "'","", "schema");
		$restypename = lang_or_i18n_get_translated($restypename, "resourcetype-", "-2");
		?><!--Panel for related resources-->
		<div class="RecordBox">
		<div class="RecordPanel">  
         <div id="RelatedResources">
		<div class="RecordResouce">
		<div class="Title"><?php echo str_replace_formatted_placeholder("%restype%", $restypename, $lang["relatedresources-restype"]); ?></div>
		<?php
		# loop and display the results by file extension
		for ($n=0;$n<count($result);$n++)			
			{	
			if ($result[$n]["resource_type"]==$rtype){
				$rref=$result[$n]["ref"];
				$title=$result[$n]["field".$view_title_field];
				$access=get_resource_access($rref);
				$use_watermark=check_use_watermark();
				# swap title fields if necessary

				if (isset($metadata_template_title_field) && isset($metadata_template_resource_type))
					{
					if ($result[$n]['resource_type']==$metadata_template_resource_type)
						{
						$title=$result[$n]["field".$metadata_template_title_field];
						}	
					}	
						
				?>
				
				<!--Resource Panel-->
				<div class="CollectionPanelShell">
				<table border="0" class="CollectionResourceAlign"><tr><td>
				<a href="<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $rref?>&search=<?php echo urlencode("!related" . $ref)?>" onClick="return CentralSpaceLoad(this,true);"><?php if ($result[$n]["has_image"]==1) { ?><img border=0 src="<?php echo get_resource_path($rref,false,"col",false,$result[$n]["preview_extension"],-1,1,$use_watermark,$result[$n]["file_modified"])?>" class="CollectImageBorder"/><?php } else { ?><img border=0 src="../gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],true)?>"/><?php } ?></a></td>
				</tr></table>
				<div class="CollectionPanelInfo"><a href="<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $rref?>" onClick="return CentralSpaceLoad(this,true);"><?php echo tidy_trim(i18n_get_translated($title),$related_resources_title_trim)?></a>&nbsp;</div>
				<?php hook("relatedresourceaddlink");?>
				</div>
				<?php		
				}
			}
		?>
		<div class="clearerleft"> </div>
		<?php $count_restypes++; if ($count_restypes==count($related_restypes)){?><a href="<?php echo $baseurl ?>/pages/search.php?search=<?php echo urlencode("!related" . $ref) ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["clicktoviewasresultset"]?></a><?php }?>
		</div>
		</div>
		</div>
		
		</div><?php
		} #end of display loop by resource extension
	} #end of IF sorted relations	
	
	
	# -------- Related Resources (Default)
	else { 
		 ?><!--Panel for related resources-->
		<div class="RecordBox">
		<div class="RecordPanel">  
         <div id="RelatedResources">
		<div class="RecordResouce">
		<div class="Title"><?php echo $lang["relatedresources"]?></div>
		<?php
    	# loop and display the results
    	for ($n=0;$n<count($result);$n++)            
        	{

			if(in_array($result[$n]["resource_type"],$relatedtypes_shown))
				{
				// Don't show this type again.
				continue;
				}
        	$rref=$result[$n]["ref"];
			$title=$result[$n]["field".$view_title_field];
			$access=get_resource_access($rref);
			$use_watermark=check_use_watermark();
			# swap title fields if necessary

			if (isset($metadata_template_title_field) && isset($metadata_template_resource_type))
				{
				if ($result[$n]["resource_type"]==$metadata_template_resource_type)
					{
					$title=$result[$n]["field".$metadata_template_title_field];
					}	
				}	
	
			global $related_resource_preview_size;
			?>
        	<!--Resource Panel-->
        	<div class="CollectionPanelShell">
            <table border="0" class="CollectionResourceAlign"><tr><td>
            <a href="<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $rref?>&search=<?php echo urlencode("!related" . $ref)?>" onClick="return CentralSpaceLoad(this,true);"><?php if ($result[$n]["has_image"]==1) { ?><img border=0 src="<?php echo get_resource_path($rref,false,$related_resource_preview_size,false,$result[$n]["preview_extension"],-1,1,$use_watermark,$result[$n]["file_modified"])?>" /><?php } else { ?><img border=0 src="../gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],true)?>"/><?php } ?></a></td>
            </tr></table>
            <div class="CollectionPanelInfo"><a href="<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $rref?>" onClick="return CentralSpaceLoad(this,true);"><?php echo tidy_trim(i18n_get_translated($title),$related_resources_title_trim)?></a>&nbsp;</div>
				<?php hook("relatedresourceaddlink");?>       
       </div>
        <?php        
        }
    ?>
    <div class="clearerleft"> </div>
        <a href="<?php echo $baseurl ?>/pages/search.php?search=<?php echo urlencode("!related" . $ref) ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["clicktoviewasresultset"]?></a>

    </div>
    </div>
    </div>
    
    </div><?php
		}# end related resources display
	} 
	# -------- End Related Resources
	
	 } # end of related resources block that requires search permissions

if ($show_related_themes==true ){
# -------- Public Collections / Themes
$result=get_themes_by_resource($ref);
if (count($result)>0) 
	{
	?><!--Panel for related themes / collections -->
	<div class="RecordBox">
	<div class="RecordPanel">  
	<div id="CollectionsThemes">
	<div class="RecordResouce BasicsBox nopadding">
	<div class="Title"><?php echo $lang["collectionsthemes"]?></div>

	<?php
		for ($n=0;$n<count($result);$n++)			
			{
            $url = generateURL("{$baseurl}/pages/search.php", array("search" => "!collection{$result[$n]["ref"]}"));

            $path = $result[$n]["path"];
            if(!$collection_public_hide_owner)
                {
                $col_name = i18n_get_translated($result[$n]["name"]);
                // legacy thing: we add the fullname right before the collection name in the path.
                $path = str_replace($col_name, htmlspecialchars($result[$n]["fullname"]) . " / {$col_name}", $path);
                }
            $path = sprintf("%s %s", LINK_CARET, htmlspecialchars($path));
			?>
            <a href="<?php echo $url; ?>" onclick="return CentralSpaceLoad(this, true);"><?php echo $path; ?></a><br>
			<?php
			}
		?>
	
	</div>
	</div>
	</div>
	
	</div><?php
	}} 


if($enable_find_similar && checkperm('s') && ($k == '' || $internal_share_access)) { ?>
<!--Panel for search for similar resources-->
<div class="RecordBox">
<div class="RecordPanel"> 
<div id="SearchSimilar">

<div class="RecordResouce">
<div class="Title"><?php echo $lang["searchforsimilarresources"]?></div>

<script type="text/javascript">
function <?php echo $context ?>UpdateFSResultCount()
	{
	// set the target of the form to be the result count iframe and submit

	// some pages are erroneously calling this function because it exists in unexpected
	// places due to dynamic page loading. So only do it if it seems likely to work.
	if(jQuery('#<?php echo $context ?>findsimilar').length > 0)
		{
		document.getElementById("<?php echo $context ?>findsimilar").target="<?php echo $context ?>resultcount";
		document.getElementById("<?php echo $context ?>countonly").value="yes";
		document.getElementById("<?php echo $context ?>findsimilar").submit();
		document.getElementById("<?php echo $context ?>findsimilar").target="";
		document.getElementById("<?php echo $context ?>countonly").value="";
		}
	}
</script>

<form method="post" action="<?php echo $baseurl ?>/pages/find_similar.php?context=<?php echo $context ?>" id="<?php echo $context ?>findsimilar">
<input type="hidden" name="resource_type" value="<?php echo $resource["resource_type"]?>">
<input type="hidden" name="countonly" id="<?php echo $context ?>countonly" value="">
<?php
generateFormToken("{$context}findsimilar");

$keywords=get_resource_top_keywords($ref,50);
if (count($keywords)!=0)
	{
		for ($n=0;$n<count($keywords);$n++)
			{
			?>
			<div class="SearchSimilar"><input type=checkbox id="<?php echo $context ?>similar_search_<?php echo urlencode($keywords[$n]) . "_" . $n  ?>" name="keyword_<?php echo urlencode($keywords[$n])?>" value="yes"
			onClick="<?php echo $context ?>UpdateFSResultCount();"><label for="similar_search_<?php echo urlencode($keywords[$n]) . "_" . $n?>">&nbsp;<?php echo htmlspecialchars(i18n_get_translated($keywords[$n]))?></label></div>
			<?php
			}
	
		?>
		<div class="clearerleft"> </div>
		<br />
		<input name="search" type="submit" value="&nbsp;&nbsp;<?php echo $lang["searchbutton"]?>&nbsp;&nbsp;" id="<?php echo $context ?>dosearch"/>
		<iframe src="<?php echo $baseurl ?>/pages/blank.html" frameborder=0 scrolling=no width=1 height=1 style="visibility:hidden;" name="<?php echo $context ?>resultcount" id="<?php echo $context ?>resultcount"></iframe>
		</form>
		<?php
	}
	
else
	{
	echo $lang["nosimilarresources"];	
	}
	?>

<div class="clearerleft"> </div>
</div>
</div>
</div>

</div>
<?php 
	hook("afterviewfindsimilar");
}

if($annotate_enabled)
    {
    ?>
    <!-- Annotorious -->
    <link type="text/css" rel="stylesheet" href="<?php echo $baseurl; ?>/lib/annotorious_0.6.4/css/theme-dark/annotorious-dark.css" />
    <script src="<?php echo $baseurl; ?>/lib/annotorious_0.6.4/annotorious.min.js"></script>

    <!-- Annotorious plugin(s) -->
    <link type="text/css" rel="stylesheet" href="<?php echo $baseurl; ?>/lib/annotorious_0.6.4/plugins/RSTagging/rs_tagging.css" />
    <script src="<?php echo $baseurl; ?>/lib/annotorious_0.6.4/plugins/RSTagging/rs_tagging.js"></script>
    <?php
    if($facial_recognition)
        {
        ?>
        <script src="<?php echo $baseurl; ?>/lib/annotorious_0.6.4/plugins/RSFaceRecognition/rs_facial_recognition.js"></script>
        <?php
        }
        ?>
    <script>
    jQuery('#PreviewToolsOptionsWrapper').trigger('readyToUseAnnotorious');
    </script>
    <!-- End of Annotorious -->
    <?php
	}
	?>

<script>
jQuery('document').ready(function()
    {
	/* Call SelectTab upon page load to select first tab*/
    SelectMetaTab(<?php echo $ref.",0,".($modal ? "true" : "false") ?>);
    registerCollapsibleSections(false);
    });
</script>
<?php include "../include/footer.php";


