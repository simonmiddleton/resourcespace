<?php

# Legacy. This was originally the path to the cron job. Include the new path.

include dirname(__FILE__) . "/../../batch/cron.php";

# Legacy hook - required because several third party plugins hook in to this page in this location.
hook("addplugincronjob");
