<?php
# Global everything we need, in case called inside a function (e.g. for push_metadata support)
global $k,$lang,$show_resourceid,$show_access_field,$show_resource_type,$show_hitcount, $resource_hit_count_on_downloads,
       $show_contributed_by,$baseurl_short,$search,$enable_related_resources,$force_display_template_order_by,$modal,
       $sort_tabs;

// Is this a modal?
$modal=(getval("modal","")=="true");

// -----------------------  Tab calculation -----------------
$disable_tabs = true;
$system_tabs = get_tab_name_options();
$tabs_fields_assoc = [];

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

// Clean the tabs by removing the ones that would end up being empty
foreach(array_keys($system_tabs) as $tab_ref)
    {
    // Always keep the Resource type tabs if configured so
    if(in_array($tab_ref, $configured_resource_type_tabs))
        {
        // Related resources can be rendered in tabs shown alongside the regular data tabs instead of in their usual position lower down the page 
        $tabs_fields_assoc[$tab_ref] = [];
        continue;
        }

    for($i = 0; $i < count($fields); ++$i)
        {
        $fields[$i]['tab'] = (int) $fields[$i]['tab'];
        $field_can_show_on_tab = (
            $fields[$i]['display_field'] == 1
            && $fields[$i]['value'] != ''
            && $fields[$i]['value'] != ','
            && ($access == 0 || ($access == 1 && !$fields[$i]['hide_when_restricted']))
            && check_view_display_condition($fields, $i, $fields_all)
        );

        // Check if the field can show on this tab
        if($tab_ref > 0 && $tab_ref == $fields[$i]['tab'] && $field_can_show_on_tab)
            {
            $tabs_fields_assoc[$tab_ref][$i] = $fields[$i]['ref'];
            $disable_tabs = false;
            }
        // Unassigned or invalid tab links end up on the "not set" list (IF they will be rendered)
        else if(
            !isset($tabs_fields_assoc[0][$i])
            && (0 === $fields[$i]['tab'] || !isset($system_tabs[$fields[$i]['tab']]))
            && $field_can_show_on_tab
        )
            {
            $tabs_fields_assoc[0][$i] = $fields[$i]['ref'];
            }
        }
    }

// System is configured with tabs once at least a field has been associated with a valid tab and the field will be rendered
if($disable_tabs)
    {
    $tabs_fields_assoc = [];
    }
else if(isset($tabs_fields_assoc[0]) && count($tabs_fields_assoc[0]) > 0)
    {
    foreach(array_keys($tabs_fields_assoc[0]) as $i)
        {
        $fields[$i]['tab'] = 1;
        }

    // Any fields marked as "not set" get placed in the Default (ref #1) tab
    $tabs_fields_assoc[1] = $tabs_fields_assoc[0];
    unset($tabs_fields_assoc[0]);
    }
$fields_tab_names = array_intersect_key($system_tabs, $tabs_fields_assoc);
$modified_view_tabs=hook("modified_view_tabs","view",array($fields_tab_names));if($modified_view_tabs!=='' && is_array($modified_view_tabs)){$fields_tab_names=$modified_view_tabs;}
// -----------------------  END: Tab calculation -----------------
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

<?php
global $extra;
$extra="";

#  -----------------------------  Draw tabs ---------------------------
$tabname="";
$tabcount=0;
$tmp = hook("tweakfielddisp", "", array($ref, $fields)); if($tmp) $fields = $tmp;
if((isset($fields_tab_names) && !empty($fields_tab_names)) && count($fields) > 0)
    {
    ?>
    <div class="Title"><?php echo htmlspecialchars($lang['metadata']); ?></div>
	<div class="TabBar">
	<?php
		foreach ($fields_tab_names as $tab_name) {
            $class_TabSelected = $tabcount == 0 ? ' TabSelected' : '';
            if ($modal) 
                {
                $tabOnClick="SelectMetaTab(".$ref.",".$tabcount.",true);";
                }
            else
                {
                $tabOnClick="SelectMetaTab(".$ref.",".$tabcount.",false);";
                }
            ?>
			<div id="<?php echo ($modal ? "Modal" : "")?>tabswitch<?php echo $tabcount.'-'.$ref; ?>" class="Tab<?php echo $class_TabSelected; ?>">
                <a href="#" onclick="<?php echo $tabOnClick?>"><?php echo htmlspecialchars($tab_name); ?></a>
			</div>
            <?php 
			$tabcount++;
		}
        ?>
	</div> <!-- end of TabBar -->
    <?php
    }

$tabModalityClass = ($modal ? " MetaTabIsModal-" : " MetaTabIsNotModal-").$ref;
?>
<div id="<?php echo ($modal ? "Modaltab0" : "tab0").'-'.$ref?>" class="TabbedPanel<?php echo $tabModalityClass; if ($tabcount>0) { ?> StyledTabbedPanel<?php } ?>">
<div class="clearerleft"> </div>
<div>
<?php 
#  ----------------------------- Draw standard fields ------------------------
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
