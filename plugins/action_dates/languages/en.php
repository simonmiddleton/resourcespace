<?php
# English
# Language File for the action_dates plugin
# -------
#
#

$lang['action_dates_restrictsettings']="Automatic restriction settings";
$lang['action_dates_deletesettings']="Automatic resource deletion settings - use with caution";
$lang['action_dates_configuration']="Select the fields that will be used to automatically perform the actions specified.";
$lang['action_dates_delete']="Automatically delete or change state of resources when the date in this field is reached";
$lang['action_dates_restrict']="Automatically restrict access to resources when the date in this field is reached. This will only apply to resources currently in the open state.";
$lang['action_dates_delete_logtext']=" - Automatically deleted by action_dates plugin";
$lang['action_dates_restrict_logtext']=" - Automatically restricted by action_dates plugin";
$lang['action_dates_reallydelete']="Fully delete resource when deletion data passed? If set to false resources will be moved to the configured resource_deletion_state and thus recoverable";
$lang['action_dates_email_admin_days']="Email administrator a set number of days before this date is reached. Leave blank for no email.";
$lang['action_dates_email_text']="The following resources are due to be restricted in %%DAYS days.";
$lang['action_dates_email_subject']="Notification of resources due to be restricted";
$lang['action_dates_new_state'] = 'New state to move to (if above option is set to fully delete resources this will be ignored)';
$lang['action_dates_notification_subject'] = 'Notification from action dates plugin';
