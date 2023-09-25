<?php
#
# Annotate setup page
#
// Do the include and authorization checking ritual -- don't change this section.
include '../../../include/db.php';
include_once "../include/annotate_functions.php";
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

$plugin_name = 'annotate';
if(!in_array($plugin_name, $plugins))
    {
    plugin_activate_for_setup($plugin_name);
    }

// Specify the name of this plugin and the heading to display for the page.
$plugin_name = 'annotate';
$plugin_page_heading = $lang['annotate_configuration'];

// Build the $page_def array of descriptions of each configuration variable the plugin uses.
$page_def[] = config_add_text_list_input('annotate_ext_exclude', $lang['extensions_to_exclude']);
$page_def[] = config_add_multi_rtype_select('annotate_rt_exclude', $lang['resource_types_to_exclude']);
$page_def[] = config_add_single_select('annotate_font', $lang['annotate_font'], array('helvetica', 'dejavusanscondensed'), false);
$page_def[] = config_add_boolean_select('annotate_debug', $lang['annotatedebug']);
$page_def[] = config_add_boolean_select('annotate_public_view', $lang['annotate_public_view']);
$page_def[] = config_add_boolean_select('annotate_show_author', $lang['annotate_show_author']);
$page_def[] = config_add_boolean_select('annotate_pdf_output', $lang["annotate_pdf_output"]);
$page_def[] = config_add_boolean_select('annotate_pdf_output_only_annotated', $lang["annotate_pdf_output_only_annotated"]);
$page_def[] = config_add_multi_group_select('annotate_admin_edit_access', $lang["annotate_admin_edit_access"]);
$page_def[] = config_add_single_ftype_select("annotate_resource_type_field", $lang["admin_resource_type_field"],300,false,[FIELD_TYPE_DYNAMIC_KEYWORDS_LIST]); 

// Do the page generation ritual -- don't change this section.
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
if(empty($annotate_resource_type_field) || $annotate_resource_type_field == 0)
    {
    ?>
    <div class="PageInformal"><?php echo $lang['annotate_metadatafield_error']?></div>
    <script>jQuery(document).ready(function(){jQuery('#annotate_resource_type_field').addClass('highlighted');});</script>
    <?php
    }
config_gen_setup_html($page_def, $plugin_name, null, $plugin_page_heading);
include '../../../include/footer.php';
