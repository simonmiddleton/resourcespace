<?php
set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Start decoupling of resource types and fields to enable multi type support...");

// Populate new join table
ps_query("insert into resource_type_field_resource_type(resource_type_field,resource_type) select ref,resource_type from resource_type_field where type>0");

// Set global values
ps_query("update resource_type_field set global=1-least(1,resource_type)"); // Type is 0, global is 1. All higher values of type, global is zero.

set_sysvar(SYSVAR_UPGRADE_PROGRESS_SCRIPT, "Finished decoupling of resource types and fields to enable multi type support!");