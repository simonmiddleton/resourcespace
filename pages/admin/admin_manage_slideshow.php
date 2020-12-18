<?php
include '../../include/db.php';
include '../../include/authenticate.php';
if(!checkperm('a'))
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Permission denied.');
    }
include '../../include/admin_functions.php';
include '../../include/slideshow_functions.php';

$slideshow_files = get_slideshow_files_data();

$ajax         = getvalescaped('ajax', '');
$action       = getvalescaped('action', '');
$slideshow_id = getvalescaped('slideshow_id', null, true);
$manageurl = "{$baseurl}/pages/admin/admin_manage_slideshow.php";

/* Re-order */
if(
    'true' === $ajax
    && ('moveup' === $action || 'movedown' === $action)
    && !is_null($slideshow_id)
    && enforcePostRequest($ajax)
)
    {
    $response['sibling']          = null;
    $response['is_first_sibling'] = false;
    $response['is_last_sibling']  = false;

    $allow_reorder = false;

    $slideshow_id_index = array_search($slideshow_id, array_column($slideshow_files, 'ref'));
    if($slideshow_id_index === false)
        {
        http_response_code(500);
        $response['error']   = "{$lang["error-failed-to-move"]} {$lang['slideshow-image']} #{$slideshow_id}";
        $response['success'] = false;

        echo json_encode($response);
        exit();
        }

    // Set current slideshow entry to the entry which has the id to be moved
    reset($slideshow_files);
    while (current($slideshow_files) !== $slideshow_files[$slideshow_id_index])
        {
        next($slideshow_files);
        }

    if (count($slideshow_files) > 1)
        {
        // Based on current pointer and direction of movement we can find the "to" element
        switch ($action)
            {
            case 'moveup':
                prev($slideshow_files);
                $to = key($slideshow_files);

                reset($slideshow_files);
                $response['is_first_sibling'] = ($slideshow_files[$to] == current($slideshow_files));

                $allow_reorder = true;   
                break;

            case 'movedown':
                next($slideshow_files);
                $to = key($slideshow_files);

                $response['is_last_sibling'] = ($slideshow_files[$to] === end($slideshow_files));

                $allow_reorder = true;
                break;
            }
        }

    if($allow_reorder && reorder_slideshow_images($slideshow_files[$slideshow_id_index], $slideshow_files[$to]))
        {
        $response['sibling'] = $slideshow_files[$to]["ref"];
        }

    echo json_encode($response);
    exit();
    }

/* Delete */
if('true' === $ajax && 'delete' === $action && !is_null($slideshow_id) && enforcePostRequest($ajax))
    {
    $response['error']   = '';
    $response['success'] = true;

    $slideshow_id_index = array_search($slideshow_id, array_column($slideshow_files, 'ref'));
    if($slideshow_id_index !== false)
        {
        $slideshow_file_info = $slideshow_files[$slideshow_id_index];
        }
    else
        {
        $slideshow_file_info = array();

        http_response_code(500);
        $response['error']   = "{$lang['error-failed-to-delete']} {$lang['slideshow-image']} #{$slideshow_id}";
        $response['success'] = false;
        }

    if(!empty($slideshow_file_info) && !delete_slideshow($slideshow_file_info['ref']))
        {
        http_response_code(500);
        $response['error']   = "{$lang['error-failed-to-delete']} '{$slideshow_file_info['file_path']}'";
        $response['success'] = false;
        }

    echo json_encode($response);
    exit();
    }

/* 
Set slideshow flags
===================
Available options:
 - homepage_show
 - featured_collections_show
 - login_show
*/
if($ajax === 'true' && $action == 'set_flag' && enforcePostRequest($ajax))
    {
    $slideshow_id_index = array_search($slideshow_id, array_column($slideshow_files, 'ref'));
    if($slideshow_id_index !== false)
        {
        $slideshow = $slideshow_files[$slideshow_id_index];
        }

    $update_status = false;
    $flag = getval('flag', '');
    $value = getval('value', false, true);

    if($value !== false && $flag != '')
        {
        $slideshow[$flag] = $value;

        $update_status = set_slideshow(
            $slideshow_id,
            $slideshow['resource_ref'],
            $slideshow['homepage_show'],
            $slideshow['featured_collections_show'],
            $slideshow['login_show']);
        }

    if($update_status !== false)
        {
        http_response_code(200);
        exit();
        }

    http_response_code(400);
    exit();
    }

if('true' === $ajax && getval("static","")!="")
    {
    if(getval("static","")=="true")
        {
        set_config_option(null, 'static_slideshow_image', true);
        }
    else
        {
        set_config_option(null, 'static_slideshow_image', false);       
        }
    }
    
include '../../include/header.php';


?>
<style>
button:disabled,
button[disabled]{
  color: #666666;
}
</style>
<div class="BasicsBox">
    <?php
    $links_trail = array(
        array(
            'title' => $lang["systemsetup"],
            'href'  => $baseurl_short . "pages/admin/admin_home.php"
        ),
        array(
            'title' => $lang["manage_slideshow"]
        )
    );

    renderBreadcrumbs($links_trail);
    ?>

    <p><?php echo $lang['manage-slideshow-instructions']; render_help_link("resourceadmin/homepage-slideshow");?></p>
    <div class="Listview">
        <table class="ListviewStyle" border="0" cellspacing="0" cellpadding="0">
            <tbody>
                <tr class="ListviewTitleStyle">
                    <td><?php echo $lang["preview"]; ?></td>
                    <td><?php echo $lang["home_page"]; ?></td>
                    <td><?php echo $lang["theme"]; ?></td>
                    <td><?php echo $lang["login_word"]; ?></td>
                    <td><?php echo $lang["tools"]; ?></td>
                </tr>
            <?php
            foreach($slideshow_files as $slideshow_index => $slideshow_file_info)
                {
                $moveup_disabled = '';
                $movedown_disabled = '';
                if($slideshow_index == 0 || count($slideshow_files) == 1)
                    {
                    $moveup_disabled = ' disabled';
                    }

                if(($slideshow_index == (count($slideshow_files) - 1)) || count($slideshow_files) == 1)
                    {
                    $movedown_disabled = ' disabled';
                    }

                $delete_btn_disabled = '';
                if(count($slideshow_files) == 1)
                    {
                    $delete_btn_disabled = ' disabled';
                    }

                $homepage_show = ($slideshow_file_info['homepage_show'] == 1 ? 'checked' : '');
                $featured_collections_show = ($slideshow_file_info['featured_collections_show'] == 1 ? 'checked' : '');
                $login_show = ($slideshow_file_info['login_show'] == 1 ? 'checked' : '');
                ?>
                <tr id="slideshow_<?php echo $slideshow_file_info["ref"]; ?>">
                    <td>
                    <?php
                    if(isset($slideshow_file_info['link']))
                        {
                        ?>
                        <a href="<?php echo $slideshow_file_info['link']; ?>" onclick="return ModalLoad(this, true);">
                            <img id="slideshow_img_<?php echo $slideshow_file_info["ref"]; ?>"
                                 src="<?php echo $slideshow_file_info['file_url']; ?>"
                                 alt="Slideshow Image <?php echo $slideshow_file_info["ref"]; ?>"
                                 width="150"
                                 height="80">
                         </a>
                        <?php
                        }
                    else
                        {
                        ?>
                        <img id="slideshow_img_<?php echo $slideshow_file_info["ref"]; ?>"
                             src="<?php echo $slideshow_file_info['file_url']; ?>"
                             alt="Slideshow Image <?php echo $slideshow_file_info["ref"]; ?>"
                             width="150"
                             height="80">
                        <?php
                        }
                    ?>
                    </td>
                    <td>
                        <input type="checkbox"
                               name="homepage_show"
                               value="1"
                               onclick="SetSlideshowFlag(this, <?php echo $slideshow_file_info['ref']; ?>);"
                               <?php echo $homepage_show; ?>>
                    </td>
                    <td>
                        <input type="checkbox"
                               name="featured_collections_show"
                               value="1"
                               onclick="SetSlideshowFlag(this, <?php echo $slideshow_file_info['ref']; ?>);"
                               <?php echo $featured_collections_show; ?>>
                    </td>
                    <td>
                        <input type="checkbox"
                               name="login_show"
                               value="1"
                               onclick="SetSlideshowFlag(this, <?php echo $slideshow_file_info['ref']; ?>);"
                               <?php echo $login_show; ?>>
                    </td>
                    <td>
                        <button id="slideshow_<?php echo $slideshow_file_info['ref']; ?>_moveup"
                                type="submit" slideMoveUpButton
                                onclick="ReorderSlideshowImage(<?php echo $slideshow_file_info['ref']; ?>, 'moveup');"
                                <?php echo $moveup_disabled; ?>><?php echo $lang['action-move-up']; ?></button>
                        <button id="slideshow_<?php echo $slideshow_file_info['ref']; ?>_movedown"
                                type="submit" slideMoveDownButton 
                                onclick="ReorderSlideshowImage(<?php echo $slideshow_file_info['ref']; ?>, 'movedown');"
                                <?php echo $movedown_disabled; ?>><?php echo $lang['action-move-down']; ?></button>
                        <?php hook('render_replace_button_for_manage_slideshow', '', array($slideshow_file_info['ref'], $slideshow_file_info)); ?>
                        <button id="slideshow_<?php echo $slideshow_file_info['ref']; ?>_delete"
                                type="submit" onclick="DeleteSlideshowImage(<?php echo $slideshow_file_info['ref']; ?>);"
                                <?php echo $delete_btn_disabled; ?>><?php echo $lang['action-delete']; ?></button>
                        <?php hook('render_replace_slideshow_form_for_manage_slideshow', '', array($slideshow_file_info['ref'], $slideshow_files)); ?>
                    </td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
<?php
if($slideshow_big)
    {?>
    <div id="slideshow_static_image" class="Question">
        <label>
        <?php echo $lang["slideshow_use_static_image"]; ?>    
        </label>
        <input type="checkbox" name="slideshow_static_image" id="slideshow_static_image_checkbox" <?php if($static_slideshow_image){echo "checked";} ?> onchange="if(this.checked){jQuery.get('<?php echo $manageurl ?>?ajax=true&static=true');}else{jQuery.get('<?php echo $manageurl ?>?ajax=true&static=false');}"></input>
    <div class="clearerleft"></div>
    </div>
    <?php
    }
    hook('render_new_element_for_manage_slideshow', '', array($slideshow_files));
?>
</div>
<script>
function SetSlideshowFlag(element, id)
    {
    var input = jQuery(element);

    var flag_value = 0;
    if(element.checked)
        {
        flag_value = 1;
        }

    var post_url  = '<?php echo $manageurl; ?>';
    var post_data =
        {
        ajax: true,
        action: 'set_flag',
        slideshow_id: id,
        flag: input.attr('name'),
        value: flag_value,
        <?php echo generateAjaxToken("SetSlideshowFlag"); ?>
        };

    CentralSpaceShowLoading();

    jQuery.ajax(
        {
        type: 'POST',
        url: post_url,
        data: post_data,
        }).fail(function(data, textStatus, jqXHR) {
            styledalert(data.status, data.statusText);
        }).always(function() {
            CentralSpaceHideLoading();
        });

    return false;
    }

function ReorderSlideshowImage(id, direction)
    {
    var post_url  = '<?php echo $manageurl ?>';
    var post_data =
        {
        ajax: true,
        action: direction,
        slideshow_id: id,
        <?php echo generateAjaxToken("ReorderSlideshowImage"); ?>
        };

    jQuery.post(post_url, post_data, function(response)
        {
        if(response.sibling !== false)
            {

            // Establish row elements and their corresponding button elements
            var moving_row      = jQuery('#slideshow_' + id);
            var moving_moveup   = jQuery('#slideshow_' + id + '_moveup');
            var moving_movedown = jQuery('#slideshow_' + id + '_movedown');
            var target_row      = jQuery('#slideshow_' + response.sibling);
            var target_moveup   = jQuery('#slideshow_' + response.sibling + '_moveup');
            var target_movedown = jQuery('#slideshow_' + response.sibling + '_movedown');
            
            // Swap rows
            if(direction == 'moveup')
                {
                jQuery(moving_row).insertBefore(target_row);
            }
            else // movedown
                {
                jQuery(moving_row).insertAfter(target_row);
            }

            jQuery(moving_row).attr("id","TEMPslideshow_"+response.sibling);
            jQuery(target_row).attr("id","TEMPslideshow_"+id);
            jQuery(moving_row).attr("id","slideshow_"+response.sibling);
            jQuery(target_row).attr("id","slideshow_"+id);
            
            // Re-establish the what the affected move buttons do
            jQuery(moving_moveup).attr("onclick","ReorderSlideshowImage("+response.sibling+",'moveup')");
            jQuery(moving_movedown).attr("onclick","ReorderSlideshowImage("+response.sibling+",'movedown')");

            jQuery(target_moveup).attr("onclick","ReorderSlideshowImage("+id+",'moveup')");
            jQuery(target_movedown).attr("onclick","ReorderSlideshowImage("+id+",'movedown')");

            // Re-establish move button availability
            jQuery("[slideMoveUpButton]").prop("disabled",false);
            jQuery("[slideMoveDownButton]").prop("disabled",false);
            jQuery("[slideMoveUpButton]:first").prop("disabled",true);
            jQuery("[slideMoveDownButton]:last").prop("disabled",true);

            }
        }, 'json').fail(function(data, textStatus, jqXHR) {
        styledalert(data.statusText, data.responseText);
    });

    return false;
    }

function DeleteSlideshowImage(id)
    {
    var post_url  = '<?php echo $manageurl ?>';
    var post_data =
        {
        ajax: true,
        action: 'delete',
        slideshow_id: id,
        <?php echo generateAjaxToken("DeleteSlideshowImage"); ?>
        };

    jQuery.post(post_url, post_data, function(response)
        {
        if(response.success)
            {
            jQuery('#slideshow_' + id).remove();

            // Make sure, appropriate buttons are still getting disabled
            var slideshow_ids = jQuery('div[id*="slideshow_"].Question');
            slideshow_ids.first().find('button[id*="_moveup"').prop('disabled', true);
            slideshow_ids.last().find('button[id*="_movedown"').prop('disabled', true);
            if (slideshow_ids.find('button[id*="_delete"').length==1)
                {
                slideshow_ids.find('button[id*="_delete"').prop('disabled', true);
                }

            }
        }, 'json').fail(function(data, textStatus, jqXHR) {
        styledalert(data.statusText, data.responseJSON.error);
    });

    return false;
    }
</script>
<?php
include '../../include/footer.php';
