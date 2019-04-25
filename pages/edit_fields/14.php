<?php /* -------- Date Range ---------------------------- */ 

global $date_d_m_y, $chosen_dropdowns, $tabs_on_edit;

# Start with a null date
$start_dy="";
$start_dm=$start_dd=$start_dh=$start_di=-1;
$end_dy="";
$end_dm=$end_dd=$end_dh=$end__di=-1;


$rangedates = explode(",",$value);
natsort($rangedates);
$value = implode(",",$rangedates);
render_date_range_field($name,$value,false,false,$field);
	 
