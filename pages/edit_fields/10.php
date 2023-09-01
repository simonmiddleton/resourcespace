<?php
$valid_date = validateDatetime($value, 'Y-m-d') || trim($value) === '';
if ($GLOBALS['use_native_input_for_date_field'] && $valid_date)
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
elseif(!$valid_date && $GLOBALS['use_native_input_for_date_field'])
    {
    include '0.php';
    ?>
    <i aria-hidden="true" class="fa fa-fw fa-info" title="<?php echo escape_quoted_data(str_replace('%%VALUE%%', $value, $lang['error_invalid_revert_date']));?>"></i>
    <?php
    }
else
    {
    // Legacy: Date uses same code as date + time
    include '4.php';
    }