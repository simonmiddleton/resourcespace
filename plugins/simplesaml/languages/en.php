<?php

$lang['simplesaml_configuration']="SimpleSAML configuration";
$lang['simplesaml_main_options']="Usage options";
$lang['simplesaml_site_block']="Use SAML to block access to site completely, if set to true then no one can access site, even anonymously, without authenticating";
$lang['simplesaml_allow_public_shares']="If blocking site, allow public shares to bypass SAML authentication?";
$lang['simplesaml_allowedpaths']="List of additional allowed paths that can bypass SAML requirement";
$lang['simplesaml_allow_standard_login']="Allow users to log in with standard accounts as well as using SAML SSO? WARNING: Disabling this can risk locking all users out of system if SAML authentication fails";
$lang["simplesaml_use_sso"]="Use SSO to log in";
$lang['simplesaml_idp_configuration']="IdP configuration";
$lang['simplesaml_idp_configuration_description']="Use the following to configure the plugin to work with your IdP";
$lang["simplesaml_username_attribute"]="Attribute(s) to use for username.  If this is a concatenation of two attributes please separate with a comma ";
$lang["simplesaml_username_separator"]="If  joining fields for username use this character as a separator";
$lang["simplesaml_fullname_attribute"]="Attribute(s) to use for full name. If this is a concatenation of two attributes please separate with a comma ";
$lang["simplesaml_fullname_separator"]="If  joining fields for full name use this character as a separator";
$lang["simplesaml_email_attribute"]="Attribute to use for email address";
$lang["simplesaml_group_attribute"]="Attribute to use to determine group membership";
$lang["simplesaml_username_suffix"]="Suffix to add to created usernames to distinguish them from standard ResourceSpace accounts";
$lang["simplesaml_update_group"]="Update user group at each logon. If not using SSO group attribute to determine access then set this to false so that users can be manually moved between groups ";
$lang['simplesaml_groupmapping'] = "SAML - ResourceSpace Group Mapping";
$lang['simplesaml_fallback_group']="Default user group that will be used for newly created users";
$lang['simplesaml_samlgroup'] = "SAML group";
$lang['simplesaml_rsgroup'] = "ResourceSpace Group";
$lang['simplesaml_priority']="Priority (higher number will take precedence)";
$lang['simplesaml_addrow'] = "Add mapping";
$lang['simplesaml_service_provider'] = "Name of local service provider (SP)";
$lang['simplesaml_prefer_standard_login'] = "Prefer standard login (redirect to login page by default)";
$lang['simplesaml_sp_configuration'] = "The simplesaml SP configuration must be completed in order to use this plugin. Please see the Knowledge Base article for more information";
$lang['simplesaml_custom_attributes'] = 'Custom attributes to record against the user record';
$lang['simplesaml_custom_attribute_label'] = 'SSO attribute - ';
$lang["simplesaml_usercomment"] = "Created by SimpleSAML plugin";
$lang["origin_simplesaml"] = "SimpleSAML plugin";
$lang['simplesaml_lib_path_label'] = 'SAML lib path (please specify full server path)';
$lang['simplesaml_login'] = 'Use SAML credentials to login to ResourceSpace? (This is only relevant if above option is enabled)';

$lang['simplesaml_create_new_match_email'] = "Email-match: Before creating new users, check if the SAML user email matches an existing RS account email. If a match is found the SAML user will 'adopt' that account";
$lang['simplesaml_allow_duplicate_email'] ="Allow new accounts to be created if there are existing ResourceSpace accounts with the same email address? (this is overridden if email-match is set above and one match is found)";
$lang['simplesaml_multiple_email_match_subject'] ="ResourceSpace SAML - conflicting email login attempt";
$lang['simplesaml_multiple_email_match_text'] ="A new SAML user has accessed the system but there is already more than one account with the same email address.";
$lang['simplesaml_multiple_email_notify']="Email address to notify if an email conflict is found";
$lang['simplesaml_duplicate_email_error']="There is an existing account with the same email address. Please contact your administrator.";
$lang['simplesaml_usermatchcomment'] = "Updated to SAML user by SimpleSAML plugin.";
$lang['simplesaml_usercreated'] = "Created new SAML user";
$lang['simplesaml_duplicate_email_behaviour'] = "Duplicate account management";
$lang['simplesaml_duplicate_email_behaviour_description'] = "This section controls what happens if a new SAML user logging in conflicts with an existing acount";

$lang['simplesaml_authorisation_rules_header'] = 'Authorisation rule';
$lang['simplesaml_authorisation_rules_description'] = 'Enable ResourceSpace to be configured with additional local authorisation of users based upon an extra attribute (ie. assertion/ claim) in the response from the IdP. This assertion will be used by the plugin to determine whether the user is allowed to log in to ResourceSpace or not.';
$lang['simplesaml_authorisation_claim_name_label'] = 'Attribute (assertion/ claim) name';
$lang['simplesaml_authorisation_claim_value_label'] = 'Attribute (assertion/ claim) value';
$lang['simplesaml_authorisation_login_error'] = "You don't have access to this application! Please contact the administrator for your account!";
$lang['simplesaml_authorisation_version_error'] = "IMPORTANT: Your SimpleSAML configuration needs to be updated before you can use it with PHP version 7.1 or later. Please refer to the <a href='https://www.resourcespace.com/knowledge-base/plugins/simplesaml#upgrade' target='_blank'> Upgrading SimpleSAML </a> section of the Knowledge Base for more information";
$lang['simplesaml_healthcheck_error'] = "SimpleSAML plugin error";
$lang['simplesaml_rsconfig'] = "Use standard ResourceSpace configuration files to set SP configuration and metadata? If this set to false then manual editing of files is required";
$lang['simplesaml_sp_generate_config'] = "Generate SP config";
$lang['simplesaml_sp_config'] = "Service Provider (SP) Configuration";
$lang['simplesaml_idp_section'] = "IdP";
$lang['simplesaml_idp_metadata_xml'] = "Paste in the IdP Metadata XML";
$lang['simplesaml_sp_cert_path'] = "Path to SP certificate file (leave empty to generate but fill in cert details below)";
$lang['simplesaml_sp_key_path'] = "Path to SP key file (.pem) (leave empty to generate)";
$lang['simplesaml_sp_idp'] = "IdP identifier (leave blank if processing XML)";
$lang['simplesaml_saml_config_output'] = "Paste this into your ResourceSpace config file";
$lang['simplesaml_sp_cert_info'] = "Certificate information (required)";
$lang['simplesaml_sp_cert_gen_error'] = "Unable to generate certificate";
$lang['simplesaml_sp_samlphp_link'] = "Visit SimpleSAMLphp test site";