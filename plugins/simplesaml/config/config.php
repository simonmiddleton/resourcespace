<?php
$simplesaml_version = "1.19.6";
$simplesaml_check_phpversion = "8.0";
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
$simplesaml_update_group=false;
$simplesaml_fallback_group=2;
$simplesaml_groupmap=array();
$simplesaml_sp="resourcespace-sp";
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

$simplesaml_rsconfig = false;

// When using ResourceSpace to store SAML config these setttings are initialised and set in the following pages:-
// plugins/simplesaml/lib/lib/_autoload.php
// plugins/simplesaml/lib/resourcespace/config/config.php
// plugins/simplesaml/lib/resourcespace/config/authsources.php
// plugins/simplesaml/lib/resourcespace/metadata/saml20-idp-remote.php

// Set some defaults to ease setup
global $baseurl_short, $email_from, $application_name, $scramble_key, $storagedir;
$samlid = hash('sha256', "saml" . $scramble_key);
$samltempdir = get_temp_dir(false,"simplesaml");
$simplesaml_config_defaults = array();
$simplesaml_config_defaults["baseurlpath"] =  $baseurl_short . 'plugins/simplesaml/lib/www/';
$simplesaml_config_defaults["tempdir"] =  $samltempdir;
$simplesaml_config_defaults["technicalcontact_name"] =  $application_name;
$simplesaml_config_defaults["technicalcontact_email"] =  $email_from;
$simplesaml_config_defaults["secretsalt"] =  $samlid;
$simplesaml_config_defaults["loggingdir"] = $samltempdir;
$simplesaml_config_defaults["logging.logfile"] = "saml_" . md5($samlid) . ".log";
$simplesaml_config_defaults["admin.protectindexpage"] = true;
$simplesaml_config_defaults["admin.protectmetadata"] = false;
$simplesaml_config_defaults["enable.saml20-idp"] = true;
$simplesaml_config_defaults["datadir"] = $storagedir . "/simplesamldata";
$simplesaml_config_defaults["timezone"] = null;
$simplesaml_config_defaults["session.cookie.secure"] = true;







