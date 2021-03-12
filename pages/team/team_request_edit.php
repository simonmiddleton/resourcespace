<?php
/**
 * Edit resource request page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php"; 
if (!checkperm("R")) { exit ("Permission denied."); }
include "../../include/request_functions.php";

$ref = getvalescaped("ref", "", true);
$modal=(getval("modal","")=="true");
$backurl=getval("backurl","");
$url=$baseurl_short."pages/team/team_request_edit.php?ref=" . $ref . "&backurl=" . urlencode($backurl);

if (getval("submitted","") != "" && enforcePostRequest(false))
    {
    # Save research request data
    if(!hook("saverequest", "", array($ref)))
        {
        save_request($ref);
        }
    if(!$modal)
            {
            redirect($baseurl_short . "pages/team/team_request.php?reload=true&nc=" . time());
            exit();
            }
        else
            {
            $resulttext=$lang["changessaved"];
            }
    
    }

# Fetch research request data
$request = get_request($ref);
if ($request === false && !isset($resulttext))
    {
    $resulttext = "Request " . htmlspecialchars($ref) . " not found.";
    }
  
include "../../include/header.php";

?>
<div class="BasicsBox">
    
<div class="RecordHeader">
<div class="backtoresults">	
    <?php if($modal)
        {
        ?>
        <a class="maxLink fa fa-expand" href="<?php echo $url ?>" onClick="return CentralSpaceLoad(this);"></a>
        &nbsp;
        <a href="#"  class="closeLink fa fa-times" onClick="ModalClose();"></a>
        <?php
        }
        ?>
    </div>

    <?php
    if(!$modal)
        {?>
        <p><a href="<?php echo ($backurl!=""?$backurl:$baseurl_short ."pages/team/team_request.php");?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $backurl!=""?$lang["back"]:$lang["managerequestsorders"]; ?></a></p>
        <?php
        }
        ?>
    <h1><?php echo $lang["editrequestorder"];render_help_link('resourceadmin/user-resource-requests');?></h1>
</div>
<?php
if (isset($resulttext))
	{
	echo "<div class=\"PageInformal \">" . $resulttext . "</div>";
	}

if ($request !== false)
	{
    $show_this_request=false;
    # Show request assigned to the user if the user can accept requests (permission "Rb")
    # Should the request be shown if assigned to the user (used to have "Rb") but the user can no longer accept requests
    if (checkperm("Rb") && ($request["assigned_to"]==$userref))
        {
        $show_this_request=true;
        }
    # Show request if the user can assign requests (permission "Ra")
    if(checkperm('Ra'))
        {
        $show_this_request=true;
        }
    
    if (!$show_this_request)
        {
        ?><p><?php echo str_replace("%","<b>" . ($request["assigned_to_username"]==""?"(unassigned)":$request["assigned_to_username"]) . "</b>",$lang["requestnotassignedtoyou"]) ?></p><?php
        }
    else
        {
        ?>
        
    <form method="post" action="<?php echo $baseurl_short?>pages/team/team_request_edit.php" onSubmit="return <?php echo ($modal?"Modal":"CentralSpace") ?>Post(this,true);">
    <?php
    generateFormToken("team_request_edit");

    if($modal)
        {
        ?>
        <input type=hidden name="modal" value="true">
        <?php
        }
        ?>
        <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref) ?>" />
        <input type="hidden" name="submitted" value="yes" />

        <div class="Question">
            <label><?php echo $lang["requestedby"]?></label>
            <div class="Fixed"><?php echo $request["fullname"]?> (<?php echo $request["username"]?> / <?php echo $request["email"]?>)</div>
            <div class="clearerleft"></div>
        </div>

        <?php hook("morerequesterinfo"); ?>

        <div class="Question">
            <label><?php echo $lang["date"]?></label>
            <div class="Fixed"><?php echo nicedate($request["created"],true,true, true)?></div>
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label><?php echo $lang["comments"]?></label>
            <div class="Fixed"><?php echo nl2br($request["comments"]) ?></div>
            <div class="clearerleft"></div>
        </div>

        <?php if(!hook("disprequesteditems")): ?>
        <div class="Question">
            <label><?php echo $lang["requesteditems"]?></label>
            <div class="Fixed"><a href="#" onclick="ChangeCollection(<?php echo $request["collection"]?>,'');"><?php echo LINK_CARET ?><?php echo $lang["action-selectrequesteditems"]?></a></div>
            <div class="clearerleft"></div>
        </div>
        <?php endif; ?>


        <?php
        # Show any warnings
        if (isset($warn_field_request_approval))
            {
            $warnings=sql_query("select resource,value from resource_data where resource_type_field='$warn_field_request_approval' and length(value)>0 and resource in (select resource from collection_resource where collection='" . $request["collection"] . "') order by resource");
            foreach ($warnings as $warning)
                { ?>
                <div class="Question">
                    <div class="FormError">
                        <?php echo str_replace("%","<a onClick='return CentralSpaceLoad(this,true);' href=".$baseurl_short."pages/view.php?ref=" . $warning["resource"] . ">" . $warning["resource"] . "</a>",$lang["warningrequestapprovalfield"]) ?><br/>
                        <?php echo $warning["value"] ?>
                    </div>
                    <div class="clearerleft"></div>
                </div>
                <?php
                }
            }
        
        if (checkperm("Ra"))
            {
            ?>
            <div class="Question">
                <label><?php echo $lang["assignedtoteammember"]?></label>
                <select class="shrtwidth" name="assigned_to">
                    <option value="0"><?php echo $lang["requeststatus0"]?></option>
                    <?php 
                    $users = get_users_with_permission("Rb");
                    for ($n=0;$n<count($users);$n++)
                        { 
                        $assigned_selected = ($request["assigned_to"] == $users[$n]["ref"]) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $users[$n]["ref"]?>" <?php echo $assigned_selected ?>><?php echo $users[$n]["username"]?></option> 
                        <?php
                        } ?>
                </select>
                <div class="clearerleft"></div>
            </div>
            <?php
            } ?>

        <div class="Question">
            <label><?php echo $lang["status"]?></label>
            <div class="tickset">
                <?php 
                for ($n=0;$n<=2;$n++) 
                    { 
                    $status_checked = ($request["status"] == $n) ? 'checked' : '';
                    ?>
                    <div class="Inline">
                        <label>
                            <input type="radio" name="status" value="<?php echo $n ?>" <?php echo $status_checked; ?>
                                onClick="<?php 
                                if ($n==1) 
                                    { 
                                    ?>jQuery('#Expires').fadeIn();jQuery('#ReasonApprove').fadeIn();<?php 
                                    } 
                                else 
                                    { 
                                    ?>jQuery('#Expires').slideUp();jQuery('#ReasonApprove').slideUp();<?php 
                                    }
                                if ($n==2) 
                                    { 
                                    ?>jQuery('#ReasonDecline').fadeIn();<?php 
                                    } 
                                else 
                                    { 
                                    ?>jQuery('#ReasonDecline').slideUp();<?php 
                                    } ?>"/> 
                                <?php echo $lang["resourcerequeststatus" . $n] ?>
                        </label>
                    </div>
                    <?php 
                    } ?>
            </div>
            <div class="clearerleft"></div>
        </div>

        <div class="Question" id="Expires" <?php if ($request["status"]!=1) { ?>style="display:none;"<?php } ?>>
            <label><?php echo $lang["expires"]?></label>
            <select name="expires" class="stdwidth">
                <?php 
                if (!$removenever) 
                    { ?>
                    <option value=""><?php echo $lang["never"]?></option>
                    <?php 
                    } 
                $sel = false;
                for ($n=1;$n<=150;$n++)
                    {
                    $date    = time() + (60*60*24*$n);
                    $dateval = date("Y-m-d", $date);
                    $d       = date("D", $date);

                    $expires_selected = '';
                    if ($dateval == $request["expires"] || ($request["expires"] == '' && $removenever && $n == 7) ) 
                        { 
                        $sel = true;
                        $expires_selected = 'selected';
                        }
                    $option_class = '';
                    if (($d == "Sun") || ($d == "Sat"))
                        {
                        $option_class = 'optionWeekend';
                        } ?>
                    <option class="<?php echo $option_class ?>" value="<?php echo $dateval ?>" <?php echo $expires_selected ?>><?php echo nicedate($dateval,false,true)?></option>
                    <?php
                    }
                if ($request["expires"]!="" && $sel==false)
                    {
                    # Option is out of range, but show it anyway.
                    ?>
                    <option value="<?php echo $request["expires"] ?>" selected><?php echo nicedate(date("Y-m-d",strtotime($request["expires"])),false,true)?></option>
                    <?php
                    }
                ?>
            </select>
            <div class="clearerleft"></div>
        </div>

        <div class="Question" id="ReasonDecline" <?php if ($request["status"]!=2) { ?>style="display:none;"<?php } ?>>
            <label><?php echo $lang["declinereason"]?></label>
            <textarea name="reason" class="stdwidth" rows="5" cols="50"><?php echo htmlspecialchars($request["reason"])?></textarea>
            <div class="clearerleft"></div>
        </div>

        <div class="Question" id="ReasonApprove" <?php if ($request["status"]!=1) { ?>style="display:none;"<?php } ?>>
            <label><?php echo $lang["approvalreason"]?></label>
            <textarea name="reasonapproved" class="stdwidth" rows="5" cols="50"><?php echo htmlspecialchars($request["reasonapproved"])?></textarea>
            <div class="clearerleft"></div>
        </div>

        <div class="Question">
            <label><?php echo $lang["deletethisrequest"]?></label>
            <input name="delete" type="checkbox" value="yes">
            <div class="clearerleft"></div>
        </div>

        <div class="QuestionSubmit">
            <label></label>          
            <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
        </div>
    </form>

    <?php       
    }
	}?>
</div> <!-- .BasicsBox -->
<?php
include "../../include/footer.php";
?>
