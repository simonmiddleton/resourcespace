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

# Option to show a popup to users that upload resources to pending submission status. Prompts user to either submit for review or continue editing.
$pending_submission_prompt_review=true;

# Prevent granting of open access if a user has edit permissions. Setting to true will allow group permissions ('e*' and 'ea*') to determine editability.
$prevent_open_access_on_edit_for_active=false;

# Allow a Preview page for entire collections (for more side to side comparison ability)
$preview_all=false;

# Preview All default orientation ("v" for vertical or "h" for horizontal)
$preview_all_default_orientation="h";

$psd_transparency_checkerboard=false;

$public_collections_header_only=false; // show public collections page in header, omit from Themes and Manage Collections

$random_sort=false;

# Select the field to display in searchcrumbs for a related search (defaults to filename)
# If this is set to a different field and the value is empty fallback to filename
$related_search_searchcrumb_field=51;

# Send confirmation emails to user when request sent or assigned
$request_senduserupdates=true;

# Option to force users to select a resource type at upload
$resource_type_force_selection=false;

# Specifies that searching will search all workflow states
# NOTE - does not work with $advanced_search_archive_select=true (advanced search status searching) as the below option removes the workflow selection altogether.
# IMPORTANT - this feature gets disabled when requests ask for a specific archive state (e.g. View deleted resources or View resources in pending review)
$search_all_workflow_states=false;

# whether field-specific keywords should include their shortnames in searchcrumbs (if $search_titles_searchcrumbs=true;) ex. "originalfilename:pdf"
$search_titles_shortnames=false;

# move search and clear buttons to bottom of searchbar
$searchbar_buttons_at_bottom=true;

# Enable list view option for search screen
$searchlist=true;

# Suppress SQL information in the debug log?
$suppress_sql_log = false;

# Index the unnormalized keyword in addition to the normalized version, also applies to keywords with diacritics removed. Quoted search can then be used to find matches for original unnormalized keyword.
$unnormalized_index=false;

// Set to TRUE to review resources based on resource ID (starting from most recent) when using upload then edit mode.
// Requires "$upload_then_edit = true;"
$upload_review_mode_review_by_resourceid = true;

# dynamicLabel: If true current label will be displayed in control bar. If false gear icon is displayed.
$videojs_resolution_selection_dynamicLabel=false;

# Height of map in pixels on resource view page
$view_mapheight=200;

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

# When using $prefix_resource_id_to_filename above, what string should be used prior to the resource ID?
# This is useful to establish that a resource was downloaded from ResourceSpace and that the following number
# is a ResourceSpace resource ID.
$prefix_filename_string="RS";

# When $original_filenames_when_downloading, should the original filename be prefixed with the resource ID?
# This ensures unique filenames when downloading multiple files.
# WARNING: if switching this off, be aware that when downloading a collection as a zip file, a file with the same name as another file in the collection will overwrite that existing file. It is therefore advisiable to leave this set to 'true'.
$prefix_resource_id_to_filename=true;

# Should the download filename have the size appended to it?
$download_filenames_without_size = false;

#Option for downloaded filename to be just <resource id>.extension, without indicating size or whether an alternative file. Will override $original_filenames_when_downloading which is set as default
$download_filename_id_only = false;

# Append the size to the filename when downloading
# Required: $download_filename_id_only = true;
$download_id_only_with_size = false;

# Use original filename when downloading a file?
$original_filenames_when_downloading=true;

# Option to select metadata field that will be used for downloaded filename (do not include file extension)
#$download_filename_field=8;

# Encode preview asynchronous?
# REQUIRES: $php_path
# Deprecated as there are now much better options for offline video processing
$ffmpeg_preview_async=false;
