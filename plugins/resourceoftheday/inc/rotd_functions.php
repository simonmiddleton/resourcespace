<?php

function get_resource_of_the_day()
    {
    global $rotd_field;

    if(is_numeric($rotd_field))
        {
        # Search for today's resource of the day.
        $rotd = sql_value("SELECT resource value FROM resource_data WHERE resource>0 AND resource IN (SELECT ref FROM resource WHERE archive=0 AND access=0) AND resource_type_field=$rotd_field AND value LIKE '" . date("Y-m-d") . "%' limit 1;",0);
        if ($rotd!=0) {return $rotd;} # A resource was found?

        # No resource of the day today. Pick one at random, using today as a seed so the same image will be used all of the day.
        $rotd = sql_value("SELECT resource value FROM resource_data WHERE resource>0 AND resource IN (SELECT ref FROM resource WHERE archive=0 AND access=0) AND resource_type_field=$rotd_field AND length(value)>0 ORDER BY rand(" . date("d") . ") limit 1;",0);
        if ($rotd!=0) {return $rotd;} # A resource was found now?
        }
    # No resource of the day fields are set. Return to default slideshow functionality.
    return false;
    }

?>
