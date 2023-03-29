<?php
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Start decoupling of resource types and fields to enable multi type support...");

// Clear out any entries added by dbstruct
ps_query("TRUNCATE resource_type_field_resource_type");

// Populate new join table
ps_query("INSERT INTO resource_type_field_resource_type(resource_type_field,resource_type) SELECT ref,resource_type FROM resource_type_field WHERE resource_type>0 AND resource_type<999");

// Set global values
ps_query("UPDATE resource_type_field SET global=IF(resource_type=999,1,1-LEAST(1,resource_type))"); // If resource_type is 0 or 999, global is 1. For all higher values of resource_type, global is zero.


// TODO Remove fields that do not inherit global fields, this option will be hidden now
// $noglobals = ps_array("SELECT ref value FROM resource_type WHERE inherit_global_fields=0");


set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Finished decoupling of resource types and fields to enable multi type support!");