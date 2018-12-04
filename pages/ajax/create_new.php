<?php
// Generic modal page to create new resource types or metadata fields

include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";
include_once __DIR__ . "/../../include/authenticate.php";
include_once __DIR__ . "/../../include/collections_functions.php";

$newtype = getval("type","");

if($newtype == "")
    {
    http_response_code(401);
    exit($lang['error-permissiondenied']);
    }

switch ($newtype)
    {
    case "resource_type":
        $targeturl = $baseurl_short . "pages/admin/admin_resource_types.php";
        $newparam = "newtype";
        $newtext = $lang["admin_resource_type_create"];
        $csrf_code = "admin_resource_types";
    break;
    
    case "resource_type_field":
        $targeturl = $baseurl_short . "pages/admin/admin_resource_type_fields.php";
        $newparam = "newfield";
        $newtext = $lang["admin_resource_type_field_create"];
        $csrf_code = "admin_resource_type_fields";
    break;
    }  
    
?>

<div class="BasicsBox" id="create_new">
    <form action="<?php echo $targeturl; ?>" onsubmit="return CentralSpacePost(this,true);">
	<div class="Question">
		<label><?php echo $newtext; ?></label>
        <?php generateFormToken($csrf_code); ?>
		<input type="text" class="medwidth" name="<?php echo $newparam ?>" id="<?php echo $newparam ?>" value="">
		<div class="clearerleft"> </div>
	</div>
	<div class="Question">
		<label />
		<input type="submit" class="medcomplementwidth" value="<?php echo $lang["save"]?>" />
		<input type="submit" class="medcomplementwidth" value="<?php echo $lang["cancel"]?>" onclick="ModalClose();" />
		<div class="clearerleft"> </div>
	</div>
    </form>
</div>
