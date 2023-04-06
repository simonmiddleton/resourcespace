<?php
$onchange_attr = $edit_autosave ? sprintf('onchange="AutoSave(%s);"', (int) $field['ref']) : '';
?>
<input
    type="date"
    name="<?php echo escape_quoted_data($name); ?>"
    id="<?php echo escape_quoted_data($name); ?>"
    value="<?php echo escape_quoted_data($value); ?>"
    <?php echo $onchange_attr; ?>
>
<hr>
<p>todo: delete everything below line (and including) once the work is done</p>
<?php
# Date uses same code as date + time
include "4.php";
