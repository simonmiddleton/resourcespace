<?php
// Get config from ResourceSpace
global $simplesamlconfig;
foreach($simplesamlconfig["metadata"] as $idp => $idpmetadata)
    {
    $metadata[$idp] = $idpmetadata;
    }
