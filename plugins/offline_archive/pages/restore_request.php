<?php

include '../../../include/db.php';
include '../../../include/authenticate.php'; 

$ref=getvalescaped("ref","",true);
$error=false;
# fetch the current search (for finding similar matches)
$search=getvalescaped("search","");
$order_by=getvalescaped("order_by","relevance");
$offset=getvalescaped("offset",0,true);
$restypes=getvalescaped("restypes","");
$starsearch=getvalescaped("starsearch","");
if (strpos($search,"!")!==false) {$restypes="";}
$archive=getvalescaped("archive",0,true);

$default_sort="DESC";
if (substr($order_by,0,5)=="field"){$default_sort="ASC";}
$sort=getval("sort",$default_sort);

if (getval("save","")!="")
    {
    $details=getvalescaped("request","");
    $templatevars['username']=$username . " (" . $useremail . ")";
    $templatevars['url']=$baseurl."/?r=".$ref;

    $htmlbreak="";
    global $use_phpmailer;
    if ($use_phpmailer){$htmlbreak="<br><br>";}
        
    $templatevars['details']=stripslashes($details);
    if ($templatevars['details']!=""){$adddetails=$lang["offline_archive_request_restore_reason"] . ": " . newlines($templatevars['details'])."\n\n";} else {return false;}
        

    $message=$lang["username"] . ": " . $username . " (" . $useremail . ")\n".$adddetails . $lang["clicktoviewresource"] . "\n\n". $templatevars['url'];

    $userconfirmmessage = $lang["offline_archive_requestsenttext"];
    $result=send_mail($email_notify,$applicationname . ": " . $lang["offline_archive_request_email_subject"] . " - $ref",$message,$useremail,$useremail);	
    resource_log($ref,"",0,$lang['offline_archive_resource_log_restore_request'],"","");	
    if ($result===false)
        {
        $error=$lang["requiredfields"];
        }
    }
include "../../../include/header.php";

?>
<div class="BasicsBox">
<?php
if (isset($userconfirmmessage))
	{
	echo "<div class=\"FormError\">" . $userconfirmmessage . "</div>";
	}

?>

<div class="BasicsBox"> 
  
  <h1><?php echo $lang["offline_archive_request_restore"]?></h1>
  <p><?php echo $lang["offline_archive_request_restore_text"]?></p>
  
    <form method="post" name="request_restore_form" id="request_restore_form" action="<?php echo $baseurl ?>/plugins/offline_archive/pages/restore_request.php?ref=<?php echo urlencode($ref)?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>">  
    <?php generateFormToken("request_restore_form"); ?>
    <input type=hidden name=ref value="<?php echo htmlspecialchars($ref)?>">

    <div class="Question">
        <label><?php echo $lang["resourceid"]?></label>
        <div class="Fixed"><?php echo htmlspecialchars($ref)?></div>
        <div class="clearerleft"> </div>
    </div>


    <div class="Question">
        <label for="request"><?php echo $lang["offline_archive_request_restore_reason"]?> <sup>*</sup></label>
        <textarea class="stdwidth" name="request" id="request" rows=5 cols=50><?php echo htmlspecialchars(getvalescaped("request","")) ?></textarea>
        <div class="clearerleft"> </div>
    </div>

    <div class="QuestionSubmit">
        <?php if ($error) { ?><div class="FormError">!! <?php echo $error ?> !!</div><?php } ?>
        <label for="buttons"> </label>			
        <input name="cancel" type="button" value="&nbsp;&nbsp;<?php echo $lang["cancel"]?>&nbsp;&nbsp;" onclick="document.location='<?php echo $baseurl_short?>pages/view.php?ref=<?php echo htmlspecialchars($ref)?>';"/>&nbsp;
        <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["offline_archive_request_restore"]?>&nbsp;&nbsp;" />
        </div>
    </form>
	
</div>
</div>

<?php
include "../../../include/footer.php";
?>
