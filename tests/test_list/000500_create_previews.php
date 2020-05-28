<?php
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}
include_once(dirname(__FILE__) . "/../../include/image_processing.php");

# Copy the default slideshow image to the location of the first resource
copy(dirname(__FILE__) . '/../../gfx/homeanim/1.jpg', get_resource_path(1,true));

# Try preview creation.
create_previews(1);

# Did it work? Look for additional image sizes.
$sizes=get_image_sizes(1);

# If preview creation worked there will now be a screen size also, so two sizes.
return (count($sizes)>1);
