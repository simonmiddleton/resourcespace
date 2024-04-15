<?php
/**
 * This file contains the configuration settings that have been deprecated.
 * They will be removed in a future release and the code will operate in line with the default values set below and code to handle the non-default case(s) will be removed.
 * 
 * **** DO NOT ALTER THIS FILE! ****
 * 
 * If you need to change any of the below values, copy them to config.php and change them there, although as these options will be removed in a future release, this is not advised.
 */

# Option to show a popup to users that upload resources to pending submission status. Prompts user to either submit for review or continue editing.
$pending_submission_prompt_review=true;

# Prevent granting of open access if a user has edit permissions. Setting to true will allow group permissions ('e*' and 'ea*') to determine editability.
$prevent_open_access_on_edit_for_active=false;

$psd_transparency_checkerboard=false;

$public_collections_header_only=false; // show public collections page in header, omit from Themes and Manage Collections

# Send confirmation emails to user when request sent or assigned
$request_senduserupdates=true;

# whether field-specific keywords should include their shortnames in searchcrumbs (if $search_titles_searchcrumbs=true;) ex. "originalfilename:pdf"
$search_titles_shortnames=false;

# Index the unnormalized keyword in addition to the normalized version, also applies to keywords with diacritics removed. Quoted search can then be used to find matches for original unnormalized keyword.
$unnormalized_index=false;

// Set to TRUE to review resources based on resource ID (starting from most recent) when using upload then edit mode.
// Requires "$upload_then_edit = true;"
$upload_review_mode_review_by_resourceid = true;




# Disk Usage Warnings - requires running check_disk_usage.php
# Percentage of disk space used before notification is sent out. The number should be between 1 and 100.
# $disk_quota_notification_limit_percent_warning = 90;
# Interval in hours to wait before sending another percent warning 
# $disk_quota_notification_interval = 24;