<?php

function HookTms_linkAllUpdate_field($resource,$field,$value,$existing)
        {
	global $tms_link_object_id_field,$tms_link_resource_types,$lang,$tms_link_field_mappings_saved;
        $resdata=get_resource_data($resource);
        if(!in_array($resdata["resource_type"],$tms_link_resource_types)){return false;}
	
	if($resource<0 || $field!=$tms_link_object_id_field){return false;}

        $tms_object_id=intval($value);
        $tmsdata=tms_link_get_tms_data($resource,$tms_object_id);

        // Update resource with TMS data
        $tms_link_field_mappings=unserialize(base64_decode($tms_link_field_mappings_saved));
        debug("tms_link: updating resource id #" . $resource);
        foreach($tms_link_field_mappings as $tms_link_column_name=>$tms_link_field_id)
                {
                if($tms_link_field_id!="" && $tms_link_field_id!=0 && isset($tmsdata[$tms_link_column_name]) && ($tms_link_field_id!=$tms_link_object_id_field))
                        {
                        debug("tms_link: updating field " . $field  . " with data from column " . $tms_link_column_name  . " for resource id #" . $resource);
                        update_field($resource,$tms_link_field_id,escape_check($tmsdata[$tms_link_column_name]));
                        }
                }
        return true;
        }
