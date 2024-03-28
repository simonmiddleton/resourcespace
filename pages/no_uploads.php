<?php

include "../include/boot.php";

include "../include/authenticate.php"; 


include "../include/header.php";

?>
<div class="BasicsBox">
    <p><a href="<?php echo $baseurl?>/pages/<?php echo $default_home_page?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo escape($lang["home"]); ?></a></p>
    <h1><?php echo escape($lang['disk_size_no_upload_heading']); ?></h1>
    <p><?php echo escape($lang['disk_size_no_upload_explain']); ?></p>
</div>
<?php

include "../include/footer.php";
