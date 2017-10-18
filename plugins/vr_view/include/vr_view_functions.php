<?php
/**
* Check whether VR view is to be enabled for the specified resource
* 
* @uses get_resource_type_field()
* @uses get_data_by_field()
* 
* @param array $resource     Resource data array (not resource field data)
* 
* @return boolean
*/
function VrViewUseVR($resource)
    {
    global $vr_view_restypes, $vr_view_projection_field, $vr_view_projection_value, $NODE_FIELDS;
        
    if(!in_array($resource['resource_type'],$vr_view_restypes))
        {
        // Not a valid VR image
        return false;
        }
        
    if($vr_view_projection_field != 0)
        {
		$fieldtype = get_resource_type_field($vr_view_projection_field);
		if(in_array($fieldtype['type'],$NODE_FIELDS))
			{
			$resnodes = get_resource_nodes($resource["ref"], $vr_view_projection_field, true);
			$resvals = array_column($resnodes,"name");
			}
		else
			{
			$resdata = get_data_by_field($resource["ref"],$vr_view_projection_field);
			$resvals = explode(",",$resdata);
			}
		
        if(!in_array($vr_view_projection_value, $resvals))
            {
			// Not a valid VR image
            return false;
            }
        }
        
    // This is a valid resource type and either meets metadata criteria or no criteris is set 
    return true;
    }
    
    
    
    
function VrViewRenderPlayer($ref,$source, $isvideo = false,$width=852, $height=600, $parentdivid = "", $scope = "")
    {
    global $vr_view_projection_field, $vr_view_projection_value, $vr_view_stereo_field, $vr_view_stereo_value;
    global $vr_view_yaw_only_field, $vr_view_yaw_only_value, $vr_view_autopan, $vr_view_vr_mode_off;
	global $NODE_FIELDS;
	
    // Check for stereo value 
    $stereo = false;
    if($vr_view_stereo_field !=0 )
        {	
		$stereofieldtype = get_resource_type_field($vr_view_stereo_field);
		if(in_array($stereofieldtype['type'],$NODE_FIELDS))
			{
			$resnodes = get_resource_nodes($ref, $vr_view_stereo_field, true);
			$resvals = array_column($resnodes,"name");
			}
		else
			{
			$resdata = get_data_by_field($ref,$vr_view_stereo_field);
			$resvals = explode(",",$resdata);
			}
		
        if(in_array($vr_view_stereo_value, $resvals))
            {
            $stereo = true;
            }
        }
        
    // Check for yaw only  value
    $yaw_only = false;
	if($vr_view_yaw_only_field !=0 )
        {	
		$stereofieldtype = get_resource_type_field($vr_view_yaw_only_field);
		if(in_array($stereofieldtype['type'],$NODE_FIELDS))
			{
			$resnodes = get_resource_nodes($ref, $vr_view_yaw_only_field, true);
			$resvals = array_column($resnodes,"name");
			}
		else
			{
			$resdata = get_data_by_field($ref,$vr_view_yaw_only_field);
			$resvals = explode(",",$resdata);
			}
		
        if(in_array($vr_view_yaw_only_value, $resvals))
            {
            $yaw_only = true;
            }
        }
        
	?>
    <div id="<?php echo $parentdivid; ?>">
		<div id="<?php echo htmlspecialchars($scope); ?>vrview" style="width:<?php echo htmlspecialchars($width); ?>px;margin:auto;"></div>
    </div>
    <script>
        jQuery(document).ready(function ()
            {
            var vrView = new VRView.Player('#<?php echo htmlspecialchars($scope); ?>vrview', {
            <?php echo $isvideo ? "video" : "image" ?>: '<?php echo $source; ?>',
                is_stereo: <?php echo $stereo ? "true": "false" ?>,
                width: <?php echo htmlspecialchars($width) ?>,
                height: <?php echo htmlspecialchars($height) ?>,
                is_yaw_only: <?php echo $yaw_only ? "true": "false" ?>,
                //default_yaw: 0,
                is_vr_off: <?php echo $vr_view_vr_mode_off ? "true": "false" ?>,
                is_autopan_off: <?php echo $vr_view_autopan ? "false": "true" ?>,
                is_debug:false
          });
        });
	</script>
	<?php
    return true;
	}
