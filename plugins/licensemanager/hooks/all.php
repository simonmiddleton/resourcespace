<?php

function HookLicensemanagerAllExport_add_tables()
    {
    return array("resource_license"=>array("scramble"=>array("holder","license_usage","description")));
    }