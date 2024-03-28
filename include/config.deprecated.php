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

# Option to force users to select a resource type at upload
$resource_type_force_selection=false;

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
