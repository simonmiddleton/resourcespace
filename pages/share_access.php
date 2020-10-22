<?php
include_once "../include/db.php";
$k = getval("k","");
$resource = getval("resource","",true);
$collection = getval("collection","",true);
$return_url = urldecode(getval("return_url",""));

$share_password = getval("share_password","");
if($share_password != "" && getval("submit","") !="" && enforcePostRequest(false))
    {
    // Check the supplied password
    $check = check_share_password($k,$share_password,"");
    if($check)
        {
        if($return_url == "")
            {
            $return_url = $baseurl . "/?" . ($resource != "" ? "r=" . $resource : "c=" . $collection ) . "&k=". $k; 
            }
        header("Location: {$return_url}");
        exit();
        }
    else
        {
        sleep(5);
        $onload_message = array("title" => $lang["error"],"text" => $lang["share-invalid"]);
        }
    }
    
if($k == "")
    {
    $onload_message = array("title" => $lang["error"],"text" => $lang["share-invalid"]);
    }

include "../include/header.php";
?>
<div class="BasicsBox">
    
	<form method="post" action="<?php echo $baseurl_short ?>pages/share_access.php" >
	<?php generateFormToken("share_access"); ?>
    <input type=hidden name="collection" value="<?php echo htmlspecialchars($collection)?>">
    <input type=hidden name="resource" value="<?php echo htmlspecialchars($resource)?>">
    <input type=hidden name="k" value="<?php echo htmlspecialchars($k)?>">
    <input type=hidden name="return_url" value="<?php echo htmlspecialchars($return_url)?>">
	
	<div class="Question">
	<label><?php echo $lang["share-enter-password"] ?></label>
	<input name="share_password" id="share_password" type="password" class="stdwidth" />
	<div class="clearerleft"> </div>
	</div>
	
	<div class="QuestionSubmit">
	<label></label>
    <input type=hidden name="submit" value=true">
	<input name="submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["proceed"]?>&nbsp;&nbsp;" />
	</div>
	</form>
	
</div>

<?php
include "../include/footer.php";
?>
