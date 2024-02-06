<?php
# AJAX ratings save

include "../../include/db.php";

include "../../include/authenticate.php";

user_rating_save(getval("userref","",true),getval("ref","",true),getval("rating",""));

