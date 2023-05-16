<?php
/**
 * This file contains the configuration settings that have been deprecated.
 * They will be removed in a future release and the code will operate in line with the default values set below and code to handle the non-default case(s) will be removed.
 * 
 * **** DO NOT ALTER THIS FILE! ****
 * 
 * If you need to change any of the below values, copy them to config.php and change them there, although as these options will be removed in a future release, this is not advised.
 */

# Ability to alter collection frame height
$collection_frame_height=153;

# Hide owner in list of public collections
$collection_public_hide_owner=true;

// Display help links on pages
$contextual_help_links=true;

# Set the Default Level for Custom Access. 
# This will only work for resources that haven't been set to custom previously, otherwise they will show their previously set values.
/*
	0 - Open
	1 - Restricted
	2 - Confidential
*/
$default_customaccess=2;

# Default home page (when not using themes as the home page).
# You can set other pages, for example search results, as the home page e.g.
# $default_home_page="search.php?search=example";
$default_home_page="home.php";

# add direct link to original file for each image size
$direct_link_previews = false;

# SECURITY WARNING: The next two options will  effectively allow anyone
# to download any resource without logging in. Be careful!!!!
// allow direct resource downloads without authentication
$direct_download_noauth = false;
// make preview direct links go directly to filestore rather than through download.php
// (note that filestore must be served through the web server for this to work.)
$direct_link_previews_filestore = false;

# Multi-lingual support for e-mails. Try switching this to true if e-mail links aren't working and ASCII characters alone are required (e.g. in the US).
$disable_quoted_printable_enc=false;

# edit.php - disable links to upload preview 
$disable_upload_preview = false;

# Disk Usage Warnings - require running check_disk_usage.php
# Percentage of disk space used before notification is sent out. The number should be between 1 and 100.
#$disk_quota_notification_limit_percent_warning=90;
# interval in hours to wait before sending another percent warning 
#$disk_quota_notification_interval=24;
$disk_quota_notification_email='';

# Default lifetime in days of a temporary download file created by the job queue. After this time it will be deleted by another job
$download_file_lifetime=14;

# For dynamic keyword list searching, perform logical AND instead of OR when selecting multiple options.
$dynamic_keyword_and = false;

# experimental email notification of php errors to $email_notify. 
$email_errors=false;
$email_errors_address="";

#enable user-to-user emails to come from user's address by default (for better reply-to), with the user-level option of reverting to the system address
$email_from_user=false;

# Do not create any new snapshots when recreating FFMPEG previews. (This is to aid in migration to mp4 when custom previews have been uploaded)
$ffmpeg_no_new_snapshots=false;

# Workflow states to ignore when verifying file integrity (to verify file integrity usign checksums requires $file_checksums_50k=false;)
$file_integrity_ignore_states = array();

# Display fields with display templates in their ordered position instead of at the end of the metadata on the view page.
$force_display_template_orderby=false;

# A list of upper/lower long/lat bounds, defining areas that will be excluded from geographical search results.
# Areas are defined using values in the following sequence: southwest lat, southwest long, northeast lat, northeast long
$geo_search_restrict=array
	(	
	# array(50,-3,54,3) # Example omission zone
	# ,array(-10,-20,-8,-18) # Example omission zone 2
	# ,array(1,1,2,2) # Example omission zone 3
    );

# Do not show any notification text if a password reset attempt fails to find a valid user. Setting this to false means potential hackers can discover valid email addresses
$hide_failed_reset_text=true;

# Highlight search keywords when displaying results and resources?
$highlightkeywords=true;

# embed the target preview profile?
$icc_preview_profile_embed=false;

# Experimental ImageMagic optimizations. This will not work for GraphicsMagick.
$imagemagick_mpr=false;

# Set the depth to be passed to mpr command.
$imagemagick_mpr_depth="8";

# Should colour profiles be preserved?
$imagemagick_mpr_preserve_profiles=true;

# If using imagemagick and mpr, specify any metadata profiles to be retained. Default setting good for ensuring copyright info is not stripped which may be required by law
$imagemagick_mpr_preserve_metadata_profiles=array('iptc');

// Option to automatically send a digest of all messages if a user has not logged on for the specified number of days
$inactive_message_auto_digest_period=7;

# By default, keyword relationships are two-way 
# (if "tiger" has a related keyword "cat", then a search for "cat" also includes "tiger" matches).
# $keyword_relationships_one_way=true means that if "tiger" has a related keyword "cat",
# then a search for "tiger" includes "tiger", but does not include "cat" matches.
$keyword_relationships_one_way=false;

# Prevent previews from creating versions that result in the same size?
# If true pre, thm, and col sizes will not be considered.
$lean_preview_generation=false;

# How many thumbnails to show in the collections panel until a 'View All...' link appears, linking to a search in the main window.
$max_collection_thumbs=150;

#Option to turn on metadata download in view.php.
$metadata_download=false;

// Ability to default metadata templates to a particular resource ID
$metadata_template_default_option = 0;

// Force selection of a metadata template
$metadata_template_mandatory = false;

# Enable multi-lingual free text fields
# By default, only the checkbox list/dropdown fields can be multilingual by using the special syntax when defining
# the options. However, setting the below to true means that free text fields can also be multi-lingual. Several text boxes appear when entering data so that translations can be entered.
$multilingual_text_fields=false;

# Force MySQL Strict Mode? (regardless of existing setting) - This is useful for developers so that errors that might only occur when Strict Mode is enabled are caught. Strict Mode is enabled by default with some versions of MySQL. The typical error caused is when the empty string ('') is inserted into a numeric column when NULL should be inserted instead. With Strict Mode turned off, MySQL inserts NULL without complaining. With Strict Mode turned on, a warning/error is generated.
$mysql_force_strict_mode = false;

# If true, it does not remove the backslash from DB queries, and doesn't do any special processing.
# to them. Unless you need to store '\' in your fields, you can safely keep the default.
$mysql_verbatim_queries = false;

# Normalize keywords when indexing and searching? Having this set to true means that various character encodings of e.g. diacritics will be standardised when indexing and searching. Requires internationalization functions (PHP versions >5.3). For example, there are several different ways of encoding "é" (e acute) and this will ensure that a standard form of "é" will always be used.
$normalize_keywords=true;

# Allow sorting by resource_type on thumbnail views
$order_by_resource_type=true;

# pager dropdown
$pager_dropdown=false;

# Should the resources that are in the archive state "User Contributed - Pending Review" (-1) be
# visible in the main searches (as with resources in the active state)?
# The resources will not be downloadable, except to the contributer and those with edit capability to the resource.
$pending_review_visible_to_all=false;

# Option to show a popup to users that upload resources to pending submission status. Prompts user to either submit for review or continue editing.
$pending_submission_prompt_review=true;

# Should the resources that are in the archive state "User Contributed - Pending submission" (-2) be
# searchable (otherwise users can search only for their own resources pending submission
$pending_submission_searchable_to_all=false;

# Prevent granting of open access if a user has edit permissions. Setting to true will allow group permissions ('e*' and 'ea*') to determine editability.
$prevent_open_access_on_edit_for_active=false;

# Allow a Preview page for entire collections (for more side to side comparison ability)
$preview_all=false;
# Minimize collections frame when visiting preview_all.php
$preview_all_hide_collections=true;

# Preview All default orientation ("v" for vertical or "h" for horizontal)
$preview_all_default_orientation="h";

# Show header and footer on resource preview page
$preview_header_footer=false;

# Allow unique quality settings for each preview size. This will use $imagemagick_quality as a default setting.
# If you want to adjust the quality settings for internal previews you must also set $internal_preview_sizes_editable=true
$preview_quality_unique=false;

$psd_transparency_checkerboard=false;

$public_collections_header_only=false; // show public collections page in header, omit from Themes and Manage Collections

# A list of extensions that QLPreview should NOT be used for.
$qlpreview_exclude_extensions=array("tif","tiff");

$random_sort=false;

# Separator to use when rendering date range field values
$range_separator = " / ";

#Size of the related resource previews on the resource page. Usually requires some restyling (#RelatedResources .CollectionPanelShell)
#Takes the preview code such as "col","thm"
$related_resource_preview_size="col";

# Select the field to display in searchcrumbs for a related search (defaults to filename)
# If this is set to a different field and the value is empty fallback to filename
$related_search_searchcrumb_field=51;

# Remove the line that separates collections panel menu from resources
$remove_collections_vertical_line=false;

# Show/ hide "Remove resources" link from collection bar:
$remove_resources_link_on_collection_bar = TRUE;

# Send confirmation emails to user when request sent or assigned
$request_senduserupdates=true;

# Option to force users to select a resource type at upload
$resource_type_force_selection=false;

# Specifies that searching will search all workflow states
# NOTE - does not work with $advanced_search_archive_select=true (advanced search status searching) as the below option removes the workflow selection altogether.
# IMPORTANT - this feature gets disabled when requests ask for a specific archive state (e.g. View deleted resources or View resources in pending review)
$search_all_workflow_states=false;

# When returning to search results from the view page via "all" link, bring user to result location of viewed resource?
$search_anchors=true;

# Highlight last viewed result when using $search_anchors
$search_anchors_highlight=false;

# Show an edit icon/link in the search results.
$search_results_edit_icon=true;

# whether field-specific keywords should include their shortnames in searchcrumbs (if $search_titles_searchcrumbs=true;) ex. "originalfilename:pdf"
$search_titles_shortnames=false;

# move search and clear buttons to bottom of searchbar
$searchbar_buttons_at_bottom=true;

# Enable list view option for search screen
$searchlist=true;

# Option to separate some resource types in searchbar selection boxes
$separate_resource_types_in_searchbar=Array();


# Always create a collection when sharing an individual resource via email
$share_resource_as_collection=false;

# Add option to include related resources when sharing single resource (creates a new collection)
$share_resource_include_related=false;

# Enable the 'edit all' function in the collection and search actions dropdowns
$show_edit_all_link = true;

// Show required field legend on upload
$show_required_field_label = true;

# Show the link to 'user contributed assets' on the My Contributions page
# Allows non-admin users to see the assets they have contributed
$show_user_contributed_resources=true;

# Set this to true in order for the top bar to remain present when scrolling down the page
$slimheader_fixed_position=false;

# Omit archived resources from get_smart_themes (so if all resources are archived, the header won't show)
# Generally it's not possible to check for the existence of results based on permissions,
# but in the case of archived files, an extra join can help narrow the smart theme results to active resources.
$smart_themes_omit_archived=false;

# Store Resource Refs when uploading, this is useful for other developer tools to hook into the upload.
$store_uploadedrefs=false;

# Suppress SQL information in the debug log?
$suppress_sql_log = false;

# display an alert icon next to the Admin link 
# and the relevant Admin item when there are requests that need managing
# only affects users with permissions to do this.
$team_centre_alert_icon = true;

# Normally, image tweaks are only applied to scr size and lower. 
# This could require recreating previews to sync up the various image rotations.
$tweak_all_images=false;
$tweak_allow_gamma=true;

# Allows Dash Administrators to have their own dash whilst all other users have the managed dash ($managed_home_dash must be on)
$unmanaged_home_dash_admins = false;

# Index the unnormalized keyword in addition to the normalized version, also applies to keywords with diacritics removed. Quoted search can then be used to find matches for original unnormalized keyword.
$unnormalized_index=false;

#Batch uploads - always upload to Default Collection
$upload_force_mycollection=false;

# Allow users to skip upload and create resources with no attached file
$upload_no_file=false;

# Option to allow users to 'lock' metadata fields in upload_then_edit_mode
$upload_review_lock_metadata = true;

// Set to TRUE to review resources based on resource ID (starting from most recent) when using upload then edit mode.
// Requires "$upload_then_edit = true;"
$upload_review_mode_review_by_resourceid = true;

# Show the fullname of the user who approved the account when editing user
$user_edit_approved_by=false;
# Also show the user email address if $user_edit_approved_by=true
$user_edit_approved_by_email=false;

# Show the fullname of the user who created the account when editing user
$user_edit_created_by=false;
# Also show the user email address if $user_edit_created_by=true
$user_edit_created_by_email=false;

# Allow user to remove their rating.
$user_rating_remove=true;
# play backwards (in development) - default 'j'
$video_playback_backwards=false;

# show video player in thumbs view 
$video_player_thumbs_view=false;

# use an ffmpeg alternative for search preview playback
$video_player_thumbs_view_alt=false;

# dynamicLabel: If true current label will be displayed in control bar. If false gear icon is displayed.
$videojs_resolution_selection_dynamicLabel=false;

# Default DPI setting for the view page if no resolution is stored in the db.
$view_default_dpi=300;

# Height of map in pixels on resource view page
$view_mapheight=200;

# Set to true if wildcard should also be prepended to the keyword
$wildcard_always_applied_leading = false;

# Should *all* manually entered keywords (e.g. basic search and 'all fields' search on advanced search) be treated as wildcards?
# E.g. "cat" will always match "catch", "catalogue", "category" with no need for an asterisk.
# WARNING - this option could cause search performance issues due to the hugely expanded searches that will be performed.
# It will also cause some other features to be disabled: related keywords and quoted string support
$wildcard_always_applied=false;

# Zip files - the contents of the zip file can be imported to a text field on upload.
# Requires 'unzip' on the command path.
# If the below is not set, but unzip is available, the archive contents will be written to $extracted_text_field
#
# $zip_contents_field=18;
$zip_contents_field_crop=1; # The number of lines to remove from the top of the zip contents output (in order to remove the filename field and other unwanted header information).