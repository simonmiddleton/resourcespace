<?php
include "../include/db.php";
include_once "../include/general.php";
include "../include/authenticate.php"; 
include_once "../include/collections_functions.php";
include_once "../include/resource_functions.php";
include_once "../include/search_functions.php"; 

$ref = getvalescaped("ref",0,true);

if(checkperm("b") || !collection_writeable($ref))
    {
    http_response_code(403);
	$error = $lang["error-permissiondenied"];
    error_alert($error, false);
	exit();
    }

# Fetch collection data
$list = get_user_collections($userref);
$collection = get_collection($ref);
if ($collection === false || (count($list) <= 1 && in_array($ref,array_column($list,"ref"))))
    {
    // Collection not found or user is attempting to copy resources to one of their own collections but only has one collection
    http_response_code(404);
    $error = $lang['error-collectionnotfound'];
    error_alert($error, false);
    exit();
    }

if (getval("submitted","")!="" && enforcePostRequest(false))
	{
    $copyfrom = getval("collection_copy_from",0,true);
    if($copyfrom > 0 && collection_readable($copyfrom))
        {
        copy_collection($copyfrom,$ref);
        redirect ($baseurl_short . "pages/search.php?search=!collection" . $ref);
        exit();
        }
    }

include "../include/header.php";
?>
<div class="BasicsBox">
<h1><?php echo $lang["collection_copy_resources"]?></h1>
<p><?php echo text("introtext")?></p>
<form method=post id="collection_copy_form" action="<?php echo $baseurl_short?>pages/collection_copy_resources.php">
    <?php generateFormToken("collection_copy_form"); ?>
	<input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">
	<input type=hidden name="submitted" value="true">
	<div class="Question">
		<label for="name"><?php echo $lang["collection"]?></label>
		<div class="Fixed"><?php echo htmlspecialchars(i18n_get_collection_name($collection, $index="name")); ?></div >
		<div class="clearerleft"> </div>
	</div>
    <div class="Question">
	    <label for='collection_copy_from' id='label_collection'><?php echo $lang["copyfromcollection"]; ?></label>
        <select name='collection_copy_from' id='collection_copy_from'>
    
        <?php
        for ($n=0;$n<count($list);$n++)
            {
            if($ref == $list[$n]["ref"])
                {
                continue;
                }

            #show only active collections if a start date is set for $active_collections 
            if (strtotime($list[$n]['created']) > ((isset($active_collections)) ? strtotime($active_collections) : 1) || ($list[$n]['name']=="My Collection" && $list[$n]['user']==$userref))
                    {
                    echo "<option value='" . $list[$n]["ref"] . "'>" . htmlspecialchars(i18n_get_collection_name($list[$n])) . "</option>\n";
                    }
            }
            ?>
        </select>
	</div>

	<div class="QuestionSubmit">
		<label for="buttons"> </label>			
		<input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["copy"]?>&nbsp;&nbsp;" />
	</div>
</form>
</div>

<?php		
include "../include/footer.php";