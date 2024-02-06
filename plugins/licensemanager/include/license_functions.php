<?php

function licensemanager_get_licenses($ref)
    {     
    return ps_query("select license.ref,license.outbound,license.holder,license.license_usage,license.description,license.expires from license join resource_license on license.ref=resource_license.license where resource_license.resource=? order by ref", ['i', $ref]);
    }
