<?php
# Global everything we need, in case called inside a function (e.g. for push_metadata support)
global $k,$lang,$show_resourceid,$show_access_field,$show_resource_type,$show_hitcount, $resource_hit_count_on_downloads,
       $show_contributed_by,$baseurl_short,$search,$enable_related_resources,$force_display_template_order_by,$modal,
       $sort_tabs;

// Is this a modal?
$modal=(getval("modal","")=="true");

// -----------------------  Tab calculation -----------------
$system_tabs = array_filter(get_tab_name_options(), 'mb_strlen');

$configured_resource_type_tabs = [];
if(isset($related_type_show_with_data) && !empty($related_type_show_with_data))
    {
    $configured_resource_type_tabs = ps_array(
           "SELECT DISTINCT t.ref AS `value`
              FROM resource_type AS rt
        INNER JOIN tab AS t ON t.ref = rt.tab
             WHERE rt.ref IN(" . ps_param_insert(count($related_type_show_with_data)) . ") AND rt.ref <> ?;",
        array_merge(ps_param_fill($related_type_show_with_data, 'i'), ['i', $resource['resource_type']]),
        'schema'
    );
    }

$tabs_with_data = [];
foreach($system_tabs as $tab_ref => $tab_name)
    {
    // Always keep the Resource type tabs if configured so
    if(in_array($tab_ref, $configured_resource_type_tabs))
        {
        $tabs_with_data[$tab_ref] = $tab_name;
        continue;
        }


    for($i = 0; $i < count($fields); ++$i)
        {
        // $fields[$i]['tab'] = ''; #TODO; delete after testing
        $fields[$i]['tab'] = (int) $fields[$i]['tab'] ?: 1; # Place on the default tab (ref #1) if unassigned

        if(
            $tab_ref == $fields[$i]['tab']
            && $fields[$i]['display_field'] == 1
            && $fields[$i]['value'] != ''
            && $fields[$i]['value'] != ','
            && ($access == 0 || ($access == 1 && !$fields[$i]['hide_when_restricted']))
            && check_view_display_condition($fields, $i, $fields_all)
        )
            {
            $tabs_with_data[$tab_ref] = $tab_name;
            }
        }
    }
$fields_tab_names = $tabs_with_data;


/* TODO; delete once done
$fields_tab_names = tab_names($fields);

// Clean the tabs by removing the ones that would just be empty:
$tabs_with_data = array();
foreach ($fields_tab_names as $tabname)
    {
    for ($i = 0; $i < count($fields); $i++)
        {
        if (trim($fields[$i]['tab_name']) == "")
            {
            $fields[$i]["tab_name"] = $lang["default"];
            }

        $displaycondition = check_view_display_condition($fields, $i, $fields_all);

        if($displaycondition && $tabname == $fields[$i]['tab_name'] && $fields[$i]['value'] != '' && $fields[$i]['value'] != ',' && $fields[$i]['display_field'] == 1 && ($access == 0 || ($access == 1 && !$fields[$i]['hide_when_restricted'])))
            {
            $tabs_with_data[] = $tabname;
            }
    	}
    }

$fields_tab_names = array_intersect($fields_tab_names, $tabs_with_data);

if ($sort_tabs)
    {
    sort($fields_tab_names);
    }

// Related resources can be rendered in tabs shown alongside the regular data tabs instead of in their usual position lower down the page 
if(isset($related_type_show_with_data)) {
    // Fetch the tab names for the resource types which have been specifed for rendering in related tabs
    // Exclude the current resource type 
    $show_related_type_tab_list = implode(",",$related_type_show_with_data);
    $resource_type_tab_names = sql_array("SELECT tab_name as value FROM resource_type 
                                           WHERE ref IN(".$show_related_type_tab_list.") and ref<>'" . $resource['resource_type'] . "'", "schema");
    $resource_type_tab_names = array_values(array_unique($resource_type_tab_names));

    // This is the list of tab names which will be rendered for the resource specified
    $fields_tab_names = array_values(array_unique((array_merge($fields_tab_names, $resource_type_tab_names))));
}

// Make sure the fields_tab_names is empty if there are no values:
foreach ($fields_tab_names as $key => $value) {
	if(empty($value)) {
		unset($fields_tab_names[$key]);
	}
}
*/
$modified_view_tabs=hook("modified_view_tabs","view",array($fields_tab_names));if($modified_view_tabs!=='' && is_array($modified_view_tabs)){$fields_tab_names=$modified_view_tabs;}
?>

<div id="Metadata">
    <div class="NonMetadataProperties">
    <?php
    hook("beforefields");
    if($show_resourceid)
        {
        ?>
        <div class="itemNarrow">
            <h3><?php echo htmlspecialchars($lang["resourceid"]); ?></h3>
            <p><?php echo htmlspecialchars($ref)?></p>
        </div>
        <?php
        }

    if($show_access_field)
        {
        $resource["access"] = 50;
        ?>
        <div class="itemNarrow">
            <h3><?php echo htmlspecialchars($lang["access"]); ?></h3>
            <p><?php echo htmlspecialchars($lang["access{$resource['access']}"] ?? ''); ?></p>
        </div>
        <?php
        }

    if($show_resource_type)
        {
        ?>
        <div class="itemNarrow">
            <h3><?php echo htmlspecialchars($lang["resourcetype"]); ?></h3>
            <p><?php echo  htmlspecialchars(get_resource_type_name($resource["resource_type"]))?></p>
        </div>
        <?php
        }

    if($show_hitcount)
        {
        ?>
        <div class="itemNarrow">
            <h3><?php echo $resource_hit_count_on_downloads?$lang["downloads"]:$lang["hitcount"]?></h3>
            <p><?php echo $resource["hit_count"]+$resource["new_hit_count"]?></p>
        </div>
        <?php
        }
    hook("extrafields");

    // Contributed by
    if(!hook("replacecontributedbyfield"))
        {
        if($show_contributed_by)
            {
            $udata = get_user($resource["created_by"]);
            if($udata !== false)
                {
                $udata_fullname = highlightkeywords(htmlspecialchars($udata["fullname"]), $search);
                $udata_a_tag_href = generateURL("{$baseurl_short}pages/team/team_user_edit.php", ['ref' => $udata["ref"]]);
                $udata_a_tag = sprintf(
                    '<a href="%s" onclick="return CentralSpaceLoad(this, true);">%s</a>',
                    $udata_a_tag_href,
                    $udata_fullname
                );
                ?>
                <div class="itemNarrow">
                    <h3><?php echo htmlspecialchars($lang["contributedby"]); ?></h3>
                    <p><?php echo checkperm("u") ? $udata_a_tag : $udata_fullname; ?></p>
                </div>
                <?php
                }
            }
        } // end hook replacecontributedby
    ?>
        <div class="clearerleft"></div>
    </div><!-- End of NonMetadataProperties -->
    <div class="Title"><?php echo htmlspecialchars($lang['metadata']); ?></div>

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
		foreach ($fields_tab_names as $tab_ref => $tabname) { 
            if ($modal) 
                {
                $tabOnClick="SelectMetaTab(".$ref.",".$tabcount.",true);";
                }
            else
                {
                $tabOnClick="SelectMetaTab(".$ref.",".$tabcount.",false);";
                }
            ?>
			<div id="<?php echo ($modal ? "Modal" : "")?>tabswitch<?php echo $tabcount.'-'.$ref; ?>" class="Tab<?php if($tabcount == 0) { ?> TabSelected<?php } ?>">
            <a href="#" onclick="<?php echo $tabOnClick?>"><?php echo i18n_get_translated($tabname)?></a>
			</div>
		
		<?php 
			$tabcount++;
		} ?>

	</div> <!-- end of TabBar -->

<?php
} ?>
<?php $tabModalityClass = ($modal ? " MetaTabIsModal-" : " MetaTabIsNotModal-").$ref;?>
<div id="<?php echo ($modal ? "Modaltab0" : "tab0").'-'.$ref?>" class="TabbedPanel<?php echo $tabModalityClass; if ($tabcount>0) { ?> StyledTabbedPanel<?php } ?>">
<div class="clearerleft"> </div>
<div>
<?php 
#  ----------------------------- Draw standard fields ------------------------

// TODO: you might have to also render here the non-metadata properties if there are no tabs associated with fields

$tabname                        = '';
$tabcount                       = 0;
$extra                          = '';
$show_default_related_resources = TRUE;
foreach($fields_tab_names as $tab_ref => $tabname)
    {
    for($i = 0; $i < count($fields); $i++)
        {
        $displaycondition = check_view_display_condition($fields, $i, $fields_all);

        if($fields[$i]['resource_type'] == '0' || $fields[$i]['resource_type'] == $resource['resource_type'] || $resource['resource_type'] == $metadata_template_resource_type)
            {
            if($displaycondition && $tab_ref == $fields[$i]['tab'])
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
        <div class="TabbedPanel StyledTabbedPanel <?php echo $tabModalityClass?>" style="display:none;" id="<?php echo ($modal ? "Modal" : "")?>tab<?php echo $tabcount.'-'.$ref?>"><div>
        <?php
        }
    }

if(empty($fields_tab_names))
    {
    for($i = 0; $i < count($fields); $i++)
        {
        $displaycondition = check_view_display_condition($fields, $i, $fields_all);

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
