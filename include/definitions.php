<?php

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
define ('FIELD_TYPE_DYNAMIC_TREE_IN_DEVELOPMENT',      11);
define ('FIELD_TYPE_RADIO_BUTTONS',                    12);
define ('FIELD_TYPE_WARNING_MESSAGE',                  13);

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
    FIELD_TYPE_DYNAMIC_TREE_IN_DEVELOPMENT      =>"fieldtype-dynamic_tree_in_development",
    FIELD_TYPE_RADIO_BUTTONS                    =>"fieldtype-radio_buttons",
    FIELD_TYPE_WARNING_MESSAGE                  =>"fieldtype-warning_message"
);

$FIXED_LIST_FIELD_TYPES = array(
    FIELD_TYPE_CHECK_BOX_LIST,
    FIELD_TYPE_DROP_DOWN_LIST,
    FIELD_TYPE_CATEGORY_TREE,
    FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,
    FIELD_TYPE_RADIO_BUTTONS
);

// ------------------------- LOG_CODE_ -------------------------

// codes used for log entries (including resource and activity logs)

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

// used internally
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