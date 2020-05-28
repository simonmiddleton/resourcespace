<?php
# AJAX ratings save

include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";

user_rating_save(getvalescaped("userref","",true),getvalescaped("ref","",true),getvalescaped("rating",""));

?>
