<?php
include dirname(__FILE__)."/../../../include/db.php";

include dirname(__FILE__)."/../../../include/authenticate.php";

$is_admin = checkperm("a");
if (!$is_admin && !checkperm("lm")) {exit ("Permission denied.");}
global $baseurl;


# Check if it's necessary to upgrade the database structure
include dirname(__FILE__) . "/../upgrade/upgrade.php";


$offset=getval("offset",0,true);
if (array_key_exists("findtext",$_POST)) {$offset=0;} # reset page counter when posting
$findtext=getval("findtext","");

$delete=getval("delete","");
if ($delete!="" && enforcePostRequest(false))
	{
	# Delete license
	ps_query("delete from license where ref= ?", ['i', $delete]);
	}



include dirname(__FILE__)."/../../../include/header.php";

$url_params = array(
    'search'     => getval('search',''),
    'order_by'   => getval('order_by',''),
    'collection' => getval('collection',''),
    'offset'     => getval('offset',0),
    'restypes'   => getval('restypes',''),
    'archive'    => getval('archive','')
);
?>
<div class="BasicsBox"> 
<h1><?php echo $lang["managelicenses"]; ?></h1>
<?php
    $links_trail = array(
        array(
            'title' => !$is_admin ? htmlspecialchars($lang["home"]) : htmlspecialchars($lang["teamcentre"]),
            'href'  => $baseurl_short . (!$is_admin ? "pages/home.php" : "pages/team/team_home.php"),
            'menu'  => !$is_admin ? false : true
        ),
        array(
            'title' => $lang["managelicenses"]
        )
    );

    renderBreadcrumbs($links_trail); ?>
    
<form method=post id="licenselist" action="<?php echo $baseurl_short ?>plugins/licensemanager/pages/list.php" onSubmit="CentralSpacePost(this);return false;">
<?php generateFormToken("licenselist"); ?>
<input type=hidden name="delete" id="licensedelete" value="">
 
<?php 
$sql = '';
$params = [];
if ($findtext!="")
    {
    $sql    = "where description like CONCAT('%', ?, '%') or holder like CONCAT('%', ?, '%') or license_usage like CONCAT('%', ?, '%')";
    $params = ['s', $findtext, 's', $findtext, 's', $findtext];
    }

$licenses=ps_query("select " . columns_in("license",null,"licensemanager") . " from license $sql order by ref", $params);

# pager
$per_page = $default_perpage_list;
$results=count($licenses);
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$url="list.php?findtext=".urlencode($findtext)."&offset=". $offset;
$jumpcount=1;
?>

<p><a href="<?php echo $baseurl_short ?>plugins/licensemanager/pages/edit.php?ref=new" onClick="CentralSpaceLoad(this);return false;"><?php echo LINK_PLUS_CIRCLE . $lang["new_license"] ?></a></p>


<div class="Listview">
<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<tr class="ListviewTitleStyle">
<td><?php echo $lang["license_id"] ?></a></td>
<td><?php echo $lang["type"] ?></a></td>
<td><?php echo $lang["licensor_licensee"] ?></a></td>
<td><?php echo $lang["indicateusagemedium"] ?></a></td>
<td><?php echo $lang["description"] ?></a></td>
<td><?php echo $lang["fieldtitle-expiry_date"] ?></a></td>
<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
</tr>

<?php
for ($n=$offset;(($n<count($licenses)) && ($n<($offset+$per_page)));$n++)
	{
    $license=$licenses[$n];
    $license_usage_mediums = trim_array(explode(", ", $license["license_usage"]));
    $translated_mediums = "";
    $url_params['ref'] = $license["ref"];
	?>
	<tr>
    <td>
            <?php echo $license["ref"] ?></td>
			<td><?php echo ($license["outbound"]?$lang["outbound"]:$lang["inbound"]) ?></td>
			<td><?php echo $license["holder"] ?></td>
			<td><?php
				foreach ($license_usage_mediums as $medium)
					{
					$translated_mediums = $translated_mediums . lang_or_i18n_get_translated($medium, "license_usage-") . ", ";
					}
				$translated_mediums = substr($translated_mediums, 0, -2); # Remove the last ", "
				echo $translated_mediums;
				?>
			</td>
			<td><?php echo $license["description"] ?></td>
			<td><?php echo ($license["expires"]==""?$lang["no_expiry_date"]:nicedate($license["expires"])) ?></td>
		
			<td><div class="ListTools">
			<a href="<?php echo generateURL($baseurl_short . "plugins/licensemanager/pages/edit.php",$url_params); ?>" onClick="return CentralSpaceLoad(this,true);"><i class="fas fa-edit"></i>&nbsp;<?php echo $lang["action-edit"]?></a>
			<a href="<?php echo generateURL($baseurl_short . "plugins/licensemanager/pages/delete.php",$url_params); ?>" onClick="return CentralSpaceLoad(this,true);"><i class="fa fa-trash"></i>&nbsp;<?php echo $lang["action-delete"]?></a>
			</div></td>
	</tr>
	<?php
	}
?>

</table>
</div>
<div class="BottomInpageNav"><?php pager(true); ?></div>



		<div class="Question">
			<label for="find"><?php echo $lang["licensesearch"]?><br/></label>
			<div class="tickset">
			 <div class="Inline">			
			<input type=text placeholder="<?php echo $lang['searchbytext']?>" name="findtext" id="findtext" value="<?php echo escape($findtext)?>" maxlength="100" class="shrtwidth" />
			
			<input type="button" value="<?php echo $lang['clearbutton']?>" onClick="$('findtext').value='';CentralSpacePost(document.getElementById('licenselist'));return false;" />
			<input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["searchbutton"]?>&nbsp;&nbsp;" />
			 
			</div>
			</div>
			<div class="clearerleft"> 
			</div>
		</div>

</form>
<?php

include dirname(__FILE__)."/../../../include/footer.php";