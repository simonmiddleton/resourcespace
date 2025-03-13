<?php

include "../../include/boot.php";
include "../../include/authenticate.php";

$force_appearance = getval("force_appearance", "");

header('Content-Type: text/plain');
echo get_header_image(false, true, $force_appearance);
