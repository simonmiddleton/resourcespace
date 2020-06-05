<?php
include_once "../include/db.php";

include "../include/authenticate.php";
include "../include/header.php";

$content=getvalescaped("content","");
if ($content!=""){$content=text($content);}else{$content="This is default content text. You can create text (including html) in Team Centre->Manage Content and display it here.";}

$allowed_tags = array_merge(array("a"),$permitted_html_tags);
$allowed_attributes = array_merge(array("href","target"),$permitted_html_attributes);
$content = strip_tags_and_attributes($content,$allowed_tags,$allowed_attributes);
?>

<div class="BasicsBox"> 
  <?php echo $content ?>
</div>

<?php
include "../include/footer.php";
?>
