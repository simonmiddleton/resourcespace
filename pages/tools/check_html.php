<?php

# Quick script to check valid HTML
include "../../include/db.php";

include "../../include/authenticate.php";

echo "<pre>";

$text=getval("text","");

$html=trim($text);
$result=validate_html($html);
if ($result===true || $html=="")
    {
    echo "OK\n";
    }
else
    {
    echo "FAIL - $result \n";
    }
echo "</pre>";
?>
