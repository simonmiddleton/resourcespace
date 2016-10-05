<?php
/* -------- Dynamic Keywords List ----------- */ 
global $baseurl, $pagename, $edit_autosave;

// Decide when the user can add new keywords to a dynamic keywords list
$readonly = false;
if('search_advanced' == $pagename || checkperm('bdk' . $field['ref']))
    {
    $readonly = true;
    }

// In case we let new lines in our value, make sure to clean it for Dynamic keywords
if(strpos($value, "\r\n") !== false)
	{
	$value = str_replace("\r\n", ' ', $value);
	}

$set                              = trim($value);
$add_searched_nodes_function_call = '';
?>
<div class="dynamickeywords ui-front">
    <input id="<?php echo $name ?>_selector" type="text" <?php if ($pagename=="search_advanced") { ?> class="SearchWidth" <?php } else {?>  class="stdwidth" <?php } ?>
           name="<?php echo $name ?>_selector"
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
    if(!in_array($node['ref'], $searched_nodes))
        {
        continue;
        }

    $i18n_node_name = i18n_get_translated($node['name']);

    $add_searched_nodes_function_call .= "addKeyword_{$name}('{$node['ref']}', '{$i18n_node_name}');";
    }
    ?>
    <div id="<?php echo $name?>_selected" class="keywordsselected"></div>
</div>
<div class="clearerleft"></div>
<script>
// Associative array with index being the node ID
// Example: [232: United Kingdom, 233: United States]
var Keywords_<?php echo $name ?> = [];


function updateSelectedKeywords_<?php echo $name ?>(user_action)
    {
    var html                  = '';
    var hidden_input_elements = '';

    Keywords_<?php echo $name ?>.forEach(function(item, index)
        {
        hidden_input_elements += '<input id="nodes_searched_' + index + '" type="hidden" name="nodes_searched[<?php echo $field["ref"]; ?>][]" value="' + index + '">';

        html += '<a href="#"';
        html += ' onClick="removeKeyword_<?php echo $name; ?>(\'' + escape(index) + '\', true); return false;"';
        html += '>[ x ]</a>&nbsp;' + Keywords_<?php echo $name; ?>[index] + '<br/>';
        });

    // Update DOM with all our recent changes
    var existing_hiddent_input_elements = document.getElementsByName('nodes_searched[<?php echo $field["ref"]; ?>][]');
    while(existing_hiddent_input_elements[0])
        {
        existing_hiddent_input_elements[0].parentNode.removeChild(existing_hiddent_input_elements[0]);
        }
    document.getElementById('<?php echo $name?>_selected').insertAdjacentHTML('beforeBegin', hidden_input_elements);
    document.getElementById('<?php echo $name?>_selected').innerHTML = html;

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
    }


function removeKeyword_<?php echo $name ?>(node_id, user_action)
    {
    var old_keywords = Keywords_<?php echo $name ?>;

    Keywords_<?php echo $name ?> = [];

    old_keywords.forEach(function(item, index)
        {
        if(index != node_id)
            {
            Keywords_<?php echo $name ?>[index] = item;
            }
        });

    updateSelectedKeywords_<?php echo $name ?>(user_action);
    }


function addKeyword_<?php echo $name ?>(node_id, keyword)
    {
    removeKeyword_<?php echo $name ?>(node_id, false);

    Keywords_<?php echo $name ?>[node_id] = keyword;
    }


function selectKeyword_<?php echo $name ?>(event, ui)
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
            keyword: keyword
            };

        jQuery.ajax({
            type    : 'POST',
            url     : '<?php echo $baseurl?>/pages/edit_fields/9_ajax/add_keyword.php',
            data    : args,
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
        document.getElementById('<?php echo $name ?>_selector').value = '';

        found_suggested = false;
        }

    if(found_suggested)
        {
        addKeyword_<?php echo $name ?>(node_id, keyword);

        updateSelectedKeywords_<?php echo $name ?>(true);

        document.getElementById('<?php echo $name ?>_selector').value = '';
        }

    return false;
    }


jQuery('#<?php echo $name?>_selector').autocomplete(
    {
    source : "<?php echo $baseurl; ?>/pages/edit_fields/9_ajax/suggest_keywords.php?field=<?php echo $field['ref']; ?>&readonly=<?php echo $readonly; ?>",
    select : selectKeyword_<?php echo $name; ?>
    });

// prevent return in autocomplete field from submitting entire form
// we want the user to explicitly choose what they want to do
jQuery('#<?php echo $name?>_selector').keydown(function(event)
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

updateSelectedKeywords_<?php echo $name ?>(false);
</script>