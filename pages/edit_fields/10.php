<?php
if ($GLOBALS['use_native_input_for_date_field'])
    {
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
    }
else
    {
    // Legacy: Date uses same code as date + time
    include '4.php';
    }
