<?php
/**
 * This file contains the configuration settings that have been deprecated.
 * They will be removed in a future release and the code will operate in line with the default values set below and code to handle the non-default case(s) will be removed.
 * 
 * **** DO NOT ALTER THIS FILE! ****
 * 
 * If you need to change any of the below values, copy them to config.php and change them there, although as these options will be removed in a future release, this is not advised.
 */

 # Show an error when someone tries to request an account with an email already in the system.
# Hiding this error is useful if you consider this error to be a security issue (i.e. exposing that the email is linked to an account)
$account_email_exists_note=true;

 # "U" permission allows management of users in the current group as well as children groups. TO test stricter adherence to the idea of "children only", set this to true. 
$U_perm_strict=false;

# Send a confirmation e-mail to requester
$account_request_send_confirmation_email_to_requester = true;

# Allow a link to re-extract metadata per-resource (on the View Page) to users who have edit abilities.
$allow_metadata_revert=false;

# Allow users to delete resources?
# (Can also be controlled on a more granular level with the "D" restrictive permission.)
$allow_resource_deletion = true;

# Allow the addition of 'saved searches' to collections. 
$allow_save_search=true;

# Display resource title on alternative file management page
$alternative_file_resource_title=true;

# Always record the name of the resource creator for new records.
# If false, will only record when a resource is submitted into a provisional status.
$always_record_resource_creator = true;

// When using anonymous users, set to TRUE to allow anonymous users to add/ edit/ delete annotations
$annotate_crud_anonymous = false;

// The user can see existing annotations in read-only mode
$annotate_read_only = false;

# When anonymous access is on, show login in a modal.
$anon_login_modal=false;

# Place the default dash (tiles set for all_users) on the home page for anonymous users with none of the drag 'n' drop functionality.
$anonymous_default_dash=true;

$attach_user_smart_groups=true; //enable user attach to include 'smart group option', different from the default "users in group" method (which will still be available)

# Should the "Add to basket" function appear on the download sizes, so the size of the file required is selected earlier and stored in the basket? This means the total price can appear in the basket.
$basket_stores_size=true; 

// Default browse bar width;
$browse_default_width = 295;

$camera_autorotation_checked = true;

# Option to force single branch selection in category tree selection 
$cat_tree_singlebranch=false;

# Should the category tree status window be shown?
$category_tree_show_status_window=true;

# Use the 'chosen' library for rendering dropdowns (improved display and search capability for large dropdowns)
$chosen_dropdowns=false;

# The number of options that must be present before including seach capability.
$chosen_dropdowns_threshold_main=10;
$chosen_dropdowns_threshold_simplesearch=10;

# Use the 'chosen' library for rendering dropdowns in the collection bar.
$chosen_dropdowns_collection=false;

# The number of options that must be present before including seach capability for collection bar dropdowns.
$chosen_dropdowns_threshold_collection=10;

# Show clear button on the upload page
$clearbutton_on_upload=true;

# Show clear button on the edit page
$clearbutton_on_edit=true;

# Allow users to create new collections. Set to false to prevent creation of new collections.
$collection_allow_creation=true;

# Option to hide the collection bar (hidden, not minimised) if it has no resources in it
$collection_bar_hide_empty=false;

# Pop-out Collection Bar Upon Collection Interaction such as "Select Collection"
$collection_bar_popout=false;

# Option to replace the collection actions dropdown with a simple 'download' link if collection_download is enabled
$collection_download_only = false;

# add user and access information to collection results in the collections panel dropdown
# this extends the width of the dropdown and is intended to be used with $collections_compact_style
# but should also be compatible with the traditional collections tools menu.
$collection_dropdown_user_access_mode=false;

# Option to remove all resources from the current collection once it has been requested
$collection_empty_on_submit=false;

# Ability to alter collection frame height/width
$collection_frame_divider_height=3;
$collection_frame_height=153;

# add a prefix to all collection refs, to distinguish them from resource refs
$collection_prefix = "";

# Hide owner in list of public collections
$collection_public_hide_owner=true;

# Add the collections footer
$collections_footer = true;

# trim characters - will be removed from the beginning or end of the string, but not the middle
# when indexing. Format for this argument is as described in PHP trim() documentation.
# leave blank for no extra trimming.
$config_trimchars="";

# Enable work-arounds required when installed on Microsoft Windows systems (rarely used in the code)
$config_windows=false;

// Display help links on pages
$contextual_help_links=true;

/*
* Dash tile color picker/ selector
* If $dash_tile_colour = true and there are no colour options, a colour picker (jsColor) will be used instead
* Example of colour options array:
* $dash_tile_colour_options = array('0A8A0E' => 'green', '0C118A' => 'blue');
*/
$dash_tile_colour         = true;
$dash_tile_colour_options = array();
/* End Dash Config Options */

// Option to have the front end show pop up error when and invalid date value or format is entered e.g. 31-02-2020 or bad partial dates, this configuration could be removed once a more subtle way of erroring this is found.
$date_validator=false;

# Set to true to see the download iframe for debugging purposes.
$debug_direct_download=false; 

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


# Specify field references for fields that you do not wish the blank default entry to appear for, so the first keyword node is selected by default.
# e.g. array(3,12);
$default_to_first_node_for_fields=array();

# What is the default value for the user select box, for example when e-mailing resources?
$default_user_select="";

# Show and allow to remove custom access for users when editing a resource
$delete_resource_custom_access = false;

# add direct link to original file for each image size
$direct_link_previews = false;

# SECURITY WARNING: The next two options will  effectively allow anyone
# to download any resource without logging in. Be careful!!!!
// allow direct resource downloads without authentication
$direct_download_noauth = false;
// make preview direct links go directly to filestore rather than through download.php
// (note that filestore must be served through the web server for this to work.)
$direct_link_previews_filestore = false;

$disable_alternative_files = false;

# Don't display the link to toggle thumbnails in collection frame
$disable_collection_toggle=false;

# Multi-lingual support for e-mails. Try switching this to true if e-mail links aren't working and ASCII characters alone are required (e.g. in the US).
$disable_quoted_printable_enc=false;

# edit.php - disable links to upload preview 
$disable_upload_preview = false;

# The following can be set to show a custom message for disabled plugins. Default is the language string 'plugins-disabled-plugin-message' but this will override it.
$disabled_plugins_message = "";

# Disk Usage Warnings - require running check_disk_usage.php
# Percentage of disk space used before notification is sent out. The number should be between 1 and 100.
#$disk_quota_notification_limit_percent_warning=90;
# interval in hours to wait before sending another percent warning 
#$disk_quota_notification_interval=24;
$disk_quota_notification_email='';

# Make dropdown selectors for Display and Results Display menus
$display_selector_dropdowns=false;

# When displaying title of the resource, set the following to true if you want to show Upload resources or Edit resource when on edit page:
$distinguish_uploads_from_edits=false;

# Default lifetime in days of a temporary download file created by the job queue. After this time it will be deleted by another job
$download_file_lifetime=14;

# For dynamic keyword list searching, perform logical AND instead of OR when selecting multiple options.
$dynamic_keyword_and = false;

# experimental email notification of php errors to $email_notify. 
$email_errors=false;
$email_errors_address="";

##  The URL that goes in the bottom of the 'emaillogindetails' template (save_user function in general.php)
##  If blank, uses $baseurl 
$email_url_save_user = ""; //emaillogindetails

# Enable remote apis - MUST ALWAYS BE TRUE now as parts of the UI use the API.
$enable_remote_apis=true;

$enable_theme_breadcrumbs = true;

# Require email address to be entered when users are submitting collecion feedback
$feedback_email_required=true;

# Do not create any new snapshots when recreating FFMPEG previews. (This is to aid in migration to mp4 when custom previews have been uploaded)
$ffmpeg_no_new_snapshots=false;

# Workflow states to ignore when verifying file integrity (to verify file integrity usign checksums requires $file_checksums_50k=false;)
$file_integrity_ignore_states = array();

# Force fields with display templates to obey "order by" numbering.
$force_display_template_order_by=false;
# Display fields with display templates in their ordered position instead of at the end of the metadata on the view page.
$force_display_template_orderby=false;

# Show geographical search results in a modal
$geo_search_modal_results = true;

# A list of upper/lower long/lat bounds, defining areas that will be excluded from geographical search results.
# Areas are defined using values in the following sequence: southwest lat, southwest long, northeast lat, northeast long
$geo_search_restrict=array
	(	
	# array(50,-3,54,3) # Example omission zone
	# ,array(-10,-20,-8,-18) # Example omission zone 2
	# ,array(1,1,2,2) # Example omission zone 3
    );
    
# Set cookies at root (for now, this is implemented for the colourcss cookie to preserve selection between pages/ team/ and plugin pages)
# probably requires the user to clear cookies.
$global_cookies=false;

# Simpler search in header, expanding for the full box.
# Work in progress - in development for larger ResourceSpace 9.0 release. Some functions may not work currently.
$header_search=false;

#replace header logo with text, application name and description
$header_text_title=false;

#Batch Uploads, do not display hidden collections
$hidden_collections_hide_on_upload=false;
#Batch Uploads, include show/hide hidden collection toggle. Must have $hidden_collections_hide_on_upload=true;
$hidden_collections_upload_toggle=false;

#collection_public.php - hide 'access' column
$hide_access_column_public = false;
#collection_manage.php - hide 'access' column
$hide_access_column = false;

# Hide "Generate URL" from the collection_share.php page?
$hide_collection_share_generate_url=false;

# Do not show any notification text if a password reset attempt fails to find a valid user. Setting this to false means potential hackers can discover valid email addresses
$hide_failed_reset_text=true;

# Hide "Generate URL" from the resource_share.php page?
$hide_resource_share_generate_url=false;

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

# Index the resource type, so searching for the resource type string will work (e.g. if you have a resource of type "photo" then "cat photo" will match even if the resource metadata itself doesn't contain the word 'photo')
$index_resource_type=true;

# If ResourceSpace is behind a proxy, enabling this will mean the "X-Forwarded-For" Apache header is used
# for the IP address. Do not enable this if you are not using such a proxy as it will mean IP addresses can be
# easily faked.
$ip_forwarded_for=false;

# Show friendly error to user instead of 403 if IP not in permitted range.
$iprestrict_friendlyerror=false;

# By default, keyword relationships are two-way 
# (if "tiger" has a related keyword "cat", then a search for "cat" also includes "tiger" matches).
# $keyword_relationships_one_way=true means that if "tiger" has a related keyword "cat",
# then a search for "tiger" includes "tiger", but does not include "cat" matches.
$keyword_relationships_one_way=false;

# A list of groups for which the knowledge base will launch on login, until dismissed.
$launch_kb_on_login_for_groups=array();

# Prevent previews from creating versions that result in the same size?
# If true pre, thm, and col sizes will not be considered.
$lean_preview_generation=false;

# Use a file tree display for local folder upload
$local_upload_file_tree=false;

# if using $collections_compact_style, you may want to remove the contact sheet link from the Manage Collections page
$manage_collections_contact_sheet_link=true;

# How many thumbnails to show in the collections panel until a 'View All...' link appears, linking to a search in the main window.
$max_collection_thumbs=150;

// maximum number of words shown before more/less link is shown (used in resource log)
$max_words_before_more=30;

#Add full username column in my messages/actions pages
$messages_actions_fullname = true;

#Option to turn on metadata download in view.php.
$metadata_download=false;

# Custom logo to use when downloading metadata in PDF format
$metadata_download_header_title = 'ResourceSpace';
#$metadata_download_pdf_logo     = '/path/to/logo/location/logo.png';
$metadata_download_footer_text  = '';

// Ability to default metadata templates to a particular resource ID
$metadata_template_default_option = 0;

// Force selection of a metadata template
$metadata_template_mandatory = false;

# Enable multi-lingual free text fields
# By default, only the checkbox list/dropdown fields can be multilingual by using the special syntax when defining
# the options. However, setting the below to true means that free text fields can also be multi-lingual. Several text boxes appear when entering data so that translations can be entered.
$multilingual_text_fields=false;

# Hide mycontributions link from regular users
$mycontributions_userlink=true;

# Force MySQL Strict Mode? (regardless of existing setting) - This is useful for developers so that errors that might only occur when Strict Mode is enabled are caught. Strict Mode is enabled by default with some versions of MySQL. The typical error caused is when the empty string ('') is inserted into a numeric column when NULL should be inserted instead. With Strict Mode turned off, MySQL inserts NULL without complaining. With Strict Mode turned on, a warning/error is generated.
$mysql_force_strict_mode = false;

# If true, it does not remove the backslash from DB queries, and doesn't do any special processing.
# to them. Unless you need to store '\' in your fields, you can safely keep the default.
$mysql_verbatim_queries = false;

# Normalize keywords when indexing and searching? Having this set to true means that various character encodings of e.g. diacritics will be standardised when indexing and searching. Requires internationalization functions (PHP versions >5.3). For example, there are several different ways of encoding "é" (e acute) and this will ensure that a standard form of "é" will always be used.
$normalize_keywords=true;


$notify_user_contributed_unsubmitted=false;

