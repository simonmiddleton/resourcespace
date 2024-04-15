<?php
/**
 * This file contains the configuration settings that have been deprecated.
 * They will be removed in a future release and the code will operate in line with the default values set below and code to handle the non-default case(s) will be removed.
 * 
 * **** DO NOT ALTER THIS FILE! ****
 * 
 * If you need to change any of the below values, copy them to config.php and change them there, although as these options will be removed in a future release, this is not advised.
 */

# whether field-specific keywords should include their shortnames in searchcrumbs (if $search_titles_searchcrumbs=true;) ex. "originalfilename:pdf"
$search_titles_shortnames=false;

# Disk Usage Warnings - requires running check_disk_usage.php
# Percentage of disk space used before notification is sent out. The number should be between 1 and 100.
# $disk_quota_notification_limit_percent_warning = 90;
# Interval in hours to wait before sending another percent warning 
# $disk_quota_notification_interval = 24;