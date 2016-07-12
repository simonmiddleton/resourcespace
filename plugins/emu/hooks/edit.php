<?php
include_once dirname(__FILE__) . '/../include/emu_api.php';
include_once dirname(__FILE__) . '/../include/emu_functions.php';

function HookEmuEditEdithidefield($field)
    {
    global $ref, $resource, $emu_irn_field, $emu_resource_types;

    if($emu_irn_field == $field['ref'] && 0 > $ref && in_array($resource['resource_type'], $emu_resource_types))
        {
        return true;
        }

    return false;
    }