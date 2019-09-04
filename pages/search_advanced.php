<?php
include_once "../include/db.php";
include_once RS_ROOT . "/include/general.php";
include RS_ROOT . "/include/authenticate.php";
include_once RS_ROOT . "/include/search_functions.php";
include_once RS_ROOT . "/include/resource_functions.php";
include_once RS_ROOT . "/include/collections_functions.php";
include_once RS_ROOT . '/include/render_functions.php';

if(!checkperm("s"))
    {
    http_response_code(403);
    exit($lang["error-permissiondenied"]);
    }

$filter_bar_reload = trim(getval('filter_bar_reload', '')) !== 'false' ? true : false;
if(!$filter_bar_reload)
    {
    http_response_code(204);
    exit();
    }

define("FILTER_BAR", true);

function get_search_default_restypes()
	{
	global $search_includes_resources, $collection_search_includes_resource_metadata, $search_includes_user_collections,
           $search_includes_public_collections, $search_includes_themes;
	$defaultrestypes=array();
	if($search_includes_resources)
		{
		$defaultrestypes[] = "Global";
		}
	  else
		{
		$defaultrestypes[] = "Collections";
		if($search_includes_user_collections){$defaultrestypes[] = "mycol";}
		if($search_includes_public_collections){$defaultrestypes[] = "pubcol";}
		if($search_includes_themes){$defaultrestypes[] = "themes";}
		}	
	return $defaultrestypes;
	}

function get_search_open_sections()
    {
    global $search_includes_resources, $collection_search_includes_resource_metadata;

    $advanced_search_section = getvalescaped('advancedsearchsection', '');

    if('' != $advanced_search_section || '' != getval('resetform', ''))
        {
        if (isset($default_advanced_search_mode)) 
            {
            $opensections = $default_advanced_search_mode;
            }
        else
            {
            if($search_includes_resources)
                {
                $opensections = array('Global', 'Media');
                }
            else
                {
                $opensections=array('Collections');
                }
            }
        }
    else
        {
        $opensections = explode(',', $advanced_search_section);
        }

    return $opensections;
    }

$selected_archive_states=array();


$archivechoices=getvalescaped("archive",getvalescaped("saved_archive",get_default_search_states()));
if(!is_array($archivechoices)){$archivechoices=explode(",",$archivechoices);}
foreach($archivechoices as $archivechoice)
    {
    if(is_numeric($archivechoice)) {$selected_archive_states[] = $archivechoice;}  
    }

$archive = implode(",", $selected_archive_states);
$archiveonly=count(array_intersect($selected_archive_states,array(1,2)))>0;

$starsearch=getvalescaped("starsearch","");	
rs_setcookie('starsearch', $starsearch,0,"","",false,false);

$opensections=get_search_open_sections();

# Disable auto-save function, only applicable to edit form. Some fields pick up on this value when rendering then fail to work.
$edit_autosave=false;
$reset_form = trim(getval("resetform", "")) !== "";

if (getval("submitted","")=="yes" && !$reset_form)
	{
	$restypes="";
	reset($_POST);foreach ($_POST as $key=>$value)
		{
		if (substr($key,0,12)=="resourcetype") {if ($restypes!="") {$restypes.=",";} $restypes.=substr($key,12);}
		if ($key=="hiddenfields") 
		    {
		    $hiddenfields=$value;
		    }
		}
	rs_setcookie('restypes', $restypes,0,"","",false,false);
		
	# advanced search - build a search query and redirect
	$fields=array_merge(get_advanced_search_fields(false, $hiddenfields ),get_advanced_search_collection_fields(false, $hiddenfields ));
  
	# Build a search query from the search form
	$search=search_form_to_search_query($fields);
	$search=refine_searchstring($search);

    $extra_params = array();

    hook("moresearchcriteria");

    $search_url = generateURL(
        "{$baseurl}/pages/search.php",
        array(
            'search'            => $search,
            'archive'           => $archive,
            'restypes'          => $restypes,
            'filter_bar_reload' => 'false',
            'source'            => getval("source", ""),
        ),
        $extra_params);
    ?>
    <html>
    <script>
    jQuery(document).ready(function ()
        {
        CentralSpaceLoad("<?php echo $search_url; ?>");
        UpdateActiveFilters({search: "<?php echo $search; ?>"});
        });
    </script>
    </html>
    <?php
    exit();
	}



# Reconstruct a values array based on the search keyword, so we can pre-populate the form from the current search
$search = getval("search", "");
$keywords=split_keywords($search,false,false,false,false,true);
$allwords="";$found_year="";$found_month="";$found_day="";$found_start_date="";$found_end_date="";
$searched_nodes = array();

foreach($advanced_search_properties as $advanced_search_property=>$code)
  {$$advanced_search_property="";}
 
$values=array();
	
if($reset_form)
    {
    $found_year="";$found_month="";$found_day="";$found_start_date="";$found_end_date="";$allwords="";$starsearch="";
    $restypes=get_search_default_restypes();
    $selected_archive_states=array(0);
    rs_setcookie("search","",0,"","",false,false);
    rs_setcookie("saved_archive","",0,"","",false,false);
    rs_setcookie("restypes", implode(",", $restypes), 0, "", "", false, false);

    $extra_params = array();

    hook("reset_filter_bar");

    $search_url = generateURL(
        "{$baseurl}/pages/search.php",
        array(
            'search'   => '',
            'archive'  => implode(",", $selected_archive_states),
            'restypes' => implode(",", $restypes),
        ),
        $extra_params);
        ?>
    <html>
    <script>
    jQuery(document).ready(function ()
        {
        CentralSpaceLoad("<?php echo $search_url; ?>");
        });
    </script>
    </html>
    <?php
    exit();
    }
else
  {
  if(getval("restypes","")=="")
	{$restypes=get_search_default_restypes();}
  else
		{$restypes=explode(",",getvalescaped("restypes",""));}

  for ($n=0;$n<count($keywords);$n++)
	  {
	  $keyword=trim($keywords[$n]);
	  if (strpos($keyword,":")!==false && substr($keyword,0,1)!="!")
		  {
            
          if(substr($keyword,0,1) =="\"" && substr($keyword,-1,1) == "\"")
            {
            $nk=explode(":",substr($keyword,1,-1));
            $name=trim($nk[0]);
            $keyword = "\"" . trim($nk[1]) . "\"";
            }
		  else
            {
            $nk=explode(":",$keyword);
            $name=trim($nk[0]);
            $keyword=trim($nk[1]);
            }
		  if ($name=="basicday") {$found_day=$keyword;}
		  if ($name=="basicmonth") {$found_month=$keyword;}
		  if ($name=="basicyear") {$found_year=$keyword;}
		  if ($name=="startdate") {$found_start_date=$keyword;}
		  if ($name=="enddate") {$found_end_date=$keyword;}
		  if (isset($values[$name])){$values[$name].=" ".$keyword;}
		  else
			 {
			 $values[$name]=$keyword;
			 }
		  }
	  elseif (substr($keyword,0,11)=="!properties")
		  {
		  $properties = explode(";",substr($keyword,11));
		  $propertyfields = array_flip($advanced_search_properties);
		  foreach($properties as $property)
			  {
			  $propertycheck=explode(":",$property);
			  $propertyname=$propertycheck[0];
			  $propertyval=escape_check($propertycheck[1]);
			  if($propertyval!="")
				{
				$fieldname=$propertyfields[$propertyname];
				$$fieldname=$propertyval;
				}
			  }
		  }
        // Nodes search
        else if(strpos($keyword, NODE_TOKEN_PREFIX) !== false)
            {
            $nodes = resolve_nodes_from_string($keyword);

            foreach($nodes as $node)
                {
                $searched_nodes[] = $node;
                }
            }
	  else
		  {
		  if ($allwords=="") {$allwords=$keyword;} else {$allwords.=", " . $keyword;}
		  }
	  }

    $allwords = str_replace(', ', ' ', $allwords);
  }
?>
<script type="text/javascript">

var resTypes=Array();
<?php

$types=get_resource_types();

for ($n=0;$n<count($types);$n++)
	{
	echo "resTypes[" .  $n  . "]=" . $types[$n]["ref"] . ";";
	}
?>
	
jQuery(document).ready(function()
    {
    selectedtypes=['<?php echo implode("','",$opensections) ?>'];
    if(selectedtypes[0]===""){selectedtypes.shift();}
    });
</script>
<div class="BasicsBox">
<form method="post" id="advancedform" action="<?php echo $baseurl ?>/pages/search_advanced.php" >
<?php generateFormToken("advancedform"); ?>
<input type="hidden" name="submitted" id="submitted" value="yes">
<input type="hidden" name="source" value="filter_bar">

<script type="text/javascript">
var updating = false;
function UpdateResultCount()
	{
    updating = false;
    CentralSpacePost(document.getElementById('advancedform'), true, false, false);
    return;
	}
	
jQuery(document).ready(function(){
    // Detect which submit input was last called so we can figure out if we need to treat it differently (e.g when 
    // resetform is clicked and we are using filter bar we want to reload filter bar clearing all fields)
    var submit_caller_element = '';
    jQuery(":submit").click(function()
        {
        submit_caller_element = this.name;
        });

	    jQuery('#advancedform').submit(function(event) {
            if(submit_caller_element == 'resetform')
                {
                event.preventDefault();
                ClearFilterBar(true);
                return false;
                }

            if (jQuery('#AdvancedSearchTypeSpecificSectionCollections').is(":hidden")) 
                {
                    jQuery('.tickboxcoll').prop('checked',false);
                }
	       var inputs = jQuery('#advancedform :input');
	       var hiddenfields = Array();
	       inputs.each(function() {

	           if (jQuery(this).parent().is(":hidden")) hiddenfields.push((this.name).substr(6));
	           
	       });
	      jQuery("#hiddenfields").val(hiddenfields.toString());
	    
    	    
    	    	
	    });
		jQuery('.Question').easyTooltip({
			xOffset: -50,
			yOffset: 70,
			charwidth: 70,
			tooltipId: "advancedTooltip",
			cssclass: "ListviewStyle"
			});
		});

// Resource type fields information. Can be used to link client side actions with fields (e.g clearing active filters 
// in the filter bar need to clear the actual fields as well)
var resource_type_fields_data = [];
</script>
<div id="ActiveFilters" class="Question">
    <label><?php echo $lang["active_filters"]; ?></label>
    <div class="clearerleft"></div>
    <span id="ActiveFiltersList"></span>
    <div class="clearerleft"></div>
</div>
<?php
if($search_includes_resources && !hook("advsearchrestypes"))
    {
    ?>
    <div class="Question">
    <?php
    $wrap = 5;
    ?>
        <table>
            <tr>
                <td valign=middle>
                    <input type=checkbox class="SearchTypeCheckbox" id="SearchGlobal" name="resourcetypeGlobal" value="yes" <?php if (in_array("Global",$restypes)) { ?>checked<?php }?>></td><td valign=middle><?php echo $lang["resources-all-types"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
            <?php
            $hiddentypes=array();
            for ($n=0;$n<count($types);$n++)
                {
                if(in_array($types[$n]['ref'], $hide_resource_types))
                    {
                    continue;
                    }

                $wrap++;

                if($wrap > 4)
                    {
                    $wrap = 5;
                    ?>
                    </tr>
                    <tr>
                    <?php
                    }
                    ?>
                <td valign=middle>
                    <input type=checkbox class="SearchTypeCheckbox SearchTypeItemCheckbox" name="resourcetype<?php echo $types[$n]["ref"]?>" value="yes" <?php if (in_array("Global",$restypes) || in_array($types[$n]["ref"],$restypes)) {?>checked<?php } else $hiddentypes[]=$types[$n]["ref"]; ?>></td><td valign=middle><?php echo htmlspecialchars($types[$n]["name"])?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
                <?php
                }
    
    if($search_includes_user_collections || $search_includes_public_collections ||$search_includes_themes)
                {
                ?>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
            <tr>
                <td valign=middle>
                    <input type=checkbox id="SearchCollectionsCheckbox" class="SearchTypeCheckbox" name="resourcetypeCollections" value="yes" <?php if (in_array("Collections",$restypes) || in_array("mycol",$restypes) || in_array("pubcol",$restypes) || in_array("themes",$restypes)) { ?>checked<?php }?>></td><td valign=middle><?php print $lang["collections"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
                <?php
                }
                ?>
            </tr>
        </table>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

if(!hook('advsearchallfields'))
    {
    ?>
    <!-- Search across all fields -->
    <input type="hidden" id="hiddenfields" name="hiddenfields" value="">
    <input id="allfields" type="hidden" name="allfields" value="<?php echo htmlspecialchars($allwords); ?>" onChange="UpdateResultCount();">
    <?php
    }
    ?>

<?php
if(!hook('advsearchresid') && trim($search) === "")
    {
    ?>
    <div class="Question">
        <label for="resourceids"><?php echo $lang["resourceids"]?></label>
        <input id="resourceids" class="SearchWidth"
               type=text name="resourceids"
               value="<?php echo htmlspecialchars(getval("resourceids","")); ?>"
               onChange="UpdateResultCount();">
        <div class="clearerleft"></div>
    </div>
    <?php
    }

if(!hook('advsearchdate'))
    {
    $date_field_data = get_resource_type_field($date_field);
    if(!$daterange_search && !($date_field_data["simple_search"] == 1 || $date_field_data["advanced_search"] == 1))
        {
        ?>
        <div class="Question">
            <label><?php echo $lang["bydate"]; ?></label>
            <select id="basicyear" name="basicyear" class="SearchWidth" style="width:120px;" onChange="UpdateResultCount();">
                <option value=""><?php echo $lang["anyyear"]?></option>
            <?php
            $y=date("Y");
            for($n = $minyear; $n <= $y; $n++)
                {
                $selected = ($n == $found_year ? "selected" : "");
                ?>
                <option <?php echo $selected; ?>><?php echo $n; ?></option>
                <?php
                }
                ?>
            </select>
            <select id="basicmonth" name="basicmonth" class="SearchWidth" style="width:120px;" onChange="UpdateResultCount();">
                <option value=""><?php echo $lang["anymonth"]?></option>
            <?php
            for($n = 1; $n <= 12; $n++)
                {
                $m=str_pad($n,2,"0",STR_PAD_LEFT);
                ?>
                <option <?php if ($n==$found_month) { ?>selected<?php } ?> value="<?php echo $m; ?>"><?php echo $lang["months"][$n-1]?></option>
                <?php
                }
                ?>
            </select>
            <select id="basicday" name="basicday" class="SearchWidth" style="width:120px;" onChange="UpdateResultCount();">
                <option value=""><?php echo $lang["anyday"]?></option>
            <?php
            for($n = 1; $n <= 31; $n++)
                {
                $m = str_pad($n, 2, "0", STR_PAD_LEFT);
                ?>
                <option <?php if ($n==$found_day) { ?>selected<?php } ?> value="<?php echo $m; ?>"><?php echo $m; ?></option>
                <?php
                }
                ?>
            </select>
            <div class="clearerleft"> </div>
        </div><!-- End of basic date question -->
        <?php
        }
    }

hook('advsearchaddfields');

$fields = get_advanced_search_fields($archiveonly);
// Fake fields are added to the end of the fields list for rendering special filters like Media section, Contributed by and others
$fake_fields = array(
    array(
        "ref" => null,
        "simple_search" => 0,
        "advanced_search" => $advanced_search_archive_select,
        "fct_name" => "render_fb_archive_state",
        "fct_args" => array(
            $selected_archive_states
        ),
    ),
    array(
        "ref" => null,
        "simple_search" => 0,
        "advanced_search" => $advanced_search_contributed_by,
        "fct_name" => "render_fb_contributed_by",
        "fct_args" => array(
            $properties_contributor
        ),
    ),
    array(
        "ref" => null,
        "simple_search" => 0,
        "advanced_search" => $advanced_search_media_section,
        "fct_name" => "render_fb_media_section",
        "fct_args" => array(
            $media_heightmin,
            $media_heightmax,
            $media_widthmin,
            $media_widthmax,
            $media_filesizemin,
            $media_filesizemax,
            $media_fileextension,
            $properties_haspreviewimage
        ),
    ),
);

$modified_fields = hook("fb_modify_fields", "", array($fields));
if($modified_fields !== false && is_array($modified_fields) && !empty($modified_fields))
    {
    $fields = $modified_fields;
    }

$advanced_section_rendered = false;
$n = 0; # this is used by render_search_field()
foreach(array_merge($fields, $fake_fields) as $field)
    {
    $simple_search_flag = $field["simple_search"] == 1 ? true : false;
    $advanced_search_flag = $field["advanced_search"] == 1 ? true : false;

    if(!$advanced_section_rendered && !$simple_search_flag && $advanced_search_flag)
        {
        ?>
        <h1 class="CollapsibleSectionHead collapsed"><?php echo $lang["advanced"]; ?></h1>
        <div id="FilterBarAdvancedSection" class="CollapsibleSection">
        <?php
        $advanced_section_rendered = true;
        }

    if(is_null($field["ref"]) && trim($field["fct_name"]) !== "" && is_array($field["fct_args"]))
        {
        call_user_func_array($field["fct_name"], $field["fct_args"]);
        continue;
        }

    $n++;
    $value = "";
    if(!$reset_form && array_key_exists($field["name"], $values))
        {
        $value = $values[$field["name"]];
        }

    // Normal rendering of ResourceSpace fields
    render_search_field($field, $value, true, 'SearchWidth', false, array(), $searched_nodes);
    ?>
    <script>
    resource_type_fields_data[<?php echo $field["ref"]; ?>] = {
        ref: "<?php echo $field["ref"]; ?>",
        name: "<?php echo $field["name"]; ?>",
        type: "<?php echo $field["type"]; ?>",
        resource_type: "<?php echo $field["resource_type"]; ?>",
    };
    </script>
    <?php
    }
if($advanced_section_rendered)
    {
    echo "</div> <!-- End of AdvancedSection -->";
    }

hook("fb_after_advancedsection");

if($search_includes_user_collections || $search_includes_public_collections || $search_includes_themes) { ?>
<h1 class="AdvancedSectionHead CollapsibleSectionHead" id="AdvancedSearchTypeSpecificSectionCollectionsHead" <?php if (!in_array("Collections",$opensections) && !$collection_search_includes_resource_metadata) {?> style="display: none;" <?php } ?>><?php echo $lang["collections"]; ?></h1>
<div class="AdvancedSection" id="AdvancedSearchTypeSpecificSectionCollections" <?php if (!in_array("Collections",$opensections) && !$collection_search_includes_resource_metadata) {?> style="display: none;" <?php } ?>>

<script type="text/javascript">	
function resetTickAllColl(){
	var checkcount=0;
	// set tickall to false, then check if it should be set to true.
	jQuery('.rttickallcoll').prop('checked',false);
	var tickboxes=jQuery('#advancedform .tickboxcoll');
		jQuery(tickboxes).each(function (elem) {
            if( tickboxes[elem].checked){checkcount=checkcount+1;}
        });
	if (checkcount==tickboxes.length){jQuery('.rttickallcoll').prop('checked',true);}	
}
</script>
<div class="Question">
<label><?php echo $lang["scope"]?></label><?php

$types=get_resource_types();
$wrap=0;
?>
<table><tr>
<td align="middle"><input type='checkbox' class="rttickallcoll" id='rttickallcoll' name='rttickallcoll' <?php if (in_array("Collections",$restypes)) {?> checked <?php } ?> onclick='jQuery("#advancedform .tickboxcoll").each (function(index,Element) {jQuery(Element).prop("checked",(jQuery(".rttickallcoll").prop("checked")));}); UpdateResultCount(); ' /><?php echo $lang['allcollectionssearchbar']?></td>

<?php
$clear_function="";
if ($search_includes_user_collections) 
    { ?>
    <td align="middle"><?php if ($searchbar_selectall){ ?>&nbsp;&nbsp;<?php } ?><input class="tickboxcoll" id="TickBoxMyCol" type="checkbox" name="resourcetypemycol" value="yes" <?php if ((count($restypes)==1 && $restypes[0]=="Collections") || in_array("mycol",$restypes)) {?>checked="checked"<?php } ?>onClick="resetTickAllColl();" onChange="UpdateResultCount();"/><?php echo $lang["mycollections"]?></td><?php	
    $clear_function.="document.getElementById('TickBoxMyCol').checked=true;";
    $clear_function.="resetTickAllColl();";
    }
if ($search_includes_public_collections) 
    { ?>
    <td align="middle"><?php if ($searchbar_selectall){ ?>&nbsp;&nbsp;<?php } ?><input class="tickboxcoll" id="TickBoxPubCol" type="checkbox" name="resourcetypepubcol" value="yes" <?php if ((count($restypes)==1 && $restypes[0]=="Collections") || in_array("pubcol",$restypes)) {?>checked="checked"<?php } ?>onClick="resetTickAllColl();" onChange="UpdateResultCount();"/><?php echo $lang["findpubliccollection"]?></td><?php	
    $clear_function.="document.getElementById('TickBoxPubCol').checked=true;";
    $clear_function.="resetTickAllColl();";
    }
if ($search_includes_themes) 
    { ?>
    <td align="middle"><?php if ($searchbar_selectall){ ?>&nbsp;&nbsp;<?php } ?><input class="tickboxcoll" id="TickBoxThemes" type="checkbox" name="resourcetypethemes" value="yes" <?php if ((count($restypes)==1 && $restypes[0]=="Collections") || in_array("themes",$restypes)) {?>checked="checked"<?php } ?>onClick="resetTickAllColl();" onChange="UpdateResultCount();"/><?php echo $lang["findcollectionthemes"]?></td><?php	
    $clear_function.="document.getElementById('TickBoxThemes').checked=true;";
    $clear_function.="resetTickAllColl();";
    }
?>
</tr></table></div>
<script type="text/javascript">resetTickAllColl();</script>
<?php
if (!$collection_search_includes_resource_metadata)
   {
 $fields=get_advanced_search_collection_fields();
 for ($n=0;$n<count($fields);$n++)
	 {
	 # Work out a default value
	 if (array_key_exists($fields[$n]["name"],$values)) {$value=$values[$fields[$n]["name"]];} else {$value="";}
	 if (getval("resetform","")!="") {$value="";}
	 # Render this field
	 render_search_field($fields[$n],$value,true,"SearchWidth",false,array(),$searched_nodes);
	 }
   }
?>
</div>
<?php
}
?>
        <div class="QuestionSubmit">
            <label for="buttons"></label>
            <input class="resetform FullWidth" name="resetform" type="submit" form="advancedform" value="<?php echo $lang["clearbutton"]; ?>">
        </div>
    </form>
</div> <!-- BasicsBox -->
<script>
function ClearFilterBar(load)
    {
    load = typeof load !== "undefined" && load === true ? true : false;
    var url = "<?php echo generateURL("{$baseurl}/pages/search_advanced.php", array('submitted' => true, 'resetform' => true)); ?>";

    if(load)
        {
        jQuery("#FilterBarContainer").load(url);
        }
    else
        {
        TogglePane(
            'FilterBarContainer',
            {
                load_url: '<?php echo $baseurl; ?>/pages/search_advanced.php',
                <?php echo generateAjaxToken("ToggleFilterBar"); ?>
            },
            true);
        jQuery("#FilterBarContainer").empty();
        }

    document.getElementById('ssearchbox').value='';
    <?php hook("clear_filter_bar_js"); ?>
    }

function HideInapplicableFilterBarFields()
    {
    jQuery(".SearchTypeCheckbox").each(function(index, element)
        {
        const id = (element.name).substr(12);
        var show_rtype_fields = false;

        if(jQuery(element).is(":checked"))
            {
            show_rtype_fields = true;
            }

        jQuery(".Question").not("#ActiveFilters").each(function()
            {
            if(jQuery(this).data("resource_type") !== parseInt(id))
                {
                return true;
                }

            if(show_rtype_fields)
                {
                jQuery(this).show();
                return true;
                }

            jQuery(this).hide();
            });
        });
    }

jQuery(document).ready(function()
    {
    UpdateActiveFilters({search: "<?php echo $search; ?>"});
    jQuery("#FilterBarContainer .Question table").PutShadowOnScrollableElement();
    registerCollapsibleSections(false);

    jQuery("#CentralSpace").on("CentralSpaceLoaded", function(event, data)
        {
        var page_name = typeof data.pagename !== "undefined" ? data.pagename : "";

        if(pagename != "search")
            {
            ClearFilterBar(false);
            }

        return true;
        });

    HideInapplicableFilterBarFields();
    jQuery('.SearchTypeCheckbox').change(function() 
        {
        var id = (this.name).substr(12);

        if(jQuery(this).is(":checked"))
            {
            if(id == "Global")
                {
                selectedtypes = ["Global"];

                // Global has been checked, check all other checkboxes
                jQuery('.SearchTypeItemCheckbox').prop('checked', true);

                //Uncheck Collections
                jQuery('#SearchCollectionsCheckbox').prop('checked', false);
                }
            else if(id == "Collections")
                {
                //Uncheck All checkboxes
                jQuery('.SearchTypeCheckbox').prop('checked', false);        

                //Check Collections
                selectedtypes = ["Collections"];
                jQuery('#SearchCollectionsCheckbox').prop('checked', true);
                jQuery('.tickboxcoll').prop('checked', true);

                // Show collection search sections  
                jQuery('#AdvancedSearchTypeSpecificSectionCollectionsHead').show();
                if(getCookie('advancedsearchsection') != "collapsed")
                    {
                    jQuery("#AdvancedSearchTypeSpecificSectionCollections").show();
                    }
                }
            else
                {
                selectedtypes = jQuery.grep(
                    selectedtypes,
                    function(value)
                        {
                        return value != "Collections";
                        });
                selectedtypes.push(id); 

                jQuery('#SearchGlobal').prop('checked', false);
                jQuery('#SearchCollectionsCheckbox').prop('checked', false);

                // Show resource type specific search sections  if only one checked
                if(selectedtypes.length == 1)
                    {
                    if(getCookie('AdvancedSearchTypeSpecificSection' + id) != "collapsed")
                        {
                        jQuery('#AdvancedSearchTypeSpecificSection' + id).show();
                        }
                    jQuery('#AdvancedSearchTypeSpecificSection' + id + 'Head').show();              
                    }
                }
            }
        else
            {
            if(id == "Global")
                {     
                selectedtypes = [];   
                jQuery('.SearchTypeItemCheckbox').prop('checked', false);
                }
            else if(id == "Collections")
                {
                selectedtypes = [];
                jQuery('#AdvancedSearchTypeSpecificSectionCollectionsHead').hide();
                }
            else
                {
                jQuery('#SearchGlobal').prop('checked',false);

                // If global was previously checked, make sure all other types are now checked
                selectedtypes = jQuery.grep(
                    selectedtypes,
                    function(value)
                        {
                        return value != id;
                        });

                if(selectedtypes.length == 1)
                    {
                    if(getCookie('AdvancedSearchTypeSpecificSection' + selectedtypes[0]) != "collapsed")
                        {
                        jQuery('#AdvancedSearchTypeSpecificSection' + selectedtypes[0]).show();
                        }
                    jQuery('#AdvancedSearchTypeSpecificSection' + selectedtypes[0] + 'Head').show();                
                    }
                }
            }

        SetCookie("advancedsearchsection", selectedtypes);
        HideInapplicableFilterBarFields();
        UpdateResultCount();
        });
    });
</script>