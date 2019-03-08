<?php
include "../../include/db.php";
include_once "../../include/general.php";
include_once "../../include/authenticate.php";
if(!checkperm("a")){exit("Access denied");}

# SQL Script to populate usergroup field on daily_stat table for activity_type “User session”
# ------------------------------------------------------------------------------------------------------------------
# The goal is to update the daily_stats "User session" rows so that the user group for a given daily_stat
# row will reflect the user group at close of play on the day the daily_stats row was generated.
#
# There are two passes involved
#
# First pass deals with daily_stat rows for which an “effective” row on the activity_log cannot be found and so
# the usergroup will therefore be populated directly from the user table.
# Example of this is for a user whose usergroup has never been changed following creation of the user.
# 
# Second pass deals with daily_stat rows for which an “effective” row on the activity_log can be found.
# The activity_log table can contain more than one row for any given user for any given daily_stat date because 
# the usergroup may have changed more than once on any given date. 
# The latest usergroup change (new_value) for that user and daily_stat date will be used to populate the usergroup.
# ------------------------------------------------------------------------------------------------------------------
    
# First pass; fetch the usergroup from the user table
$sql =
"UPDATE daily_stat ds 
    SET ds.usergroup = coalesce( (select u.usergroup from user u where u.ref = ds.object_ref), 0)
  WHERE ds.activity_type='User session' 
    AND ds.object_ref not in(select remote_ref from activity_log 
                              where log_code='e' and remote_table='user' and remote_column='usergroup'
                                and date(logged) <= STR_TO_DATE(CONCAT(ds.day,',',ds.month,',',ds.year ),'%d,%m,%Y') ); ";
sql_query($sql);
echo "First pass complete <br />";

# Second pass; fetch the usergroup effective on the daily stat date
$sql =
"UPDATE daily_stat ds 
    SET ds.usergroup = coalesce( (select a.value_new from activity_log a 
                                   where a.remote_ref = ds.object_ref
                                     and a.log_code='e' and a.remote_table='user' and a.remote_column='usergroup'
                                     and date(a.logged) <= STR_TO_DATE(CONCAT(ds.day,',',ds.month,',',ds.year ),'%d,%m,%Y')
                                  order by a.logged desc limit 1 ), 0)
  WHERE ds.activity_type='User session' 
    AND ds.object_ref in(select remote_ref from activity_log 
                          where log_code='e' and remote_table='user' and remote_column='usergroup'
                            and date(logged) <= STR_TO_DATE(CONCAT(ds.day,',',ds.month,',',ds.year ),'%d,%m,%Y') ); ";
sql_query($sql);
echo "Second pass complete <br />";
