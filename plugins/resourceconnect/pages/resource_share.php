<?php

/**
 * 
 * This file provides a resource sharing link for remote resources (retrieved via resourceconnect) in a local collection.
 * 
 * A URL is shared (the url stored in the local database table for remote resources in a local collection) that allows a logged-in user to view a resource
 * 
 */


include "../../../include/db.php";   
include "../../../include/header.php";
?>
<div class="BasicsBox">
    <h1><?php echo $lang["share-resource"]?></h1> 
    <p><?php echo $lang["generateurlinternal"];?></p>
    <p><input class="URLDisplay" type="text" value="<?php echo getvalescaped("url","") ?>"></p>                      
</div> 
<!-- BasicsBox -->
<?php
include "../../../include/footer.php";
?>
