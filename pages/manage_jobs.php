<?php
include '../include/db.php';
include '../include/authenticate.php';

$job_user    = getval("job_user",0,true);
if($job_user != $userref && !checkperm('a'))
    {
    // User does not have permission to see other user's jobs
    $job_user = $userref;
    }
$job_status  = getval("job_status",-1,true);
$job_type    = getval("job_type","");
$job_orderby = getval("job_orderby","ref");
$job_sort    = (strtoupper(getval("job_sort","ASC")) == "ASC") ? "ASC" : "DESC";
$job_find    = getval("job_find","");

if(!checkperm('a') || $job_user == $userref)
    {
    $pagetitle  = $lang["my_jobs"];
    }
else
    {
    $pagetitle  = $lang["manage_jobs_title"];
    }

$deletejob = getval("delete_job",0,true);
$resetjob = getval("reset_job",0,true);
if($deletejob > 0 && enforcePostRequest(true))
    {
    job_queue_delete($deletejob);
    }
elseif($resetjob > 0 && enforcePostRequest(true))
    {
    clear_process_lock("job_{$resetjob}");
    job_queue_update($resetjob,array(),1);
    }
elseif(getval("purge_jobs",'') != '' && enforcePostRequest(true))
    {
    job_queue_purge(STATUS_COMPLETE);
    job_queue_purge(STATUS_ERROR);
    }

$jobs = job_queue_get_jobs($job_type,$job_status,$job_user,'',$job_orderby,$job_sort,$job_find);
$endedjobs = 0;
$per_page =getvalescaped("per_page",$default_perpage, true); 
$per_page = (!in_array($per_page,$results_display_array)) ? $default_perpage : $per_page;
$jobcount   = count($jobs);
$totalpages = ceil($jobcount/$per_page);
$offset     = getval("offset",0,true);
if ($offset>$jobcount) {$offset = 0;}
$curpage=floor($offset/$per_page)+1;

$curparams = array(
    "job_user"=>$job_user,
    "job_status"=>$job_status,
    "job_type"=>$job_type,
    "job_orderby" => $job_orderby,
    "job_sort"=>$job_sort,
    "job_find"=>$job_find
);

$url = generateurl($baseurl . "/pages/manage_jobs.php",$curparams);

$tabledata = array(
    "class" => "JobTable",
    "headers"=>array(
        "ref"=>array("name"=>$lang["property-reference"],"sortable"=>true),
        "type"=>array("name"=>$lang["job_queue_type"],"sortable"=>true),
        "fullname"=>array("name"=>$lang["user"],"sortable"=>true),
        "status"=>array("name"=>$lang["status"],"sortable"=>true),
        "start_date"=>array("name"=>$lang["date"],"sortable"=>true),
        "tools"=>array("name"=>$lang["tools"],"sortable"=>false)
        ),

    "orderbyname" => "job_orderby",
    "orderby" => $job_orderby,
    "sortname" => "job_sort",
    "sort" => $job_sort,

    "defaulturl"=>$baseurl . "/pages/manage_jobs.php",
    "params"=>$curparams,
    "pager"=>array("current"=>$curpage,"total"=>$totalpages, "per_page"=>$per_page, "break" =>false),
    "data"=>array()
    );

if(!checkperm('a'))
    {
    unset($tabledata["headers"]["fullname"]);
    }
for($n=0;$n<$jobcount;$n++)
    {
    if(in_array($jobs[$n]["status"],array(STATUS_ERROR,STATUS_COMPLETE)))
        {
        $endedjobs++;
        }    

    if($n >= $offset && ($n < $offset + $per_page))
        {
        $tablejob =array();
        $tablejob["ref"] = $jobs[$n]["ref"];
        $tablejob["type"] = $jobs[$n]["type"];
        if(checkperm('a'))
            {
            // Only required if can see jobs for different users
            $tablejob["fullname"] = $jobs[$n]["fullname"];
            }
        $tablejob["status"] = isset($lang["job_status_" . $jobs[$n]["status"]]) ? $lang["job_status_" . $jobs[$n]["status"]] : $jobs[$n]["status"];
        $tablejob["start_date"] = nicedate($jobs[$n]["start_date"],true,true,true); 
        if($jobs[$n]["status"] == STATUS_ERROR || $jobs[$n]["status"] !== STATUS_COMPLETE && $jobs[$n]["start_date"] < date("Y-m-d H:i:s",time()-24*60*60))
            {
            $tablejob["alerticon"] = "fas fa-exclamation-triangle";
            }
        $tablejob["tools"] = array();
        if(checkperm('a'))
            {
            $tablejob["tools"][] = array(
                "icon"=>"fa fa-info",
                "text"=>$lang["job_details"],
                "url"=>generateurl($baseurl . "/pages/job_details.php",array("job" => $jobs[$n]["ref"])),
                "modal"=>true,
                );
            }

        $tablejob["tools"][] = array(
            "icon"=>"fa fa-trash",
            "text"=>$lang["action-delete"],
            "url"=>"#",
            "modal"=>false,
            "onclick"=>"update_job(\"" . $jobs[$n]["ref"] . "\",\"delete_job\");return false;"
            );

        if(checkperm('a') && $jobs[$n]["status"] != STATUS_ACTIVE)
            {
            $tablejob["tools"][] = array(
                "icon"=>"fas fa-undo",
                "text"=>$lang["job_reset"],
                "url"=>"#",
                "modal"=>false,
                "onclick"=>"update_job(\"" . $jobs[$n]["ref"] . "\",\"reset_job\");return false;"
                );
            }


        $tabledata["data"][] = $tablejob;
        }
    }


include '../include/header.php';
?>

<script>
    function update_job(ref, action)
        {
        var temp_form = document.createElement("form");
        temp_form.setAttribute("id", "jobform");
        temp_form.setAttribute("method", "post");
        temp_form.setAttribute("action", '<?php echo $url ?>');

        var i = document.createElement("input");
        i.setAttribute("type", "hidden");
        i.setAttribute("name", action);
        i.setAttribute("value", ref);
        temp_form.appendChild(i);

        <?php
        if($CSRF_enabled)
            {
            ?>
            var csrf = document.createElement("input");
            csrf.setAttribute("type", "hidden");
            csrf.setAttribute("name", "<?php echo $CSRF_token_identifier; ?>");
            csrf.setAttribute("value", "<?php echo generateCSRFToken($usersession, "jobform"); ?>");
            temp_form.appendChild(csrf);
            <?php
            }
            ?>
        
        document.getElementById('job_list_container').appendChild(temp_form);
        CentralSpacePost(document.getElementById('jobform'),true);

        }
</script>

<div class='BasicsBox'>
    <h1><?php echo htmlspecialchars($pagetitle);render_help_link('user/manage_jobs'); ?></h1>
    <?php
    $introtext=text("introtext");
    if ($introtext!="")
        {
        echo "<p>" . text("introtext") . "</p>";
        }

    if(checkperm('a') && $endedjobs > 0)
        {
        echo "<p><a href='#' onclick='if(confirm(\"" . $lang["job_confirm_purge"] . "\")){update_job(true,\"purge_jobs\");}'>" . LINK_CARET . $lang["jobs_action_purge_complete"] . "</a></p>";
        }

    ?>
    <form id="JobFilterForm" method="POST" action="<?php echo $url; ?>">
        <?php generateFormToken('JobFilterForm'); 

        $single_user_select_field_id = "job_user";
        $single_user_select_field_value = $job_user;
        ?>
        <div id="QuestionJobFilter">

            <div class="Question" id="QuestionJobType">
                <label><?php echo htmlspecialchars($lang["job_filter_type"]); ?></label>
                <select class="stdwidth" id="job_type" name="job_type">
                    <?php 
                    // Not filtered by default when searching, add option to filter by month
                    echo "<option " .  ($job_type == 0 ? " selected" : "") . " value=''>" . $lang["all"] . "</option>\n";                   
                    $alljobtypes = array_unique(array_column($jobs,"type"));
                    foreach ($alljobtypes as $avail_jobtype)
                        {
                        echo "<option " .  ($avail_jobtype == $job_type ? " selected" : "") . " value=\"" .  htmlspecialchars($avail_jobtype) . "\">" . htmlspecialchars($avail_jobtype) . "</option>\n";
                        }
                    ?>
                </select>
                <div class="clearerleft"></div> 
            </div>   
            <div class="Question" id="QuestionJobStatus">
                <label><?php echo htmlspecialchars($lang["job_filter_status"]); ?></label>
                <select class="stdwidth" id="job_status" name="job_status">
                    <?php 
                    // Not filtered by default when searching, add option to filter by month
                    echo "<option " .  ($job_status == -1 ? " selected" : "") . " value='-1'>" . $lang["all"] . "</option>\n";                   
                    foreach(array(0,1,2,3,5) as $status)
                        {
                        echo "<option " .  ($status == $job_status ? " selected" : "") . " value=\"" .  $status . "\">" . $lang["job_status_" . $status] . "</option>\n";
                        }
                    ?>
                </select>
                <div class="clearerleft"></div>   
            </div>   
            <?php
            if(checkperm('a'))
                {?>
                <div class="Question"  id="QuestionJobUser">
                    <label><?php echo htmlspecialchars($lang["job_filter_user"]); ?></label>
                    <?php include __DIR__ . "/../include/user_select.php" ?> 
                    <div class="clearerleft"></div>
                </div>
                <?php
                }?>

            <div class="Question"  id="QuestionJobFilterSubmit">
                <label></label>
                <input type="button" id="datesubmit" class="searchbutton" value="<?php echo $lang['filterbutton']; ?>" onclick="return CentralSpacePost(document.getElementById('JobFilterForm'));">
                <input type="button" id="datesubmit" class="searchbutton" value="<?php echo $lang['clearbutton']; ?>" onclick="addUser();jQuery('#job_status').val('-1');jQuery('#job_type').val('');return CentralSpacePost(document.getElementById('JobFilterForm'));">
                <div class="clearerleft"></div>
            </div>
        </div>

    </form>

    <?php

echo "<div id='job_list_container' class='BasicsBox'>\n";
render_table($tabledata);
echo "\n</div><!-- End of BasicsBox -->\n";

include '../include/footer.php';

