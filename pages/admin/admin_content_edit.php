<?php
/**
 * Edit content strings page (part of System area)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php"; if (!checkperm("o")) {exit ("Permission denied.");}
include "../../include/research_functions.php";

$offset        = getvalescaped('offset', 0);
$page          = getvalescaped('page', '');
$name          = getvalescaped('name', '');
$findpage      = getvalescaped('findpage', '');
$findname      = getvalescaped('findname', '');
$findtext      = getvalescaped('findtext', '');
$newhelp       = getvalescaped('newhelp', '');
$editlanguage  = getvalescaped('editlanguage', $language);
$editgroup     = getvalescaped('editgroup', '');
$save          = getvalescaped('save', '');
$text          = getval('text', '');

// Validate HTML
$html_validation = validate_html($text);

# get custom value from database, unless it has been newly passed from admin_content.php
if(getval('custom', '') == 1)
    {
    $custom    = 1;
    $newcustom = true;
    }
else
    {
    $custom    = check_site_text_custom($page, $name);
    $newcustom = false;
    }

if(($save != '') && getval('langswitch', '') == '' && $html_validation === true && enforcePostRequest(false))
    {
    # Save data
    save_site_text($page, $name, $editlanguage, $editgroup);
    if($newhelp != '')
        {
        if(getval('returntolist', '') == '')
            {
            redirect($baseurl_short . "pages/admin/admin_content_edit.php?page=help&name=" . urlencode($newhelp) . "&offset=" . urlencode($offset) . "&findpage=" . urlencode($findpage) . "&findname=" . urlencode($findname) . "&findtext=" . urlencode($findtext) );
            }
        }
    if(getval('custom', '') == 1)
        {
        if(getval('returntolist', '') == '')
            {
            redirect($baseurl_short . "pages/admin/admin_content_edit.php?page=". urlencode($page) . "&name=" . urlencode($name) . "&offset=" . urlencode($offset) . "&findpage=" . urlencode($findpage) . "&findname=" . urlencode($findname) . "&findtext=" . urlencode($findtext) );
            }
        }	
    if(getval('returntolist', '') != '')
        {
        redirect($baseurl_short . "pages/admin/admin_content.php?nc=" . time() . "&findpage=" . urlencode($findpage) . "&findname=" . urlencode($findname) . "&findtext=" . urlencode($findtext) . "&offset=" . urlencode($offset) );
        }
    }

// Need to save $lang and $language so we can revert after finding specific text
$langsaved=$lang;
$languagesaved=$language;

$text        = get_site_text($page, $name, $editlanguage, $editgroup);
$defaulttext = get_site_text($page, $name, $defaultlanguage, '');

# Default text? Show that this is the case
$text_default = false;
if($text == $defaulttext && ($editlanguage != $defaultlanguage || $editgroup != ''))
    {
    $text_default = true;
    }

// Revert to original values
$lang=$langsaved;
$language=$languagesaved;

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
        'title' => $lang["managecontent"],
		'href'  => $baseurl_short . "pages/admin/admin_content.php?nc=" . time() . "&findpage=" . urlencode($findpage) . "&findname=" . urlencode($findname) . "&findtext=" . urlencode($findtext) . "&offset=" . urlencode($offset)
    ),
	array(
        'title' => $lang["editcontent"]
	)
);

renderBreadcrumbs($links_trail);

if($html_validation !== true && $html_validation !== '')
    {
    ?>
    <div class="PageInformal"><?php echo $lang['error_check_html_first']; ?></div>
    <?php
    }
    ?>

    <form method="post" id="mainform" action="<?php echo $baseurl_short; ?>pages/admin/admin_content_edit.php?page=<?php echo urlencode($page);?>&name=<?php echo urlencode($name);?>&editlanguage=<?php echo urlencode($editlanguage);?>&editgroup=<?php echo urlencode($editgroup);?>&findpage=<?php echo urlencode($findpage)?>&findname=<?php echo urlencode($findname)?>&findtext=<?php echo urlencode($findtext)?>&offset=<?php echo urlencode($offset)?>">
        <?php generateFormToken("mainform"); ?>
        <input type=hidden name=page value="<?php echo $page?>">
        <input type=hidden name=name value="<?php echo $name?>">
        <input type=hidden name=copyme id="copyme" value="">
        <input type=hidden name=langswitch id="langswitch" value="">
        <input type=hidden name=groupswitch id="groupswitch" value="">
        <input type="hidden" name="custom" value="<?php echo getval('custom', 0, true)?>">
        <div class="Question">
            <label><?php echo $lang["page"]?></label>
            <div class="Fixed"><?php echo ($page==""?$lang["all"]:$page) ?></div>
            <div class="clearerleft"></div>
        </div>

    <?php
    if($page == 'help')
        {
        ?>
        <div class="Question">
            <label for="name"><?php echo $lang["name"]?></label>
            <input type=text name="name" class="stdwidth" value="<?php echo htmlspecialchars($name)?>">
            <div class="clearerleft"></div>
        </div>
        <?php
        }
    else
        {
        ?>
        <div class="Question">
            <label><?php echo $lang["name"]?></label>
            <div class="Fixed"><?php echo htmlspecialchars($name) ?></div>
            <div class="clearerleft"></div>
        </div>
        <?php
        }
        ?>

        <div class="Question">
            <label for="editlanguage"><?php echo $lang["language"]?></label>
            <select class="stdwidth" name="editlanguage" onchange="document.getElementById('langswitch').value='yes';document.getElementById('mainform').submit();">
            <?php
            foreach($languages as $key => $value)
                {
                ?>
                <option value="<?php echo $key?>" <?php if($editlanguage == $key) { ?>selected<?php } ?>><?php echo $value; ?></option>
                <?php
                }
                ?>
            </select>
            <div class="clearerleft"></div>
        </div>

    <?php
    if(!hook('managecontenteditgroupselector'))
        {
        ?>
        <div class="Question">
            <label for="editgroup"><?php echo $lang["group"]?></label>
            <select class="stdwidth" name="editgroup" onchange="document.getElementById('groupswitch').value='yes';document.getElementById('copyme').value='yes';document.getElementById('mainform').submit();">
                <option value=""></option>
            <?php 
            $groups = get_usergroups();
            for($n = 0; $n < count($groups); $n++)
                {
                ?>
                <option value="<?php echo $groups[$n]["ref"]?>" <?php if ($editgroup==$groups[$n]["ref"]) { ?>selected<?php } ?>><?php echo $groups[$n]["name"]?></option>
                <?php
                }
                ?>
            </select>
            <div class="clearerleft"></div>
        </div>
        <?php
        } /* End managecontenteditgroupselector */

        # Default text? Show that this is the case
        if($text_default)
            {
            render_fixed_text_question($lang["default"],str_replace("?", $languages[$defaultlanguage], $lang['managecontent_defaulttextused']));
            }
        ?>

        <div class="Question">
        <?php
        if($site_text_use_ckeditor)
            {
            ?>
            <label for="text"><?php echo $lang['text']; ?></label>
            <textarea id="<?php echo $lang["text"]?>" class="stdwidth" name="text" rows=15 cols=50><?php echo htmlspecialchars($text); ?></textarea>
            <script type="text/javascript">
            <?php
            if(!hook('ckeditorinit'))
                {
                ?>
                var editor = CKEDITOR.instances['<?php echo $lang["text"]?>'];
                if(editor) {
                    editor.destroy(true);
                }

                CKEDITOR.replace('<?php echo $lang["text"] ?>',
                    {
                    toolbar : [ <?php global $ckeditor_content_toolbars;echo $ckeditor_content_toolbars; ?> ],
                    height: "600"
                    });
                
                var editor = CKEDITOR.instances['<?php echo $lang["text"]?>'];
                
                CKEDITOR.config.autoParagraph = false;
                <?php
                }
                hook('ckeditoroptions'); ?>
            </script>
            <?php
            }
        else
            {
            ?>
            <label for="text"><?php echo $lang['text']; ?></label>
            <textarea id="text" class="stdwidth" name="text" rows=15 cols=50><?php echo htmlspecialchars($text); ?></textarea>
            <?php
            }
            ?>
            <div class="clearerleft"></div>
        </div>

    <?php
    # add special ability to create and remove help pages
    if($page == 'help')
        {
        if($name != 'introtext')
            {
            ?>
            <div class="Question">
                <label for="deleteme"><?php echo $lang["ticktodeletehelp"]?></label>
                <input id="deleteme" class="deleteBox" name="deleteme" type="checkbox" value="yes"><div class="clearerleft" />
            </div>
            <?php
            }
            ?>
            <br /><br />
            <div class="Question">
                <label for="newhelp"><?php echo $lang["createnewhelp"]?></label><input name="newhelp" type=text value="" />
                <div class="clearerleft"></div>
            </div>
        <?php 
		}

        # add ability to delete custom page/name entries
        if($custom == 1 && $page != 'help')
            {
            ?>
            <div class="Question">
                <label for="deletecustom"><?php echo $lang["ticktodeletehelp"]?></label>
                <input id="deletecustom" class="deleteBox" name="deletecustom" type="checkbox" value="yes" />
                <div class="clearerleft"> </div>
            </div>
            <?php
            }
            ?>

            <input type=hidden id="returntolist" name="returntolist" value=""/>
            <div id="submissionResponse"></div>
            <div class="QuestionSubmit">
                <label for="save"></label>
                <input type="submit" name="checkhtml" id="checkhtml" value="Check HTML" />
                <input type="submit" name="save" value="<?php echo $lang["save"]; ?>" />
                <input type="submit" name="save" value="<?php echo $lang['saveandreturntolist']; ?>" onClick="jQuery('#returntolist').val(true);" />
            </div>
        </form>
    </div><!-- End of BasicsBox -->

<script>
// When to take us back to manage content list
jQuery('#deleteme, #deletecustom').change(function() {
    if(jQuery(this).is(':checked')) {
        jQuery('#returntolist').val(true);
    } else {
        jQuery('#returntolist').val(null);
    }
});

// Manually check HTML:
jQuery('#checkhtml').click(function(e) {
    var ckeditor = '<?php echo $site_text_use_ckeditor; ?>';
    if(ckeditor == true) {
        var checktext = editor.getData();
    } else {
        var checktext = jQuery('#text').val();
    }

    jQuery.post(
        '../tools/check_html.php', 
        {
            'text': checktext, 
            <?php echo generateAjaxToken('admin_content_edit'); ?>,
        }, function(response, status, xhr){
        CentralSpaceHideLoading();
        jQuery('#submissionResponse').html(response);
    });
    e.preventDefault();
});
</script>
<?php		
include "../../include/footer.php";
?>

