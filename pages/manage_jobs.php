<?php
include '../include/db.php';
include '../include/authenticate.php';
if(!checkperm('a'))
    {
    error_alert($lang["error-permissiondenied"], false, 401);
    }
include '../include/header.php';

$job_user    = getval("job_user",0,true);
$job_status  = getval("job_status",1,true);
$job_type    = getval("job_type","");
$job_orderby = getval("job_orderby","ref");
$job_sort    = (strtoupper(getval("job_sort","ASC")) == "ASC") ? "ASC" : "DESC";
$job_find    = getval("job_find","");

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


$deletejob = getval("deletejob",0,true);
if($deletejob > 0 && enforcePostRequest(false)))
    {
    job_queue_delete($deletejob);    
    }
//echo "<pre>" . print_r($curparams,true) . "</pre>";

//echo "<pre>" . print_r($jobs,true) . "</pre>";
//echo "<pre>" . print_r($_SERVER,true) . "</pre>";

$tabledata = array(
    "headers"=>array(
        "ref"=>array("name"=>$lang["property-reference"],"sortable"=>true),
        "type"=>array("name"=>$lang["type"],"sortable"=>true),
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
    $tablejob["status"] = $jobs[$n]["status"];
    $tablejob["start_date"] = nicedate($jobs[$n]["start_date"],true,true,true);
    $tablejob["tools"] = array();
    $tablejob["tools"][] = array(
        "class"=>"fa fa-trash",
        "text"=>$lang["action-delete"],
        "url"=>generateurl($baseurl . "/pages/manage_jobs.php",$curparams,array("deletejob" => $jobs[$n]["ref"])),
        "modal"=>false,
        );

    $tablejob["tools"][] = array(
        "class"=>"fa fa-info",
        "text"=>$lang["job_details"],
        "url"=>generateurl($baseurl . "/pages/job_view.php",array("ref" => $jobs[$n]["ref"])),
        "modal"=>true,
        );


    $tabledata["data"][] = $tablejob;
    }

echo "<div class='BasicsBox'>";
render_table($tabledata);
echo "</div>";

include '../include/footer.php';

function render_table($tabledata)
    {
    pager(true);

    echo "<div class='Listview'>";
    echo "<table border='0' cellspacing='0' cellpadding='0' class='ListviewStyle'>";
    echo "<tbody><tr class='ListviewTitleStyle'>";
    foreach($tabledata["headers"] as $header=>$headerdetails)
        {
        echo "<th>";
        if($headerdetails["sortable"])
            {
            $revsort = ($tabledata["sort"]=="ASC") ? "DESC" : "ASC";
            echo "<a href='" . generateurl($tabledata["defaulturl"],$tabledata["params"],array($tabledata["orderbyname"]=>$header,$tabledata["sortname"]=>($tabledata["orderby"] == $header ? $revsort : $tabledata["sort"]))) . "' onclick='return CentralSpaceLoad(this, true);'>" . htmlspecialchars($headerdetails["name"]);
            if($tabledata["orderby"] == $header)
                {
                // Currently sorted by this column
                echo "<span class='" . $revsort . "'></span>";
                }
            echo "</a>";
            }
        else
            {
            echo htmlspecialchars($headerdetails["name"]);
            }
        
        
        echo "</th>";
        }
    echo "</tr>"; // End of table header row

    foreach($tabledata["data"] as $rowdata)
        {
        echo "<tr>";
        foreach($tabledata["headers"] as $header=>$headerdetails)
            {
            if(isset($rowdata[$header]))
                {
                echo "<td>";
                // Data is present
                if($header == "tools")
                    {
                    echo "<div class='ListTools'>";
                    foreach($rowdata["tools"] as $toolitem)
                        {
                        echo "<a aria-hidden='true' class='" . htmlspecialchars($toolitem["class"]) . "'
                        href='" . htmlspecialchars($toolitem["url"]) . "' onclick='" . ($toolitem["modal"] ? "Modal" : "return CentalSpace") . "Load(this,true);' title='" . htmlspecialchars($toolitem["text"]) . "'></a>";
                        }
                    echo "</div>";
                    }
                else
                    {
                    echo htmlspecialchars($rowdata[$header]);
                    }
                echo "</td>";
                }
            else
                {
                echo "<td></td>";
                }
            }
        echo "</tr>";
        }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    }

