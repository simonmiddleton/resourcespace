<?php
# Reporting functions

function get_report_name($report)
    {
    # Translates or customizes the report name.
    $customName = hook('customreportname', '', array($report));
    if ($customName)
        return $customName;

    return lang_or_i18n_get_translated($report["name"], "report-");
    }

function get_reports()
    {
    # Returns an array of reports. The standard reports are translated using $lang. Custom reports are i18n translated.
    # The reports are always listed in the same order - regardless of the used language. 

    # Executes query.
    $r = sql_query("SELECT ref, `name`, `query`, support_non_correlated_sql FROM report ORDER BY name");

    # Translates report names in the newly created array.
    $return = array();
    for ($n = 0;$n<count($r);$n++)
        {
        if (!hook('ignorereport', '', array($r[$n])))
            {
            $r[$n]["name"] = get_report_name($r[$n]);
            $r[$n]["contains_date"] = report_has_date($r[$n]["query"]);
            $return[] = $r[$n]; # Adds to return array.
            }
        }
    return $return;
    }

/**
 * do_report   - Runs the specified report. This is used in a number of ways:-
 *               1) Outputs an HTML table to screen ($download = false)
 *               2) Produces a CSV 
 *                  - for direct download from team_report.php
 *                  - captured and saved as a CSV file if called by send_periodic_report_emails() and over 100 rows are returned
 *                
 *
 * @param  int $ref                 Report ID
 * @param  mixed $from_y            Start year (used for reprts with date placholders)
 * @param  mixed $from_m            Start month
 * @param  mixed $from_d            Start day
 * @param  mixed $to_y              End year
 * @param  mixed $to_m              To month
 * @param  mixed $to_d              To day
 * @param  mixed $download          Output as CSV attachment (default)/output directly to client
 * @param  mixed $add_border        Optional table border (not for download)
 * @param  mixed $foremail          Sending as email?
 * @param  array $search_params     Search parameters - {@see get_search_params()} - will run the report on the search 
 *                                  results and replace the '[non_correlated_sql]' placeholder with the search query.
 * 
 * @return void | string | array    Outputs CSV file, returns HTML table or returns an array with path to the CSV file, rows and filename
 */
function do_report($ref,$from_y,$from_m,$from_d,$to_y,$to_m,$to_d,$download=true,$add_border=false,$foremail=false, array $search_params=array())
    {
    # Run report with id $ref for the date range specified. Returns a result array.
    global $lang, $baseurl, $report_rows_attachment_limit;

    $ref_escaped = escape_check($ref);
    $report = sql_query("SELECT ref, `name`, `query`, support_non_correlated_sql FROM report WHERE ref = '{$ref_escaped}'");

    if (count($report) < 1)
        {
        return $lang['error_generic'];
        }

    $has_date_range = report_has_date($report[0]["query"]);
    $report=$report[0];
    $report['name'] = get_report_name($report);

    if($download || $foremail)
        {
        if ($has_date_range)
            {
            $filename=str_replace(array(" ","(",")","-","/"),"_",$report["name"]) . "_" . $from_y . "_" . $from_m . "_" . $from_d . "_" . $lang["to"] . "_" . $to_y . "_" . $to_m . "_" . $to_d . ".csv";
            }
        else
            {
            $filename=str_replace(array(" ","(",")","-","/"),"_",$report["name"]) . ".csv";
            }
        }

    if($results = hook("customreport", "", array($ref,$from_y,$from_m,$from_d,$to_y,$to_m,$to_d,$download,$add_border, $report)))
        {
        // Hook has created the $results array
        }
    else
        {
        // Generate report results normally
        $sql=$report["query"];
        $sql=str_replace("[from-y]",$from_y,$sql);
        $sql=str_replace("[from-m]",$from_m,$sql);
        $sql=str_replace("[from-d]",$from_d,$sql);
        $sql=str_replace("[to-y]",$to_y,$sql);
        $sql=str_replace("[to-m]",$to_m,$sql);
        $sql=str_replace("[to-d]",$to_d,$sql);

        global $view_title_field;
        $sql=str_replace("[title_field]",$view_title_field,$sql);

        // IF report supports being run on search results
        if($report['support_non_correlated_sql'] === '1' && !empty($search_params))
            {
            $search_sql = do_search(
                $search_params['search'],
                $search_params['restypes'],
                $search_params['order_by'],
                $search_params['archive'],
                -1, # fetchrows
                $search_params['sort'],
                false, # access_override
                $search_params['starsearch'],
                false, # ignore_filters
                false, # return_disk_usage
                $search_params['recentdaylimit'],
                false, # go
                false, # stats_logging
                true, # return_refs_only
                false, # editable_only
                true # returnsql
            );
            if(!is_string($search_sql))
                {
                debug("Invalid SQL returned by do_search(). Report cannot be generated");
                return false;
                }
            $ncsql = sprintf('(SELECT ncsql.ref FROM (%s) AS ncsql)', $search_sql);

            $sql = str_replace(REPORT_PLACEHOLDER_NON_CORRELATED_SQL, $ncsql, $sql);
            }

        $results = sql_query($sql);
        }
    
    $resultcount = count($results);
    if($resultcount == 0)
        {
        // No point downloading as the resultant file will be empty
        $download=false;
        }            
    if ($download)
        {
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=" . $filename . "");
        }

    if ($download || ($foremail && $resultcount > $report_rows_attachment_limit))
        {
        if($foremail)
            {
            ob_clean();
            ob_start();
            }
        for ($n=0;$n<$resultcount;$n++)
            {
            $result=$results[$n];
            if ($n==0)
                {
                $f=0;
                foreach ($result as $key => $value)
                    {
                    $f++;
                    if ($f>1) {echo ",";}
                    if ($key!="thumbnail")
                        {echo "\"" . lang_or_i18n_get_translated($key,"columnheader-") . "\"";}
                    }
                echo "\n";
                }
            $f=0;
            foreach ($result as $key => $value)
                {
                $f++;
                if ($f>1) {echo ",";}
                $custom = hook('customreportfield', '', array($result, $key, $value, $download));
                if ($custom !== false)
                    {
                    echo $custom;
                    }
                else if ($key!="thumbnail")
                    {
                    $value=lang_or_i18n_get_translated($value, "usergroup-");
                    $value=str_replace('"','""',$value); # escape double quotes
                    if (substr($value,0,1)==",") {$value=substr($value,1);} # Remove comma prefix on dropdown / checkbox values 
                    echo "\"" . $value  . "\"";
                        
                    }
                }
            echo "\n";
            }

        if($foremail)
            {
            $output = ob_get_contents();
            ob_end_clean();
            $unique_id=uniqid();
            $reportfile = get_temp_dir(false, "Reports") . "/Report_" . $unique_id . ".csv";
            file_put_contents($reportfile,$output);
            return array("file" => $reportfile,"filename" => $filename, "rows" => $resultcount);
            }
        }
    else
        {
        # Not downloading - output a table

        // If report results are too big, display the first rows and notify user they should download it instead
        $output = '';
        if($resultcount > $report_rows_attachment_limit)
            {
            $results = array_slice($results, 0, $report_rows_attachment_limit);

            // Catch the error now and place it above the table in the output
            render_top_page_error_style($lang['team_report__err_report_too_long']);
            $output = ob_get_contents();
            ob_clean();
            ob_start();
            }

        // Pre-render process: Process nodes search syntax (e.g @@228 or @@!223) and add a new column that contains the node list and their names
        if(isset($results[0]['search_string']))
            {
            $results = process_node_search_syntax_to_names($results, 'search_string');
            }
        $border="";
        if ($add_border) {$border="border=\"1\"";}
        $output .= "<br /><h2>" . $report['name'] . "</h2><style>.InfoTable td {padding:5px;}</style><table $border class=\"InfoTable\">";
        for ($n=0;$n<count($results);$n++)
            {
            $result=$results[$n];
            if ($n==0)
                {
                $f=0;
                $output.="<tr>\r\n";
                foreach ($result as $key => $value)
                    {
                    $f++;
                    if ($key=="thumbnail")
                        {$output.="<td><strong>Link</strong></td>\r\n";}
                    else
                        {
                        $output.="<td><strong>" . lang_or_i18n_get_translated($key,"columnheader-") . "</strong></td>\r\n";
                        }
                    }
                $output.="</tr>\r\n";
                }
            $f=0;
            $output.="<tr>\r\n";
            foreach ($result as $key => $value)
                {
                $f++;
                if ($key=="thumbnail")
                    {
                    $thm_path=get_resource_path($value,true,"thm",false,"",$scramble=-1,$page=1,false);
                    if (!file_exists($thm_path)){
                        $resourcedata=get_resource_data($value);
                        $thm_url= $baseurl . "/gfx/" . get_nopreview_icon($resourcedata["resource_type"],$resourcedata["file_extension"],true);
                        }
                    else
                        {
                        $thm_url=get_resource_path($value,false,"col",false,"",-1,1,false);
                        }
                    $output.="<td><a href=\"" . $baseurl . "/?r=" . $value .  "\" target=\"_blank\"><img src=\"" . $thm_url . "\"></a></td>\r\n";
                    }
                else
                    {
                    $custom = hook('customreportfield', '', array($result, $key, $value, $download));
                    if ($custom !== false)
                        {
                        $output .= $custom;
                        }
                    else
                        {
                        $output.="<td>" . strip_tags_and_attributes(lang_or_i18n_get_translated($value, "usergroup-"),array("a"),array("href","target")) . "</td>\r\n";
                        }
                    }
                }
            $output.="</tr>\r\n";
            }
        $output.="</table>\r\n";
        if (count($results)==0) {$output.=$lang["reportempty"];}
        return $output;
        }

    exit();
    }

/**
* Creates a new automatic periodic e-mail report
*
*/
function create_periodic_email($user, $report, $period, $email_days, array $user_groups, array $search_params)
    {
    # Delete any matching rows for this report/period.
    $query = sprintf("
            DELETE
              FROM report_periodic_emails
             WHERE user = '%s'
               AND report = '%s'
               AND period = '%s';
        ",
        escape_check($user),
        escape_check($report),
        escape_check($period)
    );
    sql_query($query);

    # Insert a new row.
    $query = sprintf("
            INSERT INTO report_periodic_emails (
                                                   user,
                                                   report,
                                                   period,
                                                   email_days,
                                                   search_params
                                               )
                 VALUES (
                            '%s', 
                            '%s', 
                            '%s', 
                            '%s', 
                            '%s'   
                        );
        ",
        escape_check($user),
        escape_check($report),
        escape_check($period),
        escape_check($email_days),
        escape_check(json_encode($search_params, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK))
    );
    sql_query($query);
    $ref = sql_insert_id();
    
    # Send to all users?
    if (checkperm('m'))
        {
        if(!empty($user_groups))
            {

            // Manually implode usergroups to allow an escape_check()
            $ugstring="";
            $ugindex=0;
            $ugcount=count($user_groups);
            foreach($user_groups as $ug) {
                $ugindex+=1;
                if($ugindex < $ugcount) {
                    $ugstring = $ugstring . escape_check($ug) . ",";
                }
                else {
                    $ugstring = $ugstring . escape_check($ug);
                }
            }

            sql_query("UPDATE report_periodic_emails SET user_groups = '" . $ugstring . "' WHERE ref = '" . escape_check($ref) . "';");
            }
        }

    # Return
    return true;
    }


function send_periodic_report_emails($echo_out = true, $toemail=true)
    {
    # For all configured periodic reports, send a mail if necessary.
    global $lang,$baseurl, $report_rows_zip_limit, $email_notify_usergroups;

    # Query to return all 'pending' report e-mails, i.e. where we haven't sent one before OR one is now overdue.
    $query = "
        SELECT pe.ref,
               pe.user,
               pe.send_all_users,
               pe.user_groups,
               pe.report,
               pe.period,
               pe.email_days,
               pe.last_sent,
               pe.search_params,
               u.email,
               r.name
          FROM report_periodic_emails pe
          JOIN user u ON pe.user = u.ref
          JOIN report r ON pe.report = r.ref
         WHERE pe.last_sent IS NULL
            OR date_add(date(pe.last_sent), INTERVAL pe.email_days DAY) <= date(now());
    ";

    // Keep record of temporary CSV/ZIP files to delete after emails have been sent
    $deletefiles = array();

    $reports=sql_query($query);
    foreach ($reports as $report)
        {
        $start=time()-(60*60*24*$report["period"]);

        $from_y = date("Y",$start);
        $from_m = date("m",$start);
        $from_d = date("d",$start);

        $to_y = date("Y");
        $to_m = date("m");
        $to_d = date("d");

        // Translates the report name.
        $report["name"] = lang_or_i18n_get_translated($report["name"], "report-");

        $search_params = (trim($report['search_params']) !== '' ? json_decode($report['search_params'], true) : []);

        # Generate report (table or CSV)
        $output=do_report($report["report"], $from_y, $from_m, $from_d, $to_y, $to_m, $to_d,false,true, $toemail, $search_params);

        // Formulate a title
        $title = $report["name"] . ": " . str_replace("?",$report["period"],$lang["lastndays"]);

        // If report is large, make it an attachment (requires $use_phpmailer=true)
        $reportfiles = array();
        if(is_array($output) && isset($output["file"]))
            {
            $deletefiles[] = $output["file"];
            //Include the file as an attachment
            if($output["rows"] > $report_rows_zip_limit)
                {
                // Convert to  zip file
                $unique_id=uniqid();
                $zipfile = get_temp_dir(false, "Reports") . "/Report_" . $unique_id . ".zip";
                $zip = new ZipArchive();
                $zip->open($zipfile, ZIPARCHIVE::CREATE);
                $zip->addFile($output["file"], $output["filename"]);
            
                $zip->close();
                $deletefiles[] = $zipfile;
                $zipname = str_replace(".csv",".zip", $output["filename"]);
                $reportfiles[$zipname] = $zipfile;
                }
            else
                {
                $reportfiles[$output["filename"]] = $output["file"];
                }
            $output = str_replace("%%REPORTTITLE%%", $title, $lang["report_periodic_email_report_attached"]);
            }

        // Send mail to original user - this contains the unsubscribe link
        // Note: this is basically the only way at the moment to delete a periodic report
        $delete_link = "<br /><br />" . $lang["report_delete_periodic_email_link"] . "<br /><a href=\"" . $baseurl . "/?dr=" . $report["ref"] . "\" target=\"_blank\">" . $baseurl . "/?dr=" . $report["ref"] . "</a>";
        
        $unsubscribe="<br /><br />" . $lang["unsubscribereport"] . "<br /><a href=\"" . $baseurl . "/?ur=" . $report["ref"] . "\" target=\"_blank\">" . $baseurl . "/?ur=" . $report["ref"] . "</a>";
        $email=$report["email"];

        // Check user unsubscribed from this report
        $query = sprintf('
                SELECT true as `value`
                  FROM report_periodic_emails_unsubscribe
                 WHERE user_id = "%s"
                   AND periodic_email_id = "%s";
            ',
            $report['user'],
            $report['ref']
        );
        $unsubscribed_user = sql_value($query, false);
        if(!$unsubscribed_user)
            {
            if ($echo_out) {echo $lang["sendingreportto"] . " " . $email . "<br />" . $output . $delete_link . $unsubscribe . "<br />";}
            send_mail($email,$title,$output . $delete_link  . $unsubscribe,"","","","","","","",$reportfiles);
            }

        // Jump to next report if this should only be sent to one user
        if(!$report['send_all_users'] && empty($report['user_groups']))
            {
            # Mark as done.
            sql_query('UPDATE report_periodic_emails set last_sent = now() where ref = "' . $report['ref'] . '";');
            
            continue;
            }

        $users = array();
        // Send e-mail reports to users belonging to the specific user groups
        if(empty($report['user_groups']))
            {
            if ($report['send_all_users'])
                {
                // Send to all users is deprecated. Send to $email_notify_usergroups or Super Admin if not set
                if (!empty($email_notify_usergroups))
                    {
                    foreach ($email_notify_usergroups as $usergroup)
                        {
                        if(get_usergroup($usergroup)!==false)
                            {
                            $addusers = get_users($usergroup,"","u.username",false,-1,1);
                            $users = array_merge($users,$addusers);
                            }
                        }
                    }
                else
                    {
                    $users = get_notification_users("SYSTEM_ADMIN");
                    }
                }
            }
        else
            {
            $users = get_users($report['user_groups'],"","u.username",false,-1,1);
            }

        foreach($users as $user)
            {
            $email = $user['email'];

            # Do not send to original report user, as they receive the mail with the unsubscribe link above.
            if(($email == $report['email']) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                continue;
                }

            // Check user unsubscribed from this report
            $query = sprintf('
                    SELECT true as `value`
                      FROM report_periodic_emails_unsubscribe
                     WHERE user_id = "%s"
                       AND periodic_email_id = "%s";
                ',
                $user['ref'],
                $report['ref']
            );
            $unsubscribed_user = sql_value($query, false);

            if(!$unsubscribed_user)
                {
                $unsubscribe_link = sprintf('<br />%s<br />%s/?ur=%s',
                    $lang['unsubscribereport'],
                    $baseurl,
                    $report['ref']
                );

                if ($echo_out) {echo $lang["sendingreportto"] . " " . $email . "<br />" . $output . $unsubscribe_link . "<br />";}
                send_mail($email, $title, $output . $unsubscribe_link);
                }
            }

        # Mark as done.
        sql_query('UPDATE report_periodic_emails set last_sent = now() where ref = "' . $report['ref'] . '";');
        }

    $GLOBALS["use_error_exception"] = true;
    foreach($deletefiles as $deletefile)
        {
        try
            {
            unlink($deletefile);
            }
        catch(Exception $e)
            {
            debug("Unable to delete - file not found: " . $deletefile);
            }
        }
    unset($GLOBALS["use_error_exception"]);
    }

function delete_periodic_report($ref)
    {
    global $userref;
    sql_query('DELETE FROM report_periodic_emails WHERE user = "' . $userref . '" AND ref = "' . $ref . '";');
    sql_query('DELETE FROM report_periodic_emails_unsubscribe WHERE periodic_email_id = "' . $ref . '"');

    return true;
    }

function unsubscribe_user_from_periodic_report($user_id, $periodic_email_id)
    {
    $query = sprintf('
            INSERT INTO report_periodic_emails_unsubscribe (
                                                               user_id,
                                                               periodic_email_id
                                                           )
                 VALUES (
                            "%s", # user_id
                            "%s"  # periodic_email_id
                        );
        ',
        $user_id,
        $periodic_email_id
    );
    sql_query($query);

    return true;
    }

function get_translated_activity_type($activity_type)
    {
    # Activity types are stored in plain text english in daily_stat. This function will use language strings to resolve a translated value where one is set.
    global $lang;
    $key="stat-" . strtolower(str_replace(" ","",$activity_type));
    if (!isset($lang[$key]))
        {
        return $activity_type;
        }
    else
        {
        return $lang[$key];
        }
    }

/**
 * Checks for the presence of date placeholders in a report's SQL query.  
 *
 * @param  string   $query   The report's SQL query.
 * 
 * @return boolean  Returns true if a date placeholder was found else false.
 */
function report_has_date(string $query)
    {
    $date_placeholders = array('[from-y]','[from-m]','[from-d]','[to-y]','[to-m]','[to-d]');
    $date_present = false;

    foreach ($date_placeholders as $placeholder)
        {
        $position = strpos($query,$placeholder);
        if ($position !== false)
            {
            $date_present = true;
            break;
            }
        }

    return $date_present;
    }

/**
 * Checks for the presence of date placeholders in a report's sql query using the report's id.
 *
 * @param  int   $report   Report id of the report to retrieve the query data from the report table.
 * 
 * @return boolean  Returns true if a date placeholder was found else false.
 */
function report_has_date_by_id(int $report)
    {
    $query = sql_value("SELECT `query` as value FROM report WHERE ref = '" . escape_check($report) . "'",0);
    $result = report_has_date($query);
    return $result;
    }