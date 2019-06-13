<?php
include_once "../include/db.php";
include_once "../include/general.php";
include_once "../include/collections_functions.php";

# External access support (authenticate only if no key provided)
$k=getvalescaped("k","");

$k_shares_collection=getvalescaped("collection","");
$k_shares_ref=getvalescaped("ref","");
$ref=getvalescaped("ref","");

# Check access key because we need to honor terms requirement at user group override level
if ($k!="") 
	{
	if ($k_shares_collection != "") 
		{
		if (!check_access_key_collection(getvalescaped("collection","",true),$k)) {include "../include/authenticate.php";}
		}
	elseif ($k_shares_ref != "") 
		{
		if (!check_access_key(getvalescaped("ref",""),$k)) {include "../include/authenticate.php";}
		}
	}
else
	{
	include "../include/authenticate.php";
	}

$url=getvalescaped("url","pages/home.php?login=true");

$newurl = hook("beforeredirectchangeurl");
if(is_string($newurl))
    {
    $url = $newurl;
    }

$terms_save=getvalescaped('save', '');
$terms_url_accepted="";
if('' != $terms_save && enforcePostRequest(false))
    {
	$terms_iaccept=getvalescaped('iaccept', '');
    if('on' == $terms_iaccept)
        {
		sql_query("UPDATE user SET accepted_terms = 1 WHERE ref = '{$userref}'");
		$terms_url_accepted=(strpos($url, "?")?"&":"?") . "iaccept=".$terms_iaccept;
        }

	$url.=$terms_url_accepted;

    if(false !== strpos($url, 'http'))
        {
        header("Location: {$url}");
        exit();
        }
    else
        {
		redirect($url);
		
        }
    }

if($terms_download == false && getval("noredir","") == "")
    {
    redirect($url);
    }

include "../include/header.php";
?>
<div class="BasicsBox"> 
  <h1><?php echo $lang["termsandconditions"]?></h1>
  <p><?php echo text("introtext")?></p>
  
 	<div class="Question">
	<label><?php echo $lang["termsandconditions"]?></label>
	<div class="Terms"><?php 
		$termstext=text("terms");
		$modified_termstext=hook('modified_termstext');
		if($modified_termstext!=''){$termstext=$modified_termstext;}
		if (is_html($termstext)){
			echo $termstext;
		} else {
			echo txt2html($termstext);
	}?></div>
	<div class="clearerleft"> </div>
	</div>
	
	<form method="post" action="<?php echo $baseurl_short?>pages/terms.php?k=<?php echo urlencode($k); ?>" 
		onSubmit="if (!document.getElementById('iaccept').checked) {alert('<?php echo $lang["mustaccept"] ?>');return false;}">
	<?php generateFormToken("terms"); ?>
    <input type=hidden name="url" value="<?php echo htmlspecialchars($url)?>">
	
	<div class="Question">
	<label for="iaccept"><?php echo $lang["iaccept"] ?></label>
	<input type="checkbox" name="iaccept" id="iaccept" />
	<div class="clearerleft"> </div>
	</div>
	
	<div class="QuestionSubmit">
        <label></label>
        <input name="save"
               type="submit"
               value="&nbsp;&nbsp;<?php echo $lang["proceed"]?>&nbsp;&nbsp;"
               <?php hook('terms_save_input_attributes', '', array($ref, $url)); ?>/>
	</div>
	</form>
</div>

<?php
include "../include/footer.php";
?>
