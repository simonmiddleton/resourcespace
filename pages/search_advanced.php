<?php
include_once "../include/db.php";

include "../include/authenticate.php"; if (!checkperm("s")) {exit ("Permission denied.");}



$selected_archive_states=array();

$archivechoices=getvalescaped("archive",getvalescaped("saved_archive",get_default_search_states()));
if(!is_array($archivechoices)){$archivechoices=explode(",",$archivechoices);}
foreach($archivechoices as $archivechoice)
    {
    if(is_numeric($archivechoice)) {$selected_archive_states[] = $archivechoice;}  
    }

$archive=implode(",",$selected_archive_states);
$archiveonly=count(array_intersect($selected_archive_states,array(1,2)))>0;

$starsearch=getvalescaped("starsearch","");	
rs_setcookie('starsearch', $starsearch,0,"","",false,false);

# Selectedtypes is a list of (resource type) checkboxes which are checked
# Selectedtypes can also contain Global and Media which are virtual checkboxes which are always considered to be checked
$selectedtypes=get_selectedtypes();

$access = getval("access", null, true);
rs_setcookie("access", $access, 0, "{$baseurl_short}pages/", "", false, false);

# Disable auto-save function, only applicable to edit form. Some fields pick up on this value when rendering then fail to work.
$edit_autosave=false;

if (getval("submitted","")=="yes" && getval("resetform","")=="")
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
	hook("moresearchcriteria");

	if (getval("countonly","")!="")
		{
		# Only show the results (this will appear in an iframe)
        if (substr($restypes,0,11)!="Collections" && !$collection_search_includes_resource_metadata)
            {
            $result=do_search($search,$restypes,"relevance",$archive,1,"",false,$starsearch, false, false, "", false, true, false, false, false, $access);
            }
        else 
            {
            $order_by=$default_collection_sort;
            $sort="DESC";
            $result=do_collections_search($search,$restypes,$archive,$order_by,$sort);
            }
        if (is_array($result))
            {
            $count=count($result);
            }
        else
            {
            $count=0;				
            }
			
		?>
		<html>
		<script type="text/javascript">
            function populate_view_buttons(content)
                {
                var inputs = parent.document.getElementsByClassName('dosearch');

                for(var i = 0; i < inputs.length; i++)
                    {
                    if(typeof inputs[i] !== 'undefined')
                        {
                        inputs[i].value = content;
                        }
                    }
                }
		
		<?php if ($count==0) { ?>
			populate_view_buttons("<?php echo $lang["nomatchingresults"] ?>");
		<?php } else { ?>
			populate_view_buttons("<?php echo $lang["view"] . " " . number_format($count) . " " . $lang["matchingresults"] ?>");
		<?php } ?>
		</script>
		</html>
		<?php
		exit();
		}
	else
		{
		# Log this			
		daily_stat("Advanced search",$userref);

        redirect(
            generateURL(
                "{$baseurl_short}pages/search.php",
                array(
                    "search" => $search,
                    "archive" => $archive,
                    "restypes" => $restypes,
                    "access" => $access,
                )
            )
        );
		}
	}



# Reconstruct a values array based on the search keyword, so we can pre-populate the form from the current search
$search= isset($_COOKIE["search"]) ? $_COOKIE["search"] : "";
$keywords=split_keywords($search,false,false,false,false,true);
$allwords="";$found_year="";$found_month="";$found_day="";$found_start_date="";$found_end_date="";
$searched_nodes = array();

foreach($advanced_search_properties as $advanced_search_property=>$code)
  {$$advanced_search_property="";}
 
$values=array();
	
if (getval("resetform","")!="")
  { 
  $found_year="";$found_month="";$found_day="";$found_start_date="";$found_end_date="";$allwords="";$starsearch="";
  $restypes=get_search_default_restypes();
  $selected_archive_states=array(0);
  rs_setcookie("search","",0,"","",false,false);
  rs_setcookie("saved_archive","",0,"","",false,false);

  $access = null;
  rs_setcookie("access", "", 0, "{$baseurl_short}pages/", "", false, false);
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
			  $propertyname=isset($propertycheck[0])?$propertycheck[0]:"";
			  $propertyval=isset($propertycheck[1])?escape_check($propertycheck[1]):"";
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

include "../include/header.php";
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
    selectedtypes=['<?php echo implode("','",$selectedtypes) ?>'];
    if(selectedtypes[0]===""){selectedtypes.shift();}

    jQuery('.SearchTypeCheckbox').change(function() 
        {
        id=(this.name).substr(12);

        // Process checkbox change from unchecked to checked
        if (jQuery(this).is(":checked")) {
            if (id=="Global") {
				selectedtypes=["Global"];
				//Hide specific resource type areas
				jQuery('.ResTypeSectionHead').hide();
				jQuery('.ResTypeSection').hide();
				
				// Global has been checked, check all other checkboxes
				jQuery('.SearchTypeItemCheckbox').prop('checked',true);
				//Uncheck Collections
				jQuery('#SearchCollectionsCheckbox').prop('checked',false);	

				jQuery('#AdvancedSearchGlobalSectionHead').show();
				if (getCookie('AdvancedSearchGlobalSection')!="collapsed"){jQuery("#AdvancedSearchGlobalSection").show();}				
				jQuery('#AdvancedSearchMediaSectionHead').show();
				if (getCookie('AdvancedSearchMediaSection')!="collapsed"){jQuery("#AdvancedSearchMediaSection").show();}
			}
			else if (id=="Collections") {
				//Uncheck All checkboxes
                jQuery('.SearchTypeCheckbox').prop('checked',false);		

                //Check Collections
				selectedtypes=["Collections"];
				jQuery('#SearchCollectionsCheckbox').prop('checked',true);
				jQuery('.tickboxcoll').prop('checked',true);
				

				// Show collection search sections	
				jQuery('#AdvancedSearchCollectionsSectionHead').show();
				if (getCookie('AdvancedSearchCollectionsSection')!="collapsed"){jQuery("#AdvancedSearchCollectionsSection").show();}
            }
            else {	
				selectedtypes = jQuery.grep(selectedtypes, function(value) {return value != "Collections";});				
				selectedtypes.push(id);	

				//Hide specific resource type areas
				jQuery('.ResTypeSectionHead').hide();
				jQuery('.ResTypeSection').hide();
				
                jQuery('#SearchGlobal').prop('checked',false);
				jQuery('#SearchCollectionsCheckbox').prop('checked',false);		
				// Show global and media search sections	
                jQuery("#AdvancedSearchGlobalSectionHead").show();
                if (getCookie('AdvancedSearchGlobalSection')!="collapsed"){jQuery("#AdvancedSearchGlobalSection").show();}
				jQuery('#AdvancedSearchMediaSectionHead').show();
				if (getCookie('AdvancedSearchMediaSection')!="collapsed"){jQuery("#AdvancedSearchMediaSection").show();}						
				
				// Show resource type specific search sections	if only one checked
				if(selectedtypes.length==1){
					if (getCookie('AdvancedSearch'+id+'Section')!="collapsed"){jQuery('#AdvancedSearch'+id+'Section').show();}
					jQuery('#AdvancedSearch'+id+'SectionHead').show();				
				}
			}
        }
        else { // Process checkbox change from checked to unchecked
			if (id=="Global") {		
				selectedtypes=[];	
	     		jQuery('.SearchTypeItemCheckbox').prop('checked',false);
			}
			else if (id=="Collections") {
				selectedtypes=[];

				// Hide collection search sections	
				jQuery('#AdvancedSearchCollectionsSectionHead').hide();
            }
			else {								
                jQuery('#SearchGlobal').prop('checked',false);
				
				//Hide specific resource type areas
				jQuery('.ResTypeSectionHead').hide();
				jQuery('.ResTypeSection').hide();
				
				// If global was previously checked, make sure all other types are now checked
				selectedtypes = jQuery.grep(selectedtypes, function(value) {return value != id;});
				if(selectedtypes.length==1){
					if (getCookie('AdvancedSearch'+selectedtypes[0]+'Section')!="collapsed") jQuery('#AdvancedSearch'+selectedtypes[0]+'Section').show();
					jQuery('#AdvancedSearch'+selectedtypes[0]+'SectionHead').show();				
				}
			}
			//Always Show Global and Media
			jQuery("#AdvancedSearchGlobalSectionHead").show();
            if (getCookie('AdvancedSearchGlobalSection')!="collapsed"){jQuery("#AdvancedSearchGlobalSection").show();}
			jQuery('#AdvancedSearchMediaSectionHead').show();
			if (getCookie('AdvancedSearchMediaSection')!="collapsed"){jQuery("#AdvancedSearchMediaSection").show();}
		}

		// End of checkbox change processing; update cookie with checkbox values 
        SetCookie("advanced_search_section", selectedtypes);
        UpdateResultCount();

        }); // End of SearchTypeCheckbox change function call

    jQuery('.CollapsibleSectionHead').click(function() 
            {
            cur=jQuery(this).next();
            cur_id=cur.attr("id");
            if (cur.is(':visible'))
                {
                SetCookie(cur_id, "collapsed");
                jQuery(this).removeClass('expanded');
                jQuery(this).addClass('collapsed');
                }
            else
                {
                SetCookie(cur_id, "expanded")
                jQuery(this).addClass('expanded');
                jQuery(this).removeClass('collapsed');
                }
    
            cur.slideToggle();
           
            
            return false;
            }).each(function() 
                {
                    cur_id=jQuery(this).next().attr("id"); 
                    if (getCookie(cur_id)=="collapsed")
                        {
                        jQuery(this).next().hide();
                        jQuery(this).addClass('collapsed');
                        }
                    else jQuery(this).addClass('expanded');
    
                });
    
    });
</script>

<iframe src="blank.html" name="resultcount" id="resultcount" style="visibility:hidden;float:right;" width=1 height=1></iframe>
<div class="BasicsBox">
<h1><?php echo ($archiveonly)?$lang["archiveonlysearch"]:$lang["advancedsearch"];?> </h1>
<p class="tight"><?php echo text("introtext");render_help_link("user/advanced-search");?></p>
<form method="post" id="advancedform" action="<?php echo $baseurl ?>/pages/search_advanced.php" >
<?php generateFormToken("advancedform"); ?>
<input type="hidden" name="submitted" id="submitted" value="yes">
<input type="hidden" name="countonly" id="countonly" value="">

<script type="text/javascript">
var categoryTreeChecksArray = [];
var updating=false;
function UpdateResultCount()
	{
	updating=false;
	// set the target of the form to be the result count iframe and submit
	document.getElementById("advancedform").target="resultcount";
	document.getElementById("countonly").value="yes";
	
	
	jQuery("#advancedform").submit();
	document.getElementById("advancedform").target="";
	document.getElementById("countonly").value="";
	}
	
jQuery(document).ready(function(){
	    jQuery('#advancedform').submit(function() {
            if (jQuery('#AdvancedSearchCollectionsSection').is(":hidden") && (document.getElementById("countonly").value!="yes")) 
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
		});

</script>

<?php
if($advanced_search_buttons_top)
 {
 render_advanced_search_buttons();
 }

if($search_includes_resources && !hook("advsearchrestypes"))
 {?>
 <div class="Question">
 <label><?php echo $lang["search-mode"]?></label><?php
 
 $wrap=0;
 ?><table><tr>
 <td valign=middle><input type=checkbox class="SearchTypeCheckbox" id="SearchGlobal" name="resourcetypeGlobal" value="yes" <?php if (in_array("Global",$restypes)) { ?>checked<?php }?>></td><td valign=middle><?php echo $lang["resources-all-types"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><?php
 $hiddentypes=Array();
 for ($n=0;$n<count($types);$n++)
	 {
		 if(in_array($types[$n]['ref'], $hide_resource_types)) { continue; }
	 $wrap++;if ($wrap>4) {$wrap=1;?></tr><tr><?php }
	 ?><td valign=middle><input type=checkbox class="SearchTypeCheckbox SearchTypeItemCheckbox" name="resourcetype<?php echo $types[$n]["ref"]?>" value="yes" <?php if (in_array("Global",$restypes) || in_array($types[$n]["ref"],$restypes)) {?>checked<?php } else $hiddentypes[]=$types[$n]["ref"]; ?>></td><td valign=middle><?php echo htmlspecialchars($types[$n]["name"])?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><?php	
	 }
 ?>
 <?php if ($search_includes_user_collections || $search_includes_public_collections ||$search_includes_themes)
	 {
 ?></tr><tr><td>&nbsp;</td>
 </tr>
 <tr>
 <td valign=middle><input type=checkbox id="SearchCollectionsCheckbox" class="SearchTypeCheckbox" name="resourcetypeCollections" value="yes" <?php if (in_array("Collections",$restypes) || in_array("mycol",$restypes) || in_array("pubcol",$restypes) || in_array("themes",$restypes)) { ?>checked<?php }?>></td><td valign=middle><?php print $lang["collections"]; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
 <?php
	 }
 ?>
 </tr></table>
 <div class="clearerleft"> </div>
 </div>
 <?php
 }


if (!hook('advsearchallfields')) { ?>
<!-- Search across all fields -->
<input type="hidden" id="hiddenfields" name="hiddenfields" value="">
    
<div class="Question">
<label for="allfields"><?php echo $lang["allfields"]?></label><input class="SearchWidth" type=text name="allfields" id="allfields" value="<?php echo htmlspecialchars($allwords)?>" onChange="UpdateResultCount();">
<div class="clearerleft"> </div>
</div>
<?php } ?>
<h1 class="AdvancedSectionHead CollapsibleSectionHead" id="AdvancedSearchGlobalSectionHead" 
<?php if (in_array("Collections",$selectedtypes) && !$collection_search_includes_resource_metadata) 
		{?> 
		style="display: none;" 
<?php 	} ?>>
<?php echo $lang["resourcetype-global_fields"]; ?>
</h1>
<div class="AdvancedSection" id="AdvancedSearchGlobalSection" 
<?php if (in_array("Collections",$selectedtypes)) 
		{?> 
		style="display: none;" 
<?php 	} ?>>

<?php if (!hook('advsearchresid')) { ?>
<!-- Search for resource ID(s) -->
<div class="Question">
<label for="resourceids"><?php echo $lang["resourceids"]?></label><input class="SearchWidth" type=text name="resourceids" id="resourceids" value="<?php echo htmlspecialchars(getval("resourceids","")) ?>" onChange="UpdateResultCount();">
<div class="clearerleft"> </div>
</div>
<?php }
if (!hook('advsearchdate')) {
if (!$daterange_search)
	{
	?>
	<div class="Question"><label><?php echo $lang["bydate"]?></label>
	<select name="basicyear" class="SearchWidth" style="width:120px;" onChange="UpdateResultCount();">
	  <option value=""><?php echo $lang["anyyear"]?></option>
	  <?php
	  $y=date("Y");
	  $y += $maxyear_extends_current;
      for ($n=$y;$n>=$minyear;$n--)
        {
		?><option <?php if ($n==$found_year) { ?>selected<?php } ?>><?php echo $n?></option><?php
		}
	  ?>
	</select>
	<select name="basicmonth" class="SearchWidth" style="width:120px;" onChange="UpdateResultCount();">
	  <option value=""><?php echo $lang["anymonth"]?></option>
	  <?php
	  for ($n=1;$n<=12;$n++)
		{
		$m=str_pad($n,2,"0",STR_PAD_LEFT);
		?><option <?php if ($n==$found_month) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$n-1]?></option><?php
		}
	  ?>
	</select>
	<select name="basicday" class="SearchWidth" style="width:120px;" onChange="UpdateResultCount();">
	  <option value=""><?php echo $lang["anyday"]?></option>
	  <?php
	  for ($n=1;$n<=31;$n++)
		{
		$m=str_pad($n,2,"0",STR_PAD_LEFT);
		?><option <?php if ($n==$found_day) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
		}
	  ?>
	</select>
	<div class="clearerleft"> </div>
	</div>
<?php }} ?>


<?php hook('advsearchaddfields'); ?>

<?php
# Fetch fields
$fields=get_advanced_search_fields($archiveonly);
$showndivide=-1;

# Preload resource types
$rtypes=get_resource_types();

for ($n=0;$n<count($fields);$n++)
	{
	# Show a dividing header for resource type specific fields?
	if (($fields[$n]["resource_type"]!=0) && ($showndivide!=$fields[$n]["resource_type"]))
		{
		$showndivide=$fields[$n]["resource_type"];
		$label="??";
		# Find resource type name
		for ($m=0;$m<count($rtypes);$m++)
			{
			# Note: get_resource_types() has already translated the resource type name for the current user.
			if ($rtypes[$m]["ref"]==$fields[$n]["resource_type"]) {$label=$rtypes[$m]["name"];}
			}
		?>
		</div>
            <h1 class="AdvancedSectionHead CollapsibleSectionHead ResTypeSectionHead"
                id="AdvancedSearch<?php echo $fields[$n]["resource_type"]; ?>SectionHead"
                <?php
                if(!in_array($fields[$n]["resource_type"], $restypes) || in_array("Global", $restypes) || count($restypes) > 1)
                    {
                    ?> style="display: none;"
                    <?php
                    }
                    ?>
            ><?php echo $lang["typespecific"] . ": " . $label ?></h1>
        <div class="AdvancedSection ResTypeSection"
             id="AdvancedSearch<?php echo $fields[$n]["resource_type"]; ?>Section"
             <?php
             if(!in_array($fields[$n]["resource_type"], $selectedtypes))
                {
                ?> style="display: none;"
                <?php
                }
                ?>
        >
		<?php
		}

	# Work out a default value
	if (array_key_exists($fields[$n]["name"],$values)) {$value=$values[$fields[$n]["name"]];} else {$value="";}
	# Clearbutton means resetform
	$resetform=getval("resetform","");
	if ($resetform != "") 
		{
		$value="";
		}
	# Render this field
    render_search_field($fields[$n], $value, true, 'SearchWidth', false, array(), $searched_nodes, $resetform);
	}
?>
</div>

<?php
global $advanced_search_archive_select;
if($advanced_search_archive_select)
	{
    // Create an array for the archive states
	$available_archive_states = array();
	$all_archive_states=array_merge(range(-2,3),$additional_archive_states);
	foreach($all_archive_states as $archive_state_ref)
		{
		if(!checkperm("z" . $archive_state_ref))
			{
			$available_archive_states[$archive_state_ref] = (isset($lang["status" . $archive_state_ref]))?$lang["status" . $archive_state_ref]:$archive_state_ref;
			}
		}
	?>
    
    <div class="Question" id="question_archive" >
		<label><?php echo $lang["status"]?></label>
		<table cellpadding=2 cellspacing=0>
            
            <?php
            foreach ($available_archive_states as $archive_state=>$state_name)
                {
                ?>
                  <tr>
                    <td width="1">
                   <input type="checkbox"
                          name="archive[]"
                          value="<?php echo $archive_state; ?>"
                          onChange="UpdateResultCount();"<?php 
                       if (in_array($archive_state,$selected_archive_states))
                           {
                           ?>
                           checked
                           <?php
                           }?>
                       >
               </td>
               <td><?php echo htmlspecialchars(i18n_get_translated($state_name)); ?>&nbsp;</td>
               </tr>
                <?php  
                }
            ?>
        </table>
    </div>
    <div class="clearerleft"></div>
    <?php
	}
else
	{?>
	<input type="hidden" name="archive" value="<?php echo htmlspecialchars($archive)?>">
	<?php
	}

if(checkperm("v"))
    {
    render_question_div("search_advanced_access_question", function() use ($lang, $access)
        {
        ?>
        <label for="search_advanced_access"><?php echo htmlspecialchars($lang["access"]); ?></label>
        <select id="search_advanced_access" class="SearchWidth" name="access" onchange="UpdateResultCount();">
            <option><?php echo $lang["all"]; ?></option>
        <?php
        foreach(range(0, 2) as $access_level)
            {
            $label = htmlspecialchars($lang["access{$access_level}"]);
            $extra_attributes = (!is_null($access) && $access_level == $access ? " selected" : "");

            echo render_dropdown_option($access_level, $label, array(), $extra_attributes);
            }
        ?>
        </select>
        <?php
        });
    }

if($advanced_search_contributed_by)
    {
    ?>
    <div class="Question">
        <label><?php echo $lang["contributedby"]; ?></label>
        <?php
        $single_user_select_field_value=$properties_contributor;
        $single_user_select_field_id='properties_contributor';
        $single_user_select_field_onchange='UpdateResultCount();';
    	$userselectclass="searchWidth";
        include "../include/user_select.php";
    	?>
        <script>
    	jQuery('#properties_contributor').change(function(){UpdateResultCount();});
    	</script>
    	<?php
        unset($single_user_select_field_value);
        unset($single_user_select_field_id);
        unset($single_user_select_field_onchange);
        ?>
    </div>
    <?php
    }
?>

<?php if  ($search_includes_user_collections || $search_includes_public_collections || $search_includes_themes) { ?>
<h1 class="AdvancedSectionHead CollapsibleSectionHead" id="AdvancedSearchCollectionsSectionHead" <?php if (!in_array("Collections",$selectedtypes) && !$collection_search_includes_resource_metadata) {?> style="display: none;" <?php } ?>><?php echo $lang["collections"]; ?></h1>
<div class="AdvancedSection" id="AdvancedSearchCollectionsSection" <?php if (!in_array("Collections",$selectedtypes) && !$collection_search_includes_resource_metadata) {?> style="display: none;" <?php } ?>>

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
	 # Clearbutton means resetform
	 $resetform=getval("resetform","");
	 if ($resetform != "") 
	 	{
		$value="";
		}
	 # Render this field
	 render_search_field($fields[$n],$value,true,"SearchWidth",false,array(),$searched_nodes,$resetform);
	 }
   }
?>
</div>

<?php
}



if($advanced_search_media_section)
    {
    ?>
    <h1 class="AdvancedSectionHead CollapsibleSectionHead" id="AdvancedSearchMediaSectionHead" ><?php echo $lang["media"]; ?></h1>
    <div class="AdvancedSection" id="AdvancedSearchMediaSection">
    <?php 
    render_split_text_question($lang["pixel_height"], array('media_heightmin'=>$lang['from'],'media_heightmax'=>$lang['to']),$lang["pixels"], true, " class=\"stdWidth\" OnChange=\"UpdateResultCount();\"", array('media_heightmin'=>$media_heightmin,'media_heightmax'=>$media_heightmax));
    render_split_text_question($lang["pixel_width"], array('media_widthmin'=>$lang['from'],'media_widthmax'=>$lang['to']),$lang["pixels"], true, " class=\"stdWidth\" OnChange=\"UpdateResultCount();\"", array('media_widthmin'=>$media_widthmin,'media_widthmax'=>$media_widthmax));
    render_split_text_question($lang["filesize"], array('media_filesizemin'=>$lang['from'],'media_filesizemax'=>$lang['to']),$lang["megabyte-symbol"], false, " class=\"stdWidth\" OnChange=\"UpdateResultCount();\"", array('media_filesizemin'=>$media_filesizemin,'media_filesizemax'=>$media_filesizemax));
    render_text_question($lang["file_extension_label"], "media_fileextension", "",false," class=\"SearchWidth\" OnChange=\"UpdateResultCount();\"",$media_fileextension);
    render_dropdown_question($lang["previewimage"], "properties_haspreviewimage", array(""=>"","1"=>$lang["yes"],"0"=>$lang["no"]), $properties_haspreviewimage, " class=\"SearchWidth\" OnChange=\"UpdateResultCount();\"");
    render_dropdown_question(
        $lang["orientation"],
        "properties_orientation",
        array(
            ""          => "",
            "portrait"  => $lang["portrait"],
            "landscape" => $lang["landscape"],
            "square"    => $lang["square"]
        ),
        $properties_orientation,
        "class=\"SearchWidth\" onchange=\"UpdateResultCount();\"");
    ?>
    </div><!-- End of AdvancedSearchMediaSection -->
    <?php
    }

render_advanced_search_buttons();

// show result count as it stands ?>
</div> <!-- BasicsBox -->
<?php
if($archive!==0){
	?>
	<script>
	jQuery(document).ready(function()
	  {
	  UpdateResultCount();
	  jQuery("input").keypress(function(event) {
		   if (event.which == 13) {
			   event.preventDefault();
			   jQuery("#advancedform").submit();
		   }
	  });
	  });
	</script>
	<?php
}

include "../include/footer.php";
