<?php
/**
 * View resource page
 * 
 * @package ResourceSpace
 * @subpackage Pages
 */
include_once "../include/db.php";

$ref=(int) getval("ref",0,true);

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getval("k","");if (($k=="") || (!check_access_key($ref,$k))) {include "../include/authenticate.php";}
include_once "../include/image_processing.php";

// Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
$internal_share_access = internal_share_access();

# Update hit count
update_hitcount($ref);

# fetch the current search (for finding similar matches)
$search=getval("search","");
$order_by=getval("order_by","relevance");

# add order_by check to filter values prefixed by 'field'
if(
    preg_match("/^field(.*)/", $order_by, $matches)
    && !in_array($matches[1],$sort_fields) # check that field ref  is in config $sort_fields array
    ) {
    $order_by="relevance"; # if not, then sort by relevance
}

$offset=getval("offset",0,true);
$restypes=getval("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getval("archive","");
$per_page=getval("per_page",$default_perpage,true);
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);
$modal=(getval("modal","")=="true");
$context=($modal?"Modal":"Root"); # Set a unique context, used for JS variable scoping so this page in a modal doesn't conflict with the same page open behind the modal.

# next / previous resource browsing
$curpos=getval("curpos","");
$go=getval("go","");

if ($go!="") 
    {
    $origref=$ref; # Store the reference of the resource before we move, in case we need to revert this.

    # Re-run the search and locate the next and previous records.
    $modified_result_set=hook("modifypagingresult"); 
    if ($modified_result_set){
        $result=$modified_result_set;
    } else {
        $result=do_search($search,$restypes,$order_by,$archive,-1,$sort,false,DEPRECATED_STARSEARCH,false,false,"", getval("go",""));
    }
    if (is_array($result))
        {
        # Locate this resource
        $pos=-1;
        for ($n=0;$n<count($result);$n++)
            {
            if (isset($result[$n]["ref"]) && $result[$n]["ref"]==$ref) {$pos=$n;}
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
            alert('<?php echo escape($lang["resourcenotinresults"]) ?>');
            </script>
            <?php
            }
        }

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
// Get resource_type -> resource_type_field associations
$arr_fieldrestypes = get_resource_type_field_resource_types();

hook("aftergetresourcedataview","",array($ref,$resource));

# Allow alternative configuration settings for this resource type.
resource_type_config_override($resource["resource_type"]);

# get comments count
$resource_comments=0;
if($comments_resource_enable && $comments_view_panel_show_marker){
    $resource_comments=ps_value("select count(*) value from comment where resource_ref=?",array("i",$ref),"0");
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
    if (
        isset($resource_view_large_orientation)
        && $resource_view_large_orientation == true
        && $resource["has_image"] == 1
        && ($resource["thumb_height"] >= $resource["thumb_width"])
        ) {
            # Portrait or square image
           $use_larger_layout = false;
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

    if(resource_has_access_denied_by_RT_size($resource['resource_type'], ''))
        {
        $use_mp3_player = false;
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
debug(sprintf('Viewing resource #%s (access: %s)', $resource['ref'], $access));

# Establish if this is a metadata template resource, so we can switch off certain unnecessary features
$is_template=(isset($metadata_template_resource_type) && $resource["resource_type"]==$metadata_template_resource_type);

debug(sprintf('$is_template = %s', json_encode($is_template)));
$title_field=$view_title_field; 
# If this is a metadata template and we're using field data, change title_field to the metadata template title field
if (isset($metadata_template_resource_type) && ($resource["resource_type"]==$metadata_template_resource_type))
    {
    if (isset($metadata_template_title_field)){
        $title_field=$metadata_template_title_field;
        }
    else {$default_to_standard_title=true;} 
    }

# If requested, refresh the collection frame (for redirects from saves)
if (getval("refreshcollectionframe","")!="")
    {
    refresh_collection_frame();
    }

# Update the hitcounts for the search nodes (if search specified)
# (important we fetch directly from $_GET and not from a cookie
$usearch= isset($_GET["search"]) ? $_GET["search"] : "";
# Update resource/node hit count
if (strpos($usearch,NODE_TOKEN_PREFIX) !== false)
    {
    update_node_hitcount_from_search($ref,$usearch);
    }

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
            style="display:none"
            > This browser can not use IFRAME.</iframe>
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
                            alert('<?php echo escape($lang["error"]) ?>\n' + textStatus);
                            }
                        });
                }
        </script>
        <?php
        }

hook("pageevaluation");

# Load resource field data
$multi_fields = false;
# Related resources with tabs need all fields (even the ones from other resource types):
if(isset($related_type_show_with_data)) {
    $multi_fields = true;
}

// Get all fields without checking permissions (for later dependency checking)
$fields_all=get_resource_field_data($ref,$multi_fields,false,null,($k!="" && !$internal_share_access),$use_order_by_tab_view);
debug(sprintf('$fields_all = %s', json_encode(array_column(is_array($fields_all) ? $fields_all : [], 'ref'))));

# Load field data
$fields=get_resource_field_data($ref,$multi_fields,!hook("customgetresourceperms"),null,($k!="" && !$internal_share_access),$use_order_by_tab_view);
$modified_view_fields=hook("modified_view_fields","",array($ref,$fields));if($modified_view_fields){$fields=$modified_view_fields;}
debug(sprintf('$fields = %s', json_encode(array_column(is_array($fields) ? $fields : [], 'ref'))));

# If no fields were found advise of configuration issue and exit.
if (!$fields_all || !$fields)
    {
    error_alert($lang["error_no_metadata"], false);
    exit();
    }

# Load edit access level (checking edit permissions - e0,e-1 etc. and also the group 'edit filter')
$edit_access = get_edit_access($ref,$resource["archive"],$resource);

# Check if resource is locked
$resource_locked = (int)$resource["lock_user"] > 0;
debug(sprintf('$resource_locked = %s', json_encode($resource_locked)));
$unlock_option = checkperm("a") || ($userref == $resource["lock_user"] && $userref != $anonymous_login);
$lock_details = get_resource_lock_message($resource["lock_user"]);

if ($k!="" && !$internal_share_access) {$edit_access=0;}

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
                    jQuery('#lock_link_' + resource).html('&nbsp;<?php echo escape($lang["action_unlock"]) ;?>');
                    jQuery('#lock_link_' + resource).attr("title","<?php echo escape($lang["status_locked_self"]); ?>");
                    lockmessage[resource] = '<?php echo escape($lang["status_locked_self"]); ?>';
                    jQuery('#lock_details_link').show();
                    }
                else
                    {
                    jQuery('#lock_link_' + resource).html('&nbsp;<?php echo escape($lang["action_lock"]) ;?>');
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
                    styledalert('<?php echo escape($lang["error"]); ?>',xhr.responseJSON.message);
                    }
                else
                    {
                    styledalert('<?php echo escape($lang["error"]); ?>',xhr.statusText);
                    }
                }
            });
        }

    <?php
    if ($view_panels)
        {
        ?>
        jQuery(document).ready(function () {        

            let parent_element = jQuery('#<?php echo $modal ? 'modal' : 'CentralSpace'; ?>');

            var comments_marker='<?php echo $comments_view_panel_show_marker?>';
            var comments_resource_enable='<?php echo $comments_resource_enable?>';
            var resource_comments='<?php echo $resource_comments?>';

            parent_element.find("#Metadata").appendTo(parent_element.find("#Panel1"));
            parent_element.find("#Metadata").addClass("TabPanel");

            parent_element.find("#CommentsPanelHeaderRowTitle").children(".Title").attr("panel", "Comments").appendTo(parent_element.find("#Titles1"));
            parent_element.find("#CommentsPanelHeaderRowTitle").remove();
            parent_element.find("#CommentsPanelHeaderRowPolicyLink").css("width","300px").css("float","right");
            removePanel=parent_element.find("#Comments").parent().parent();
            parent_element.find("#Comments").appendTo(parent_element.find("#Panel1")).addClass("TabPanel").hide();
            removePanel.remove();
            if(comments_marker==true && comments_resource_enable==true && resource_comments>'0'){
                parent_element.find("[panel='Comments']").append("&#42;");
            }

            parent_element.find("#RelatedResources").children().children(".Title").attr("panel", "RelatedResources").addClass("Selected").appendTo(parent_element.find("#Titles2"));
            removePanel=parent_element.find("#RelatedResources").parent().parent();
            parent_element.find("#RelatedResources").appendTo(parent_element.find("#Panel2")).addClass("TabPanel");
            removePanel.remove();

            parent_element.find("#SearchSimilar").children().children(".Title").attr("panel", "SearchSimilar").appendTo(parent_element.find("#Titles2"));
            removePanel=parent_element.find("#SearchSimilar").parent().parent();
            parent_element.find("#SearchSimilar").appendTo(parent_element.find("#Panel2")).addClass("TabPanel").hide();
            removePanel.remove();
            // if there are no related resources
            if (parent_element.find("#RelatedResources").length==0) {
                parent_element.find("#SearchSimilar").show();
                parent_element.find("div[panel='SearchSimilar']").addClass("Selected"); 
            }    

            // if there are no collections and themes
            if (parent_element.find("#resourcecollections").is(':empty')) {
                parent_element.find("div[panel='CollectionsThemes']").addClass("Selected"); 
                parent_element.find("#CollectionsThemes").show(); 
            }

            parent_element.find(".ViewPanelTitles").children(".Title").click(function(){
            // function to switch tab panels
                parent_element.find(this).parent().parent().children(".TabPanel").hide();
                parent_element.find(this).parent().children(".Title").removeClass("Selected");
                parent_element.find(this).addClass("Selected");
                parent_element.find("#"+jQuery(this).attr("panel")).css("position", "relative").css("left","0px").show();;
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
            <?php
            if (!hook("renderinnerresourceheader"))
                {
                $urlparams= array(
                    'ref'               => $ref,
                    'search'            => $search,
                    'order_by'          => $order_by,
                    'offset'            => $offset,
                    'restypes'          => $restypes,
                    'archive'           => $archive,
                    'per_page'          => $per_page,
                    'default_sort_direction' => $default_sort_direction,
                    'sort'              => $sort,
                    'context'           => $context,
                    'k'                 => $k,
                    'curpos'            => $curpos
                );
                debug(sprintf('$urlparams = %s', http_build_query($urlparams)));

                # Check if actually coming from a search, but not if a numeric search and config_search_for_number is set or if this is a direct request e.g. ?r=1234.
                if (!hook("replaceviewnav") && isset($_GET["search"]) && !($config_search_for_number && is_numeric($usearch)) && !($k != "" && strpos($search,"!collection") === false))
                    { ?>
                    <div class="backtoresults">
                        <a class="prevLink fa fa-arrow-left"
                            href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams, array("go"=>"previous")) . "&amp;" .  hook("nextpreviousextraurl") ?>"
                            onClick="return <?php echo $modal ? "Modal" : "CentralSpace"; ?>Load(this);"
                            title="<?php echo escape($lang["previousresult"])?>">
                        </a>

                        <?php 
                        if (!hook("viewallresults")) 
                            { ?>
                            <a class="upLink"
                                href="<?php echo generateURL($baseurl . "/pages/search.php",$urlparams,array("go"=>"up","place"=>$ref)) ?>"
                                onClick="return CentralSpaceLoad(this);">
                                <?php echo escape($lang["viewallresults"])?>
                            </a>
                            <?php 
                            } ?>

                        <a class="nextLink fa fa-arrow-right"
                            href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams, array("go"=>"next")) . "&amp;" .  hook("nextpreviousextraurl") ?>"
                            onClick="return <?php echo $modal ? "Modal" : "CentralSpace"; ?>Load(this);"
                            title="<?php echo escape($lang["nextresult"])?>">
                        </a>

                        <?php
                        if($modal)
                            { ?>
                            <a href="<?php echo generateURL($baseurl . "/pages/view.php",$urlparams) ?>"
                                onClick="return CentralSpaceLoad(this, true);"
                                class="maxLink fa fa-expand"
                                title="<?php echo escape($lang["maximise"])?>">
                            </a>
                            <a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo escape($lang["close"]) ?>"></a>
                            <?php
                            } ?>
                    </div>
                    <?php
                    }

                elseif($modal)
                    { ?>
                    <div class="backtoresults">
                        <?php
                        if (!hook("replacemaxlink"))
                            { ?>
                            <a href="<?php echo
                                generateURL($baseurl . "/pages/view.php",$urlparams) ?>"
                                onClick="return CentralSpaceLoad(this);"
                                class="maxLink fa fa-expand"
                                title="<?php echo escape($lang["maximise"])?>">
                            </a>
                            <?php
                            } ?>
                        <a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo escape($lang["close"]) ?>"></a>
                    </div>
                    <?php
                    } ?>

                <h1><?php
                    hook("beforeviewtitle");
                    # Display title prefix based on workflow state.
                    if (!hook("replacetitleprefix","",array($resource["archive"]))) { switch ($resource["archive"])
                        {
                        case -2:
                        ?><span class="ResourcePendingSubmissionTitle"><?php echo htmlspecialchars($lang["status-2"])?>:</span>&nbsp;<?php
                        break;
                        case -1:
                        ?><span class="ResourcePendingReviewTitle"><?php echo htmlspecialchars($lang["status-1"])?>:</span>&nbsp;<?php
                        break;
                        case 1:
                        ?><span class="ArchiveResourceTitle"><?php echo htmlspecialchars($lang["status1"])?>:</span>&nbsp;<?php
                        break;
                        case 2:
                        ?><span class="ArchiveResourceTitle"><?php echo htmlspecialchars($lang["status2"])?>:</span>&nbsp;<?php
                        break;
                        case 3:
                        ?><span class="DeletedResourceTitle"><?php echo htmlspecialchars($lang["status3"])?>:</span>&nbsp;<?php
                        break;
                        }

                    #If additional archive states are set, put them next to the field used as title
                    if (
                        isset($additional_archive_states)
                        && count($additional_archive_states)!=0
                        && in_array($resource["archive"],$additional_archive_states)
                        ) {
                            ?>
                            <span class="ArchiveResourceTitle"><?php echo htmlspecialchars($lang["status" . $resource['archive']]) ?>:</span>&nbsp;<?php       
                        }
                    }

                    if (!hook('replaceviewtitle'))
                        {
                        echo highlightkeywords(htmlspecialchars(i18n_get_translated(get_data_by_field($resource['ref'], $title_field))), $search);
                        } /* end hook replaceviewtitle */
                    ?>
                    &nbsp;
                </h1>
<?php
            } /* End of renderinnerresourceheader hook */ ?>
        </div>

        <?php
        if (
            !hook("replaceresourceistranscoding")
            && isset($resource['is_transcoding'])
            && $resource['is_transcoding'] != 0
            ) {
                ?>
                <div class="PageInformal">
                    <?php echo htmlspecialchars($lang['resourceistranscoding'])?>
                </div>
                <?php
            } //end hook replaceresourceistrancoding ?>

        <?php hook('renderbeforeresourceview', '', array('resource' => $resource));

        # Keep track of need for openseadragon library
        $image_preview_zoom_lib_required = false;

        if (in_array($resource["file_extension"], config_merge_non_image_types()) && $non_image_types_generate_preview_only)
            {
            $download_multisize=false;
            $image_preview_zoom = false;
            }
        else
            {
            $download_multisize=true;
            }
        ?>

        <div class="RecordResource">
            <?php
            if (!hook("renderinnerresourceview"))
                {
                if (
                    !hook("replacerenderinnerresourcepreview")
                    && !hook("renderinnerresourcepreview")
                    ) {
                        if (file_exists("../players/type" . $resource["resource_type"] . ".php"))
                            {
                            // Legacy code - should now be replaced by a plugin
                            include "../players/type" . $resource["resource_type"] . ".php";
                            }
                        else
                            {
                            // Standard previews START
                            if (
                                (in_array((string)$resource["file_extension"], $ffmpeg_supported_extensions)
                                || ($ffmpeg_preview_gif && strtolower((string)$resource["file_extension"]) === 'gif'))
                                &&
                                !(isset($resource['is_transcoding']) && $resource['is_transcoding'] !== 0)
                                )
                                {
                                // Video preview START
                                # Establish whether it's ok to use original as the preview instead of the "pre" size
                                $videosize= ($video_preview_original) ? "" : "pre"; 
                                # Now pass the original file extension when necessary instead of arbitrarily passing the ffmpeg preview extension
                                $videoextension = ($video_preview_original) ? $resource["file_extension"] : $ffmpeg_preview_extension;

                                # Try to find a preview file.
                                $video_preview_file = get_resource_path(
                                    $ref,
                                    true,
                                    $videosize,
                                    false,
                                    ((1 == $video_preview_hls_support || 2 == $video_preview_hls_support) && !($ffmpeg_preview_gif && $resource["file_extension"] == 'gif')) ? 'm3u8' : $videoextension
                                );

                                if (file_exists($video_preview_file)
                                    && !resource_has_access_denied_by_RT_size($resource['resource_type'], 'pre')
                                    )
                                    {
                                    # Include the player if a video preview file exists for this resource.
                                    if ($resource["file_extension"] != 'gif')
                                            {
                                            $download_multisize = false; 
                                            }
                                        else
                                            {
                                            $download_multisize = true; // gif preview sizes remain available when using $ffmpeg_preview_gif
                                            }
                                    ?>
                                    <div id="previewimagewrapper">
                                        <?php 
                                        if(!hook("customflvplay")) // Note: Legacy hook name; video_player.php no longer deals with FLV files.
                                            {
                                            include "video_player.php";
                                            }
                                        ?>
                                    </div>
                                    <?php
                                    }
                                elseif($resource['has_image'] !== RESOURCE_PREVIEWS_NONE)
                                    {
                                    // render preview image instead, no zoom or annotations possible
                                    $GLOBALS["annotate_enabled"] = false;
                                    render_resource_view_image($resource,[
                                        "access"=>$access,
                                        "edit_access"=>$edit_access,
                                        ]
                                        );
                                    }
                                } // Video preview END
                            elseif ($use_mp3_player && file_exists($mp3realpath) && !hook("replacemp3player"))
                                {
                                // MP3 preview START 
                                ?>
                                <div id="previewimagewrapper">
                                <?php 
                                $thumb_path=get_resource_path($ref,true,"pre",false,"jpg");
    
                                if (file_exists($thumb_path) && !resource_has_access_denied_by_RT_size($resource['resource_type'], 'pre'))
                                    {
                                    $thumb_url=get_resource_path($ref,false,"pre",false,"jpg");
                                    }
                                else
                                    {
                                    $thumb_url=$baseurl . "/gfx/" . get_nopreview_icon($resource["resource_type"],$resource["file_extension"],false);
                                    }
    
                                include "mp3_play.php";
                                ?>
                                </div>
                                <?php
                                // MP3 preview END 
                                }
                            elseif($resource['has_image'] !== RESOURCE_PREVIEWS_NONE)
                                {
                                render_resource_view_image($resource,[
                                    "access"=>$access,
                                    "edit_access"=>$edit_access,
                                    ]
                                    );
                                }
                            else
                                {
                                // No preview. If configured, try and use a preview from a related resource
                                $pullresource = related_resource_pull($resource);
                                if($pullresource !== false)
                                    {
                                    $pull_access = get_resource_access($pullresource);
                                    render_resource_view_image($pullresource,[
                                        "access"=>$pull_access,
                                        "edit_access"=>0, // No ability to modify e.g. annotations
                                        ]
                                        );
                                    }
                                else
                                    {?>
                                    <div id="previewimagewrapper">
                                        <img src="<?php echo $baseurl ?>/gfx/<?php echo get_nopreview_icon($resource["resource_type"],$resource["file_extension"],false)?>"
                                            alt=""
                                            class="Picture NoPreview"
                                            style="border:none;"
                                            id="previewimage" />
                                        <?php
                                        hook('aftersearchimg', '', array($ref));
                                        hook("previewextras");
                                        ?>
                                    </div>
                                    <?php
                                    }
                                }
                            } // Standard previews END
                    } /* End of replacerenderinnerresourcepreview hook and end of renderinnerresourcepreview hook */

                $disable_flag = hook('disable_flag_for_renderbeforerecorddownload');
                hook("renderbeforerecorddownload", '', array($disable_flag));

                if (!hook("renderresourcedownloadspace"))
                    { ?>
                    <div class="RecordDownload" id="RecordDownloadTabContainer">
                        <div class="TabBar" id="RecordDownloadTabButtons">
                            <div class="Tab TabSelected" id="DownloadsTabButton">
                                <a href="#" onclick="selectDownloadTab('DownloadsTab',<?php echo $modal ? 'true' : 'false'; ?>);">
                                    <?php echo htmlspecialchars($lang["resourcetools"]) ?>
                                </a>
                            </div>
                            <?php
                            if ($download_summary)
                                {
                                ?>
                                <div class="Tab" id="RecordDownloadSummaryButton">
                                    <a href="#" onclick="selectDownloadTab('RecordDownloadSummary',<?php echo $modal ? 'true' : 'false'; ?>);">
                                        <?php echo $use_larger_layout ? htmlspecialchars($lang["usagehistory"]) : htmlspecialchars($lang["usage"]) ?>
                                    </a>
                                </div>
                                <?php
                                }
                            hook("additionaldownloadtabbuttons"); ?>
                        </div>
                        <div class="RecordDownloadSpace" id="DownloadsTab">
                            <?php
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
                                    <tr <?php hook("downloadtableheaderattributes")?> id="ResourceDownloadOptionsHeader">
                                        <?php
                                        $table_headers_drawn=false;
                                        $nodownloads=false;$counter=0;$fulldownload=false;
                                        hook("additionalresourcetools");
                                        if ($resource["has_image"] !== RESOURCE_PREVIEWS_NONE && $download_multisize)
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
                                                    {
                                                    $headline=$newHeadline;
                                                    }

                                                if ($hide_restricted_download_sizes && !$downloadthissize && !checkperm("q"))
                                                    {
                                                    continue;
                                                    }

                                                if (
                                                    !hook("replacedownloadspacetableheaders")
                                                    && $table_headers_drawn == false
                                                    ) {
                                                        ?>
                                                        <td><?php echo htmlspecialchars($lang["fileinformation"])?></td>
                                                        <?php echo $use_larger_layout ? "<td>" . $lang["filedimensions"] . "</td>" : ''; ?>
                                                        <td><?php echo htmlspecialchars($lang["filesize"])?></td>
                                                        <td class="textcenter"><?php echo htmlspecialchars($lang["options"])?></td>
                                                        </tr>
                                                        <?php
                                                        $table_headers_drawn=true;
                                                    } # end hook("replacedownloadspacetableheaders")?>

                                                <tr class="DownloadDBlend" id="DownloadBox<?php echo $n?>">
                                                    <td class="DownloadFileName"><h2><?php echo $headline?></h2><?php
                                                        echo $use_larger_layout ? '</td><td class="DownloadFileDimensions">' : '';

                                                        if (is_numeric($sizes[$n]["width"]))
                                                            {
                                                            echo get_size_info($sizes[$n]);
                                                            }
                                                            ?>
                                                    </td>
                                                    <td class="DownloadFileSize"><?php echo $sizes[$n]["filesize"]?></td>
                                                    <?php add_download_column($ref, $sizes[$n], $downloadthissize); ?>
                                                </tr>

                                                <?php
                                                if (
                                                    !hook("previewlinkbar")
                                                    && $downloadthissize
                                                    && $sizes[$n]["allow_preview"] == 1
                                                    ) {
                                                    # Add an extra line for previewing
                                                    global $data_viewsize;
                                                    $data_viewsize=$sizes[$n]["id"];
                                                    $data_viewsizeurl=hook('getpreviewurlforsize');
                                                    $preview_with_sizename=str_replace('%sizename', $sizes[$n]["name"], $lang['previewithsizename']);
                                                    ?> 
                                                    <tr class="DownloadDBlend">
                                                        <td class="DownloadFileName">
                                                            <h2><?php echo htmlspecialchars($lang["preview"])?></h2>
                                                            <?php echo $use_larger_layout ? '</td><td class="DownloadFileDimensions">' : '';?>
                                                            <p><?php echo $preview_with_sizename; ?></p>
                                                        </td>
                                                        <td class="DownloadFileSize"><?php echo $sizes[$n]["filesize"]?></td>
                                                        <td class="DownloadButton">
                                                            <a class="enterLink previewsizelink previewsize-<?php echo $data_viewsize; ?>" 
                                                                id="previewlink"
                                                                data-viewsize="<?php echo $data_viewsize; ?>"
                                                                data-viewsizeurl="<?php echo $data_viewsizeurl; ?>"  
                                                                href="<?php echo generateURL($baseurl . "/pages/preview.php",$urlparams,array("ext"=>$resource["file_extension"])) . "&" . hook("previewextraurl") ?>">
                                                                <?php echo htmlspecialchars($lang["action-view"])?>
                                                            </a>
                                                        </td>
                                                    </tr>
<?php
                                                    }
                                                }
                                            }
                                        elseif (strlen((string) $resource["file_extension"])>0 && !($access==1 && $restricted_full_download==false))
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
                                                        <td class="DownloadFileName" <?php echo $use_larger_layout ? ' colspan="2"' : ''; ?>>
                                                            <h2><?php echo (isset($original_download_name)) ? str_replace_formatted_placeholder("%extension", $resource["file_extension"], $original_download_name, true) : str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["originalfileoftype"]); ?></h2>
                                                        </td>
                                                        <td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($path))?></td>

                                                        <?php
                                                        $size_info = array('id' => '', 'extension' => $resource['file_extension']);
                                                        $downloadthissize=resource_download_allowed($ref,'',$resource["resource_type"]);
                                                        add_download_column($ref,$size_info, $downloadthissize);
                                                        ?>
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
                                        elseif (strlen((string) $resource["file_extension"])>0 && ($access==1 && $restricted_full_download==false))
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
                                                        <td class="DownloadFileName">
                                                            <h2><?php echo (isset($original_download_name)) ? str_replace_formatted_placeholder("%extension", $resource["file_extension"], $original_download_name, true) : str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["originalfileoftype"]); ?></h2>
                                                        </td>
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

                                        // Render a "View in browser" button for PDF/MP3 (no longer configurable in config as SVGs can easily be disguised)
                                        if (strlen((string) $resource["file_extension"]) > 0 
                                            && ($access == 0 || ($access == 1 && $restricted_full_download == true)) 
                                            && in_array(strtolower($resource["file_extension"]),VIEW_IN_BROWSER_EXTENSIONS))
                                            {
                                            $path=get_resource_path($ref,true,"",false,$resource["file_extension"]);
                                            if (resource_download_allowed($ref,"",$resource["resource_type"]) && file_exists($path))
                                                {
                                                $counter++;
                                                ?>
                                                <tr class="DownloadDBlend">
                                                    <td class="DownloadFileName">
                                                        <h2><?php echo htmlspecialchars($lang["view_directly_in_browser"]); ?></h2>
                                                        <?php if ($use_larger_layout)
                                                            {
                                                            ?></td><td class="DownloadFileDimensions"><?php
                                                            }

                                                        if ($resource["has_image"] !== RESOURCE_PREVIEWS_NONE)
                                                            {
                                                            $sizes=get_image_sizes($ref,false,$resource["file_extension"]);
                                                            $original_size = '';
                                                            for ($n=0;$n<count($sizes);$n++)
                                                                {
                                                                if ($sizes[$n]["id"]=="" && is_numeric($sizes[$n]["width"]))
                                                                    {
                                                                    $original_size = get_size_info($sizes[$n]);
                                                                    break;
                                                                    }
                                                                }
                                                            echo $original_size;
                                                            }
                                                        ?>
                                                    </td>
                                                    <td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($path))?></td>
                                                    <?php
                                                    $size_info = array('id' => '', 'extension' => $resource['file_extension']);
                                                    add_download_column($ref, $size_info, $downloadthissize, true);
                                                    ?>
                                                </tr>
                                                <?php
                                                }
                                            }

                                        if (($nodownloads || $counter == 0) && !resource_has_access_denied_by_RT_size($resource['resource_type'], ''))
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
                                                <td class="DownloadFileSize"><?php echo htmlspecialchars($lang["notavailableshort"])?></td>

                                                <?php
                                                if ($generate_data_only_pdf_file)
                                                    {
                                                    $generate_data_only_url_params = array(
                                                        'ref'             => $ref,
                                                        'download'        => 'true',
                                                        'data_only'       => 'true',
                                                        'k'               => $k
                                                    );
                                                    ?>
                                                    <td <?php hook("modifydownloadbutton") ?> class="DownloadButton">
                                                        <a href="<?php echo generateURL($baseurl . '/pages/metadata_download.php', $generate_data_only_url_params); ?>">
                                                            <?php echo htmlspecialchars($lang['action-generate_pdf']); ?>
                                                        </a>
                                                    </td>
                                                    <?php
                                                    }
                                                // No file. Link to request form.
                                                elseif(checkperm('q'))
                                                    {
                                                    if(!hook('resourcerequest'))
                                                        {
                                                        ?>
                                                        <td <?php hook("modifydownloadbutton") ?> class="DownloadButton"></td>
                                                        <?php
                                                        }
                                                    }
                                                else
                                                    {
                                                    ?>
                                                    <td <?php hook("modifydownloadbutton") ?> class="DownloadButton DownloadDisabled"><?php echo htmlspecialchars($lang["access1"])?></td>
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
                                                <td class="DownloadFileName" colspan="2">
                                                    <h2><?php echo (isset($ffmpeg_preview_download_name)) ? $ffmpeg_preview_download_name : str_replace_formatted_placeholder("%extension", $ffmpeg_preview_extension, $lang["cell-fileoftype"]); ?></h2>
                                                </td>
                                                <td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($video_preview_file))?></td>
                                                <td <?php hook("modifydownloadbutton") ?> class="DownloadButton">
                                                    <?php
                                                    if ($terms_download || $save_as)
                                                        { ?>
                                                        <a href="<?php echo generateURL($baseurl . "/pages/terms.php",$urlparams,array("url"=>generateURL($baseurl . "/pages/download_progress.php",$urlparams,array("ext"=>$ffmpeg_preview_extension,"size"=>"pre")))) ?>" onClick="return CentralSpaceLoad(this,true);">
                                                            <?php echo htmlspecialchars($lang["action-download"]) ?>
                                                        </a>
                                                        <?php
                                                        }
                                                    elseif ($download_usage)
                                                        // download usage form displayed - load into main window
                                                        { ?>
                                                        <a href="<?php echo $baseurl ?>/pages/download_progress.php?ref=<?php echo urlencode($ref)?>&ext=<?php echo $ffmpeg_preview_extension?>&size=pre&k=<?php echo urlencode($k) ?>">
                                                            <?php echo htmlspecialchars($lang["action-download"])?>
                                                        </a>                
                                                        <?php
                                                        }
                                                    else
                                                        { ?>
                                                        <a href="#" onclick="directDownload('<?php echo $baseurl ?>/pages/download_progress.php?ref=<?php echo urlencode($ref)?>&ext=<?php echo $ffmpeg_preview_extension?>&size=pre&k=<?php echo urlencode($k) ?>')">
                                                            <?php echo htmlspecialchars($lang["action-download"])?>
                                                        </a>
                                                        <?php
                                                        } ?>
                                                    </td>
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
                            ?>

                            <div class="RecordTools">
                                <ul id="ResourceToolsContainer">

                                    <?php
                                    # ----------------------------- Resource Actions -------------------------------------
                                    hook ("resourceactions");

                                    if ($k=="" || $internal_share_access)
                                        {
                                        if (!hook("replaceresourceactions"))
                                            {
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
                                                && !in_array($resource["resource_type"],$collection_block_restypes)) 
                                                { 
                                                ?>
                                                <li>
                                                    <?php 
                                                    echo add_to_collection_link($ref);
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
                                                        echo remove_from_collection_link($ref,"","",0);
                                                        echo "<i class='fa fa-fw fa-minus-circle'></i>&nbsp;" .$lang["action-removefromcollection"]?>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                    }
                                                }

                                            if (can_share_resource($ref,$access) && !$hide_resource_share_link) 
                                                { 
                                                ?>
                                                <li>
                                                    <a href="<?php echo generateurl($baseurl . "/pages/resource_share.php",$urlparams);?>" onclick="return ModalLoad(this, true);">
                                                        <?php echo "<i class='fa fa-fw fa-share-alt'></i>&nbsp;" . $lang["share"];?>
                                                    </a>
                                                </li>
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

                                                if (!checkperm("D") || hook('check_single_delete'))
                                                    {
                                                    $deletetext = (isset($resource_deletion_state) && $resource["archive"] == $resource_deletion_state) ? $lang["action-delete_permanently"] : $lang["action-delete"];
                                                    echo "<li>";
                                                    if($resource_locked && $resource['lock_user'] != $userref)
                                                        {
                                                        echo "<div class='DisabledLink LockedResourceAction'><i class='fa fa-fw fa-trash'></i>&nbsp;" . $deletetext . "</div>";
                                                        }
                                                    elseif ($delete_requires_password)
                                                        {
                                                        $delete_url = generateURL($baseurl . "/pages/delete.php", $urlparams);
                                                        echo "<a id='delete_link_" . $ref . "' href='" . $delete_url . "' class='LockedResourceAction' onclick='return ModalLoad(this, true);' ><i class='fa fa-fw fa-trash'></i>&nbsp;" . $deletetext . "</a>";
                                                        }
                                                    else
                                                        {
                                                        $urlparams['text']='deleted';
                                                        $urlparams['refreshcollection']='true';
                                                        $redirect_url = generateURL($baseurl_short . "pages/done.php",$urlparams);
                                                        ?> <a id='delete_link_" . $ref . "' href='#' onclick="
                                                        if (confirm('<?php echo escape($lang['filedeleteconfirm']) ?>'))
                                                            {
                                                            api(
                                                                'delete_resource',
                                                                {'resource':'<?php echo $ref?>'}, 
                                                                function(response){
                                                                    ModalLoad('<?php echo $redirect_url ?>',true);
                                                                },
                                                                <?php echo escape(generate_csrf_js_object('delete_resource')); ?>
                                                            );
                                                            }
                                                        " ><i class='fa fa-fw fa-trash'></i>&nbsp;<?php echo $deletetext ?></a>
                                                        <?php }
                                                    echo "</li>";
                                                    }

                                                if (!checkperm('A')) 
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
                                                    $replace_upload_type = 'batch';
                                                    }
                                                else 
                                                    {
                                                    $replace_upload_type=$top_nav_upload_type;
                                                    }

                                                if (!(in_array($resource['resource_type'], $data_only_resource_types)) && !resource_file_readonly($ref) && (checkperm("c") || checkperm("d")))
                                                    { ?>
                                                    <li>
                                                        <a id="view_replace_link" href="<?php echo generateURL($baseurl_short . "pages/upload_" . $replace_upload_type . ".php", $urlparams, array("replace_resource"=>$ref, "resource_type"=>$resource['resource_type'])); ?>" 
                                                            onClick="if(jQuery('#uploader').length){return CentralSpaceLoad(this,true);} else {return ModalLoad(this,true);}">
                                                            <?php if ($resource["file_extension"] != "")
                                                                { ?>
                                                                <i class='fa fa-fw fa-file-import'></i>&nbsp;<?php echo htmlspecialchars($lang["replacefile"]);
                                                                }
                                                            else
                                                                { ?>
                                                                <i class='fa fa-fw fa-upload'></i>&nbsp;<?php echo htmlspecialchars($lang["uploadafile"]);
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
                                                if (!$disable_upload_preview && !resource_file_readonly($ref) && !checkperm("F*") && !$custompermshowfile) 
                                                    { ?>
                                                    <li>
                                                        <a id="view_upload_preview_link" href="<?php echo generateURL($baseurl_short . "pages/upload_preview.php",$urlparams); ?>" onClick="return ModalLoad(this,true);">
                                                            <i class='fa fa-fw fa-upload'></i>&nbsp;<?php echo htmlspecialchars($lang["uploadpreview"])?>
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
                                                <li>
                                                    <a href="<?php echo generateurl($baseurl . "/pages/metadata_download.php",$urlparams);?>" onclick="return ModalLoad(this, true);">
                                                        <?php echo "<i class='fa fa-fw fa-history'></i>&nbsp;" .$lang["downloadmetadata"]?>
                                                    </a>
                                                </li>
                                                <?php 
                                                }

                                            $overrideparams= array(
                                                'search_offset'     => $offset,
                                                'offset'            => 0,
                                                'per_page'          => $default_perpage_list,
                                            );

                                            if (checkperm('v')) 
                                                { ?>
                                                <li>
                                                    <a id="view_log_link" href="<?php echo generateurl($baseurl . "/pages/log.php",$urlparams,$overrideparams);?>" onclick="return ModalLoad(this, true);">
                                                        <?php echo "<i class='fa fa-fw fa-bars'></i>&nbsp;" .$lang["log"]?>
                                                    </a>
                                                </li>
                                                <?php 
                                                }

                                            if (checkperm("R") && $display_request_log_link) 
                                                { ?>
                                                <li>
                                                    <a href="<?php echo generateurl($baseurl . "/pages/request_log.php",$urlparams,$overrideparams);?>" onclick="return ModalLoad(this, true);">
                                                        <?php echo "<i class='fa fa-fw fa-history'></i>&nbsp;" .$lang["requestlog"]?>
                                                    </a>
                                                </li>
                                                <?php 
                                                }

                                            } /* End replaceresourceactions */ 

                                        hook("afterresourceactions", "", array($ref));
                                        hook("afterresourceactions2");

                                        } /* End if ($k!="")*/ 

                                    hook("resourceactions_anonymous");
                                    ?>
                                </ul><!-- End of ResourceToolsContainer -->
                            </div>
                        </div><!-- End of RecordDownloadSpace -->

                        <?php
                        if ($download_summary)
                            {
                            include "../include/download_summary.php";
                            }

                        hook("additionaldownloadtabs"); ?>

                        <div class="clearerleft"> </div>

                        <?php
                        if (
                            !hook("replaceuserratingsbox")
                            && $user_rating # Include user rating box, if enabled and the user is not external.
                           && ($k=="" || $internal_share_access)
                            ) {
                                include "../include/user_rating.php";
                            } /* end hook replaceuserratingsbox */

                        ?>
                    </div><!-- End of RecordDownload -->

<?php
                    } /* End of renderresourcedownloadspace hook */
                } /* End of renderinnerresourceview hook */

            hook("renderbeforeresourcedetails");

            /* ---------------  Display metadata ----------------- */
            if (!hook('replacemetadata'))
                {
                ?>
                <div id="Panel1" class="ViewPanel">
                    <div id="Titles1" class="ViewPanelTitles">
                        <div class="Title Selected" panel="Metadata"><?php if (!hook("customdetailstitle")) echo htmlspecialchars($lang["resourcedetails"])?></div>
                    </div>
                </div>
                <?php include "view_metadata.php";
                } /* End of replacemetadata hook */ ?>

        </div><!-- End of RecordResource -->
    </div><!-- End of RecordPanel -->
</div><!-- End of RecordBox -->

<?php
/*
 ----------------------------------
 Show "pushed" metadata - from related resources with push_metadata set on the resource type. Metadata for those resources
 appears here in the same style.

 */
$pushed=do_search("!relatedpushed" . $ref);
debug(sprintf('$pushed = %s', json_encode(array_column($pushed, 'ref'))));

// Get metadata for all related resources to save multiple db queries
$pushedfielddata = get_resource_field_data_batch(array_column($pushed,"ref"),true,($k != "" && !$internal_share_access));
$allpushedfielddata = get_resource_field_data_batch(array_column($pushed,"ref"),false, ($k != "" && !$internal_share_access));

foreach ($pushed as $pushed_resource)
    {
    RenderPushedMetadata($pushed_resource, $pushedfielddata, $allpushedfielddata);
    }

function RenderPushedMetadata($resource, $field_data, $all_field_data)
    {
    global $k,$view_title_field,$lang, $internal_share_access, $fields_all, $ref, $access, $userpermissions;
    // Save currentt resource data
    $reset_ref          = $ref;
    $reset_access       = $access;
    $reset_fields_all   = $fields_all;

    $ref                = $resource["ref"];
    if(!array_key_exists('hit_count', $resource))
        {
        $resource['hit_count'] = $resource['score'];
        }
    // Ensure that this pushed resource honours any resource type overrides
    resource_type_config_override($resource["resource_type"]);

    $fields         = get_resource_field_data($ref,true,!hook("customgetresourceperms"),null,($k!="" && !$internal_share_access),false);
    $fields_all     = isset($all_field_data[$ref]) ? $all_field_data[$ref] : get_resource_field_data($ref,false,!hook("customgetresourceperms"),null,($k!="" && !$internal_share_access),false);
    $access         = get_resource_access($resource);
    ?>
    <div class="RecordBox PushedRecordBox">
        <div class="RecordPanel PushedRecordPanel">
            <div class="backtoresults">&gt;<a href="view.php?ref=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo htmlspecialchars($lang["view"]) ?></a>
        </div>
        <div class="Title"><?php echo i18n_get_translated($resource["resource_type_name"]) . " : " . $resource["field" . $view_title_field] ?></div>
            <?php
            $GLOBALS["showing_pushed_metadata"] = true;
            include "view_metadata.php";
            $GLOBALS["showing_pushed_metadata"] = false;
            ?>
        </div>
        </div>
    <?php
    $ref        = $reset_ref;
    $access     = $reset_access;
    $fields_all = $reset_fields_all;
    }
/*
End of pushed metadata support
------------------------------------------
*/ 

if ($view_panels)
    { ?>
    <div class="RecordBox">
        <div class="RecordPanel">  
            <div id="Panel2" class="ViewPanel">
                <div id="Titles2" class="ViewPanelTitles"></div>
            </div>
        </div>
    </div>

    <?php
    if ($view_resource_collections)
        {
        # only render this box when needed
        ?>
        <div class="RecordBox">
            <div class="RecordPanel">  
                <div id="Panel3" class="ViewPanel">
                    <div id="Titles3" class="ViewPanelTitles"></div>
                </div>
            </div>

        </div>
<?php
        }
    }

// juggle $resource at this point as an unknown issue with render_actions used within a hook causes this variable to be reset
$resourcedata=$resource;
hook("custompanels"); //For custom panels immediately below resource display area 
$resource=$resourcedata;

// Show resource geolocation map.
if (
    !$disable_geocoding
    &&  (   // Only show the map if the resource is geocoded or they have the permission to geocode it.
            $edit_access
            || ($resource['geo_lat'] != '' && $resource['geo_long'] != '')
        )
    ) {
        include '../include/geocoding_view.php';
    }

if ($comments_resource_enable && ($k=="" || $internal_share_access))
    {
    include_once "../include/comment_resources.php";
    }

hook("w2pspawn");
// include collections listing
if ($view_resource_collections && !checkperm('b')){ ?>
    <div id="resourcecollections"></div>
    <script type="text/javascript">
    jQuery("#resourcecollections").load('<?php echo $baseurl ?>/pages/resource_collection_list.php?ref=<?php echo urlencode($ref)?>&k=<?php echo urlencode($k)?>'
    <?php
    if ($view_panels) {
    ?>
        , function() {

        let parent_element = jQuery('#<?php echo $modal ? 'modal' : 'CentralSpace'; ?>');
        parent_element.find("#AssociatedCollections").children(".Title").attr("panel", "AssociatedCollections").addClass("Selected").appendTo(parent_element.find("#Titles3"));
        removePanel=parent_element.find("#AssociatedCollections").parent().parent();
        parent_element.find("#AssociatedCollections").appendTo(parent_element.find("#Panel3")).addClass("TabPanel");
        removePanel.remove();

        parent_element.find("#CollectionsThemes").children().children(".Title").attr("panel", "CollectionsThemes").appendTo(parent_element.find("#Titles3"));
        removePanel=parent_element.find("#CollectionsThemes").parent().parent();
        parent_element.find("#CollectionsThemes").appendTo(parent_element.find("#Panel3")).addClass("TabPanel").hide();
        removePanel.remove();
        if (parent_element.find("#Titles2").children().length==0) parent_element.find("#Panel2").parent().parent().remove();
        if (parent_element.find("#Titles3").children().length==0) parent_element.find("#Panel3").parent().parent().remove();    
        jQuery(".ViewPanelTitles").children(".Title").click(function(){
        // function to switch tab panels
            parent_element.find(this).parent().parent().children(".TabPanel").hide();
            parent_element.find(this).parent().children(".Title").removeClass("Selected");
            parent_element.find(this).addClass("Selected");
            parent_element.find("#"+jQuery(this).attr("panel")).show();
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
            <h3 class="CollapsibleSectionHead collapsed"><?php echo htmlspecialchars($lang['metadata-report']); ?></h3>
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

// Show related resources section
if($enable_related_resources)
    {
    $relatedcontext = [
        "ref" => $ref,
        "k" => $k,
        "userref" => $userref,
        "internal_share_access" => $internal_share_access,
        "relatedresources" => $relatedresources,
        "related_resources_shown" => $related_resources_shown,
        "related_restypes" => $related_restypes,
        "relatedtypes_shown" => $relatedtypes_shown,  
        "edit_access" => $edit_access,         
        "urlparams" => $urlparams,        
        ];

    display_related_resources($relatedcontext);
    }
if ($show_related_themes==true )
    {
    // Public/featured collections
    $result=get_themes_by_resource($ref);
    if (count($result)>0) 
        {
        ?><!--Panel for related themes / collections -->
        <div class="RecordBox">
            <div class="RecordPanel">  
                <div id="CollectionsThemes">
                    <div class="RecordResource BasicsBox nopadding">
                        <div class="Title"><?php echo htmlspecialchars($lang["collectionsthemes"])?></div>
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
        }
    } 

if($enable_find_similar && checkperm('s') && ($k == '' || $internal_share_access))
    {
    ?>
    <!--Panel for search for similar resources-->
    <div class="RecordBox">
    <div class="RecordPanel"> 
    <div id="SearchSimilar">

    <div class="RecordResource">
    <div class="Title"><?php echo htmlspecialchars($lang["searchforsimilarresources"])?></div>

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

    $keywords=array_values(array_unique(get_resource_top_keywords($ref,50)));
    if (count($keywords)!=0)
        {
        for ($n=0;$n<count($keywords);$n++)
            {
            ?>
            <div class="SearchSimilar">
                <input 
                    type=checkbox 
                    id="<?php echo escape($context . "similar_search_" . $keywords[$n] . "_" . $n)  ?>" 
                    name="keyword_<?php echo escape($keywords[$n])?>" 
                    value="yes"
                    onClick="<?php echo escape($context) ?>UpdateFSResultCount();">
                    <label 
                        class="customFieldLabel" 
                        for="<?php echo escape($context . "similar_search_" . $keywords[$n] . "_" . $n)?>">
                        <?php echo htmlspecialchars(i18n_get_translated($keywords[$n]))?>
                    </label>
                </div>
            <?php
            }        
        ?>
        <div class="clearerleft"> </div>
        <br />
        <input name="search" type="submit" value="&nbsp;&nbsp;<?php echo escape($lang["searchbutton"])?>&nbsp;&nbsp;" id="<?php echo $context ?>dosearch"/>
        <iframe src="<?php echo $baseurl ?>/pages/blank.html" frameborder=0 scrolling=no width=1 height=1 style="visibility:hidden;" name="<?php echo $context ?>resultcount" id="<?php echo $context ?>resultcount"></iframe>
        </form>
        <?php
        }

    else
        {
        echo htmlspecialchars($lang["nosimilarresources"]); 
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
    <!-- End of Annotorious -->
    <?php
    }

if($GLOBALS["image_preview_zoom"])
    {
    ?>
    <script src="<?php echo $baseurl . LIB_OPENSEADRAGON; ?>/openseadragon.min.js?css_reload_key=<?php echo $css_reload_key; ?>"></script>
    <?php
    }
    ?>

<script>
    jQuery('document').ready(function(){
        /* Call SelectTab upon page load to select first tab*/
        SelectMetaTab(<?php echo $ref.",0,".($modal ? "true" : "false") ?>);
        registerCollapsibleSections(false);
    });
    jQuery('#previewimage').click(function(){
        window.location='#Header';
    }); 
</script>
<?php include "../include/footer.php";
