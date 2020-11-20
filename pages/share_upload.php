<?php
include "../include/db.php";
include "../include/authenticate.php";

$collection	= getvalescaped('collection', 0, true);
$uploadkey  = getval("uploadkey","");

$shareinfo = get_upload_share_details($collection,$uploadkey);

$sharestatus = isset($shareinfo["status"]) ? $shareinfo["status"] : get_default_archive_state();
$sharepwd = isset($shareinfo["password"]) ? $shareinfo["password"] : "";
$shareuser = isset($shareinfo["user"]) ? $shareinfo["user"] : $userref;
$shareexpires = isset($shareinfo["user"]) ? $shareinfo["user"] : $userref;
$submitted = getval("submit","") != "";

$editing = $uploadkey != "" && can_edit_upload_share($collection,$uploadkey);

if($submitted)
    {
    edit_collection_external_access($uploadkey,-1,$shareexpires,"",$sharepwd="",1);
    }

include "../include/header.php";
?>
<div class="BasicsBox"> 	
	<form method=post id="shareuploadform" action="<?php echo $baseurl_short; ?>pages/share_upload.php?collection=<?php echo $collection; ?>">
        <input type="hidden" name="deleteshare" id="deleteshare" value="">
        <input type="hidden" name="shareexpiration" id="shareexpiration" value="">
        <input type="hidden" name="shareuser" id="shareuser" value="">
        <input type="hidden" name="generateurl" id="generateurl" value="">    
        <input type="hidden" name="uploadkey" id="uploadkey" value="<?php echo htmlspecialchars($uploadkey); ?>">
        <?php generateFormToken("shareuploadform");

        $page_header = $editing ? $lang["title-upload-link-edit"] : $lang["title-upload-link-create"];

        ?>
        <h1><?php echo $page_header; render_help_link("user/share-upload-link");?></h1>
        <?php
        if(isset($warningtext))
            {
            echo "<div class='PageInformal'>" . $warningtext . "</div>";
            }

        echo "<p><strong>" . $lang["warning-upload-link"] . "</strong></p>"; 
        echo "<p>" . $lang["warning-upload-instructions"] . "</p>";
        
        $validshareusers = array();
        foreach($upload_link_users as $upload_link_user)
            {
            $up_user = get_user($upload_link_user);
                
            if($up_user)
                {
                $validshareusers[$upload_link_user] = $up_user["fullname"] != "" ? $up_user["fullname"] : $up_user["username"];
                }            
            }
        if(!isset($validshareusers[$shareuser]))
            {
            $curshareuser = get_user($shareuser);
            $validshareusers[$shareuser]  = $curshareuser["fullname"] != "" ? $curshareuser["fullname"] : $curshareuser["username"];
            }

        render_dropdown_question($lang["user"], "shareuser", $validshareusers, $shareuser, " class=\"stdWidth\"");

        //render_dropdown_question($lang["status"], "sharestatus", $statusoptions, $sharestatus, " class=\"stdWidth\"");

        render_workflow_state_question($sharestatus);

              
        ?>
        <div class="Question">
            <label for="sharepassword"><?php echo htmlspecialchars($lang["share-set-password"]) ?></label>
            <input type="password" id="sharepassword" name="sharepassword" class="stdWidth">
        </div>
    </form>
</div> <!-- End of BasicsBox -->
<?php


// TODO Show existing upload shares for this collection
include "../include/footer.php";