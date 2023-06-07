<?php /* -------- Date ---------------------------- */ 

global $reset_date_upload_template, $reset_date_field, $blank_date_upload_template,$date_d_m_y;

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
    # Extract date parts taking account of BCE dates which have a leading -
    $value=$sd[0];
    $sd=explode("-",$value);    
    if (substr($value,0,1)==="-")
        {
        if (count($sd)>=2) $dy="-".$sd[1];
        if (count($sd)>=3) $dm=intval($sd[2]);
        if (count($sd)>=4) $dd=intval($sd[3]);
        }
    else 
        {
        if (count($sd)>=1) $dy=$sd[0];
        if (count($sd)>=2) $dm=intval($sd[1]);
        if (count($sd)>=3) $dd=intval($sd[2]);
        }
    }  
    
}
if($date_d_m_y){  
?>
<select id="<?php echo $name; ?>-d" name="<?php echo $name?>-d"

<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["day"]?></option>
<?php for ($m=1;$m<=31;$m++) {?><option <?php if($m==$dd){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>
    
<select id="<?php echo $name; ?>-m" name="<?php echo $name?>-m"
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["month"]?></option>
<?php for ($m=1;$m<=12;$m++) {?><option <?php if($m==$dm){echo " selected";}?> value="<?php echo sprintf("%02d",$m)?>"><?php echo $lang["months"][$m-1]?></option><?php } ?>
</select>
<?php
}
else{
?>
<select id="<?php echo $name; ?>-m" name="<?php echo $name?>-m"
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["month"]?></option>
<?php for ($m=1;$m<=12;$m++) {?><option <?php if($m==$dm){echo " selected";}?> value="<?php echo sprintf("%02d",$m)?>"><?php echo $lang["months"][$m-1]?></option><?php } ?>
</select>

<select id="<?php echo $name; ?>-d" name="<?php echo $name?>-d"
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
	jQuery('[id^=<?php echo $name . '-';?>]').on('change', function(){
		let day   = jQuery('#<?php echo $name;?>-d').val();
		let month = jQuery('#<?php echo $name;?>-m').val();
		let year  = jQuery('#<?php echo $name;?>-y').val(); 
        // The minimum viable non-blank date must have a valid year 
        let year_formatted="";
		if (year != "") {
            let year_is_valid=false;
            if(jQuery.isNumeric(year)) {
                if(year >=-9999 && year <=9999) {
                    year_is_valid=true;
                    // Refresh year to ensure it is in the correct format yyyy or -yyyy
                    if(year>=0) {
                        year_formatted = year.toString().padStart(4,'0');
                    }
                    else {
                        year_formatted = "-"+(0-year).toString().padStart(4,'0');
                    }
                    jQuery(this).val(year_formatted);
                }
            }
            if (!year_is_valid) {
                styledalert(<?php echo "'" . $lang["error"] . "','" . $lang["invalid_date_generic"] . "'" ?>);
                jQuery(this).val(jQuery.data(this, 'current'));
            }
        }
        // Fully entered date viability check  
		if(jQuery.isNumeric(year) && jQuery.isNumeric(day) && jQuery.isNumeric(month)){
			// Construct an ISO date string formatted as yyyy-mm-dd (or -yyyy-mm-dd for BCE date)
			let date_entered_iso = year_formatted + '-' + month + '-' + day;
            // When the whole date is entered then date object creation allows the presence of additional days 
            let date_entered_obj = new Date(date_entered_iso);
            let date_viable_iso = "";
            // To check the viability of the entered date we must convert the date object back to ISO format
            if (year>=0) {
                date_viable_iso = date_entered_obj.toISOString().split('T')[0];
            }
            else { // BCE date
                date_entered_obj.setYear(year); // Set year explicitly to ensure BCE year is set correctly
                date_viable_iso = date_entered_obj.toISOString().split('T')[0];
                // Adjust BCE date so the year is always four digits long
                date_viable_iso = "-" + date_viable_iso.substring(3);
            }
            // So an entered ISO date of 2021-02-30 will convert back into 2021-03-02
            // If the entered ISO date matches its converted back counterpart then it's a viable date  
            if(date_entered_iso !== date_viable_iso){
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
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["hour-abbreviated"]?></option>
<?php for ($m=0;$m<=23;$m++) {?><option <?php if($m==$dh){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>

<select id="<?php echo $name; ?>-i" name="<?php echo $name?>-i"
<?php if ($edit_autosave) {?>onChange="AutoSave('<?php echo $field["ref"]?>');"<?php } ?>
><option value=""><?php echo $lang["minute-abbreviated"]?></option>
<?php for ($m=0;$m<=59;$m++) {?><option <?php if($m==$di){echo " selected";}?>><?php echo sprintf("%02d",$m)?></option><?php } ?>
</select>
<?php }

