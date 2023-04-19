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
<?php
