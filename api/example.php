<?php
/*
 *
 *   Example of API integration
 *   --------------------------
 *
 *   Pure PHP example... does not require any local RS elements (connects to RS via HTTP).
 *   This code would be on a client (non ResourceSpace) system.
 *
 *   For documentation please see: http://www.resourcespace.com/knowledge-base/api/
 *   
*/

$private_key="bfab0581232784c7b04a5c9e297232359d0110fdac2275ee97ce3a7c592eace6"; # <---  From RS user edit page for the user to log in as
$user="admin"; # <-- RS username of the user you want to log in as

# Some example function calls.
#
#$query="user=" . $user . "&function=do_search&param1="; # <--- The function to execute, and parameters
$query="user=" . $user . "&function=get_resource_field_data&param1=1"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=create_resource&param1=1"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=update_field&param1=1&param2=8&param3=Example"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=delete_resource&param1=1"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=copy_resource&param1=2"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=get_resource_data&param1=2"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=get_alternative_files&param1=2"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=get_resource_types"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=add_alternative_file&param1=2&param2=Test"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=get_resource_log&param1=2"; # <--- The function to execute, and parameters
#$query="user=" . $user . "&function=upload_file_by_url&param1=2&param2=&param3=&param4=&param5=" . urlencode("http://www.montala.com/img/slideshow/montala-bg.jpg"); # <--- The function to execute, and parameters

# Sign the query using the private key
$sign=hash("sha256",$private_key . $query);

# Make the request.
$results=file_get_contents("http://localhost/resourcespace/api/?" . $query . "&sign=" . $sign);

# Output the JSON
echo "<pre>";
echo htmlspecialchars($results);
