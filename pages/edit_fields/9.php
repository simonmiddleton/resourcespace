<?php
/* -------- Dynamic Keywords List ----------- */ 
global $baseurl, $pagename, $edit_autosave;

if(!isset($selected_nodes))
    {
    $selected_nodes = array();

    if(isset($searched_nodes) && is_array($searched_nodes))
        {
        $selected_nodes = $searched_nodes;
        }
    }

// Decide when the user can add new keywords to a dynamic keywords list
$readonly = false;
if('search_advanced' == $pagename || checkperm('bdk' . $field['ref']))
    {
    $readonly = true;
    }

$is_search                        = (isset($is_search) ? $is_search : false);
$hidden_input_elements_name       = ($is_search ? 'nodes_searched' : 'nodes');
$js_keywords_suffix               = "{$hidden_input_elements_name}_{$field['ref']}";
$add_searched_nodes_function_call = '';
?>
<div class="dynamickeywords ui-front">
    <input id="<?php echo $name; ?>_selector" type="text" <?php if ($pagename=="search_advanced") { ?> class="SearchWidth" <?php } else {?>  class="stdwidth" <?php } ?>
           name="<?php echo $name; ?>_selector"
           value="<?php echo $lang['starttypingkeyword']; ?>"
           onFocus="
                <?php
                if($pagename=="edit")
                    {
                    echo "ShowHelp(" . $field["ref"] . ");";
                    }
                    ?>

                if(this.value=='<?php echo $lang["starttypingkeyword"]; ?>')
                    {
                    this.value='';
                    }
            "
           onBlur="
                <?php
                if($pagename=="edit")
                    {
                    echo "HideHelp(" . $field["ref"] . ");";
                    }
                    ?>

                if(this.value=='')
                    {
                    this.value='<?php echo $lang["starttypingkeyword"]; ?>'
                    };

                if(typeof(UpdateResultCount) == 'function' && this.value!='' && this.value!='<?php echo $lang["starttypingkeyword"]; ?>')
                    {
                    this.value='<?php echo $lang["starttypingkeyword"]; ?>';
                    }" />
<?php
foreach($field['nodes'] as $node)
    {
    // Deal with previously searched nodes
    if(!in_array($node['ref'], $selected_nodes) && !(isset($user_set_values[$field['ref']]) && in_array($node['ref'],$user_set_values[$field['ref']])))
        {
        continue;
        }

    $i18n_node_name = addslashes(i18n_get_translated($node['name']));

    $add_searched_nodes_function_call .= "addKeyword_{$js_keywords_suffix}('{$node['ref']}', '{$i18n_node_name}');";
    }
    ?>
    <div id="<?php echo $name; ?>_selected" class="keywordsselected"></div>
</div>
<div class="clearerleft"></div>
<script>
// Associative array with index being the node ID
// Example: Keywords_nodes_3 = [232: United Kingdom, 233: United States]
// or Keywords_nodes_searched_3 = [232: United Kingdom, 233: United States]
var Keywords_<?php echo $js_keywords_suffix; ?> = [];


function updateSelectedKeywords_<?php echo $js_keywords_suffix; ?>(user_action)
    {
    var html                  = '';
    var hidden_input_elements = '';

    Keywords_<?php echo $js_keywords_suffix; ?>.forEach(function (item, index)
        {
        hidden_input_elements += '<input id="<?php echo $hidden_input_elements_name; ?>_' + index + '" type="hidden" name="<?php echo $hidden_input_elements_name; ?>[<?php echo $field["ref"]; ?>][]" value="' + index + '">';

        html += '<div class="keywordselected">' + Keywords_<?php echo $js_keywords_suffix; ?>[index];
        html += '<a href="#"';
        html += ' onClick="removeKeyword_<?php echo $js_keywords_suffix; ?>(\'' + escape(index) + '\', true); return false;"';
        html += '>x</a></div>';
        
        });

    // Update DOM with all our recent changes
    var existing_hiddent_input_elements = document.getElementsByName('<?php echo $hidden_input_elements_name; ?>[<?php echo $field["ref"]; ?>][]');
    while(existing_hiddent_input_elements[0])
        {
        existing_hiddent_input_elements[0].parentNode.removeChild(existing_hiddent_input_elements[0]);
        }
    document.getElementById('<?php echo $name; ?>_selected').insertAdjacentHTML('beforeBegin', hidden_input_elements);
    document.getElementById('<?php echo $name; ?>_selected').innerHTML = html;

    // Update the result counter, if the function is available (e.g. on Advanced Search).
    if(typeof(UpdateResultCount) == 'function')
        {
        UpdateResultCount();
        }

    <?php
    if($edit_autosave)
        {
        ?>
        if(user_action)
            {
            AutoSave('<?php echo $field["ref"]; ?>');
            }
            <?php
        }
        ?>

    // Trigger an event so we can chain actions once we've changed a dynamic keyword
    jQuery('[name="<?php echo $hidden_input_elements_name; ?>[<?php echo $field["ref"]; ?>][]"]').each(function(index, element)
        {
       	jQuery('#CentralSpace').trigger('dynamicKeywordChanged',[{node: element.value}]);
        });
    }

function removeKeyword_<?php echo $js_keywords_suffix; ?>(node_id, user_action)
    {
    var old_keywords = Keywords_<?php echo $js_keywords_suffix; ?>;


    Keywords_<?php echo $js_keywords_suffix; ?> = [];

	old_keywords.forEach(function(item, index)
        {
        if(index != node_id)
            {
            Keywords_<?php echo $js_keywords_suffix; ?>[index] = item;
            }
        });

    updateSelectedKeywords_<?php echo $js_keywords_suffix; ?>(user_action);

    // Trigger an event so we can chain actions once we've changed a dynamic keyword
	jQuery('#CentralSpace').trigger('dynamicKeywordChanged',[{node: node_id}]);
    }


function addKeyword_<?php echo $js_keywords_suffix; ?>(node_id, keyword)
    {
    removeKeyword_<?php echo $js_keywords_suffix; ?>(node_id, false);

    Keywords_<?php echo $js_keywords_suffix; ?>[node_id] = keyword;
    }


function selectKeyword_<?php echo $js_keywords_suffix; ?>(event, ui)
    {
    var found_suggested = true;
    var keyword         = ui.item.label;
    var node_id         = ui.item.value;

    if(keyword.substring(0, <?php echo mb_strlen($lang['createnewentryfor'], 'UTF-8'); ?>) == '<?php echo $lang["createnewentryfor"]; ?>')
        {
        keyword = keyword.substring(<?php echo mb_strlen($lang['createnewentryfor'], 'UTF-8') + 1; ?>);

        // Add the word.
        args = {
            field: '<?php echo $field["ref"]; ?>',
            keyword: keyword,
            <?php echo generateAjaxToken("selectKeyword_{$js_keywords_suffix}"); ?>
            };

        jQuery.ajax({
            type    : 'POST',
            url     : '<?php echo $baseurl?>/pages/edit_fields/9_ajax/add_keyword.php',
            data    : args,
            dataType: 'json',
            async: false,
            success : function(result) {
                if(typeof result.new_node_id === 'undefined')
                    {
                    styledalert('Error', 'Could not determine new node ID!');
                    return false;
                    }

                node_id = result.new_node_id;
                }
            });        
        }
    else if(keyword.substring(0, <?php echo mb_strlen($lang['noentryexists'], 'UTF-8') ?>) == '<?php echo $lang["noentryexists"]; ?>')
        {
        document.getElementById('<?php echo $name; ?>_selector').value = '';

        found_suggested = false;
        }

    if(found_suggested)
        {
        addKeyword_<?php echo $js_keywords_suffix; ?>(node_id, keyword);

        updateSelectedKeywords_<?php echo $js_keywords_suffix; ?>(true);

        document.getElementById('<?php echo $name; ?>_selector').value = '';
        }

    return false;
    }


jQuery('#<?php echo $name; ?>_selector').autocomplete(
    {
    source : "<?php echo $baseurl; ?>/pages/edit_fields/9_ajax/suggest_keywords.php?field=<?php echo $field['ref']; ?>&readonly=<?php echo $readonly; ?>",
    select : selectKeyword_<?php echo $js_keywords_suffix; ?>
    });

// prevent return in autocomplete field from submitting entire form
// we want the user to explicitly choose what they want to do
jQuery('#<?php echo $name; ?>_selector').keydown(function(event)
    {
    var keyCode = event.keyCode ? event.keyCode : event.which;
    if(keyCode == 13)
        {
        event.stopPropagation();
        event.preventDefault();

        return false;
        }
    });

<?php
echo $add_searched_nodes_function_call;
?>

updateSelectedKeywords_<?php echo $js_keywords_suffix; ?>(false);
</script>
