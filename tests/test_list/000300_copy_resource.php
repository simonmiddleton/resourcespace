<?php
command_line_only();

global $debug_log, $debug_log_location, $debug_extended_info;
$debug_log=true;
$debug_log_location = "/var/log/resourcespace/debug_dev.log";
$debug_extended_info = true;



$original = create_resource(1);
update_field($original,8,"Test title");
echo "Created resource " . $original . "\n";

$new=copy_resource($original);

echo "Created resource " . $new . "\n";

# Did it work?
if (get_resource_data($new)===false) {return false;}

# Was the title field we set on the original resource copied?
if (get_data_by_field($new,8)!="Test title"){return false;}

# Was the title field change logged?
$resource_log = get_resource_log($new,-1,["r.type" => "e"])['data'];
$change_logged=false;
foreach($resource_log as $log)
    {
    if ($log["type"] == "e" && $log["title"] == "Title" && $log["diff"] == "+ Test title")
        {
            $change_logged=true;
            break;
        }
    }

if (!$change_logged){return false;}

return true;