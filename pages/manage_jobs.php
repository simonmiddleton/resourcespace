<?php
include '../include/db.php';
include '../include/authenticate.php';

$job_user    = getval("job_user",0,true);
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
if($deletejob > 0 && enforcePostRequest(true))
    {
    job_queue_delete($deletejob);
    }

$resetjob = getval("reset_job",0,true);
if($resetjob > 0 && enforcePostRequest(true))
    {
    job_queue_update($resetjob,array(),1);
    }

$jobs = job_queue_get_jobs($job_type,$job_status,$job_user,'',$job_orderby,$job_sort,$job_find);

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

$cururl = generateurl($baseurl . "/pages/manage_jobs.php",$curparams);



//echo "<pre>" . print_r($curparams,true) . "</pre>";

//echo "<pre>" . print_r($jobs,true) . "</pre>";
//echo "<pre>" . print_r($_SERVER,true) . "</pre>";

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
    "pager"=>array("current"=>$curpage,"total"=>$totalpages),
    "data"=>array()
    );


for($n=$offset;$n<$jobcount;$n++)
    {        
    $tablejob =array();
    $tablejob["ref"] = $jobs[$n]["ref"];
    $tablejob["type"] = $jobs[$n]["type"];
    $tablejob["fullname"] = $jobs[$n]["fullname"];
    $tablejob["status"] = isset($lang["job_status_" . $jobs[$n]["status"]]) ? $lang["job_status_" . $jobs[$n]["status"]] : $jobs[$n]["status"];
    $tablejob["start_date"] = nicedate($jobs[$n]["start_date"],true,true,true);
    $tablejob["tools"] = array();


    if(checkperm('a'))
        {
        $tablejob["tools"][] = array(
            "class"=>"fa fa-info",
            "text"=>$lang["job_details"],
            "url"=>generateurl($baseurl . "/pages/job_details.php",array("job" => $jobs[$n]["ref"])),
            "modal"=>true,
            );
        }

    $tablejob["tools"][] = array(
        "class"=>"fa fa-trash",
        "text"=>$lang["action-delete"],
        "url"=>"#",
        "modal"=>false,
        "onclick"=>"update_job(\"" . $jobs[$n]["ref"] . "\",\"delete_job\");return false;"
        );

    $tablejob["tools"][] = array(
        "class"=>"fa fa-chevron-circle-right",
        "text"=>$lang["job_reset"],
        "url"=>"#",
        "modal"=>false,
        "onclick"=>"update_job(\"" . $jobs[$n]["ref"] . "\",\"reset_job\");return false;"
        );


    $tabledata["data"][] = $tablejob;
    }


include '../include/header.php';
?>

<script>
    function update_job(ref, action)
        {
        var temp_form = document.createElement("form");
        temp_form.setAttribute("id", "jobform");
        temp_form.setAttribute("method", "post");
        temp_form.setAttribute("action", '<?php echo $cururl ?>');

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


<h1><?php echo htmlspecialchars($pagetitle);render_help_link();?></h1>
<?php
$introtext=text("introtext");
if ($introtext!="")
{
echo "<p>" . text("introtext") . "</p>";
}

echo "<div id='job_list_container' class='BasicsBox'>\n";
render_table($tabledata);
echo "\n</div><!-- End of BasicsBox -->\n";

include '../include/footer.php';

