<?php

include_once __DIR__ . "/../include/vr_view_functions.php";

function HookVr_viewAllAdditionalheaderjs()
    {
	global $baseurl,$vr_view_google_hosted, $vr_view_js_url;
	if ($vr_view_google_hosted)
        {?>
        <script src="//storage.googleapis.com/vrview/2.0/build/vrview.min.js"></script>
        <?php 
        }
    else
        {?>
        <script type="text/javascript" src="<?php echo htmlspecialchars($vr_view_js_url) ?>"></script>
		<?php
        }
    }
