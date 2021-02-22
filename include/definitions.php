<?php

// current upgrade level of ResourceSpace (used for migration scripts, will set sysvar using this if not already defined)
define('SYSTEM_UPGRADE_LEVEL', 15);

// PHP VERSION AND MINIMUM SUPPORTED
if (!defined('PHP_VERSION_ID'))
    {
    // Only needed PHP versions < 5.2.7 - we don't support those versions in the rest of the code but this is the one place where we need to (to tell them to upgrade).
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
    }
define('PHP_VERSION_SUPPORTED', 70000); // 7.0 is the minimum supported.

// ------------------------- FIELD TYPES -------------------------

define ('FIELD_TYPE_TEXT_BOX_SINGLE_LINE',              0);
define ('FIELD_TYPE_TEXT_BOX_MULTI_LINE',               1);
define ('FIELD_TYPE_CHECK_BOX_LIST',                    2);
define ('FIELD_TYPE_DROP_DOWN_LIST',                    3);
define ('FIELD_TYPE_DATE_AND_OPTIONAL_TIME',            4);
define ('FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE',         5);
define ('FIELD_TYPE_EXPIRY_DATE',                       6);
define ('FIELD_TYPE_CATEGORY_TREE',                     7);
define ('FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR',   8);
define ('FIELD_TYPE_DYNAMIC_KEYWORDS_LIST',             9);
define ('FIELD_TYPE_DATE',                             10);
define ('FIELD_TYPE_RADIO_BUTTONS',                    12);
define ('FIELD_TYPE_WARNING_MESSAGE',                  13);
define ('FIELD_TYPE_DATE_RANGE',                       14);

$field_types = array(
    FIELD_TYPE_TEXT_BOX_SINGLE_LINE             =>"fieldtype-text_box_single_line",
    FIELD_TYPE_TEXT_BOX_MULTI_LINE              =>"fieldtype-text_box_multi-line",
    FIELD_TYPE_CHECK_BOX_LIST                   =>"fieldtype-check_box_list",
    FIELD_TYPE_DROP_DOWN_LIST                   =>"fieldtype-drop_down_list",
    FIELD_TYPE_DATE_AND_OPTIONAL_TIME           =>"fieldtype-date_and_optional_time",
    FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE        =>"fieldtype-text_box_large_multi-line",
    FIELD_TYPE_EXPIRY_DATE                      =>"fieldtype-expiry_date",
    FIELD_TYPE_CATEGORY_TREE                    =>"fieldtype-category_tree",
    FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR  =>"fieldtype-text_box_formatted_and_ckeditor",
    FIELD_TYPE_DYNAMIC_KEYWORDS_LIST            =>"fieldtype-dynamic_keywords_list",
    FIELD_TYPE_DATE                             =>"fieldtype-date",
    FIELD_TYPE_RADIO_BUTTONS                    =>"fieldtype-radio_buttons",
    FIELD_TYPE_WARNING_MESSAGE                  =>"fieldtype-warning_message",
    FIELD_TYPE_DATE_RANGE                       =>"fieldtype-date_range"
);

$FIXED_LIST_FIELD_TYPES = array(
    FIELD_TYPE_CHECK_BOX_LIST,
    FIELD_TYPE_DROP_DOWN_LIST,
    FIELD_TYPE_CATEGORY_TREE,
    FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,
    FIELD_TYPE_RADIO_BUTTONS
);

$TEXT_FIELD_TYPES = array(
    FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
    FIELD_TYPE_TEXT_BOX_MULTI_LINE,
    FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE,
    FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR,
    FIELD_TYPE_WARNING_MESSAGE
);

$DATE_FIELD_TYPES = array(
    FIELD_TYPE_DATE_AND_OPTIONAL_TIME,
    FIELD_TYPE_EXPIRY_DATE,
    FIELD_TYPE_DATE,
    FIELD_TYPE_DATE_RANGE
);

// Array of fields that do not have fixed value options but data is stil stored using node/resource_node rather than resource_data. 
// This is now the default for new fields and will include all fields once node development is complete.
$NODE_MIGRATED_FIELD_TYPES = array(
    FIELD_TYPE_DATE_RANGE                 
);

$NODE_FIELDS=array_merge($FIXED_LIST_FIELD_TYPES,$NODE_MIGRATED_FIELD_TYPES);

// ------------------------- LOG_CODE_ -------------------------

// codes used for log entries (including resource, collection and activity logs)

define ('LOG_CODE_ACCESS_CHANGED',		'a');
define ('LOG_CODE_ALTERNATIVE_CREATED',	'b');
define ('LOG_CODE_CREATED',				'c');
define ('LOG_CODE_COPIED',				'C');
define ('LOG_CODE_DOWNLOADED',			'd');
define ('LOG_CODE_EDITED',				'e');
define ('LOG_CODE_EMAILED',				'E');
define ('LOG_CODE_LOGGED_IN',			'l');
define ('LOG_CODE_MULTI_EDITED',		'm');
define ('LOG_CODE_NODE_REVERT',			'N');
define ('LOG_CODE_CREATED_BY_CHANGED',  'o');
define ('LOG_CODE_USER_OPT_IN',	        'O');
define ('LOG_CODE_PAID',				'p');
define ('LOG_CODE_REVERTED_REUPLOADED',	'r');
define ('LOG_CODE_REPLACED',            'f');
define ('LOG_CODE_REORDERED',			'R');
define ('LOG_CODE_STATUS_CHANGED',		's');
define ('LOG_CODE_SYSTEM',				'S');
define ('LOG_CODE_TRANSFORMED',			't');
define ('LOG_CODE_UPLOADED',			'u');
define ('LOG_CODE_UNSPECIFIED',			'U');
define ('LOG_CODE_VIEWED',				'v');
define ('LOG_CODE_DELETED',				'x');
define ('LOG_CODE_DELETED_ALTERNATIVE',	'y');
define ('LOG_CODE_ENABLED',             '+');
define ('LOG_CODE_DISABLED',            '-');
define ('LOG_CODE_LOCKED',              'X');
define ('LOG_CODE_UNLOCKED',            'Y');
define ('LOG_CODE_DELETED_ACCESS_KEY',  'XK');
define ('LOG_CODE_EXTERNAL_UPLOAD',     'EUP');
define ('LOG_CODE_COLLECTION_REMOVED_RESOURCE',				'r');
define ('LOG_CODE_COLLECTION_REMOVED_ALL_RESOURCES',		'R');
define ('LOG_CODE_COLLECTION_DELETED_ALL_RESOURCES',		'D');
define ('LOG_CODE_COLLECTION_DELETED_RESOURCE',		        'd');
define ('LOG_CODE_COLLECTION_ADDED_RESOURCE',				'a');
define ('LOG_CODE_COLLECTION_ADDED_RESOURCE_COPIED',		'c');
define ('LOG_CODE_COLLECTION_ADDED_RESOURCE_COMMENT',		'm');
define ('LOG_CODE_COLLECTION_ADDED_RESOURCE_RATING', 		'*');
define ('LOG_CODE_COLLECTION_SHARED_COLLECTION',			'S');
define ('LOG_CODE_COLLECTION_EMAILED_COLLECTION',			'E');
define ('LOG_CODE_COLLECTION_SHARED_RESOURCE_WITH',			's');
define ('LOG_CODE_COLLECTION_STOPPED_SHARING_COLLECTION',	'T');
define ('LOG_CODE_COLLECTION_STOPPED_RESOURCE_ACCESS',		't');
define ('LOG_CODE_COLLECTION_DELETED_COLLECTION',			'X');
define ('LOG_CODE_COLLECTION_BATCH_TRANSFORMED',			'b');
define ('LOG_CODE_COLLECTION_ACCESS_CHANGED',				'A');
define ('LOG_CODE_COLLECTION_COLLECTION_DOWNLOADED',        'Z');
define ('LOG_CODE_COLLECTION_SHARED_UPLOAD',                'SEU');
define ('LOG_CODE_COLLECTION_EDIT_UPLOAD_SHARE',            'EEU');


// validates LOG_CODE is legal
function LOG_CODE_validate($log_code)
	{
	return in_array($log_code,LOG_CODE_get_all());
	}

// returns all allowable LOG_CODEs
function LOG_CODE_get_all()
	{
	return definitions_get_by_prefix('LOG_CODE');
	}

// ------------------------- SYSTEM NOTIFICATION TYPES -------------------------
define ('MANAGED_REQUEST',		1);
define ('COLLECTION_REQUEST',	2);
define ('USER_REQUEST',			3);
define ('SUBMITTED_RESOURCE',	4);
define ('SUBMITTED_COLLECTION',	5);

// Advanced search mappings. Used to translate field names to !properties special search codes
$advanced_search_properties=array("media_heightmin"=>"hmin",
                                  "media_heightmax"=>"hmax",
                                  "media_widthmin"=>"wmin",
                                  "media_widthmax"=>"wmax",
                                  "media_filesizemin"=>"fmin",
                                  "media_filesizemax"=>"fmax",
                                  "media_fileextension"=>"fext",
                                  "properties_haspreviewimage"=>"pi",
                                  "properties_contributor"=>"cu",
                                  "properties_orientation" => "orientation"
                                  );
							  

// ------------------------- JOB STATUS / GENERIC STATUS CODES -------------------------
define ('STATUS_DISABLED',				0);
define ('STATUS_ACTIVE',				1);
define ('STATUS_COMPLETE',				2);	
define ('STATUS_INPROGRESS',            3);	
define ('STATUS_ERROR',					5);

// -------------------- General definitions --------------------
define ('RESOURCE_LOG_APPEND_PREVIOUS', -1);    // used to specify that we want to append the previous resource_log entry

// Global definition of link bullet carets - easy to change link caret style in the future.
define('LINK_CARET','<i aria-hidden="true" class="fa fa-caret-right"></i>&nbsp;'); 
define('LINK_CARET_BACK','<i aria-hidden="true" class="fa fa-caret-left"></i>&nbsp;');
define('LINK_PLUS','<i aria-hidden="true" class="fa fa-plus"></i>&nbsp;');
define('LINK_PLUS_CIRCLE','<i aria-hidden="true" class="fa fa-plus-circle"></i>&nbsp;');
define('LINK_CHEVRON_RIGHT','<i aria-hidden="true" class="fa fa-chevron-right"></i>&nbsp;');
define('UPLOAD_ICON','<i aria-hidden="true" class="fa fa-fw fa-upload"></i>&nbsp;');
define('DASH_ICON','<i aria-hidden="true" class="fa fa-fw fa-th"></i>&nbsp;');
define('FEATURED_COLLECTION_ICON','<i aria-hidden="true" class="fa fa-fw fa-folder"></i>&nbsp;');
define('RECENT_ICON','<i aria-hidden="true" class="fa fa-fw fa-clock"></i>&nbsp;');
define('HELP_ICON','<i aria-hidden="true" class="fa fa-fw fa-book"></i>&nbsp;');
define('HOME_ICON','<i aria-hidden="true" class="fa fa-fw fa-home"></i>&nbsp;');
define('SEARCH_ICON', '<i class="fa fa-search" aria-hidden="true"></i>&nbsp;');
define('ICON_EDIT', '<i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;');
define('ICON_REMOVE', '<i class="fa fa-minus-circle" aria-hidden="true"></i>&nbsp;');
define('ICON_FOLDER', '<i class="fas fa-folder" aria-hidden="true"></i>&nbsp;');
define('ICON_CUBE', '<i class="fas fa-cube" aria-hidden="true"></i>&nbsp;');

define ('NODE_TOKEN_PREFIX','@@');
define ('NODE_TOKEN_OR','|');
define ('NODE_TOKEN_NOT','!');

// Simple Search pills' delimiter
define ('TAG_EDITOR_DELIMITER', '~');

// Facial recognition
define('FACIAL_RECOGNITION_CROP_SIZE_PREFIX', '_facial_recognition_crop_');
define('FACIAL_RECOGNITION_PREPARED_IMAGE_EXT', 'pgm');
// --------------------------------------------------------------------------------


// ------------------------- FILTER (SEARCH, EDIT) LOGICAL OPERATORS -------------------------
define ('RS_FILTER_ALL', 		1);
define ('RS_FILTER_NONE',		2);
define ('RS_FILTER_ANY',		3);

// Related node operators
define ('RS_FILTER_NODE_NOT_IN',   0);
define ('RS_FILTER_NODE_IN',       1);

// used internally within this file

function definitions_get_by_prefix($prefix)
    {
    $return_definitions = array();
    foreach (get_defined_constants() as $key=>$value)
        {
        if (preg_match('/^' . $prefix . '/', $key))
            {
            $return_definitions[$key]=$value;
            }
        }
    return $return_definitions;
    }

$h264_profiles=array(
    "Baseline"=>"242E0",
    "Main"=>"4D40",
    "High"=>"6400",
    "Extended"=>"58A0"    
    );

// Array of default html tags that are permitted in field data
$permitted_html_tags =  array(
            'html',
            'body',
            'div',
            'span',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'br',
            'em',
            'strong',
            'ol',
            'ul',
            'li',
            'small',
            'sub',
            'ins',
            'del',
            'mark',
            'b',
            'u',
            'p',
            'i'
        );

// Array of default html attributes that are permitted in field data
$permitted_html_attributes = array('id', 'class', 'style');

$jquery_path = "/lib/js/jquery-3.5.1.min.js";
$jquery_ui_path = "/lib/js/jquery-ui-1.12.1.min.js";

// Define dropdown action categories
define ('ACTIONGROUP_RESOURCE',     1);
define ('ACTIONGROUP_COLLECTION',   2);
define ('ACTIONGROUP_EDIT',         3);
define ('ACTIONGROUP_SHARE',        4);
define ('ACTIONGROUP_RESEARCH',     5);
define ('ACTIONGROUP_ADVANCED',     6);


// Global variable that contains variable names that reference metadata fields considered to be core to ResourceSpace 
// and shouldn't be deleted. Plugins can register their own with config_register_core_fieldvars()
// IMPORTANT - not an actual definition/constant, the value will change when using the config_register_core_fieldvars().
$corefields = array(
    "BASE" => array(
        'filename_field',
        'view_title_field',
        'display_field_below_preview',
        'date_field',
        'reset_date_field',
        'download_filename_field',
        'extracted_text_field',
        'facial_recognition_tag_field',
        'speedtaggingfield',
        'staticsync_filepath_to_field',
        'staticsync_extension_mapping_append_values_fields',
        'portrait_landscape_field',
        'metadata_template_title_field',
        'thumbs_display_fields',
        'list_display_fields',
        'sort_fields',
        'xl_thumbs_display_fields',
        'config_sheetlist_fields',
        'config_sheetthumb_fields',
        'config_sheetsingle_fields',
        'zip_contents_field',
        'related_search_searchcrumb_field',
        'warn_field_request_approval',
        'rating_field',
        'iiif_identifier_field',
        'iiif_description_field',
        'iiif_license_field',
        'iiif_sequence_field',
        'facial_recognition_tag_field',
        'join_fields'
        )
    );

// Similar to $corefields but holds list of field refs we want the system to prevent from deleting. Mostly plugins will want
// to register these IF the plugin is configured to use certain metadata fields.
// IMPORTANT - not an actual definition/constant, the value will change when using the config_register_core_field_refs().
$core_field_refs = [];


// ----------------------------------------------
// COLLECTIONS
// ----------------------------------------------
define("COLLECTION_TYPE_STANDARD",  0);
define("COLLECTION_TYPE_UPLOAD",    1); # for collections used in upload then edit mode
define("COLLECTION_TYPE_SELECTION", 2); # selecting resources to be edited in batch for the active user (allowed only one per user)
define("COLLECTION_TYPE_FEATURED",  3); # featured collections (used for both parents and children featured collections)
define("COLLECTION_TYPE_PUBLIC",    4); # public collections
define("COLLECTION_SHARE_UPLOAD",   5); # public collections


$FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS = array(
    "no_image" => 0,
    "most_popular_image" => 1,
    "most_popular_images" => 10,
    "manual" => 100,
);
$COLLECTION_PUBLIC_TYPES = array(COLLECTION_TYPE_PUBLIC, COLLECTION_TYPE_FEATURED);


// ----------------------------------------------
// RESOURCE ACCESS TYPES
// ----------------------------------------------

define("RESOURCE_ACCESS_FULL", 0); # Full Access (download all sizes)
define("RESOURCE_ACCESS_RESTRICTED", 1); # 1 = Restricted Access (download only those sizes that are set to allow restricted downloads)
define("RESOURCE_ACCESS_CONFIDENTIAL", 2); # Confidential (no access)
define("RESOURCE_ACCESS_CUSTOM_GROUP", 3); # custom group access
define("RESOURCE_ACCESS_INVALID_REQUEST", 99); # invalid resource request eg. invalid resource ref


// ----------------------------------------------
// MESSAGES
// ----------------------------------------------
// Enumerated types of message.  Note the base two offset for binary combination.
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_SCREEN",1);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_EMAIL",2);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_RESERVED_1",4);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_RESERVED_2",8);
DEFINE ("MESSAGE_ENUM_NOTIFICATION_TYPE_RESERVED_3",16);

DEFINE ("MESSAGE_DEFAULT_TTL_SECONDS",60 * 60 * 24 * 7);		// 7 days


// ----------------------------------------------
// MIGRATIONS
// ----------------------------------------------
define('MIGRATION_FIELD_OPTIONS_DEPRECATED_PREFIX','!deprecated');
define('MIGRATION_FIELD_OPTIONS_DEPRECATED_PREFIX_CATEGORY_TREE',"-1,,!deprecated\n");

# For str_highlight ().
define('STR_HIGHLIGHT_SIMPLE', 1);
define('STR_HIGHLIGHT_WHOLEWD', 2);
define('STR_HIGHLIGHT_CASESENS', 4);
define('STR_HIGHLIGHT_STRIPLINKS', 8);


# Keyboard control codes
# Previous/next resource: left/right arrows
$keyboard_navigation_prev=37;
$keyboard_navigation_next=39;
# add resource to collection, 'a'
$keyboard_navigation_add_resource=65;
# previous page in document preview, ','
$keyboard_navigation_prev_page=188;
# next page in document preview, '.'
$keyboard_navigation_next_page=190;
# view all results, '/'
$keyboard_navigation_all_results=191;
# toggle thumbnails in collections frame, 't'
$keyboard_navigation_toggle_thumbnails=84;
# view all resources from current collection, 'v'
$keyboard_navigation_view_all=86;
# zoom to/from preview, default 'z'
$keyboard_navigation_zoom=90;
# close modal, escape
$keyboard_navigation_close=27;
$keyboard_navigation_video_search_backwards=74;
# play/pause - 'k'
$keyboard_navigation_video_search_play_pause=75;
# play forwards - 'l'
$keyboard_navigation_video_search_forwards=76;

# Array of valid utilities (as used by get_utility_path() function) used to create files used in offline job handlers e.g. create_alt_file. create_download_file. Plugins can extend this
$offline_job_prefixes = array("ffmpeg","im-convert","im-mogrify","ghostscript","im-composite","archiver"); 

# Regular expression defining e-mail format
# Currently exclusively used for comments functionality - checking of valid (anonymous) email addresses entered in JS and in back-end PHP
$regex_email = "[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}";	

