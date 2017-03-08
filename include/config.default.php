<?php
/**
 ************************************************************
 ************************************************************
 *****                                                  *****
 *****                                                  *****
 *****                !!! IMPORTANT !!!                 *****
 *****                                                  *****
 *****                                                  *****
 *****              DO NOT ALTER THIS FILE              *****
 *****                                                  *****
 ************************************************************
 ************************************************************
      
 This file contains the default ResourceSpace configuration settings.

 If you need to change any of the values below, copy them to config.php
 and change them there. This file will be overwritten when you upgrade
 and ensures that any new configuration options are set to a sensible
 default value.

 */
include "version.php";

# ResourceSpace configuration parameters are sorted by major function with section title headers in ALL CAPS.
# The parameter is listed first ($parameter=xxx;) ending with a semicolon, then comment text ‘#’ describing the parameter with default values provided.
# Where values equal ‘true’ or ‘false’, comment ends with a question mark ‘?’.
# Where values come from a list, values or examples are enclosed with brackets ‘[]’ that do not get entered.

#----------BASE RESOURCESPACE SETTINGS----------
# BASE RESOURCESPACE SETTINGS
$baseurl="http://my.site/resourcespace"; # The 'base' web address for this installation with no trailing slash.
$applicationname="ResourceSpace"; # Name of your implementation/installation, such as [“MyCompany Resource System”].
$applicationdesc=""; # Subtitle (i18n translated) if $header_text_title=true; # Subtitle text describing the installation, such as [“Photograph Archive”].
$scramble_key="abcdef123"; # Security key value, set the scramble key to be a hard-to-guess string (similar to a password), to disable, set to the empty string [“”]. 
$enable_remote_apis=true; # Enable remote APIs (if using API, RSS2, or other plugins that allow remote authentication via an API key)?
$api_scramble_key="abcdef123"; # API scramble key.
$send_statistics=true; # Send occasional statistics to Montala? The number of resources and users will be sent every 7 days (total number of installations, users, and resources).
$config_windows=false; # Enable work arounds required when installed on Microsoft Windows systems?
$team_centre_bug_report=false; # Show bug report link in system settings?

# MySQL DATABASE SERVER CONNECTION SETTINGS
$mysql_server="localhost"; # MySQL database server name, use 'localhost' if MySQL is installed on the same server as your web server.
$mysql_username="root";	# MySQL database username.
$mysql_password=""; # MySQL database password.
$mysql_db="resourcespace"; # MySQL database name.
$mysql_bin_path="/usr/bin"; # MySQL client binaries path with no trailing slash, such as mysqldump (needed if you use the export tool).
$use_mysqli=function_exists("mysqli_connect"); # Use php-mysqli extension for interfacing with the mysql database?
$use_mysqli_prepared=$use_mysqli && false; # Use prepared statements (default is false until technology proven)?

# MySQL DATABASE SERVER SETTINGS
#$mysql_charset="utf8"; # MySQL database connection character set.
$mysql_force_strict_mode=false; # Force MySQL Strict Mode regardless of existing setting? 
$mysql_verbatim_queries=false; # Remove the backslash from DB queries? Unless you need to store '\' in your fields, you can safely keep the default.
$mysql_log_transactions=false; # Record important DB transactions (e.g. INSERT, UPDATE, DELETE) in a SQL file to allow replaying of changes since DB was last backed up? 
#$mysql_log_location="/var/resourcespace_backups/sql_log.sql"; # MySQL log path, ensure the log location is not in a web accessible directory.

# RESOURCESPACE DEPENDENCY PATH SETTINGS
# $php_path="/usr/bin"; # PHP path to run certain actions asynchronous (eg. preview transcoding).
# $imagemagick_path='/sw/bin'; # ImageMagick/GraphicsMagick path for graphics file processing.
# $ghostscript_path='/sw/bin'; # Ghostscript path for PDF file processing.
$ghostscript_executable='gs'; # Ghostscript executable name.
# $ffmpeg_path='/usr/bin'; # FFMpeg path for video file decoding.
# $exiftool_path='/usr/local/bin'; # Exiftool path for file metadata read/write.
# $pdftotext_path='/usr/bin'; # PDFtoText path for extraction of text from PDF files.

# RESOURCESPACE OPTIONAL DEPENDENCY PATH SETTINGS
#$archiver_path='/usr/bin'; # Archiving tool path: Linux with zip or 7z: ['/usr/bin’], Linux with tar: ['/bin’], Windows with 7z: ['C:\Program\7-Zip’].
# $antiword_path='/usr/bin'; # Antiword path for text extraction and indexing of Microsoft Word files.
# $blender_path='/usr/bin/'; # Blender path.
#$qtfaststart_path="/usr/bin"; # qt-faststart path, to make video MP4 previews start faster.
#$qtfaststart_extensions=array("mp4","m4v","mov"); # Video extensions that will use qt-faststart.
#$calibre_path="/usr/bin"; # Calibre path to allow ebook conversion to PDF.
#$unoconv_path="/usr/bin"; # Unoconv (a python-based bridge to OpenOffice) path to allow document conversion to PDF.
#$dump_gnash_path="/usr/local/gnash-dump/bin"; # If compiled (gnash w/o gui), SWF previews are possible. 

# RESOURCESPACE OPTIONAL QUICKLOOK (MACOS) DEPENDENCY SETTINGS
#$qlpreview_path="/usr/bin"; # QuickLook path, attempt to produce a preview for files using MacOS builtin QuickLook system which supports multiple files, requires at >=v0.2 of 'qlpreview’.
$qlpreview_exclude_extensions=array("tif","tiff"); # List of extensions that QLPreview should not be used for.

# SERVER SETTINGS
$php_time_limit=300; # PHP execution time limit, default is 5 minutes.
$ip_forwarded_for=false; # Enable "X-Forwarded-For" Apache header if ResourceSpace is behind a proxy? Do not enable if not using a proxy as IP addresses can be easily faked.
$cron_job_time_limit=1800; # Cron jobs maximum execution time limit, default is 30 minutes.
$image_rotate_reverse_options=false; # Reverse image rotation (on some PHP installs, the imagerotate() function is wrong)?
$ajax_loading_timer=1500; # Length if time in milliseconds the loading popup appears during an ajax request.
$purge_temp_folder_age=0; # Option to empty the temp folder of old files when it is creating new temporary files [0]=off, ensure IIS/Apache service account does not have write access to the whole server.
$download_file_lifetime=14; # Default lifetime in days of a temporary download file created by the job queue; afterwards it will be deleted by another job.
#$server_charset=''; # Server charset needed when dealing with filenames in some situations, at collection download [‘UTF-8', 'ISO-8859-1’, 'Windows-1252’].

# SERVER OFFLINE PROCESSING SETTINGS
$process_locks_max_seconds=60*60*4; # For offline process (staticsync and create_previews.php) locking, age of a lock before it is ignored (4 hr default).
$offline_job_queue=false; # Enable offline job queue functionality for resource heavy tasks to run offline and send notifications once complete.
$offline_job_delete_completed=false; # Delete completed jobs from the queue?

# ZIP ARCHIVER SETTINGS
#$archiver_executable='zip'; # Archiver utility path (set $collection_download = true;), here for Linux with zip utility.
#$archiver_listfile_argument="-@ <"; # Archiver parameters, here for Linux with zip utility.

# Example for Linux with the 7z utility:
# $archiver_executable = '7z';
# $archiver_listfile_argument = "@";
# Example for Windows with the 7z utility:
# $archiver_executable = '7z.exe';
# $archiver_listfile_argument = "@";

# FILE STORAGE SETTINGS
#$storagedir="/path/to/filestore"; # Path to filestore with no trailing slash, can be absolute [“/var/www/blah/blah”] or relative to the installation.
#$storageurl="http://my.storage.server/filestore"; # URL path to storagedir, no trailing slash, can be absolute [“http://files.example.com”] or relative to the installation.
$originals_separate_storage=false; # Store original files separately from previews? If true with existing resources, run /pages/tools/filestore_separation.php.

# FILE STORAGE TEMPLATE SETTINGS
# FSTemplate, allows a system to contain an initial batch of resources that are stored elsewhere and read only.
$fstemplate_alt_threshold=0; # Applies to resource IDs below this number only. Set the system so the user created resources start at 1000.
$fstemplate_alt_storagedir=""; # Alternative filestore folder path location for the sample files. The location of the template installation.
$fstemplate_alt_storageurl=""; # Alternative filestore url location for the sample files.
$fstemplate_alt_scramblekey=""; # The scramble key used by the template installation, so paths must be scrambled using this instead for the sample images.

# DISK SPACE SETTINGS
#$disksize=150; # Quota size of disk space used by application in GB; for Unix systems only.
#$disk_quota_notification_limit_percent_warning=90; # Percentage of disk space (1 to 100) used before notification is sent, requires running check_disk_usage.php.
#$disk_quota_notification_interval=24; # Interval in hours to wait before sending another disk quota notification.
#$disk_quota_notification_email=''; # Email address disk quota notification is sent to.
#$disk_quota_limit_size_warning_noupload=10; # Disk space in GB left before uploads are disabled, this causes disk space to be checked before each upload.

# FILE TRANSFER PROTOCOL (FTP) SETTINGS
# FTP settings for batch upload only necessary if you plan to use the FTP upload feature.
$ftp_server="my.ftp.server"; # FTP server name.
$ftp_username="my_username"; # FTP username.
$ftp_password="my_password"; # FTP user password.
$ftp_defaultfolder="temp/"; # FTP default upload folder name.
$local_ftp_upload_folder='upload/'; # Location of the local upload folder, so that it is not in the web visible path, relative and absolute paths allowed.

# LANGUAGE SETTINGS
$defaultlanguage="en"; # Default language, uses ISO 639-1 language codes below.  If $defaultlanguage is not set, the browser's default will be used.
$languages["en"]="British English";
$languages["en-US"]="American English";
$languages["ar"]="العربية"; # Arabic
$languages["id"]="Bahasa Indonesia"; # Indonesian
$languages["ca"]="Català"; # Catalan
$languages["zh-CN"]="简体字"; # Simplified Chinese
$languages["da"]="Dansk"; # Danish
$languages["de"]="Deutsch"; # German
$languages["el"]="Ελληνικά"; # Greek
$languages["es"]="Español"; # Spanish
$languages["es-AR"]="Español (Argentina)"; # Argentinian Spanish
$languages["fr"]="Français"; # French
$languages["hr"]="Hrvatski"; # Croatian
$languages["it"]="Italiano"; # Italian
$languages["jp"]="日本語"; # Japanese
$languages["nl"]="Nederlands"; # Dutch
$languages["no"]="Norsk"; # Norwegian
$languages["pl"]="Polski"; # Polish
$languages["pt"]="Português"; # Portuguese
$languages["pt-BR"]="Português do Brasil"; # Brazilian Portuguese
$languages["ru"]="Русский язык"; # Russian
$languages["fi"]="Suomi"; # Finnish
$languages["sv"]="Svenska"; # Swedish
$disable_languages=false; # Disable language selection options, includes browser detection for language?
$show_language_chooser=true; # Show the language chooser on the bottom of each page?
$browser_language=true; # Allow browser language detection?
$multilingual_text_fields=false; # Enable multi-lingual free text fields? 

#----------ADDITIONAL BASE RESOURCESPACE SETTINGS----------
# FILE CHECKSUM SETTINGS
$file_checksums=false; # Create file checksums?
$file_checksums_50k=true; # Calculate checksums on first 50k and size if true or on the full file if false?
$file_upload_block_duplicates=false; # Block duplicate files based on checksums (performance impact)? May not work with $file_checksums_offline=true, unless script is run frequently. 
$file_checksums_offline=true; # Create checksums not in real time with background cron job for large files, since the checksums can take time?

# WEB BROWSER SETTINGS
$xframe_options="SAMEORIGIN"; # Set to [“DENY”] (prevent all), [“SAMEORIGIN”] or [“ALLOW-FROM”] with a URL to allow site to be used in an iframe, disable [“”].
$global_cookies=false; # Set cookies at root (implemented for the colourcss cookie to preserve selection between pages/ team/ and plugin pages), probably requires the user to clear cookies?
$download_no_session_cache_limiter=false; # Set to true to prevent possible issues with IE and download.php. Found an issue with a stray pragma: no-cache header that seemed to be added by SAML SSO solution.

# WEB SPIDER SETTINGS
$spider_password="TBTT6FD"; # Password required for spider.php, randomize for each new installation as your resources will be readable by anyone that knows this password.
$spider_usergroup=2; # User group that will be used to access the resource list for the spider index.
$spider_access=array(0,1); # Access level(s) required when producing the index [0]=Open, [1]=Restricted, [2]=Confidential/Hidden].

# RESOURCESPACE PLUGIN SETTINGS
$use_plugins_manager=true; # Use system plugin manager?
$enable_plugin_upload=true; # Allow online plugin upload?

# RESOURCESPACE LOGGING SETTINGS
$debug_log=false; # Log developer debug information to the debug log (/filestore/tmp/debug.txt)?  
#$debug_log_location="/var/log/resourcespace/resourcespace.log"; # Optional debug log location, specify a full path to debug file. Ensure folder permissions allow write access by web service account.
$log_resource_views=false; # Should resource views be logged for detailed reporting purposes (general daily statistics for each resource are logged anyway)?
$max_words_before_more=30; # Maximum number of words shown before more/less link is shown (used in resource log).

# RESOURCESPACE ERROR HANDLING SETTINGS
$show_error_messages=true; # Hide error messages?
$email_errors=false; # Enable experimental email notification of php errors to $email_errors_address=?
$email_errors_address=""; # Address where PHP errors are sent.
$system_down_redirect=false; # Enable a system down message to all users?

# RESOURCESPACE DEBUGGING SETTINGS
$include_rs_header_info=true; # Include ResourceSpace version header in page View Source?
$config_show_performance_footer=false; # Show the performance metrics in the footer for debug?

# ADMINISTRATIVE SPECIAL SETTINGS
$system_architect_user_names=array('admin'); # List of users that can update very low level configuration options, for example debug_log? Setting for experienced technical users.
$team_centre_alert_icon=true; # Display an alert icon next to the System Setup link and relevant item when there are requests that need managing?
$execution_lockout=false; # Execution lockout mode, prevents entry of PHP even to admin users (e.g. config overrides and upload of new plugins), useful on shared/multi-tenant systems.
$site_text_custom_create=false; # Allow for creation of new site text entries from Manage Content (intended for developers who create custom pages or hooks)?
$web_config_edit=false; # Enable web-based config.php editing, using CodeMirror for highlighting (make config.php writable, use caution as broken syntax requires server side editing to fix)?
$body_classes=array(); # Initialize array for classes to be added to <body> element.
$admin_header_height=120; # Header height for tall theme header than standard to still be fully visible in System Setup. 
$team_user_filter_top=false; # Show group filter and user search at top of team_user.php?

# XML METADATA DUMP FILE SETTINGS
$xml_metadump=true; # Create XML metadata dump files in the resource folder? 
$xml_metadump_dc_map=array
	(
	"title" => "title",
	"caption" => "description",
	"date" => "date"
	); # Configure mapping between metadata and Dublin Core fields, which are used in the XML metadata dump instead if a match is found.

# RESOURCESPACE REPORTING SETTINGS
$reporting_periods_default=array(7,30,100,365); # Array of reporting periods, in days.
$resource_hit_count_on_downloads=false; # Use hit count functionality to track downloads rather than resource views?
$show_hitcount=false; # Show hit count?

#----------FORMATTING SETTINGS----------
# DATE AND TIME FORMATTING SETTINGS
$minyear=1980; # Four digit year of the earliest resource record, used for the date selector on the search form.
if (function_exists("date_default_timezone_set")) {date_default_timezone_set("GMT");} # Set local time zone (default GMT).
$date_d_m_y=true; # Use day-month-year format instead of month-day-year?
$date_yyyy=false; # Display year in a four digit format?

# USER DISPLAY SETTINGS
$responsive_ui=true; # Enable responsive user interface?
$retina_mode=false; # Enable Retina mode, use next size up when rendering previews/thumbs for a more crisp display on high resolution screens, uses more bandwidth?

# LISTS ON PAGE SETTINGS
$list_display_array=array(15,30,60); # Number of results to display for lists (user admin, public collections, manage collections)?
$default_perpage_list=15; # How many default list results per page?
    
# TABBED VIEW SETTINGS
$use_order_by_tab_view=false; # Use the new tab ordering system, sorted by the order set in System Setup?
$view_panels=false; # Show tabbed panels in view? Metadata, Location, Comments, Related Collection, Related Galleries, and Related Resources, Search for Similar are grouped.

# CHECKBOX SETTINGS
$auto_order_checkbox=true; # Automatically order checkbox lists alphabetically?
$auto_order_checkbox_case_insensitive=false; # Use a case insensitive sort when automatically order checkbox lists alphabetically?
$checkbox_ordered_vertically=true; # Order checkbox lists vertically (as opposed to horizontally, as HTML tables normally work)?
$use_checkboxes_for_selection=false; # Use checkboxes for selecting resources?

# DROPDOWN SETTINGS
$display_selector_dropdowns=false; # Make dropdown selectors for Display and Results Display menus?
$perpage_dropdown=true; # Allow per-page dropdown without $display_selector_dropdown=true (useful when using display selector icons with per-page dropdowns)?
$pager_dropdown=false; # Enable pager dropdown?

# GLOBAL FORMATTING SETTINGS
$imperial_measurements=false; # Use imperial instead of metric for the download size guidelines?
$show_resource_title_in_titlebar=false; # Show title of the resource being viewed in the browser title bar?
$remove_collections_vertical_line=false; # Remove the line that separates collections panel menu from resources?
$force_display_template_order_by=false; # Force fields with display templates to obey "order by" numbering?
$default_to_first_node_for_fields=array(); # References for fields the blank default entry will not appear, first keyword node is selected by default.

# CKEDITOR SITE EDITING SETTINGS
$enable_ckeditor=true; # Enable CKEditor for edit boxes?
$site_text_use_ckeditor=false; # Enable CKEditor for site content?
$ckeditor_toolbars="'Styles', 'Bold', 'Italic', 'Underline','FontSize', 'RemoveFormat', 'TextColor','BGColor'"; # Available CKEditor toolbars.
$ckeditor_content_toolbars="
	{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','RemoveFormat' ] },
	{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','-','Undo','Redo' ] },
	{ name: 'styles', items : [ 'Format' ] },
	{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
	{ name: 'links', items : [ 'Link','Unlink' ] },
	{ name: 'insert', items : [ 'Image','HorizontalRule'] },
	{ name: 'tools', items : [ 'Source', 'Maximize' ] }
";

#----------USER PASSWORD SETTINGS----------
# USER PASSWORD SECURITY SETTINGS
$password_min_length=7; # Minimum length of password in characters.
$password_min_alpha=1; # Minimum number of alphabetical characters (a-z, A-Z) in any case.
$password_min_numeric=1; # Minimum number of numeric characters (0-9).
$password_min_uppercase=0; # Minimum number of upper case alphabetical characters (A-Z).
$password_min_special=0; # Minimum number of 'special' i.e. non alphanumeric characters (!@$%& etc.).
$password_expiry=0; # Length of time when passwords expire, in days (set to 0 for no expiry).

# USER PASSWORD RESET SETTINGS
$hide_failed_reset_text=true; # Hide any notification text if a password reset attempt fails to find a valid user? False means potential hackers can discover valid email addresses.
$allow_password_email=false; # Allow passwords to be emailed directly to users (a security risk)?
$password_reset_link_expiry=1; # How many extra days a reset password link is valid for.

# LOGIN SECURITY SETTINGS
$case_insensitive_username=false; # Ignore case when validating username at login?
$max_login_attempts_per_ip=20; # Number of failed login attempts per IP address until a temporary ban is placed on this address to prevent dictionary attacks.
$max_login_attempts_per_username=5; # Number of failed login attempts per username until a temporary ban is placed on this IP address.
$max_login_attempts_wait_minutes=10; # Length of time in minutes user must wait after failing the login.
$password_brute_force_delay=4; # Length of time in seconds before a 'password incorrect' login message or 'e-mail not found' new password message to deter brute force attacks.
$login_autocomplete=true; # Allow browsers to save the login information on the login form?
$session_length=30; # Length of a user session in minutes. This is used for user session statistics and for auto-log out if $session_autologout=true;.
$session_autologout=false; # Automatically log a user out at the end of a session, idleness equal to $session_length?
$randomised_session_hash=false; # Use randomised session hash? Each new session is completely unique per login. 
$iprestrict_friendlyerror=false; # Show friendly error to user instead of 403 if IP not in permitted range?
$login_background=false; # Enable first slideshow image as a background for the login screen and will not then be used in the slideshow?

#----------EMAIL SETTINGS----------
# SMTP EMAIL SETTINGS
$use_smtp=false; # Use an external SMTP server for outgoing emails (e.g. Gmail), requires $use_phpmailer?
$smtp_secure=''; # Encrypted email transmission: [’’], [‘tls’] or [‘ssl’]. For Gmail, [‘tls’] or [‘ssl’] is required.
$smtp_host=''; # Server hostname, e.g. [‘smtp.gmail.com’].
$smtp_port=25; # Server port number, e.g. [465] for Gmail using SSL.
$smtp_auth=true; # Send credentials to SMTP server (false to use anonymous access).
$smtp_username=''; # Username (full email address).
$smtp_password=''; # Password.

# SYSTEM EMAIL SETTINGS
$use_phpmailer=false; # Use PHPmailer?
$email_from="resourcespace@my.site"; # Address of system e-mails.
#$email_test_fails_to="example@example.com"; # Address where automated tests (/tests/test.php) fail report are sent.
$regex_email="[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}"; # Checking of valid email addresses entered in JS and PHP, currently used for comments.
$always_email_copy_admin=false; # Always CC admin on emails from the logged in user?

# USER EMAIL SETTINGS
$email_from_user=true; # Enable user-to-user emails to come from user's address by default for better reply-to), with the user-level option of reverting to the system address?
$always_email_from_user=false; # Always send emails from the logged in user?
$user_resources_approved_email=false; # Email contributor when their resources have been approved (moved from Pending Submission/Review to Active)?
$cc_me=false; # Allow a user to CC oneself when sending resources or collections?

# RESOURCE STATUS CHANGE EMAIL SETTINGS
$notify_user_contributed_submitted=true; # Send e-mail to $email_notify address when resource in “User Contributed Pending Submission”?
$notify_user_contributed_unsubmitted=false; # Send e-mail to $email_notify address when status changes to “User Contributed Pending Review”?

# RESOURCE REQUEST EMAIL SETTINGS
# $resource_type_request_emails[1]="imageadministrator@my.site"; # Override the default $email_notify address for resource request email notifications, applies to specified resource types.
$resource_type_request_emails_and_email_notify=false; # Notify users beyond $resource_type_request_emails?
$notify_on_resource_change_days=0; # If the primary resource file is replaced/alternative file added, users who have downloaded the resource in the last X days will be sent an email indicating a change.
#$expiry_notification_mail="myaddress@mydomain.example"; # Send a notification when resources expire to this e-mail address, requires batch/expiry_notification.php.

# EMAIL FORMATTING SETTINGS
$email_url_save_user=""; # URL added to the bottom of 'emaillogindetails' templates (save_user function in general.php), if blank, uses $baseurl.
$email_url_remind_user=""; # URL added to the bottom of ‘emailreminder’ template, if blank, uses $baseurl.
$email_footer=""; # Footer text applied to all e-mails (blank by default).
$disable_quoted_printable_enc=false; # Enable multilingual support for e-mails? Enable if e-mail links are not working and ASCII characters alone are required (e.g. in the US).
$send_collection_to_admin=FALSE; # Add collection link to email when user submits a collection of resources for review (upload stage only)?

#----------RESOURCE SHARING SETTINGS----------
# GENERAL RESOURCE AND COLLECTION SHARING SETTINGS
$allow_share=true; # Allow resources to be e-mailed/shared (internally and externally)?
$email_sharing=true; # Enable email sharing.
$default_user_select=""; # Default value for the user select box, for example when e-mailing resources.
$share_resource_as_collection=false; # Always create a collection when sharing an individual resource via email?
$bypass_share_screen=false; # Bypass collection/resource_share.php and go straight to e-mail?
$sharing_userlists=false; # Enable users to save/select predefined lists of users/groups when sharing collections and resources?
$share_resource_include_related=false; # Add option to include related resources when sharing single resource (creates a new collection)?
$list_recipients=false; # Allow listing of all recipients when sending resources or a collection?

# COLLECTION SHARING SETTINGS
$enable_theme_category_sharing=false; # Allow Featured Collection category sharing?
$external_share_view_as_internal=false; # Display external shares in standard internal collection view when accessed by a logged in user?
$collection_allow_empty_share=false; # Allow empty collections to be shared?
$collection_allow_not_approved_share=false; # Allow collections containing resources that are not active to be shared?
$email_multi_collections=false; # Allow multiple collections to be e-mailed at once?

# RESOURCE AND COLLECTION SHARING TIME LIMIT SETTINGS
$resource_share_expire_days=150; # Maximum number of days allowed for the resource share.
$resource_share_expire_never=true; # Allow the resource share ‘Never' expire option?
$collection_share_expire_days=150; # Maximum number of days allowed for the collection share. 
$collection_share_expire_never=true; # Allow the collection share ’Never' expire option?
$show_expiry_warning=true; # Show expiry warning when expiry date has been passed?

# RESOURCE AND COLLECTION SHARING ACCESS SETTINGS
$restricted_share=false; # Should those with 'restricted' access to a resource be able to share the resource?
$allow_custom_access_share=false; # Should those that have been granted open access to an otherwise restricted resource be able to share the resource?
$open_access_for_contributor=false; # Should a user that has contributed a resource always have open access to it?
$edit_access_for_contributor=false; # Should a user that has contributed a resource always have edit access to it (even if resource is live)?
$prevent_open_access_on_edit_for_active=false; # Prevent granting of open access if a user has edit permissions? True will allow group permissions ('e*' and 'ea*') to determine edibility.
$resource_share_filter_collections=false; # Show only existing shares that have been shared by the user when sharing resources (not collections)?
$ignore_collection_access=FALSE; # Share internally a collection which is not private?
$allowed_external_share_groups=array(); # When sharing externally as a specific user group (permission x), limit the user groups shown only if they are allowed.
$external_share_groups_config_options=false; # When sharing externally as a specific user group (permission x), honor group config options (meant to respect settings like $collection_download)?

# RESOURCE AND COLLECTION SHARING FORMATTING SETTINGS
$custom_stylesheet_external_share=false; # Use a custom stylesheet when sharing externally?
$custom_stylesheet_external_share_path=''; # Path can be set anywhere inside website root folder [‘/plugins/your plugin name/css/external_shares.css’].
$hide_internal_sharing_url=false; # Hide display of internal URLs when sharing collections (intended to prevent inadvertently sending external users invalid URLs)?
$hide_collection_share_generate_url=false; # Hide "Generate URL" from the collection_share.php page?
$hide_resource_share_generate_url=false; # Hide "Generate URL" from the resource_share.php page?

#----------METADATA PARSING AND INDEXING SETTINGS----------
# METADATA INDEXING SETTINGS
$index_contributed_by=false; # Index the ‘Contributed By' field?
$index_resource_type=true; # Index the resource type, so searching for resource type string will work?
$index_collection_titles=true; # Include keywords from collection titles when indexing collections?
$index_collection_creator=true; # Include creator name when indexing collections?
$config_trimchars=""; # Trim characters that will be removed from the beginning/end of the string, but not the middle when indexing, leave blank for no extra trimming.

# METADATA SEPARATOR SETTINGS
$config_separators=array("/","_",".","; ","-","(",")","'","\"","\\", "?"); # Array of separators when splitting keywords (characters treated as white space), reindex if data exists (/pages/tools/reindex.php).

# METADATA TEMPLATE SETTINGS
# $metadata_template_resource_type=5; # Enable Metadata Templates.  
# $metadata_template_title_field=10; # Set different field used for 'title', so that the original title field can still be used for template data.

#----------RESOURCE TYPE SETTINGS----------
# RESOURCE TYPE SETTINGS
$hide_resource_types=array(); # Array of resource types to visually hide when searching and uploading, but will still be available (subject to filtering).

# RESOURCE TYPE TEMPLATE SETTINGS
#$pdf_resource_type_templates=array(); # Array of resource type templates, stored in /filestore/system/pdf_templates.

# CATEGORY TREE SETTINGS
$category_tree_open=false; # Should the category tree field, if exists, default to being open instead of closed?
$category_tree_show_status_window=true; # Should the category tree status window be shown?
$cat_tree_singlebranch=false; # Force single branch selection in category tree selection?

#----------ADDITIONAL ARCHIVE STATES SETTINGS----------
# ADDITIONAL ARCHIVE STATES SETTINGS
$additional_archive_states=array(); # Array of additional archive states beyond defaults of -2, -1, 0, 1, 2, and 3. Can be used in with 'z' permissions to restrict access to workflow states.  
#$lang[‘statusx’]=””; # Additional archive states language text parameter, where ‘x’ is the additional state number.

#----------WEB PAGE FORMATTING SETTINGS----------
# PAGE HEADER STYLING SETTINGS
$header_favicon="gfx/interface/favicon.png"; # Path/filename for header image with $baseurl and leading “/“, defaults to ResourceSpace logo if blank. Recommended image size: 350px(X) x 80px(Y).
$header_text_title=false; # Replace header logo with text, application name, and description?
$header_link=true; # Is the logo a link to the home page?
$header_size="HeaderMid"; # Header size class, options [HeaderSmall, HeaderMid, HeaderLarge].
$slimheader_fixed_position=false; # Fix slim header to top bar when scrolling down the page?
$linkedheaderimgsrc=""; # Linked header image path/filename.
#$header_link_url=“http://my-alternative-header-link”; # Change header logo link to another webpage by adding URL.
$header_colour_style_override=''; # Specify custom colors for header.
$header_link_style_override=''; # Specify custom colors for header.

# PAGE HEADER TOP NAVIGATION SETTINGS
$search_results_link=true; # Display ‘Search Results’ link?
$disable_searchresults=false; # Do not display ‘Search Results' link?
$advanced_search_nav=false; # Show ‘Advanced Search’ link?
$themes_navlink=true; # Display a ‘Featured Collections’ link if featured collections are enabled?
$public_collections_top_nav=false; # Show ‘Public Collections’ link?
$public_collections_header_only=false; # Show Public Collections page in header and omit from Featured Collections and Manage Collections?
$recent_link=true; # Display a 'Recent' link?
$help_link=true; # Display a ‘Help and Advice’ link?
$contact_link=true; # Show the ‘Contact Us’ link?
$about_link=true; # Show the ‘About Us’ link?
$research_link=true; # Display a 'Research Request' link?
$myrequests_link=false; # Display a 'My Requests' link?
$mycollections_link=false; # Display a 'My Collections' link? Permission 'b' is needed for collection_manage.php to be displayed.
$mycontributions_userlink=true; # Hide 'My Contributions' link from regular users?
$mycontributions_link=false; # Display a 'My Contributions' link for administrators (permission ‘C’)?
$top_nav_upload=true; # Show an ‘Upload’ link if 't' and 'c' permissions for the current user?
$top_nav_upload_user=false; # Show an ‘Upload’ link in addition to 'my contributions' for standard user if 'd' permission for the current user?
$top_nav_upload_type="plupload"; # The upload type. Options are [plupload, ftp, local]. 
$nav2contact_link=false;

# PAGE HEADER CUSTOM TOP NAVIGATION SETTINGS
#$custom_top_nav[0]["title"]="(lang)mytitle"; # Custom navigation panels numbered sequentially starting from zero (0, 1, 2, 3), URL should be absolute, or include $baseurl.

# PAGE FOOTER SETTINGS
$bottom_links_bar=false; # Show extra home, about, and contact us links in the page footer?

# HOMEPAGE SETTINGS
$default_home_page="home.php"; # Default homepage when not using featured collections as the homepage. Can set other pages, such as search results, $default_home_page="search.php?search=example";
$use_theme_as_home=false; # Use the Featured Collections page as the homepage?
$use_recent_as_home=false; # Use the Recent page as the homepage?
$home_advancedsearch=false; # Disable Advanced Search on homepage?
$welcome_text_picturepanel=false; # Move welcome text into the homepage picture panel, stops text from falling behind other panels?
$no_welcometext=false; # Hide welcome text?

# HOMEPAGE SLIDESHOW SETTINGS
$homeanim_folder="gfx/homeanim/gfx"; # Slideshow images, such as ["gfx/homeanim/mine/“], files should be numbered sequentially, and will be auto-counted.
#$home_slideshow_width=517; # Custom slideshow image width in pixels, used by the Transform plugin so still allows easy replacement of images.
#$home_slideshow_height=350; # Custom slideshow image height in pixels.
$small_slideshow=true; # Use small slideshow mode (old slideshow)?
$slideshow_big=false; # Use big slideshow mode (fullscreen slideshow)? 
$slideshow_photo_delay=5; # Number of seconds for slideshow to wait before changing image (must be greater than 1).
$static_slideshow_image=false; # Use a random static image from the available slideshow images (requires $slideshow_big=true;)?

# MODAL VIEW SETTINGS
$resource_view_modal=true; # Show the resource view in a modal when accessed from search results?
$resource_edit_modal_from_view_modal=false; # Show the resource edit in a modal when accessed from resource view modal?
$help_modal=true; # Show the help page in a modal?

# CHOSEN LIBRARY VIEW SETTINGS
$chosen_dropdowns=false; # Use the JQuery Chosen library for rendering dropdowns (improved display and search capability for large dropdowns)?
$chosen_dropdowns_threshold_main=10; # Number of options that must be present before including search capability.
$chosen_dropdowns_threshold_simplesearch=10; # Number of options that must be present for simple search.

# MY CONTRIBUTION PAGE SETTINGS
$show_user_contributed_resources=true; # Show the link to ‘User Contributed Resources’ (allows non-admin users to see resource assets they have contributed)?

#----------HOMEPAGE DASH TILE SETTINGS----------
# DASH TILE SETTINGS
$home_dash=true; # Enable home dash functionality, on by default?
$tile_styles['srch']=array('thmbs', 'multi', 'blank'); # Define the available search dash tile styles.
$tile_styles['ftxt']=array('ftxt'); # Define the available fixed text dash tile styles.
$tile_styles['conf']=array('blank'); # Define the available conf dash tile styles.
$dash_tile_shadows=false; # Use shadows on all tile content (support for transparent tiles)?
$dash_tile_colour=true; # Enable dash tile color picker/selector? If true and there are no color options, a jsColor picker will be used.
$dash_tile_colour_options=array(); # Dash tile color options ['0A8A0E' => 'green', '0C118A' => 'blue’] using hexadecimal codes.

# DASH TILE MANAGEMENT SETTINGS
$anonymous_default_dash=true; # Set default dash tiles for all_users on the homepage for anonymous users with no drag-n-drop functionality.
$managed_home_dash=false; # Dash administrator manages a single dash for all users? Only those with admin privileges can modify the dash from the Team Centre > Manage all user dash tiles.
$unmanaged_home_dash_admins=false; # Allow dash administrators to have their own dash while all other users have the managed dash ($managed_home_dash must be on)?

#----------USER ACCOUNT SETTINGS----------
# USER GROUP ACCOUNT SETTINGS
$global_permissions=""; # Global permissions that will be prefixed to all user group permissions.
$global_permissions_mask=""; # Global permissions that will be removed from all user group permissions.
$launch_kb_on_login_for_groups=array(); # List of groups for which the Knowledge Base will launch on login, until dismissed.
$U_perm_strict=false; # Enable “U” permission for management of users in the current group as well as children groups. To test stricter adherence to the idea of "children only", set this to true. 
$attach_user_smart_groups=true; # Enable user attach to include 'smart group option', different from the default "users in group" method (which will still be available)?
$user_purge=true; # Enable Purge Users function for administrators?

# USER ACCOUNT SETTINGS
$terms_login=false; # Require terms on first login?
$allow_password_change=true; # Can users change passwords?
$allow_password_reset=true; # Allow users to request new passwords via the login screen?
$user_preferences = true; # Enable user preferences per user?
$remember_me_checked=true; # Enable ‘Remember Me’ checked by default?
$allow_keep_logged_in=true; # Enable the ‘Keep me logged in at this workstation' option on login form (100 day expiry time set on cookie)?
$display_useredit_ref=false; # Display User Ref on the User Edit Page in the header? Example Output: Edit User 12.

# USER ACCOUNT REQUEST SETTINGS
$allow_account_request=true; # Allow users to request accounts?
$default_group=2; # Default group number when adding new users.
$user_account_auto_creation=false; # Enable user account application and auto creation, but will need to be approved by an administrator?
$user_account_auto_creation_usergroup=2; # Default user group for auto-created accounts? (see also $registration_group_select - allows users to select the group themselves).
$auto_approve_accounts=false; # Automatically approve ALL account requests (created via $user_account_auto_creation above)?
$auto_approve_domains=array(); # Automatically approve accounts that have e-mails with in given domain names, ["mycompany.com","othercompany.org”].   
$user_account_fullname_create=false; # Allows for usernames to be created based on full name (such as John Mac -> John_Mac), set $user_account_auto_creation=true.
$account_email_exists_note=true; # Show an error when someone tries to request an account with an email already used? 

# CUSTOM USER REGISTRATION SETTINGS
#$custom_registration_fields="Phone Number,Department"; # Additional custom fields that are collected and e-mailed when new users apply for an account.
#$custom_registration_required="Phone Number"; # Which of the custom registration fields are required.
$registration_group_select=false; # Allow user group to be selected as part of user registration, only useful when $user_account_auto_creation=true;?

# ANONYMOUS USER ACCESS SETTINGS
#$anonymous_login="guest"; # Anonymous username to allow anonymous access. Since collections will be shared among all anonymous users, best to turn off all collections functionality for user.
#$anonymous_login=array(“http://example.com" => "guest","http://test.com" => "guest2"); # Domain linked anonymous access, must match them against the full domain $baseurl that they will be using.
$anon_login_modal=false; # When anonymous access is on, show login in a modal?
$anonymous_user_session_collection=true;
$show_anonymous_login_panel=true; # Show the login panel for anonymous users?

# USER PREFERENCE SETTINGS
$user_pref_resource_notifications=true; # Receive notifications about resource management, such as archive state changes?
$user_pref_resource_access_notifications=true; # Receive notifications about resource access, such as resource requests?
$admin_resource_access_notifications=true; # Administrator default for receiving notifications about resource access, such as resource requests. Cannot use $user_pref_resource_access_notifications.
$user_pref_user_management_notifications=true; # Receive notifications about user management changes, such as account requests (user admins only)?
$user_pref_system_management_notifications=true; # Receive notifications about system events, such as low disk space (system admins only)?
$email_user_notifications=false; # Receive emails instead of new style system notifications where appropriate?
$email_and_user_notifications=false; # Receive emails instead of new style system notifications where appropriate?
$user_pref_show_notifications=true; # Show notification popups for new messages?
$user_pref_daily_digest=false; # Show daily email digest of unread system notifications?
$user_pref_daily_digest_mark_read=false; # Set the messages as read once the email is sent?
$message_polling_interval_seconds=10; # Frequency at which the page header will poll for new messages for the user, set to 0 (zero) to disable.

# CUSTOM USER RESOURCE ACCESS SETTINGS
$custom_access=true; # Enable 'custom' access level for fine-grained control over access to resources?  Disable if using metadata based access control (search filter on the user group).
$default_customaccess=2; # Default level for custom access, will only work for resources that have not been set to custom previously; otherwise, they will show their previous values; [0] Open, [1] Restricted, or [2] Confidential.

#----------KEYBOARD NAVIGATION SETTINGS----------
# RESOURCES KEYBOARD NAVIGATION SETTINGS
$keyboard_navigation=true; # Enable keyboard navigation for left and right arrows to browse through resources in view/search/preview modes?
$keyboard_navigation_prev=37; # Previous resource control code.
$keyboard_navigation_next=39; # Next resource control code.
$keyboard_navigation_pages_use_alt=false; # Use alternate keyboard control codes?
$keyboard_navigation_add_resource=65; # Add resource to collection, default ‘a’.
$keyboard_navigation_remove_resource=82; # Remove resource from collection, default ‘r’.
$keyboard_navigation_prev_page=188; # Previous page in document preview, default ‘,’.
$keyboard_navigation_next_page=190; # Next page in document preview, default ‘.’.
$keyboard_navigation_all_results=191; # View all results, default ‘/‘.
$keyboard_navigation_toggle_thumbnails=84; # Toggle thumbnails in collections frame, default ’t’.
$keyboard_navigation_view_all=86; # View all resources from current collection, default ‘v’.
$keyboard_navigation_zoom=90; # Zoom to/from preview, default ‘z’.
$keyboard_navigation_close=27; # Close modal, default escape.
$keyboard_scroll_jump=false; # Enable arrow keys to jump from picture to picture in preview_all mode (horizontal only)?

# VIDEO FILE KEYBOARD NAVIGATION SETTINGS
$keyboard_navigation_video_search=false; # Enable keyboard navigation for video search?
$keyboard_navigation_video_view=false; # Enable keyboard navigation for viewing videos?
$keyboard_navigation_video_preview=false; # Enable keyboard navigation for previewing videos?
$video_playback_backwards=false; # Enable backwards video playback?
$keyboard_navigation_video_search_backwards=74; # Play backwards control code (in development) - default ‘j’?
$keyboard_navigation_video_search_play_pause=75; # Play/pause control code - default ‘k’.
$keyboard_navigation_video_search_forwards=76; # Play forward control code - default ‘l’.

#----------RESOURCE AND COLLECTION SEARCH SETTINGS----------
# GENERAL SEARCH SETTINGS
$max_results=200000; # Maximum number of search results.
$recent_search_quantity=1000; # For $recent_link, $view_new_material, and $use_recent_as_home, the quantity of resources to return.
$noadd=array(); # Common keywords to ignore both when searching and when indexing. Copy this block to config.php and uncomment the languages you would like to use.
$noadd=array_merge($noadd, array("", "a","the","this","then","another","is","with","in","and","where","how","on","of","to", "from", "at", "for", "-", "by", "be")); # English stop words.
#$noadd=array_merge($noadd, array("och", "det", "att", "i", "en", "jag", "hon", "som", "han", "på", "den", "med", "var", "sig", "för", "så", "till", "är", "men", "ett", "om", "hade", "de", "av", "icke", "mig", "du", "henne", "då", "sin", "nu", "har", "inte", "hans", "honom", "skulle", "hennes", "där", "min", "man", "ej", "vid", "kunde", "något", "från", "ut", "när", "efter", "upp", "vi", "dem", "vara", "vad", "över", "än", "dig", "kan", "sina", "här", "ha", "mot", "alla", "under", "någon", "eller", "allt", "mycket", "sedan", "ju", "denna", "själv", "detta", "åt", "utan", "varit", "hur", "ingen", "mitt", "ni", "bli", "blev", "oss", "din", "dessa", "några", "deras", "blir", "mina", "samma", "vilken", "er", "sådan", "vår", "blivit", "dess", "inom", "mellan", "sånt", "varför", "varje", "vilka", "ditt", "vem", "vilket", "sitta", "sådana", "vart", "dina", "vars", "vårt", "våra", "ert", "era", "vilkas")); # Swedish stop words.
$archive_search=false; # Also search the archive and display a count with every search (performance penalty)?
$config_search_for_number=false; # How are numeric searches handled (true=resource with matching ID shown, false=same as any keyword, with ID shown first)?

# TEMPORAL (DATE) SEARCH SETTINGS
$date_field=12; # Searchable date field. 
$searchbyday=false; # Search on day in addition to month/year?
$daterange_search=false; # Allow dates to be set within ranges (ensure to allow By Date in Advanced Search if required)?
$recent_search_period_select=false; # Limit recent search to resources uploaded in the last X days?
$recent_search_period_array=array(1,7,14,60); # Recent search period array of days.
$recent_search_by_days=false; # Recent link to use recent X days instead of recent X resources?
$recent_search_by_days_default=60; # Recent search number of days default.

# COLLECTION SEARCH SETTINGS
$collection_search_includes_resource_metadata=false; # When searching collections, return results based on the metadata of the resources inside also?
$search_public_collections_ref=true; # Do not omit results for public collections on numeric searches?
$collections_omit_archived=false; # Remove archived resources from collections results unless user has e2 permission (admins)?
$clear_button_unchecks_collections=true; # Should the ‘Clear’ button leave collection searches off by default?

# SEARCH ACCESS SETTINGS
$pending_review_visible_to_all=false; # Should resources that are in the archive state "User Contributed - Pending Review" (-1) be viewable to all?
$pending_submission_searchable_to_all=false; # Should resources that are in the archive state "User Contributed - Pending submission" (-2) be searchable to all?
$search_all_workflow_states=false; # Search all workflow states? Does not work with $advanced_search_archive_select=true;.
#$resource_created_by_filter=array(); # Filter searches to resources uploaded by users with the specified user IDs only, [-1] alias to the current user. 
$smartsearch_accessoverride=true; # Allow the smartsearch to override $access rules when searching?
$custom_access_overrides_search_filter=false; # Custom access to override search filters, if custom access has been granted for the user or group, nullify the filter?
$search_filter_strict=true; # Make search filter strict (prevents direct access to view and preview pages)? 

# SIMPLE SEARCHBAR SETTINGS
$basic_simple_search=false; # Make Simple Search even more simple with just the single search box?
$country_search=false; # Enable Country search (requires a field with the short name 'country’)?
$resourceid_simple_search=false; # Resource ID search blank (only needed if $config_search_for_number is set to true)?
$simple_search_date=true; # Enable Date search?
$advancedsearch_disabled=false; # Hide Advanced Search on search bar?
$view_new_material=false; # Display 'View New Material' link (same as 'Recent’)?
$searchbar_selectall=false; # Include an "all" toggle checkbox for resource types?

# SIMPLE SEARCHBAR FORMATTING SETTINGS
$searchbar_buttons_at_bottom=true; # Move search and clear buttons to bottom?
$simple_search_dropdown_filtering=false; # When multiple dropdowns are used, should selecting from one or more dropdowns limit the options in other dropdowns (performance penalty)?
$simple_search_display_condition=array(); # Honor display condition settings on Simple Search for the included fields.
$simple_search_reset_after_search=false; # Reset Simple Search input boxes after search?
$swap_clear_and_search_buttons=false; # Move the Search button before the Clear button?
$simple_search_show_dynamic_as_dropdown=true; # Show dynamic dropdown as normal dropdowns on the Simple Search?
$hide_main_simple_search=false; # Hide the main simple search field if using only Simple Search fields?
$simple_search_pills_view = false; # Display keywords as pills on Simple Search? Use tab to create new tags/pills and full text strings are ok as a pill.

# SIMPLE SEARCHBAR AUTOCOMPLETE SETTINGS
$autocomplete_search=true; # Enable auto-completion of search text?
$autocomplete_search_items=15; # Number of auto-completion search items shown.
$autocomplete_search_min_hitcount=10; # The minimum number of times a keyword appears in metadata before it qualifies for inclusion in auto-complete.

# ADVANCED SEARCH SETTINGS
$advancedsearch_disabled=true; # Disable advanced search?
$advanced_search_contributed_by=true; # Enable Contributed By search (search for resources contributed by a specific user)?
$advanced_search_media_section=true; # Show Media section?
$advanced_search_buttons_top=false; # Show additional ‘Clear’ and ‘Show Results' buttons at top of page?
$advanced_search_archive_select=true; # Allow user to select archive state?
$default_advanced_search_mode="Global"; # Default search [“Global”], [“Collections”], or resource type ID (1 for photo in default installation, can be comma separated to enable multiple selections.

# SPECIAL SEARCH SETTINGS
$default_res_types=""; # Default resource types to use for searching (leave empty for all).
$checkbox_and=false; # For checkbox list searching, perform logical AND instead of OR when ticking multiple boxes?
$dynamic_keyword_and=false; # For dynamic keyword list searching, perform logical AND instead of OR when selecting multiple options?
$special_search_honors_restypes=false; # Allow special searches to honor resource type settings?
$resource_field_column_limit=200; # How many characters (varchar length) from the fields are 'mirrored' on to the resource table?
$category_tree_search_use_and=false; # Should searches using the category tree use AND for hierarchical keys?
$star_search=false; # Search for a minimum number of stars in Simple Search/Advanced Search (requires $display_user_rating_stars=true;)?
$use_refine_searchstring=false; # Can improve search string parsing, disabled by Dan due to an issue I was unable to replicate (tom)?
$search_sql_double_pass_mode=true; # Experimental performance enhancement, two pass mode for search results. 
$data_joins=array(); # Data joins developer tool to allow adding additional resource field data to the resource table for use in search displays.

# SEARCH RESULTS FORMAT SETTINGS
$highlightkeywords=true; # Highlight search keywords when displaying results and resources?
$search_anchors_highlight=false; # Highlight last viewed result when using $search_anchors?
$search_anchors=true; # When returning to search results from the view page via "all" link, bring user to result location of viewed resource?
$resource_type_icons=false; # Add icons for resource types? Add style IconResourceType<resourcetyperef> and IconResourceTypeLarge<resourcetyperef> similar to videotypes, option overrides $videtypes.
$videotypes=array(3); # List of types which get the extra video icon.
$iconthumbs=true; # Replace text descriptions of search views (thumbnails and list) with icons?
$show_extension_in_search=false; # Show the extension after the truncated text in the search results?

# SEARCH RESULTS DISPLAY SETTINGS
$default_display="thumbs"; # Default display mode [“smallthumbs”], [“thumbs”], or[“list”].
$search_includes_themes=false; # Include Featured Collections (themes) at top of results?
$search_includes_public_collections=false; # Include Public Collections at top of results?
$search_includes_user_collections=false; # Include User Collections at top of results?
$search_includes_resources=true; # Include resources at top of results?
$display_resource_id_in_thumbnail=false; # Show resource ID in the thumbnail, next to the action icons?
$search_titles=false; # Enable titles on the search page that describe the current context?
$search_titles_searchcrumbs=false; # Should all/additional keywords should be displayed in search titles (such as "Recent 1000 / pdf”)?
$separate_resource_types_in_searchbar=array(); # Separate some resource types in searchbar selection boxes?
$search_titles_shortnames=false; # Should field-specific keywords include their shortnames in searchcrumbs (if $search_titles_searchcrumbs=true; ex. "originalfilename:pdf”)?
$show_searchitemsdiskusage=true; # Show total disk usage for returned results?
$search_results_edit_icon=true; # Show an edit icon/link in the search results?

# SEARCH STANDARD THUMBNAIL DISPLAY SETTINGS
$thumbs_display_fields=array(8); # Array of large thumbnail display fields.
$thumbs_display_extended_fields=array(); # Array of large thumbnail fields to apply CSS modifications.
#$search_result_title_height=26; # Large thumbnail title height, also used with $thumbs_display_extended_fields=array();.
$search_results_title_trim=30; # Large thumbnail title trim in characters, also used with $thumbs_display_extended_fields=array();.
$search_results_title_wordwrap=100; # Large thumbnail line wrap in characters. If titles that have large unbroken words (e.g. filenames with no spaces), may be useful to set value lower, e.g. 20.
$mp3_player_thumbs_view=false; # Show MP3 player if $mp3_player=true;?
$video_player_thumbs_view=false; # Show FLV player?

# SEARCH EXTRA LARGE THUMBNAIL DISPLAY SETTINGS
$xlthumbs=true; # Enable extra large thumbnails option?
$xl_thumbs_display_fields=array(8); # Array of extra large thumbnail display fields.
$xl_thumbs_display_extended_fields=array(); # Array of extra large thumbnail fields to apply CSS modifications.
#$xl_search_result_title_height=26; # Extra large thumbnail title height, also used with $xl_thumbs_display_extended_fields=array();.
$xl_search_results_title_trim=60; # Extra large thumbnail title trim in characters, also used with $xl_thumbs_display_extended_fields=array();.
$xl_search_results_title_wordwrap=100; # Extra large thumbnail line wrap in characters, also used with $xl_thumbs_display_extended_fields=array();.
$mp3_player_xlarge_view=true; # Show MP3 player if $mp3_player=true;?
$flv_player_xlarge_view=false; # Show FLV player?
$display_swf_xlarge_view=false; # Show embedded SWFs?

# SEARCH SMALL THUMBNAIL DISPLAY SETTINGS
$smallthumbs=true; # Enable small thumbnails option?
$small_thumbs_display_fields=array(); # Array of small thumbnail display fields.
$small_thumbs_display_extended_fields=array(); # Array of small thumbnail fields to apply CSS modifications.
#$small_search_result_title_height=26; # Small thumbnail title height in characters, also used with $small_thumbs_display_extended_fields=array();.
$small_search_results_title_trim=30; # Small thumbnail title trim in characters, $small_thumbs_display_extended_fields=array();.
$small_search_results_title_wordwrap=100; # Small thumbnail line wrap in characters, $small_thumbs_display_extended_fields=array();.
$video_player_small_thumbs_view=false; # Show FLV player? 

# SEARCH LIST VIEW DISPLAY SETTINGS
$searchlist=true; # Enable list view option?
$list_display_fields=array(8,3,12); # Array of list display fields.
$list_search_results_title_trim=25; # List view title trim in characters.
$id_column=true; # Show ResourceID column in list view?
$resource_type_column=true; # Show Resource Type column in list view?
$list_view_status_column=false; # Show resource archive status?

#----------RESOURCE SORTING SETTINGS----------
# RESOURCE SORTING SETTINGS
$default_sort="relevance"; # Default sort order? [date, colour, relevance, popularity, country].
$default_collection_sort="relevance"; # Default sort order when viewing collection resources? [date, colour, relevance, popularity, country].
$colour_sort=true; # Enable color sorting?
$popularity_sort=true; # Enable popularity sorting?
$random_sort=false; # Enable random sorting?
$orderbyrating=false; # Enable order by rating? Requires rating field updating to rating column.
$order_by_resource_id=false; # Allow sorting by resource ID?
$sort_fields=array(12); # Display fields to be added to the sort links on thumbnail views.
$order_by_resource_type=false; # Allow sorting by resource_type on thumbnail views?

#----------RESOURCE KEYWORD SETTINGS----------
# KEYWORD SETTINGS
$normalize_keywords=true; # Normalize keywords when indexing and searching? True means that various character encodings of diacritics will be standardized when indexing and searching.
$unnormalized_index=false; # Index the unnormalized keyword in addition to the normalized version, also applies to keywords with diacritics removed? 
$keywords_remove_diacritics=false; # Remove diacritics for indexing, e.g. 'zwälf' is indexed as 'zwalf', 'café' is indexed as 'cafe’, actual data not changed, only affects searching/indexing.
$partial_index_min_word_length=3; # For fields with partial keyword indexing enabled, this determines the minimum infix length.
$stemming=false; # Enable experimental stemming support (indexes word stems only, so plural/singular (etc) keyword forms are indexed as they are equivalent, requires a full reindex)?
$keyword_relationships_one_way=false; # Enable one-way keyword relationships? If "tiger" has a related keyword "cat", then a search for "cat" also includes "tiger" matches.
$soundex_suggest_limit=10; # Number of times a keyword must be used before considered eligible for suggesting when a matching keyword is not found.  Set to 0 to suggest any known keyword.
#$resource_field_verbatim_keyword_regex[1]='/\d+\.\d+\w\d+\.\d+/'; # Resource field verbatim keyword regex, this example would add 994.1a9.93 to indexed keywords for field 1.  

# WILDCARD SETTINGS
$wildcard_always_applied_leading = false; # Should wildcard be prepended to the keyword?
$wildcard_expand_limit=50; # Number of keywords included in the search when a single keyword expands via a wildcard, setting too high may cause performance issues.
$wildcard_always_applied=false; # Should all manually entered keywords (e.g. basic search and 'all fields' search on Advanced Search) be treated as wildcards? 

#----------METADATA READING (EXTRACTION) AND WRITING SETTINGS----------
# METADATA READ/EXTRACT SETTINGS WHEN NOT USING EXIFTOOL
# Which fields do we add the EXIF data to? Comment out these three lines to disable basic EXIF reading or see Exiftool for more advanced EXIF/IPTC/XMP extraction.
$exif_comment=18;
$exif_model=52;
$exif_date=12;

# METADATA READ/EXTRACT SETTINGS WHEN USING EXIFTOOL
$metadata_read=true; # Read metadata on upload?.
$metadata_read_default=true; # If $metadata_read=true;, is default setting on the edit and upload pages to read metadata?
$metadata_report=false; # Enable metadata report on the View page?  Can enable on the usergroup level.
$allow_metadata_revert=false; # Show a link to re-extract metadata per-resource on the Edit page?
$exiftool_no_process=array(); # List of file extensions to not read metadata, example ["eps","png”].
$filename_field=51; # Which field number do we drop the original filename in to?
$strip_rich_field_tags=false; # Strip tags from rich fields when downloading metadata, by default is false (keeping the tags added by CKEditor)?
$exiftool_resolution_calc=false; # Use Exiftool to extract specified resolution and unit information from files (ex. Adobe files) upon upload?
$exiftool_remove_existing=false; # Strip out existing EXIF, IPTC, and XMP metadata when adding metadata to resources using Exiftool?
$iptc_expectedchars=""; # IPTC header encoding auto-detection, if using IPTC headers, specify any non-ascii characters used in your local language to aid with character encoding auto-detection.   
$extracted_text_field=72; # When extracting text from documents (e.g. HTML, DOC, TXT, PDF) which field is used for the actual content?

# METADATA WRITE SETTINGS WHEN USING EXIFTOOL
$exiftool_write=true; # Write metadata to files upon download if possible?
$exiftool_write_omit_utf8_conversion=false; # Omit conversion to utf8 when Exiftool writes (this happens when $mysql_charset is not set, or $mysql_charset="utf8”;)?
$force_exiftool_write_metadata=false; # When $exiftool_write_option=true; will write metadata to files on download.
$force_exiftool_write_metadata=true; # When $exiftool_write_option=false; will not write metadata to files on download.
#$exiftool_write_option=true; # Use Exiftool resource and collection downloads to write metadata to the downloaded files?

#----------PDF AND GRAPHICS FILE PROCESSING SETTINGS----------
# PDF AND GRAPHICS FILE SETTINGS
$pdf_resolution=150; # PDF/EPS base ripping quality in DPI. Higher values may increase server load on preview generation (see $pdf_dynamic_rip to avoid).
$pdf_dynamic_rip=false; # Use pdfinfo (PDFs) or identify (EPS) to extract document size to calculate efficient ripping resolution? 
$psd_transparency_checkerboard=false; # Show PSD transparency checkerboard?
$transparency_background="gfx/images/transparency.gif"; # Checkerboard graphic path and file for gif and png with transparency.
$always_make_previews=array(); # Array of preview sizes to always create, helpful if your preview size is small than the "thm" size.

# PREVIEW IMAGE SETTINGS
$lean_preview_generation=false; # Prevent previews from creating versions that result in the same size (true: pre, thm, and col sizes will not be considered)?
$previews_allow_enlarge=false; # Create all preview sizes at the full target size if image is smaller (except for HPR as would result in massive images)?
$no_preview_extensions=array("icm","icc"); # List of file extensions for files which will not have previews automatically generated. 
$preview_quality_unique=false; # Allow unique quality settings for each preview size using $imagemagick_quality as default?
$internal_preview_sizes_editable=true; # Allow editing of internal sizes (will require additional updates to css settings)?

# IMAGEMAGICK SETTINGS
$imagemagick_preserve_profiles=false; # Preserve color profiles for larger sizes above 'scr’?
$imagemagick_quality=90; # JPEG quality (0=worst quality/lowest filesize, 100=best quality/highest filesize).
$photoshop_eps_miff=false; # Turn on creation of a miff file for Photoshop EPS files? Off by default, 4x slower than using Ghostscript and bloats filestore.
$imagemagick_calculate_sizes=false; # Resolve height and width of the ImageMagick file formats at view time (may cause slowdown on viewing resources when large files are used)?
$pdf_pages=30; # If using Imagemagick for PDF, EPS, and PS files, how many pages should be extracted for previews? If >1, user will be able to page through the PDF file.
$tweak_all_images=false; # Tweak all images, instead only scr size and lower?

# IMAGEMAGICK COLOR SPACE SETTINGS
$imagemagick_colorspace="RGB"; # Colorspace usage ["sRGB" for ImageMagick version >=6.7.6-4, “RGB" for ImageMagick versions <6.7.6-4 and for GraphicsMagick].
$dUseCIEColor=true; # Use the Ghostscript command -dUseCIEColor (generally true, but in some cases where scripts might want to turn it off)?
$tweak_allow_gamma=true; # Allow tweaking image gamma?

# IMAGEMAGICK (WITH LCMS SUPPORT) ICC PROFILE SETTINGS
$icc_extraction=false; # Enable extraction and use of ICC profiles from original images?
$icc_preview_profile='sRGB_IEC61966-2-1_black_scaled.icc'; # Target color profile for preview generation, file must be located in the /iccprofiles folder.
$icc_preview_profile_embed=false; # Embed the target preview profile?
$icc_preview_options='-intent perceptual -black-point-compensation'; # Additional options for profile conversion during preview generation.
#$default_icc_file='my-profile.icc'; # Default color profile for all rendered files (or just thumbnails if $imagemagick_preserve_profiles=true;).

# EXPERIMENTAL IMAGEMAGICK SETTINGS (WILL NOT WORK WITH GRAPHICSMAGICK)
$imagemagick_mpr=false; # Enable experimental MPR optimizations?
$imagemagick_mpr_depth="8"; # Set the depth to be passed to MPR command.
$imagemagick_mpr_preserve_profiles=true; # Should MPR color profiles be preserved?
$imagemagick_mpr_preserve_metadata_profiles=array('iptc');# If using imagemagick/mpr, specify metadata profiles to be retained. Default setting good for ensuring copyright info is not stripped.

# EMBEDDED FILE PREVIEW SETTINGS
# Use embedded file preview for complex files? If a preview image cannot be extracted, RS will revert to ImageMagick. 
$photoshop_thumb_extract=false; # Use Adobe Photoshop PSD or PSB file embedded preview image?
$cr2_thumb_extract=false; # Use Canon CR2 raw image file embedded preview image?
$nef_thumb_extract=false; # Use Nikon Electronic Format NEF raw image file embedded preview image?
$dng_thumb_extract=false; # Use Adobe Digital Negative DNG raw image file embedded preview image?
$rw2_thumb_extract=true; # Use Panasonic RW2 raw image file embedded preview image?
$raf_thumb_extract=false; # Use Red Digital Cinema Camera Company RAF file embedded preview image?
$arw_thumb_extract=false; # Use Sony ARW raw image file embedded preview image?

# WATERMARKING SETTINGS
#$watermark="gfx/watermark.png"; # Path and file to watermark placed on images for 'internal' (thumb/preview) images. If set, run /pages/tools/update_previews.php?previewbased=true’.
$watermark_open=false; # Watermark thumbnail and preview for groups with the 'w' permission even when access is 'open’? Use if $terms_download=true;.
$watermark_open_search=false; # Extend $watermark_open to the search page? Set $watermark_open=true;.
$contact_sheet_force_watermarks=false; # Use watermarked previews for contact sheets? If 'true’, watermarks will be forced rather than judged based on user credentials.
# $watermark_single_image = array('scale'=>40,'position'=>'Center',); # Array, display watermark without repeating it, [NorthWest], [North], [NorthEast], [West], [Center], [East], [SouthWest], [South], [SouthEast], watermark used requires an aspect ratio of 1 to work as expected, different aspect ratio will return unexpected results.

#----------DOCUMENT FILE PROCESSING SETTINGS----------
# UNOCONV FILE CONVERSION TO PDF SETTINGS
$unoconv_extensions=array("ods","xls","doc","docx","odt","odp","html","rtf","txt","ppt","pptx","sxw","sdw","html","psw","rtf","sdw","pdb","bib","txt","ltx","sdd","sda","odg","sdc","potx","key"); # Array of file extensions passed to unoconv for conversion to PDF and auto thumb-preview generation.

# CALIBRE EBOOK CONVERSION SETTINGS
$calibre_extensions=array("epub","mobi","lrf","pdb","chm","cbr","cbz"); # File extensions for conversion to PDF and auto thumb-preview generation.

#----------AUDIO AND VIDEO FILE PROCESSING SETTINGS----------
# AUDIO FILE SETTINGS
$ffmpeg_audio_extensions=array('wav','ogg','aif','aiff','au','cdda','m4a','wma','mp2','aac','ra','rm','gsm'); # List of extensions which will be ported to MP3 for preview.
$ffmpeg_audio_params="-acodec libmp3lame -ab 64k -ac 1"; # MP3 preview settings, default to 64Kbps mono.
$mp3_player=true; # Allow player for mp3 files, player: http://flash-mp3-player.net/players/maxi/, will use VideoJS (http://videojs.com/) if enabled $videojs=true;?

# VIDEO FILE SETTINGS
$videojs=true; # Use VideoJS for video playback?
$ffmpeg_preview=true; # Create a preview video for FFmpeg compatible files? A FLV (Flash Video) file will automatically be produced for supported file types: AVI, MOV, MPEG etc.
$ffmpeg_preview_options = "-f flv -ar 22050 -b 650k -ab 32k -ac 1";; # Preview file format options: MP4[“-f mp4 -ar 22050 -b 650k -ab 32k -ac 1”], FLV["-f flv -ar 22050 -b 650k -ab 32k -ac 1”].
$ffmpeg_preview_seconds=120; # Number of seconds to preview.
$ffmpeg_preview_extension="flv"; # Video preview file extension.
$ffmpeg_preview_min_width=32; # Video preview minimum width.
$ffmpeg_preview_min_height=18; # Video preview minimum height.
$ffmpeg_preview_max_width=480; # Video preview maximum width.
$ffmpeg_preview_max_height=270; # Video preview maximum height.
#$ffmpeg_preview_download_name="Flash web preview"; # Change the FFmpeg download name from the default "FLV File” to a custom string.
#$ffmpeg_global_options=“”; # Options to be applied to every FFmpeg command, ["-loglevel panic"] use for recent versions or ["-v panic”] for older versions of FFmpeg where verbose output prevents run_command completing.
#$ffmpeg_snapshot_fraction=0.1; # Point in video for snapshot image as a proportion of the video duration 0 and 1 and valid if duration is >10 seconds.
#$ffmpeg_snapshot_seconds=10;  # Number of seconds into video at for snapshot image, overrides $ffmpeg_snapshot_fraction setting.
$ffmpeg_snapshot_frames=12; # Number of video multiple snapshot images, hovering over a search result thumbnail preview will show frames from the video, set to 0 to disable.
#$ffmpeg_command_prefix=“”; # Ability to add prefix to command when calling FFmpeg, Linux example using nice to avoid slowing down the server ["nice -n 10”].
$ffmpeg_preview_force=false; # If uploaded file is in the preview format already, should we transcode it anyway? This is now ON by default as of switching to MP4 previews, since likely that uploaded MP4 files will need a lower bitrate preview and were not intended to be the actual preview themselves.
$video_preview_original=false; # Always play the original file instead of preview? Useful if recent change to $ffmpeg_preview_force doesn't suit e.g. if all users are on internal network and want to see HQ video.
$ffmpeg_preview_async=false; # Encode preview video asynchronous?
$ffmpeg_get_par=false; # Determine and obey the Pixel Aspect Ratio?
$ffmpeg_use_qscale=true; # Use new qscale to maintain quality (else uses -sameq)?
$ffmpeg_no_new_snapshots=false; # Create any new snapshots when recreating FFmpeg previews (to aid in migration to MP4 when custom previews have been uploaded)?
$ffmpeg_supported_extensions=array('aaf','3gp','asf','avchd','avi','cam','dat','dsh','flv','m1v','m2v','mkv','wrap','mov','mpeg','mpg','mpe','mp4','mxf','nsv','ogm','ogv','rm','ram','svi','smi','webm','wmv','divx','xvid','m4v'); # List of extensions that can be processed by FFmpeg.
$flv_preview_downloadable=false; # Should the automatically produced FLV file be available as a separate download?

# VIDEO ALTERNATIVE FILE SETTINGS
# FFMpeg generation of alternative video file sizes/formats, and attach them as alternative files; blocks must be numbered sequentially [0, 1, 2].  Ensure the formats you are specifying with vcodec and acodec are supported by checking 'ffmpeg -formats’, "lines_min" refers to the minimum number of lines (vertical pixels/height) needed in the source file before this alternative video file will be created and prevents the creation of alternative files that are larger than the source where alternative files are being used for creating downscaled copies (e.g. for web use).

#$ffmpeg_alternatives[0]["name"]="QuickTime H.264 WVGA"; # Video file alternative name.
#$ffmpeg_alternatives[0]["filename"]="quicktime_h264"; # Video file alternative filename.
#$ffmpeg_alternatives[0]["extension"]="mov"; # Video file alternative file extension.
#$ffmpeg_alternatives[0]["params"]="-vcodec h264 -s wvga -aspect 16:9 -b 2500k -deinterlace -ab 160k -acodec mp3 -ac 2"; # Video file alternative transcoding parameters.
#$ffmpeg_alternatives[0]["lines_min"]=480; # Minimum lines in video file alternative.
#$ffmpeg_alternatives[0]["alt_type"]='mywebversion'; # Video file alternative title.

# Convert .mov to .avi “params” ["-g 60 -vcodec msmpeg4v2 -acodec pcm_u8 -f avi”].
# $ffmpeg_alternatives[0]["name"]="QuickTime H.264 WVGA";
# $ffmpeg_alternatives[0]["filename"]="quicktime_h264";
# $ffmpeg_alternatives[0]["extension"]="mov";
# $ffmpeg_alternatives[0]["params"]="-vcodec h264 -s wvga -aspect 16:9 -b 2500k -deinterlace -ab 160k -acodec mp3 -ac 2";
# $ffmpeg_alternatives[0]["lines_min"]=480;
# $ffmpeg_alternatives[0]["alt_type"]='mywebversion';

# Convert FLV to Larger FLV 
# $ffmpeg_alternatives[1]["name"]="Larger FLV";
# $ffmpeg_alternatives[1]["filename"]="flash";
# $ffmpeg_alternatives[1]["extension"]="FLV";
# $ffmpeg_alternatives[1]["params"]="-s wvga -aspect 16:9 -b 2500k -deinterlace -ab 160k -acodec mp3 -ac 2";
# $ffmpeg_alternatives[1]["lines_min"]=480;
# $ffmpeg_alternatives[1]["alt_type"]='mywebversion';

# VIDEO PLAYBACK SETTINGS
$video_player_thumbs_view_alt=false; # Use FFmpeg alternative for search preview playback?
#$video_player_thumbs_view_alt_name='searchprev'; # FFmpeg alternate executable name.
$video_search_play_hover=false; # Play search.php page audio/video files on hover instead of on click?  
$video_view_play_hover=false; # Play view.php page audio/video files on hover instead of on click?
$video_preview_play_hover=false; # Play preview.php and preview_all.php page audio/video files on hover instead of on click?

#----------ALTERNATIVE FILE AND GRAPHICS PROCESSING SETTINGS----------
# ALTERNATIVE FILE SETTINGS
$alternative_file_previews=true; # Generate thumbs/previews for alternative files?
$alternative_file_previews_batch=true; # Generate thumbs/previews for alternative files in batch?
$alternative_file_resource_title=true; # Display resource title on alternative file management page?
$alternative_file_resource_preview=true; # Display col-size image of resource on alternative file management page?
$alternative_file_previews_mouseover=false; # Enable a thumbnail mouseover to see the preview image?
#$alt_types=array("","Print","Web","Online Store","Detail"); # Enable support for storing an alternative type for each alternate file, first is default.
$alt_types_organize=false; # Organize View page display according to alt_type?
$alt_files_visible_when_restricted=true; # Should alternative files be visible to restricted users (they must still request access to download)?

# ALTERNATIVE IMAGE FILE SIZE AND FORMAT SETTINGS (USING IMAGEMAGICK/GRAPHICMAGICK)
# Automatically generate different file sizes and have them attached as alternative files, similar to video file alternatives. The blocks must be numbered sequentially (0, 1, 2). 'params' are any extra parameters to pass to ImageMagick, for example DPI. 'source_extensions' is a comma-separated list of the files that will be processed ["eps,png,gif”]. 'source_params' are parameters for the source file [-density 1200].
# Example - automatically create a PNG file alternative when an EPS file is uploaded.
# $image_alternatives[0]["name"]="PNG File";
# $image_alternatives[0]["source_extensions"]="eps";
# $image_alternatives[0]["source_params"]="";
# $image_alternatives[0]["filename"]="alternative_png";
# $image_alternatives[0]["target_extension"]="png";
# $image_alternatives[0]["params"]="-density 300"; # 300 dpi
# $image_alternatives[0]["icc"]=false;

# Example - automatically create a CMYK JPEG file alternative when a JPEG or TIFF file is uploaded.
# $image_alternatives[1]["name"]="CMYK JPEG";
# $image_alternatives[1]["source_extensions"]="jpg,tif";
# $image_alternatives[1]["source_params"]="";
# $image_alternatives[1]["filename"]="cmyk";
# $image_alternatives[1]["target_extension"]="jpg";
# $image_alternatives[1]["params"]="-quality 100 -flatten $icc_preview_options -profile ".dirname(__FILE__) . "/../iccprofiles/name_of_cmyk_profile.icc"; # Quality 100 JPEG with specific CMYK ICC profile
# $image_alternatives[1]["icc"]=true; # Use source ICC profile in command?

# Example - automatically create a JPG2000 file alternative when an TIF file is uploaded.
# $image_alternatives[2]['name']='JPG2000 File';
# $image_alternatives[2]['source_extensions']='tif';
# $image_alternatives[2]["source_params"]="";
# $image_alternatives[2]['filename']='New JP2 Alternative';
# $image_alternatives[2]['target_extension']='jp2';
# $image_alternatives[2]['params']='';
# $image_alternatives[2]['icc']=false;

#----------RELATED RESOURCES SETTINGS----------
# RELATED RESOURCES SETTINGS
$related_resources_title_trim=15; # Related Resource title trim, set to 0 to disable.
$related_resource_preview_size="col"; #Size of the related resource previews on the resource page ["col","thm”,etc], usually requires some restyling: RelatedResources.CollectionPanelShell.
$sort_relations_by_filetype=false; # Separate related resource results into separate sections (ie. PDF, JPG)?
$sort_relations_by_restype=false; # Separate related resource results into separate sections by resource type (ie. Document, Photo)?
$related_search_show_self=false; # When using the "View these resources as a result set" link, show the original resource in search result?
$related_search_searchcrumb_field=51; # Field to display in searchcrumbs for a related search (defaults to filename)?
#$related_type_show_with_data=array(3,4); # Option to show related resources of specified resource types in a table alongside resource data and will not be shown in the usual related resources area.
$related_type_upload_link=true; # Show a link for users with edit access allowing upload of new related resources, the resource type will automatically selected for the upload?

#----------GEOLOCATION AND GEOTAGGING SETTINGS----------
# GEOLOCATION/GEOTAGGING SETTINGS
$disable_geocoding=false; # Disable geocoding/geotagging feature?
$hide_geolocation_panel=false; # Hide geolocation panel by default (a link to show it will be displayed instead)?
$use_google_maps=false; # Use Google Map layers (a Google Maps API key is not required)?
#$google_maps_api_key=''; # Google Maps API key, if used.
$geo_search_modal_results=true; # Show geographical search results in a modal?
$geo_locate_collection=false; # Enable geolocating multiple assets on a map that are part of a collection?
$geo_layers="osm"; # OpenLayers to make available: OpenStreetMap [“osm”], Google Maps [“gmap, gsat, gphy”], first is the default.
$view_mapheight=200; # Height of map in pixels on resource view page.
$geo_tile_caching=false; # Cache OpenStreetMap tiles on your server? Slower when loading, but eliminates non-SSL warnings for SSL sites (requires curl).

# GEOLOCATION DEFAULT MAP BOUND SETTINGS
$geolocation_default_bounds="-3.058839178216e-9,2690583.3951564,2"; # Default OpenLayers center/zoom for world map view when searching or selecting a new location. For example, for the USA: #$geolocation_default_bounds="-10494743.596017,4508852.6025659,4"; or for Utah: $geolocation_default_bounds="-12328577.96607,4828961.5663655,6”;.
$geo_search_restrict=array
	(	
	# array(50,-3,54,3) # Example omission zone
	# ,array(-10,-20,-8,-18) # Example omission zone 2
	# ,array(1,1,2,2) # Example omission zone 3
	); # List of upper/lower long/lat bounds, defining areas that will be excluded from geographical search results: southwest lat, southwest long, northeast lat, northeast long.

#----------RESOURCE UPLOAD SETTINGS----------
# UPLOADER SETTINGS
$hide_uploadertryother=false; # Hide links to other uploader?
$upload_methods = array('single_upload' => true,'in_browser_upload' => true,'fetch_from_ftp' => false,'fetch_from_local_folder' => false,); # Allow to selectively disable upload methods.
$uploader_view_override=true; # Allow users to see all resources that they uploaded, irrespective of 'z' permissions?

# PLUPLOAD SETTINGS
$plupload_runtimes='html5,gears,silverlight,browserplus,flash,html4'; # Plupload settings, supported runtimes, and priority.
$plupload_autostart=false; # Start uploads as soon as files are added to the queue?
$plupload_clearqueue=true; # Clear the queue after uploads have completed?
#$plupload_max_file_size='50M'; # Maximum upload file size, translates into plupload's max_file_size if set.
$plupload_chunk_size='5mb'; # Upload chunk size, set to ‘’ to disable chunking.
$plupload_show_failed=true; # Keep failed uploads in the queue after uploads have completed?
$plupload_max_retries=5; # Maximum number of attempts to upload a file chunk before erring.
$plupload_allow_duplicates_in_a_row=false; # Allow upload multiple times the same file in a row? Set to true only if you want to create duplicates when client is losing server connection.
$plupload_widget=true;
$plupload_widget_thumbnails=true;

# UPLOAD RESOURCE SETTINGS
$pdf_split_pages_to_resources=false; # When uploading PDF files, split each page to a separate resource file?
$groupuploadfolders=false; # Enable group based upload folders (separate local upload folders for each group)?
$useruploadfolders=false; # Enable username based upload folders (separate local upload folders for each user based on username)?
$store_uploadedrefs=false; # Store Resource Refs when uploading (useful for other developer tools to hook into the upload)?
$always_record_resource_creator=true; # Always record the name of the resource creator for new records (false=only record when a resource is submitted into a provisional status)?
$data_only_resource_types = array(); # Array of resource types that cannot upload files. They are used to store information, use resource type ID as values for this array, preview will default to "No preview" icon. For a resource type specific icon, add to /gfx/no_preview/resource_type/, intended use with $pdf_resource_type_templates.
$pending_submission_prompt_review=true; # Show popup to users that upload resources to either submit for review or continue editing?

# UPLOAD FILE SETTINGS
$banned_extensions=array("php","cgi","pl","exe","asp","jsp", "sh", "bash", "csh"); # List of file extensions that cannot be uploaded for security reasons.
$upload_no_file=false; # Allow users to skip upload and create resources with no attached file?
$local_upload_file_tree=false; # Use a file tree display for local folder upload?
# $zip_contents_field=18; # Contents of a ZIP file can be imported to a text field on upload, requires 'unzip' path, if not set, but unzip is available, the archive contents will be written to $extracted_text_field.
$zip_contents_field_crop=1; # Number of lines to remove from the top of the zip contents output (in order to remove the filename field and other unwanted header information).
$merge_filename_with_title=FALSE; # Enable option to use the embedded filename to generate the title?
$merge_filename_with_title_default='do_not_use'; # Filename title default option [‘do_not_use’], [‘replace’], [‘prefix’], [‘suffix’].
#$preview_generate_max_file_size=100; # Upload file maximum size (MB) that thumbnail/preview images will be created for to reduce server load.

# UPLOAD STATUS AND ACCESS SETTINGS
$override_access_default=false; # Set a default access value?
$override_status_default=false; # Set a default status value?
$show_status_and_access_on_upload=false; # Show status and access fields?
$show_status_and_access_on_upload_perm="return !checkperm('F*');"; # Stack permissions= " return !checkperm('e0') && !checkperm('c')"; Set permission required to show access/status on upload.
$show_access_on_upload=&$show_status_and_access_on_upload; # Show status and access fields. 
$show_access_on_upload_perm="return true;"; # Permission required to show access field on upload? Evaluates PHP code so must be preceded with 'return’, true:no permission required.

# UPLOAD DISPLAY SETTINGS
$clearbutton_on_upload=true; # Show clear button on the upload page?
$show_upload_log=true; # Display an upload log on the upload page (not stored or saved)?
$show_required_field_label = true; # Show required field legend on upload?
$blank_date_upload_template=false; # Use default date left blank, instead of current date?
$do_not_add_to_new_collection_default=false;  # Set “Do not add to a collection" as the default option?

# UPLOAD FILE METADATA SETTINGS
$no_metadata_read_default=false; # If set to true and $metadata_read is false then metadata will be imported by default.
$embedded_data_user_select=false; # Allow user to select to import or append embedded metadata on a field by field basis?
#$embedded_data_user_select_fields=array(1,8); # Always display the option to override the import or appending/prepending of embedded metadata for the fields specified in the array.

# UPLOAD IMAGE RESOURCE/FILE AUTOROTATION SETTINGS
$enable_thumbnail_creation_on_upload=true; # Enable thumbnail generation during batch resource upload from FTP or local folder? A multi-threaded thumbnail generation script is available in the batch folder (create_previews.php), use as a cron job, or manually, also works for normal uploads (through web browser).
$camera_autorotation=false; # Enable autorotation of new images based on embedded camera orientation data (requires ImageMagick)?
$camera_autorotation_checked=true; # Make autorotation on upload the default?
$camera_autorotation_ext=array('jpg','jpeg','tif','tiff','png'); # Only try to autorotate these formats.
$camera_autorotation_gm=false; # Enable GraphicsMagick autorotation?

# BATCH UPLOAD RESOURCE/FILE SETTINGS
$upload_then_edit=false; # Enable upload, edit, approve, and change status workflow mode?
$enable_add_collection_on_upload=true; # Show the 'add resources to collection' selection box?
$enable_public_collection_on_upload=false; # Allow users to set collection public in upload process/assignment to themes for users who have appropriate privileges?
$upload_add_to_new_collection=true; # Default to “Add to New Collection”?
$upload_add_to_new_collection_opt=true; # Enable the "Add to New Collection" option?
$upload_do_not_add_to_new_collection_opt=true; # Enable the "Do Not Add to New Collection" option, false to force upload to a collection?
$upload_collection_name_required=false; # Require a collection name is entered, to override the Upload<timestamp> default behavior?
$upload_force_mycollection=false; # Always upload to My Collection?
$hidden_collections_hide_on_upload=false; # Display hidden collections?
$hidden_collections_upload_toggle=false; # Include show/hide hidden collection toggle? Must have $hidden_collections_hide_on_upload=true;
$enable_copy_data_from=true; # Enable the 'copy resource data from existing resource' feature?
$reset_date_upload_template=true; # When uploading/editing the template, should the date be reset to today's date? If false, the previously entered date is used.
$reset_date_field=12; # Which date field to reset (if using multiple date fields)? 
$blank_edit_template=false; # When uploading/editing the template, should all values be reset to blank or the default value every time?
$default_resource_type=1; # What is the default resource type to use for templates?
$relate_on_upload=false; # Enable option that allows resources uploaded together to all be related (requires $enable_related_resources=true and $php_path set)?
$relate_on_upload_default=false; # Enable option to make relating all resources at upload the default option if $relate_on_upload is true?

#----------RESOURCE SETTINGS----------
# RESOURCE VIEW PAGE SETTINGS
$show_resourceid=true; # Show the Resource ID?
$show_resource_type=false; # Show the resource type field?
$show_access_field=true; # Show the access field?
$show_contributed_by=true; # Show the 'contributed by' field?
$show_related_themes=true; # Show related themes and public collections panel?
$enable_find_similar=true; # Enable find similar search?
$download_summary=false; # Show download summary?
$image_preview_zoom=false; # Enable image preview zoom using jQuery.zoom (hover over the preview image to zoom in)?
$view_default_dpi=300; # Default DPI setting if no resolution is stored in the database.
$view_resource_collections=false; # Enable a list of collections that a resource belongs to?
$direct_link_previews = false; # Add direct link to original file for each image size?
$display_request_log_link=false; # Display link to request log?
$resource_contact_link=false; # Enable link that allows a user to email the $email_notify address about the resource?
$view_title_field=8; # Title field code used as title on the View and Collections pages.
$preview_header_footer=false; # Show header and footer on resource preview page?
$display_swf=false; # Display SWF in full on the View page (note that JPEG previews are not created yet)?
$preview_all_default_orientation="h"; # Preview All default orientation ("v" for vertical or "h" for horizontal).
#$display_field_below_preview=18; # Show specified metadata field below the resource preview image.
$hide_resource_share_link=false; # Hide the Share link?.

# RESOURCE EDIT PAGE SETTINGS
$clearbutton_on_edit=true; # Show clear button?
$enable_related_resources=true; # Enable the Related Resources field when editing resources?
$edit_large_preview=true; # Display a larger preview image on the edit page?
$disable_upload_preview=false; # Disable link to upload preview?
$disable_alternative_files=false; # Disable link to alternative files?
$replace_file_resource_title=true; # Display resource title on replace file page?
$replace_file_resource_preview=true; # Display col-size image of resource on replace file page?
$custompermshowfile=false; # Permission to show the replace file, preview image only and alternative files options (overrides F*)?
$edit_show_save_clear_buttons_at_top=false; # Show "Save" and "Clear" buttons at the top and bottom of the page?
$tabs_on_edit=false; # Show tabs on the edit/upload page (disables collapsible sections)?
$edit_upload_options_at_top=false; # Upload options at top of page (Collection, import metadata checkbox) rather than the bottom default?
$ctrls_to_save=false; # Enable CTRL + S to save data?
$edit_autosave=true; # Automatically save the edit form after making changes?
$distinguish_uploads_from_edits=false; # When displaying resource title, show Upload resources or Edit resource when on edit page?
$edit_all_checkperms=false; # Disable permission checking before showing Edit All link in collection bar and on Manage My Collections page (performance hit)?
$delete_resource_custom_access=false; # Show and allow to remove custom access for users when editing a resource?
$replace_resource_preserve_option=false; # Keep original resource files as alternatives when replacing resource?
$replace_resource_preserve_default=false; # If $replace_resource_preserve_option=true; should the option be checked by default?

# RESOURCE THUMBNAIL AND PREVIEW DOWNLOAD SETTINGS
$thumbs_previews_via_download=false; # Use 'download.php' to send thumbs/previews (improved security as 'filestore' web access can be disabled, experimental)?

# RESOURCE METADATA DOWNLOAD SETTINGS
$metadata_download=false; # Enable metadata download on view.php?
$metadata_download_header_title='ResourceSpace'; # PDF metadata download header text.
#$metadata_download_pdf_logo='/path/to/logo/location/logo.png'; # Custom logo to use when downloading metadata in PDF format.
$metadata_download_footer_text=''; # PDF metadata download footer text.

# RESOURCE DOWNLOAD SETTINGS
$terms_download=false; # Require terms for download?
$restricted_full_download=false; # Allow download of original file for resources with "Restricted" access? For custom preview sizes, value set per preview size in System Setup.
$hide_restricted_download_sizes=false; # Hide download size for "Restricted" access only if user does not have "q" permission?
$original_filenames_when_downloading=true; # Use original filename when downloading a file?
$download_filenames_without_size=false; # Should the download filename have the size appended to it?
$prefix_resource_id_to_filename=true; # Should the original filename be prefixed with the resource ID to ensure unique filenames?
$prefix_filename_string="RS"; # When using $prefix_resource_id_to_filename, string that will be used prior to the resource ID?
$save_as=false; # Display download as 'save as' link instead of redirecting to the download which can cause a security warning? For Opera/IE7 browsers, will always be enabled.
$download_chunk_size=(2 << 20); # Download chunk size, change to 4096 if experiencing slow downloads.
$mime_type_by_extension=array('mov'=>'video/quicktime','3gp'=>'video/3gpp','mpg'=>'video/mpeg','mp4'=>'video/mp4','avi'=>'video/msvideo','mp3'=>'audio/mpeg','wav'=>'audio/x-wav','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','png'=>'image/png','odt'=>'application/vnd.oasis.opendocument.text','ods'=>'application/vnd.oasis.opendocument.spreadsheet','odp'=>'application/vnd.oasis.opendocument.presentation'); # Mime types by extensions used by pages/download.php to detect the mime type of the file proposed to download.
$download_filename_id_only=false; # Enable downloaded filename to be just <ResourceID>.extension, without size/alternative file, overrides $original_filenames_when_downloading.
$download_id_only_with_size=false; # Append the size to the filename when downloading (requires $download_filename_id_only=true;)?
$direct_download_noauth=false; # Enable allow direct resource downloads without authentication? Will allow anyone to download any resource without logging in.
$direct_link_previews_filestore=false; # Make preview direct links go directly to filestore rather than through download.php (filestore must be served through the web server for this to work)?
#$original_download_name="Original %EXTENSION file"; # Option to change the original download filename (Use %EXTENSION, %extension or %Extension as a placeholder. 

# RESOURCE DELETE SETTINGS
$allow_resource_deletion=true; # Allow users to delete resources (can be controlled on a more granular level with the "D" restrictive permission)?
$resource_deletion_state=3; # Resource deletion state to move the resources into an alternative state instead of removing the resource and its files from entirely.
$delete_requires_password=false; # Require password entry (single resource delete, off by default as resources are moved to deleted state, see $resource_deletion_state)?
$collection_purge=false; # Allow users capable of deleting a full collection (of resources) to do so from the Collection Manage page?

# RESEARCH REQUEST SETTINGS
$research_request=false; # Display Research Request link? Allows users to request resources via a form, which is e-mailed.
# Manage requests automatically using $manage_request_admin[resource type ID] = user ID;
# IMPORTANT: the admin user needs to have permissions R and Rb set otherwise this will not work.
#  $manage_request_admin[1] = 1; // Photo
#  $manage_request_admin[2] = 1; // Document
#  $manage_request_admin[3] = 1; // Video
#  $manage_request_admin[4] = 1; // Audio
$resource_request_reason_required=true; # When requesting a resource or resources, is the "reason for request" field mandatory?
$prevent_external_requests=false; # Prevent users without accounts from requesting resources when accessing external shares? If true, external users requesting access will be redirected to the login, only recommended if account requests are allowed.
#$collection_empty_on_submit=false; # Remove all resources from the current collection once it has been requested?
$request_adds_to_collection=false; # Enable 'request' button on resources adds the item to the current collection (which then can be requested) instead of starting a request process for this individual item?
#$warn_field_request_approval=115; # Field that will cause warnings to appear when approving requests containing these resources.
$request_senduserupdates=true; # Send confirmation emails to user when request sent or assigned?
$removenever=false; # Remove 'never' option for resource request access expiration and sets default expiry date to 7 days?

#----------RESEARCH REQUEST SETTINGS----------
# RESEARCH REQUEST CUSTOM DISPLAY OPTIONS
#$custom_request_fields="Phone Number,Department"; # Additional custom fields that are collected and e-mailed when new resources or collections are requested.
#$custom_request_required="Phone Number"; # Required custom fields.
#$custom_request_types["Department"]=1; # Listed fields special display: [1] Normal text box (default), [2] Large text box, [3] Drop down box (see next line), or [4] HTML block (see second line below).
#$custom_request_options["Field Name"]=array("Option 1","Option 2","Option 3"); # Request type 3 settings.
# $custom_request_html="<b>Some HTML</b>"; # Request type 4 settings.

#----------COLLECTION SETTINGS----------
# FEATURED COLLECTIONS (THEMES) SETTINGS
$enable_themes=true; # Enable Featured Collections intended for showcasing selected resources?
$descthemesorder=false; # Force collections lists on the Featured Collections page to be in Descending order?
$theme_images=true; # Show images along with Featured Collections category headers (image selected is the most popular within category)?
$theme_images_number=1; # Number of images to auto-select (if 0, chosen manually).
$theme_images_align_right=false; # Align theme images to the right on the Featured Collections page (useful when there are multiple images)?
$show_theme_collection_stats=false; # Show count of Featured Collections and resources in category?
$flag_new_themes=true; # Display a 'new' flag next to new themes?
$flag_new_themes_age=14; # New theme flag active for x days (default: themes created <2 weeks ago).
$themes_simple_view=false; # Enable simple view (show featured collection categories and featured collections as basic tiles with no images)? Only works with $themes_category_split_pages=true;.
$themes_simple_images=true; # Show images on featured collection and featured collection category tiles if $themes_simple_view is enabled?
$themes_with_resources_only=false; # Show only collections that have resources the current user can see?
$themes_date_column=false; # Show date column?
$themes_ref_column=false; # Show reference column?
$smart_themes_omit_archived=false; # Omit archived resources from get_smart_themes (so if all resources are archived, the header won't show)? 
$enable_theme_breadcrumbs=true;

# FEATURED COLLECTIONS (THEMES) CATEGORY DISPLAY SETTINGS
$theme_category_levels=1; # How many featured collection categories to show, if >1, a dropdown box will appear to allow browsing sub-levels.
$enable_theme_category_edit=true; # Allow Featured Collection names to be batch edited?
$themes_category_split_pages=false; # Display categories as links, and Featured Collections on separate pages?
$themes_category_split_pages_parents=false; # Display breadcrumb-style Featured Collection parent links instead of subcategories?
$themes_category_split_pages_parents_root_node=true; # Include root node before level crumbs to add context and link to themes.php?
$themes_category_navigate_levels=false; # Navigate to deeper levels in theme category trees (false: link to matching resources directly)?
$themes_single_collection_shortcut=false; # If a theme header contains a single collection, allow the title to be a direct link to the collection?
$themes_column_sorting=false; # Sort columns in category display, requires $themes_category_split_pages=true;.
$theme_direct_jump=false; # Enable direct jump mode? $theme_category_levels must be >1 and sub-category levels will not show, must be directly linked to using top navigation links, or similar.

# MANAGE COLLECTION SETTINGS
$collection_allow_creation=true; # Allow users to create new collections?
$themes_in_my_collections=false; # Leave collections in place instead of removing from the user's My Collections when published as Featured Collections?
$enable_collection_copy=true; # Enable selection in collection edit menu that allows selection of another accessible collection to base the current one upon?
$collections_delete_empty=false; # Enable deletion of owned collections at bottom of the Collection Manager list? 
$manage_collections_contact_sheet_link=true; # Remove the contact sheet link from the Manage Collections page?
$manage_collections_remove_link=true; # Show collections remove link?
$manage_collections_share_link=true; # Show collections share link?
$allow_save_search=true; # Allow the addition of 'saved searches' to collections?
# $active_collections="-3 months"; # Point in time where collections are considered 'active' and appear in the drop-down based on creation date. 
$collection_block_restypes=array(); # Array of resource types not allowed to be added to collections, will not affect existing resources in collections [$collection_block_restypes=array(3,4);].
$hide_access_column=false; # Hide 'access' column?
$collection_prefix = ""; # Add a prefix to all collection refs, to distinguish them from resource refs?
$emptycollection=false; # Show an Empty Collection link which will empty the collection of resources (not delete them)?

# COLLECTION VIEW SETTINGS	
$preview_all=false; # Allow a Preview page for entire collections (for more side to side comparison ability, works with collection_reorder_caption)?
$preview_all_hide_collections=true; # Minimize collections frame when visiting preview_all.php?
$disable_collection_toggle=false; # Display the link to toggle thumbnails in collection frame?
$back_to_collections_link=""; # Link back to collections from log page, if "" then link is ignored (suggest: $back_to_collections_link = "&lt;&lt;-- Back to My Collections &lt;&lt;—“;).
$thumbs_default="show"; # In collection frame, [“show”] or [“hide”] thumbnails by default "hide" is better if collections are not going to be heavily used)?
$autoshow_thumbs=false; # Automatically show thumbs when you change collection (only if $thumbs_default=“show”;)?
$max_collection_thumbs=150; # Number of thumbnails to show in the collections panel until a 'View All...' link appears, linking to a search in the main window.
$results_display_array=array(24,48,72,120,240); # Options for available number of thumbnail results to display per page.
$default_perpage=48; # How many default thumbnail results per page?

# PUBLIC COLLECTIONS SETTINGS
$enable_public_collections=true; # Enable Public Collections?
$collection_public_hide_owner=true; # Hide owner in list of Public Collections?
$hide_access_column_public = false; # Hide 'access' column?
$public_collections_exclude_themes=true; # Should Public Collections exclude Featured Collections (themes)?
$public_collections_confine_group=false; # Confine Public Collections display to the collections posted by the user's own group, sibling groups, parent group, and children groups?

# SMART COLLECTION SETTINGS
$allow_smart_collections=false; # Allow saving searches as Smart Collections which self-update based on a saved search?
$smart_collections_async=false; # Run Smart Collections asynchronously?

# COLLECTION PANEL SETTINGS
$collection_frame_divider_height=3; # Collection frame divider height.
$collection_frame_height=153; # Collection frame height.
$collection_dropdown_user_access_mode=false; # Add user/access information to collection results in the collections panel dropdown? Use with $collections_compact_style, should be compatible with the traditional collections tools menu.

# COLLECTION BAR SETTINGS
$collections_footer=true; # Show the collections bar/footer?
$collection_bar_hide_empty=false; # Hide the collection bar (hidden, not minimized) if it has no resources in it?
$collection_bar_popout=false; # Pop-out collection bar upon collection interaction such as "Select Collection”?
$contact_sheet_link_on_collection_bar=true; # Show Contact Sheet link?
$chosen_dropdowns_collection=false; # Use the 'chosen' library for rendering dropdowns in the collection bar (set $chosen_dropdowns=true;)?
$chosen_dropdowns_threshold_collection=10; # Number of options that must be present before including search capability for collection bar dropdowns.
$remove_resources_link_on_collection_bar=true; # Hide "Remove resources" link from collection bar?
$show_edit_all_link=true; # Enable the 'edit all' function in the bar and My Collections?

#----------RESOURCE AND COLLECTION DOWNLOAD SETTINGS----------
# FILE DOWNLOAD USAGE SETTINGS
$download_usage=false; # Ask the user the intended usage when downloading?
$download_usage_options=array("Press","Print","Web","TV","Other"); # Usage options if $download_usage=true;
#$download_usage_prevent_options=array("Press"); # Block download (hide the button) if user selects specific option(s) as a guide.
$remove_usage_textbox=false; # Remove textbox on the download usage page?
$usage_textbox_below=false; # Move textbox below dropdown on the download usage page?
$usage_comment_blank=false; # Make filling in usage text box a non-requirement?

# FILE DOWNLOAD OUTPUT FILE SETTINGS
$csv_export_add_original_size_url_column=false; # Add add original URL size column to CSV file?
#$download_filename_field=8; # Metadata field that will be used for downloaded filename (do not include file extension).
$direct_download=false; # Use iframe-based direct download from the view page (to avoid going to download.php)? Incompatible with $terms_download and $download_usage, overridden by $save_as.
$debug_direct_download=false; # Set to true to see the download iframe for debugging purposes.
$direct_download_allow_ie7=false; # Enable IE7 direct download? IE7 blocks initial downloads but after allowing once, it seems to work.
$direct_download_allow_ie8=false; # Enable IE8 direct download? IE8 blocks initial downloads but after allowing once, it seems to work.
$direct_download_allow_opera=false;  # Enable Opera direct download (not recommended)?

# COLLECTION DOWNLOAD ZIP SETTINGS
$collection_download=false; # Enable download of collections as ZIP archives?  Must set $archiver_path.
$collection_download_max_size = 1024 * 1024 * 1024; # Maximum collection download size before compression in bytes, default 1 GB.
$use_collection_name_in_zip_name=false; # Use the collection name in the downloaded zip filename when downloading collections as a zip file?
$zipped_collection_textfile=false; # Write a text file into zipped collections containing resource data?
$zipped_collection_textfile_default_no=false; # Default to ‘no’ for text file download?
#$collection_download_settings[0]["name"]='ZIP'; # Zipped filename, here for Linux with the zip utility.
#$collection_download_settings[0]["extension"]='zip'; # Zipped filename extension, here for Linux with the zip utility.
#$collection_download_settings[0]["arguments"]='-j'; # Archiver utility parameters, here for Linux with the zip utility.
#$collection_download_settings[0]["mime"]='application/zip'; # Archiver utility name, here for Linux with the zip utility.

# Example for Linux with the 7z utility:
# $collection_download_settings[0]["name"] = 'ZIP';
# $collection_download_settings[0]["extension"] = 'zip';
# $collection_download_settings[0]["arguments"] = 'a -tzip';
# $collection_download_settings[0]["mime"] = 'application/zip';
# $collection_download_settings[1]["name"] = '7Z';
# $collection_download_settings[1]["extension"] = '7z';
# $collection_download_settings[1]["arguments"] = 'a -t7z';
# $collection_download_settings[1]["mime"] = 'application/x-7z-compressed';

# Example for Linux with tar (saves time if large compressed resources):
# $collection_download_settings[0]["name"] = 'tar file';
# $collection_download_settings[0]["extension"] = 'tar';
# $collection_download_settings[0]["arguments"] = '-cf ';
# $collection_download_settings[0]["mime"] = 'application/tar';
# $archiver_executable = 'tar';
# $archiver_listfile_argument = " -T ";

# Example for Windows with the 7z utility:
# $collection_download_settings[0]["name"] = 'ZIP';
# $collection_download_settings[0]["extension"] = 'zip';
# $collection_download_settings[0]["arguments"] = 'a -scsWIN -tzip';
# $collection_download_settings[0]["mime"] = 'application/zip';

#----------ACTIONS SETTINGS----------
$actions_enable=true; # Enables separate actions task lists in the user menu

#----------SUGGESTION, COMMENTING, USER RATING, AND SPEED TAGGING SETTINGS----------
# SUGGESTION AND COMMENTING SETTINGS
$suggest_threshold=-1; # Number of results that trigger the suggestion feature, ‘-1’ disables, significant performance penalty for enabling as it attempts to find the most popular keywords for the entire result set, not recommended for large systems.
$collection_commenting=false; # Enable collection commenting and ranking?
$feedback_email_required=true; # Require email address to be entered when users are submitting collection feedback?
$feedback_resource_select=false; # When requesting feedback, allow the user to select resources (e.g. pick preferred photos from a photo shoot)?
#$collection_feedback_display_field=51; # When requesting feedback, display the contents of the specified field (if available) instead of the resource ID. 
#$comments_collection_enable=false; # Reserved for future use.
$comments_resource_enable=false; # Allow users to make comments on resources?
$comments_flat_view=false; # Show in a threaded (indented view)?
$comments_responses_max_level=10; # Maximum number of nested comments / threads.
$comments_max_characters=200; # Maximum number of characters for a comment.
$comments_email_notification_address=""; # Email address to use for flagged comment notifications.
$comments_show_anonymous_email_address=false; # Keep anonymous commenter's email address private?
$comments_policy_external_url=""; # If specified, will popup a new window fulfilled by URL (when clicking on "comment policy" link).
$comments_view_panel_show_marker=true; # Show an asterisk by the comment view panel title if comments exist?

# USER RATING SETTINGS
$user_rating=false; # Enable user rating of resources using a star ratings system on the resource view page?
$display_user_rating_stars=false; # Display User Rating Stars in search views (a popularity column in list view)?
$user_rating_only_once=true; # Allow each user only one rating per resource, can be edited, will remove all accumulated ratings/weighting on newly rated items?
$user_rating_stats=true; # If $user_rating_only_once=true; allow a log view of user's ratings (link is in the rating count on the View page)?
$user_rating_remove=true; # Allow user to remove their rating?

# SPEED TAGGING SETTINGS (DEVELOPMENTAL)
$speedtagging=false; # Enable speed tagging featurE?
#$speedtaggingfield=1; # Which field ID to add speed tags.
#$speedtagging_by_type[1]=18; # Set speed tag field by resource type, set $speedtagging_by_type[resource_type]=resource_type_field; example for Photo type(1) to Caption(18) field. 

#----------CONTACT SHEET SETTINGS----------
# CONTACT SHEET BASE SETTINGS
$contact_sheet=true; # Enable contact sheet feature (requires ImageMagick/Ghostscript)?
$contact_sheet_resource=false; # Produce a separate resource file when creating contact sheets?
$contact_sheet_previews=true; # Use ajax previews in contact sheet configuration? 
$contact_sheet_preview_size="250x250"; # Ajax previews image size in pixels. 
$contactsheet_sorting=false; # Enable experimental sorting (doesn't include ASC/DESC yet)?
$contact_sheet_single_select_size=false;
$contact_sheet_add_link=true; # Make images in Contact Sheet links to the resource view page?
$contact_sheet_add_link_option=false; # Give user option to enable links?
$contact_sheet_force_watermark_option=false; # Give user option to force watermarks?

# CONTACT SHEET FONT SETTINGS
$contact_sheet_font="helvetica"; # Contact sheet font, defaults: [“helvetica”, “times”, “courier” (standard), and “dejavusanscondensed” for Unicode support (but embedding/subsetting slower).
$contact_sheet_unicode_filenames=true; # Allow unicode filenames (stripped out by default in tcpdf, but since collection names may have special characters, may be needed)?
$titlefontsize=20; # Set title font size, in points.
$refnumberfontsize=14; # Set field text font size, in points.

# CONTACT SHEET HEADER AND FOOTER SETTINGS
$contact_sheet_include_header=true; # Add header text to contact sheet page?
$contact_sheet_include_header_option=false; # Give user option to add header text to contact page?
$contact_sheet_footer=false; # Show contact sheet footer?

# CONTACT SHEET LIST, THUMBNAIL, AND RESOURCE STYLE SETTINGS
$config_sheetlist_fields=array(8); # List style sheet displayed fields.
$config_sheetlist_include_ref=true; # Include Resource ID on list style sheet?
$config_sheetthumb_fields=array(); # Thumbnail style sheet displayed fields.
$config_sheetthumb_include_ref=true; # Include Resource ID on thumbnail style sheet?
$contact_sheet_metadata_under_thumbnail=false; # Show contact sheet metadata under thumbnail preview?
$config_sheetsingle_fields=array(8); # One resource per sheet style displayed fields.
$config_sheetsingle_include_ref=true; # Include Resource ID on one resource per sheet style?
$columns_select='
<option value=2>2</option>
<option value=3>3</option>
<option value=4 selected>4</option>
<option value=5>5</option>
<option value=6>6</option>
<option value=7>7</option>'; # Columns options (May want to limit options if you are adding text fields to the thumbnail style contact sheet).

# CONTACT SHEET LOGO SETTINGS
$include_contactsheet_logo=false; # Add logo image to contact page? set contact_sheet_logo if set to true
#$contact_sheet_logo="gfx/contactsheetheader.png"; # Path to contact sheet logo (png, gif, jpg, or pdf).
$contact_sheet_logo_resize=true; # Resize logo at 300ppi (scaled to a hardcoded percentage of the page size)?
#$contact_sheet_logo_option=true; # Enable user option to add/remove logo?

# CONTACT SHEET PAPER/PRINT SETTINGS
$papersize_select='
<option value="a4">A4 - 210mm x 297mm</option>
<option value="a3">A3 - 297mm x 420mm</option>
<option value="letter">US Letter - 8.5" x 11"</option>
<option value="legal">US Legal - 8.5" x 14"</option>
<option value="tabloid">US Tabloid - 11" x 17"</option>'; # Available paper sizes [US: “letter”, “legal”, “tabloid” and Metric: “a4”, “a3”].

#----------ECOMMERCE AND BASKET SETTINGS----------
# ECOMMERCE AND BASKET SETTINGS
$pricing["scr"]=10; # Screen image price.
$pricing["lpr"]=20; # LPR image price.  
$pricing["hpr"]=30; # Original file image price.
$currency_symbol="&pound;"; # Local currency symbol.
$payment_address="payment.address@goes.here"; # Payment email, must enable Instant Payment Notifications in your Paypal account settings.
$payment_currency="GBP"; # Local currency name.
$basket_stores_size=true; # Should the "Add to basket" function appear on download sizes, so file size required is selected earlier and stored in the basket? Total price can appear in the basket.
$paypal_url="https://www.paypal.com/cgi-bin/webscr"; # PayPal payment URL.
#$portrait_landscape_field=1; # Ability to set a field which will store 'Portrait' or 'Landscape' depending on image dimensions.

#----------STATICSYNC (SYNCHRONIZE WITH A SEPARATE AND STANDALONE FILESTORE) SETTINGS----------
# STATICSYNC BASE SETTINGS
$syncdir="/var/www/r2000/accounted"; # The sync folder path.
$nogo="[folder1]"; # List of folders to ignore within the sync folder.
$staticsync_max_files=10000; # Maximum number of files to process per execution of staticsync.php.
#$staticsync_userref=-1; # User account reference that the staticsync resources will be 'created by’.
$staticsync_allow_syncdir_deletion=false; # Allow deletion of files located in $syncdir through the UI?

# STATICSYNC FOLDER STRUCTURE AND MAPPING SETTINGS
$staticsync_autotheme=true; # Automatically create themes based on the first and second levels of the sync folder structure?
$staticsync_folder_structure=false; # Allow unlimited theme levels to be created based on the folder structure (must update $theme_category_levels)?
$staticsync_extension_mapping_default=1; # Mapping extensions to resource types for synced files by resource type.
$staticsync_extension_mapping[3]=array("mov","3gp","avi","mpg","mp4","flv"); # Video files.
$staticsync_extension_mapping[4]=array("flv");
#$staticsync_mapped_category_tree=50; # Category tree field to store retrieved path information for each file, tree structure will be automatically modified to match the folder structure within the sync folder (performance penalty).
#$staticsync_filepath_to_field=100; # Text field to store retrieved path information for each file, time saving alternative to above option.
$staticsync_extension_mapping_append_values=true; # Append multiple mapped values instead of overwriting (will use the same appending methods as when editing fields, not used on dropdown, date, category tree, datetime, or radio buttons)?
$staticsync_title_includes_path=true; # Should the generated resource title include the sync folder path?
$staticsync_ingest=false; # Should the synced resource files be 'ingested' (moved into filestore structure, synced folder is as an upload mechanism, if path to metadata mapping is used, then this allows metadata to be extracted based on the file's location)?

# STATICSYNC RESOURCE METADATA MAPPING SETTINGS
# It is possible to take path information and map selected parts of the path to metadata fields.
# For example, if you added a mapping for '/projects/' and specified that the second level should be 'extracted' means that 'ABC' would be extracted as metadata into the specified field if you added a file to '/projects/ABC/'
# Hence meaningful metadata can be specified by placing the resource files at suitable positions within the static
# folder hierarchy.
# Use the line below as an example. Repeat this for every mapping you wish to set up
#	$staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/",
#		"field"=>10,
#		"level"=>2
#		);

# You can also now enter "access" in "field" to set the access level for the resource. The value must match the name of the access level
# in the default local language. Note that custom access levels are not supported. For example, the mapping below would set anything in 
# the projects/restricted folder to have a "Restricted" access level.
#	$staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/restricted",
#		"field"=>"access",
#		"level"=>2
#		);

# You can enter "archive" in "field" to set the archive state for the resource. You must include "archive" to the array and its value must match either a default level or a custom archive level. The mapped folder level does not need to match the name of the archive level. Note that this will override $staticsync_defaultstate. For example, the mapping below would set anything in the restricted folder to have an "Archived" archive level.
#   $staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/restricted",
#		"field"=>"archive",
#		"level"=>2,
#		"archive"=>2
#		);
$staticsync_alternatives_suffix="_alternatives"; # Alternative files folder suffix, only works when $staticsync_ingest=true;.
# If staticsync finds a folder in the same directory as a file with the same name as a file but with this suffix appended, then files in the folder will be treated as alternative files for the give file.
# For example a folder/file structure might look like:
# /staticsync_folder/myfile.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative1.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative2.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative3.jpg
#$staticsync_alternative_file_text="_alt_"; # Option to have alternative files located in same directory as primary files, but identified by a defined string, only works when $staticsync_ingest=true;.
$staticsync_prefer_embedded_title=true; # Use embedded title (if ‘false’, a title will be synthesized from the filename and path, even if an embedded title is available.

# STATICSYNC IMAGE SETTINGS
$autorotate_no_ingest=false; # Rotate images automatically when not ingesting resources (if ‘true’, must also set $imagemagick_preserve_profiles=true;)?
$autorotate_ingest=false; # Rotate images automatically when ingesting resources (if ‘true’, must also set $imagemagick_preserve_profiles=true;)?

# STATICSYNC WORKFLOW STATE SETTINGS
$staticsync_defaultstate=0; # Default workflow state for imported files (-2 = pending submission, -1 = pending review, etc.).
$staticsync_deleted_state=2; # Archive state to set for resources where files have been deleted/moved from $syncdir.
#$staticsync_revive_state=-1; # If set, then deleted items that later reappear will be moved to this archive state.


#----------DEPRECATED SETTINGS----------
#$email_notify="resourcespace@my.site"; # Address where resource/research/user requests are sent.
#$email_notify_usergroups=array(); # Use of email_notify is deprecated as system notifications are now sent to the appropriate users based on permissions and user preferences. This variable can be set to an array of usergroup references and will take precedence.
#$use_zip_extension=false; //use php-zip extension instead of $archiver or $zipcommand

<<<<<<< .mine
<<<<<<< .mine
# Options to show/hide the tiles on the home page
$home_themeheaders=false;
$home_themes=true;
$home_mycollections=true;
$home_helpadvice=true;
$home_advancedsearch=false;
$home_mycontributions=false;
||||||| .r9727
# Options to show/hide the tiles on the home page
#$home_themeheaders=false;
#$home_themes=true;
#$home_mycollections=true;
#$home_helpadvice=true;
#$home_advancedsearch=false;
#$home_mycontributions=false;
=======
>>>>>>> .r9726
||||||| .r9726
=======
# Options to show/hide the tiles on the home page
#$home_themeheaders=false;
#$home_themes=true;
#$home_mycollections=true;
#$home_helpadvice=true;
#$home_advancedsearch=false;
#$home_mycontributions=false;
>>>>>>> .r9727

# Custom panels for the home page. You can add as many panels as you like. They must be numbered sequentially starting from zero (0,1,2,3 etc.). You may want to turn off $home_themes etc. above if you want ONLY your own custom panels to appear on the home page. Examples:
# $custom_home_panels[0]["title"]="Custom Panel A";
# $custom_home_panels[0]["text"]="Custom Panel Text A";
# $custom_home_panels[0]["link"]="search.php?search=example";
# $custom_home_panels[1]["title"]="Custom Panel B";
# $custom_home_panels[1]["text"]="Custom Panel Text B";
# $custom_home_panels[1]["link"]="search.php?search=example";

# You can add additional code to a link like this:
# $custom_home_panels[0]["additional"]="target='_blank'";

#$title_sort=false; // deprecated, based on resource table column
#$country_sort=false; // deprecated, based on resource table column
#$original_filename_sort=false; // deprecated, based on resource table column

# Zip command to use to create zip archive (uncomment to enable download of collections as a zip file)
# $zipcommand =
# This setting is deprecated and replaced by $collection_download and $collection_download_settings.

# Enable captioning and ranking of collections (deprecated - use $collection_commenting instead)
$collection_reorder_caption=false; 

# List of active plugins.
# Note that multiple plugins must be specified within array() as follows:
# $plugins=array("loader","rss","messaging","googledisplay"); 
$plugins = array('transform', 'rse_version');

#(old $contact_sheet_custom_footerhtml removed as this is now handled in templates and enabled by either showing/ hiding the footer)

$date_column=false; // based on creation_date which is a deprecated mapping. The new system distinguishes creation_date (the date the resource record was created) from the date metadata field. creation_date is updated with the date field.  List view.

# $rating_field. A legacy option that allows for selection of a metadata field that contains administrator ratings (not user ratings) that will be displayed in search list view. Field must be plain text and have numeric only numeric values.
# $rating_field=121;




# -----------------------------------------------------------
# !!! NOTE TO DEVELOPERS !!!
#
# Do not add config options to the end of this file.
# Please add them to the appropriate section above.
#
# This marks the end of the file. Do not place options
# below this block.
#
# -----------------------------------------------------------






