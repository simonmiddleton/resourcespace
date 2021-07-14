<?php

include_once "../../../include/db.php";
include_once "../../../include/authenticate.php";

$filename=getval("filename","");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
header("Content-Type: image/svg+xml");
echo getval("svg","");
