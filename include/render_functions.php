<?php
/**
* Functions used to render HTML & Javascript
*
* @package ResourceSpace
*/


/**
* Renders the HTML for the provided $field for inclusion in a search form, for example the
* advanced search page. Standard field titles are translated using $lang.  Custom field titles are i18n translated.
*
* $field    an associative array of field data, i.e. a row from the resource_type_field table.
* $name     the input name to use in the form (post name)
* $value    the default value to set for this field, if any
* $reset    is non-blank if the caller requires the field to be reset
* @param array $searched_nodes Array of all the searched nodes previously
*/
function render_search_field($field,$value="",$autoupdate=false,$class="stdwidth",$forsearchbar=false,$limit_keywords=array(), $searched_nodes = array(), $reset="",$simpleSearchFieldsAreHidden=false)
    {
    node_field_options_override($field);
	
	global $auto_order_checkbox, $auto_order_checkbox_case_insensitive, $lang, $category_tree_open, $minyear, $daterange_search, $searchbyday, $is_search, $values, $n, $simple_search_show_dynamic_as_dropdown, $clear_function, $simple_search_display_condition, $autocomplete_search, $baseurl, $fields, $baseurl_short, $extrafooterhtml,$FIXED_LIST_FIELD_TYPES, $maxyear_extends_current;
?>
<!-- RENDERING FIELD=<?php echo $field['ref']." ".$field['name'];?> -->
<?php

    // set this to zero since this does not apply to collections
    if (!isset($field['field_constraint'])){$field['field_constraint']=0;}
      
    $name="field_" . ($forsearchbar ? htmlspecialchars($field["name"]) : $field["ref"]);
    $id="field_" . $field["ref"];

    $scriptconditions=array();
        
    # Assume the field being rendered should be displayed
    $displaycondition=true;

    # If the field being rendered has a display condition
    #  For advanced search, always check the display condition
    #  For simple search, only check the display condition if the field is included in the simple_search_display_condition config array 
    if ( $field["display_condition"]!="" 
    && ( !$forsearchbar || ($forsearchbar && !empty($simple_search_display_condition) && in_array($field['ref'],$simple_search_display_condition)) ) )
        {
        # Split the display condition of the field being rendered into an array of tests (if there are more than one, they are separated by a ";")
        # Each test is in the form governing field = governing field value 
        #   If the field being rendered is itself a governing field then "On Change" code must be generated for the governing field
        #   If the field being rendered is a governed field then "Checking" code must be generated for each governing field
        $s=explode(";",$field["display_condition"]);
        $condref=0;
        foreach ($s as $condition) # Check each individual test
            {
            # Assume that the current test does not need to be checked
            $displayconditioncheck=false;
            $s=explode("=",$condition);
            # Process each field to see if it is being referenced in the current test
            global $fields;
            for ($cf=0;$cf<count($fields);$cf++) # Check each field to see if needs to be checked
                {
                # If the field being processed is referenced in the current test 
                if ($s[0]==$fields[$cf]["name"]) 
                    {
                    # The field being processed is a governing field whose value(s) control whether the field being rendered is to be visible or hidden
                    $display_condition_js_prepend=($forsearchbar ? "#simplesearch_".$fields[$cf]["ref"]." " : "");
                    
                    # The script conditions array contains an entry for each governing field
                    $scriptconditions[$condref]["field"]               = $fields[$cf]["ref"];  # add new jQuery code to check value
                    $scriptconditions[$condref]["name"]                = $fields[$cf]["name"];
                    $scriptconditions[$condref]['type']                = $fields[$cf]['type'];
                    $scriptconditions[$condref]['display_as_dropdown'] = $fields[$cf]['display_as_dropdown'];
                    # Get the node references of the governing field
					$scriptconditionnodes = get_nodes($fields[$cf]['ref'], null, (FIELD_TYPE_CATEGORY_TREE == $fields[$cf]['type'] ? true : false));
                    
                    $checkvalues=$s[1];
                    # Prepare an array of values present in the test
                    $validvalues=explode("|",strtoupper($checkvalues));
					$scriptconditions[$condref]['valid'] = array();
					$scriptconditions[$condref]['validtext'] = array();
					foreach($validvalues as $validvalue)
						{
                        # The validtext array is for checking input values instead of their corresponding node references
                        $scriptconditions[$condref]['validtext'][] = strtolower($validvalue);

                        # Convert the value name into a node entry if it is a valid node within the governing field
						$found_validvalue = get_node_by_name($scriptconditionnodes, $validvalue);

                        # If there is a node which corresponds to that value name then append its node reference to a list of valid nodes
						if(0 != count($found_validvalue))
							{
							$scriptconditions[$condref]['valid'][] = $found_validvalue['ref'];
                            
                            # Is the node present in search result list of nodes
                            if(in_array($found_validvalue['ref'],$searched_nodes))
                                {
                                # Value being tested is present in the searched nodes array
                                $displayconditioncheck = true;
                                }
                            }
                        }
				
                    # Suppress this field if none of the nodes (of the values) in the test match the searched nodes array
                    if (!$displayconditioncheck)
                        {
                        $displaycondition=false; # Do not render field
                        }

					
				// Certain fixed list types allow for multiple nodes to be passed at the same time

                // Generate a javascript function specific to the field being rendered
                // This function will be invoked whenever any governing field changes
                if(in_array($fields[$cf]['type'], $FIXED_LIST_FIELD_TYPES))
					{
						if(FIELD_TYPE_CATEGORY_TREE == $fields[$cf]['type'])
							{
                            ?>
                            <!-- SETUP HANDLER FOR GOVERNOR=<?php echo $fields[$cf]['ref']; ?> GOVERNED=<?php echo $field['ref']; ?>-->
							<script type="text/javascript">
                            var wto;
							jQuery(document).ready(function()
								{
								jQuery('#CentralSpace').on('categoryTreeChanged', function(e,node)
									{
                                    // Debounce multiple events fired by the category tree
                                    clearTimeout(wto);
                                    wto=setTimeout(function() {
                                        // Reflect the change of the governing field into the following governed field condition checker
                                        console.log("<?php echo "DISPCOND CATTREE CHANGEGOVERNOR=".$fields[$cf]['ref'] ?>");
                                        for (i = 0; i<categoryTreeChecksArray.length; i++) {
                                            categoryTreeChecksArray[i]();
                                        }
                                    }, 200);
									});
								});
							</script>
							<?php
							// Move on to the next field now
							continue;
							}
						else if(FIELD_TYPE_DYNAMIC_KEYWORDS_LIST == $fields[$cf]['type'])
							{
                            if ($forsearchbar) {
                                if ($simple_search_show_dynamic_as_dropdown) {
                                    $checkname       = "nodes_searched[{$fields[$cf]['ref']}]";
                                    $jquery_selector = "select[name=\"{$checkname}\"]";
                                }
                                else {
                                    $jquery_selector = "input[name=\"field_{$fields[$cf]["name"]}\"]";
                                }
							?>
                            <!-- SETUP HANDLER FOR GOVERNOR=<?php echo $fields[$cf]['ref']; ?> GOVERNED=<?php echo $field['ref']; ?>-->
							<script type="text/javascript">
							jQuery(document).ready(function()
								{
                                jQuery('<?php echo $jquery_selector; ?>').change(function ()
                                    {
                                    // Reflect the change of the governing field into the following governed field condition checker
                                    console.log("<?php echo "DISPCOND DYNAMKKD CHANGEGOVERNOR=".$fields[$cf]['ref']." CHECK GOVERNED=".$field['ref'] ?>");
                                    checkSearchDisplayCondition<?php echo $field['ref']; ?>();
                                    });
                                });
							</script>
							<?php
                            }
                            else { # Advanced search
                            ?>
                            <!-- SETUP HANDLER FOR GOVERNOR=<?php echo $fields[$cf]['ref']; ?> GOVERNED=<?php echo $field['ref']; ?>-->
							<script type="text/javascript">
							jQuery(document).ready(function()
								{
								jQuery('#CentralSpace').on('dynamicKeywordChanged', function(e,node)
									{
                                    // Reflect the change of the governing field into the following governed field condition checker
                                    console.log("<?php echo "DISPCOND DYNAMKWD CHANGEGOVERNOR=".$fields[$cf]['ref']." CHECK GOVERNED=".$field['ref'] ?>");
									checkSearchDisplayCondition<?php echo $field['ref']; ?>();
									});
								});
							</script>
							<?php
                            }

							// Move on to the next field now
							continue;
							}
                        else
                            {
                            # Otherwise FIELD_TYPE_CHECK_BOX_LIST or FIELD_TYPE_DROP_DOWN_LIST or FIELD_TYPE_RADIO_BUTTONS
                            
                            # Simple search will always display these types as dropdowns
                            if ($forsearchbar) {
                                $checkname       = "nodes_searched[{$fields[$cf]['ref']}]";
                                $jquery_selector = "select[name=\"{$checkname}\"]";
                            }
                            # Advanced search will display these as dropdowns if marked as such, otherwise they are displayed as checkbox lists to allow OR selection
                            else {
                                # Prepare selector on the assumption that its an input element (ie. a checkbox list or a radio button or a dropdown displayed as checkbox list)
                                $checkname = "nodes_searched[{$fields[$cf]['ref']}]";
                                $jquery_selector = "input[name=\"{$checkname}\"]";

                                # If however its a drop down list then we should be processing select elements
                                If ($fields[$cf]['display_as_dropdown'] == true)
                                    {
                                    $checkname       = "nodes_searched[{$fields[$cf]['ref']}]";
                                    $jquery_selector = "select[name=\"{$checkname}\"]";
                                    }
                            } 
                            ?>
                            <!-- SETUP HANDLER FOR GOVERNOR=<?php echo $fields[$cf]['ref']; ?> GOVERNED=<?php echo $field['ref']; ?>-->
                            <script type="text/javascript">
                            jQuery(document).ready(function()
                                {
                                jQuery('<?php echo $jquery_selector; ?>').change(function ()
                                    {
                                    // Reflect the change of the governing field into the following governed field condition checker
                                    console.log("<?php echo "DISPCOND CHANGEGOVERNOR=".$fields[$cf]['ref']." CHECK GOVERNED=".$field['ref'] ?>");
                                    checkSearchDisplayCondition<?php echo $field['ref']; ?>();
                                    });
                                });
                            </script>
                            <?php
                            }
						} 
					else
						{ # Not one of the FIXED_LIST_FIELD_TYPES
						?>
                        <!-- SETUP HANDLER FOR GOVERNOR=<?php echo $fields[$cf]['ref']; ?> GOVERNED=<?php echo $field['ref']; ?>-->
                        <script type="text/javascript">
						jQuery(document).ready(function()
							{
							jQuery('#field_<?php echo $fields[$cf]["ref"]; ?>').change(function ()
								{
                                // Reflect the change of the governing field into the following governed field condition checker
                                checkSearchDisplayCondition<?php echo $field['ref']; ?>();
								});
							});
						</script>
						<?php
						}
					}
                } # see if next field needs to be checked

            $condref++;
            } # check next condition

        ?>
        <?php echo "<!-- CHECK CONDITIONS FOR GOVERNED FIELD ".$field['name']." [".$field['ref']."] -->";
        $function_has_category_tree_check=false;
        ?>
        <script type="text/javascript">
        
        <?php 
        if(!$displaycondition && $forsearchbar)
            {
            // Create or add to array of fields to hide when clearing search
            ?>
            if(typeof clearhiddenfields == "object")
                {
                clearhiddenfields.push('<?php echo $field["ref"]; ?>');
                }
            else
                {
                clearhiddenfields = new Array('<?php echo $field["ref"]; ?>');
                }
            <?php
            }?>

        checkSearchDisplayCondition<?php echo $field["ref"];?> = function ()   
			{
            // Check the node passed in from the changed governing field
            var idname<?php echo $field['ref']; ?>     = "<?php echo $forsearchbar?"#simplesearch_".$field['ref']:"#question_".$n; ?>";
            // Get current display state for governed field ("block" or "none")
            field<?php echo $field['ref']; ?>status    = jQuery(idname<?php echo $field['ref']; ?>).css('display');
			newfield<?php echo $field['ref']; ?>status = 'none';
           
            // Assume visible by default
            field<?php echo $field['ref']; ?>visibility = true;

            <?php
			foreach($scriptconditions as $scriptcondition)
				{
                echo "// Checking values on field ".$scriptcondition['field']."\n";
                # Example of $scriptconditions: [{"field":"73","type":"3","display_as_dropdown":"0","valid":["267","266"]}] 
                if ($scriptcondition['type'] == FIELD_TYPE_CATEGORY_TREE) {
                    $function_has_category_tree_check=true;
                }
            ?>

            field<?php echo $field['ref']; ?>valuefound = false;
            fieldokvalues<?php echo $scriptcondition['field']; ?> = <?php echo json_encode($scriptcondition['valid']); ?>;

            <?php
            if ($scriptcondition['type'] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST) {
                if ($forsearchbar)
                    if(!$simple_search_show_dynamic_as_dropdown) {
                    ?>
                        // When a dynamic keyword list is rendered as regular input field on simple search, the valid values to check against are the text values (not nodes) 
                        fieldokvalues<?php echo $scriptcondition['field']; ?> = <?php echo json_encode($scriptcondition['validtext']); ?>;
                    <?php
                    }
                }
            ?>

            <?php echo "// CHECK IF GOVERNING ".$scriptcondition['name']." [".$scriptcondition['field']."] VALUE(S) ENABLE DISPLAY";?>

            <?php

            # Generate the javascript code necessary to condition the rendered field based on value(s) present in the governing field

            # Prepare base name for selector 
            $checkname = "nodes_searched[{$scriptcondition['field']}]";
            $js_conditional_statement  = "fieldokvalues{$scriptcondition['field']}.indexOf(element.value) != -1";

            # Prepare fallback selector 
            $jquery_condition_selector = "input[name=\"{$checkname}\"]";
            $jquery_selector_suffix="";

            if(in_array($scriptcondition['type'], $FIXED_LIST_FIELD_TYPES))
                {
                # Append additional brackets rendered on category tree and dynamic keyword list hidden inputs
                if (in_array($scriptcondition['type'], array(FIELD_TYPE_CATEGORY_TREE, FIELD_TYPE_DYNAMIC_KEYWORDS_LIST)) ) {
                    $jquery_condition_selector = "input[name=\"{$checkname}[]\"]";
                }
                
                # Prepare selector for a checkbox list or a radio button or a dropdown list
                if (in_array($scriptcondition['type'], array(FIELD_TYPE_CHECK_BOX_LIST, FIELD_TYPE_RADIO_BUTTONS, FIELD_TYPE_DROP_DOWN_LIST))) {

                    # Simple search will always display these types as dropdowns, so search for selected option
                    if ($forsearchbar) {
                        $jquery_condition_selector = "select[name=\"{$checkname}\"] option:selected";
                    }
                    # Advanced search will display these as dropdowns if marked as such, otherwise they are displayed as checkbox lists to allow OR selection
                    else {
                        # Prepare selector on the assumption that its an input element (ie. a checkbox list or a radio button or a dropdown displayed as checkbox list)
                        #   so search for checked boxes
                        $jquery_condition_selector = "input[name=\"{$checkname}\"]:checked:enabled";

                        # If however its a drop down list then we should be searching for selected option
                        If ($scriptcondition['display_as_dropdown'] == true)
                            {
                            $jquery_condition_selector = "select[name=\"{$checkname}\"] option:selected";
                            }
                    }                    

                }

                # Prepare selector for unusual dynamic keyword list configurations
                if ($scriptcondition['type'] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST) {
                    if ($forsearchbar)
                        if($simple_search_show_dynamic_as_dropdown) {
                            # Prepare selector for a dynamic keyword list configured to display as a dropdown list on simple search
                            $jquery_condition_selector = "select[name=\"{$checkname}\"] option:selected";
                        } else {
                            # Prepare selector for a dynamic keyword list rendered as regular input field
                            $jquery_condition_selector = "input[name=\"field_{$scriptcondition['name']}\"]";
                        }
                    }

                ?>
                    jQuery('<?php echo $jquery_condition_selector; ?>').each(function(index, element)
                    {
                        if(<?php echo $js_conditional_statement; ?>)
                        {
                        // The governing node is in the list of qualifying node(s) which enable this governed field
                        field<?php echo $field['ref']; ?>valuefound = true;
                        }
                    });

                <?php
                }?>

                // If no governing node found then disable this governed field
                if(!field<?php echo $field['ref']; ?>valuefound)
                {
                field<?php echo $field['ref']; ?>visibility = false;
                }

            <?php
                echo "// End of checking values on field ".$scriptcondition['field']."\n\n            ";
                }
            ?>

                // If the governed field is enabled then set it to display
                if(field<?php echo $field['ref']; ?>visibility)
                    {
                    newfield<?php echo $field['ref']; ?>status = 'block';
                    }

                // If the governed field display state has changed then enact the change by sliding
                if ( newfield<?php echo $field['ref']; ?>status != field<?php echo $field['ref']; ?>status )
                    {
                    console.log("IDNAME " + idname<?php echo $field['ref']; ?>);
                    console.log("   FIELD <?php echo $field['ref']; ?> STATUS '" + field<?php echo $field['ref']; ?>status+"'");
                    console.log("NEWFIELD <?php echo $field['ref']; ?> STATUS '" + newfield<?php echo $field['ref']; ?>status+"'");
                    // Toggle the display state between "block" and "none", clearing any incomplete actions in the process
                    jQuery(idname<?php echo $field['ref']; ?>).slideToggle(function()
                        {
                        console.log("SLIDETOGGLE FIELD <?php echo $field['ref']; ?>");
                        jQuery(idname<?php echo $field['ref']; ?>).clearQueue();
                        });
                    
                    // Adjust the border accordingly
                    if(jQuery(idname<?php echo $field['ref']; ?>).css('display') == 'block')
                        {
                        jQuery(idname<?php echo $field['ref']; ?>).css('border-top', '');
                        }
                    else
                        {
                        jQuery(idname<?php echo $field['ref']; ?>).css('border-top', 'none');
                        }
                    }
        }

        <?php 
        if ($function_has_category_tree_check) {
        ?>
        categoryTreeChecksArray.push(checkSearchDisplayCondition<?php echo $field["ref"];?>);
        <?php
        }
        ?>

        </script>
    	<?php
    	if($forsearchbar)
    		{
    		// add the display condition check to the clear function
    		$clear_function.="checkSearchDisplayCondition".$field['ref']."();";
    		}
        }

    $is_search = true;

    if (!$forsearchbar)
        {
        ?>
        <div class="Question" id="question_<?php echo $n ?>" <?php if (!$displaycondition) {?>style="display:none;border-top:none;"<?php } ?><?php
        if (strlen($field["tooltip_text"])>=1)
            {
            echo "title=\"" . htmlspecialchars(lang_or_i18n_get_translated($field["tooltip_text"], "fieldtooltip-")) . "\"";
            }
        ?>>
        <label><?php echo htmlspecialchars(lang_or_i18n_get_translated($field["title"], "fieldtitle-")) ?></label>
        <?php
        }
    else
        {
        hook("modifysearchfieldtitle");
        ?>
        <div class="SearchItem" id="simplesearch_<?php echo $field["ref"] ?>" <?php if (!$displaycondition || $simpleSearchFieldsAreHidden) {?>style="display:none;"<?php } if (strlen($field["tooltip_text"]) >= 1){ echo "title=\"" . htmlspecialchars(lang_or_i18n_get_translated($field["tooltip_text"], "fieldtooltip-")) . "\"";} ?> ><?php echo htmlspecialchars(lang_or_i18n_get_translated($field["title"], "fieldtitle-")) ?><br/>
        
        <?php
        #hook to modify field type in special case. Returning zero (to get a standard text box) doesn't work, so return 1 for type 0, 2 for type 1, etc.
		if(hook("modifyfieldtype")){$fields[$n]["type"]=hook("modifyfieldtype")-1;}
        }

    //hook("rendersearchhtml", "", array($field, $class, $value, $autoupdate));

    # Generate markup for field
    switch ($field["type"]) {
        case FIELD_TYPE_TEXT_BOX_SINGLE_LINE:
        case FIELD_TYPE_TEXT_BOX_MULTI_LINE:
        case FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE:
        case FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR:
        case ($forsearchbar && $field["type"]==FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && !$simple_search_show_dynamic_as_dropdown):
        # Dynamic keyword list behaviour replaced with regular input field under these circumstances
        if ((int)$field['field_constraint']==0)
            { 
			
			?><input class="<?php echo $class ?>" type=text name="<?php echo $name ?>" id="<?php echo $id ?>" value="<?php echo htmlspecialchars($value)?>" <?php if ($autoupdate) { ?>onChange="UpdateResultCount();"<?php } if(!$forsearchbar){ ?> onKeyPress="if (!(updating)) {setTimeout('UpdateResultCount()',2000);updating=true;}"<?php } if($forsearchbar){?>onKeyUp="if('' != jQuery(this).val()){FilterBasicSearchOptions('<?php echo htmlspecialchars($field["name"]) ?>',<?php echo htmlspecialchars($field["resource_type"]) ?>);}"<?php } ?>><?php 
			# Add to the clear function so clicking 'clear' clears this box.
			$clear_function.="document.getElementById('field_" . ($forsearchbar? $field["ref"] : $field["name"]) . "').value='';";
		    }
        // number view - manipulate the form value (don't send these but send a compiled numrange value instead
        else if ((int)$field['field_constraint']==1)
            {
             // parse value for to/from simple search
			$minmax=explode('|',str_replace("numrange","",$value));
			($minmax[0]=='')?$minvalue='':$minvalue=str_replace("neg","-",$minmax[0]);
			(isset($minmax[1]))?$maxvalue=str_replace("neg","-",$minmax[1]):$maxvalue='';
			?>
			<input id="<?php echo $name ?>_min" onChange="jQuery('#<?php echo $name?>').val('numrange'+jQuery(this).val().replace('-','neg')+'|'+jQuery('#<?php echo $name?>_max').val().replace('-','neg'));" class="NumberSearchWidth" type="number" value="<?php echo htmlspecialchars($minvalue)?>"> ...
			<input id="<?php echo $name ?>_max" onChange="jQuery('#<?php echo $name?>').val('numrange'+jQuery('#<?php echo $name?>_min').val().replace('-','neg')+'|'+jQuery(this).val().replace('-','neg'));" class="NumberSearchWidth" type="number" value="<?php echo htmlspecialchars($maxvalue)?>">
			<input id="<?php echo $name?>" name="<?php echo $name?>" type="hidden" value="<?php echo $value?>">
		    <?php 
			# Add to the clear function so clicking 'clear' clears this box.
			 $clear_function.="document.getElementById('".$name."_max').value='';";
			 $clear_function.="document.getElementById('".$name."_min').value='';";
			 $clear_function.="document.getElementById('".$name."').value='';";
		    }
		

        
        if ($forsearchbar && $autocomplete_search) { 
				# Auto-complete search functionality
				?></div>
				<script type="text/javascript">
				
				jQuery(document).ready(function () { 
				
					jQuery("#field_<?php echo htmlspecialchars($field["ref"])?>").autocomplete( { source: "<?php echo $baseurl?>/pages/ajax/autocomplete_search.php?field=<?php echo htmlspecialchars($field["name"]) ?>&fieldref=<?php echo $field["ref"]?>"} );
					})
				
				</script>
				<div class="SearchItem">
			<?php }
            
        break;
    
        case FIELD_TYPE_CHECK_BOX_LIST: 
        case FIELD_TYPE_DROP_DOWN_LIST:
        case ($forsearchbar && $field["type"]==FIELD_TYPE_DYNAMIC_KEYWORDS_LIST && $simple_search_show_dynamic_as_dropdown):
       if(!hook("customchkboxes", "", array($field, $value, $autoupdate, $class, $forsearchbar, $limit_keywords)))
            {
            global $checkbox_ordered_vertically;

            # -------- Show a check list or dropdown for dropdowns and check lists?
            # By default show a checkbox list for both (for multiple selections this enabled OR functionality)
            
            $setnames  = trim_array(explode(";",cleanse_string($value,true)));
            # Translate all options
            $adjusted_dropdownoptions=hook("adjustdropdownoptions");
            if ($adjusted_dropdownoptions){$options=$adjusted_dropdownoptions;}
            
            if($forsearchbar)
            	{
            	$optionfields[]=$field["name"]; # Append to the option fields array, used by the AJAX dropdown filtering
            	}

            $node_options = array_column($field["nodes"], "name");

            if((bool) $field['automatic_nodes_ordering'])
                {
                $field['nodes'] = reorder_nodes($field['nodes']);
                }

            $order_by_resetter = 0;
            foreach($field['nodes'] as $node_index => $node)
                {
                // Special case for vertically ordered checkboxes.
                // Order by needs to be reset as per the new order so that we can reshuffle them using the order by as a reference
                if($checkbox_ordered_vertically)
                    {
                    $field['nodes'][$node_index]['order_by'] = $order_by_resetter++;
                    }
                }

            if ($field["display_as_dropdown"] || $forsearchbar)
                {
                # Show as a dropdown box
                $name = "nodes_searched[{$field['ref']}]";
                ?>
                <select class="<?php echo $class ?>" name="<?php echo $name ?>" id="<?php echo $id ?>"
                    <?php if ($autoupdate) { ?>onChange="UpdateResultCount();"<?php } 
                          if($forsearchbar){ ?>onChange="FilterBasicSearchOptions('<?php echo htmlspecialchars($field["name"]) ?>',<?php echo htmlspecialchars($field["resource_type"]) ?>);" <?php } ?>>
                    <option value=""></option>
                <?php
                foreach($field['nodes'] as $node)
                    {
                    if('' != trim($node['name']))
                        {
                        ?>
                        <option value="<?php echo htmlspecialchars(trim($node['ref'])); ?>" <?php if (0 < count($searched_nodes) && in_array($node['ref'], $searched_nodes)) { ?>selected<?php } ?>><?php echo htmlspecialchars(trim(i18n_get_translated($node['name']))); ?></option>
                        <?php
                        }
                    }
                ?></select><?php
                if($forsearchbar)
                	{
                	// Add to the clear function so clicking 'clear' clears this box.
					$clear_function .= "document.getElementById('{$id}').selectedIndex = -1;";
                	}
                }
            else
                {
                # Show as a checkbox list (default)
                $setnames=trim_array(explode(";",$value));
                $wrap=0;

                $l    = average_length($node_options);
                switch($l)
                    {
                    case($l > 40): $cols = 1; break; 
                    case($l > 25): $cols = 2; break;
                    case($l > 15): $cols = 3; break;
                    case($l > 10): $cols = 4; break;
                    case($l > 5):  $cols = 5; break;
                    default:       $cols = 10;
                    }

                $height = ceil(count($field['nodes']) / $cols);

                global $checkbox_ordered_vertically, $checkbox_vertical_columns;
                if($checkbox_ordered_vertically)
                    {
                    if(!hook('rendersearchchkboxes'))
                        {
                        # ---------------- Vertical Ordering (only if configured) -----------
                        ?>
                        <table cellpadding=2 cellspacing=0>
                            <tbody>
                                <tr>
                                <?php
                                for($i = 0; $i < $height; $i++)
                                    {
                                    for($j = 0; $j < $cols; $j++)
                                        {
                                        $order_by = ($height * $j) + $i;

                                        $node_index_to_be_reshuffled = array_search($order_by, array_column($field['nodes'], 'order_by', 'ref'));

                                        if(false === $node_index_to_be_reshuffled)
                                            {
                                            continue;
                                            }

                                        $node = $field['nodes'][$node_index_to_be_reshuffled];
                                        ?>
                                        <td valign=middle>
                                            <input id="nodes_searched_<?php echo $node['ref']; ?>" type="checkbox" name="nodes_searched[<?php echo $field['ref']; ?>]" value="<?php echo $node['ref']; ?>" <?php if((0 < count($searched_nodes) && in_array($node['ref'], $searched_nodes)) || in_array(i18n_get_translated($node['name']),$setnames)) { ?>checked<?php } ?> <?php if($autoupdate) { ?>onClick="UpdateResultCount();"<?php } ?>>
                                        </td>
                                        <td valign=middle>
                                            <?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?>&nbsp;&nbsp;
                                        </td>
                                        <?php
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                    <?php
                                    }
                                    ?>
                            </tbody>
                        </table>
                        <?php
                        }
                    }
                else
                    {
                    # ---------------- Horizontal Ordering (Standard) ---------------------             
                    ?>
                    <table cellpadding=2 cellspacing=0>
                        <tr>
                    <?php
                    foreach($field['nodes'] as $node)
                        {
                        $wrap++;

                        if($wrap > $cols)
                            {
                            $wrap = 1;
                            ?>
                            </tr>
                            <tr>
                            <?php
                            }

                        if('' != $node['name'])
                            {
                            ?>
                            <td valign=middle>
                                <input id="nodes_searched_<?php echo $node['ref']; ?>" type="checkbox" name="nodes_searched[<?php echo $field['ref']; ?>]" value="<?php echo $node['ref']; ?>" <?php if ((0 < count($searched_nodes) && in_array($node['ref'], $searched_nodes)) || in_array(i18n_get_translated($node['name']),$setnames)) {?>checked<?php } ?> <?php if ($autoupdate) { ?>onClick="UpdateResultCount();"<?php } ?>>
                            </td>
                            <td valign=middle>
                                <?php echo htmlspecialchars(i18n_get_translated($node['name'])); ?>&nbsp;&nbsp;
                            </td>
                            <?php
                            }
                        }
                        ?>
                        </tr>
                    </table>
                    <?php
                    }
                    
                }
            }
        break;
        
        case FIELD_TYPE_DATE_AND_OPTIONAL_TIME:
        case FIELD_TYPE_EXPIRY_DATE: 
        case FIELD_TYPE_DATE: 
        case FIELD_TYPE_DATE_RANGE: 
        $found_year='';$found_month='';$found_day='';$found_start_year='';$found_start_month='';$found_start_day='';$found_end_year='';$found_end_month='';$found_end_day='';
        if (!$forsearchbar && $daterange_search)
            {
			render_date_range_field($name, $value, true, $autoupdate, array(), $reset);
            }
        else
            {
            $s=explode("|",$value);
            if (count($s)>=3)
            {
            $found_year=$s[0];
            $found_month=$s[1];
            $found_day=$s[2];
            }
            ?>      
            <select name="<?php echo $name?>_year" id="<?php echo $id?>_year" class="SearchWidth<?php if ($forsearchbar){ echo "Half";} ?>" style="width:120px;" <?php if ($autoupdate) { ?>onChange="UpdateResultCount();"<?php } ?>>
              <option value=""><?php echo $lang["anyyear"]?></option>
              <?php
              $y=date("Y");
              $y += $maxyear_extends_current;
              for ($d=$y;$d>=$minyear;$d--)
                {
                ?><option <?php if ($d==$found_year) { ?>selected<?php } ?>><?php echo $d?></option><?php
                }
              ?>
            </select>
            
            <?php if ($forsearchbar && $searchbyday) { ?><br /><?php } ?>
            
            <select name="<?php echo $name?>_month" id="<?php echo $id?>_month" class="SearchWidth<?php if ($forsearchbar){ echo "Half SearchWidthRight";} ?>" style="width:120px;" <?php if ($autoupdate) { ?>onChange="UpdateResultCount();"<?php } ?>>
              <option value=""><?php echo $lang["anymonth"]?></option>
              <?php
              for ($d=1;$d<=12;$d++)
                {
                $m=str_pad($d,2,"0",STR_PAD_LEFT);
                ?><option <?php if ($d==$found_month) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$d-1]?></option><?php
                }
              ?>
            </select>
            
            <?php if (!$forsearchbar || ($forsearchbar && $searchbyday)) 
            	{ 
            	?>
				<select name="<?php echo $name?>_day" id="<?php echo $id?>_day" class="SearchWidth<?php if ($forsearchbar){ echo "Half";} ?>" style="width:120px;" <?php if ($autoupdate) { ?>onChange="UpdateResultCount();"<?php } ?>>
				  <option value=""><?php echo $lang["anyday"]?></option>
				  <?php
				  for ($d=1;$d<=31;$d++)
					{
					$m=str_pad($d,2,"0",STR_PAD_LEFT);
					?><option <?php if ($d==$found_day) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
					}
				  ?>
				</select>
            	<?php 
            	}
            if($forsearchbar)
            	{
            	# Add to the clear function so clicking 'clear' clears this box.
				$clear_function.="
					document.getElementById('field_" . $field["ref"] . "_year').selectedIndex=0;
					document.getElementById('field_" . $field["ref"] . "_month').selectedIndex=0;
					";
				if($searchbyday)
					{
					$clear_function.="document.getElementById('field_" . $field["ref"] . "_day').selectedIndex=0;";
					}
				}
            }
                    
        break;
        
        case FIELD_TYPE_CATEGORY_TREE:
        global $category_tree_add_parents, $category_tree_search_use_and;

        $set  = preg_split('/[;\|]/', cleanse_string($value, true));
        $name = "nodes_searched[{$field['ref']}][]";

        /*
        For search, category trees work slightly different than the intended behaviour shown in edit_fields/7.php:
        Intended behaviour:
        1. Selecting a sub (child) node will automatically select all parent nodes up to and including the root level,
        unless the option $category_tree_add_parents is set to false

        On search this should work like this:
        Selecting a sub (child) node will NOT select all parent nodes unless the system is configured to search using AND
        */
        $category_tree_add_parents = $category_tree_search_use_and;

        if($forsearchbar)
            {
            $original_category_tree_open = $category_tree_open;
            $category_tree_open          = true;
            $treeonly                    = true;
            $status_box_elements         = '';

            foreach($searched_nodes as $node)
                {
                $n_details = array();

                if(get_node($node, $n_details)===false)
                    {
                    continue;
                    }

                if($n_details["resource_type_field"] != $field["ref"])
                    {
                    continue;
                    }

                // Show previously searched options on the status box
                $status_box_elements .= "<span id=\"nodes_searched_{$field['ref']}_statusbox_option_{$n_details['ref']}\">{$n_details['name']}</span><br />";
                }
            ?>
			<div id="field_<?php echo htmlspecialchars($field['name']); ?>">
    			<div id="nodes_searched_<?php echo $field['ref']; ?>_statusbox" class="MiniCategoryBox">
                    <?php echo $status_box_elements; ?>
                </div> 
                <a href="#"
                   onClick="
                        jQuery('#cattree_<?php echo $field['name']; ?>').slideToggle();
                        
                        return false;"><?php echo $lang['showhidetree']; ?></a>
                <div id="cattree_<?php echo $fields[$n]['name']; ?>" class="RecordPanel PopupCategoryTree">
                    <?php
                    include __DIR__ . '/../pages/edit_fields/7.php';

                    // Reset category_tree_open because normally searchbar occurs before edit/ advanced search page
                    $category_tree_open = $original_category_tree_open;
                    ?>
                </div>
                
            </div>
			<?php
			# Add to clear function
			$clear_function .= "
                jQuery('#search_tree_{$field['ref']}').jstree(true).deselect_all();

                /* remove the hidden inputs */
                var elements = document.getElementsByName('nodes_searched[{$field['ref']}][]');
                while(elements[0])
                    {
                    elements[0].parentNode.removeChild(elements[0]);
                    }

                /* update status box */
                var node_statusbox = document.getElementById('nodes_searched_{$field['ref']}_statusbox');
                while(node_statusbox.lastChild)
                    {
                    node_statusbox.removeChild(node_statusbox.lastChild);
                    }
                ";
            }
        else
            {
            # For advanced search and elsewhere, include the category tree.
            include __DIR__ . "/../pages/edit_fields/7.php";
            }
        break;
        
        case FIELD_TYPE_DYNAMIC_KEYWORDS_LIST:
            include __DIR__ . '/../pages/edit_fields/9.php';
        break;      

        case FIELD_TYPE_RADIO_BUTTONS:
            // auto save is not needed when searching
            $edit_autosave           = false;
            $display_as_radiobuttons = false;
            $display_as_checkbox     = true;
            $name                    = "nodes_searched[{$field['ref']}][]";

            if($forsearchbar || $field['display_as_dropdown'])
                {
                $display_as_dropdown = true;
                $display_as_checkbox = false;
                $name                = "nodes_searched[{$field['ref']}]";

                $clear_function .= "document.getElementsByName('{$name}')[0].selectedIndex = -1;";
                }
            
            include __DIR__ . '/../pages/edit_fields/12.php';
            // need to adjust the field's name value
            ?>
        	<script type="text/javascript">
        		jQuery("#field_<?php echo $field['ref']?>").attr('name', 'field_<?php echo $field["name"]?>');
        	</script>
            <?php
        break;
        } ## END CASE
    ?>
    <div class="clearerleft"> </div>
    </div>
    <!-- ------------------------------------------------ -->
    <?php
    } # End of render_search_field

/**
* Renders sort order functionality as a dropdown box
*
*/
function render_sort_order(array $order_fields,$default_sort_order)
    {
    global $order_by, $baseurl_short, $lang, $search, $archive, $restypes, $k, $sort, $date_field;

    // use query strings here as this is used to render elements and sometimes it
    // can depend on other params
    $modal  = ('true' == getval('modal', ''));
    $sort = (in_array(mb_strtoupper($sort), array("ASC", "DESC")) ? mb_strtoupper($sort) : "DESC");
    ?>
    <select id="sort_order_selection" onChange="UpdateResultOrder();">
    
    <?php
    $options = '';
    foreach($order_fields as $name => $label)
        {
        // date shows as 'field'.$date_field rather than 'date' for collection searches so let's fix it
        if($name=='field'.$date_field)
			{
			$name='date';
			}
		
        // Are we constructing the option for the default order (ie. the first entry in the order_fields array)
        $current_is_default = ($name == $default_sort_order);

        // Is the currently set order that of the current field
        $selected = ($order_by == $name || ($name=='date' && $order_by=='field'.$date_field));
		
        // Build the option:
        $option = '<option value="' . $name . '"';

        // Set selection attribute if necessary
        if(($selected && $current_is_default) || $selected)
            {
            $option .= ' selected';
            }

        $option .= sprintf('
                data-url="%spages/search.php?search=%s&amp;order_by=%s&amp;archive=%s&amp;k=%s&amp;restypes=%s"
            ',
            $baseurl_short,
            urlencode($search),
            $name,
            urlencode($archive),
            urlencode($k),
            urlencode($restypes)
        );

        $option .= '>';
        $option .= $label;
        $option .= '</option>';

        // Add option to the options list
        $options .= $option;
        }

        hook('render_sort_order_add_option', '', array($options));
        echo $options;
    ?>
    </select>
    &nbsp;
    <a href="#" onClick="UpdateResultOrder(true);">
        <i id="sort_selection_toggle" class="fa fa-sort-amount-<?php echo mb_strtolower($sort); ?>"></i>
    </a>

    <script>
    function UpdateResultOrder(toggle_order)
        {
        var selected_option      = jQuery('#sort_order_selection :selected');
        var option_url           = selected_option.data('url');
        
        if (toggle_order)
            {
            var selected_sort_option='<?php echo ($sort=='ASC'?'DESC':'ASC'); ?>';
            }
        else
            {
            if(selected_option='resourcetype')
                {
                // The default sort should be ascending when sorting by resource type
                var selected_sort_option='<?php echo ($sort=='ASC'?'DESC':'ASC'); ?>';
                }
            else
                {
                var selected_sort_option='<?php echo ($sort=='ASC'?'ASC':'DESC'); ?>';
                }
            }
        option_url += '&sort=' + selected_sort_option;
         <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(option_url);
        }
    </script>
    <?php
    return;
    }

/**
* Renders a dropdown option
* 
*/
function render_dropdown_option($value, $label, array $data_attr = array(), $extra_tag_attributes  = '')
    {
    $result = '<option value="' . $value . '"';

    // Add any extra tag attributes
    if(trim($extra_tag_attributes) !== '')
        {
        $result .= ' ' . $extra_tag_attributes;
        }

    // Add any data attributes you may need
    foreach($data_attr as $data_attr_key => $data_attr_value)
        {
        $data_attr_key = str_replace(' ', '_', $data_attr_key);

        $result .= ' data-' . $data_attr_key . '="' . $data_attr_value . '"';
        }

    $result .= '>' . $label . '</option>';

    return $result;
    }


/**
* Renders search actions functionality as a dropdown box
* 
* @param array   $collection_data  Collection data
* @param boolean $top_actions      Set to true if actions are to be rendered in the search filter bar (above results)
* @param boolean $two_line         Display on two lines
* @param string  $id               Selector HTML ID
* @param array   $resource_data    Resource data
* @param boolean $optionsonly      Render only options
* @param string  $forpage          Specifically target for which page actions apply
* 
* @return void
*/
function render_actions(array $collection_data, $top_actions = true, $two_line = true, $id = '',$resource_data=array(),$optionsonly=false, $forpage="")
    {
    if(hook('prevent_running_render_actions'))
        {
        return;
        }

    global $baseurl, $lang, $k, $pagename, $order_by, $sort, $chosen_dropdowns, $allow_resource_deletion;
    
    // globals that could also be passed as a reference
    global $result /*search result*/;

    $action_selection_id = ($forpage!=""?$forpage:$pagename) . '_action_selection' . $id;
    if(!$top_actions)
        {
        $action_selection_id .= '_bottom';
        }
    if(isset($collection_data['ref']))
        {
        $action_selection_id .= '_' . str_replace("-","_",$collection_data['ref']);
        }
        
        
    if(!$optionsonly)
            {?>
    
            <div class="ActionsContainer  <?php if($top_actions) { echo 'InpageNavLeftBlock'; } ?>">
                <?php
        
                if($two_line)
                    {
                    ?>
                    <br />
                    <?php
                    }
                    ?>
                <select onchange="action_onchange_<?php echo $action_selection_id; ?>(this.value);" id="<?php echo $action_selection_id; ?>" <?php if(!$top_actions) { echo 'class="SearchWidth"'; } ?>>
            <?php } ?>
            <option class="SelectAction" selected disabled hidden value=""><?php echo $lang["actions-select"]?></option>
            <?php

            // Collection Actions
            $collection_actions_array = compile_collection_actions($collection_data, $top_actions, $resource_data);

            // Usual search actions
            $search_actions_array = compile_search_actions($top_actions);

            // Remove certain actions that apply only to searches
            if(!$top_actions)
                {
                $action_index_to_remove = array_search('search_items_disk_usage', array_column($search_actions_array, 'value'));
                unset($search_actions_array[$action_index_to_remove]);
                $search_actions_array = array_values($search_actions_array);
				
				$action_index_to_remove = array_search('save_search_items_to_collection', array_column($search_actions_array, 'value'));
                unset($search_actions_array[$action_index_to_remove]);
				$search_actions_array = array_values($search_actions_array);

                if($forpage === "themes")
                    {
                    $action_index_to_remove = array_search('remove_collection', array_column($collection_actions_array, 'value'));
                    unset($collection_actions_array[$action_index_to_remove]);
                    $collection_actions_array = array_values($collection_actions_array);
                    }
                }
    
            /**
            * @var A global variable that allows other parts in ResourceSpace to append extra options to the actions 
            * unified dropdown (plugins can use existing hooks).
            */
            $render_actions_extra_options = array();
            if(
                isset($GLOBALS["render_actions_extra_options"])
                && is_array($GLOBALS["render_actions_extra_options"])
                && !empty($GLOBALS["render_actions_extra_options"]))
                {
                $render_actions_extra_options = $GLOBALS["render_actions_extra_options"];
                }

            $actions_array = array_merge($collection_actions_array, $search_actions_array, $render_actions_extra_options);
            unset($render_actions_extra_options);

            $modify_actions_array = hook('modify_unified_dropdown_actions_options', '', array($actions_array,$top_actions));

            if(!empty($modify_actions_array))
                {
                $actions_array = $modify_actions_array;
                }

            /**
            * @var A global variable that allows other parts in ResourceSpace to filter actions options (plugins can use 
            * existing hooks).
            */
            if(isset($GLOBALS["render_actions_filter"]) && is_callable($GLOBALS["render_actions_filter"]))
                {
                $actions_array = array_filter($actions_array, $GLOBALS["render_actions_filter"]);
                unset($GLOBALS["render_actions_filter"]);
                }

            // Sort array into category groups
            usort($actions_array, function($a, $b){
               if(isset($a['category']) && isset($b['category']))
                    {
                    if($a['category'] == $b['category'])
                        {
                        // Same category, check for order_by. If no order_by add to end of category
                        if(isset($a['order_by']) && (!isset($b['order_by']) || ($b['order_by'] > $a['order_by'])))
                            {
                            return -1;
                            }
                        return 1;
                        }
                    else
                        {
                        return  $a['category'] - $b['category'];
                        }
                    }
                else
                    {
                    return isset($a['category']) ? -1 : 1;
                    }
                });
                                    
            // loop and display
            $options='';
            $lastcategory = 0;
			for($a = 0; $a < count($actions_array); $a++)
				{
                // Is this a new category?
                if(!isset($actions_array[$a]['category']))
                    {
                    $actions_array[$a]['category'] = 999;  
                    }
                if($lastcategory != $actions_array[$a]['category'])
                    {
                    if($a > 0)
                        {
                        $options .= "</optgroup>\n";
                        }
                    $options .= "<optgroup label='" . htmlspecialchars($lang["collection_actiontype_" . $actions_array[$a]['category']]) . "'>\n";
                    }

				if(!isset($actions_array[$a]['data_attr']))
					{
					$actions_array[$a]['data_attr'] = array();
					}

				if(!isset($actions_array[$a]['extra_tag_attributes']))
					{
					$actions_array[$a]['extra_tag_attributes'] = '';
					}

				$options .= render_dropdown_option($actions_array[$a]['value'], $actions_array[$a]['label'], $actions_array[$a]['data_attr'], $actions_array[$a]['extra_tag_attributes']);

				$add_to_options = hook('after_render_dropdown_option', '', array($actions_array, $a));
				if($add_to_options != '')
					{
					$options .= $add_to_options;
                    }
                if($a == count($actions_array))
                    {
                    $options .= "\n</optgroup>\n";
                    }
                $lastcategory = $actions_array[$a]['category'];
				}

			echo $options;
            
            if(!$optionsonly)
                { ?>
                </select>
                <?php } ?>
        <script>
        function action_onchange_<?php echo $action_selection_id; ?>(v)
            {
            if(v == '')
                {
                return false;
                }
            switch(v)
                {
            <?php
            if(0 !== count($collection_data) && collection_readable($collection_data['ref']))
                {
                ?>
                case 'select_collection':
                    ChangeCollection(<?php echo $collection_data['ref']; ?>, '');
                    break;

                case 'remove_collection':
                    if(confirm("<?php echo $lang['removecollectionareyousure']; ?>")) {
                        // most likely will need to be done the same way as delete_collection
                        document.getElementById('collectionremove').value = '<?php echo urlencode($collection_data["ref"]); ?>';
                        document.getElementById('collectionform').submit();
                    }
                    break;

                case 'purge_collection':
                    if(confirm('<?php echo $lang["purgecollectionareyousure"]; ?>'))
                        {
                        document.getElementById('collectionpurge').value='".urlencode($collections[$n]["ref"])."';
                        document.getElementById('collectionform').submit();
                        }
                    break;

                case 'relate_all':
                    var collection = <?php echo urlencode($collection_data['ref']);?>;
                    jQuery.ajax({
                        type: 'POST',
                        url: baseurl_short + 'pages/ajax/relate_resources.php?collection=' + collection,
                        data: {<?php echo generateAjaxToken("relate_resources"); ?>},
                        success: function(data) {
                            if (data.trim() == "SUCCESS") {
                                styledalert('<?php echo $lang["complete"]?>', '<?php echo $lang['relateallresources_confirmation']?>');
                            }
                        },
                        error: function (err) {
                            console.log("AJAX error : " + JSON.stringify(err, null, 2));
                        }
                    }); 
                    break;
                <?php
                }

            if(!$top_actions || !empty($collection_data))
                {
                global $search;
                $search_collection='';
                if(substr($search,0,11)=='!collection')
                    {
                    $search_trimmed = substr($search,11); // The collection search must always be the first part of the search string
                    $search_elements = split_keywords($search_trimmed, false, false, false, false, true);
                    $search_collection = (int)$search_elements[0];
                    }
                ?>
                case 'delete_collection':
                    if(confirm('<?php echo $lang["collectiondeleteconfirm"]; ?>')) {
                        var post_data = {
                            ajax: true,
                            dropdown_actions: true,
                            delete: <?php echo urlencode($collection_data['ref']); ?>,
                            <?php echo generateAjaxToken("delete_collection"); ?>
                        };

                        jQuery.post('<?php echo $baseurl; ?>/pages/collection_manage.php', post_data, function(response) {
                            if(response.success === 'Yes')
                                {
                                CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?collection=' + response.redirect_to_collection + '&k=' + response.k + '&nc=' + response.nc);

                                if(basename(document.URL).substr(0, 17) === 'collection_manage')
                                    {
                                    CentralSpaceLoad(document.URL);
                                    }
                                else if(basename(document.URL).substr(0, 6) === 'search' && '<?php echo $search_collection?>'=='<?php echo $collection_data["ref"]?>')
                                    {
                                    CentralSpaceLoad('<?php echo $baseurl; ?>/pages/search.php?search=!collection' + response.redirect_to_collection, true);
                                    }
                                }
                        }, 'json');    
                    }
                    break;
                <?php
                }

            // Add extra collection actions javascript case through plugins
            // Note: if you are just going to a different page, it should be easily picked by the default case
            $extra_options_js_case = hook('render_actions_add_option_js_case', '', array($action_selection_id));
            if(trim($extra_options_js_case) !== '')
                {
                echo $extra_options_js_case;
                }
            ?>

                case 'save_search_to_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'save_search_to_dash':
                    var option_url  = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    var option_link = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('link');
                    
                    // Dash requires to have some search parameters (even if they are the default ones)
                    if((basename(option_link).substr(0, 10)) != 'search.php')
                        {
                        option_link = (window.location.href).replace(window.baseurl, '');
                        }

                    option_url    += '&link=' + option_link;

                    CentralSpaceLoad(option_url);
                    break;

                case 'save_search_smart_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'save_search_items_to_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'empty_collection':
                    if(!confirm('<?php echo $lang["emptycollectionareyousure"]; ?>'))
                        {
                        break;
                        }

                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    CollectionDivLoad(option_url);
                    break;

                case 'copy_collection':
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    ModalLoad(option_url, false, true);
                    break;

            <?php
            if(!$top_actions)
                {
                if($allow_resource_deletion)
                    {
                    ?>
                    case 'delete_all_in_collection':
                        if(confirm('<?php echo $lang["deleteallsure"]; ?>'))
                            {
                            var post_data = {
                                submitted: true,
                                ref: '<?php echo $collection_data["ref"]; ?>',
                                name: <?php echo json_encode($collection_data["name"]); ?>,
                                public: '<?php echo ($collection_data["type"] == COLLECTION_TYPE_PUBLIC ? 1 : 0); ?>',
                                deleteall: 'on',
                                <?php echo generateAjaxToken("delete_all_in_collection"); ?>
                            };

                            jQuery.post('<?php echo $baseurl; ?>/pages/collection_edit.php?ajax=true', post_data, function()
                                {
                                CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?collection=<?php echo $collection_data["ref"] ?>');
                                });
                            }
                        break;
                    <?php
                    }
                    ?>

					case 'hide_collection':
						var action = 'hidecollection';
						var collection = <?php echo urlencode($collection_data['ref']);?>;
						var mycol = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('mycol');

						jQuery.ajax({
							type: 'POST',
							url: baseurl_short + 'pages/ajax/showhide_collection.php?action=' + action + '&collection=' + collection,
							data: {<?php echo generateAjaxToken("hide_collection"); ?>},
                            success: function(data) {
								if (data.trim() == "HIDDEN") {
									CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?collection='+mycol);
								}
							},
							error: function (err) {
								console.log("AJAX error : " + JSON.stringify(err, null, 2));
							}
						}); 
						break;

                <?php
                }
                ?>

                default:
                    var option_url = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('url');
                    var option_callback = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('callback');
                    var option_no_ajax = jQuery('#<?php echo $action_selection_id; ?> option:selected').data('no-ajax');

                    // If action option has a defined data-callback attribute, then we can call it
                    // IMPORTANT: never allow callback data attribute to be input/saved by user. Only ResourceSpace should
                    // generate the callbacks - key point is "generate"
                    if(typeof option_callback !== "undefined")
                        {
                        eval(option_callback);
                        }

                    // If action option has a defined data-url attribute, then we can CentralSpaceLoad it
                    if(typeof option_url !== "undefined")
                        {
                        if (typeof option_no_ajax == "undefined")
                            {
                            CentralSpaceLoad(option_url, true);
                            }
                        else
                            {
                            window.location.href = option_url;
                            }
                        }
    
                    break;
                }
				
                // Go back to no action option
                jQuery('#<?php echo $action_selection_id; ?> option[value=""]').prop('selected', true);
                <?php
                if($chosen_dropdowns)
                	{
                	?>
                	jQuery('#<?php echo $action_selection_id; ?>').trigger('chosen:updated');
                	<?php
                	}
                ?>

        }
        </script>
        
    <?php if (!$optionsonly)
        {?>
        </div>
        <?php
        }
    return;
    }



/**
* @param string $name
* @param array  $current  Current selected values (eg. array(1, 3) for Admins and Super admins user groups selected)
* @param int    $size     How many options to show before user has to scroll
*/
function render_user_group_multi_select($name, array $current = array(), $size = 10, $style = '')
    {
    ?>
    <select id="<?php echo $name; ?>" class="MultiSelect" name="<?php echo $name; ?>[]" multiple="multiple" size="<?php echo $size; ?>" style="<?php echo $style; ?>">
    <?php
    foreach(get_usergroups() as $usergroup)
        {
        ?>
        <option value="<?php echo $usergroup['ref']; ?>"<?php echo (in_array($usergroup['ref'], $current) ? ' selected' : ''); ?>><?php echo $usergroup['name']; ?></option>
        <?php
        }
        ?>
    </select>
    <?php
    }


/**
* Renders a list of user groups
* 
* @param string $name
* @param array  $current  Current selected values (eg. array(1, 3) for Admins and Super admins user groups selected)
* @param string $style    CSS styling that will apply to the outer container (ie. table element)
*
* @return void
*/
function render_user_group_checkbox_select($name, array $current = array(), $style = '')
    {
    ?>
    <table id="<?php echo $name; ?>"<?php if('' !== $style) { ?>style="<?php echo $style; ?>"<?php } ?>>
        <tbody>
    <?php
    foreach(get_usergroups(true) as $group)
        {
        ?>
        <tr>
            <td><input id="<?php echo $name . '_' . $group['ref']; ?>" type="checkbox" name="<?php echo $name; ?>[]" value="<?php echo $group['ref']; ?>"<?php if(in_array($group['ref'], $current)) { ?> checked<?php } ?> /></td>
            <td><label for="<?php echo $name . '_' . $group['ref']; ?>"><?php echo $group['name']; ?></label></td>
        </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
    <?php

    return;
    }

/**
* render_text_input_question - Used to display a question with simple text input
* 
* @param string $label			Label of question
* @param string $input  		Name of input field
* @param string $additionaltext Text to to display after input
* @param boolean $numeric 		Set to true to force numeric input
* @param array  $ctx            Rendering context. Should be used to inject different elements (e.g set the div class)
* 
* @return void
*/
function render_text_question($label, $input, $additionaltext="", $numeric=false, $extra="", $current="", array $ctx = array())
    {
    $div_class = array("Question");
    if(isset($ctx["div_class"]) && is_array($ctx["div_class"]) && !empty($ctx["div_class"]))
        {
        $div_class = array_merge($div_class, $ctx["div_class"]);
        }
	?>
	<div id="pixelwidth" class="<?php echo implode(" ", $div_class); ?>" >
		<label><?php echo $label; ?></label>
		<div>
		<?php
		echo "<input name=\"" . $input . "\" type=\"text\" ". ($numeric?"numericinput":"") . "\" value=\"" . $current . "\"" . $extra . "/>\n";
			
		echo $additionaltext;
		?>
		</div>
	</div>
	<div class="clearerleft"> </div>
	<?php
	}
	
/**
* render_split_text_question - Used to display a question with two inputs e.g. for a from/to range
* 
* @param string $label	Label of question
* @param array  $inputs  Array of input names and labels(eg. array('pixelwidthmin'=>'From','pixelwidthmin'=>'To')
* @param string $additionaltext (optional) 	Text to to display after input
* @param boolean $numeric 					Set to true to force numeric input
*/
function render_split_text_question($label, $inputs = array(), $additionaltext="", $numeric=false, $extra="", $currentvals=array())
    {
	?>
	<div class="Question" id = "pixelwidth">
		<label><?php echo $label; ?></label>
		<div>
		<?php
		foreach ($inputs as $inputname=>$inputtext)
			{
			echo "<div class=\"SplitSearch\">" . $inputtext . "</div>\n";
			echo "<input name=\"" . $inputname . "\" class=\"SplitSearch\" type=\"text\"". ($numeric?"numericinput":"") . "\" value=\"" . $currentvals[$inputname] . "\"" . $extra . " />\n";
			}
		echo $additionaltext;
		?>
		</div>
	</div>
	<div class="clearerleft"> </div>
	<?php
	}

/**
* render_dropdown_question - Used to display a question with a dropdown selector
* 
* @param string $label	   Label of question
* @param string $inputname Name of input field
* @param array  $options   Array of options (value and text pairs) (eg. array('pixelwidthmin'=>'From','pixelwidthmin'=>'To')
* @param string $current   The current selected value
* @param string $extra     Extra attributes used on the selector element
* @param array  $ctx       Rendering context. Should be used to inject different elements (e.g set the div class, add onchange for select)
* 
* @return void
*/
function render_dropdown_question($label, $inputname, $options = array(), $current="", $extra="", array $ctx = array())
    {
    $div_class = array("Question");
    if(isset($ctx["div_class"]) && is_array($ctx["div_class"]) && !empty($ctx["div_class"]))
        {
        $div_class = array_merge($div_class, $ctx["div_class"]);
        }

    $onchange = (isset($ctx["onchange"]) && trim($ctx["onchange"]) != "" ? trim($ctx["onchange"]) : "");
    $onchange = ($onchange != "" ? sprintf("onchange=\"%s\"", $onchange) : "");

    $extra .= " {$onchange}";
    ?>
    <div class="<?php echo implode(" ", $div_class); ?>">
        <label for="<?php echo $inputname?>"><?php echo $label; ?></label>
        <select  name="<?php echo $inputname?>" id="<?php echo $inputname?>" <?php echo $extra; ?>>
        <?php
        foreach ($options as $optionvalue=>$optiontext)
            {
            ?>
            <option value="<?php echo htmlspecialchars(trim($optionvalue))?>" <?php if (trim($optionvalue)==trim($current)) {?>selected<?php } ?>><?php echo htmlspecialchars(trim($optiontext))?></option>
            <?php
            }
        ?>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    return;
    }

/**
* Render a table row (tr) for a single access key
* 
* @param array $record Access key record details
* 
* @return void
*/
function render_access_key_tr(array $record)
    {
    global $baseurl, $baseurl_short, $lang;
    $link      = '';
    $type      = '';
    $edit_link = '';

    // Set variable dependent on type (ie. Resource / Collection)
    if('' == $record['collection'] && '' != $record['resource'])
        {
        // For resource
        $link      = $baseurl . '?r=' . urlencode($record['resource']) . '&k=' . urlencode($record['access_key']);
        $type      = $lang['share-resource'];
        $edit_link = sprintf('%spages/resource_share.php?ref=%s&editaccess=%s&editexpiration=%s&editaccesslevel=%s&editgroup=%s&backurl=%s',
            $baseurl_short,
            urlencode($record['resource']),
            urlencode($record['access_key']),
            urlencode($record['expires']),
            urlencode($record['access']),
            urlencode($record['usergroup']),
            urlencode("/pages/team/team_external_shares.php")
        );
        }
    else
        {
        // For collection
        $link      = $baseurl . '?c=' . urlencode($record['collection']) . '&k=' . urlencode($record['access_key']);
        $type      = $lang['sharecollection'];
        $edit_link = sprintf('%spages/collection_share.php?ref=%s&editaccess=%s&editexpiration=%s&editaccesslevel=%s&editgroup=%s&backurl=%s',
            $baseurl_short,
            urlencode($record['collection']),
            urlencode($record['access_key']),
            urlencode($record['expires']),
            urlencode($record['access']),
            urlencode($record['usergroup']),
            urlencode("/pages/team/team_external_shares.php")
        );
        }
        ?>


    <tr id="access_key_<?php echo $record['access_key']; ?>">
        <td>
            <div class="ListTitle">
                <a href="<?php echo $link; ?>" target="_blank"><?php echo htmlspecialchars($record['access_key']); ?></a>
            </div>
        </td>
        <td><?php echo htmlspecialchars($type); ?></td>
        <td><?php echo htmlspecialchars(resolve_users($record['users'])); ?></td>
        <td><?php echo htmlspecialchars($record['emails']); ?></td>
        <td><?php echo htmlspecialchars(nicedate($record['maxdate'], true, true, true)); ?></td>
        <td><?php echo htmlspecialchars(nicedate($record['lastused'], true, true, true)); ?></td>
        <td><?php echo htmlspecialchars(('' == $record['expires']) ? $lang['never'] : nicedate($record['expires'], false)); ?></td>
        <td><?php echo htmlspecialchars((-1 == $record['access']) ? '' : $lang['access' . $record['access']]); ?></td>
        <td>
            <div class="ListTools">
                <a href="#" onClick="delete_access_key('<?php echo $record['access_key']; ?>', '<?php echo $record['resource']; ?>', '<?php echo $record['collection']; ?>');"><?php echo LINK_CARET ?><?php echo $lang['action-delete']; ?></a>
                <a href="<?php echo $edit_link; ?>"><?php echo LINK_CARET ?><?php echo $lang['action-edit']; ?></a>
            </div>
        </td>
    </tr>
    <?php

    return;
    }

# The functions is_field_displayed, display_multilingual_text_field and display_field below moved from edit.php
function is_field_displayed($field)
    {
    global $ref, $resource, $upload_review_mode;

    # Conditions under which the field is not displayed
    return !(
        # Field is an archive only field
        (isset($resource["archive"]) && $resource["archive"]==0 && $field["resource_type"]==999)
        # Field does not have individual write access allowed; and does not have edit access allowed on upload
        || (checkperm("F*") && !checkperm("F-" . $field["ref"]) && !($ref < 0 && checkperm("P" . $field["ref"])))
        # Field has write access denied directly
        || checkperm("F" . $field["ref"])
        # Field is hidden on upload
        || (($ref < 0 || $upload_review_mode) && $field["hide_when_uploading"])
        # Other field conditions
        || hook('edithidefield', '', array('field' => $field))
        || hook('edithidefield2', '', array('field' => $field)));
    }

# Allows language alternatives to be entered for free text metadata fields.
function display_multilingual_text_field($n, $field, $translations)
  {
  global $language, $languages, $lang;
  ?>
  <p><a href="#" class="OptionToggle" onClick="l=document.getElementById('LanguageEntry_<?php echo $n?>');if (l.style.display=='block') {l.style.display='none';this.innerHTML='<?php echo $lang["showtranslations"]?>';} else {l.style.display='block';this.innerHTML='<?php echo $lang["hidetranslations"]?>';} return false;"><?php echo $lang["showtranslations"]?></a></p>
  <table class="OptionTable" style="display:none;" id="LanguageEntry_<?php echo $n?>">
     <?php
     reset($languages);
     foreach ($languages as $langkey => $langname)
     {
       if ($language!=$langkey)
       {
         if (array_key_exists($langkey,$translations)) {$transval=$translations[$langkey];} else {$transval="";}
         ?>
         <tr>
            <td nowrap valign="top"><?php echo htmlspecialchars($langname)?>&nbsp;&nbsp;</td>

            <?php
            if ($field["type"]==0)
            {
              ?>
              <td><input type="text" class="stdwidth" name="multilingual_<?php echo $n?>_<?php echo $langkey?>" value="<?php echo htmlspecialchars($transval)?>"></td>
              <?php
           }
           else
           {
              ?>
              <td><textarea rows=6 cols=50 name="multilingual_<?php echo $n?>_<?php echo $langkey?>"><?php echo htmlspecialchars($transval)?></textarea></td>
              <?php
           }
           ?>
        </tr>
        <?php
     }
  }
  ?></table><?php
  }

function display_field($n, $field, $newtab=false,$modal=false)
  {
  global $use, $ref, $original_fields, $multilingual_text_fields, $multiple, $lastrt,$is_template, $language, $lang,
  $blank_edit_template, $edit_autosave, $errors, $tabs_on_edit, $collapsible_sections, $ctrls_to_save,
  $embedded_data_user_select, $embedded_data_user_select_fields, $show_error, $save_errors, $baseurl, $is_search,
  $all_selected_nodes,$original_nodes, $FIXED_LIST_FIELD_TYPES, $TEXT_FIELD_TYPES, $upload_review_mode, $check_edit_checksums,
  $upload_review_lock_metadata, $locked_fields, $lastedited, $copyfrom, $fields;

  debug_function_call("display_field", func_get_args());

  // Set $is_search to false in case page request is not an ajax load and $is_search hs been set from the searchbar
  $is_search=false;
  
  if(!isset($locked_fields))
    {
    $locked_fields = explode(",",getval("lockedfields",""));
    }

    if(!isset($copyfrom))
        {
        $copyfrom = getval('copyfrom', '');
        }

  $name="field_" . $field["ref"];
  $value=$field["value"];
  $value=trim($value);
  $use_copyfrom=true;
    if ($use != $ref && ($field["omit_when_copying"]))
        {
        debug("display_field: reverting copied value for field " . $field["ref"] . " as omit_when_copying is enabled");
        # Return this field value back to the original value, instead of using the value from the copied resource/metadata template
        # This is triggered if field has the 'omit_when_copying' flag set
        reset($original_fields);
        $use_copyfrom=false;
        foreach ($original_fields as $original_field)
            {
            if ($original_field["ref"]==$field["ref"])
                {
                $value=$original_field["value"];
                }
            }
        $selected_nodes = $original_nodes;
        }
    elseif(($ref<0 || $upload_review_mode) && isset($locked_fields) && in_array($field["ref"], $locked_fields) && $lastedited > 0)
        {
        // Get value from last edited resource 
        debug("display_field: locked field " . $field['ref'] . ". Using nodes from last resource edited - " . $lastedited);
        $selected_nodes = get_resource_nodes($lastedited,$field["ref"]);
        }
    else
        {
        $selected_nodes = $all_selected_nodes;
        }
    
  $displaycondition=true;
  if ($field["display_condition"]!="")
    {
    #Check if field has a display condition set and render the client side check display condition functions
    $displaycondition = check_display_condition($n, $field, $fields, true);
    }

  if ($multilingual_text_fields)
    {
    # Multilingual text fields - find all translations and display the translation for the current language.
    $translations=i18n_get_translations($value);
    if (array_key_exists($language,$translations)) {$value=$translations[$language];} else {$value="";}
    }

  if ($multiple && ((getval("copyfrom","") == "" && getval('metadatatemplate', '') == "") || str_replace(array(" ",","),"",$value)=="")) {$value="";} # Blank the value for multi-edits  unless copying data from resource.

  if ($field["resource_type"]!=$lastrt && $lastrt!=-1 && $collapsible_sections)
      {
      ?></div><h2 class="CollapsibleSectionHead" id="resource_type_properties"><?php echo htmlspecialchars(get_resource_type_name($field["resource_type"]))?> <?php echo $lang["properties"]?></h2><div class="CollapsibleSection" id="ResourceProperties<?php if ($ref==-1) echo "Upload"; ?><?php echo $field["resource_type"]; ?>Section"><?php
      }
    $lastrt=$field["resource_type"];

    # Blank form if 'reset form' has been clicked
    if('' != getval('resetform', ''))
        {
        $value = '';

        if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
            {
            $selected_nodes = array();
            }
        $user_set_values = array();
        }
    // Copy from resource should only show values from the resource we are copying from
    else if($ref != $use && $copyfrom != '')
        {
        $user_set_values = array();
        }
    else
        {
        debug("display_field: getting all user selected values from form data for field " . $field['ref']);
        $user_set_values = getval('nodes', array());
        }

    /****************************** Errors on saving ***************************************/
    $field_save_error = FALSE;
    if (isset($show_error) && isset($save_errors))
      {
      if(array_key_exists($field['ref'], $save_errors))
        {
        $field_save_error = TRUE;
        }
      }
     
    if ($multiple && !hook("replace_edit_all_checkbox","",array($field["ref"])))
      {
      # Multiple items, a toggle checkbox appears which activates the question
      ?>
      <div class="Question edit_multi_checkbox">
        <input name="editthis_<?php echo htmlspecialchars($name) ?>"
               id="editthis_<?php echo $n?>"
               type="checkbox"
               value="yes"
               <?php if($field_save_error){?> checked<?php } ?>
               onClick="batch_edit_toggle_edit_multi_checkbox_question(<?php echo (int) $n; ?>);" <?php if(getval("copyfrom","")!="" && $use_copyfrom && $value!=""){echo " checked" ;} ?>>&nbsp;
            <label for="editthis<?php echo $n?>"><?php echo htmlspecialchars($field["title"]) ?></label>
            <div class="clearerleft"></div>
        </div>
        <!-- End of edit_multi_checkbox -->
      <?php
      }

  if ($multiple && !hook("replace_edit_all_mode_select","",array($field["ref"])))
      {
      # When editing multiple, give option to select Replace All Text or Find and Replace
      $onchangejs = "var fr=document.getElementById('findreplace_" . $n . "');\n";
      $onchangejs .= "var q=document.getElementById('question_" . $n . "');\n";
      if ($field["type"] == FIELD_TYPE_CATEGORY_TREE)
        {
        $onchangejs .= "if (this.value=='RM'){branch_limit_field['field_" . $field["ref"] . "']=1;}else{branch_limit_field['field_" . $field["ref"] . "']=0;}";
        }
      elseif (in_array($field["type"], $TEXT_FIELD_TYPES ))
        {
        $onchangejs .= "
        var cf=document.getElementById('copy_from_field_" . $field["ref"] . "');
            if (this.value=='CF')
                {
                cf.style.display='block';q.style.display='none';fr.style.display='none';
                }
            else if (this.value=='FR')
                {
                fr.style.display='block';q.style.display='none';cf.style.display='none';
                }
            else
                {
                fr.style.display='none';cf.style.display='none';q.style.display='block';
                }";
        }
      ?>
      <div class="Question" id="modeselect_<?php echo $n?>" style="<?php if($value=="" && !$field_save_error ){echo "display:none;";} ?>padding-bottom:0;margin-bottom:0;">
      <label for="modeselectinput"><?php echo $lang["editmode"]?></label>
      <select id="modeselectinput_<?php echo $n?>" name="modeselect_<?php echo $field["ref"]?>" class="stdwidth" onChange="<?php echo $onchangejs;hook ("edit_all_mode_js"); ?>">
      <option value="RT"><?php echo $lang["replacealltext"]?></option>
      <?php
      if (in_array($field["type"], $TEXT_FIELD_TYPES ))
        {
        # 'Find and replace', prepend and 'copy from field' options apply to text boxes only.
        ?>
        <option value="FR"<?php if(getval("modeselect_" . $field["ref"],"")=="FR"){?> selected<?php } ?>><?php echo $lang["findandreplace"]?></option>
        <option value="CF"<?php if(getval("modeselect_" . $field["ref"],"")=="CF"){?> selected<?php } ?>><?php echo $lang["edit_copy_from_field"]?></option>
        <option value="PP"<?php if(getval("modeselect_" . $field["ref"],"")=="PP"){?> selected<?php } ?>><?php echo $lang["prependtext"]?></option>
        <?php
        }
      if(in_array($field['type'], array_merge($TEXT_FIELD_TYPES, array(FIELD_TYPE_CHECK_BOX_LIST, FIELD_TYPE_CATEGORY_TREE, FIELD_TYPE_DYNAMIC_KEYWORDS_LIST))))
        {
        # Append applies to text boxes, checkboxes ,category tree and dynamic keyword fields only.
        ?>
        <option value="AP"<?php if(getval("modeselect_" . $field["ref"],"")=="AP"){?> selected<?php } ?>><?php echo $lang["appendtext"]?></option>
        <?php
        }
      if(in_array($field['type'], array_merge($TEXT_FIELD_TYPES, array(FIELD_TYPE_CHECK_BOX_LIST, FIELD_TYPE_DROP_DOWN_LIST, FIELD_TYPE_CATEGORY_TREE, FIELD_TYPE_DYNAMIC_KEYWORDS_LIST))))
        {
        # Remove applies to text boxes, checkboxes, dropdowns, category trees and dynamic keywords only. 
        ?> 
        <option value="RM"<?php if(getval("modeselect_" . $field["ref"],"")=="RM"){?> selected<?php } ?>><?php echo $lang["removetext"]?></option>
        <?php
        }
        hook ("edit_all_extra_modes");
        ?>
        </select>
      </div><!-- End of modeselect_<?php echo $n?> -->

      <?php
      if (in_array($field["type"], $TEXT_FIELD_TYPES))
        {
        render_field_selector_question("","copy_from_field_" . $field["ref"], array(), "stdwidth", true);
        }
        ?>

      <div class="Question" id="findreplace_<?php echo $n?>" style="display:none;border-top:none;">
        <label>&nbsp;</label>
        <?php echo $lang["find"]?> <input type="text" name="find_<?php echo $field["ref"]?>" class="shrtwidth">
        <?php echo $lang["andreplacewith"]?> <input type="text" name="replace_<?php echo $field["ref"]?>" class="shrtwidth">
      </div><!-- End of findreplace_<?php echo $n?> -->

      <?php hook ("edit_all_after_findreplace","",array($field,$n)); 
      }
      ?>

      <div class="Question <?php if($upload_review_mode && in_array($field["ref"],$locked_fields)){echo " lockedQuestion ";} if($field_save_error) { echo 'FieldSaveError'; } ?>" id="question_<?php echo $n?>" <?php
      if (($multiple && !$field_save_error) || !$displaycondition || $newtab)
        {?>style="border-top:none;<?php 
        if (($multiple && $value=="") || !$displaycondition) # Hide this
        {
        ?>
        display:none;
        <?php
        }
        ?>"<?php
        }
     ?>>
     <?php 
     $labelname = $name;

     // For batch editing, CKEditor renders as a text box, as it does not work at all well when appending / prepending (it expects to work with HTML only)
     if ($field['type'] == 8 && $multiple)
        {
        $field['type']=1;
        }
      
     // Add _selector to label so it will keep working:
     if($field['type'] == 9)
      {
      $labelname .= '_selector';
      }

      // Add -d to label so it will keep working
     if($field['type'] == 4)
        {
        $labelname .= '-d';
        }
        ?>
     <label for="<?php echo htmlspecialchars($labelname)?>" >
     <?php 
     if (!$multiple) 
        {
        echo htmlspecialchars($field["title"]);
        if (!$is_template && $field["required"]==1)
            {
            echo "<sup>*</sup>";
            }
        } 
     if ($upload_review_mode && $upload_review_lock_metadata)
        {
        renderLockButton($field["ref"], $locked_fields);
        }
        ?>
     </label>

     <?php
    # Autosave display
     if ($edit_autosave || $ctrls_to_save)
      {
      ?>
      <div class="AutoSaveStatus">
      <span id="AutoSaveStatus<?php echo $field["ref"] ?>" style="display:none;"></span>
      </div>
      <?php
      } 
    # Define some Javascript for help actions (applies to all fields)
    # Help actions for CKEditor fields are set in pages/edit_fields/8.php
     if (trim($field["help_text"]=="")) 
       {
        # No helptext; so no javascript for toggling
        $help_js="";
       }
     else
       {
       if ( in_array($field["type"],array(2,3,4,6,7,10,12,14)) )
         {
         # For the selected field types the helptext is always shown; so no javascript toggling 
         $help_js="";
         }
       else
         {
         # For all other field types setup javascript to toggle helptext depending on loss or gain of focus
         $help_js="onBlur=\"HideHelp(" . $field["ref"] . ");return false;\" onFocus=\"ShowHelp(" . $field["ref"] . ");return false;\"";
         }
       }

    #hook to modify field type in special case. Returning zero (to get a standard text box) doesn't work, so return 1 for type 0, 2 for type 1, etc.
     $modified_field_type="";
     $modified_field_type=(hook("modifyfieldtype"));
     if ($modified_field_type){$field["type"]=$modified_field_type-1;}

     hook("addfieldextras");
    # ----------------------------  Show field -----------------------------------
    $type = $field['type'];

    // Default to text type.
    if('' == $type)
        {
        $type = 0;
        }

    if(!hook('replacefield', '', array($field['type'], $field['ref'], $n)))
        {
        global $auto_order_checkbox, $auto_order_checkbox_case_insensitive, $FIXED_LIST_FIELD_TYPES, $is_search;

        if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
            {
            $name = "nodes[{$field['ref']}]";

            // Sometimes we need to pass multiple options
            if(in_array($field['type'], array(FIELD_TYPE_CHECK_BOX_LIST, FIELD_TYPE_CATEGORY_TREE)))
                {
                $name = "nodes[{$field['ref']}][]";
                }
            else if(FIELD_TYPE_DYNAMIC_KEYWORDS_LIST == $field['type'])
                {
                $name = "field_{$field['ref']}";
                }
			
			$field_nodes = array();
			foreach($selected_nodes as $selected_node)
				{
                $node_data = array();
                if(get_node($selected_node, $node_data) && $node_data["resource_type_field"] != $field["ref"])
                    {
                    continue;
                    }

                $field_nodes[] = $selected_node;
				natsort($field_nodes);
                unset($node_data);
				}

			if(!$multiple && !$blank_edit_template && getval("copyfrom","") == "" && getval('metadatatemplate', '') == "" && $check_edit_checksums)
				{
				echo "<input id='field_" . $field['ref']  . "_checksum' name='" . "field_" . $field['ref']  . "_checksum' type='hidden' value='" . md5(implode(",",$field_nodes)) . "'>";
				echo "<input name='" . "field_" . $field['ref']  . "_currentval' type='hidden' value='" . implode(",",$field_nodes) . "'>";
				}
            }
        elseif($field['type']==FIELD_TYPE_DATE_RANGE && !$blank_edit_template && getval("copyfrom","") == "" && getval('metadatatemplate', '') == "" && $check_edit_checksums)
			{
            $field['nodes'] = get_nodes($field['ref'], NULL, FALSE);
            $field_nodes = array();
			foreach($selected_nodes as $selected_node)
				{
				if(in_array($selected_node,array_column($field['nodes'],"ref")))
					{
					$field_nodes[] = $selected_node;
					}
				}
			natsort($field_nodes);
			
			echo "<input id='field_" . $field['ref']  . "_checksum' name='" . "field_" . $field['ref']  . "_checksum' type='hidden' value='" . md5(implode(",",$field_nodes)) . "'>";
			}
		elseif(!$multiple && !$blank_edit_template && getval("copyfrom","")=="" && getval('metadatatemplate', '') == "" && $check_edit_checksums)
			{
			echo "<input id='field_" . $field['ref']  . "_checksum' name='" . "field_" . $field['ref']  . "_checksum' type='hidden' value='" . md5(trim(preg_replace('/\s\s+/', ' ', $field['value']))) . "'>";
			}

        $is_search = false;

        include "edit_fields/{$type}.php";
        }
		
    # ----------------------------------------------------------------------------

    # Display any error messages from previous save
    if (array_key_exists($field["ref"],$errors))
      {
       ?>
       <div class="FormError">!! <?php echo $errors[$field["ref"]]?> !!</div>
       <?php
      }

    if (trim($field["help_text"]!=""))
     {
        # Show inline help for this field.
        # For certain field types that have no obvious focus, the help always appears
       ?>
       <div class="FormHelp" style="padding:0;<?php if ( in_array($field["type"],array(2,3,4,6,7,10,12,14)) ) { ?> clear:left;<?php } else { ?> display:none;<?php } ?>" id="help_<?php echo $field["ref"]?>"><div class="FormHelpInner"><?php echo nl2br(trim(i18n_get_translated($field["help_text"])))?></div></div>
       <?php
     }

    # If enabled, include code to produce extra fields to allow multilingual free text to be entered.
    if ($multilingual_text_fields && ($field["type"]==0 || $field["type"]==1 || $field["type"]==5))
      {
       display_multilingual_text_field($n, $field, $translations);
      }
    
    if(($embedded_data_user_select || (isset($embedded_data_user_select_fields) && in_array($field["ref"],$embedded_data_user_select_fields))) && ($ref<0 && !$multiple))
    {
      ?>
      <table id="exif_<?php echo $field["ref"] ?>" class="ExifOptions" cellpadding="3" cellspacing="3" <?php if ($embedded_data_user_select){?> style="display: none;" <?php } ?>>                    
         <tbody>
           <tr>        
             <td>
                <?php echo "&nbsp;&nbsp;" . $lang["embeddedvalue"] . ": " ?>
             </td>
             <td width="10" valign="middle">
               <input type="radio" id="exif_extract_<?php echo $field["ref"] ?>" name="exif_option_<?php echo $field["ref"] ?>" value="yes" checked>
            </td>
            <td align="left" valign="middle">
               <label class="customFieldLabel" for="exif_extract_<?php echo $field["ref"] ?>"><?php echo $lang["embedded_metadata_extract_option"] ?></label>
            </td>


            <td width="10" valign="middle">
               <input type="radio" id="no_exif_<?php echo $field["ref"] ?>" name="exif_option_<?php echo $field["ref"] ?>" value="no">
            </td>
            <td align="left" valign="middle">
               <label class="customFieldLabel" for="no_exif_<?php echo $field["ref"] ?>"><?php echo $lang["embedded_metadata_donot_extract_option"] ?></label>
            </td>


            <td width="10" valign="middle">
               <input type="radio" id="exif_append_<?php echo $field["ref"] ?>" name="exif_option_<?php echo $field["ref"] ?>" value="append">
            </td>
            <td align="left" valign="middle">
               <label class="customFieldLabel" for="exif_append_<?php echo $field["ref"] ?>"><?php echo $lang["embedded_metadata_append_option"] ?></label>
            </td>


            <td width="10" valign="middle">
               <input type="radio" id="exif_prepend_<?php echo $field["ref"] ?>" name="exif_option_<?php echo $field["ref"] ?>" value="prepend">
            </td>
            <td align="left" valign="middle">
               <label class="customFieldLabel" for="exif_prepend_<?php echo $field["ref"] ?>"><?php echo $lang["embedded_metadata_prepend_option"] ?></label>
            </td>

         </tr>
      </tbody>
   </table>        
   <?php
  }
  ?>
  <div class="clearerleft"> </div>
  </div><!-- end of question_<?php echo $n?> div -->
  <?php     
  
  hook('afterfielddisplay', '', array($n, $field));
  }

	
function render_date_range_field($name,$value,$forsearch=true,$autoupdate=false,$field=array(),$reset="")
	{
	$found_year='';$found_month='';$found_day='';$found_start_year='';$found_start_month='';$found_start_day='';$found_end_year='';$found_end_month='';$found_end_day=''; 
	global $daterange_edtf_support,$lang, $minyear,$date_d_m_y, $chosen_dropdowns, $edit_autosave,$forsearchbar, $maxyear_extends_current;
	if($forsearch)
		{
		// Get the start/end date from the string
		$startvalue=strpos($value,"start")!==false?substr($value,strpos($value,"start")+5,10):"";
		$endvalue=strpos($value,"end")!==false?substr($value,strpos($value,"end")+3,10):"";
		}
	else
		{
		if($value!="" && strpos($value,",")!==false)
			{
			// Extract the start date from the value obtained from get_resource_field_data
			$rangevalues = explode(",",$value);
			$startvalue = $rangevalues[0];
			$endvalue = $rangevalues[1];
			}
		elseif(strlen($value)==10 && strpos($value,"-") !==  false)
			{
			$startvalue = $value;
			$endvalue = "";
			}
		else
			{
			$startvalue = "";
			$endvalue = "";
			}
		}
				
	$ss=explode("-",$startvalue);
	if (count($ss)>=3)
		{
		$found_start_year=$ss[0];
		$found_start_month=$ss[1];
		$found_start_day=$ss[2];
		}
	$se=explode("-",$endvalue);
	if (count($se)>=3)
		{
		$found_end_year=$se[0];
		$found_end_month=$se[1];
		$found_end_day=$se[2];
		}
        
    // If the form has been submitted (but not reset) but data was not saved get the submitted values   
    if($reset == "") 
        {
        foreach(array("start_year", "start_month","start_day","end_year","end_month","end_day") as $subpart)
            {
            if(getval($name . "_" . $subpart,"") != "")
                {
                ${"found_" . $subpart} = getval($name . "_" . $subpart,"");
                }
            }
        }
	
	if($daterange_edtf_support)
		{
		// Use EDTF format for date input
		?>		
		<input class="<?php echo $forsearch?"SearchWidth":"stdwidth"; ?>"  name="<?php echo $name?>_edtf" id="<?php echo $name?>_edtf" type="text" value="<?php echo ($startvalue!=""|$endvalue!="")?$startvalue . "/" . $endvalue:""; ?>" style="display:none;" disabled <?php if ($forsearch && $autoupdate) { ?>onChange="UpdateResultCount();"<?php } if($forsearch && !$forsearchbar){ ?> onKeyPress="if (!(updating)) {setTimeout('UpdateResultCount()',2000);updating=true;}"<?php } else if (!$forsearch  && $edit_autosave){?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>>
		<?php
		}?>
    <!--  date range search start -->   		
    <!--- start date -->	
    <div class="stdwidth indent <?php echo $name?>_range" id="<?php echo $name?>_from">
    <label class="InnerLabel"><?php echo $lang["fromdate"]?></label>
    
        <?php 		
        if($date_d_m_y)
            {  
            ?>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_start_day"><?php echo $lang["day"]; ?></label>
            <select name="<?php echo $name?>_start_day"
             <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeDay"<?php }
            if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
            else if (!$forsearch  && $edit_autosave)
            {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
              >
              <option value=""><?php echo $forsearch?$lang["anyday"]:$lang["day"]; ?></option>
              <?php
              for ($d=1;$d<=31;$d++)
                {
                $m=str_pad($d,2,"0",STR_PAD_LEFT);
                ?><option <?php if ($d==$found_start_day) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
                }
              ?>
            </select>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_start_month"><?php echo $lang["month"]; ?></label>
            <select name="<?php echo $name?>_start_month"
                <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeMonth"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >
                <option value=""><?php echo $forsearch?$lang["anymonth"]:$lang["month"]; ?></option>
                <?php
                for ($d=1;$d<=12;$d++)
                    {
                    $m=str_pad($d,2,"0",STR_PAD_LEFT);
                    ?><option <?php if ($d==$found_start_month) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$d-1]?></option><?php
                    }?>
            </select>
            <?php
            }
        else
            { 
            ?>		
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_start_month"><?php echo $lang["month"]; ?></label>
            <select name="<?php echo $name?>_start_month"
                <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeMonth"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >					
                <option value=""><?php echo $forsearch?$lang["anymonth"]:$lang["month"]; ?></option>
                <?php
                for ($d=1;$d<=12;$d++)
                    {
                    $m=str_pad($d,2,"0",STR_PAD_LEFT);
                    ?><option <?php if ($d==$found_start_month) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$d-1]?></option><?php
                    }?>
            </select>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_start_day"><?php echo $lang["day"]; ?></label>
            <select name="<?php echo $name?>_start_day"
              <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeDay"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >
              <option value=""><?php echo $forsearch?$lang["anyday"]:$lang["day"]; ?></option>
              <?php
              for ($d=1;$d<=31;$d++)
                {
                $m=str_pad($d,2,"0",STR_PAD_LEFT);
                ?><option <?php if ($d==$found_start_day) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
                }
              ?>
            </select>
            <?php			
            }
        if($forsearch)
            {?>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_year"><?php echo $lang["year"]; ?></label>
            <select name="<?php echo htmlspecialchars($name) ?>_start_year"
                <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeYear"<?php } 
                if ($forsearch && $autoupdate) 
                        { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                >
                <option value=""><?php echo $forsearch?$lang["anyyear"]:$lang["year"]; ?></option>
                <?php
                $y=date("Y");
                $y += $maxyear_extends_current;
                for ($d=$y;$d>=$minyear;$d--)
                    {
                    ?><option <?php if ($d==$found_start_year) { ?>selected<?php } ?>><?php echo $d?></option><?php
                    }?>
            </select>
            <?php
            }
        else
            {?>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_year"><?php echo $lang["year"]; ?></label>
            <input size="5" name="<?php echo htmlspecialchars($name) ?>_start_year" id="<?php echo htmlspecialchars($name) ?>_start_year" type="text" value="<?php echo $found_start_year ?>"
                <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeYear"<?php }
                if ($forsearch && $autoupdate)
                    { ?>onChange="UpdateResultCount();"<?php }
                if($forsearch && !$forsearchbar)
                    { ?> onKeyPress="if (!(updating)) {setTimeout('UpdateResultCount()',2000);updating=true;}"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>>
		    <?php
            }?>
    </div>
    
    <div class="clearerleft"> </div>
    
    <!--- to date -->
    <label></label>
    
    
    
    <div class="stdwidth indent <?php echo $name?>_range" id="<?php echo $name?>_to" >
    <label class="InnerLabel"><?php echo $lang["todate"]?></label>
    <?php 		
        if($date_d_m_y)
            {
            ?>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_day"><?php echo $lang["day"]; ?></label>
            <select name="<?php echo $name?>_end_day"
              <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeDay"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >
                <option value=""><?php echo $forsearch?$lang["anyday"]:$lang["day"]; ?></option>
                <?php
                for ($d=1;$d<=31;$d++)
                    {
                    $m=str_pad($d,2,"0",STR_PAD_LEFT);
                    ?><option <?php if ($d==$found_end_day) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
                    }?>
            </select>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_month"><?php echo $lang["month"]; ?></label>
            <select name="<?php echo $name?>_end_month"
                <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeMonth"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >
                <option value=""><?php echo $forsearch?$lang["anymonth"]:$lang["month"]; ?></option>
                <?php
                for ($d=1;$d<=12;$d++)
                    {
                    $m=str_pad($d,2,"0",STR_PAD_LEFT);
                    ?><option <?php if ($d==$found_end_month) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$d-1]?></option><?php
                    }?>
            </select>
            <?php
            }
        else
            {
            ?>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_month"><?php echo $lang["month"]; ?></label>
            <select name="<?php echo $name?>_end_month"
                <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeMonth"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >					
                <option value=""><?php echo $forsearch?$lang["anymonth"]:$lang["month"]; ?></option>
                <?php
                for ($d=1;$d<=12;$d++)
                    {
                    $m=str_pad($d,2,"0",STR_PAD_LEFT);
                    ?><option <?php if ($d==$found_end_month) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $lang["months"][$d-1]?></option><?php
                    }?>
            </select>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_day"><?php echo $lang["day"]; ?></label>
            <select name="<?php echo $name?>_end_day"
              <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeDay"<?php }
                if ($forsearch && $autoupdate) 
                    { ?>onChange="UpdateResultCount();"<?php }
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >
              <option value=""><?php echo $forsearch?$lang["anyday"]:$lang["day"]; ?></option>
              <?php
              for ($d=1;$d<=31;$d++)
                {
                $m=str_pad($d,2,"0",STR_PAD_LEFT);
                ?><option <?php if ($d==$found_end_day) { ?>selected<?php } ?> value="<?php echo $m?>"><?php echo $m?></option><?php
                }
              ?>
            </select>
            <?php			
            }
        if($forsearch)
            {?>
            <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_year"><?php echo $lang["year"]; ?></label>
            <select name="<?php echo $name?>_end_year" 
            <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeYear"<?php }
            if ($forsearch && $autoupdate) { ?>onChange="UpdateResultCount();"<?php } 
                else if (!$forsearch  && $edit_autosave)
                    {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
                    >
              <option value=""><?php echo $forsearch?$lang["anyyear"]:$lang["year"]?></option>
              <?php
              $y=date("Y");
              $y += $maxyear_extends_current;
              for ($d=$y;$d>=$minyear;$d--)
                {
                ?><option <?php if ($d==$found_end_year ) { ?>selected<?php } ?>><?php echo $d?></option><?php
                }
              ?>
            </select>
             <?php
                }
            else
                {?>
                <label class="accessibility-hidden" for="<?php echo htmlspecialchars($name) ?>_end_year"><?php echo $lang["year"]; ?></label>
                <input size="5" name="<?php echo htmlspecialchars($name) ?>_end_year" id="<?php echo htmlspecialchars($name) ?>_end_year" type="text" value="<?php echo $found_end_year ?>"
                    <?php if ($chosen_dropdowns) {?>class="ChosenDateRangeYear"<?php }
                    
                    if ($forsearch && $autoupdate)
                        { ?>onChange="UpdateResultCount();"<?php }
                    if($forsearch && !$forsearchbar)
                        { ?> onKeyPress="if (!(updating)) {setTimeout('UpdateResultCount()',2000);updating=true;}"<?php }
                    else if (!$forsearch  && $edit_autosave)
                        {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>>
                <?php
                }?>
    <!--  date range search end date-->         
    </div>
    <div class="clearerleft"></div>
    <?php if($daterange_edtf_support)
		{?>
        <a href="#" onclick="if(jQuery('#<?php echo $name ?>_edtf').prop('disabled')){jQuery('#<?php echo $name ?>_edtf').prop('disabled',false);jQuery('#<?php echo $name ?>_edtf').show();jQuery('.<?php echo $name ?>_range').hide();}else{jQuery('#<?php echo $name ?>_edtf').prop('disabled',true);jQuery('#<?php echo $name ?>_edtf').hide();jQuery('.<?php echo $name ?>_range').show();}return false;">
            <i aria-hidden="true" class="fa fa-caret-right"></i>
            <?php echo "EDTF"; ?>
        </a>
        <?php
        }
	}

/**
* Renders a full breadcrumbs trail.
* 
* @param array  $links     List of link "objects" that create the trail
* @param string $pre_links Pre-rendered links in HTML form
* @param string $class     Extra classes for the main container div
* 
* @return void
*/
function renderBreadcrumbs(array $links, $pre_links = '', $class = '')
    {
    global $lang;
    /*
    NOTE: implemented as seen on themes and search. There is a lot of room for improvement UI wise

    TODO: search_title_processing.php is using it intensively and at the moment there are differences in terms of 
    rendered HTML between themes/ search and search_title_processing.php. We should refactor all places were breadcrumbs
    are being created and make sure they all use this function (or any future related functions - like generateBreadcrumb() ).
    */

    if(0 === count($links))
        {
        return;
        }
    ?>
    <div class="BreadcrumbsBox <?php echo $class; ?>">
        <div class="SearchBreadcrumbs">
        <?php
        if('' !== $pre_links && $pre_links !== strip_tags($pre_links))
            {
            echo $pre_links . '&nbsp;' . LINK_CHEVRON_RIGHT;
            }

        for($i = 0; $i < count($links); $i++)
            {
            $anchor = isset($links[$i]['href']);
            $anchor_attrs = (isset($links[$i]["attrs"]) && is_array($links[$i]["attrs"]) && !empty($links[$i]["attrs"]) ? $links[$i]["attrs"] : array());
            $anchor_attrs = join(" ", $anchor_attrs);

            // search_title_processing.php is building spans with different class names. We need to allow HTML in link titles.
            $title = get_inner_html_from_tag(strip_tags_and_attributes($links[$i]['title']), "p");

            // remove leading * used for featured collection sorting.
            $title = strip_prefix_chars($title,"*");

            if(0 < $i)
                {
                echo LINK_CHEVRON_RIGHT;
                }
                
            if ($anchor)
                { ?><a href="<?php echo htmlspecialchars($links[$i]['href']); ?>" onclick="return CentralSpaceLoad(this, true);"<?php echo $anchor_attrs; ?>><?php } ?><span><?php echo $title; ?></span><?php if ($anchor) { ?></a><?php }
            if (isset($links[$i]['help']))
                {
                render_help_link($links[$i]['help']);
                }
            }
            ?>
        </div>
    </div>
    <?php

    return;
    }


/**
* Render a blank tile used for call to actions (e.g: on featured collections, a tile for creating new collections)
* 
* @param string $url URL
* @param array  $ctx Rendering options determined by the outside context
* 
* @return void
*/
function render_new_featured_collection_cta(string $url, array $ctx)
    {
    global $collection_allow_creation;

    if('' === $url)
        {
        return;
        }

    $full_width = (isset($ctx["full_width"]) && $ctx["full_width"]);
    $centralspaceload = (isset($ctx["centralspaceload"]) && $ctx["centralspaceload"]);
    $html_h2_span_class = (isset($ctx["html_h2_span_class"]) && trim($ctx["html_h2_span_class"]) != "" ? trim($ctx["html_h2_span_class"]) : "fas fa-plus-circle");

    $html_tile_class = array("FeaturedSimplePanel", "HomePanel", "DashTile", "FeaturedSimpleTile", "FeaturedCallToActionTile");
    $html_contents_h2_class = array();

    if($full_width)
        {
        $html_tile_class[] = "FullWidth";
        $html_contents_h2_class[] = "MarginZeroAuto";
        }

    $onclick_fn = ($centralspaceload ? "CentralSpaceLoad(this, true);" : "ModalLoad(this, true, true);");
    ?>
    <div id="FeaturedSimpleTile" class="<?php echo implode(" ", $html_tile_class); ?>">
        <a href="<?php echo $url; ?>" onclick="return <?php echo $onclick_fn; ?>">
            <div class="FeaturedSimpleTileContents">
                <div class="FeaturedSimpleTileText">
                    <h2 class="<?php echo implode(" ", $html_contents_h2_class); ?>"><span class="<?php echo $html_h2_span_class; ?>"></span></h2>
                </div>
            </div>
        </a>
    </div>
    <?php
    return;
    }

/**
* Renders social media links in order to share a particular link
* 
* @param string $url The URL to be shared on social media networks
* 
* @return void
*/
function renderSocialMediaShareLinksForUrl($url)
    {
    global $social_media_links;

    $url_encoded = urlencode($url);

    if(in_array("facebook", $social_media_links))
        {
        ?>
        <!-- Facebook -->
        <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url_encoded; ?>"><i class="fa fa-lg fa-facebook-official" aria-hidden="true"></i></a>
        <?php
        }

    if (in_array("twitter", $social_media_links))
        {
        ?>
        <!-- Twitter -->
        <a target="_blank" href="https://twitter.com/?status=<?php echo $url_encoded; ?>"><i class="fa fa-lg fa-twitter-square" aria-hidden="true"></i></a>
        <?php
        }

    if (in_array("linkedin", $social_media_links))
        {
        ?>
        <!-- LinkedIn -->
        <a target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $url_encoded; ?>"><i class="fa fa-lg fa-linkedin-square" aria-hidden="true"></i></a>
        <?php
        }

    return;
    }
    
/**
* Renders a lock button for a field - used to 'lock' metadata in upload_review_mode
* 
* @param string $name  The field identifier e.g. 'resource_type', '18'
* @param array $locked_fields - Array of locked field identifiers
* 
* @return void
*/
function renderLockButton($name, $locked_fields=array())
    {
    global $lang;
    ?>
    <button type="submit" class="lock_icon" id="lock_icon_<?php echo htmlspecialchars($name) ; ?>" onClick="toggleFieldLock('<?php echo htmlspecialchars($name) ; ?>');return false;" title="<?php echo $lang['lock-tooltip']; ?>">
        <i aria-hidden="true" class="fa <?php if(in_array($name,$locked_fields)){echo "fa-lock";} else {echo "fa-unlock";} ?> fa-fw"></i>
    </button>
    <?php    
    }

/**
* Renders an image, with width and heigth specified for centering in div
* 
* @param array $imagedata  An array of resource data - usually from search results
* @param string $img_url - URL to image file
* @param string $display -size to use - from search results
* 
* @return void
*/
function render_resource_image($imagedata, $img_url, $display="thumbs")
    {
    global $view_title_field;
    
    list($width, $height, $margin) = calculate_image_display($imagedata, $img_url, $display);

    $margin = (is_numeric($margin)) ? $margin . "px" : $margin;

    // Produce a 'softer' colour for the loading preview (extracted colours tend to have a very high saturation)
    if (isset($imagedata["image_red"]) && isset($imagedata["image_green"]) && isset($imagedata["image_green"]))
        {
        $preview_red=100+($imagedata["image_red"]/1000)*156;
        $preview_green=100+($imagedata["image_green"]/1000)*156;
        $preview_blue=100+($imagedata["image_blue"]/1000)*156;
        }
    else
        {
        $preview_red=$preview_green=$preview_blue=255;
        }
    ?>
    <div class="ImageColourWrapper" style="background-color: rgb(<?php echo $preview_red ?>,<?php echo $preview_green ?>,<?php echo $preview_blue ?>);width:<?php echo $width ?>px;height:<?php echo $height ?>px;margin:<?php echo $margin ?> auto 0 auto;"><img
    border="0"
    width="<?php echo $width ?>" 
    height="<?php echo $height ?>"
    src="<?php echo $img_url ?>" 
    alt="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($imagedata["field".$view_title_field]))))); ?>"
    /></div>
    <?php
    }


/**
 * Calculations width, height and margin-top property for resource image to display in ResourcePanel
 * 
 * @param   array   $imagedata
 * @param   string  $img_url
 * @param   string  $display
 * 
 * @return  array   array($width, $height, $margin);
 */


function calculate_image_display($imagedata, $img_url, $display="thumbs")
    {
    if('' != $imagedata['thumb_width'] && 0 != $imagedata['thumb_width'] && '' != $imagedata['thumb_height'])
        {
        $ratio = $imagedata["thumb_width"] / $imagedata["thumb_height"];   
        }
    else
        {
        // use php function getimagesize()
        $size = ($img_url != "") ? getimagesize($img_url) : "";
        $ratio = (isset($size[0]))? $size[0] / $size[1] : 1;
        }
    
    switch($display)
        {
        case "xlthumbs":
            $defaultwidth = 320;
            $defaultheight = 320;
        break;

        case "thumbs":
            $defaultwidth = 175;
            $defaultheight = 175;
        break;        
        
        case "collection":
            $defaultwidth = 75;
            $defaultheight = 75;
        break;

        default:
            $defaultwidth = 75;
            $defaultheight = 75;
        break;        
        }

    if ($ratio > 1)
        {
        $width = $defaultwidth;
        $height = round($defaultheight / $ratio);
        $margin = floor(($defaultheight - $height ) / 2);
        }
    elseif ($ratio < 1)
        {
        # portrait image dimensions
        $height = $defaultheight;
        $width = round($defaultwidth * $ratio);
        $margin = floor(($defaultheight - $height ) / 2);
        }
    else
        {
        # square image or no image dimensions
        $height = $defaultheight;
        $width = $defaultwidth;
        $margin = "auto";
        }

    return array($width, $height, $margin);
    }

/**
 * Render the share options (used on collection_share.php and resource_share.php)
 *
 * @param  array $shareopts     Array of share options. If not set will use the old getval() methods
 *                  "password" bool             Has a password been set for this share? (password will not actually be displayed)
 *                  "editaccesslevel" int       Current access level of share
 *                  "editexpiration" string     Current expiration date
 *                  "editgroup" int             ID of existing share group
 * @return void
 */
function render_share_options($shareopts=array())
    {
    global $lang, $usergroup, $resource_share_expire_never, $resource_share_expire_days,$minaccess,$allowed_external_share_groups;
    $validshareops = array(
        "password",
        "editaccesslevel",
        "editexpiration",
        "editgroup",
        );
    foreach($validshareops as $validshareop)
        {
        if(isset($shareopts[$validshareop]))
            {
            $$validshareop = $shareopts[$validshareop];
            }
        else
            {
            $$validshareop = getval($validshareop,'');
            }
        }
    if(!hook('replaceemailaccessselector'))
        {?>
        <div class="Question" id="question_access">
            <label for="archive"><?php echo $lang["access"] ?></label>
            <select class="stdwidth" name="access" id="access">
            <?php
            # List available access levels. The highest level must be the minimum user access level.
            for ($n=$minaccess;$n<=1;$n++) 
                { 
                $selected = $editaccesslevel == $n;
                ?>
                <option value="<?php echo $n?>" <?php if($selected) echo "selected";?>><?php echo $lang["access" . $n]?></option>
                <?php 
                } 
                ?>
            </select>
            <div class="clearerleft"> </div>
        </div><?php
        } #hook replaceemailaccessselector
    
    if(!hook('replaceemailexpiryselector'))
        {
        ?>
        <div class="Question">
            <label><?php echo $lang["expires"] ?></label>
            <select name="expires" class="stdwidth">
            <?php 
            if($resource_share_expire_never) 
                { ?>
                <option value=""><?php echo $lang["never"]?></option><?php 
                } 
            for ($n=1;$n<=$resource_share_expire_days;$n++)
                {
                $date       = time() + (60*60*24*$n);
                $ymd_date   = date('Y-m-d', $date);
                $selected   = (substr($editexpiration,0,10) == $ymd_date);
                $date_text  = nicedate($ymd_date,false,true);
                $option_class = '';
                $day_date = date('D', $date);
                if (($day_date == "Sun") || ($day_date == "Sat"))
                    {
                    $option_class = 'optionWeekend';
                    }
                ?>
                <option class="<?php echo $option_class ?>" value="<?php echo $ymd_date ?>" <?php if($selected) echo "selected"; ?>><?php echo $date_text ?></option>
                <?php
                } ?>
            </select>
            <div class="clearerleft"> </div>
        </div>
        <?php 
        }
    if (checkperm("x")) 
        {
        # Allow the selection of a user group to inherit permissions from 
        # for this share (the default is to use the current user's user group).
        ?>
        <div class="Question">
            <label for="groupselect"><?php echo $lang["share_using_permissions_from_user_group"]; ?></label>
            <select id="groupselect" name="usergroup" class="stdwidth">
            <?php $grouplist = get_usergroups(true);
            foreach ($grouplist as $group)
                {
                if(!empty($allowed_external_share_groups) && !in_array($group['ref'], $allowed_external_share_groups))
                    {
                    continue;
                    }

                $selected = $editgroup == $group["ref"] || ($editgroup == "" && $usergroup == $group["ref"]);
                ?>
                <option value="<?php echo $group["ref"] ?>" <?php if ($selected) echo "selected" ?>><?php echo $group["name"] ?></option>
                <?php
                }
                ?>
            </select>
            <div class="clearerleft"> </div>
        </div>
        <?php 
        }
    else if(!checkperm("x") && !empty($allowed_external_share_groups) && in_array($usergroup, $allowed_external_share_groups))
        {
        ?>
        <input type="hidden" name="usergroup" value="<?php echo $usergroup; ?>">
        <?php
        }
        render_share_password_question(!$password);
        hook("additionalresourceshare");
        ?>
    <?php        
    }
    
/**
* Renders a metadata field selector
* 
* @param string     $label      label for the field
* @param string     $name       name of form select
* @param array      $ftypes     array of field types to include
* @param string     $class      array CSS class to apply
* @param boolean    $hidden     optionally hide the question usng CSS display:none
* @param array      $current    Current selected value
* 
* @return void
*/
function render_field_selector_question($label, $name, $ftypes,$class="stdwidth",$hidden=false, $current = 0)
    {
    global $lang;
    $fieldtypefilter = "";
	if(count($ftypes)>0)
		{
		$fieldtypefilter = " WHERE type IN ('" . implode("','", $ftypes) . "')";
		}
        
    $fields=sql_query("SELECT * from resource_type_field " .  (($fieldtypefilter=="")?"":$fieldtypefilter) . " ORDER BY title, name", "schema");
    
    echo "<div class='Question' id='" . $name . "'" . ($hidden ? " style='display:none;border-top:none;'" : "") . ">";
    echo "<label for='" . htmlspecialchars($name) . "' >" . htmlspecialchars($label) . "</label>";
    echo "<select name='" . htmlspecialchars($name) . "' id='" . htmlspecialchars($name) . "' class='" . $class . "'>";
    echo "<option value='' selected >" . $lang["select"] . "</option>";
    foreach($fields as $field)
        {
        $selected = ($field["ref"] == $current ? "selected" : "");
        echo "<option value='{$field['ref']}' {$selected}>" . lang_or_i18n_get_translated($field['title'],'fieldtitle-') . "</option>";
        }
    echo "</select>";
    echo "<div class='clearerleft'></div>";
    echo "</div>";
    }


/**
* Render a filter bar button
* 
* @param string $text Button text
* @param string $attr Button attributes
* @param string $icon HTML for icon element (e.g "<i aria-hidden="true" class="fa fa-fw fa-upload"></i>")
* 
* @return void
*/
function render_filter_bar_button($text, $attr, $icon)
    {
    ?>
    <div class="InpageNavLeftBlock">
        <button type="button" <?php echo $attr; ?>><?php echo $icon . htmlspecialchars($text); ?></button>
    </div>
    <?php
    return;
    }


/**
* Render "Upload here" button.
*
* This applies to search results that are either a special search "!collection" and/or consist of purely the following:
* - Nodes
* - Resource type
* - Workflow (archive) state
* 
* For free text searches this SHOULD NOT work!
* 
* @param array   $search_params
* @param boolean $return_params_only Exception to the rule! Rather than render, return the upload here params
* 
* @return void
*/
function render_upload_here_button(array $search_params, $return_params_only = false)
    {
    if(!(checkperm('c') || checkperm('d')))
        {
        return;
        }

    if(!isset($search_params['search']) || !isset($search_params['restypes']) || !isset($search_params['archive']))
        {
        return;
        }

    if(
        isset($search_params['search'])
        && (
            mb_substr($search_params['search'], 0, 11) != '!collection'
            && empty(resolve_nodes_from_string($search_params['search']))
        )
    )
        {
        return;
        }

    $upload_here_params = array();

    $upload_endpoint = 'pages/upload_plupload.php';
    if(!$GLOBALS['upload_then_edit'])
        {
        $upload_endpoint = 'pages/edit.php';
        $upload_here_params['ref'] = 0 - $GLOBALS['userref'];
        $upload_here_params['uploader'] = $GLOBALS['top_nav_upload_type'];
        }

    $upload_here_params['upload_here'] = true;
    $upload_here_params['search'] = $search_params['search'];

    // Special search !collection
    if(mb_substr($search_params['search'], 0, 11) == '!collection')
        {
        $collection = explode(' ', $search_params['search']);
        $collection = str_replace('!collection', '', $collection[0]);
        $collection = explode(',', $collection);
        $collection = (int) $collection[0];

        //Check the user is able to upload to this collection before continuing
        if(!collection_writeable($collection)) {return;}

        $upload_here_params['collection_add'] = $collection;
        }

    // If resource types is a list then always select the first resource type the user has access to
    $resource_types = explode(',', $search_params['restypes']);
    foreach($resource_types as $resource_type)
        {
        if(!checkperm("XU{$resource_type}"))
            {
            $upload_here_params['resource_type'] = $resource_type;
            break;
            }
        }

    // Archive can be a list (e.g from advanced search) so always select the first archive state user access to, 
    // favouring the Active one
    $search_archive = explode(',', $search_params['archive']);
    $default_workflow_state = get_default_archive_state(0);
    if($default_workflow_state == 0)
        {
        $upload_here_params['status'] = $default_workflow_state;
        $search_archive = array();
        }
    // Check remaining states
    foreach($search_archive as $archive)
        {
        if($archive == '' || !is_numeric($archive))
            {
            continue;
            }

        if(get_default_archive_state($archive) != $archive)
            {
            continue;
            }
        
        $upload_here_params['status'] = $archive;
        break;
        }
    // Last attempt to set the archive state
    if(!isset($upload_here_params['status']))
        {
        $upload_here_params['status'] = $default_workflow_state;
        }

    // Option to return out just the upload params
    if ($return_params_only)
        {
        return $upload_here_params;
        }
        
    $upload_here_url = generateURL("{$GLOBALS['baseurl']}/{$upload_endpoint}", $upload_here_params);
    $attributes = "onclick=\"CentralSpaceLoad('{$upload_here_url}');\"";

    render_filter_bar_button($GLOBALS['lang']['upload_here'], $attributes, UPLOAD_ICON);

    return;
    }

/**
* Renders the trash bin. This is used to delete dash tiles and remove resources from collections
* 
* @param string $type   type of trash_bin
* 
* @return void
*/ 

function render_trash($type, $deletetext,$forjs=false)
    {
    $trash_html = '<div id="' . $type . '_bin" class="trash_bin ui-droppable ui-droppable-active ui-state-hover"><span class="trash_bin_text"><i class="fa fa-trash" aria-hidden="true"></i></span></div>
    <div id="trash_bin_delete_dialog" style="display:none;"></div>
    <div id="delete_permanent_dialog" style="display:none;text-align:left;">'  . $deletetext . '</div>
';
    if($forjs)
        {
        return str_replace(array("\r","\n"),"",$trash_html);
        }
    else
        {
        echo $trash_html;
        }
    }

/**
* Renders the browse bar
*  
* @return void
*/ 

function render_browse_bar()
    {
    global $lang, $browse_bar_workflow, $browse_show, $enable_themes;
    $bb_html = '<div id="BrowseBarContainer" class="ui-layout-west" style="display:none;">';
    $bb_html .= '<div id="BrowseBar" class="BrowseBar" ' . ($browse_show ?  '' : 'style="display:none;"') . '>';
    $bb_html .= '<div id="BrowseBarContent" >'; 
    
    //Browse row template
    // script will replace %BROWSE_TYPE%, %BROWSE_EXPAND_CLASS%, %BROWSE_CLASS% %BROWSE_LEVEL%, %BROWSE_EXPAND%, %BROWSE_NAME%, %BROWSE_TEXT%, %BROWSE_ID%
    $bb_html .= "
            <div id='BrowseBarTemplate' style='display: none;'>
            <div class='BrowseBarItem BrowseRowOuter %BROWSE_DROP%' data-browse-id='%BROWSE_ID%' data-browse-parent='%BROWSE_PARENT%'  data-browse-loaded='0' data-browse-status='closed' data-browse-level='%BROWSE_LEVEL%' style='display: none;'>
                <div class='BrowseRowInner' >
                    %BROWSE_INDENT%
                    %BROWSE_EXPAND%
                    %BROWSE_TEXT%
                    %BROWSE_REFRESH%
                </div><!-- End of BrowseRowInner -->
            </div><!-- End of BrowseRowOuter -->
            </div><!-- End of BrowseBarTemplate -->
            ";

    // Add root elements
    $bb_html .= generate_browse_bar_item("R", $lang['browse_by_tag']);
    if($enable_themes)
        {
        $bb_html .= generate_browse_bar_item("FC", $lang["themes"]);
        }
    if(!checkperm('b'))
        {
        $bb_html .= generate_browse_bar_item("C", $lang["mycollections"]);
        }
        
    if($browse_bar_workflow)
        {
        $bb_html .= generate_browse_bar_item("WF", $lang['browse_by_workflow_state']);
        }

    $bb_html .= '</div><!-- End of BrowseBarContent -->
                </div><!-- End of BrowseBar -->
                    <a href="#" title="' . $lang['browse_bar_text'] . '" onclick="ToggleBrowseBar();" ><div id="BrowseBarTab" style="display:none;"><div class="BrowseBarTabText" >' . $lang['browse_bar_text'] . '</div></div><!-- End of BrowseBarTab --></a>
                </div><!-- End of BrowseBarContainer -->
                
            ';
    echo $bb_html;
    
    $browsejsvar = $browse_show ? 'show' : 'hide';
    echo '<script>
        var browse_show = "' . $browsejsvar . '";
        SetCookie("browse_show", "' . $browsejsvar . '");
        b_loading = new Array();
        // Expand tree to previous state based on stored cookie
        jQuery(document).ready(function()
            {
            ReloadBrowseBar();
            });
        </script>';
    }


/**
* Generates a root row item for the browse bar
*  
* @return string  $html
*/    
function generate_browse_bar_item($id, $text)
	{
	//global $browse_bar_elements;
    $html = '<div class="BrowseBarItem BrowseRowOuter BrowseBarRoot" data-browse-id="' . $id . '" data-browse-parent="root" data-browse-loaded="0" data-browse-status="closed" data-browse-level="0" >';
    $html .= '<div class="BrowseRowInner" >';
	
    $html .= '<div class="BrowseBarStructure">
            <a href="#" class="browse_expand browse_closed" onclick="toggleBrowseElements(\'' . $id . '\',false,true);" ></a>
            </div><!-- End of BrowseBarStructure -->';	
    $html .= '<div onclick="toggleBrowseElements(\'' . $id . '\',false,true);" class="BrowseBarLink" >' . $text . '</div>';
    
    $html .= '<a href="#" class="BrowseRefresh " onclick="toggleBrowseElements(\'' . $id . '\',true, true);" ><i class="fas fa-sync reloadicon"></i></a>';	
    
    $html .= "</div><!-- End of BrowseRowInner -->
            </div><!-- End of BrowseRowOuter -->";
	return $html;
    }
    
/**
* Generates a help icon that opens the relevant Knowledge Base article in a modal
*  
* These links can be disabled by setting $contextual_help_links=false;
* 
* @param string  $page              Knowledge Base article to display, leave blank to show the Knowledge Base homepage
* @param boolean $return_string     Set to true to return the html as a single line string, False will cause the function to echo the html
* 
* @return mixed  if $return_string=true return is string, else void
*/
function render_help_link($page='',$return_string=false)
    {
    global $contextual_help_links,$pagename,$lang,$help_modal,$baseurl;
    if ($contextual_help_links === false){return;}

    // Build html for link into a string
    $help_link_html  =      '<a ';
    $help_link_html .=          'href="' . $baseurl . '/pages/help.php?page=' . $page . '" ';
    $help_link_html .=          'title="' . $lang["help-tooltip"] . '" ';
    $help_link_html .=          'class="HelpLink"';
    if ($help_modal) 
        { $help_link_html .=    'onClick="return ModalLoad(this, true);" ';}
    else
        { $help_link_html .=    'target="_blank" ';}
    $help_link_html .=      '>';
    $help_link_html .=      '<i aria-hidden="true" class="fa fa-fw fa-question-circle"></i>';
    $help_link_html .=      '</a>';

    if ($return_string===false) {echo $help_link_html;return;}
    else {return $help_link_html;}
    }


/**
* Render generic Question div (including clearleft)
* 
* @var  string    $id              Div ID if required. Set to empty string if not needed.
* @var  callable  $render_content  Content renderer
* 
* @return void
*/
function render_question_div($id, callable $render_content)
    {
    $id = (trim($id) !== "" ? 'id="' . htmlspecialchars(trim($id)) . '"' : "");
    ?>
    <div <?php echo $id; ?> class="Question">
        <?php $render_content(); ?>
        <div class="clearerleft"></div>
    </div>
    <?php
    return;
    }


/**
* Render custom fields (NOT metadata fields)
* 
* @param  array  $cfs  Custom fields information (as returned by process_custom_fields_submission function)
* 
* @return true
*/
function render_custom_fields(array $cfs)
    {
    return array_walk($cfs, function($field, $i)
        {
        render_question_div("Question_{$field["html_properties"]["id"]}", function() use ($field)
            {
            $field_id    = $field["html_properties"]["id"];
            $field_name  = $field["html_properties"]["name"];
            $field_value = $field["value"];

            global $FIXED_LIST_FIELD_TYPES;
            $selected_options_hashes = array_map(function($opt) use ($field_id)
                {
                return md5("{$field_id}_{$opt}");
                }, (in_array($field["type"], $FIXED_LIST_FIELD_TYPES) ? $field["selected_options"] : array()));

            $required_html = ($field["required"] ? "<sup>*</sup>" : "");
            ?>
            <label for="custom_<?php echo $field_id; ?>"><?php echo htmlspecialchars(i18n_get_translated($field["title"])) . $required_html; ?></label>
            <?php
            switch($field["type"])
                {
                case FIELD_TYPE_TEXT_BOX_MULTI_LINE:
                    ?>
                    <textarea id="<?php echo $field_id; ?>"
                              class="stdwidth MultiLine"
                              name="<?php echo $field_name; ?>"
                              rows=6
                              cols=50><?php echo htmlspecialchars($field_value); ?></textarea>
                    <?php
                    break;

                case FIELD_TYPE_DROP_DOWN_LIST:
                    ?>
                    <select id="<?php echo $field_id; ?>" class="stdwidth" name="<?php echo $field_name; ?>">
                    <?php
                    foreach($field["options"] as $f_option)
                        {
                        $computed_value = md5("{$field_id}_{$f_option}");
                        $label = htmlspecialchars(i18n_get_translated($f_option));
                        $extra_attributes = (in_array($computed_value, $selected_options_hashes) ? " selected" : "");

                        echo render_dropdown_option($computed_value, $label, array(), $extra_attributes);
                        }
                    ?>
                    </select>
                    <?php
                    break;

                case FIELD_TYPE_CHECK_BOX_LIST:
                    ?>
                    <div>
                    <?php
                    foreach($field["options"] as $f_option)
                        {
                        $computed_value = md5("{$field_id}_{$f_option}");
                        $label = htmlspecialchars(i18n_get_translated($f_option));
                        $checked = (in_array($computed_value, $selected_options_hashes) ? " checked" : "");
                        ?>
                        <div class="Inline">
                            <input type="checkbox" name="<?php echo $field_name; ?>" value="<?php echo $computed_value; ?>"<?php echo $checked; ?>>&nbsp;<?php echo $label; ?>
                        </div>
                        <?php
                        }
                        ?>
                        <div class="clearerleft"></div>
                    </div>
                    <?php
                    break;

                case FIELD_TYPE_TEXT_BOX_SINGLE_LINE:
                default:
                    ?>
                    <input type=text
                           id="<?php echo $field_id; ?>"
                           class="stdwidth"
                           name="<?php echo $field_name; ?>"
                           value="<?php echo htmlspecialchars($field_value); ?>">
                    <?php
                    break;
                }

            if(isset($field["error"]) && trim($field["error"]) != "")
                {
                ?>
                <div class="FormError"><?php echo htmlspecialchars($field["error"]); ?></div>
                <?php
                }
            });
        });
    }


/**
* Generates HTML for the "X Selected" in the search results found part pointing to the special collection COLLECTION_TYPE_SELECTION
* 
* @param integer $i Counter to display
* 
* @return string  Returns HTML
*/
function render_selected_resources_counter($i)
    {
    global $baseurl, $lang, $USER_SELECTION_COLLECTION;

    $url = generateURL("{$baseurl}", array("c" => $USER_SELECTION_COLLECTION));

    $x_selected = '<span class="Selected">' . number_format($i) . "</span> {$lang["selected"]}";
    $return = "<a href=\"{$url}\" class=\"SelectionCollectionLink\" onclick=\"return CentralSpaceLoad(this, true);\">{$x_selected}</a>";

    return $return;
    }


/**
* Renders the "Edit selected" button. This is using the special 'COLLECTION_TYPE_SELECTION' collection
* 
* @return void
*/
function render_edit_selected_btn()
    {
    global $baseurl_short, $lang, $USER_SELECTION_COLLECTION, $restypes, $archive;

    $search = "!collection{$USER_SELECTION_COLLECTION}";
    $editable_resources = do_search($search, $restypes, "resourceid", $archive, -1, "desc", false, 0, false, false, "", false, false, true, true);
    $non_editable_resources = do_search($search, $restypes, "resourceid", $archive, -1, "desc", false, 0, false, false, "", false, false, true, false);

    if(!is_array($editable_resources) || !is_array($non_editable_resources))
        {
        return;
        }

    $editable_resources_count = count($editable_resources);
    $non_editable_resources_count = count($non_editable_resources);

    if($editable_resources_count == 0 || $non_editable_resources_count == 0)
        {
        return;
        }

    // If not all resources are editable, don't show the batch edit button
    if($editable_resources_count != $non_editable_resources_count)
        {
        return;
        }

    $batch_edit_url = generateURL(
        "{$baseurl_short}pages/edit.php",
        array(
            "search"            =>  $search,
            "collection"        =>  $USER_SELECTION_COLLECTION,
            "restypes"          =>  $restypes,
            "order_by"          =>  "resourceid",
            "archive"           =>  $archive,
            "sort"              =>  "desc",
            "daylimit"          =>  "",
            "editsearchresults" => "true",
            "modal"             => "true",
        ));

    $attributes  = " id=\"EditSelectedResourcesBtn\"";
    $attributes .= " onclick=\"ModalLoad('{$batch_edit_url}', true);\"";

    render_filter_bar_button($lang["edit_selected"], $attributes, ICON_EDIT);

    return;
    }


/**
* Renders the "Clear selected" button. This is using the special 'COLLECTION_TYPE_SELECTION' collection
* 
* @return void
*/
function render_clear_selected_btn()
    {
    global $lang, $USER_SELECTION_COLLECTION, $CSRF_token_identifier, $usersession;

    $attributes  = " id=\"ClearSelectedResourcesBtn\" class=\"ClearSelectedButton\"";
    $attributes .= " onclick=\"ClearSelectionCollection(this);\"";
    $attributes .= " data-csrf-token-identifier=\"{$CSRF_token_identifier}\"";
    $attributes .= " data-csrf-token=\"" . generateCSRFToken($usersession, "clear_selected_btn_{$USER_SELECTION_COLLECTION}") . "\"";

    render_filter_bar_button($lang["clear_selected"], $attributes, ICON_REMOVE);

    return;
    }


/**
* Render the actions specific to when a user selected resources (using the special "COLLECTION_TYPE_SELECTION" collection)
* 
* @return void
*/
function render_selected_collection_actions()
    {
    global $USER_SELECTION_COLLECTION, $usercollection, $usersession, $lang, $CSRF_token_identifier, $search,
           $render_actions_extra_options, $render_actions_filter, $resources_count, $result;

    $orig_search = $search;
    $search = "!collection{$USER_SELECTION_COLLECTION}";

    $orig_result = $result;
    $result = get_collection_resources_with_data($USER_SELECTION_COLLECTION);

    $selected_resources = array_column($result, "ref");
    $resources_count = count($selected_resources);
    $usercollection_resources = get_collection_resources($usercollection);
    $refs_to_remove = count(array_intersect($selected_resources, $usercollection_resources));
    $collection_data = get_collection($USER_SELECTION_COLLECTION);

    $valid_selection_collection_actions = array(
        "relate_all",
        "save_search_items_to_collection",
        "remove_selected_from_collection",
        "search_items_disk_usage",
        "csv_export_results_metadata",
        "share_collection",
        "download_collection",
        "license_batch",
    );

    if($refs_to_remove > 0)
        {
        $callback_csrf_token = generateCSRFToken($usersession, "remove_selected_from_collection");
        $render_actions_extra_options = array(
            array(
                "value" => "remove_selected_from_collection",
                "label" => $lang["remove_selected_from_collection"],
                "data_attr" => array(
                    "callback" => "RemoveSelectedFromCollection('{$CSRF_token_identifier}', '{$callback_csrf_token}');",
                ),
                "category" => ACTIONGROUP_COLLECTION,
            ),
        );
        }
    $render_actions_filter = function($action) use ($valid_selection_collection_actions)
        {
        return in_array($action["value"], $valid_selection_collection_actions);
        };

    // override the language for actions as it's now specific to a selection of resources
    $lang["relateallresources"] = $lang["relate_selected_resources"];
    $lang["savesearchitemstocollection"] = $lang["add_selected_to_collection"];
    $lang["searchitemsdiskusage"] = $lang["selected_items_disk_usage"];
    $lang["share"] = $lang["share_selected"];

    render_actions($collection_data, true, false);

    $search = $orig_search;
    $result = $orig_result;

    return;
    }


// Render a select input for a user's collections
function render_user_collection_select($name = "collection", $collections=array(), $selected=0, $classes = "", $onchangejs = "")
    {
    global $userref,$hidden_collections,$active_collections,$lang;
    if(count($collections) == 0)
        {
        $collections = get_user_collections($userref);   
        }
    
    echo "<select name=\"" . $name . "\" id=\"" . $name . "\" " . ($onchangejs != "" ? (" onchange=\"" . htmlspecialchars($onchangejs) . "\"") : "") . ($classes != "" ? (" class=\"" . htmlspecialchars($classes) . "\"") : "")  . ">";
    echo "<option value=\"0\">" . $lang["select"] . "</option>";
    for ($n=0;$n<count($collections);$n++)
        {
        if(in_array($collections[$n]['ref'],$hidden_collections))
            {
            continue;
            }
        
        #show only active collections if a start date is set for $active_collections 
        if (strtotime($collections[$n]['created']) > ((isset($active_collections))?strtotime($active_collections):1))
            {
            echo "<option value=\"" . $collections[$n]["ref"] . "\" " . ($selected==$collections[$n]["ref"] ? "selected" : "") . ">" . i18n_get_collection_name($collections[$n]) . "</option>";
            }
        }
           
	echo "</select>";
    }


/**
* Render CSRF information as data attributes. Useful to allow JS to run state changing operations
*/
function render_csrf_data_attributes($ident)
    {
    global $CSRF_token_identifier, $usersession;

    $token = generateCSRFToken($usersession, $ident);
    return "data-csrf-token-identifier=\"{$CSRF_token_identifier}\" data-csrf-token=\"{$token}\"";
    }


/**
* Check display condition for a field. 
* 
* @uses get_nodes()
* @uses extract_node_options()
* @uses get_resource_nodes()
* @uses get_node_by_name()
* 
* @param integer $n         Question sequence number on the rendered form
* @param array   $field     Field on which we check display conditions
* @param array   $fields    Resource field data and properties as returned by get_resource_field_data()
* @param boolean $render_js Set to TRUE to render the client side code for checking display conditions or FALSE otherwise
* 
* 
* @return boolean Returns TRUE if no display condition or if field shoud be displayed or FALSE if field should not be displayed.
*/
function check_display_condition($n, array $field, array $fields, $render_js)
    {
    global $required_fields_exempt, $blank_edit_template, $ref, $use, $FIXED_LIST_FIELD_TYPES;

    if(trim($field['display_condition']) == "")
        {
        return true;  # This field does not have a display condition, so it should be displayed
        }

    // Assume the candidate field is to be displayed    
    $displaycondition = true;
    // Break down into array of conditions
    $conditions       = explode(';', $field['display_condition']);
    $condref          = 0;
    $scriptconditions = array();
    
    
    // Need all field data to check display conditions
    global $display_check_data;
    if(!is_array($display_check_data))
        {
        $display_check_data = get_resource_field_data($use,false,false);
        }

    // On upload, check against the posted nodes as save_resource_data() saves nodes after going through all the fields
    $user_set_values = getval('nodes', array());

    foreach ($conditions as $condition) # Check each condition
        {
        $displayconditioncheck = false;

        // Break this condition down into fieldname $s[0] and value(s) $s[1]
        $s = explode('=', $condition);

        // Process all fields which are referenced by display condition(s) on the candidate field
        // For each referenced field, render javascript to trigger when the referenced field changes
        for ($cf=0;$cf<count($display_check_data);$cf++) # Check each field to see if needs to be checked
            {
            // Work out nodes submitted by user, if any
            $ui_selected_node_values = array();
            if(
                isset($user_set_values[$display_check_data[$cf]['ref']])
                && !is_array($user_set_values[$display_check_data[$cf]['ref']])
                && $user_set_values[$display_check_data[$cf]['ref']] != ''
                && is_numeric($user_set_values[$display_check_data[$cf]['ref']])
            )
                {
                $ui_selected_node_values[] = $user_set_values[$display_check_data[$cf]['ref']];
                }
            else if(isset($user_set_values[$display_check_data[$cf]['ref']]) && is_array($user_set_values[$display_check_data[$cf]['ref']]))
                {
                $ui_selected_node_values = $user_set_values[$display_check_data[$cf]['ref']];
                }

            // Does the fieldname on this condition match the field being processed
            if($s[0] == $display_check_data[$cf]['name']) # this field needs to be checked
                {
                $display_check_data[$cf]['nodes'] = get_nodes($display_check_data[$cf]['ref'], null, (FIELD_TYPE_CATEGORY_TREE == $display_check_data[$cf]['type'] ? true : false));

                $node_options = extract_node_options($display_check_data[$cf]['nodes']);

                $scriptconditions[$condref]['field'] = $display_check_data[$cf]['ref'];
                $scriptconditions[$condref]['type']  = $display_check_data[$cf]['type'];

                $checkvalues=$s[1];
                // Break down values delimited with pipe characters
                $validvalues=explode("|",mb_strtoupper($checkvalues));
                $scriptconditions[$condref]['valid'] = array();
                $v = trim_array(get_resource_nodes($ref, $display_check_data[$cf]['ref']));

                if(count($ui_selected_node_values) > 0)
                    {
                    $v = $ui_selected_node_values;
                    }

                // If blank edit template is used, on upload form the dependent fields should be hidden
                if($blank_edit_template && $ref < 0 && $use == $ref)
                    {
                    $v = array();
                    }

                foreach($validvalues as $validvalue)
                    {
                    $found_validvalue = get_node_by_name($display_check_data[$cf]['nodes'], $validvalue);

                    if(0 != count($found_validvalue))
                        {
                        $scriptconditions[$condref]['valid'][] = $found_validvalue['ref'];

                        if(in_array($found_validvalue['ref'], $v))
                            {
                            $displayconditioncheck = true;
                            }
                        }
                    }

                 if(!$displayconditioncheck)
                    {
                    $displaycondition = false;
                    $required_fields_exempt[]=$field["ref"];
                    }

                // Skip rendering the JS calls to checkDisplayCondition functions
                // Skip if user does not have access to the master (parent) field 
                if(!$render_js || !in_array($display_check_data[$cf]['ref'], array_column($fields,"ref")))
                    {
                    continue;
                    }

                // Check display conditions
                // Certain fixed list types allow for multiple nodes to be passed at the same time

                // Generate a javascript function specific to the field with the display condition
                // This function will be invoked whenever a field referenced by the display condition changes
                if(in_array($display_check_data[$cf]['type'], $FIXED_LIST_FIELD_TYPES))
                    {
                    if(FIELD_TYPE_CATEGORY_TREE == $display_check_data[$cf]['type'])
                        {
                        ?>
                        <script>
                        jQuery(document).ready(function()
                            {
                            <?php
                            if($GLOBALS["multiple"] === false)
                                {
                                ?>
                                checkDisplayCondition<?php echo $field['ref']; ?>();
                                <?php
                                }
                            ?>
                            jQuery('#CentralSpace').on('categoryTreeChanged', function(e,node)
                                {
                                checkDisplayCondition<?php echo $field['ref']; ?>();
                                });
                            });
                        </script>
                        <?php

                        // Move on to the next field now
                        continue;
                        }
                    else if(FIELD_TYPE_DYNAMIC_KEYWORDS_LIST == $display_check_data[$cf]['type'])
                        {
                        ?>
                        <script>
                        jQuery(document).ready(function()
                            {
                            <?php
                            if($GLOBALS["multiple"] === false)
                                {
                                ?>
                                checkDisplayCondition<?php echo $field['ref']; ?>();
                                <?php
                                }
                            ?>
                            jQuery('#CentralSpace').on('dynamicKeywordChanged', function(e,node)
                                {
                                checkDisplayCondition<?php echo $field['ref']; ?>();
                                });
                            });
                        </script>
                        <?php

                        // Move on to the next field now
                        continue;
                        }

                    $checkname = "nodes[{$display_check_data[$cf]['ref']}][]";

                    if(FIELD_TYPE_RADIO_BUTTONS == $display_check_data[$cf]['type'])
                        {
                        $checkname = "nodes[{$display_check_data[$cf]['ref']}]";
                        }

                    $jquery_selector = "input[name=\"{$checkname}\"]";

                    if(FIELD_TYPE_DROP_DOWN_LIST == $display_check_data[$cf]['type'])
                        {
                        $checkname       = "nodes[{$display_check_data[$cf]['ref']}]";
                        $jquery_selector = "select[name=\"{$checkname}\"]";
                        }
                    ?>
                    <script type="text/javascript">
                    jQuery(document).ready(function()
                        {
                        <?php
                        if($GLOBALS["multiple"] === false)
                            {
                            ?>
                            checkDisplayCondition<?php echo $field['ref']; ?>();
                            <?php
                            }
                        ?>
                        jQuery('<?php echo $jquery_selector; ?>').change(function ()
                            {
                            checkDisplayCondition<?php echo $field['ref']; ?>();
                            });
                        });
                    </script>
                    <?php
                    }
                else
                    {
                    ?>
                    <script type="text/javascript">
                    jQuery(document).ready(function()
                        {
                        <?php
                        if($GLOBALS["multiple"] === false)
                            {
                            ?>
                            checkDisplayCondition<?php echo $field['ref']; ?>();
                            <?php
                            }
                        ?>
                        jQuery('#field_<?php echo $display_check_data[$cf]["ref"]; ?>').change(function ()
                            {
                            checkDisplayCondition<?php echo $field['ref']; ?>();
                            });
                        });
                    </script>
                    <?php
                    }
                }

            } # see if next field needs to be checked
        $condref++;

        } # check next condition

    if($render_js)
        {
        ?>
        <script type="text/javascript">
        function checkDisplayCondition<?php echo $field["ref"];?>()
            {
            // Get current display state for governed field ("block" or "none")
            field<?php echo $field['ref']; ?>status    = jQuery('#question_<?php echo $n; ?>').css('display');
            newfield<?php echo $field['ref']; ?>status = 'none';

            // Assume visible by default
            field<?php echo $field['ref']; ?>visibility = true;
            <?php
            foreach($scriptconditions as $scriptcondition)
                {
                /* Example of $scriptconditions:
                    [{"field":"73","type":"3","display_as_dropdown":"0","valid":["267","266"]}]
                */
                ?>

                field<?php echo $field['ref']; ?>valuefound = false;
                fieldokvalues<?php echo $scriptcondition['field']; ?> = <?php echo json_encode($scriptcondition['valid']); ?>;
                
                <?php
                ############################
                ### Field type specific
                ############################
                if(in_array($scriptcondition['type'], $FIXED_LIST_FIELD_TYPES))
                    {
                    $jquery_condition_selector = "input[name=\"nodes[{$scriptcondition['field']}][]\"]";
                    $js_conditional_statement  = "fieldokvalues{$scriptcondition['field']}.indexOf(element.value) != -1";

                    if(FIELD_TYPE_CHECK_BOX_LIST == $scriptcondition['type'])
                        {
                        $js_conditional_statement = "element.checked && {$js_conditional_statement}";
                        }

                    if(FIELD_TYPE_DROP_DOWN_LIST == $scriptcondition['type'])
                        {
                        $jquery_condition_selector = "select[name=\"nodes[{$scriptcondition['field']}]\"] option:selected";
                        }

                    if(FIELD_TYPE_RADIO_BUTTONS == $scriptcondition['type'])
                        {
                        $jquery_condition_selector = "input[name=\"nodes[{$scriptcondition['field']}]\"]:checked";
                        }
                    ?>
                        jQuery('<?php echo $jquery_condition_selector; ?>').each(function(index, element)
                            {
                            if(<?php echo $js_conditional_statement; ?>)
                                {
                                field<?php echo $field['ref']; ?>valuefound = true;
                                }
                            });

                    <?php
                    }
                ?>
                if(!field<?php echo $field['ref']; ?>valuefound)
                    {
                    field<?php echo $field['ref']; ?>visibility = false;
                    }
                <?php
                }
                ?>

                // Is field to be displayed
                if(field<?php echo $field['ref']; ?>visibility)
                    {
                    newfield<?php echo $field['ref']; ?>status = 'block';
                    }

                // If display status changed then toggle the visibility
                if(newfield<?php echo $field['ref']; ?>status != field<?php echo $field['ref']; ?>status)
                    {
                    jQuery('#question_<?php echo $n ?>').css("display", newfield<?php echo $field['ref']; ?>status);                   

                <?php
                // Batch edit mode
                if($GLOBALS["multiple"] === true)
                    {
                    ?>
                    var batch_edit_editthis = jQuery("#<?php echo "editthis_{$n}"; ?>");
                    batch_edit_editthis.prop("checked", !batch_edit_editthis.prop("checked"));
                    batch_edit_toggle_edit_multi_checkbox_question(<?php echo (int) $n; ?>);
                    <?php
                    }
                    ?>

                    if(jQuery('#question_<?php echo $n ?>').css('display') == 'block')
                        {
                        jQuery('#question_<?php echo $n ?>').css('border-top', '');
                        }
                    else
                        {
                        jQuery('#question_<?php echo $n ?>').css('border-top', 'none');
                        }
                    }
            }
        </script>
        <?php
        }

    return $displaycondition;
    }


/**
* Utility to check if browse bar should be rendered
*  
* @return boolean
*/   
function has_browsebar()
    {
    global $username, $pagename,$not_authenticated_pages, $loginterms, $not_authenticated_pages, $k, $internal_share_access, $browse_bar;
    return isset($username)
    && is_array($not_authenticated_pages) && !in_array($pagename, $not_authenticated_pages)
    && ('' == $k || $internal_share_access)
    && $browse_bar;
    //   && false == $loginterms ?
    }

/**
* Utility to if collapsable upload options should be displayed
*  
* @return boolean
*/   
function display_upload_options()
    {
    global $metadata_read, $enable_add_collection_on_upload, $relate_on_upload, $camera_autorotation;
    if ($metadata_read || $enable_add_collection_on_upload || $relate_on_upload || $camera_autorotation)
        {
        return true;
        }
    else
        {
        return false;
        }
    }
    

function display_field_data($field,$valueonly=false,$fixedwidth=452)
	{		
	global $ref, $show_expiry_warning, $access, $search, $extra, $lang, $FIXED_LIST_FIELD_TYPES, $range_separator, $force_display_template_orderby;

	$value=$field["value"];
    # Populate field value for node based fields so it conforms to automatic ordering setting

    if($field['type'] == FIELD_TYPE_CATEGORY_TREE)
        {
        $treenodes = get_resource_nodes($ref, $field["ref"], true);
        $treetext_arr = get_tree_strings($treenodes);
        $value = implode(", ",$treetext_arr);        
        }
    elseif(in_array($field['type'],$FIXED_LIST_FIELD_TYPES))
		{
		# Get all nodes attached to this resource and this field    
		$nodes_in_sequence = get_resource_nodes($ref,$field['ref'],true);
		
		if((bool) $field['automatic_nodes_ordering'])
			{
			uasort($nodes_in_sequence,"node_name_comparator");    
			}
		else
			{
			uasort($nodes_in_sequence,"node_orderby_comparator");    
			}
	
		$node_tree = get_node_tree("", $nodes_in_sequence); // get nodes as a tree in correct hierarchical order
		$node_names = get_node_elements(array(), $node_tree, "name"); // retrieve values for a selected field in the tree 

		$keyword_array=array();
		foreach($node_names as $name)
			{
			$keyword_array[] = i18n_get_translated($name);
			}
		$value = implode(',',$keyword_array);
		}

	$modified_field=hook("beforeviewdisplayfielddata_processing","",array($field));
    if($modified_field)
        {
		$field=$modified_field;
	    }
	
	# Handle expiry fields
	if (!$valueonly && $field["type"]==FIELD_TYPE_EXPIRY_DATE && $value!="" && $value<=date("Y-m-d H:i") && $show_expiry_warning) 
		{
		$extra.="<div class=\"RecordStory\"> <h1>" . $lang["warningexpired"] . "</h1><p>" . $lang["warningexpiredtext"] . "</p><p id=\"WarningOK\"><a href=\"#\" onClick=\"document.getElementById('RecordDownload').style.display='block';document.getElementById('WarningOK').style.display='none';\">" . $lang["warningexpiredok"] . "</a></p></div><style>#RecordDownload {display:none;}</style>";
		}
	
	# Handle warning messages
	if (!$valueonly && FIELD_TYPE_WARNING_MESSAGE == $field['type'] && '' != trim($value)) 
		{
		$extra.="<div class=\"RecordStory\"><h1>{$lang['fieldtype-warning_message']}</h1><p>" . nl2br(htmlspecialchars(i18n_get_translated($value))) . "</p><br /><p id=\"WarningOK\"><a href=\"#\" onClick=\"document.getElementById('RecordDownload').style.display='block';document.getElementById('WarningOK').style.display='none';\">{$lang['warningexpiredok']}</a></p></div><style>#RecordDownload {display:none;}</style>";
		}
	
	# Process the value using a plugin. Might be processing an empty value so need to do before we remove the empty values
	$plugin="../plugins/value_filter_" . $field["name"] . ".php";
	
    if ($field['value_filter']!="")
        {
        eval($field['value_filter']);
        }
    else if (file_exists($plugin))
        {
        include $plugin;
        }
    else if ($field["type"]==FIELD_TYPE_DATE_AND_OPTIONAL_TIME && strpos($value,":")!=false)
        {
        // Show the time as well as date if entered
        $value=nicedate($value,true,true);
        }
    else if ($field["type"]==FIELD_TYPE_DATE_AND_OPTIONAL_TIME || $field["type"]==FIELD_TYPE_EXPIRY_DATE || $field["type"]==FIELD_TYPE_DATE)
        {
        $value=nicedate($value,false,true);
        }
	else if ($field["type"]==FIELD_TYPE_DATE_RANGE) 
		{
		$rangedates = explode(",",$value);		
		natsort($rangedates);
		$value=implode($range_separator,$rangedates);
		}
	
    if (($field["type"]==FIELD_TYPE_CHECK_BOX_LIST) || ($field["type"]==FIELD_TYPE_DROP_DOWN_LIST) || ($field["type"]==FIELD_TYPE_CATEGORY_TREE) || ($field["type"]==FIELD_TYPE_DYNAMIC_KEYWORDS_LIST))
        {
        $value=TidyList($value);
        }
	
	if (($value!="") && ($value!=",") && ($field["display_field"]==1) && ($access==0 || ($access==1 && !$field["hide_when_restricted"])))
		{			
		if (!$valueonly)
			{
            $title=htmlspecialchars(str_replace("Keywords - ","",$field["title"]));
            }
        else
            {
            $title="";
            }

		# Value formatting
		$value=i18n_get_translated($value);
		
        // Don't display the comma for radio buttons:
        if($field['type'] == FIELD_TYPE_RADIO_BUTTONS)
            {
            $value = str_replace(',', '', $value);
            }

		$value_unformatted=$value; # store unformatted value for replacement also

        # Do not convert HTML formatted fields (that are already HTML) to HTML. Added check for extracted fields set to 
        # ckeditor that have not yet been edited.
        if(
            $field["type"] != FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR
            || ($field["type"] == FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR && $value == strip_tags($value))
            )
            {
            $value = nl2br(htmlspecialchars($value));
            }

		$modified_value = hook('display_field_modified_value', '', array($field));
        if($modified_value)
            {		
			$value = $modified_value['value'];
		    }

		if (!$valueonly && trim($field["display_template"])!="")
			{
			# Highlight keywords
			$value=highlightkeywords($value,$search,$field["partial_index"],$field["name"],$field["keywords_index"]);
			
			$value_mod_after_highlight=hook('value_mod_after_highlight', '', array($field,$value));
            if($value_mod_after_highlight)
                {
				$value=$value_mod_after_highlight;
			    }

            # Use a display template to render this field
            $template = $field['display_template'];
            $template = str_replace('[title]', $title, $template);
            $template = str_replace('[value]', strip_tags_and_attributes(htmlspecialchars($value),array("a"),array("href","target")), $template);
            $template = str_replace('[value_unformatted]', $value_unformatted, $template);
            $template = str_replace('[ref]', $ref, $template);

            /*Language strings
            Format: [lang-language-name_here]
            Example: [lang-resourcetype-photo]
            */
            preg_match_all('/\[lang-(.+?)\]/', $template, $template_language_matches);
            $i = 0;
            foreach($template_language_matches[0] as $template_language_match_placeholder)
                {
                $placeholder_value = $template_language_match_placeholder;

                if(isset($lang[$template_language_matches[1][$i]]))
                    {
                    $placeholder_value = $lang[$template_language_matches[1][$i]];
                    }

                $template = str_replace($template_language_match_placeholder, $placeholder_value, $template);

                $i++;
                }

            $extra   .= $template;
			}
		else
			{
			#There is a value in this field, but we also need to check again for a current-language value after the i18n_get_translated() function was called, to avoid drawing empty fields
            if ($value!="")
                {
                # Draw this field normally. - value has already been sanitized by htmlspecialchars
				# Highlight keywords
				$value=highlightkeywords($value,$search,$field["partial_index"],$field["name"],$field["keywords_index"]);
				
				$value_mod_after_highlight=hook('value_mod_after_highlight', '', array($field,$value));
				if($value_mod_after_highlight)
					{
					$value=$value_mod_after_highlight;
					}
				
                ?><div 
                <?php
                if (!$valueonly)
                    {
                    if ($field["full_width"])
                        {
                        echo "class=\"clearerleft item itemType".$field['type']."\"";
                        }
                    else
                        {
                        echo "class=\"itemNarrow itemType".$field['type']."\"";
                        }
                    }
                elseif (isset($fixedwidth))
                    {
                    echo "style=\"width:" . $fixedwidth . "px\"";
                    } ?>>
				<h3><?php echo $title?></h3><p><?php echo $value; ?></p></div><?php
				}
			}
			
        if($force_display_template_orderby)
            {
            echo $extra;
            $extra='';
            }
        }
    }

/*
* Render the resource lock/unlock link for resource tools
* 
* @param  int       $ref         Resource ID
* @param  int       $lock_user   ID of the user that locked the resource
* @param  boolean   $editaccess  Does the user have edit access to the resource?
* 
* @return void
*/
function render_resource_lock_link($ref,$lockuser,$editaccess)
    {
    global $userref, $lang;
    
    $resource_locked = (int)$lockuser > 0;

    $edit_lock_option = false;
    if(checkperm("a") 
        ||
        $userref == $lockuser
        ||
        (!$resource_locked && !checkperm("nolock") && $editaccess)
        )
        {
        $edit_lock_option = true;
        }

    if(!$resource_locked && !$edit_lock_option)
        {
        // User is not permitted to lock resource
        return;
        }    
    
    $lock_details = get_resource_lock_message($lockuser);

    echo "<li>";
    if($edit_lock_option)
        {
        echo "<a href='#' id='lock_link_" . $ref . "' onclick='return updateResourceLock(" . $ref . ",!resource_lock_status);' ";
        echo "title='" .  $lock_details . "'";
        echo "class='LockedResourceAction " . ($resource_locked ? "ResourceLocked" : "ResourceUnlocked" ). "'>&nbsp;";
        if($resource_locked)
            {
            $locktext = (checkperm("a") || ($lockuser == $userref)) ? $lang["action_unlock"] : $lang["status_locked"];
            }
        else
            {
            $locktext = $lang["action_lock"];
            }
        echo $locktext . "</a>";
        }
    else
        {
        echo "<div  class='ResourceLocked' title='" .  htmlspecialchars($lock_details) . "' >" . $lang["status_locked"] . "</div>";
        }

    echo "<a id='lock_details_link' href='#' " . ($resource_locked ? "" : "style='display:none;'") . " onclick='if(resource_lock_status){styledalert(\"" . $lang["status_locked"] . "\",lockmessage[" . $ref . "]);}'>&nbsp;<i class='fas fa-info-circle'></i></a> </li>";
    }

/**
 * EditNav - render html for next/back browsing on the resource edit page. Called by SaveAndClearButtons()
 *
 * @return void
 */
function EditNav() 
   {
    global $baseurl_short,$ref,$search,$offset,$order_by,$sort,$archive,$lang,$modal,$restypes,$disablenavlinks,$upload_review_mode, $urlparams;
    ?>
    <div class="BackToResultsContainer"><div class="backtoresults">
    <?php
    if(!$disablenavlinks && !$upload_review_mode)
        {?>
        <a class="prevLink fa fa-arrow-left" onClick="return <?php echo ($modal?"Modal":"CentralSpace") ?>Load(this,true);" href="<?php echo generateURL($baseurl_short . "pages/edit.php",$urlparams, array("go"=>"previous")); ?>"></a>
   
        <a class="upLink" onClick="return CentralSpaceLoad(this,true);" href="<?php echo generateURL($baseurl_short . "pages/search.php",$urlparams, array("go"=>"previous")); ?>"><?php echo $lang["viewallresults"]?></a>
   
        <a class="nextLink fa fa-arrow-right" onClick="return <?php echo ($modal?"Modal":"CentralSpace") ?>Load(this,true);" href="<?php echo generateURL($baseurl_short . "pages/edit.php",$urlparams, array("go"=>"next")); ?>"></a>
   
        <?php
        }
    if ($modal)
        { ?>
        &nbsp;&nbsp;<a class="maxLink fa fa-expand" href="<?php echo generateURL($baseurl_short . "pages/edit.php",$urlparams); ?>" onClick="return CentralSpaceLoad(this);"></a>
        &nbsp;<a href="#"  class="closeLink fa fa-times" onClick="ModalClose();"></a>
        <?php
        } ?>
    </div></div><?php
  }
 
/**
 * Render the 'QuestionSubmit' div with the 'Save', 'Clear' and 'Save all with values' locked Buttons - used by the resource edit page
 *
 * @param  string   $extraclass   - Additional CSS classes to add to the Question div
 * @param  bool     $requiredfields - to indicate the input is required
 * @param  bool     $backtoresults - Show the next/back links using EditNav()
 * @return void
 */
function SaveAndClearButtons($extraclass="",$requiredfields=false,$backtoresults=false)
    {
    global $lang, $multiple, $ref, $upload_review_mode, $noupload, $is_template,
    $show_required_field_label, $modal, $edit_selection_collection_resources, $locked_fields;

    $save_btn_value = ($ref > 0 ? ($upload_review_mode ? $lang["saveandnext"] : $lang["save"]) : $lang["next"]);
    if($ref < 0 && $noupload)
        {
        $save_btn_value = $lang['create'];
        }

    $confirm_text = $lang["confirmeditall"];
    if($edit_selection_collection_resources)
        {
        $confirm_text = $lang["confirm_edit_all_selected_resources"];
        }
    ?>
    <div class="QuestionSubmit <?php echo $extraclass ?>">
        <?php
        if($ref < 0 || $upload_review_mode)
            {
            echo "<input name='resetform' class='resetform' type='submit' value='" . $lang["clearbutton"] . "' />&nbsp;";
            }
            ?>
        <input <?php if ($multiple) { ?>onclick="return confirm('<?php echo $confirm_text; ?>');"<?php } ?>
               name="save"
               class="editsave"
               type="submit"
               value="&nbsp;&nbsp;<?php echo $save_btn_value; ?>&nbsp;&nbsp;" />
        <?php
        if($upload_review_mode)
            {
            ?>&nbsp;<input name="save_auto_next" <?php if(count($locked_fields) == 0){echo "style=\"display:none;\"";} ?>class="editsave save_auto_next" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save_and_auto"] ?>&nbsp;&nbsp;" />
            <?php
            }

        if(!$is_template && $show_required_field_label && $requiredfields)
            {
            ?>
            <div class="RequiredFieldLabel"><sup>*</sup> <?php echo $lang['requiredfield']; ?></div>
            <?php
            }

        # Duplicate navigation
       if (!$multiple && !$modal && $ref>0 && !hook("dontshoweditnav") && $backtoresults)
            {
            EditNav();
            }
            ?>
        <br />
        <div class="clearerleft"> </div>
    </div>
    <?php
    }


/**
 * display_size_option
 *
 * @param  mixed $sizeID
 * @param  mixed $sizeName
 * @param  mixed $fordropdown
 * @return void
 */
function display_size_option($sizeID, $sizeName, $fordropdown=true)
	{
	global $available_sizes, $lang, $result;
	if(!hook('replace_display_size_option','',array($sizeID, $sizeName, $fordropdown))){
    	if ($fordropdown)
			{
			?><option value="<?php echo htmlspecialchars($sizeID) ?>"><?php
			echo $sizeName;
			}
    	if(isset($available_sizes[$sizeID]))
			{
			$availableCount = count($available_sizes[$sizeID]);
			}
		else
			{
			$availableCount=0;
			}
		$resultCount = count($result);
		if ($availableCount != $resultCount && $sizeID != "largest")
			{
			echo " (" . $availableCount . " " . $lang["of"] . " " . $resultCount . " ";
			switch ($availableCount)
				{
				case 0:
					echo $lang["are_available-0"];
					break;
				case 1:
					echo $lang["are_available-1"];
					break;
				default:
					echo $lang["are_available-2"];
					break;
				}
			echo ")";
			}
			 if ($fordropdown)
				{
			?></option><?php
			}
		}
	}


/**
* Render the featured collection category selector
* 
* @param integer $parent   Parent collection ref
* @param array   $context  Contextual data (e.g depth level to render or the current branch path)
* 
* @return void
*/
function render_featured_collection_category_selector(int $parent, array $context)
    {
    global $lang;

    // If this information is missing, that's an unrecoverable error, the developer should really make sure this information is provided
    $collection = $context["collection"]; # as returned by get_collection()
    $depth = (int) $context["depth"];
    $current_branch_path = $context["current_branch_path"]; # as returned by get_featured_collection_category_branch_by_leaf()
    $modal = (isset($context["modal"]) && is_bool($context["modal"]) ? $context["modal"] : false);

    $featured_collection_categories = get_featured_collection_categories($parent, array());
    if(empty($featured_collection_categories))
        {
        return;
        }

    $html_selector_name = "selected_featured_collection_category_{$depth}";
    $html_question_label_txt = $lang["themecategory"] . ($depth == 0 ? "" : " {$depth}");
    ?>
    <div class="Question">
        <label for="<?php echo $html_selector_name; ?>"><?php echo $html_question_label_txt; ?></label>
        <?php
        $next_level_parent = null;
        ?>
        <select id="<?php echo $html_selector_name; ?>" class="stdwidth" name="<?php echo $html_selector_name; ?>"
                onchange="featured_collection_category_select_onchange(this, document.getElementById('collectionform'));
                <?php echo ($modal ? "Modal" : "CentralSpace"); ?>Post(document.getElementById('collectionform'));">                
            <option value="0"><?php echo $lang["select"]; ?></option>
        <?php
        // Allow user to move FC category to the root. Because we don't expose the collection type to the user, this will
        // give users the ability to convert between public collection and featured category at root level without access
        // to the collection type.
        if($depth == 0)
            {
            $dummy_root_lvl_selected = ($collection["type"] == COLLECTION_TYPE_FEATURED && $parent == 0 ? "selected" : "");
            ?>
            <option value="root" <?php echo $dummy_root_lvl_selected; ?>><?php echo $lang["featured_collection_root_category"]; ?></option>
            <?php
            }
        foreach($featured_collection_categories as $fc_category)
            {
            // Never show as an option the FC you're editing
            if($fc_category["ref"] == $collection["ref"])
                {
                continue;
                }

            $html_attr_selected = "";
            if(isset($current_branch_path[$depth]) && $fc_category["ref"] == $current_branch_path[$depth]["ref"])
                {
                $html_attr_selected = "selected";
                $next_level_parent = $fc_category["ref"];
                }
            ?>
            <option value="<?php echo $fc_category["ref"]; ?>" <?php echo $html_attr_selected; ?>><?php echo htmlspecialchars(i18n_get_translated($fc_category["name"])); ?></option>
            <?php
            }
            ?>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    if(is_null($next_level_parent))
        {
        return;
        }

    $context["depth"] = ++$depth;
    return render_featured_collection_category_selector($next_level_parent, $context);
    }


/**
* Render featured collections (as tiles on the collections_featured.php page)
* 
* @param array $ctx    Context data to allow caller code to decide rendering requirements
* @param array $items  List of items to render (featured collection category, actual collection or smart collection)
*/
function render_featured_collections(array $ctx, array $items)
    {
    global $baseurl_short, $lang, $k, $themes_simple_images, $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS, $themes_simple_view;

    $is_smart_featured_collection = (isset($ctx["smart"]) ? (bool) $ctx["smart"] : false);
    $general_url_params = (isset($ctx["general_url_params"]) && is_array($ctx["general_url_params"]) ? $ctx["general_url_params"] : array());
    $all_fcs = (isset($ctx["all_fcs"]) && is_array($ctx["all_fcs"]) ? $ctx["all_fcs"] : array());

    foreach($items as $fc)
        {
        $render_ctx = $ctx;
        $is_featured_collection_category = is_featured_collection_category($fc);
        $is_featured_collection = (!$is_featured_collection_category && !$is_smart_featured_collection);

        $tool_edit = array(
            "href" => generateURL("{$baseurl_short}pages/collection_edit.php",
                array(
                    "ref" => $fc["ref"],
                    "redirection_endpoint" => urlencode(
                        generateURL(
                            "{$baseurl_short}pages/collections_featured.php",
                            $general_url_params,
                            array("parent" => $fc["parent"])
                        )
                    )
                )
            ),
            "text" => $lang['action-edit'],
            "modal_load" => true,
            "redirect" => true
        );
        $tool_select = array(
            "text" => $lang['action-select'],
            "custom_onclick" => "return ChangeCollection({$fc['ref']}, '');"
        );

        // Prepare FC images
        $thumbnail_selection_method = $fc["thumbnail_selection_method"];
        $show_images = ($themes_simple_view && in_array($thumbnail_selection_method, $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS) && $thumbnail_selection_method != $FEATURED_COLLECTION_BG_IMG_SELECTION_OPTIONS["no_image"]);
        unset($fc_resources);
        if($themes_simple_images && $show_images)
            {
            $fc_resources = get_featured_collection_resources(
                $fc,
                array(
                    "smart" => $is_smart_featured_collection,
                    "use_thumbnail_selection_method" => !$is_smart_featured_collection,
                    "all_fcs" => $all_fcs,
                ));
            $fc_images = generate_featured_collection_image_urls($fc_resources, "pre");

            if(!empty($fc_images))
                {
                $render_ctx["images"] = $fc_images;
                }
            }

        // Featured collection default tools
        if($is_featured_collection && checkPermission_dashmanage())
            {
            $render_ctx["tools"][] = array(
                "href" => generateURL(
                    "{$baseurl_short}pages/dash_tile.php",
                    array(
                        'create'            => 'true',
                        'tltype'            => 'srch',
                        'title'             => "{$fc['name']}",
                        'freetext'          => 'true',
                        'tile_audience'     => 'false',
                        'all_users'         => 1,
                        'promoted_resource' => (isset($render_ctx["images"]) ? 'true' : ""),
                        'link'              => "{$baseurl_short}pages/search.php?search=!collection{$fc['ref']}",
                    )
                ),
                "text" => $lang['add_to_dash']);
            }
        if($is_featured_collection && collection_readable($fc['ref']))
            {
            $render_ctx["tools"][] = $tool_select;
            }
        if($is_featured_collection && collection_writeable($fc['ref']))
            {
            $render_ctx["tools"][] = $tool_edit;
            }
        if($is_featured_collection)
            {
            $render_ctx['show_resources_count'] = true;
            }


        if($is_featured_collection_category && !$is_smart_featured_collection)
            {
            global $enable_theme_category_edit;

            $fc_category_url = generateURL("{$baseurl_short}pages/collections_featured.php", $general_url_params, array("parent" => $fc["ref"]));
            $fc_category_has_children = (isset($fc["has_children"]) ? (bool) $fc["has_children"] : false);

            $render_ctx["href"] = $fc_category_url;
            $render_ctx["icon"] = ICON_FOLDER;
            $render_ctx["tools"] = array();

            if(checkPermission_dashmanage())
                {
                $render_ctx["tools"][] = array(
                    "href" => generateURL(
                        "{$baseurl_short}pages/dash_tile.php",
                        array(
                            'create'            => 'true',
                            'tltype'            => 'fcthm',
                            'tlstyle'           => 'thmbs',
                            'title'             => "{$fc['name']}",
                            'freetext'          => 'true',
                            'tile_audience'     => 'false',
                            'promoted_resource' => (isset($render_ctx["images"]) ? 'true' : ""),
                            'link'              => $fc_category_url
                        )
                    ),
                    "text" => $lang["add_to_dash"]);
                }

            if(checkperm("h"))
                {
                $render_ctx["tools"][] = array(
                    "href" => generateURL("{$baseurl_short}pages/collection_share.php", array("ref" => $fc["ref"])),
                    "text" => $lang["share"]);
                }

            if(!$fc_category_has_children && collection_readable($fc['ref']))
                {
                $render_ctx["tools"][] = $tool_select;
                }

            if($enable_theme_category_edit && checkperm("t"))
                {
                $render_ctx["tools"][] = $tool_edit;
                }
            }

        if($is_smart_featured_collection)
            {
            $search = NODE_TOKEN_PREFIX . $fc["ref"];
            $render_ctx["href"] = generateURL("{$baseurl_short}pages/search.php", array("search" => $search, "resetrestypes" => "true"));

            $node_is_parent = (isset($fc["node_is_parent"]) ? $fc["node_is_parent"] : true);
            if($node_is_parent)
                {
                $render_ctx["href"] = generateURL(
                    "{$baseurl_short}pages/collections_featured.php",
                    array(
                        "smart_rtf" => $fc["resource_type_field"],
                        "smart_fc_parent" => $fc["parent"],
                    ));
                }
            $render_ctx["icon"] = ICON_FOLDER;
            $render_ctx["tools"] = array();
            }

        // Don't show the tools for external shares
        if((trim($k) != ""))
            {
            $render_ctx["tools"] = array();
            }

        render_featured_collection($render_ctx, $fc);
        }

    return;
    }


/**
* Render a featured collection (as tiles on the collections_featured.php page)
* 
* @param array $ctx Context data to allow caller code to decide rendering requirements
* @param array $fc  Featured collection data structure
* 
* @return void
*/
function render_featured_collection(array $ctx, array $fc)
    {
    if(empty($fc))
        {
        return;
        }

    global $baseurl_short, $lang, $k, $flag_new_themes, $flag_new_themes_age;

    $is_smart_featured_collection = (isset($ctx["smart"]) ? (bool) $ctx["smart"] : false);
    $full_width = (isset($ctx["full_width"]) && $ctx["full_width"]);
    $general_url_params = (isset($ctx["general_url_params"]) && is_array($ctx["general_url_params"]) ? $ctx["general_url_params"] : array());
    $show_resources_count = (isset($ctx["show_resources_count"]) ? (bool) $ctx["show_resources_count"] : false);


    $html_container_class = array("FeaturedSimplePanel", "HomePanel", "DashTile", "FeaturedSimpleTile");
    $html_container_style = array();


    // Set main featured collection URL (e.g for collections it's the !collection[ID], for categories it's for collection_featured.php)
    $html_fc_a_href = generateURL("{$baseurl_short}pages/search.php", $general_url_params, array("search" => "!collection{$fc["ref"]}"));
    $html_fc_a_href = (isset($ctx["href"]) && trim($ctx["href"]) !== "" ? $ctx["href"] : $html_fc_a_href);


    $html_contents_class = array("FeaturedSimpleTileContents");
    $html_contents_icon = (isset($ctx["icon"]) && trim($ctx["icon"]) != "" ? $ctx["icon"] : ICON_CUBE);
    $fc_display_name = strip_prefix_chars(i18n_get_collection_name($fc),"*");
            
    $html_contents_h2 = $html_contents_icon . $fc_display_name;
    $html_contents_h2_style = array();
    if(!$is_smart_featured_collection && $flag_new_themes && (time() - strtotime($fc["created"])) < (60 * 60 * 24 * $flag_new_themes_age))
        {
        $html_contents_h2 .= sprintf(' <div class="NewFlag">%s</div>', htmlspecialchars($lang['newflag']));
        }
    if($full_width)
        {
        $html_container_class[] = "FullWidth";
        $html_contents_h2_style[] = "max-width: unset;";

        $action_selection_id = "themes_action_selection{$fc["ref"]}_bottom_{$fc["ref"]}";
        
        if($show_resources_count && !$is_smart_featured_collection)
            {
            $html_contents_h2 .= sprintf(
                ' <span data-tag="resources_count" data-fc-ref="%s">%s</span>',
                htmlspecialchars($fc['ref']),
                htmlspecialchars($lang['counting_resources']));
            }
        }


    $theme_images = (isset($ctx["images"]) ? $ctx["images"] : array());
    if(!empty($theme_images))
        {
        $html_container_class[] = "FeaturedSimpleTileImage";
        $html_contents_class[] = "TileContentShadow";

        if(count($theme_images) == 1)
            {
            $theme_image_path = $theme_images[0];
            $html_container_style[] = "background: url({$theme_image_path});";
            $html_container_style[] = "background-size: cover;";
            $theme_images = array();
            }
        }


    $tools = (isset($ctx["tools"]) && is_array($ctx["tools"]) && !$full_width ? $ctx["tools"] : array());
    $html_actions_style = ['display: none;'];
    if(count($tools) > 3)
        {
        $html_actions_style[] = "height: 43px;";
        }


    // DEVELOPER NOTE: anything past this point should be set. All logic is handled above
    ?>
    <div id="FeaturedSimpleTile_<?php echo md5($fc['ref']); ?>" class="<?php echo implode(" ", $html_container_class); ?>" style="<?php echo implode(" ", $html_container_style); ?>">
        <a href="<?php echo $html_fc_a_href; ?>" onclick="return CentralSpaceLoad(this, true);" id="featured_tile_<?php echo $fc["ref"]; ?>" class="FeaturedSimpleLink">
            <div id="FeaturedSimpleTileContents_<?php echo $fc["ref"]; ?>" class="<?php echo implode(" ", $html_contents_class); ?>">
            <?php
            foreach($theme_images as $i => $theme_image)
                {
                $gap = 200 / count($theme_images);
                $space = $i * $gap;
                $style = array(
                    "left: {$space}px;",
                    "transform: rotate(" . (20 - ($i * 12)) . "deg);"
                );
                ?>
                <img src="<?php echo $theme_image; ?>" class="TileGroupImageBase" style="<?php echo implode(" ", $style); ?>">
                <?php
                }
                ?>
                <h2 style="<?php echo implode(" ", $html_contents_h2_style); ?>"><?php echo $html_contents_h2; ?></h2>
            </div>
        </a>
    <?php
    if(!empty($tools))
        {
        ?>
        <div id="FeaturedSimpleTileActions_<?php echo md5($fc['ref']); ?>" class="FeaturedSimpleTileActions" style="<?php echo implode(" ", $html_actions_style); ?>">
        <?php
        foreach($tools as $tool)
            {
            if(empty($tool))
                {
                continue;
                }

            $href = (isset($tool["href"]) && trim($tool["href"]) != "" ? $tool["href"] : "#");
            $text = $tool["text"]; // if this is missing, code is wrong somewhere else

            $tool_onclick = (isset($tool["modal_load"]) && $tool["modal_load"] ? 'return ModalLoad(this, true);' : 'return CentralSpaceLoad(this, true);');
            if(isset($tool["custom_onclick"]) && trim($tool["custom_onclick"]) != "")
                {
                $tool_onclick = $tool["custom_onclick"];
                }
            ?>
            <div class="tool">
                <a href="<?php echo $href; ?>" onclick="<?php echo $tool_onclick; ?>">
                    <span><?php echo LINK_CARET; ?><?php echo htmlspecialchars($text); ?></span>
                </a>
            </div>
            <?php
            }
            ?>
        </div><!-- End of FeaturedSimpleTileActions_<?php echo md5($fc['ref']); ?> -->
        <?php
        }
    else if($full_width && !$is_smart_featured_collection)
        {
        ?>
        <div class="ListTools">
            <div class="ActionsContainer">
                <select id="<?php echo $action_selection_id; ?>" onchange="action_onchange_<?php echo $action_selection_id; ?>(this.value);">
                    <option><?php echo htmlspecialchars($lang["actions-select"]); ?></option>
                </select>
            </div>
            <script>
            jQuery('#<?php echo $action_selection_id; ?>').bind({
                mouseenter: function(e)
                    {
                    LoadActions('themes', '<?php echo $action_selection_id; ?>', 'collection', '<?php echo $fc["ref"]; ?>');
                    }
            });
            </script>
        </div><!-- End of ListTools -->
        <?php
        }
        ?>
    </div><!-- End of FeaturedSimpleTile_<?php echo $fc["ref"]; ?>-->
    <?php
    return;
    }


/**
* Renders an option in the Permission Manager (admin_group_permissions.php page) 
* 
* @param string  $permission   Permission identifier
* @param string  $description  User friendly description of the permission
* @param boolean $reverse      Reverse the permission
* @param boolean $reload       Autosave changes done on this permission
* 
* @return void
*/
function DrawOption($permission,$description,$reverse=false,$reload=false)
    {
    global $permissions,$permissions_done;
    $checked=(in_array($permission,$permissions));
    if ($reverse) {$checked=!$checked;}
    ?>
    <input type="hidden" name="permission_<?php echo base64_encode($permission)?>" value="<?php echo ($reverse)?"reverse":"normal" ?>">
    <tr>
        <td><?php if ($reverse) {?><i><?php } ?><?php echo $permission?><?php if ($reverse) {?></i><?php } ?></td>
        <td><?php echo $description?></td>
        <td><input type="checkbox" name="checked_<?php echo base64_encode($permission) ?>" <?php 
            if ($checked) { ?> checked <?php } ?><?php if ($reload) { ?> onChange="CentralSpacePost(this.form,false);" <?php } ?>></td>
    </tr>
    <?php
    $permissions_done[]=$permission;
    }


/**
* Render featured collections options in the Permission Manager (admin_group_permissions.php page)
* 
* This function will generate and render the following permissions that target featured collection categories
*   # j[numeric ID of new collection]  - valid for FC categories at root level. These are normal permissions.
*   # -j[numeric ID of new collection] - valid for the rest of FC sub-categories. These permissions are reversed, {@see DrawOption()}!
* 
* @param array $ctx Context data to allow caller code to start from different tree levels. Supports the following
*                   properties: parent and depth
* 
* @return void
*/
function render_featured_collections_category_permissions(array $ctx)
    {
    global $lang;

    $permissions = (isset($ctx["permissions"]) && is_array($ctx["permissions"]) ? $ctx["permissions"] : array());
    $parent = (isset($ctx["parent"]) ? validate_collection_parent(array("parent" => $ctx["parent"])) : 0);
    $path_depth = (isset($ctx["depth"]) ? $ctx["depth"] : 0);
    $branch_path = (isset($ctx["branch_path"]) && is_array($ctx["branch_path"]) ? $ctx["branch_path"] : array());

    $current_depth = $path_depth;
    $current_branch_path = $branch_path;
    $reverse_permission = ($parent > 0);

    foreach(get_featured_collection_categories($parent, array("access_control" => false)) as $fc)
        {
        $branch_path = $current_branch_path;
        $branch_path[] = array(
            "ref"    => $fc["ref"],
            "name"   => $fc["name"],
            "parent" => validate_collection_parent($fc),
        );

        $fc_perm_id = (!$reverse_permission ? "" : "-") . "j{$fc["ref"]}";
        $description = sprintf("%s%s '%s'",
            ($path_depth == 0 ? "" : str_pad("", $path_depth * 7, "&mdash;") . " "),
            (!$reverse_permission ? $lang["can_see_theme_category"] : $lang["can_see_theme_sub_category"]),
            i18n_get_translated($fc["name"])
        );
        DrawOption($fc_perm_id, $description, $reverse_permission, true);

        // Root categories (ie that don't have a parent) get rendered as normal permissions. Sub-categories, get rendered
        // as reverse permissions
        debug(sprintf("render_featured_collections_category_permissions: Check if allowed to render sub-categories for FC category '%s'", $fc['ref']));
        $render_subcategories = array_reduce($branch_path, function($carry, $item) use ($permissions)
            {
            $root_node = is_null($item["parent"]);
            $perm_id = ($root_node ? "" : "-") . "j{$item["ref"]}";
            $allow_render = ($root_node ? in_array($perm_id, $permissions) : !in_array($perm_id, $permissions));
            debug(sprintf("render_featured_collections_category_permissions: For perm ID '%s': carry = %s; root_node = %s; allow_render = %s", $perm_id, json_encode($carry), json_encode($root_node), json_encode($allow_render)));

            // FALSE if at least one featured collection category parent is forbidden
            return (!is_bool($carry) ? $allow_render : $carry && $allow_render);
            }, null);
        debug("render_featured_collections_category_permissions: render_subcategories = " . json_encode($render_subcategories));
        debug("render_featured_collections_category_permissions: ");

        if($render_subcategories)
            {
            render_featured_collections_category_permissions(
                array(
                    "permissions" => $permissions,
                    "parent" => $fc["ref"],
                    "depth" => ++$path_depth,
                    "branch_path" => $branch_path,
                ));

            // Step back to initial depth level
            $path_depth = $current_depth;
            }
        }

    return;
    }

/**
 * show_upgrade_in_progress message
 *
 * @param  bool $dbstructonly - Indicates whether this is a full upgrade with migration scripts or just a check_db_structs()
 * @return void
 */
function show_upgrade_in_progress($dbstructonly=false)
    {
    global $lang;
    $message="This system is currently being upgraded by another process. Delete filestore/tmp/process_locks/* if this process has stalled." . PHP_EOL;
    if(!$dbstructonly)
        {
        $upgrade_progress_overall=get_sysvar(SYSVAR_UPGRADE_PROGRESS_OVERALL);
        $upgrade_progress_script=get_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT);
        $message.=($upgrade_progress_overall===false ? '' : $upgrade_progress_overall . PHP_EOL);
        $message.=($upgrade_progress_script===false ? '' : 'Script status: ' . $upgrade_progress_script . PHP_EOL);
        }
    if(PHP_SAPI == 'cli')
        {
        echo $message;
        }
    else
        {
        echo "<h1>{$lang["upgrade_in_progress"]}</h1>";
        echo nl2br($message);
        ?>
        <script>
        setTimeout(function()
            {
            window.location.reload(true);
            }, 5000);
        </script>
        <?php
        }
    }



/**
*  add link to mp3 preview file if resource is a wav file
* 
* @param array      $resource               - resource data
* @param int        $ref                    - resource ref
* @param string     $k                      - url param key
* @param array      $ffmpeg_audio_extensions - config var containing a list of extensions which will be ported to mp3 format for preview      
* @param string     $baseurl                - config base url
* @param array      $lang                   - array containing language strings         
* @param boolean    $use_larger_layout      - should the page use a larger resource preview layout?                        
 * 
 */

function render_audio_download_link($resource, $ref, $k, $ffmpeg_audio_extensions, $baseurl, $lang, $use_larger_layout)
{

// if resource is a .wav file and user has permissions to download then allow user also to download the mp3 preview file if available
// resources with extension in $ffmpeg_audio_extensions will always create an mp3 preview file 
    
    $path                       = get_resource_path($resource['ref'],true,"",false,"mp3");
    $resource_download_allowed  = resource_download_allowed($ref,'',$resource["resource_type"]);
    $size_info                  = array('id' => '', 'extension' => 'mp3');

    if (
        $resource['file_extension']=="wav" && in_array($resource['file_extension'], $ffmpeg_audio_extensions) && file_exists($path) && $resource_download_allowed)
        {
        $colspan = $use_larger_layout ? ' colspan="2"' : '';
        echo "<tr class=\"DownloadDBlend\"><td class=\"DownloadFileName\" $colspan><h2>" . $lang['mp3_preview_file'] . "</h2></td><td class=\"DownloadFileSize\">" . formatfilesize(filesize_unlimited($path)) . "</td>" ; 
        add_download_column($ref,$size_info, true);
        echo "</tr>";
        }   
}


/**
 * Render a table based on ResourceSpace data to include sorting by various columns
 *
 * @param  array $tabledata - This must be constructed as detailed below
 * 
 * Required elements:-
 * 
 * "class"  Optional class to add to table div
 * "headers"  - Column headings using the identifier as the index,
 *  - name - Title to display
 *  - Sortable - can column be sorted?
 * 
 * "orderbyname"    - name of variable used on page to determine orderby (used to differentiate from standard search values)
 * "orderby"        - Current order by value
 * "sortbyname"     - name of variable used on page to determine sort
 * "sort"           - Current sort
 * "defaulturl"     - Default URL to construct links
 * "params"         - Current parameters to use in URL
 * "pager"          - Pager settings 
 *  - current page
 *  - total pages
 * "data"          - Array of data to display in table, using header identifers as indexes
 *  - If "rowid" is specified this wil be used as the id attribute for the <tr> element
 *  - An additional 'tools' element can be included to add custom action icons
 *  - "class" - FontAwesome class to use for icon
 *  - "text" - title attribute
 *  - "url" - URl to link to
 *  - "modal" - (boolean) Open link in modal?
 *  - "onclick" - OnClick action to add to icon
 *  
 *   e.g.
 * 
 *   array(
 *       "class"=>"fa fa-trash",
 *       "text"=>$lang["action-delete"],
 *       "url"=>"",
 *       "modal"=>false,
 *       "onclick"=>"delete_job(" . $jobs[$n]["ref"] . ");return false;"
 *       );
 *
 *   array(
 *       "class"=>"fa fa-info",
 *       "text"=>$lang["job_details"],
 *       "url"=>generateurl($baseurl . "/pages/job_details.php",array("job" => $jobs[$n]["ref"])),
 *       "modal"=>true,
 *       );
 * 
 * @return void
 */
function render_table($tabledata)
    {
    ?>
    <div class="TablePagerHolder"><?php
    if(isset($tabledata["pager"]))
        {
        $pageroptions = array(
            "curpage" => $tabledata["pager"]["current"],
            "totalpages" => $tabledata["pager"]["total"],
            "per_page" => isset($tabledata["pager"]["per_page"]) ? $tabledata["pager"]["per_page"] : $default_perpage,
            "break" => isset($tabledata["pager"]["break"]) ? $tabledata["pager"]["break"] : true,
            "scrolltotop" => isset($tabledata["pager"]["scrolltotop"]) ? $tabledata["pager"]["scrolltotop"] : true,
            "url" => $tabledata["defaulturl"],
            "url_params" => $tabledata["params"],
            );
        pager(true, true,$pageroptions);
        }?>
    </div><?php

    echo "<div class='Listview " . (isset($tabledata["class"]) ? $tabledata["class"] : "") . "'>\n";
    echo "<table border='0' cellspacing='0' cellpadding='0' class='ListviewStyle'>\n";
    echo "<tbody><tr class='ListviewTitleStyle'>\n";
    echo "<th id='RowAlertStatus' style='width: 10px;'></th>";
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
    echo "</tr>\n"; // End of table header row

    if(count($tabledata["data"]) == 0)
        {
        echo "<tr><td colspan='" . (strval(count($tabledata["headers"]))) . "'>No results found<td></tr>\n";
        }
    else
        {
        foreach($tabledata["data"] as $rowdata)
            {
            $rowid = isset($rowdata["rowid"]) ? " id = '" . $rowdata["rowid"]  . "'" : "";
            echo "<tr" . $rowid . ">";

            if(isset($rowdata['alerticon']))
                {
                echo "<td><i class='" . $rowdata['alerticon'] . "'></i></td>";
                }
            else
                {
                echo "<td></td>"; 
                }
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
                            echo "<a aria-hidden='true' href='" . htmlspecialchars($toolitem["url"]) . "' onclick='";
                            if(isset($toolitem["onclick"]))
                                {
                                echo $toolitem["onclick"];
                                }
                            else
                                {
                                echo "return " . ($toolitem["modal"] ? "Modal" : "return CentralSpace") . "Load(this,true);";
                                }
                            echo "' title='" . htmlspecialchars($toolitem["text"]) . "'><span class='" . htmlspecialchars($toolitem["icon"]) . "'></span></a>";
                            }
                        echo "</div>";
                        }
                    else
                        {
                        echo (isset($headerdetails["html"]) && (bool)$headerdetails["html"]) ? strip_tags_and_attributes($rowdata[$header], array("a"), array("href", "target")) : htmlspecialchars($rowdata[$header]);
                        }
                    echo "</td>";
                    }
                else
                    {
                    echo "<td></td>\n";
                    }
                }
            echo "</tr>\n";
            }
        }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    }

/**
 * Render multimensional array or object to display within table cells
 *
 * @param  array $array
 * @return void
 */
function render_array_in_table_cells($array)
    {
    foreach($array as $name => $value)
        {
        echo '<table class="TableArray">';
        echo "<tr><td width='50%'>";
        echo htmlspecialchars($name);
        echo "</td><td width='50%'>";

        if(is_array($value))
            {
            render_array_in_table_cells($value);
            }
        elseif(is_bool($value))
            {
            echo ($value ? "TRUE" : "FALSE");
            }
        else
            {
            echo htmlspecialchars($value);
            }
                
        echo "</td></tr>";
        echo "</table>";
        }
    }

/**
* Render the top page error style version
* 
* @param string $err_msg Error message
* 
* @return void
*/
function render_top_page_error_style(string $err_msg)
    {
    if(trim($err_msg) == "")
        {
        return;
        }

    ?><div class="PageInformal"><?php echo htmlspecialchars($err_msg); ?></div><?php
    return;
    }


/**
* Render a FormHelper. These are used in forms, to provide extra information to the user to a question.
* 
* @param string $txt Help text
* @param string $id  Div ID
* @param array  $ctx Contextual data
*/
function render_question_form_helper(string $txt, string $id, array $ctx)
    {
    $txt = trim($txt);
    $id = trim($id);

    if($txt === '' || $id === '')
        {
        return;
        }

    $ctx_class = (isset($ctx['class']) && is_array($ctx['class']) ? $ctx['class'] : array());
    $ctx_style = (isset($ctx['style']) && is_string($ctx['style']) ? $ctx['style'] : ''); # Use a class if possible!


    $class = htmlspecialchars(join(' ', array_merge(array('FormHelp'), $ctx_class)));
    $style = (trim($ctx_style) !== '' ? sprintf(' style="%s"', htmlspecialchars($ctx_style)) : '');
    ?>
    <div id="help_<?php echo htmlspecialchars($id); ?>" class="<?php echo $class; ?>"<?php echo $style; ?>>
        <div class="FormHelpInner"><?php echo htmlspecialchars($txt); ?></div>
    </div>
    <?php
    return;
    }


/**
* Render an HTML hidden input
* 
* @param string $name  Input name
* @param string $value Input value
*/
function render_hidden_input(string $name, string $value)
    {
    ?>
    <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
    <?php
    return;
    }


function render_workflow_state_question($current=null, $checkaccess=true)
    {
    global $additional_archive_states, $lang;
    $statusoptions = array();
    for ($n=-2;$n<=3;$n++)
        {
        if (!$checkaccess || checkperm("e" . $n) || $n==$current)
            {
            $statusoptions[$n] =  isset($lang["status" . $n]) ?  $lang["status" . $n] : $n;
            }
        }
    foreach ($additional_archive_states as $additional_archive_state)
        {
        if (!$checkaccess || checkperm("e" . $additional_archive_state) || $additional_archive_state==$current)
            {
            $statusoptions[$additional_archive_state] =  isset($lang["status" . $additional_archive_state]) ?  $lang["status" . $additional_archive_state] : $additional_archive_state;
            }
        }
    
    render_dropdown_question($lang["status"], "share_status", $statusoptions, $current, " class=\"stdWidth\"");
    }

function render_share_password_question($blank=true)
    {
    global $lang;
    ?>
    <div class="Question">
    <label for="sharepassword"><?php echo htmlspecialchars($lang["share-set-password"]) ?></label>
    <input type="password" id="sharepassword" name="sharepassword" maxlength="40" class="stdwidth" value="<?php echo $blank ? "" : $lang["password_unchanged"]; ?>">
    <span class="fa fa-fw fa-eye infield-icon" onclick="togglePassword('sharepassword');"></span>
    <script>

    function togglePassword(pwdelement)
        {
        input = jQuery('#' + pwdelement);
        if (input.attr("type") == "password")
            {
            input.attr("type", "text");
            }
        else
            {
            input.attr("type", "password");
            }
        }
    var passInput="";
    var passState="(unchanged)";
    var passHistory="";
    function pclick(id) 
        {
        // Set to password mode
        document.getElementById(id).type="password";
        document.getElementById(id).value=passState;
        document.getElementById(id).select();
        }
    function pblur(id) 
        {
        // Copy keyed input other than bracketed placeholders to hidden password
        passInput = document.getElementById(id).value;
        if(passInput!="(unchanged)" && passInput!="(changed)") 
            {
            document.getElementById("sharepassword").value=passInput; 
            passState="(changed)";
            }
        // Return to text mode showing the appropriate bracketed placeholder
        document.getElementById(id).value=passState;
        document.getElementById(id).type="text";
        }
    </script>
    </div>
    <?php
    }
