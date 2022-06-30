<?php /* -------- Date ---------------------------- */ 

global $reset_date_upload_template, $reset_date_field, $blank_date_upload_template,$date_d_m_y, $chosen_dropdowns;

# Start with a null date
$dy="";
$dm=$dd=$dh=$di=-1;

if(!$blank_date_upload_template || $ref>0 || '' != getval('submitted', '')) {
	
if ($value!="")
	{
    #fetch the date parts from the value
    $sd=explode(" ",$value);
    if (count($sd)>=2)
    	{
    	# Attempt to extract hours and minutes from second part.
    	$st=explode(":",$sd[1]);
    	if (count($st)>=2)
    		{
    		$dh=intval($st[0]);
    		$di=intval($st[1]);
    		} 
    	}
    $value=$sd[0];
    $sd=explode("-",$value);    
	if (count($sd)>=1) $dy=$sd[0];
	if (count($sd)>=2) $dm=intval($sd[1]);
    if (count($sd)>=3) $dd=intval($sd[2]);
    }  
    
}
if($date_d_m_y){  
?>
<select id="<?php echo $name; ?>-d" name="<?php echo $name?>-d"
<?php if ($chosen_dropdowns) {?>class="ChosenDateDay"<?php } ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["day"]?></option>
<?php for ($m=1;$m<=31;$m++) {?><option <?php if($m==$dd){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>
    
<select id="<?php echo $name; ?>-m" name="<?php echo $name?>-m"
<?php if ($chosen_dropdowns) {?>class="ChosenDateMonth"<?php } ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["month"]?></option>
<?php for ($m=1;$m<=12;$m++) {?><option <?php if($m==$dm){echo " selected";}?> value="<?php echo sprintf("%02d",$m)?>"><?php echo $lang["months"][$m-1]?></option><?php } ?>
</select>
<?php
}
else{
?>
<select id="<?php echo $name; ?>-m" name="<?php echo $name?>-m"
<?php if ($chosen_dropdowns) {?>class="ChosenDateMonth"<?php } ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["month"]?></option>
<?php for ($m=1;$m<=12;$m++) {?><option <?php if($m==$dm){echo " selected";}?> value="<?php echo sprintf("%02d",$m)?>"><?php echo $lang["months"][$m-1]?></option><?php } ?>
</select>

<select id="<?php echo $name; ?>-d" name="<?php echo $name?>-d"
<?php if ($chosen_dropdowns) {?>class="ChosenDateDay"<?php } ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["day"]?></option>
<?php for ($m=1;$m<=31;$m++) {?><option <?php if($m==$dd){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>
<?php
}
?>
<script>
	//Get value of the date element before the change
	jQuery('[id^=<?php echo $name;?>]').on('focus', function(){
		jQuery.data(this, 'current', jQuery(this).val());
	});
	//Check the value of the date after the change
	jQuery('[id^=<?php echo $name;?>]').on('change', function(){
		let day   = jQuery('#<?php echo $name;?>-d').val();
		let month = jQuery('#<?php echo $name;?>-m').val();
		let year  = jQuery('#<?php echo $name;?>-y').val(); 
		if (year != "" && !jQuery.isNumeric(year))
			{
			styledalert(<?php echo "'" . $lang["error"] . "','" . $lang["invalid_date_generic"] . "'" ?>);
			jQuery(this).val(jQuery.data(this, 'current'));
			}
		if(jQuery.isNumeric(year) && jQuery.isNumeric(day) && jQuery.isNumeric(month)){
			//format date string into yyyy-mm-dd
			let date_string = year + '-' + month + '-' + day;
			//get a timestamp from the date string and then convert that back to yyyy-mm-dd
			let date		= new Date(date_string).toISOString().split('T')[0];
			//check if the before and after are the same, if a date like 2021-02-30 is selected date would be 2021-03-02
			if(date_string !== date){
				styledalert(<?php echo "'" . $lang["error"] . "','" . $lang["invalid_date_generic"] . "'" ?>);
				jQuery(this).val(jQuery.data(this, 'current'))
			}
		}
	})
</script>
<label class="accessibility-hidden" for="<?php echo $name; ?>-y"><?php echo $lang["year"]; ?></label>
<input id="<?php echo $name; ?>-y" type=text size=5 name="<?php echo $name?>-y" value="<?php echo $dy?>" <?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>>


<?php hook("addtoeditdate","",array($field));?>

<?php if ($field["type"]!=10) { ?>
<!-- Time (optional) -->
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

<select id="<?php echo $name; ?>-h" name="<?php echo $name?>-h"
<?php if ($chosen_dropdowns) {?>class="ChosenDateHour"<?php } ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["hour-abbreviated"]?></option>
<?php for ($m=0;$m<=23;$m++) {?><option <?php if($m==$dh){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>

<select id="<?php echo $name; ?>-i" name="<?php echo $name?>-i"
<?php if ($chosen_dropdowns) {?>class="ChosenDateMinute"<?php } ?>
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["minute-abbreviated"]?></option>
<?php for ($m=0;$m<=59;$m++) {?><option <?php if($m==$di){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>
<?php } ?>

