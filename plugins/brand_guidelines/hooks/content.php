<?php

declare(strict_types=1);

/**
 * Render decorator of custom fields used by Brand Guidelines
 * @param array $field Custom field structure - {@see process_custom_fields_submission()}
 * @return bool Returns true to indicate to ResourceSpace that the core render functionality is being overriden by the
 * plugin (for this field type), false otherwise.
 */
function HookBrand_guidelinesContentRender_custom_fields_default_case_override(array $field): bool
{
    $field_id = $field['html_properties']['id'];
    $field_name = $field['html_properties']['name'];
    $field_value = $field['value'];

    if ($field['type'] === FIELD_TYPE_NUMERIC) {
        $attrs = '';
        $constraints = $field['constraints'] ?? [];
        foreach ($constraints as $attr => $val) {
            if (in_array($attr, ['min', 'step'])) {
                $attrs .= sprintf('%s="%s"', $attr, escape((string) $val));
            }
        }
        ?>
        <input
            id="<?php echo escape($field_id); ?>"
            class="stdwidth"
            name="<?php echo escape($field_name); ?>"
            value="<?php echo escape($field_value); ?>"
            type="number"
            <?php echo $attrs; ?>
        >
        <?php
        return true;
    }
    return false;
}

/**
 * Validate custom fields' submitted input.
 * @return false|string Return custom fields' type input validation error or false to let ResourceSpace core handling
 * to run.
 */
function HookBrand_guidelinesContentProcess_custom_fields_submission_validator(array $field)
{
    $field_value = $field['value'];

    if (
        $field['type'] === FIELD_TYPE_NUMERIC
        && !(is_positive_int_loose($field_value) && $field_value < $field['constraints']['min'])
    ) {
        return sprintf(
            '%s. %s: %s',
            $GLOBALS['lang']['requiredfields-general'],
            i18n_get_translated($field['title']),
            str_replace('?', '1', $GLOBALS['lang']['shouldbeormore'])
        );
    }

    return false;
}
