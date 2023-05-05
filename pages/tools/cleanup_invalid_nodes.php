<?php
include "../../include/db.php";
include_once "../../include/authenticate.php";
if(!checkperm("a")){exit("Access denied");}

set_time_limit(0);

$restypes       = getval("restypes",[]);
$fields         = getval("fields",[]);
$dryrun         = getval("dryrun","") != "";
$deletedata     = getval("deletedata","")=="true";
       
$restypes = array_filter($restypes,"is_int_loose");
$allfields = get_resource_type_fields();
$validfields = [];
foreach($allfields as $field)
    {
    if(in_array($field["ref"],$fields))
        {
        $validfields[] = $field;
        }
    }

if(getval("submit","") != "")
    {
    
    }
    
include_once "../../include/header.php";


?>
<div class="BasicsBox">
	<p>    
	<a href="<?php echo $backurl ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a>
	</p>
	<h1>Cleanup invalid node data</h1>

	<form method="post" class="FormWide" action="<?php echo $baseurl_short ?>pages/tools/cleanup_invalid_nodes.php" onsubmit="return CentralSpacePost(this,true);">
     <?php generateFormToken("cleanup_invalid_nodes"); ?>
    <?php
    render_field_selector_question($lang["field"],"fields",[],"medwidth",false,$migrate_field);
    ?>
	<div class="Question" >
		<label for="dryrun" ><?php echo $lang["admin_resource_type_field_migrate_dry_run"] ?></label>
		<input class="medwidth" type="checkbox" name="dryrun" value="true">
        <div class="clearerleft"> </div>
	</div>
    <div class="Question" >
		<input type="hidden" id="submitinput" name="submit" value="">
		<input type="submit" name="submit" value="<?php echo $lang["action-submit-button-label"] ?>"" onclick="document.getElementById('submitinput').value='true';">
        <div class="clearerleft"> </div>
	</div>
    
	<div class="clearerleft"> </div>
    
	</form>
        
</div>
<?php


include_once "../../include/footer.php";


