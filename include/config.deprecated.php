<?php
/**
 * This file contains the configuration settings that have been deprecated.
 * They will be removed in a future release and the code will operate in line with the default values set below and code to handle the non-default case(s) will be removed.
 * 
 * **** DO NOT ALTER THIS FILE! ****
 * 
 * If you need to change any of the below values, copy them to config.php and change them there, although as these options will be removed in a future release, this is not advised.
 */

// Option to automatically send a digest of all messages if a user has not logged on for the specified number of days
$inactive_message_auto_digest_period=7;

# How many thumbnails to show in the collections panel until a 'View All...' link appears, linking to a search in the main window.
$max_collection_thumbs=150;

# Option to show a popup to users that upload resources to pending submission status. Prompts user to either submit for review or continue editing.
$pending_submission_prompt_review=true;

# Prevent granting of open access if a user has edit permissions. Setting to true will allow group permissions ('e*' and 'ea*') to determine editability.
$prevent_open_access_on_edit_for_active=false;

$psd_transparency_checkerboard=false;

$public_collections_header_only=false; // show public collections page in header, omit from Themes and Manage Collections

# Select the field to display in searchcrumbs for a related search (defaults to filename)
# If this is set to a different field and the value is empty fallback to filename
$related_search_searchcrumb_field=51;

# Send confirmation emails to user when request sent or assigned
$request_senduserupdates=true;

# Specifies that searching will search all workflow states
# NOTE - does not work with $advanced_search_archive_select=true (advanced search status searching) as the below option removes the workflow selection altogether.
# IMPORTANT - this feature gets disabled when requests ask for a specific archive state (e.g. View deleted resources or View resources in pending review)
$search_all_workflow_states=false;

# whether field-specific keywords should include their shortnames in searchcrumbs (if $search_titles_searchcrumbs=true;) ex. "originalfilename:pdf"
$search_titles_shortnames=false;

# Index the unnormalized keyword in addition to the normalized version, also applies to keywords with diacritics removed. Quoted search can then be used to find matches for original unnormalized keyword.
$unnormalized_index=false;

// Set to TRUE to review resources based on resource ID (starting from most recent) when using upload then edit mode.
// Requires "$upload_then_edit = true;"
$upload_review_mode_review_by_resourceid = true;

# dynamicLabel: If true current label will be displayed in control bar. If false gear icon is displayed.
$videojs_resolution_selection_dynamicLabel=false;

# Height of map in pixels on resource view page
$view_mapheight=200;

# Zip files - the contents of the zip file can be imported to a text field on upload.
# Requires 'unzip' on the command path.
# If the below is not set, but unzip is available, the archive contents will be written to $extracted_text_field
#
# $zip_contents_field=18;
$zip_contents_field_crop=1; # The number of lines to remove from the top of the zip contents output (in order to remove the filename field and other unwanted header information).


# Disk Usage Warnings - requires running check_disk_usage.php
# Percentage of disk space used before notification is sent out. The number should be between 1 and 100.
# $disk_quota_notification_limit_percent_warning = 90;
# Interval in hours to wait before sending another percent warning 
# $disk_quota_notification_interval = 24;