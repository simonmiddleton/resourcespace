<?php

// current upgrade level of ResourceSpace (used for migration scripts, will set sysvar using this if not already defined)
define('SYSTEM_UPGRADE_LEVEL', 6);

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
define ('LOG_CODE_PAYED',				'p');
define ('LOG_CODE_REVERTED_REUPLOADED',	'r');
define ('LOG_CODE_REORDERED',			'R');
define ('LOG_CODE_STATUS_CHANGED',		's');
define ('LOG_CODE_SYSTEM',				'S');
define ('LOG_CODE_TRANSFORMED',			't');
define ('LOG_CODE_UPLOADED',			'u');
define ('LOG_CODE_UNSPECIFIED',			'U');
define ('LOG_CODE_VIEWED',				'v');
define ('LOG_CODE_DELETED',				'x');
define ('LOG_CODE_ENABLED',             '+');
define ('LOG_CODE_DISABLED',            '-');
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

// Advanced search mappings. USed to translate field names to !properties special search codes
$advanced_search_properties=array("media_heightmin"=>"hmin",
                                  "media_heightmax"=>"hmax",
                                  "media_widthmin"=>"wmin",
                                  "media_widthmax"=>"wmax",
                                  "media_filesizemin"=>"fmin",
                                  "media_filesizemax"=>"fmax",
                                  "media_fileextension"=>"fext",
                                  "properties_haspreviewimage"=>"pi",
                                  "properties_contributor"=>"cu"
                                  );
							  

// ------------------------- JOB STATUS / GENERIC STATUS CODES -------------------------
define ('STATUS_DISABLED',				0);
define ('STATUS_ACTIVE',				1);
define ('STATUS_COMPLETE',				2);	
define ('STATUS_ERROR',					5);

// -------------------- General definitions --------------------
define ('RESOURCE_LOG_APPEND_PREVIOUS', -1);    // used to specify that we want to append the previous resource_log entry

// Global definition of link bullet carets - easy to change link caret style in the future.
define('LINK_CARET','<i aria-hidden="true" class="fa fa-caret-right"></i>&nbsp;'); 
define('LINK_CARET_BACK','<i aria-hidden="true" class="fa fa-caret-left"></i>&nbsp;');
define('LINK_CARET_PLUS','<i aria-hidden="true" class="fa fa-plus"></i>&nbsp;');
define('UPLOAD_ICON','<i aria-hidden="true" class="fa fa-fw fa-upload"></i>&nbsp;');
define('DASH_ICON','<i aria-hidden="true" class="fa fa-fw fa-th"></i>&nbsp;');
define('FEATURED_COLLECTION_ICON','<i aria-hidden="true" class="fa fa-fw fa-folder"></i>&nbsp;');
define('RECENT_ICON','<i aria-hidden="true" class="fa fa-fw fa-clock"></i>&nbsp;');
define('HELP_ICON','<i aria-hidden="true" class="fa fa-fw fa-book"></i>&nbsp;');
define('HOME_ICON','<i aria-hidden="true" class="fa fa-fw fa-home"></i>&nbsp;');
define('SEARCH_ICON', '<i class="fa fa-search" aria-hidden="true"></i>&nbsp;');

define ('NODE_TOKEN_PREFIX','@@');
define ('NODE_TOKEN_OR','|');
define ('NODE_TOKEN_NOT','!');

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

$jquery_path = "/lib/js/jquery-3.3.1.min.js";
$jquery_ui_path = "/lib/js/jquery-ui-1.12.1.min.js";

// Define dropdown action categories
define ('ACTIONGROUP_RESOURCE',     1);
define ('ACTIONGROUP_COLLECTION',   2);
define ('ACTIONGROUP_EDIT',         3);
define ('ACTIONGROUP_SHARE',        4);
define ('ACTIONGROUP_RESEARCH',     5);
define ('ACTIONGROUP_ADVANCED',     6);
