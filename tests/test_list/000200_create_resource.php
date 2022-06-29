<?php
command_line_only();

$resourcenew=create_resource(1,0);
# Did it work?
return (get_resource_data($resourcenew)!==false);
