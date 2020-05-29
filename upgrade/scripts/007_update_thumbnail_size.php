<?php

include_once __DIR__ . "/../../include/db.php";


sql_query("UPDATE preview_size SET width = 175, height = 175 WHERE id = 'thm' AND width < 175 AND height < 175");