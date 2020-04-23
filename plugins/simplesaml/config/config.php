<?php
$simplesaml_version = "1.18.5";
$simplesaml_check_phpversion = "7.1";
$simplesaml_site_block=false;
$simplesaml_login=true;
$simplesaml_allow_public_shares=true;
$simplesaml_allowedpaths=array();
$simplesaml_allow_standard_login=true;

$simplesaml_username_attribute="uid";
$simplesaml_fullname_attribute="cn";
$simplesaml_email_attribute="mail";
$simplesaml_username_suffix="";
$simplesaml_group_attribute="groups";
$simplesaml_update_group=true;
$simplesaml_fallback_group=2;
$simplesaml_groupmap=array();
$simplesaml_sp="default-sp";
$simplesaml_fullname_separator=",";
$simplesaml_username_separator=".";
$simplesaml_prefer_standard_login=true;
$simplesaml_custom_attributes = '';
$simplesaml_lib_path = '';
$simplesaml_create_new_match_email = false;
$simplesaml_allow_duplicate_email = false;
$simplesaml_multiple_email_notify = "";

// Enable ResourceSpace to be configured with additional local authorisation of users based upon an extra attribute 
// (ie. assertion/ claim) in the response from the IdP. This assertion will be used by the plugin to determine whether 
// the user is allowed to log in to ResourceSpace or not
$simplesaml_authorisation_claim_name = '';
$simplesaml_authorisation_claim_value = '';