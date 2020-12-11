<?php
include '../include/db.php';
include '../include/authenticate.php';

$share_user    = getval("share_user",0,true);
if($share_user != $userref && !checkperm('a'))
    {
    // User does not have permission to see other user's shares
    $share_user = $userref;
    }

$share_group  = getval("share_group",-1,true);
//$share_type    = getval("share_type","");
$share_orderby = getval("share_orderby","ref");
$share_sort    = (strtoupper(getval("share_sort","ASC")) == "ASC") ? "ASC" : "DESC";
$share_type  = getval("share_type",-1,true);

if(!checkperm('a') || $share_user == $userref)
    {
    $pagetitle  = $lang["my_external_shares"];
    }
else
    {
    $pagetitle  = $lang["manage_shares_title"];
    }

$deleteshare = getval("delete_share",0,true);
$resetshare = getval("reset_share",0,true);
if($deleteshare > 0 && enforcePostRequest(true))
    {
    // TODO DELETE SHARE
    }
elseif(getval("purge_expired",'') != '' && enforcePostRequest(true))
    {
    // TODO DELETE EXPIRED SHARES
    }


$sharefltr = array(
    "share_group"       => $share_group,
    "share_user"        => $share_user,
    "share_order_by"    => $share_orderby,
    "share_sort"        => $share_sort,
    "share_type"        => $share_type,
    );

$shares = get_external_shares($sharefltr);
//$allgroups = get_usergroups();

//print_r($allgroups);
$allsharedgroups = array("-1" => $lang["action-select"]);
$sharedgroups = array_unique(array_column($shares,"usergroup"));
foreach($sharedgroups as $sharedgroup)
    {    
    $up_group = get_usergroup($sharedgroup);
    if($up_group)
        {
        $allsharedgroups[$sharedgroup] = $up_group["name"];
        }
    }

//echo "<pre>" . print_r($shares) . "</pre>";
$expiredshares = 0;
$per_page =getvalescaped("per_page",$default_perpage, true); 
$per_page = (!in_array($per_page,$results_display_array)) ? $default_perpage : $per_page;
$sharecount   = count($shares);
$totalpages = ceil($sharecount/$per_page);
$offset     = getval("offset",0,true);
if ($offset>$sharecount) {$offset = 0;}
$curpage=floor($offset/$per_page)+1;

$curparams = array(
    "share_user"    =>$share_user,
    "share_group"   =>$share_group,
    "share_orderby" => $share_orderby,
    "share_sort"    =>$share_sort,
    "share_type"    =>$share_type,
);

$url = generateurl($baseurl . "/pages/manage_external_shares.php",$curparams);

$tabledata = array(
    "class" => "ShareTable",
    "headers"=>array(
        "collection"=>array("name"=>$lang["collection"],"sortable"=>true),
        "resources"=>array("name"=>$lang["shared_resources"],"sortable"=>true),
        //"user"=>array("name"=>$lang["user"],"sortable"=>true),
        "sharedas"=>array("name"=>$lang["share_usergroup"],"sortable"=>true),
        "email"=>array("name"=>$lang["email"],"sortable"=>true),
        "fullname"=>array("name"=>$lang["user_created_by"],"sortable"=>true),
        "expires"=>array("name"=>$lang["expires"],"sortable"=>true),
        "date"=>array("name"=>$lang["created"],"sortable"=>true),
        "lastused"=>array("name"=>$lang["lastused"],"sortable"=>true),
        "access_key"=>array("name"=>$lang["accesskey"],"html"=>true,"sortable"=>true),
        "type"=>array("name"=>$lang["share_type"],"sortable"=>true),
        "tools"=>array("name"=>$lang["tools"],"sortable"=>false)
        ),

    "orderbyname" => "share_orderby",
    "orderby" => $share_orderby,
    "sortname" => "share_sort",
    "sort" => $share_sort,

    "defaulturl"=>$baseurl . "/pages/manage_external_shares.php",
    "params"=>$curparams,
    "pager"=>array("current"=>$curpage,"total"=>$totalpages),
    "data"=>array()
    );

if(!checkperm('a'))
    {
    unset($tabledata["headers"]["fullname"]);
    }
for($n=0;$n<$sharecount;$n++)
    {
    // TODO Check if expired
    // if(in_array($shares[$n]["status"],array(STATUS_ERROR,STATUS_COMPLETE)))
    //     {
    //     $$expiredshares++;
    //     }    

    if($n >= $offset && $offset + $per_page)
        {
        $tableshare =array();
        $tableshare["collection"] = $shares[$n]["collection"];
        //$tableshare["type"] = $shares[$n]["type"];
        if(checkperm('a'))
            {
            // Only required if can see shares for different users
            $tableshare["fullname"] = $shares[$n]["fullname"];
            }
        $tableshare["sharedas"]    = i18n_get_translated($shares[$n]["sharedas"]);
        $tableshare["resources"]    = $shares[$n]["resources"];
        $tableshare["email"]        = $shares[$n]["email"];
        $tableshare["expires"]      = nicedate($shares[$n]["expires"]);
        $tableshare["lastused"]     = $shares[$n]["lastused"];

        $keylink = $baseurl . "/?";
        $keylink .= (is_int_loose($shares[$n]["collection"]) && $shares[$n]["collection"] > 0) ? "c=" . (int)$shares[$n]["collection"] :  ((int)$shares[$n]["resources"] > 0 ? "r=" .(int)$shares[$n]["resources"] : "");
        $keylink .= "&k=" . $shares[$n]["access_key"];
        $tableshare["access_key"]   = "<a href='" . $keylink . "' target='_blank'>" . $shares[$n]["access_key"] . "<a>";

        $tableshare["date"] = nicedate($shares[$n]["date"],true,true,true); 
        if($shares[$n]["expires"] < date("Y-m-d H:i:s",time()))
            {
            $tableshare["alerticon"] = "fas fa-exclamation-triangle";
            }

        $tableshare["type"]       = (bool)$shares[$n]["upload"] ? $lang["share_type_upload"] : $lang["share_type_access"];


        $tableshare["tools"] = array();

        $tableshare["tools"][] = array(
            "icon"=>"fa fa-trash",
            "text"=>$lang["action-delete"],
            "url"=>"#",
            "modal"=>false,
            "onclick"=>"update_share(\"" . $shares[$n]["collection"] . "\",\"" . $shares[$n]["access_key"] . "\",\"delete_share\");return false;"
            );

        if(checkperm('a') || $shares[$n]["user"] == $userref)
            {
            $editlink = generateurl($baseurl . "/collection_share.php", 
                array(
                    "collection"    => $shares[$n]["collection"],
                    "key"           => $shares[$n]["access_key"],
                ));
            $tableshare["tools"][] = array(
                "icon"=>"fas fa-pencil",
                "text"=>$lang["action-edit"],
                "url"=>"#",
                "modal"=>false,
                "onclick"=>"return CentralSpaceLoad('" . $editlink . "');"
                );
            }


        $tabledata["data"][] = $tableshare;
        }
    }


include '../include/header.php';
?>

<script>
    function update_share(collection, key, action)
        {
        var temp_form = document.createElement("form");
        temp_form.setAttribute("id", "shareform");
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
            csrf.setAttribute("value", "<?php echo generateCSRFToken($usersession, "shareform"); ?>");
            temp_form.appendChild(csrf);
            <?php
            }
            ?>
        
        document.getElementById('share_list_container').appendChild(temp_form);
        CentralSpacePost(document.getElementById('shareform'),true);

        }
    function clearsharefilter()
        {
        jQuery('#share_group').val('-1');
        jQuery('#share_type').val('-1');
        jQuery('#autocomplete').val('');
        }

</script>

<div class='BasicsBox'>
    <h1><?php echo htmlspecialchars($pagetitle);render_help_link('user/manage_external_shares'); ?></h1>
    <?php
    $introtext=text("introtext");
    if ($introtext!="")
        {
        echo "<p>" . text("introtext") . "</p>";
        }

    if(checkperm('a') && $expiredshares > 0)
        {
        echo "<p><a href='#' onclick='if(confirm(\"" . $lang["share_confirm_purge"] . "\")){update_share(true,NULL,\"purge_shares\");}'>" . LINK_CARET . $lang["shares_action_purge_complete"] . "</a></p>";
        }

    ?>
    <form id="ShareFilterForm" method="POST" action="<?php echo $url; ?>">
        <?php generateFormToken('ShareFilterForm'); 

        $single_user_select_field_id = "share_user";
        $single_user_select_field_value = $share_user;
        ?>
        <div id="QuestionShareFilter">
            <?php
            render_dropdown_question($lang["property-user_group"], "share_group", $allsharedgroups, $share_group, " class=\"stdwidth\"");
            $sharetypes = array(
                    "-1"    => $lang["action-select"],
                    "0"     => $lang["share_type_access"],
                    "1"     => $lang["share_type_upload"],
                );

            render_dropdown_question($lang["share_type"], "share_type", $sharetypes, $share_type, " class=\"stdwidth\"");

            if(checkperm('a'))
                {?>
                <div class="Question"  id="QuestionShareUser">
                    <label><?php echo htmlspecialchars($lang["share_user"]); ?></label>
                    <?php include __DIR__ . "/../include/user_select.php" ?> 
                    <div class="clearerleft"></div>
                </div>
                <?php
                }?>

            


            <div class="Question"  id="QuestionShareFilterSubmit">
                <label></label>
                <input type="button" id="filter_button" class="searchbutton" value="<?php echo $lang['filterbutton']; ?>" onclick="return CentralSpacePost(document.getElementById('ShareFilterForm'));">
                <input type="button" id="clear_button" class="searchbutton" value="<?php echo $lang['clearbutton']; ?>" onclick="clearsharefilter();return CentralSpacePost(document.getElementById('ShareFilterForm'));">
                <div class="clearerleft"></div>
            </div>
        </div>

    </form>

    <?php

echo "<div id='share_list_container' class='BasicsBox'>\n";
render_table($tabledata);
echo "\n</div><!-- End of BasicsBox -->\n";

include '../include/footer.php';

