<?php

$rootlevel="/resourcespace/filestore/iiif/";

$path=substr($_SERVER["REQUEST_URI"],strlen($rootlevel));

$xpath=explode("/",$path);

if (count($xpath)==1)
	{
	# Root level request - send information file only

	http_response_code(200); # Send OK
	header("Content-type: application/json");
	?>
	{
	 	"@context" : "http://iiif.io/api/image/2/context.json",
  		"@id" : "http://www.example.org/image-service/abcd1234/1E34750D-38DB-4825-A38A-B60A345E591C",
		"protocol" : "http://iiif.io/api/image",
		"width" : 6000,
		"height" : 4000,
		"sizes" : [
    			{"width" : 150, "height" : 100},
    			{"width" : 600, "height" : 400},
    			{"width" : 3000, "height": 2000}
  			],
  		"tiles": [
    			{"width" : 512, "scaleFactors" : [1,2,4,8,16]}
  			],
		"profile" : [ "http://iiif.io/api/image/2/level2.json" ]
	}
	<?php
	}

