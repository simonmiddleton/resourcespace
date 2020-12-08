<?php
include "../include/db.php";
include "../include/authenticate.php";

// Set up array of valid users that share can be set to emulate
$validshareusers = array();
foreach($upload_link_users as $upload_link_user)
    {
    $up_user = get_user($upload_link_user);
        
    if($up_user)
        {
        $validshareusers[$upload_link_user] = $up_user["fullname"] != "" ? $up_user["fullname"] : $up_user["username"];
        }
    if(!isset($validshareusers[$userref]))
        {
        $validshareusers[$userref] = $username;
        }
    }

$collection	= getvalescaped('collection', 0, true);
$uploadkey  = getval("uploadkey","");
if($uploadkey != "")
    {
    $shareinfo      = get_upload_share_details($collection,$uploadkey);
    $editable       = can_edit_upload_share($collection,$uploadkey);
    if(!$editable)
        {
        error_alert($lang["error-permissiondenied"],true);
        exit();
        }

    print_r($shareinfo);
    $editing        = $uploadkey != "" && $editable;
    $sharepwd       = isset($shareinfo["password_hash"]) && $shareinfo["password_hash"] != "" ? "password_placeholder" : "";
    $shareuser      = isset($shareinfo["user"]) ? $shareinfo["user"] : $userref;
    $shareexpires   = isset($shareinfo["user"]) ? $shareinfo["user"] : $userref;    
    //$sharestatus  = isset($shareinfo["status"]) ? $shareinfo["status"] : get_default_archive_state();
    if(!isset($validshareusers[$shareuser]))
        {
        $curshareuser = get_user($shareuser);
        $validshareusers[$shareuser]  = $curshareuser["fullname"] != "" ? $curshareuser["fullname"] : $curshareuser["username"];
        }
    }
else
    {
    $sharepwd       = getval("inputpassword","");
    $shareuser      = getval("shareuser",$userref,true);
    if(!isset($validshareusers[$shareuser]))
        {
        $errortext = $lang["error_invalid_user"];
        }

    $shareexpires   = getval("shareexpires","");
    $editing        = false; 
    }

$collectiondata	= get_collection($collection);
$submitted = getval("submitted","") != "";

if($submitted && !isset($errortext))
    {
    $shareoptions = array(
        "user" => $shareuser,
        "expires" => $shareexpires,
        "password" => $sharepwd,
        "upload" => 1,
        );
    if($uploadkey != "")
        {
        $result = edit_collection_external_access($uploadkey,-1,$shareexpires,"",$sharepwd,1);
        }
    else
        {
        $result = create_upload_link($collection,$shareoptions);
        }
    if(is_string($result))
        {
        $shareurl = $baseurl . "/?c=" . $collection . "&k=" . $result;
        $errortext = $lang["generateurlexternal"] . "<br/><a href='" . $shareurl . "'>" . $shareurl  . "</a>";
        }
    }
$page_header = $editing ? $lang["title-upload-link-edit"] : $lang["title-upload-link-create"];

include "../include/header.php";
?>
<div class="BasicsBox"> 	
	<?php

        ?>
        <h1><?php echo $page_header; render_help_link("user/share-upload-link");?></h1>
        <?php
        if(isset($errortext))
            {
            echo "<div class='PageInformal'>" . $errortext . "</div>";
            }

        echo "<p><strong>" . $lang["warning-upload-link"] . "</strong></p>"; 
        echo "<p>" . $lang["warning-upload-instructions"] . "</p>";
        ?>
        <form method=post id="shareuploadform" action="<?php echo $baseurl_short; ?>pages/share_upload.php?collection=<?php echo $collection; ?>" onsubmit="return CentralSpacePost(this,true);">
            <input type="hidden" name="deleteshare" id="deleteshare" value="">   
            <input type="hidden" name="submitted" id="submit" value="true">    
            <input type="hidden" name="uploadkey" id="uploadkey" value="<?php echo htmlspecialchars($uploadkey); ?>">
            <?php generateFormToken("shareuploadform"); ?>
            
            <div class="Question">
                <label><?php echo $lang["collectionname"]; ?></label>
                <div class="Fixed"><?php echo i18n_get_collection_name($collectiondata); ?></div>
                <div class="clearerleft"> </div>
            </div>

            <?php
            if(count($validshareusers) > 1)
                {
                render_dropdown_question($lang["user"], "shareuser", $validshareusers, $shareuser, " class=\"stdwidth\"");
                }                

            //render_dropdown_question($lang["status"], "sharestatus", $statusoptions, $sharestatus, " class=\"stdwidth\"");
            //render_workflow_state_question($sharestatus);
            ?>
            <div class="Question">
                <label><?php echo $lang["expires"] ?></label>
                <input name="shareexpires" type=date class="stdwidth" min="<?php echo date("Y-m-d",time()); ?> value="<?php if($shareexpires != ""){echo $shareexpires;} ?>"></input>
                <div class="clearerleft"> </div>
            </div>

            <?php 
            render_share_password_question($sharepwd == "");
            ?>

            <h2 class="CollapsibleSectionHead" id="EmailUploadSectionHead"><?php echo $lang["action-email-upload-link"]; ?></h2>

            <div class="CollapsibleSection" id="EmailUploadSection">
                <div class="Question">
                    <label for="message"><?php echo $lang["message"]?></label>
                    <textarea class="stdwidth" rows=6 cols=50 name="message" id="message"></textarea>
                <div class="clearerleft"> </div>
                </div>
                <div class="Question">
                    <label for="users"><?php $lang["emailtousers"]; ?></label>
                    <?php $userstring=getval("users","");include "../include/user_select.php"; ?>
                    <div class="clearerleft"> </div>
                </div>
                <?php 
                if ($list_recipients)
                    {?>
                    <div class="Question">
                        <label for="list_recipients"><?php echo $lang["list-recipients-label"]; ?></label>
                        <input type=checkbox id="list_recipients" name="list_recipients">
                        <div class="clearerleft"> </div>
                    </div><?php
                    }?>
            </div>
            <div class="QuestionSubmit">
                <label for="buttons"> </label>			
                <input name="submit" type="submit" value="&nbsp;&nbsp;<?php {echo $lang["button-upload-link-create"] ;}?>&nbsp;&nbsp;" onclick="return CentralSpacePost(this.form,true);" />
            </div>
        </form>
        <script>
        jQuery('document').ready(function()
            {
            registerCollapsibleSections(true);
            });
        </script>


</div> <!-- End of BasicsBox -->
<?php


// TODO Show existing upload shares for this collection
include "../include/footer.php";