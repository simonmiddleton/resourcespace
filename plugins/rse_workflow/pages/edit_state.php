<?php
#
# rse_workflow edit archive/workflow states page, requires System Setup permission
#

include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
unset($additional_archive_states);$additional_archive_states=array();
include_once '../include/rse_workflow_functions.php';

$code=getvalescaped("code","");
$name=getvalescaped("name","");
$notify_group=getvalescaped("notify_group",0, true);
$more_notes = getvalescaped('more_notes', 0, true);
$notify_user = getvalescaped('notify_user', 0, true);
$rse_workflow_email_from = getvalescaped('rse_workflow_email_from', '');
$rse_workflow_bcc_admin = getvalescaped('rse_workflow_bcc_admin', 0, true);
$simple_search = getval('simple_search', 0, true);
$errortext="";
$saved=false;

global $additional_archive_states;
# Get state details
$fixedstate=false;
        
if($code=="new")
    {
    $workflowstate["fixed"]="false";
    $workflowstate["name"]="";
    $workflowstate["notify_group"]="";
    $workflowstate['more_notes_flag'] = 0;
    $workflowstate['notify_user_flag'] = 0;
    $workflowstate["rse_workflow_email_from"] = "";
    $workflowstate["rse_workflow_bcc_admin"] = 0;
    $workflowstate["simple_search_flag"] = 0;
    }
else
    {   
    $workflowstates = rse_workflow_get_archive_states();    
    // Check that this is a valid state to edit
    if($code=="" || in_array($code,$additional_archive_states) || ($code>-3 && $code <4) || !isset($workflowstates[$code]))
        {
        $fixedstate=true;
        //$errortext=$lang["rse_workflow_state_not_editable"];
        }
    $workflowstate=$workflowstates[$code];
    }


if (getvalescaped("submitted","")!="" && enforcePostRequest(getval("ajax", false)))
    {
    if ($name=="")
        {
        $errortext=$lang["rse_workflow_state_check_fields"];
        }
    if($errortext=="")
        {
        $simple_search_escaped = escape_check($simple_search);

        if($code=="new")
            {               
            # Get current maximum code reference
            $max=sql_value("select max(code) as value from archive_states","");
                        $code=$max+1;
            sql_query("
                INSERT INTO archive_states (
                                code,
                                name,
                                notify_group,
                                more_notes_flag,
                                notify_user_flag,
                                email_from,
                                bcc_admin,
                                simple_search_flag
                            )
                    VALUES (
                            '$code',
                            '$name',
                            '$notify_group',
                            '$more_notes',
                            '$notify_user',
                            '$rse_workflow_email_from',
                            '$rse_workflow_bcc_admin',
                            '$simple_search_escaped'
                           )");
            }
        else
            {
            sql_query("
                UPDATE archive_states
                   SET name='$name',
                       notify_group='$notify_group',
                       more_notes_flag='$more_notes',
                       notify_user_flag='$notify_user',
                       email_from='$rse_workflow_email_from',
                       bcc_admin='$rse_workflow_bcc_admin',
                       simple_search_flag = '$simple_search_escaped'
                 WHERE code = '$code'");
            }

        $saved=true;
        }
    
    $workflowstate["name"]=$name;
    $workflowstate["notify_group"]=$notify_group;
    $workflowstate['more_notes_flag']=$more_notes;
    $workflowstate['notify_user_flag']=$notify_user;
    $workflowstate["rse_workflow_email_from"]=$rse_workflow_email_from;
    $workflowstate["rse_workflow_bcc_admin"]=$rse_workflow_bcc_admin;
    $workflowstate["simple_search_flag"] = $simple_search;
    }   
    
    

include '../../../include/header.php';

?>

<div class="BasicsBox">
<a href="<?php echo $baseurl_short?>plugins/rse_workflow/pages/edit_workflow_states.php" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK . $lang["rse_workflow_manage_states"] ?>&nbsp;</a>
</div>

<?php

if($errortext!="")
    {
    ?>
    <script type="text/javascript">
    alert('<?php echo $lang['rse_workflow_state_check_fields'] ?>');
    </script><?php
    }
    
else if ($saved)
    {
    echo "<div class=\"PageInformal\">" . $lang['saved'] . "</div>";
    }
    
    
?>
        
<div class="BasicsBox">
<h1><?php echo $lang['rse_workflow_edit_state']; ?></h1>

<form id="form_workflow_state" name="form_workflow_state" method="post" action="<?php echo $baseurl_short?>plugins/rse_workflow/pages/edit_state.php?code=<?php echo $code ?>">
    <?php generateFormToken("form_workflow_state"); ?>
    <input type="hidden" name="submitted" value="true">
    <input type="hidden" name="code" value="<?php echo htmlspecialchars($code);  ?>">
        
    <div class="Question" id="status_name_question">
    <label for="name"><?php echo $lang["rse_workflow_state_name"]?></label>
    <?php if ($fixedstate)
        {?>
        <div class="Fixed"><?php echo htmlspecialchars($workflowstate["name"]); ?></div>
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($workflowstate["name"]);  ?>">
        <?php
        }
    else
        {?>
        <input type="text" class="stdwidth" name="name" id="name" value="<?php echo htmlspecialchars($workflowstate["name"]);  ?>" />
        <?php
        }
        ?>
    <div class="clearerleft"> </div>
    </div>

        <div class="Question" id="status_notify_group_question">
    <label for="notify_group"><?php echo $lang["rse_workflow_state_notify_group"]?></label>
        <select class="stdwidth" name="notify_group" id="notify_group">
        <option value=""></option>
        <?php
        $groups=get_usergroups(true);
        for ($n=0;$n<count($groups);$n++)
                {
                ?>
                <option value="<?php echo $groups[$n]["ref"]?>" <?php if ($workflowstate["notify_group"]==$groups[$n]["ref"]) {?>selected<?php } ?>><?php echo $groups[$n]["name"]?></option>   
                <?php
                }
        ?>
        </select>
        <div class="clearerleft"> </div></div>

        <div class="Question" id="more_notes_question">
            <label for="more_notes"><?php echo $lang['rse_workflow_more_notes_label']; ?></label>
            <?php
                $more_notes_checked = '';
                if($workflowstate['more_notes_flag'] == 1) {
                    $more_notes_checked = 'checked';
                }
            ?>
            <input id="more_notes" type="checkbox" name="more_notes" value="1" <?php echo $more_notes_checked; ?>>
            <div class="clearerleft"></div>
        </div>
        
        <div class="Question" id="emailfrom_question">
            <label for="rse_workflow_email_from"><?php echo str_replace("%EMAILFROM%",$email_from,$lang['rse_workflow_email_from']); ?></label>
            <input class="stdwidth" type="text" name="rse_workflow_email_from" id="rse_workflow_email_from" value="<?php echo htmlspecialchars($workflowstate["rse_workflow_email_from"]);  ?>" />
            <div class="clearerleft"></div>
        </div>
        
        <div class="Question" id="bcc_admin_question">
            <label for="bcc_admin"><?php echo str_replace("%ADMINEMAIL%",$email_notify,$lang['rse_workflow_bcc_admin']); ?></label>
            <?php
                $bcc_admin = '';
                if($workflowstate['rse_workflow_bcc_admin'] == 1)
                    {
                    $bcc_admin = 'checked';
                    }
            ?>
            <input id="rse_workflow_bcc_admin" type="checkbox" name="rse_workflow_bcc_admin" value="1" <?php echo $bcc_admin; ?>>
            <div class="clearerleft"></div>
        </div>

        <div class="Question" id="notify_user_question">
            <label for="notify_user"><?php echo $lang['rse_workflow_notify_user_label']; ?></label>
            <?php
                $notify_user_checked = '';
                if($workflowstate['notify_user_flag'] == 1) {
                    $notify_user_checked = 'checked';
                }
            ?>
            <input id="notify_user" type="checkbox" name="notify_user" value="1" <?php echo $notify_user_checked; ?>>
            <div class="clearerleft"></div>
        </div>

        <div class="Question" id="simple_search_question">
            <label for="simple_search"><?php echo $lang['rse_workflow_simple_search_label']; ?></label>
            <?php
                $simple_search_checked = '';
                if($workflowstate['simple_search_flag'] == 1) {
                    $simple_search_checked = 'checked';
                }
            ?>
            <input id="simple_search" type="checkbox" name="simple_search" value="1" <?php echo $simple_search_checked; ?>>
            <div class="clearerleft"></div>
        </div>
    <div class="Question" id="QuestionSubmit">
        <label for="buttons"> </label>
        <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" onclick="event.preventDefault();CentralSpacePost(document.getElementById('form_workflow_state'),true);"/>
    </div>
</form>
</div>
<?php

include '../../../include/footer.php';