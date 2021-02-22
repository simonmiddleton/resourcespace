<?php
include '../include/db.php';
include '../include/authenticate.php';

$share_user    = getval("share_user",0,true);
if($share_user != $userref && !(checkperm('a') || checkperm('noex')))
    {
    // User does not have permission to see other user's shares
    $share_user = $userref;
    }

$share_group        = getval("share_group",-1,true);
$share_orderby      = getval("share_orderby","ref");
$share_sort         = (strtoupper(getval("share_sort","ASC")) == "ASC") ? "ASC" : "DESC";
$share_type         = getval("share_type",-1,true);
$share_collection   = getval("share_collection",-1,true);

if($share_collection != -1 &&!collection_readable($share_collection))
    {
    error_alert($lang["error-permissiondenied"],true);
    exit();
    }
if(!checkperm('a') || $share_user == $userref)
    {
    $pagetitle  = $lang["my_shares"];
    }
else
    {
    $pagetitle  = $lang["manage_shares_title"];
    }

$ajax              = ('true' == getval('ajax', '') ? true : false);
$delete_access_key = getval('delete_access_key', '');
$messages           = array();

// Process access key deletion
if($delete_access_key != "" && enforcePostRequest($ajax))
    {
    $deleteresource   = getvalescaped('delete_resource', '');
    $deletecollection = getvalescaped('delete_collection', '');
    $response   = array(
        'success' => false
    );

    if($deleteresource != "" && $deleteresource != "-")
        {
        delete_resource_access_key($deleteresource, $delete_access_key);
        $response['success'] = true;
        }
    elseif($deletecollection != "")
        {
        delete_collection_access_key($deletecollection, $delete_access_key);
        $response['success'] = true;
        }
    else
        {
        delete_collection_access_key(0, $delete_access_key);
        $response['success'] = true;
        }

    exit(json_encode($response));
    }

$sharefltr = array(
    "share_group"       => $share_group,
    "share_user"        => $share_user,
    "share_order_by"    => $share_orderby,
    "share_sort"        => $share_sort,
    "share_type"        => $share_type,
    "share_collection"  => $share_collection,
    );

if(getval("purge_expired",'') != '' && enforcePostRequest(true))
    {
    $deleted = purge_expired_shares($sharefltr);
    $messages[] = str_replace("%%DELETEDCOUNT%%",$deleted,$lang["shares_purged_message"]);
    }

$shares = get_external_shares($sharefltr);
$allsharedgroups = array("-1" => ($share_group == -1 ? $lang["action-select"] : $lang["all"]));
$sharedgroups = array_unique(array_column($shares,"usergroup"));
foreach($sharedgroups as $sharedgroup)
    {    
    $up_group = get_usergroup($sharedgroup);
    if($up_group)
        {
        $allsharedgroups[$sharedgroup] = $up_group["name"];
        }
    }
$allsharedcols = array("-1" => ($share_collection == -1 ? $lang["action-select"] : $lang["all"]));
$sharedcols = array_unique(array_column($shares,"collection"));
foreach($sharedcols as $sharedcol)
    {    
    $coldetails = get_collection($sharedcol);
    if($coldetails)
        {
        $allsharedcols[$sharedcol] = i18n_get_translated($coldetails["name"]);
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
    "share_user"        =>$share_user,
    "share_group"       =>$share_group,
    "share_orderby"     =>$share_orderby,
    "share_collection"  =>$share_collection,
    "share_sort"        =>$share_sort,
    "share_type"        =>$share_type,
    "per_page"          =>$per_page,
    "offset"            =>$offset,
);

$url = generateurl($baseurl . "/pages/manage_external_shares.php",$curparams);

$tabledata = array(
    "class" => "ShareTable",
    "headers"=>array(
        "collection"=>array("name"=>$lang["collectionid"],"html"=>true,"sortable"=>true),
        "resource"=>array("name"=>$lang["columnheader-resource_id"],"sortable"=>true),
        "sharedas"=>array("name"=>$lang["share_usergroup"],"sortable"=>true),
        "email"=>array("name"=>$lang["email"],"sortable"=>true),
        "fullname"=>array("name"=>$lang["user_created_by"],"sortable"=>true),
        "expires"=>array("name"=>$lang["expires"],"sortable"=>true),
        "date"=>array("name"=>$lang["created"],"sortable"=>true),
        "lastused"=>array("name"=>$lang["lastused"],"sortable"=>true),
        "access_key"=>array("name"=>$lang["accesskey"],"html"=>true,"sortable"=>true),
        "upload"=>array("name"=>$lang["share_type"],"sortable"=>true),
        "tools"=>array("name"=>$lang["tools"],"sortable"=>false)
        ),

    "orderbyname" => "share_orderby",
    "orderby" => $share_orderby,
    "sortname" => "share_sort",
    "sort" => $share_sort,

    "defaulturl"=>$baseurl . "/pages/manage_external_shares.php",
    "params"=>$curparams,
    "pager"=>array("current"=>$curpage,"total"=>$totalpages, "per_page"=>$per_page, "break" =>false),
    "data"=>array()
    );

if(!checkperm('a'))
    {
    unset($tabledata["headers"]["fullname"]);
    }
for($n=0;$n<$sharecount;$n++)
    {
    if($n >= $offset && ($n < $offset + $per_page))
        {
        $colshare = is_int_loose($shares[$n]["collection"]) && $shares[$n]["collection"] > 0;
        $tableshare =array();
        $tableshare["rowid"] = "access_key_" . $shares[$n]["access_key"];
        $tableshare["collection"] = "<a href='" . $baseurl_short . "?c=" . $shares[$n]["collection"] . "' target='_blank'>" . $shares[$n]["collection"] . "</a>";
        if(checkperm('a'))
            {
            // Only required if user can see shares for different users
            $tableshare["fullname"] = $shares[$n]["fullname"];
            }
        $tableshare["sharedas"]     = i18n_get_translated($shares[$n]["sharedas"]);
        $tableshare["resource"]     = $shares[$n]["resource"];
        $tableshare["email"]        = $shares[$n]["email"];
        $tableshare["expires"]      = $shares[$n]["expires"] ? nicedate($shares[$n]["expires"]) : $lang["never"];
        $tableshare["lastused"]     = $shares[$n]["lastused"];

        $keylink = $baseurl . "/?";
        $keylink .= $colshare ? "c=" . (int)$shares[$n]["collection"] :  ((int)$shares[$n]["resource"] > 0 ? "r=" .(int)$shares[$n]["resource"] : "");
        $keylink .= "&k=" . $shares[$n]["access_key"];
        $tableshare["access_key"]   = "<a href='" . $keylink . "' target='_blank'>" . $shares[$n]["access_key"] . "<a>";

        $tableshare["date"] = nicedate($shares[$n]["date"],true,true,true); 
        if($shares[$n]["expires"] != "" && $shares[$n]["expires"] < date("Y-m-d H:i:s",time()))
            {
            $expiredshares++;
            $tableshare["alerticon"] = "fas fa-exclamation-triangle";
            }

        $tableshare["upload"] = (bool)$shares[$n]["upload"] ? $lang["share_type_upload"] : $lang["share_type_view"];


        $tableshare["tools"] = array();

        if(!$colshare || collection_writeable($shares[$n]["collection"]))
            {
            $tableshare["tools"][] = array(
            "icon"=>"fa fa-trash",
            "text"=>$lang["action-delete"],
            "url"=>"#",
            "modal"=>false,
            "onclick"=>"delete_access_key(\"" . $shares[$n]["access_key"] . "\",\"" . $shares[$n]["resource"] . "\",\"" . $shares[$n]["collection"] . "\");return false;"
            );
            }

        if(checkperm('a') || $shares[$n]["user"] == $userref)
            {
            if((bool)$shares[$n]["upload"])
                {
                // Edit an upload share
                $editlink = generateurl($baseurl . "/pages/share_upload.php", 
                    array(
                        "share_collection"    => $shares[$n]["collection"],
                        "uploadkey"     => $shares[$n]["access_key"],
                    ));
                }
            elseif($colshare)
                {
                $editlink = generateurl($baseurl . "/pages/collection_share.php", 
                    array(
                        "ref"               => $shares[$n]["collection"],
                        "editaccess"        => $shares[$n]["access_key"],
                        "editaccesslevel"   => $shares[$n]["access"],
                        "editexpiration"    => $shares[$n]["expires"],
                        "editgroup"         => $shares[$n]["usergroup"],
                        "password"          => $shares[$n]["password_hash"] != "" ? "true" : "",
                    ));
                }
            else
                {
                // Edit a resource share
                $editlink = generateurl($baseurl . "/pages/resource_share.php", 
                    array(
                        "ref"               => $shares[$n]["resource"],
                        "editaccess"        => $shares[$n]["access_key"],
                        "editaccesslevel"   => $shares[$n]["access"],
                        "editexpiration"    => $shares[$n]["expires"],
                        "usergroup"         => $shares[$n]["usergroup"],
                        "password"          => $shares[$n]["password_hash"] != "" ? "true" : "",
                    ));
                }

            $tableshare["tools"][] = array(
                "icon"=>"fa fa-pencil",
                "text"=>$lang["action-edit"],
                "url"=>$editlink,
                "modal"=>false,
                "onclick"=>"return CentralSpaceLoad(\"" . $editlink . "\");"
                );
            }

        $tabledata["data"][] = $tableshare;
        }
    }


include '../include/header.php';
?>

<script>
function delete_access_key(access_key, resource, collection)
    {
    var confirmationMessage = "<?php echo $lang['confirmdeleteaccessresource']; ?>";
    var post_data = {
        ajax: true,
        delete_access_key: access_key,
        delete_resource: resource,
        <?php echo generateAjaxToken("delete_access_key"); ?>
    };

    if(collection != '')
        {
        confirmationMessage = "<?php echo $lang['confirmdeleteaccess']; ?>";
        delete post_data.resource;
        post_data.delete_collection = collection;
        }

    if(confirm(confirmationMessage))
        {
        jQuery.post('<?php echo $url; ?>', post_data, function(response)
                {
                if(response.success === true)
                    {
                    jQuery('#access_key_' + access_key).remove();
                    }
                },
                'json'
            );
        
        return false;
        }

    return true;
    }

function purge_expired_shares()
    {
    var temp_form = document.createElement("form");
    temp_form.setAttribute("id", "purgeform");
    temp_form.setAttribute("method", "post");
    temp_form.setAttribute("action", '<?php echo $url ?>');

    var i = document.createElement("input");
    i.setAttribute("type", "hidden");
    i.setAttribute("name", "purge_expired");
    i.setAttribute("value", "true");
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
        }?>

    confirmationMessage = "<?php echo $lang['share_confirm_purge']; ?>";

    if(confirm(confirmationMessage))
        {
        document.getElementById('share_list_container').appendChild(temp_form);
        CentralSpacePost(document.getElementById('purgeform'),true);
        }
    }


function clearsharefilter()
    {
    jQuery('#share_collection').val('-1');
    jQuery('#share_group').val('-1');
    jQuery('#share_type').val('-1');
    jQuery('#share_user').val('');
    jQuery('#autocomplete').val('');
    CentralSpacePost(document.getElementById('ShareFilterForm'));
    }

</script>

<div class='BasicsBox'>
    <h1><?php echo htmlspecialchars($pagetitle);render_help_link('user/manage_external_shares'); ?></h1>
    <?php

    if(count($messages) > 0)
        {
        echo "<div class='PageInformal'>" . implode("<br/>", $messages) . "</div>";
        }
    $introtext=text("introtext");
    if ($introtext!="")
        {
        echo "<p>" . text("introtext") . "</p>";
        }

    if(checkperm('a') && $expiredshares > 0)
        {
        echo "<p><a href='#' onclick='purge_expired_shares();return false;'>" . LINK_CARET . $lang["share_purge_text"] . "</a></p>";
        }

    ?>
    <form id="ShareFilterForm" method="POST" action="<?php echo $url; ?>">
        <?php generateFormToken('ShareFilterForm'); 

        $single_user_select_field_id = "share_user";
        $single_user_select_field_value = $share_user;
        ?>
        <div id="QuestionShareFilter">
            <?php
            render_dropdown_question($lang["collection"], "share_collection", $allsharedcols, $share_collection, " class=\"stdwidth\"");
            render_dropdown_question($lang["property-user_group"], "share_group", $allsharedgroups, $share_group, " class=\"stdwidth\"");
            $sharetypes = array(
                    "-1"    => ($share_type == -1 ? $lang["action-select"] : $lang["all"]),
                    "0"     => $lang["share_type_view"],
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

