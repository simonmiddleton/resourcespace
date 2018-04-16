<?php

$simplesaml_site_block=false;
$simplesaml_login=true;
$simplesaml_allow_public_shares=true;
$simplesaml_allowedpaths=array("plugins/api_core","plugins/api_search","plugins/api_upload","plugins/api_log","plugins/api_alt_file");
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
$simplesaml_login_expiry=3;
$simplesaml_custom_attributes = '';
$simplesaml_lib_path = '';