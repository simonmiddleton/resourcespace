<?php
include_once "../include/db.php";

if (!hook("authenticate")){include "../include/authenticate.php";}

include "../include/header.php";
?>

<div class="BasicsBox">
  <h1><?php echo escape($lang["contactus"]); ?></h1>
  <p><?php echo text("contact")?></p>
</div>

<?php
include "../include/footer.php";
?>
