<?php

# Store key variables to revert later so that we don't interfere with values that still need to be processed by search.php

use Montala\ResourceSpace\UserInterfaceComponents\Icon;

$stored_restypes = (isset($restypes) ? $restypes : '');
$stored_search = (isset($search) ? $search : '');
$stored_quicksearch = (isset($quicksearch) ? $quicksearch : '');
$stored_category_tree_add_parents = $category_tree_add_parents;

$ssearchhiddenfields = isset($_COOKIE['ssearchhiddenfields']) ? $_COOKIE['ssearchhiddenfields'] : "";
$ssearchhiddenfieldsarray = explode(',', $ssearchhiddenfields);

if ($simple_search_reset_after_search) {
    $restypes    = '';
    $search      = '';
    $quicksearch = '';
} else {
    # pull values from cookies if necessary, for non-search pages where this info hasn't been submitted
    if (!isset($restypes)) {
        $restypes = isset($_COOKIE['restypes']) ? $_COOKIE['restypes'] : "";
    }
    if (!isset($search) || false !== strpos($search, '!')) {
        $quicksearch = (isset($_COOKIE['search']) ? $_COOKIE['search'] : '');
    } else {
        $quicksearch = $search;
    }
}

$origsearch = $quicksearch;

if ($basic_simple_search) {
    $restypes    = '';
}

if ($hide_search_resource_types) {
    $restypes = '';
}

if (!isset($internal_share_access)) {
    // Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
    $internal_share_access = internal_share_access();
}

# Load the basic search fields, so we know which to strip from the search string
$fields = get_simple_search_fields();
$simple_fields = array();

for ($n = 0; $n < count($fields); $n++) {
    $simple_fields[] = $fields[$n]["name"];
}

# Also strip date related fields.
$simple_fields[] = "basicyear";
$simple_fields[] = "basicmonth";
$simple_fields[] = "basicday";

# Check for fields with the same short name and add to an array used for deduplication.
$f = array();
$duplicate_fields = array();

for ($n = 0; $n < count($fields); $n++) {
    if (in_array($fields[$n]["name"], $f)) {
        $duplicate_fields[] = $fields[$n]["name"];
    }
    $f[] = $fields[$n]["name"];
}

# Process all keywords, putting set fieldname/value pairs into an associative array ready for setting later.
# Also build a quicksearch string.
$quicksearch    = refine_searchstring($quicksearch);

if (preg_match('/^[^-][^\\s]+$/', $quicksearch)) {
    $keywords   = [$quicksearch];
} else {
    $keywords   = split_keywords($quicksearch, false, false, false, false, true);
}

$set_fields     = array();
$simple         = array();
$searched_nodes = array();
$initial_tags = array();

# Check if any negative node searches in any of the keywords,
# if there is bypass the node expansion/text replacement/OR syntax generation
$negative_node_search_check = preg_match("/@{2}!/", $quicksearch);

for ($n = 0; $n < count($keywords); $n++) {
    if (trim($keywords[$n]) != "") {
        $quoted_string = (substr($keywords[$n], 0, 1) == "\""  || substr($keywords[$n], 0, 2) == "-\"" ) && substr($keywords[$n], -1, 1) == "\"";
        if (!$quoted_string && strpos($keywords[$n], ":") !== false && substr($keywords[$n], 0, 11) != "!properties") {
            $s = explode(":", $keywords[$n]);
            if (isset($set_fields[$s[0]])) {
                $set_fields[$s[0]] .= " " . $s[1];
            } else {
                $set_fields[$s[0]] = $s[1];
                $i = $n + 1;
                while (
                    $i < count($keywords)
                    && strpos($keywords[$i], ":") === false
                    && strpos($keywords[$i], NODE_TOKEN_PREFIX) === false
                ) {
                    $set_fields[$s[0]] .= " " . $keywords[$i];
                    $i++;
                }
                $n = $i - 1;
            }
            if (!in_array($s[0], $simple_fields)) {
                $simple[] = trim($keywords[$n]);
                $initial_tags[] = trim($keywords[$n]);
            }
        }

        // Nodes search
        elseif (strpos($keywords[$n], NODE_TOKEN_PREFIX) !== false  && 0 === $negative_node_search_check) {
            $nodes = resolve_nodes_from_string($keywords[$n]);
            foreach ($nodes as $node) {
                $searched_nodes[] = $node;
            }

            $searched_nodes = array_unique($searched_nodes);
            $simpletext_count = count($simple);
            $initial_tag_count = count($initial_tags);
            foreach ($searched_nodes as $searched_node_index => $searched_node) {
                $node = array();

                if (!get_node($searched_node, $node)) {
                    continue;
                }

                $field_index = array_search($node['resource_type_field'], array_column($fields, 'ref'));

                if (false === $field_index) { // Node is not from a simple search field
                    $fieldsearchterm = rebuild_specific_field_search_from_node($node);

                    if (strpos(" ", $fieldsearchterm) !== false) {
                        $fieldsearchterm = "\"" . $fieldsearchterm . "\"";
                    }

                    if (!isset($all_fields)) {
                        $all_fields = get_resource_type_fields();
                    }

                    $all_fields_index = array_search($node['resource_type_field'], array_column($all_fields, 'ref'));
                    $field_name = $all_fields[$all_fields_index]["name"];
                    if (isset($last_field_name) && $last_field_name == $field_name && (($all_fields[$all_fields_index]["type"] == FIELD_TYPE_CHECK_BOX_LIST && !$checkbox_and) ||  ($all_fields[$all_fields_index]["type"] == FIELD_TYPE_DYNAMIC_KEYWORDS_LIST))) {
                        // Append in order to construct the field:value1;value2 syntax used for an OR search in the same field
                        $fieldsearchterm = substr($fieldsearchterm, strpos($fieldsearchterm, ":") + 1);

                        if (!isset($simple[$simpletext_count])) {
                            $simple[$simpletext_count] = "";
                        }
                        $simple[$simpletext_count] .= ";" . $fieldsearchterm;
                        if (!isset($initial_tags[$initial_tag_count])) {
                            $initial_tags[$initial_tag_count] = "";
                        }
                        $initial_tags[$initial_tag_count] .= ";" . $fieldsearchterm;

                        unset($searched_nodes[$searched_node_index]);
                    } else {
                        $simple[$simpletext_count] = $fieldsearchterm;
                        $initial_tags[] = $fieldsearchterm;

                        unset($searched_nodes[$searched_node_index]);
                    }

                    // Store the field name so we can check for ORs on same field
                    $last_field_name = $field_name;
                    continue;
                }

                $searched_field = $fields[$field_index];

                // We already have a field on search bar so remove this keyword from search box
                if ($searched_field['simple_search']) {
                    $quicksearch = str_replace(NODE_TOKEN_PREFIX . $searched_node, '', $quicksearch);
                }
            }
        } else {
            # Plain text (non field) search.
            $simple[] = trim($keywords[$n]);
            $initial_tags[] = trim($keywords[$n]);
        }
    }
}

# Set the text search box to the stripped value, and condense consecutive wildcards
$simple = array_unique($simple);
$initial_tags = array_unique($initial_tags);
$quicksearch = join(" ", trim_array($simple));
$quicksearch = preg_replace('/\*+/', '*', $quicksearch);

# Set the predefined date fields
$found_year = "";
if (isset($set_fields["basicyear"])) {
    $found_year = $set_fields["basicyear"];
}

$found_month = "";
if (isset($set_fields["basicmonth"])) {
    $found_month = $set_fields["basicmonth"];
}

$found_day = "";
if (isset($set_fields["basicday"])) {
    $found_day = $set_fields["basicday"];
}

$selected_search_tab = getval("selected_search_tab", "search");

// The search DOM element is replaced when calling the ResourceSpace.Modules.Header.reloadSearchBar() via search.php in
// order to update it when carrying out searches outside the filter panel ?>
<search id="SearchBox" class="header-search-field" aria-label="<?php echo escape(text('simplesearch')); ?>">
    <script>var categoryTreeChecksArray = [];</script>
    <?php if (checkperm("s") && (!isset($k) || $k == "" || $internal_share_access)) { ?>
    <form
        id="simple_search_form"
        method="post"
        action="<?php echo $baseurl; ?>/pages/search.php"
        onsubmit="return CentralSpacePost(this,true);"
    >
        <?php generateFormToken("simple_search_form"); ?>
        <div class="input-wrapper">
            <?php render_icon_wrapper_component(Icon::Search); ?>
            <input 
                type="search"
                name="search"
                id="ssearchbox"
                value="<?php echo escape(stripslashes($quicksearch)); ?>"
            >
            <button
                type="button"
                class="icon-sliders-horizontal"
                aria-label="<?php echo escape(text('search_filter_panel_toggle')); ?>"
                aria-expanded="false"
                aria-controls="search-filters-panel"
            ></button>
        </div>
        <section id="search-filters-panel" hidden>
            <div id="SearchBoxPanel">
                <div id="search-panel-main">
                    <input
                        id="ssearchhiddenfields"
                        name="ssearchhiddenfields"
                        type="hidden"
                        value="<?php echo escape($ssearchhiddenfields); ?>"
                    >

                    <script>
                        <?php
                        $autocomplete_src = '';
                        if ($autocomplete_search) {
                            $autocomplete_src = "{$baseurl}/pages/ajax/autocomplete_search.php";
                        }
                        ?>
                        jQuery(document).ready(function () {
                            jQuery('#ssearchbox').autocomplete({
                                source: "<?php echo $autocomplete_src; ?>",
                                minLength: 3,
                            });
                            
                            <?php if (!$basic_simple_search) { ?>
                                // Ensure any previously hidden search fields remain hidden
                                SimpleSearchFieldsHideOrShow();
                            <?php } ?>
                        });
                    </script>

                    <?php
                    $types = get_resource_types("", true, false, true);

                    $simpleSearchFieldsAreHidden = hook("simplesearchfieldsarehidden");
                    hook("aftersearchbox");

                    if (!$basic_simple_search && !$hide_search_resource_types) {
                        ?>
                        <input type="hidden" name="resetrestypes" value="yes">
                        <?php
                        // WIP on replacing the old tick boxes for RTs with a multi select dropdown
                        render_dropdown_question(
                            label: $lang['resourcetypes'],
                            inputname: 'field_test_select2[]',
                            options: array_merge(
                                ['allRTs' => $lang['all_resource_types']],
                                array_filter(
                                    array_column($types, 'name', 'ref'),
                                    static fn($V, $ref) => !in_array($ref, $hide_resource_types),
                                    ARRAY_FILTER_USE_BOTH
                                )
                            ),
                            current: 'allRTs',
                            // Style needed for the width - @see https://select2.org/appearance#container-width
                            extra: ' multiple="multiple" onchange="SimpleSearchFieldsHideOrShow(true);" style="width: 100%;"',
                            ctx: [
                                'div_class' => ['field-input'],
                                'no_div_class_question' => true,
                                'input_class' => '',
                            ]
                        );
                        // todo: drop the below code once done with the WIP above
                        ?>
                        <div class="field-input" <?php echo $simpleSearchFieldsAreHidden ? 'style="display:none;"' : ''; ?>>
                            <?php
                            $rt = is_array($restypes) ? $restypes : explode(",", $restypes);
                            $clear_function = "SetCookie('search','');SetCookie('restypes','');SetCookie('ssearchhiddenfields','');SetCookie('saved_offset','');SetCookie('saved_archive','');";
                            hook('clearsearchcookies');

                            # Render resource type checkbox inputs
                            for ($n = 0; $n < count($types); $n++) {
                                if (in_array($types[$n]['ref'], $hide_resource_types)) {
                                    continue;
                                }

                                $tickBoxClass = "tick";
                                $inputBoxClass = "tickbox";
                                $resetTickAllCall = "";
                                $clear_function .= "jQuery('#TickBox" . $types[$n]["ref"] . "').prop('checked',true);";
                                ?>
                                <div class="<?php echo $tickBoxClass; ?>">
                                    <input
                                        class="<?php echo $inputBoxClass; ?>"
                                        id="TickBox<?php echo (int) $types[$n]["ref"]; ?>" 
                                        type="checkbox"
                                        value="yes"
                                        name="resource<?php echo (int) $types[$n]["ref"]; ?>"  
                                        <?php if (((count($rt) == 1) && ($rt[0] == "")) || ($restypes == "Global") || (in_array($types[$n]["ref"], $rt))) { ?>
                                            checked="checked"
                                        <?php } ?> 
                                        onclick="SimpleSearchFieldsHideOrShow(true);<?php echo $resetTickAllCall; ?>"
                                    >
                                    <label for="TickBox<?php echo (int) $types[$n]["ref"]; ?>">
                                        <i class="icon-<?php echo escape($types[$n]["icon"] != "" ? $types[$n]["icon"] : LUCIDE_EXTENSIONS["default"]); ?>"></i>    
                                        <?php echo "&nbsp;" . escape($types[$n]["name"]); ?>
                                    </label>
                                </div>

                                <?php
                            }
                            # End of rendering for resource type checkbox inputs
                            ?>
                            
                            <div class="spacer"></div>
                            <?php
                            if ($clear_button_unchecks_collections) {
                                $colcheck = "false";
                            } else {
                                $colcheck = "true";
                            }

                            if ($search_includes_themes) { ?>
                                <div class="tick">
                                    <input
                                        class="tickboxcoll"
                                        id="TickBoxFeaturedCollections"
                                        type="checkbox"
                                        name="resourceFeaturedCollections"
                                        value="yes"
                                        <?php if (((count($rt) == 1) && ($rt[0] == "")) || (in_array("FeaturedCollections", $rt))) { ?>
                                            checked="checked"
                                        <?php } ?>
                                        onClick="SimpleSearchFieldsHideOrShow(true);"
                                    />
                                    <label for="TickBoxFeaturedCollections">&nbsp;<?php echo escape($lang["findcollectionthemes"]); ?></label>
                                </div>
                                <?php
                                $clear_function .= "jQuery('#TickBoxFeaturedCollections').prop('checked'," . $colcheck . ");";
                            }
                    } elseif ($restypes == '') {
                        # We still need a way to pass restypes based on simple search settings or things like search crumbs will be incorrect
                        if ($search_includes_resources) {
                            for ($t = 0; $t < count($types); $t++) {
                                $restypes .= ($restypes == '' ? '' : ',') . $types[$t]['ref'];
                            }
                        }

                        if ($search_includes_themes) {
                            $restypes .= ($restypes == '' ? '' : ',') . "FeaturedCollections";
                        }
                        ?>

                        <input type="hidden" name="restypes" id="restypes" value="<?php echo escape($restypes); ?>" />
                        <?php
                    }

                    if (!$basic_simple_search && !$hide_search_resource_types) {
                        ?>
                        </div>
                        <?php
                    }

                    hook("searchfiltertop");

                    if (!$basic_simple_search) {
                        // Include simple search items (if any)
                        global $clear_function, $simple_search_show_dynamic_as_dropdown;

                        $optionfields = array();
                        $rendered_names = array();
                        $rendered_refs = array();
                        $has_value = array();

                        for ($n = 0; $n < count($fields); $n++) {
                            $render = true;
                            # Render duplicate fields only once.
                            if (in_array($fields[$n]["name"], $duplicate_fields) && in_array($fields[$n]["name"], $rendered_names)) {
                                $render = false;
                            }

                            if ($render) {
                                $rendered_names[] = $fields[$n]["name"];

                                # Fetch current value
                                $value = '';

                                if (isset($set_fields[$fields[$n]["name"]])) {
                                    $value = $set_fields[$fields[$n]["name"]];
                                }

                                $fields[$n]['value'] = $value;

                                if ($value !== '') {
                                    $has_value[] = $fields[$n]['ref'];
                                }

                                render_search_field($fields[$n], $fields, $value, false, 'SearchWidth', true, array(), $searched_nodes, false, $simpleSearchFieldsAreHidden);
                            }
                        }
                        ?>

                        <script type="text/javascript">
                            function FilterBasicSearchOptions(clickedfield,resourcetypes) {
                                if (typeof resourcetypes !== 'undefined' && resourcetypes != 0) {
                                    resourcetypes = resourcetypes.toString().split(",");
                                    // When selecting resource type specific fields, automatically untick all other resource types, because selecting something from this field will never produce resources from the other resource types.
                                    allselected = false;

                                    if (jQuery('#rttickallres').prop('checked')) {
                                        allselected = true;
                                        // Always untick the Tick All box
                                        if (jQuery('#rttickallres')) {
                                            jQuery('#rttickallres').prop('checked', false);
                                        }
                                    }

                                    <?php for ($n = 0; $n < count($types); $n++) { ?>
                                        if (resourcetypes.indexOf('<?php echo (int) $types[$n]["ref"]; ?>') == -1) {
                                            jQuery("#TickBox<?php echo (int) $types[$n]["ref"]; ?>").prop('checked', false);
                                        } else if (allselected) {
                                            jQuery("#TickBox<?php echo (int) $types[$n]["ref"]; ?>").prop('checked', true);
                                        }
                                    <?php } ?>

                                    // Hide any fields now no longer relevant.  
                                    SimpleSearchFieldsHideOrShow(false);
                                }
                            }

                            function SimpleSearchFieldsHideOrShow(resetvalues) {
                                // ImageBank is selection has already dealt with hiding of elements, so just reset the searchfields
                                if (jQuery("#SearchImageBanks :selected").text().length > 0)  { 
                                    SimpleSearchFieldsResetValues(true); // true = include globals
                                    return; 
                                }

                                if (resetvalues) {
                                    console.debug("Resetting values");
                                    SimpleSearchFieldsResetValues(false); // false = exclude globals
                                }

                                var ssearchhiddenfields = [];
                                ssearchhiddenfields.length = 0;
                                document.getElementById('ssearchhiddenfields').value='';

                                <?php
                                # Show or hide each searchfield depending on whether the resource type for this field is selected
                                # Exclude global fields
                                for ($n = 0; $n < count($fields); $n++) {
                                    # Duplicate fields are skipped
                                    # Fields subjected to display conditioning are skipped
                                    if (
                                        !in_array($fields[$n]["name"], $duplicate_fields)
                                        && (
                                            empty($simple_search_display_condition)
                                            || (
                                                !empty($simple_search_display_condition)
                                                && !in_array($fields[$n]['ref'], $simple_search_display_condition)
                                            )
                                        )
                                        && $fields[$n]["global"] != 1
                                    ) {
                                        // Process resource type checkboxes, whether checked or unchecked
                                        $hideconditions =  [];
                                        $showconditions =  [];
                                        $notypeconditions = [];
                                        // Check if resource types are valid for field
                                        $validrestypes = explode(",", (string)$fields[$n]["resource_types"]);
                                        $invalidrestypes = array_diff(array_column($types, "ref"), array_merge($hide_resource_types, $validrestypes));

                                        // Don't hide if any of the valid resource types are checked AND none of the invalid types are checked
                                        foreach ($validrestypes as $validrestype) {
                                            $showconditions[] = "jQuery('#TickBox" . (int) $validrestype . "').prop('checked') == false";
                                        }

                                        foreach ($invalidrestypes as $invalidrestype) {
                                            $hideconditions[] = "jQuery('#TickBox" . (int) $invalidrestype . "').prop('checked')";
                                        }

                                        foreach (array_diff(array_column($types, "ref"), $hide_resource_types) as $displayedrestype) {
                                            // Check to field if no resource types are selected
                                            $notypeconditions[] = "jQuery('#TickBox" . (int) $displayedrestype . "').prop('checked') == false";
                                        }

                                        $hidecondition = " if ((" .  implode(" && ", $showconditions) . ") " . (count($hideconditions) > 0 ? "|| " : "") . implode(" || ", $hideconditions) . " || (" . implode(" && ", $notypeconditions) . ")) {";
                                        echo "// Start of hide field code\n" . $hidecondition;
                                        ?>

                                        // Process unchecked element
                                        ssearchfieldname = 'simplesearch_<?php echo $fields[$n]["ref"]; ?>';
                                        document.getElementById(ssearchfieldname).style.display = 'none';

                                        // Search field is hidden, so add it to the list of hidden search field names for use when searchbar is redisplayed
                                        ssearchhiddenfields.push(ssearchfieldname)

                                        // Also deselect it.
                                        <?php
                                        switch ($fields[$n]['type']) {
                                            case FIELD_TYPE_DATE_AND_OPTIONAL_TIME:
                                            case FIELD_TYPE_EXPIRY_DATE:
                                            case FIELD_TYPE_DATE:
                                                ?>
                                                document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>-y').value = '';
                                                document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>-m').value = '';
                                                <?php
                                                if ($searchbyday) {
                                                    ?>
                                                    document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>-d').value = '';
                                                    <?php
                                                }
                                                break;
                                            case FIELD_TYPE_CATEGORY_TREE:
                                                ?>
                                                document.getElementById('field_<?php echo escape($fields[$n]["name"]) ?>').value = '';
                                                <?php
                                                break;
                                            case FIELD_TYPE_CHECK_BOX_LIST:
                                            case FIELD_TYPE_DROP_DOWN_LIST:
                                            case FIELD_TYPE_RADIO_BUTTONS:
                                                ?>
                                                jQuery('select[name="nodes_searched[<?php echo $fields[$n]["ref"]; ?>]"]').val('');
                                                <?php
                                                break;
                                            default:
                                                if ($fields[$n]['field_constraint'] == 1) { ?>
                                                    document.getElementById('field_<?php echo escape($fields[$n]["name"]) ?>').value = '';  
                                                <?php } else { ?>
                                                    document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>').value = '';
                                                <?php }
                                        }
                                        ?>
                                    } else {
                                        // Process checked element
                                        <?php
                                        if (in_array($fields[$n]['type'], array(2,3)) || ($fields[$n]["type"] == 9 && $simple_search_show_dynamic_as_dropdown)) {
                                            ?>
                                            document.getElementById('field_<?php echo $fields[$n]["ref"]; ?>').disabled=false;
                                            <?php
                                        }
                                        ?>

                                        ssearchfieldname = 'simplesearch_<?php echo $fields[$n]["ref"]; ?>';
                                        document.getElementById(ssearchfieldname).style.display = '';

                                        // Search field is no longer hidden, so remove it from the list of hidden search field names for use when searchbar is redisplayed
                                        ssindex = ssearchhiddenfields.indexOf(ssearchfieldname);
                                        if (ssindex > -1)  {
                                            ssearchhiddenfield.splice(ssindex, 1);
                                        }
                                    }
                                    <?php
                                }
                            }
                            ?>

                            // Save the hidden field names for use when searchbar is redisplayed
                            ssearchhiddenfieldsstring = ssearchhiddenfields.join(',');
                            document.getElementById('ssearchhiddenfields').value = ssearchhiddenfieldsstring;
                            SetCookie('ssearchhiddenfields', ssearchhiddenfieldsstring);
                            console.debug("SETCOOKIE SSEARCHHIDDENFIELDS=" + ssearchhiddenfieldsstring);
                            }

                            function SimpleSearchFieldsResetValues(includeglobals) {
                                <?php
                                # Reset the data in each of the searchfields including global
                                for ($n = 0; $n < count($fields); $n++) {
                                    if ($fields[$n]["global"] == 1) {
                                        $resetcondition = " if (includeglobals) {";
                                    } else {
                                        $resetconditions =  [];
                                        $showconditions =  [];
                                        // Check if resource types are valid for field
                                        $validrestypes = explode(",", (string)$fields[$n]["resource_types"]);
                                        $invalidrestypes = array_diff(array_column($types, "ref"), array_merge($hide_resource_types, $validrestypes));

                                        // Don't reset if any of the valid resource types are checked AND none of the invalid types are checked
                                        foreach ($validrestypes as $validrestype) {
                                            $showconditions[] = "jQuery('#TickBox" . (int) $validrestype . "').prop('checked') == false";
                                        }

                                        foreach ($invalidrestypes as $invalidrestype) {
                                            $resetconditions[] = "jQuery('#TickBox" . (int) $invalidrestype . "').prop('checked')";
                                        }
                                        $resetcondition = " if ((" .  implode(" && ", $showconditions) . ") " . (count($resetconditions) > 0 ? "|| " : "")  . implode(" || ", $resetconditions) . ") {";
                                    }

                                    echo "// Start of reset field code\n" . $resetcondition;

                                    # Duplicate fields are skipped
                                    # Fields subjected to display conditioning are skipped
                                    if (
                                        !in_array($fields[$n]["name"], $duplicate_fields)
                                        && (
                                            empty($simple_search_display_condition)
                                            || (
                                                !empty($simple_search_display_condition)
                                                && !in_array($fields[$n]['ref'], $simple_search_display_condition)
                                            )
                                        )
                                    ) {
                                        switch ($fields[$n]['type']) {
                                            case FIELD_TYPE_CATEGORY_TREE:
                                                ?>
                                                var ref = <?php echo escape($fields[$n]["ref"]) ?>;
                                                jQuery('#search_tree_' + ref).jstree({
                                                    'core' : {
                                                        'themes' : {
                                                            'name' : 'default-dark',
                                                            'icons': false
                                                        }
                                                    }
                                                }).deselect_all();

                                                /* Remove the hidden inputs */
                                                var elements = document.getElementsByName('nodes_searched[' + ref + ']');
                                                while (elements[0]) {
                                                    elements[0].parentNode.removeChild(elements[0]);
                                                }

                                                /* Update status box */
                                                var node_statusbox = document.getElementById('nodes_searched_' + ref + '_statusbox');
                                                while (node_statusbox.lastChild) {
                                                    node_statusbox.removeChild(node_statusbox.lastChild);
                                                }
                                                
                                                jQuery('.search_tree_' + ref + '_nodes').remove();
                                                <?php
                                                break;
                                            case FIELD_TYPE_DATE_AND_OPTIONAL_TIME:
                                            case FIELD_TYPE_EXPIRY_DATE:
                                            case FIELD_TYPE_DATE:
                                                ?>
                                                document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>-y').value='';
                                                document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>-m').value='';
                                                <?php
                                                if ($searchbyday) {
                                                    ?>
                                                    document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>-d').value='';
                                                    <?php
                                                }
                                                break;
                                            case FIELD_TYPE_CHECK_BOX_LIST:
                                            case FIELD_TYPE_DROP_DOWN_LIST:
                                            case FIELD_TYPE_RADIO_BUTTONS:
                                                ?>
                                                console.debug("Clearing field <?php echo $fields[$n]["ref"]; ?>"); 
                                                jQuery('select[name="nodes_searched[<?php echo $fields[$n]["ref"]; ?>]"]').val('');
                                                <?php
                                                break;
                                            default:
                                                if ($fields[$n]['field_constraint'] == 1) { ?>
                                                    document.getElementById('field_<?php echo escape($fields[$n]["name"]) ?>').value = '';  
                                                <?php } else { ?>
                                                    document.getElementById('field_<?php echo escape($fields[$n]["ref"]) ?>').value = '';
                                                <?php }
                                        }
                                    }
                                    
                                    echo "} // End of reset field condition\n";
                                }
                                ?>
                            }
                        </script>
        
                        <div id="basicdate" class="field-input" <?php echo $simpleSearchFieldsAreHidden ? 'style="display:none;"' : ''; ?>>
                            <?php if ($simple_search_date) { ?>
                                <label for="basicyear"><?php echo escape($lang["bydate"]); ?></label>
                                <select id="basicyear" name="basicyear" class="SearchWidthHalf" title="<?php echo escape($lang['year']); ?>" aria-label="<?php echo escape($lang['year']); ?>">
                                    <option selected="selected" value=""><?php echo escape($lang["anyyear"]); ?></option>
                                    <?php
                                    $y = date("Y");
                                    $y += $maxyear_extends_current;
                                    for ($n = $y; $n >= $minyear; $n--) {
                                        ?>
                                        <option <?php echo ($n == $found_year) ? 'selected' : ''; ?>><?php echo $n; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select> 
                                <select id="basicmonth" name="basicmonth" class="SearchWidthHalf SearchWidthRight" title="<?php echo escape($lang['month']);?>" aria-label="<?php echo escape($lang['month']); ?>">
                                    <option selected="selected" value=""><?php echo escape($lang["anymonth"]) ?></option>
                                    <?php
                                    for ($n = 1; $n <= 12; $n++) {
                                        $m = str_pad($n, 2, "0", STR_PAD_LEFT);
                                        ?>
                                        <option <?php echo ($n == $found_month) ? 'selected' : ''; ?> value="<?php echo $m; ?>">
                                            <?php echo escape($lang["months_list"][$n - 1]); ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <?php if ($searchbyday) { ?>
                                    <select id="basicday" name="basicday" class="SearchWidthHalf" title="<?php echo escape($lang['day']);?>">
                                        <option selected="selected" value=""><?php echo escape($lang["anyday"]); ?></option>
                                        <?php
                                        for ($n = 1; $n <= 31; $n++) {
                                            $m = str_pad($n, 2, "0", STR_PAD_LEFT);
                                            ?>
                                            <option <?php echo ($n == $found_day) ? 'selected' : ''; ?> value="<?php echo $m; ?>"><?php echo $m; ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                <?php }
                        ?>
                        </div>
                        <?php
                        }

                        if (isset($resourceid_simple_search) && $resourceid_simple_search) {
                            ?>
                            <div class="field-input">
                                <label for="searchresourceid"><?php echo escape($lang["resourceid"]); ?></label>
                                <input id="searchresourceid" name="searchresourceid" type="text" value="" />
                            </div>
                            <?php
                        }
                        ?>
                        <script type="text/javascript">
                            function ResetTicks() {
                                <?php echo $clear_function; ?>

                                const date_range_fields = document.querySelectorAll('.header-search-field #search-filters-panel .date-range-search select');
                                date_range_fields.forEach(f => f.selectedIndex = 0);
                            }
                        </script>
        
                        <?php
                    }
                ?>
                </div>
                <div id="simplesearchbuttons">
                    <div class="search-actions-primary">
                        <input name="Submit" id="searchbutton" class="searchbutton" type="submit" value="<?php echo escape($lang['searchbutton']); ?>" onclick="SimpleSearchFieldsHideOrShow();">
                        <a href="#" onclick="<?php
                            if ($basic_simple_search) {
                                echo "document.getElementById('ssearchbox').value='';";
                            } else {
                                printf(
                                    'unsetCookie(\'search_form_submit\', baseurl_short);
                                    document.getElementById(\'ssearchbox\').value=\'\';
                                    %s
                                    %s
                                    %s
                                    ResetTicks(); SimpleSearchFieldsHideOrShow();
                                    return false;',
                                    $simple_search_date ? "document.getElementById('basicyear').value='';document.getElementById('basicmonth').value='';" : '',
                                    $searchbyday && $simple_search_date ? "document.getElementById('basicday').value='';" : '',
                                    $resourceid_simple_search ? "document.getElementById('searchresourceid').value='';" : ''
                                );
                            }
                        ?>">
                            <?php render_icon_wrapper_component(Icon::CircleX); ?>
                            <span><?php echo escape($lang["clearbutton"]); ?></span>
                        </a>
                    </div>
                    <div class="search-actions-secondary">
                <?php
                hook("searchbarbeforebottomlinks");

                if (!$disable_geocoding) {
                    ?>
                    <a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl; ?>/pages/geo_search.php">
                        <?php render_icon_wrapper_component(Icon::Globe); ?>
                        <span><?php echo escape($lang["geographicsearch"]); ?></span>
                    </a>
                    <?php
                }
                
                if (!$advancedsearch_disabled) {
                    ?>
                    <a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl; ?>/pages/search_advanced.php">
                        <?php render_icon_wrapper_component(Icon::FunnelPlus); ?>
                        <span><?php echo escape($lang["gotoadvancedsearch"]); ?></span>
                    </a>
                    <?php
                }
                
                hook("searchbarafterbuttons");
                
                if ($view_new_material) {
                    ?>
                    <a onclick="return CentralSpaceLoad(this,true);" href="<?php echo generateURL("{$baseurl}/pages/search.php", ['search' => "!last{$recent_search_quantity}"]); ?>">
                        <?php render_icon_wrapper_component(Icon::Clock); ?>
                        <span><?php echo escape($lang["viewnewmaterial"]); ?></span>
                    </a>
                    <?php
                }
                ?>
                    </div>
                </div>
            </div>
            <button type="button" class="<?php echo Icon::X->value; ?>" aria-label="<?php echo escape(text('close')); ?>"></button>
        </section>
    </form>
    <?php
    }
    ?>
</search>

<?php
# Restore original values that may have been affected by processsing so the search page still draws correctly with the current search.
$restypes = $stored_restypes;
$search = $stored_search;
$quicksearch = $stored_quicksearch;
$category_tree_add_parents = $stored_category_tree_add_parents;
