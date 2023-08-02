<?php

// Functions to support offline jobs ($offline_job_queue = true)
// Offline jobs require a frequent cron/scheduled task to run tools/offline_jobs.php

/**
 * Adds a job to the job_queue table.
 *
 * @param  string $type
 * @param  array $job_data
 * @param  string $user
 * @param  string $time
 * @param  string $success_text
 * @param  string $failure_text
 * @param  string $job_code
 * @param  int    $priority
 * @return string|integer ID of newly created job or error text
 */
function job_queue_add($type="",$job_data=array(),$user="",$time="", $success_text="", $failure_text="", $job_code="",$priority=NULL)
    {
    global $lang, $userref;
    if($time==""){$time=date('Y-m-d H:i:s');}
    if($type==""){return false;}
    if($user==""){$user=isset($userref)?$userref:0;}
    // Assign priority based on job type if not explicitly passed
    if(!is_int_loose($priority))
        {
        $priority = get_job_type_priority($type);
        }

    $job_data_json=json_encode($job_data,JSON_UNESCAPED_SLASHES); // JSON_UNESCAPED_SLASHES is needed so we can effectively compare jobs
    
    if($job_code == "")
        {
        // Generate a code based on job data to avoid incorrect duplicate job detection
        $job_code = $type . "_" . substr(md5(serialize($job_data)),10);
        }

    // Check for existing job matching
    $existing_user_jobs=job_queue_get_jobs($type,STATUS_ACTIVE,"",$job_code);
    if(count($existing_user_jobs)>0)
            {
            return $lang["job_queue_duplicate_message"];
            }
    ps_query("INSERT INTO job_queue (type,job_data,user,start_date,status,success_text,failure_text,job_code, priority) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)", array("s",$type,"s",$job_data_json,"i",$user,"s",$time,"i",STATUS_ACTIVE,"s",$success_text,"s",$failure_text,"s",$job_code,"i",(int)$priority));
    return sql_insert_id();
    }
    
/**
 * Update the data/status/time of a job queue record.
 *
 * @param  integer $ref
 * @param  array $job_data - pass empty array to leave unchanged
 * @param  string $newstatus
 * @param  string $newtime
 * @return void
 */
function job_queue_update($ref,$job_data=array(),$newstatus="", $newtime="", $priority=NULL)
    {
    $update_sql = array();
    $parameters = array();
    if (count($job_data) > 0)
        {
        $update_sql[] = "job_data = ?";
        $parameters = array_merge($parameters,array("s",json_encode($job_data)));
        } 
    if($newtime!="")
        {
        $update_sql[] = "start_date = ?";
        $parameters = array_merge($parameters,array("s",$newtime));
        }
    if($newstatus!="")
        {
        $update_sql[] = "status = ?";
        $parameters = array_merge($parameters,array("i",$newstatus));
        }
    if(is_int_loose($priority))
        {
        $update_sql[] = "priority = ?";
        $parameters = array_merge($parameters,array("i",(int)$priority));
        }
    if(count($update_sql) == 0)
        {
        return false;
        }

    $sql = "UPDATE job_queue SET " . implode(",",$update_sql) . " WHERE ref = ?";
    $parameters = array_merge($parameters,array("i",$ref));
    ps_query($sql,$parameters);
    }

/**
 * Delete a job queue entry if user owns job or user is admin
 *
 * @param  mixed $ref
 * @return void
 */
function job_queue_delete($ref)
    {
    global $userref;
    $query = "DELETE FROM job_queue WHERE ref= ?";
    $parameters = array("i",$ref);
    if (!checkperm('a') && !php_sapi_name() == "cli")
        {
        $query .= " AND user = ?";
        $parameters = array_merge($parameters,array("i",$userref));
        }
    ps_query($query, $parameters);
    }

/**
 * Gets a list of offline jobs
 *
 * @param  string $type         Job type
 * @param  string $status       Job status - see definitions.php
 * @param  int    $user         Job user
 * @param  string $job_code     Unique job code
 * @param  string $job_order_by 
 * @param  string $job_sort     
 * @param  string $find         
 * @param  bool   $returnsql    
 * @return mixed                Resulting array of requests or an SQL query object
 */
function job_queue_get_jobs($type="", $status=-1, $user="", $job_code="", $job_order_by="priority", $job_sort="asc", $find="", $returnsql=false)
    {
    global $userref;
    $condition = array();
    $parameters = array();
    if($type != "")
        {
        $condition[] = " type = ? ";
        $parameters = array_merge($parameters,array("s",$type));
        }
    if(!checkperm('a') && PHP_SAPI != 'cli')
        {
        // Don't show certain jobs for normal users
        $hiddentypes = array();
        $hiddentypes[] = "delete_file";
        $condition[] = " type NOT IN (" . ps_param_insert(count($hiddentypes)) . ")";  
        $parameters = array_merge($parameters, ps_param_fill($hiddentypes,"s"));
        }
        
    if((int)$status > -1)
        {
        $condition[] =" status = ? ";
        $parameters = array_merge($parameters,array("i",(int)$status));
        }

    if((int)$user > 0)
        {
        // Has user got access to see this user's jobs?
        if($user == $userref || checkperm_user_edit($user))
            {             
            $condition[] = " user = ?";
            $parameters = array_merge($parameters,array("i",(int)$user));
            }
        elseif(isset($userref))
            {
            // Only show own jobs
            $condition[] = " user = ?";
            $parameters = array_merge($parameters,array("i",(int)$userref));
            }
        else
            {
            // No access - return empty array
            return array();
            }
        }
    else
        {
        // Requested jobs for all users - only possible for cron or system admin, set condition otherwise
        if(PHP_SAPI != "cli" && !checkperm('a'))
            {
            if(isset($userref))
                {
                // Only show own jobs
                $condition[] = " user = ?";
                $parameters = array_merge($parameters,array("i",(int)$userref));
                }
            else
                {
                // No access - return nothing
                return array();
                }
            }
        }

    if($job_code!="")
        {
        $condition[] =" job_code = ?";
        $parameters = array_merge($parameters,array("s",$job_code));
        }

    if($find!="")
        {
        $find = '%' . $find . '%';
        $condition[] = " (j.ref LIKE ? OR j.job_data LIKE ? OR j.success_text LIKE ? OR j.failure_text LIKE ? OR j.user LIKE ? OR u.username LIKE ? OR u.fullname LIKE ?)";
        }

    $conditional_sql="";
    if (count($condition)>0){$conditional_sql=" where " . implode(" and ",$condition);}
    
    // Check order by value is valid
    if (!in_array(strtolower($job_order_by), array("priority", "ref", "type", "fullname", "status", "start_date")))
        {
        $job_order_by = "priority";
        }
    
    // Check sort value is valid
    if (!in_array(strtolower($job_sort), array("asc", "desc")))
        {
        $job_sort = "asc";
        }

    $sql = "SELECT j.ref, j.type, replace(replace(j.job_data,'\r',' '),'\n',' ') as job_data, j.user, j.status, j.start_date, j.success_text, j.failure_text,j.job_code, j.priority, u.username, u.fullname FROM job_queue j LEFT JOIN user u ON u.ref = j.user " . $conditional_sql . " ORDER BY " . $job_order_by . " " . $job_sort . ",start_date ASC";
    if($returnsql){return new PreparedStatementQuery($sql, $parameters);}
    $jobs=ps_query($sql, $parameters);
    return $jobs;
    }

/**
 * Get details of specified offline job
 *
 * @param  int $job identifier
 * @return array
 */
function job_queue_get_job($ref)
    {
    $sql = "SELECT j.ref, j.type, j.job_data, j.user, j.status, j.start_date, j.priority, j.success_text, j.failure_text, j.job_code, u.username, u.fullname FROM job_queue j LEFT JOIN user u ON u.ref = j.user WHERE j.ref = ?";
    $job_data=ps_query($sql, array("i",(int)$ref));

    return (is_array($job_data) && count($job_data)>0) ? $job_data[0] : array();
    }    

/**
 * Delete all jobs in the specified state
 *
 * @param  int $status to purge, whole queue will be purged if not set
 * @return void
 */
function job_queue_purge($status=0)
    {
    $deletejobs = job_queue_get_jobs('',$status == 0 ? '' : $status);
    if(count($deletejobs) > 0)
        {
        $deletejobs_sql = job_queue_get_jobs('',$status == 0 ? '' : $status,"","","priority","asc","",true);
        ps_query(
            "DELETE FROM job_queue 
                WHERE ref IN 
                    (SELECT jobs.ref FROM 
                        ( " . $deletejobs_sql->sql . ") AS jobs)"
            ,$deletejobs_sql->parameters);
        }
    }

/**
* Run offline job
* 
* @param  array    $job                 Metadata of the queued job as returned by job_queue_get_jobs()
* @param  boolean  $clear_process_lock  Clear process lock for this job
* 
* @return void
*/
function job_queue_run_job($job, $clear_process_lock)
    {
    // Runs offline job using defined job handler
    $jobref = $job["ref"];
    $job_data=json_decode($job["job_data"], true);

    $jobuser = $job["user"];
    if (!isset($jobuser) || $jobuser == 0 || $jobuser == "")
        {
        $logmessage = " - Job could not be run as no user was supplied #{$jobref}" . PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        job_queue_update($jobref,$job_data,STATUS_ERROR);
        return;
        }

    $jobuserdata = get_user($jobuser);
    setup_user($jobuserdata);
    $job_success_text=$job["success_text"];
    $job_failure_text=$job["failure_text"];

    // Variable used to avoid spinning off offline jobs from an already existing job.
    // Example: create_previews() is using extract_text() and both can run offline.
    global $offline_job_in_progress, $plugins;
    $offline_job_in_progress = false;

    if(is_process_lock('job_' . $jobref) && !$clear_process_lock)
        {
        $logmessage =  " - Process lock for job #{$jobref}" . PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        return;
        }
    else if($clear_process_lock)
        {
        $logmessage =  " - Clearing process lock for job #{$jobref}" . PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        clear_process_lock("job_{$jobref}");
        }
    
    set_process_lock('job_' . $jobref);
    
    $logmessage =  "Running job #" . $jobref . PHP_EOL;
    echo $logmessage;
    debug($logmessage);

    $logmessage =  " - Looking for " . __DIR__ . "/job_handlers/" . $job["type"] . ".php" . PHP_EOL;
    echo $logmessage;
    debug($logmessage);

    if (file_exists(__DIR__ . "/job_handlers/" . $job["type"] . ".php"))
        {
        $logmessage=" - Attempting to run job #" . $jobref . " using handler " . $job["type"]. PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        job_queue_update($jobref, $job_data,STATUS_INPROGRESS);
        $offline_job_in_progress = true;
        include __DIR__ . "/job_handlers/" . $job["type"] . ".php";
        // Update to mark job as complete and reset priority to the default according to job type
        job_queue_update($jobref, $job_data,STATUS_COMPLETE,date('Y-m-d H:i:s'),JOB_PRIORITY_COMPLETED);
        }
    else
        {
        // Check for handler in plugin
        $offline_plugins = $plugins;

        // Include plugins for this job user's group
        $group_plugins = ps_query("SELECT name, config, config_json, disable_group_select FROM plugins WHERE inst_version >= 0 AND disable_group_select = 0 AND find_in_set(?,enabled_groups) ORDER BY priority", array("i",$jobuserdata["usergroup"]), "plugins");
        foreach($group_plugins as $group_plugin)
            {
            include_plugin_config($group_plugin['name'],$group_plugin['config'],$group_plugin['config_json']);
            register_plugin($group_plugin['name']);
            register_plugin_language($group_plugin['name']);
            $offline_plugins[]=$group_plugin['name'];
            }	

        foreach($offline_plugins as $plugin)
            {
            if (file_exists(__DIR__ . "/../plugins/" . $plugin . "/job_handlers/" . $job["type"] . ".php"))
                {
                $logmessage=" - Attempting to run job #" . $jobref . " using handler " . $job["type"]. PHP_EOL;
                echo $logmessage;
                debug($logmessage);
                job_queue_update($jobref, $job_data,STATUS_INPROGRESS);
                $offline_job_in_progress = true;
                include __DIR__ . "/../plugins/" . $plugin . "/job_handlers/" . $job["type"] . ".php";
                job_queue_update($jobref, $job_data,STATUS_COMPLETE,date('Y-m-d H:i:s'),JOB_PRIORITY_COMPLETED);
                break;
                }
            }
        }
    
    if(!$offline_job_in_progress)
        {
        $logmessage="Unable to find handlerfile: " . $job["type"]. PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        job_queue_update($jobref,$job_data,STATUS_ERROR,date('Y-m-d H:i:s'));
        }
    
    $logmessage =  " - Finished job #" . $jobref . PHP_EOL;
    echo $logmessage;
    debug($logmessage);
    
    clear_process_lock('job_' . $jobref);
    }


/**
 * Get the default priority for a given job type
 *
 * @param  string $type      Name of job type e.g. 'collection_download'
 * 
 * @return int
 */
function get_job_type_priority($type="")
    {
    if(trim($type) != "")
        {
        switch (trim($type))
            {
            case 'collection_download':
            case 'create_download_file':
            case 'config_export':
            case 'csv_metadata_export':
                return JOB_PRIORITY_USER;
                break;
            
            case 'create_previews':
            case 'extract_text':
            case 'replace_batch_local':
            case 'create_alt_file':
            case 'delete_file':
            case 'update_resource':
            case 'upload_processing':
                return JOB_PRIORITY_SYSTEM;
                break;

            default:
                return JOB_PRIORITY_SYSTEM;
                break;
            }
        }
    return JOB_PRIORITY_SYSTEM;
    }