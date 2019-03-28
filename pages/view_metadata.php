<?php

# Global everything we need, in case called inside a function (e.g. for push_metadata support)
global $k,$lang,$show_resourceid,$show_access_field,$show_resource_type,$show_hitcount,$show_contributed_by,$baseurl_short,$search,$enable_related_resources,$force_display_template_order_by,$modal, $sort_tabs;

// Is this a modal?
$modal=(getval("modal","")=="true");

// -----------------------  Tab calculation -----------------
$fields_tab_names = array();
	
foreach ($fields as $field) {
	$fields_tab_names[] = $field['tab_name'];
	$resources_per_tab_name[$field['tab_name']][] = $field['ref'];
}

$fields_tab_names = array_values(array_unique($fields_tab_names));

// Clean the tabs by removing the ones that would just be empty:
$tabs_with_data = array();
foreach ($fields_tab_names as $tabname) {
	for ($i = 0; $i < count($fields); $i++) { 
		
		$displaycondition = check_view_display_condition($fields, $i);
	
		if($displaycondition && $tabname == $fields[$i]['tab_name'] && $fields[$i]['value'] != '' && $fields[$i]['value'] != ',' && $fields[$i]['display_field'] == 1 && ($access == 0 || ($access == 1 && !$field['hide_when_restricted']))) {
			$tabs_with_data[] = $tabname;
		}

	}
}
$fields_tab_names = array_intersect($fields_tab_names, $tabs_with_data);

if ($sort_tabs)
    {
    sort($fields_tab_names);
    }

if(isset($related_type_show_with_data)) {
	// Get resource type tab names, excluding the current resource type (if any set):
	$resource_type_tab_names = sql_array("SELECT tab_name as value FROM resource_type WHERE ref<>'" . $resource['resource_type'] . "'", "");
	$resource_type_tab_names = array_values(array_unique($resource_type_tab_names));

	// These are the tab names which will be rendered for the resource specified:
	$fields_tab_names = array_values(array_unique((array_merge($fields_tab_names, $resource_type_tab_names))));
}

// Make sure the fields_tab_names is empty if there are no values:
foreach ($fields_tab_names as $key => $value) {
	if(empty($value)) {
		unset($fields_tab_names[$key]);
	}
}

$modified_view_tabs=hook("modified_view_tabs","view",array($fields_tab_names));if($modified_view_tabs!=='' && is_array($modified_view_tabs)){$fields_tab_names=$modified_view_tabs;}
        
?>
        
        
<div id="Metadata">
<?php
global $extra;
$extra="";

#  -----------------------------  Draw tabs ---------------------------
$tabname="";
$tabcount=0;
$tmp = hook("tweakfielddisp", "", array($ref, $fields)); if($tmp) $fields = $tmp;
if((isset($fields_tab_names) && !empty($fields_tab_names)) && count($fields) > 0) { ?>
	
	<div class="TabBar">
	
	<?php
		foreach ($fields_tab_names as $tabname) { ?>

			<div id="<?php echo ($modal ? "Modal" : "")?>tabswitch<?php echo $tabcount; ?>" class="Tab<?php if($tabcount == 0) { ?> TabSelected<?php } ?>">
				<a href="#" onclick="Select<?php echo ($modal ? "Modal" : "")?>Tab(<?php echo $tabcount; ?>);return false;"><?php echo i18n_get_translated($tabname)?></a>
			</div>
		
		<?php 
			$tabcount++;
		} ?>

	</div> <!-- end of TabBar -->
	<script type="text/javascript">
	function Select<?php echo ($modal ? "Modal" : "")?>Tab(tab) {
		// Deselect all tabs
		<?php for($n = 0; $n < $tabcount; $n++) { ?>
		document.getElementById("<?php echo ($modal ? "Modal" : "")?>tab<?php echo $n; ?>").style.display="none";
		document.getElementById("<?php echo ($modal ? "Modal" : "")?>tabswitch<?php echo $n; ?>").className="Tab";
		<?php } ?>
		document.getElementById("<?php echo ($modal ? "Modal" : "")?>tab" + tab).style.display="block";
		document.getElementById("<?php echo ($modal ? "Modal" : "")?>tabswitch" + tab).className="Tab TabSelected";
	}
	</script>

<?php
} ?>

<div id="<?php echo ($modal ? "Modal" : "")?>tab0" class="TabbedPanel<?php if ($tabcount>0) { ?> StyledTabbedPanel<?php } ?>">
<div class="clearerleft"> </div>
<div>
<?php 
#  ----------------------------- Draw standard fields ------------------------
?>
<?php hook("beforefields");?>
<?php if ($show_resourceid) { ?><div class="itemNarrow"><h3><?php echo $lang["resourceid"]?></h3><p><?php echo htmlspecialchars($ref)?></p></div><?php } ?>
<?php if ($show_access_field) { ?><div class="itemNarrow"><h3><?php echo $lang["access"]?></h3><p><?php echo @$lang["access" . $resource["access"]]?></p></div><?php } ?>
<?php if ($show_resource_type) { ?><div class="itemNarrow"><h3><?php echo $lang["resourcetype"]?></h3><p><?php echo  htmlspecialchars(get_resource_type_name($resource["resource_type"]))?></p></div><?php } ?>
<?php if ($show_hitcount){ ?><div class="itemNarrow"><h3><?php echo $resource_hit_count_on_downloads?$lang["downloads"]:$lang["hitcount"]?></h3><p><?php echo $resource["hit_count"]+$resource["new_hit_count"]?></p></div><?php } ?>
<?php hook("extrafields");?>
<?php
# contributed by field
if (!hook("replacecontributedbyfield")){
$udata=get_user($resource["created_by"]);
if ($udata!==false)
	{
	?>
<?php if ($show_contributed_by){?>	<div class="itemNarrow"><h3><?php echo $lang["contributedby"]?></h3><p><?php if (checkperm("u")) { ?><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/team/team_user_edit.php?ref=<?php echo $udata["ref"]?>"><?php } ?><?php echo highlightkeywords(htmlspecialchars($udata["fullname"]),$search)?><?php if (checkperm("u")) { ?></a><?php } ?></p></div><?php } ?>
	<?php
	}
} // end hook replacecontributedby

# Show field data
$tabname                        = '';
$tabcount                       = 0;
$extra                          = '';
$show_default_related_resources = TRUE;
foreach($fields_tab_names as $tabname)
    {
    for($i = 0; $i < count($fields); $i++)
        {
        $displaycondition = check_view_display_condition($fields, $i);

        if($fields[$i]['resource_type'] == '0' || $fields[$i]['resource_type'] == $resource['resource_type'])
            {
            if($displaycondition && $tabname == $fields[$i]['tab_name'])
                {
                if(!hook('renderfield', '', array($fields[$i], $resource)))
                    {
                    display_field_data($fields[$i]);
                    }
                }
            }
        }

    // Show related resources which have the same tab name:
    include '../include/related_resources.php';

    $tabcount++;
    if($tabcount != count($fields_tab_names))
        {
        ?>
        <div class="clearerleft"></div>
        <?php
        // Show the fields with a display template now
        echo $extra;
        $extra = '';
        ?>
        <div class="clearerleft"></div>
        </div>
        </div>
        <div class="TabbedPanel StyledTabbedPanel" style="display:none;" id="<?php echo ($modal ? "Modal" : "")?>tab<?php echo $tabcount?>"><div>
        <?php
        }
    }

if(empty($fields_tab_names))
    {
    for($i = 0; $i < count($fields); $i++)
        {
        $displaycondition = check_view_display_condition($fields, $i);

        if($displaycondition)
            {
            if(!hook('renderfield',"", array($fields[$i], $resource)))
                {
                display_field_data($fields[$i]);
                }
            }
        }
    }
    
?><?php hook("extrafields2");?>
<?php if(!$force_display_template_order_by){ ?> <div class="clearerleft"></div> <?php } ?>
<?php if(!isset($related_type_show_with_data)) { echo $extra; } ?>
<?php if($force_display_template_order_by){ ?> <div class="clearerleft"></div> <?php } ?>
</div>
</div>
<?php hook("renderafterresourcedetails"); ?>
<!-- end of tabbed panel-->
</div>

