<?php
include '../include/db.php';
include_once '../include/general.php';
include '../include/authenticate.php'; 
include_once '../include/collections_functions.php';

$collection     = getvalescaped('ref', '', true);
$collectiondata = get_collection($collection);
$ajax           = ('true' == getvalescaped('ajax', '') ? true : false);
$sheetstyle     = getvalescaped('sheetstyle', 'thumbnails');


switch($sheetstyle)
    {
    case 'thumbnails':
        $sheetstyle_fields = $config_sheetthumb_fields;
        break;

    case 'list':
        $sheetstyle_fields = $config_sheetlist_fields;
        break;

    case 'single':
        $sheetstyle_fields = $config_sheetsingle_fields;
        break;
    }

/* Depending on the style, users get different fields to select from.
Super Admins decide what fields they can see based on config options (e.g. $config_sheetthumb_fields)and permissions
Note: By default we use thumbnails fields
*/
$available_contact_sheet_fields = array(
    0 => array(
        'ref'   => '',
        'title' => $lang['allfields']
    )
);
foreach(get_fields($sheetstyle_fields) as $field_data)
    {
    $available_contact_sheet_fields[] = $field_data;
    }

if($ajax && 'get_sheetstyle_fields' == getval('action', ''))
    {
    $response = array();

    foreach($available_contact_sheet_fields as $field_data)
        {
        $response[] = array(
            'ref'   => $field_data['ref'],
            'title' => $field_data['title'],
        );
        }

    echo json_encode($response);
    exit();
    }


include '../include/header.php';
?>
<div class="BasicsBox" >
    <h1><?php echo $lang['contactsheetconfiguration']; ?></h1>
<?php
# Check access
if(!collection_readable($collection))
    {
    echo($lang["no_access_to_collection"]);
    echo "</div></div>";
    include "../include/footer.php";
    exit();
    }
    ?>
    <p><?php echo $lang["contactsheetintrotext"]; ?></p>

    <!-- each time the form is modified, the variables are sent to contactsheet.php with preview=true
    contactsheet.php makes just the first page of the pdf (with col size images) 
    and then thumbnails it for the ajax request. This creates a very small but helpful 
    preview image that can be judged before initiating a download of sometimes several MB.-->
    <form method="post" name="contactsheetform" id="contactsheetform" action="<?php echo $baseurl_short; ?>pages/ajax/contactsheet.php" >
        <input type=hidden name="c" value="<?php echo htmlspecialchars($collection); ?>">
        <!--<div name="error" id="error"></div>-->
        <div class="BasicsBox" style="width:450px;float:left;margin-top:0;" >
        
            <div class="Question">
                <label><?php echo $lang["collectionname"]; ?></label>
                <span><?php echo i18n_get_collection_name($collectiondata); ?></span>
                <div class="clearerleft"></div>
            </div>

            <div class="Question">
                <label><?php echo $lang["display"]; ?></label>
                <select class="shrtwidth" name="sheetstyle" id="sheetstyle" onChange="
                	if (jQuery('#sheetstyle').val()=='list')
                		{
                		document.getElementById('OrientationOptions').style.display='block';		
                		document.getElementById('ThumbnailOptions').style.display='none';
                		if (document.getElementById('size_options'))
                			{
                			document.getElementById('size_options').style.display='none';
                			}

                        updateAvailableContactSheetFields('list');
                		}
                	else if (jQuery('#sheetstyle').val()=='single')
                		{
                		document.getElementById('ThumbnailOptions').style.display='none';
                		if (document.getElementById('size_options'))
                			{
                			document.getElementById('size_options').style.display='block';
                			}

                        updateAvailableContactSheetFields('single');
                		}
                	else if (jQuery('#sheetstyle').val()=='thumbnails')
                		{
                		document.getElementById('OrientationOptions').style.display='block';		
                		document.getElementById('ThumbnailOptions').style.display='block';
                		if (document.getElementById('size_options'))
                			{
                			document.getElementById('size_options').style.display='none';
                			}

                        updateAvailableContactSheetFields('thumbnails');
                		}
                	jQuery().rsContactSheet('revert');	
                		">
                    <option value="thumbnails" selected><?php echo $lang["thumbnails"]; ?></option>
                    <option value="list"><?php echo $lang["list"]; ?></option>
                    <option value="single" ><?php echo $lang["contactsheet-single"]; ?></option>
                </select>
                <div class="clearerleft"></div>
                <script>
                function updateAvailableContactSheetFields(style)
                    {
                    var contact_sheet_fields_selector = jQuery('#selected_contact_sheet_fields');

                    var post_url  = '<?php echo $baseurl; ?>/pages/contactsheet_settings.php';
                    var post_data = 
                        {
                        ajax: true,
                        sheetstyle: style,
                        action: 'get_sheetstyle_fields',
                        };

                    jQuery.post(post_url, post_data, function(response)
                        {
                        if(typeof response !== 'undefined')
                            {
                            var response_obj = JSON.parse(response);

                            // Remove all options
                            contact_sheet_fields_selector.empty();

                            var x;
                            for(x in response_obj)
                                {
                                var contact_sheet_field_obj = response_obj[x];

                                contact_sheet_fields_selector.append('<option value="' + contact_sheet_field_obj.ref + '">' + contact_sheet_field_obj.title + '</option>');
                                }

                            return true;
                            }
                        });

                    return false;
                    }
                </script>
            </div>
<?php
if($contact_sheet_include_header_option)
    {
    ?>	
    <div class="Question">
        <label><?php echo $lang["contact_sheet-include_header_option"]; ?></label>
        <select class="shrtwidth" name="includeheader" id="includeheader" onChange="jQuery().rsContactSheet('revert');">
            <option value="true"><?php echo $lang["yes"]?></option>
            <option value="false" <?php if (!$contact_sheet_include_header){?>selected<?php } ?>><?php echo $lang["no"]?></option>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

if($contact_sheet_single_select_size)
    {
    $sizes = get_all_image_sizes(false, false);
    ?>
    <div id="size_options" class="Question" style="display:none">
        <label><?php echo $lang["contact_sheet-single_select_size"]; ?></label>
        <select class="shrtwidth" name="ressize" id="ressize" onChange="jQuery().rsContactSheet('revert');">
        <?php
        foreach($sizes as $size)
            {
            echo '    <option value="'. $size['id'] . '"' . ($size['id']=='lpr'?' selected':'') . '>' . htmlspecialchars($size['name']) . '</option>';
            }
            ?>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

if(isset($contact_sheet_logo_option) && $contact_sheet_logo_option && isset($contact_sheet_logo))
    {
    ?>
    <div class="Question">
        <label><?php echo $lang["contact_sheet-add_logo_option"]; ?></label>
        <select class="shrtwidth" name="addlogo" id="addlogo" onChange="jQuery().rsContactSheet('revert');">
            <option value="true"><?php echo $lang["yes"]; ?></option>
            <option value="false"><?php echo $lang["no"]; ?></option>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    }

if($contact_sheet_add_link_option)
    {
    ?>	
    <div class="Question">
        <label><?php echo $lang["contact_sheet-add_link_option"]; ?></label>
        <select class="shrtwidth" name="addlink" id="addlink" onChange="jQuery().rsContactSheet('revert');">
            <option value="true"><?php echo $lang["yes"]; ?></option>
            <option value="false" <?php if (!$contact_sheet_add_link){?>selected<?php } ?>><?php echo $lang["no"]; ?></option>
        </select>
        <div class="clearerleft"></div>
    </div>
    <?php
    }
    ?>

    <div class="Question">
        <label><?php echo $lang['contact_sheet_select_fields']; ?></label>
        <select id="selected_contact_sheet_fields" class="shrtwidth" name="selected_contact_sheet_fields[]" multiple>
            <?php
            foreach($available_contact_sheet_fields as $contact_sheet_field)
                {
                $selected = '';
                if('' == $contact_sheet_field['ref'])
                    {
                    $selected = 'selected';
                    }
                ?>
                <option value="<?php echo $contact_sheet_field['ref']; ?>"<?php echo $selected; ?>><?php echo $contact_sheet_field['title']; ?></option>
                <?php
                }
            ?>
        </select>
        <div class="clearerleft"></div>
    </div>

    <div class="Question">
        <label><?php echo $lang["size"]; ?></label>
        <select class="shrtwidth" name="size" id="size" onChange="jQuery().rsContactSheet('revert');"><?php echo $papersize_select; ?></select>
        <div class="clearerleft"> </div>
    </div>

<?php
if($contactsheet_sorting)
    {
    $all_field_info = get_fields_for_search_display(array_unique(array_merge($thumbs_display_fields,$list_display_fields,$config_sheetlist_fields,$config_sheetthumb_fields)));
    ?>
    <div class="Question">
        <label><?php echo $lang["sortorder"]?></label>
        <select class="shrtwidth" name="orderby" id="orderby" onChange="jQuery().rsContactSheet('preview');">
            <option value="relevance" selected><?php echo $lang["collection-order"]?></option>
            <option value="date"><?php echo $lang["date"]?></option>
            <option value="colour"><?php echo $lang["colour"]?></option>
            <option value="resourceid"><?php echo $lang["resourceid"]?></option>
            <?php 
            foreach ($all_field_info as $sortable_field)
                {
                // don't display the ones we've already covered above.
                if(!($sortable_field["title"] == $lang["date"] || $sortable_field["title"] == $lang["colour"]))
                    {
                    ?>
                    <option value="<?php echo $sortable_field['ref']?>"><?php echo htmlspecialchars($sortable_field["title"]) ?></option>
                    <?php
                    }
                }	
                ?>
        </select>
        <div class="clearerleft"> </div>
    </div>

    <div class="Question">
        <label><?php echo htmlspecialchars($lang["sort-type"]) ?></label>
        <select class="shrtwidth" name="sort" id="sort" onChange="jQuery().rsContactSheet('preview');">
            <option value="asc" selected><?php echo $lang["ascending"]?></option>
            <option value="desc"><?php echo $lang["descending"]?></option>
        </select>
        <div class="clearerleft"> </div>
    </div>
    <?php
    }
    ?>

            <div id="ThumbnailOptions" class="Question">
                <label><?php echo $lang["columns"]?></label>
                <select class="shrtwidth" name="columns" id="ThumbnailOptions" onChange="jQuery().rsContactSheet('revert');"><?php echo $columns_select ?></select>
                <div class="clearerleft"> </div>
            </div>

            <div id="OrientationOptions" class="Question">
                <label><?php echo $lang["orientation"]?></label>
                <select class="shrtwidth" name="orientation" id="orientation" onChange="jQuery().rsContactSheet('revert');">
                    <option value="P"><?php echo $lang["portrait"]; ?></option>
                    <option value="L"><?php echo $lang["landscape"]; ?></option>
                </select>
                <div class="clearerleft"> </div>
            </div>

            <div name="previewPageOptions" id="previewPageOptions" class="Question" style="display:none">
                <label><?php echo $lang['previewpage']?></label>
                <select class="shrtwidth" name="previewpage" id="previewpage" onChange="jQuery().rsContactSheet('preview');"></select>
            </div>

            <div class="QuestionSubmit">
                <label for="buttons"> </label>	
                <?php
                if($contact_sheet_previews==true)
                    {
                    ?>
                    <input name="preview" type="button" value="&nbsp;&nbsp;<?php echo $lang["action-preview"]?>&nbsp;&nbsp;" onClick="jQuery().rsContactSheet('preview');"/>
                    <?php
                    }
                    ?>
                <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"]?>&nbsp;&nbsp;" />
            </div>
        </div> <!-- end of small BasicBox -->
    </form>
</div>

<div>
<!-- this is the container for some Ajax fun. The image will go here...-->
<?php
$cs_size = explode("x", $contact_sheet_preview_size);
$height  = $cs_size[1];

if($contact_sheet_previews == true)
    {
    ?>
    <div style="float:left;padding:0px -50px 15px 0;height:<?php echo htmlspecialchars($height) ?>px;margin-top:-15px;margin-right:-50px">
        <img id="previewimage" name="previewimage" src=""/>
    </div>
    <?php
    }
    ?>
</div>
<script type="text/javascript">jQuery().rsContactSheet('preview');</script>
<?php
include '../include/footer.php';