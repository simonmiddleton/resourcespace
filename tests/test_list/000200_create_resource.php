<?php
command_line_only();




create_resource(1);

# Did it work?
return (get_resource_data(1)!==false);
