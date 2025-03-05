<?php
include "../../include/boot.php";
include "../../include/authenticate.php";

if (!checkperm("a")) {
    exit("Permission denied");
}

if (getval('ajax', false) !== false && getval('posting', false) == true) {
    $usage_option = getval('ref', 0, true);
    $action       = trim(getval('tool_action', ''));
    
    $response = ['success' => false];

    if (trim($action) === '') {
        echo json_encode($response);
        exit();
    } else {
        switch ($action) {
            case 'order_up':
                $success = usage_options_reorder($usage_option, true);
                break;
            case 'order_down':
                $success = usage_options_reorder($usage_option, false);
                break;
            case 'delete':
                $success = usage_option_delete($usage_option);
                break;
            case 'enable':
                $success = toggle_usage_option($usage_option, true);
                break;
            case 'disable':
                $success = toggle_usage_option($usage_option, false);
                break;
        }
    }

    if (!$success) {
        $error = $lang['manage_usage-error'];
    }
} elseif (trim(getval('name', '')) !== '') {
    if (!create_usage_option(getval('name', ''))) {
        $error = $lang['manage_usage-error'];
    }
}

$form_base_url = $baseurl . '/pages/team/team_manage_usage_options.php';
$usage_options = get_usage_options(true, true);

$links_trail = [
    [
        'title' => $lang["systemsetup"],
        'href'  => $baseurl_short . "pages/admin/admin_home.php",
        'menu' =>  true
    ],
    [
        'title' => $lang["admin_resource_type_fields"],
        'help'  => "resourceadmin/manage-usage-options"
    ]
];

include "../../include/header.php";
?>

<div class="BasicsBox">
    <h1><?php echo escape($lang["usage"]); ?></h1>
    <?php 
        renderBreadcrumbs($links_trail); 
        //render_help_link("usage-history");
    ?>
    <script>
        jQuery(document).ready(function() 
        {
            let error = "<?php echo escape($error ?? ""); ?>";
            if (error !== "") {
                styledalert("<?php echo escape($lang['error']); ?>", error);
            }

            jQuery('.usage_tools').on('change', function(){
                let selected_option = jQuery(this);
                if (selected_option.val() === 'delete') {
                    if(!confirm("<?php echo escape($lang["confirm-deletion"]); ?>")) {
                        return;
                    }
                }
                jQuery('#usage_update_form input[name=\'ref\']').val(selected_option.attr('data-option-id'));
                jQuery('#usage_update_form input[name=\'tool_action\']').val(selected_option.val());
                CentralSpacePost(document.getElementById('usage_update_form'), false, false);
            });

            jQuery('[name="enabled"]').on('change', function(){
                let checkbox = jQuery(this);
                jQuery('#usage_update_form input[name=\'ref\']').val(checkbox.attr('data-option-id'));
                if (checkbox.prop('checked') == true) {
                    jQuery('#usage_update_form input[name=\'tool_action\']').val('enable');
                } else {
                    jQuery('#usage_update_form input[name=\'tool_action\']').val('disable');
                }
                CentralSpacePost(document.getElementById('usage_update_form'), false, false);
            })
        });
    </script>
    <form id="usage_update_form" method="post" action="<?php echo $form_base_url; ?>">
        <?php generateFormToken("manage_usage"); ?>
        <input type="hidden" name="ref" value="">
        <input type="hidden" name="tool_action" value="">
    </form>

    <div class="Listview">
        <table class="ListviewStyle">
            <tr class="ListviewTitleStyle">
                <th class="ref"><?php echo escape($lang["id"]); ?></th>
                <th class="name"><?php echo escape($lang["usage-option_name"]); ?></th>
                <th><?php echo escape($lang["usage-enabled"]); ?></th>
                <th class="ListTools"><?php echo escape($lang['actions']); ?></th>
            </tr>
            <?php

                foreach ($usage_options as $option) {
                    ?>
                        <tr>
                            <td class="ref"><?php echo (int) $option['ref']; ?></td>
                            <td class="name"><?php echo escape($option['name']); ?></td>
                            <td><input type="checkbox" name="enabled" data-option-id="<?php echo (int) $option['ref']; ?>" <?php echo $option['enabled'] == 1 ? 'checked' : ''; ?>></td>
                            <td class="ListTools">
                                <select class="usage_tools" data-option-id="<?php echo (int) $option['ref']; ?>">
                                    <option selected><?php echo escape($lang["actions-select"]); ?></option>
                                    <option value="order_up"><?php echo escape($lang['action-move-up']); ?></option>
                                    <option value="order_down"><?php echo escape($lang['action-move-down']); ?></option>
                                    <option value="delete"><?php echo escape($lang["action-delete"]); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php
                }
            ?>
        </table>
    </div>
</div>

<form id="new_usage_option" method="post" action="<?php echo $form_base_url; ?>">
    <?php generateFormToken("new_usage"); ?>
    <div class="Question">
        <label for="name"><?php echo escape($lang['manage_usage_option_new']); ?></label>
        <div class="tickset">
            <div class="Inline"><input name="name" type="text" maxlength="50" class="shrtwidth"></div>
            <div class="Inline"><input name="submit" type="submit" onClick="return centralSpacePost(document.getElementById('new_usage_option'), true, true);"
                value="<?php echo escape($lang['manage_usage_option_create']); ?>"></div>
        </div>
    </div>
</form>


<?php
include "../../include/footer.php";
?>
