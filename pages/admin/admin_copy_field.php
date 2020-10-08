<?php
include '../../include/db.php';
include '../../include/authenticate.php';
if(!checkperm('a'))
    {
    exit('Permission denied.');
    }

$ref    = getvalescaped('ref', '');
$copied = '';
$title  = sql_value("SELECT title AS `value` FROM resource_type_field WHERE ref = '{$ref}'", '', "schema");

# Perform copy
if (getval("saveform","")!="" && enforcePostRequest(false))
	{
	$sync=getvalescaped("sync","");
	if ($sync==1) {$sync="'" . $ref . "'";} else {$sync="null";}
	
	sql_query("insert into resource_type_field
	(
		name,
		title,
		type,
		order_by,
		keywords_index,
		partial_index,
		resource_type,
		resource_column,
		display_field,
		use_for_similar,
		iptc_equiv,
		display_template,
		required,
		smart_theme_name,
		exiftool_field,
		advanced_search,
		simple_search,
		help_text,
		display_as_dropdown,
		external_user_access,
		autocomplete_macro,
		hide_when_uploading,
		hide_when_restricted,
		value_filter,
		exiftool_filter,
		omit_when_copying,
		tooltip_text,
		regexp_filter,
                display_condition,
                onchange_macro,
		sync_field
	)
	
	select
	
		name,
		title,
		type,
		9999,
		keywords_index,
		partial_index,
		'" . getvalescaped("resource_type","") . "',
		resource_column,
		display_field,
		use_for_similar,
		iptc_equiv,
		display_template,
		required,
		smart_theme_name,
		exiftool_field,
		advanced_search,
		simple_search,
		help_text,
		display_as_dropdown,
		external_user_access,
		autocomplete_macro,
		hide_when_uploading,
		hide_when_restricted,
		value_filter,
		exiftool_filter,
		omit_when_copying,
		tooltip_text,
		regexp_filter,
                display_condition,
                onchange_macro,
		" . $sync . "
		from resource_type_field where ref='$ref'
		");

	$copied = sql_insert_id();

    // Copy nodes if resource type is a fixed list type:
    copy_resource_type_field_nodes($ref, $copied);

	log_activity(null, LOG_CODE_COPIED, "{$lang['copy_of']} {$ref}", 'resource_type_field', '', $copied);
	redirect($baseurl_short . "pages/admin/admin_resource_type_field_edit.php?ref=" . $copied);
	}
        
if ($copied!='')
    {
    $saved_text=str_replace('?', $copied, $lang['copy-completed']);    
    }

include "../../include/header.php";

?>
<div class="BasicsBox">
<?php
$links_trail = array(
    array(
        'title' => $lang["systemsetup"],
        'href'  => $baseurl_short . "pages/admin/admin_home.php"
    ),
    array(
        'title' => $lang["admin_resource_type_fields"],
        'href'  => $baseurl_short . "pages/admin/admin_resource_type_fields.php"
    ),
    array(
        'title' => $lang["admin_resource_type_field"] . ": " . i18n_get_translated($title),
        'href'  => $baseurl_short . "pages/admin/admin_resource_type_field_edit.php?ref=" . $ref
    ),
    array(
        'title' => $lang['copy-field'] . ": " . i18n_get_translated($title),
        'help'  => "managing-metadata"
    )
);

renderBreadcrumbs($links_trail);

if(isset($saved_text))
    {
    ?>
    <div class="PageInformal"><?php echo $saved_text; ?></div>
    <?php
    }
    ?>
    <form method="post" action="admin_copy_field.php">
        <?php generateFormToken("admin_copy_field"); ?>
        <input type="hidden" name="saveform" value="true">
        <input type="hidden" name="ref" value="<?php echo $ref; ?>">
        <p><?php echo $lang['copy-to-resource-type']; ?><br />
            <select name="resource_type" style="width:100%;">
        <?php
        $types = get_resource_types();
        for($n = 0; $n < count($types); $n++)
            {
            ?>
            <option value="<?php echo $types[$n]['ref']; ?>"><?php echo $types[$n]['name']; ?></option>
            <?php
            }
            ?>
            </select>
        </p>

    <p>
    <?php echo $lang['synchronise-changes-with-this-field']; ?><br />
        <select name="sync" style="width:100%;">
            <option value="0"><?php echo $lang['no']; ?></option>
            <option value="1"><?php echo $lang['yes']; ?></option>
        </select>
    </p>
    <p align="right">
        <input type="submit" name="copy" value="<?php echo $lang['copy']; ?>" style="width:100px;">
    </p>
    </form>

</div><!--End of BasicsBox -->
<?php
include "../../include/footer.php";