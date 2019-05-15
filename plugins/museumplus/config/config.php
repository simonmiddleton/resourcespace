<?php
// Constants



// API
$museumplus_host = '';
$museumplus_application = '';
$museumplus_api_user = '';
$museumplus_api_pass = '';
$museumplus_search_mpid_field = '';


// ResourceSpace settings
$museumplus_mpid_field = null;
$museumplus_resource_types = array();


// MuseumPlus - ResourceSpace mappings
// @todo: once mappings have been established - move to setup page of the plugin and then set it here to an empty array
$museumplus_rs_mappings = array(
    'ObjMgrFileMatchVrt' => 87,
    'ObjObjectTitleVrt' => 88,
    'ObjLiteratureRef.moduleReferenceItem' => 89,
    'ObjMaterialTechniqueTxt' => 90,
    'ObjPublicationStatusVoc' => 91,
    'ObjDimUniversalGrp.repeatableGroupItem' => 92,
    'DimDisplayVrt' => 92,
);
$museumplus_rs_saved_mappings = base64_encode(serialize($museumplus_rs_mappings));