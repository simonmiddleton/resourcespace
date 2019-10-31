<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

include_once(__DIR__ . '/../../include/search_functions.php');

# PREAMBLE
# Shipped resource types are as follows
#  1 Photo
#  2 Document
#  3 Video
#  4 Audio

# SETUP FOR TEST 1
# Create several resource type text fields with resource type 1 (Photo) which are marked with advanced_search as per list below 

# name                      advanced_search   type
# ------------------------------------------------- 
# PhotoFieldOneNoSearch      0                Text
# PhotoFieldTwoSearch        1                Text
# PhotoFieldThreeSearch      1                Date
# PhotoFieldFourNoSearch     0                Text
# PhotoFieldFiveSearch       1                Radio
# PhotoFieldSixSearch        1                Text

# Create several resource type text fields with resource type 3 (Video) which are marked with advanced_search as per list below 

# name                      advanced_search   type
# ------------------------------------------------- 
# VideoFieldZoneSearch       1                Checkbox
# VideoFieldNameSearch       1                Text
# VideoFieldTypeSearch       1                Radio
# VideoFieldDateNoSearch     0                Date
# VideoFieldAreaNoSearch     0                Text

# Set config $date_field to the shipped resource_type_field "Date" (ref 12) which is a Global resource type (0)

# TEST 1
# Call the function which assembles the searchable fields

# Assert the following
#   That they are all fields which are marked with advanced_search = 1
#   That they form three contiguous groups
#   That the first group should be for Global (resource type 0) fields
#   That the second group should be for Photo (resource type 1) fields
#   That the third group should be for Video (resource type 3) fields

# SETUP FOR TEST 2
# Set config $date_field to resource_type_field "VideoFieldDateNoSearch" 
#   This is one of the fields setup for TEST 1 which is a Video (resource type 3) field

# TEST 2
# Call the function which assembles the searchable fields

# Assert the following
#   That they are, with the exception of VideoFieldDateNoSearch, fields which are marked with advanced_search = 1
#   That they form three contiguous groups
#   That the first group should be for Global (resource type 0) fields
#   That the second group should be for Photo (resource type 1) fields
#   That the third group should be for Video (resource type 3) fields
#   That the first field in the third group is field VideoFieldDateNoSearch

### WORK IN PROGRESS ###
### WORK IN PROGRESS ###
### WORK IN PROGRESS ###
### WORK IN PROGRESS ###

echo "TEST 002300 ADVANCED SEARCHABLE FIELDS".PHP_EOL;
return true;
