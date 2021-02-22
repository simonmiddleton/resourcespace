<?php
include "../include/db.php";

include "../include/authenticate.php";

$ref=getvalescaped("ref","",true);
$k=getvalescaped("k","");
$modal = (getval("modal", "") == "true");

$filter_by_type = trim(getval("filter_by_type", ""));
$filter_by_usageoption = getval("filter_by_usageoption", null, true);
$filter_url_params = array(
    "filter_by_type" => $filter_by_type,
    "filter_by_usageoption" => $filter_by_usageoption,
);

// Logs can sometimes contain confidential information and the user looking at them must have admin permissions set.
// Some log records can be viewed by all users. Ensure access control by allowing only white listed log codes to bypass 
// permissions checks.
$safe_log_codes = array(LOG_CODE_DOWNLOADED);
$resource_access = get_resource_access($ref);
$bypass_permission_check = in_array($filter_by_type, $safe_log_codes) && in_array($resource_access, array(0, 1));
if(!checkperm('v') && !$bypass_permission_check)
    {
    die($lang['log-adminpermissionsrequired']);
    }

# fetch the current search (for finding simlar matches)
$search=getvalescaped("search","");
$order_by=getvalescaped("order_by","relevance");
$search_offset=getvalescaped("search_offset",0,true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getvalescaped("archive",0,true);
$starsearch=getvalescaped("starsearch","");
$default_sort_direction="DESC";
if (substr($order_by,0,5)=="field"){$default_sort_direction="ASC";}
$sort=getval("sort",$default_sort_direction);

$offset=getvalescaped("offset",0);
$per_page=getvalescaped("per_page_list", $default_perpage_list);rs_setcookie('per_page_list', $per_page);
// When filtering by download records only the table output will be slightly different, showing only the following columns:
// date, user, usage option and usage reason
$filter_dld_records_only = ($filter_by_type == LOG_CODE_DOWNLOADED);


# next / previous resource browsing
$go=getval("search_go","");
if ($go!="")
	{
	$origref=$ref; # Store the reference of the resource before we move, in case we need to revert this.
	
	# Re-run the search and locate the next and previous records.
	$modified_result_set=hook("modifypagingresult"); 
	if ($modified_result_set){
		$result=$modified_result_set;
	} else {
		$result=do_search($search,$restypes,$order_by,$archive,240+$search_offset+1,$sort,false,$starsearch);
	}
	if (is_array($result))
		{
		# Locate this resource
		$pos=-1;
		for ($n=0;$n<count($result);$n++)
			{
			if ($result[$n]["ref"]==$ref) {$pos=$n;}
			}
		if ($pos!=-1)
			{
			if (($go=="previous") && ($pos>0)) {$ref=$result[$pos-1]["ref"];}
			if (($go=="next") && ($pos<($n-1))) {$ref=$result[$pos+1]["ref"];if (($pos+1)>=($search_offset+72)) {$search_offset=$pos+1;}} # move to next page if we've advanced far enough
			}
		else
			{
			?>
			<script type="text/javascript">
			alert('<?php echo $lang["resourcenotinresults"] ?>');
			</script>
			<?php
			}
		}
	# Check access permissions for this new resource, if an external user.
	$newkey=hook("nextpreviewregeneratekey");
	if (is_string($newkey)) {$k=$newkey;}
	if ($k!="" && !check_access_key($ref,$k)) {$ref=$origref;} # cancel the move.
	}

$url_params = array(
    "ref" => $ref,
    "search" => $search,
    "search_offset" => $search_offset,
    "order_by" => $order_by,
    "sort" => $sort,
    "archive" => $archive,
    "k" => $k,
);

include "../include/header.php";
?>
<div class="BasicsBox">
<?php
if (getval("context",false) == 'Modal'){$previous_page_modal = true;}
else {$previous_page_modal = false;}
if(!$modal)
    {
    ?>
    <p><a href="<?php echo generateurl($baseurl_short . "pages/view.php",$url_params);?>"  onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
    <?php
    }
elseif ($previous_page_modal)
    {
    ?>
    <p><a href="<?php echo generateurl($baseurl_short . "pages/view.php",$url_params);?>"  onClick="return ModalLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["backtoresourceview"]?></a></p>
    <?php
    }
?>
    <div class="RecordHeader">
        <div class="BackToResultsContainer">
            <div class="backtoresults">
                <a href="<?php echo generateURL("{$baseurl_short}pages/log.php", array_merge($url_params, $filter_url_params), array("search_go" => "previous")) . hook("nextpreviousextraurl"); ?>" onclick="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Load(this, true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["previousresult"]?></a>
                <?php 
                hook("viewallresults");
                if ($k=="") { ?>
                |
                <a href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($search_offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>&k=<?php echo urlencode($k)?>" onclick="return CentralSpaceLoad(this, true);"><?php echo $lang["viewallresults"]?></a>
                <?php } ?>
                |
                <a href="<?php echo generateURL("{$baseurl_short}pages/log.php", array_merge($url_params, $filter_url_params), array("search_go" => "next")) . hook("nextpreviousextraurl"); ?>" onclick="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Load(this, true);"><?php echo $lang["nextresult"]?>&nbsp;<?php echo LINK_CARET ?></a>
                <?php
                if($modal)
                    {
                    ?>
                    &nbsp;&nbsp;<a class="maxLink fa fa-expand" href="<?php echo generateURL("{$baseurl_short}pages/log.php", array_merge($url_params, $filter_url_params)); ?>" onclick="return CentralSpaceLoad(this);"></a>
                    &nbsp;<a href="#" class="closeLink fa fa-times" onclick="ModalClose();"></a>
                    <?php
                    }
                ?>
            </div>
        </div>
        <h1><?php echo $lang["resourcelog"] . " : " . $lang["resourceid"] . " " .  htmlspecialchars($ref);render_help_link("user/logs");?></h1>
    </div>

<?php
$fetchrows = $offset + $per_page;

$filters = array();
if($filter_by_type != "")
    {
    $filters["r.type"] = $filter_by_type;
    }
if(!is_null($filter_by_usageoption) && $filter_by_usageoption >= 0)
    {
    $filters["r.usageoption"] = $filter_by_usageoption;
    }

if($bypass_permission_check)
    {
    $log = bypass_permissions(array("v"), "get_resource_log", array($ref, $fetchrows, $filters));
    }
else
    {
    $log = get_resource_log($ref, $fetchrows, $filters);
    }

# Calculate pager vars.
$results=count($log);
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$url = generateURL(
    "{$baseurl_short}pages/log.php",
    array_merge($url_params, $filter_url_params)
) . hook("nextpreviousextraurl");
?>
<div class="TopInpageNav"><!--<?php pager(false); ?></div>-->
<div class="InpageNavLeftBlock"><?php echo $lang["resultsdisplay"]?>:
	<?php 
	for($n=0;$n<count($list_display_array);$n++){?>
	<?php if ($per_page==$list_display_array[$n]){?><span class="Selected"><?php echo $list_display_array[$n]?></span><?php } else { ?><a onclick="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Load(this, true);" href="<?php echo $url; ?>&per_page_list=<?php echo $list_display_array[$n]?>"><?php echo $list_display_array[$n]?></a><?php } ?>&nbsp;|
	<?php } ?>
	<?php if ($per_page==99999){?><span class="Selected"><?php echo $lang["all"]?></span><?php } else { ?><a onclick="return <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Load(this, true);" href="<?php echo $url; ?>&per_page_list=99999"><?php echo $lang["all"]?></a><?php } ?>
	</div> <?php pager(false); ?></div>


<div class="Listview">
<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<!--Title row-->	
<tr class="ListviewTitleStyle">
    <td width="10%"><?php echo $lang["date"]?></td>
    <td width="10%"><?php echo $lang["user"]?></td>
    <?php
    if($filter_dld_records_only)
        {
        ?>
        <td width="20%"><?php echo $lang["indicateusagemedium"]; ?></td>
        <td><?php echo $lang["usage"]; ?></td>
        <?php
        }
    else
        {
        ?>
        <td width="10%"><?php echo $lang["action"]; ?></td>
        <td width="10%"><?php echo $lang["field"]; ?></td>
        <td><?php echo $lang["difference"]; ?></td>
        <?php
        hook("log_extra_columns_header");
        }
        ?>
</tr>
<?php
for ($n=$offset;(($n<count($log)) && ($n<($offset+$per_page)));$n++)
	{
    if (!isset($lang["log-".$log[$n]["type"]])){$lang["log-".$log[$n]["type"]]="";}
    $logusertext = $log[$n]["access_key"] != "" ? ($lang["externalusersharing"] . ": " . $log[$n]["access_key"] . " " . $lang["viauser"] . " " . (empty($log[$n]["shared_by"]) ? $log[$n]["fullname"] : $log[$n]["shared_by"])) : $log[$n]["fullname"];
	?>
	<!--List Item-->
	<tr>
	<td nowrap><?php echo nicedate($log[$n]["date"],true,true, true)?></td>
	<td nowrap><?php echo hook("userdisplay","",array($log[$n])) ? "" : $logusertext ?></td>
	
    <?php
    if($filter_dld_records_only)
        {
        ?>
        <td><?php
        if(isset($download_usage_options[$log[$n]["usageoption"]]) && $log[$n]["usageoption"] != -1 && $log[$n]["usageoption"] >= 0)
            {
            echo nl2br(htmlspecialchars($download_usage_options[$log[$n]["usageoption"]]));
            }
        ?></td>
        <td><?php echo htmlspecialchars($log[$n]["notes"]); ?></td>
        </tr>
        <?php
        continue;
        }
    ?>
    <td><?php echo htmlspecialchars($lang["log-" . $log[$n]["type"]]." ".$log[$n]["notes"])?></td>
	<td><?php echo htmlspecialchars($log[$n]["title"])?></td>
	<td><?php
    if($log[$n]["diff"]!=="")
        {
        $difftext = $log[$n]["diff"];
        if($log[$n]["resource_type_field"] != "" && in_array($log[$n]["resource_type_field"],$FIXED_LIST_FIELD_TYPES))
            {
            $transdifflines = array();
            $difflines = explode("\n",$difftext);
            foreach($difflines as $diffline)
                {
                $action = substr($diffline,0,1);
                $transdifflines[] = $action . " " . i18n_get_translated(substr($diffline,2));
                }
            $difftext = implode("\n",$transdifflines);
             }
        echo nl2br(format_string_more_link(htmlspecialchars(wordwrap($difftext,75,"\n",true))));
        }
    if ($log[$n]["usageoption"]!="-1" && $log[$n]["usageoption"]!="")
        {
        // if usageoption is set to -1 when logging, you can avoid the usage description here
        // Check that the value in the table is valid first
        if (isset($download_usage_options[$log[$n]["usageoption"]]))
            {
            if ($log[$n]["notes"]=="" || $log[$n]["notes"]=="-1")
                { 
                echo $lang["indicateusagemedium"] . ": " . nl2br(htmlspecialchars($download_usage_options[$log[$n]["usageoption"]])); 
                }
            else
                {
                echo $lang["usage"] . ": " . nl2br(htmlspecialchars($log[$n]["notes"])) . "<br />" . $lang["indicateusagemedium"] . ": " . nl2br(htmlspecialchars($download_usage_options[$log[$n]["usageoption"]]));
                }
            }
	    }

	# For purchases, append size and price
	if ($log[$n]["type"]=="p")
        {
        echo " (" . ($log[$n]["purchase_size"]==""?$lang["collection_download_original"]:$log[$n]["purchase_size"]) . ", " . $currency_symbol . number_format($log[$n]["purchase_price"],2) . ")";
        }
	
	# For downloads, add size 
	if ($log[$n]["type"]=="d")
        {
		if(is_numeric($log[$n]["purchase_size"])) // The user downloaded an alternative file
			{
			echo " (" . $lang["alternativefiles"] . " <a href='" . $baseurl . "/pages/alternative_files.php?ref=" . $ref . "&alternative=" . $log[$n]["purchase_size"] . "'>#" . $log[$n]["purchase_size"] . "<a/>)";
			}
		else
			{
			echo " (" . ($log[$n]["size"]==""?$lang["collection_download_original"]:$log[$n]["size"]) . ")";	
			}
        }

        hook("log_diff_td_extra","",array($ref));
	?></td>
	<?php hook("log_extra_columns_row") ?>
	</tr>
	<?php
	}
?>
</table>
</div> <!-- end of BasicsBox -->

<div class="BottomInpageNav"><?php pager(false); ?></div>
<?php
include "../include/footer.php";