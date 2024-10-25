<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

/**
 * Helper function for access control purposes to check if user can view the Brand Guidelines
 */
function acl_can_view_brand_guidelines(): bool {
    return !checkperm('bgv');
}

/**
 * Helper function for access control purposes to check if user can edit the Brand Guidelines
 */
function acl_can_edit_brand_guidelines(): bool {
    return checkperm('a') || checkperm('bge');
}

/**
 * Check if a page item is a section
 * @param array{parent: int|numeric-string} $I Generic page data structure
 */
function is_section(array $I): bool {
    return (int) $I['parent'] === 0;
}

/**
 * Check if a page item is just a page (i.e. NOT a section)
 * @param array{parent: int|numeric-string} $I Generic page data structure
 */
function filter_only_pages(array $I): bool
{
    return !is_section($I);
}

/**
 * Helper function to cast a re-order direction to an integer.
 * @param 'up'|'down' Re-order direction
 * @return -1|1
 */
function cast_reorder_direction_to_int(string $direction): int
{
    return $direction === 'up' ? -1 : 1;
}

/**
 * Compute moving (for re-ordering purposes) an item based on the requested direction (up/down). Example:
 * 
 * ```php
 * $changed_list = array_replace(
 *     $all_pages_index,
 *     [$ref => compute_item_order($all_pages_index[$ref], $reorder)]
 * );
 * ```
 * 
 * @phpstan-template T of array
 * @param T $item Generic item record with at least the 'order_by' key
 * @param string $direction The direction of the re-order - up/down
 * @param int $jump_over Specify how many elements to jump over
 * @return T
 */
function compute_item_order(array $item, string $direction, int $jump_over = 1): array
{
    /* Use cases:
    Move up from 50 (over 40): 50+(−10×1)−5 = 35
    Move down from 40 (over 50): 40+(10×1)+5 = 55
    Move up from 40 (over 2 items): 40+(−10×2)−5 = 15
    */
    $sign = cast_reorder_direction_to_int($direction);
    $item['order_by'] += ($sign * 10 * $jump_over) + ($sign * 5);
    return $item;
}

/** Input validation for the re-order capability */
function reorder_input_validator($value): bool
{
    return is_string($value) && in_array($value, ['up', 'down']);
}

/** Rich text editor input parser (removes invalid HTML tags/attributes)  */
function richtext_input_parser(string $value): string
{
    $cache_permitted_html_tags = $GLOBALS['permitted_html_tags'];
    // Minimum tags required by {@see strip_tags_and_attributes()}
    $GLOBALS['permitted_html_tags'] = [
        'html',
        'body',
    ];
    $parsed = strip_tags_and_attributes(
        $value,
        ['p', 'span', 'h2', 'h3', 'strong', 'em', 'ul', 'ol', 'li', 'a'],
        ['href', 'target', 'rel']
    );
    $GLOBALS['permitted_html_tags'] = $cache_permitted_html_tags;
    return $parsed;
}

/** Input validation for HEX colour syntax, including short form notations (e.g. 03A === 0033AA) */
function colour_hex_input_validator($value): bool
{
    return preg_match(
        '/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i',
        // Expand short form, if applicable
        preg_replace_callback(
            '/^#?([a-f\d])([a-f\d])([a-f\d])$/i',
            fn(array $matches) => sprintf('%1$s%1$s%2$s%2$s%3$s%3$s', $matches[1], $matches[2], $matches[3]),
            $value,
        )
    ) === 1;
}

/** Input validation for RGB colour syntax (e.g. 0,0,0) */
function colour_rgb_input_validator($value): bool
{
    return is_string($value) && preg_match('/^\d{1,3}, ?\d{1,3}, ?\d{1,3}$/', $value) === 1 
        ? count(array_filter(array_map('trim', explode(',', $value)), fn($val) => $val >= 0 && $val <= 255)) === 3
        : false;
}

/** Input validation for CMYK colour syntax (e.g. 0,0,0,100) */
function colour_cmyk_input_validator($value): bool
{
    return is_string($value) && preg_match('/^\d{1,3}, ?\d{1,3}, ?\d{1,3}, ?\d{1,3}$/', $value) === 1 
        ? count(array_filter(array_map('trim', explode(',', $value)), fn($val) => $val >= 0 && $val <= 100)) === 4
        : false;
}

/**
 * Create a new page content item (use case).
 * @param int $page Page ID
 * @param array{type: BRAND_GUIDELINES_CONTENT_TYPES, fields: array, position_after: int} $item An item data structure
 */
function create_page_content(int $page, array $item): bool
{
    if (!acl_can_edit_brand_guidelines()) {
        return false;
    }
    $created = in_array($item['type'], BRAND_GUIDELINES_CONTENT_TYPES) && create_content_item($page, $item);
    if ($created && $item['position_after'] > 0) {
        // adjust order_by following new addition otherwise moving content might end up in the wrong place
        reorder_items('brand_guidelines_content', get_page_contents($page), null);
    }
    return $created;
}

/**
 * Save a page content item change (use case).
 * @param int $ref Page content item ID
 * @param array{type: BRAND_GUIDELINES_CONTENT_TYPES, fields: array} An item data structure
 */
function save_page_content(int $ref, array $item)
{
    if (!acl_can_edit_brand_guidelines()) {
        return false;
    }
    return in_array($item['type'], BRAND_GUIDELINES_CONTENT_TYPES) && save_content_item($ref, $item);
}

/**
 * Re-order page content. Use cases:
 * - move up/down items (no group logic);
 * - move up/down items over an entire group;
 * - move up/down an entire group of items;
 * - move left/right within the group boundaries;
 * - move up/down outside group boundaries to take an item out of a group;
 */
function reorder_page_content($reorder, array $init_page_content, array $refs): bool
{
    $direction_int = cast_reorder_direction_to_int($reorder);
    $act_on_group = count($refs) > 1;
    if ($act_on_group && $direction_int === 1) {
        $refs = array_reverse($refs);
    }
    $group_items_jump = 0;
    $closest_item = function(int $needle, array $items, int $direction) {
        $keys = array_keys($items);
        return $items[$keys[array_search($needle, $keys) + $direction] ?? -1] ?? [];
    };

    foreach ($refs as $ref) {
        if (!isset($init_page_content[$ref])) {
            continue;
        }

        $page_contents_db ??= $init_page_content;
        $page_contents_grouped = group_content_items($page_contents_db);
        $applicable_group = array_filter($page_contents_grouped, is_group_member($ref, $page_contents_db));
        $applicable_group_members = (reset($applicable_group) ?: [])['members'] ?? [];
        $list_of_applicable_members = array_column($applicable_group_members, 'ref');
        sort($list_of_applicable_members, SORT_NUMERIC);
        $ref_idx = array_search($page_contents_db[$ref], $page_contents_grouped);

        if (
            $applicable_group === []
            && $ref_idx !== false
            && ($ci = $closest_item($ref_idx, $page_contents_grouped, $direction_int))
            && isset($ci['members'])
        ) {
            // Jump over an entire group
            $items_to_sort = array_replace(
                $page_contents_db,
                [$ref => compute_item_order($page_contents_db[$ref], $reorder, count($ci['members']))]
            );
        } else if (
            $act_on_group
            && $list_of_applicable_members !== []
            && $refs === $list_of_applicable_members
        ) {
            // Move up/down an entire group (one group item at a time)
            if ($group_items_jump <= 0) {
                $group_items_jump = count($list_of_applicable_members);
            }

            $items_to_sort = array_replace(
                $page_contents_db,
                [$ref => compute_item_order($page_contents_db[$ref], $reorder, $group_items_jump--)]
            );
        } else {
            // Move up/down (or the equivalent left/right, when item is within a group)
            $items_to_sort = array_replace(
                $page_contents_db,
                [$ref => compute_item_order($page_contents_db[$ref], $reorder)]
            );
        }

        $page_contents_db = reorder_items_in_memory($items_to_sort);
        $done = true;
    }

    if ($done) {
        reorder_items('brand_guidelines_content', $page_contents_db, null);
        return true;
    }
    return false;
}

/**
 * Data transfer utility to help translate custom fields' data structures to a minimum required data structure that we
 * can store in the database.
 * @param array $input Submitted custom fields' data structure {@see process_custom_fields_submission()}
 */
function convert_to_db_content(array $input): array
{
    /**
     * @var list<string> List of content fields' IDs that would be meaningless to store (e.g. colour_preview is a field
     * used only to visually display the color).
     */
    $unnecessary_fields = ['colour_preview'];

    // Storing each field ID and its value should be enough relevant information to correctly render when reconstructed.
    // Dropdown fields use the the field options' ID instead (static information as opposed to the value which is i18n)
    $db_col_value = [];
    foreach ($input as $field) {
        if (in_array($field['id'], $unnecessary_fields)) {
            continue;
        }

        $db_col_value[$field['id']] = $field['type'] === FIELD_TYPE_DROP_DOWN_LIST
            ? array_key_first($field['selected_options'])
            : $field['value'];
    }
    return $db_col_value;
}

/**
 * Data transfer utility to help reconstruct custom fields' data structure 
 * @param string $json Database record value (JSON type)
 * @param array $def Custom fields' data structure as input to {@see process_custom_fields_submission()}
 * @return array Same custom fields' data structure with, where applicable, an extra "value" key representing the current
 * field value
 */
function convert_from_db_content(string $json, array $def): array
{
    $content = json_decode($json, true);

    // Reconstruct custom fields' (with HTML properties - also used by {@see process_custom_fields_submission()})
    $fields = gen_custom_fields_html_props(
        get_valid_custom_fields($def),
        ['html_properties_prefix' => '']
    );

    $result = [];
    foreach ($fields as $field) {
        if (!isset($content[$field['id']])) {
            continue;
        }

        $value = $field['type'] === FIELD_TYPE_DROP_DOWN_LIST && isset($field['options'][$content[$field['id']]])
            ? $field['options'][$content[$field['id']]]
            : $content[$field['id']];

        // Add custom fields' value. Note: {@see process_custom_fields_submission()} will always override this "value" key
        $result[] = array_merge($field, ['value' => $value]);
    }
    return $result;
}

/**
 * Helper function to group similar sequential page (colour & resource) content items for rendering purposes. A group is
 * made of at least one item (to allow users to add to it).
 * @param array $items Page content item records
 * @return array Returns item as is and a group item (with members) otherwise
 */
function group_content_items(array $items): array
{
    $result = [];
    $allow_groups_for = [
        BRAND_GUIDELINES_CONTENT_TYPES['colour'],
        BRAND_GUIDELINES_CONTENT_TYPES['resource']
    ];
    $tmp = [
        'type' => BRAND_GUIDELINES_CONTENT_TYPES['group'],
        'members' => [],
    ];
    foreach ($items as $item) {
        $group_type = in_array($item['type'], $allow_groups_for);
        $next_item = next($items);
        if (
            $next_item !== false
            && $item['type'] === $next_item['type']
            && $group_type
            // todo: except full-width resources 
        ) {
            $tmp['members'][] = $item;
        } elseif ($tmp['members'] !== [] || ($tmp['members'] === [] && $group_type)) {
            // todo: half-width resources should only be in groups of max 2 elements
            $tmp['members'][] = $item;
            $result[] = $tmp;
            $tmp['members'] = [];
        } else {
            $result[] = $item;
        }
    }
    return $result;
}

/**
 * Helper filter function to determine if a page content item is part of group
 * @param int $ref Page content item ID
 * @param array $table Lookup table (e.g.: array_column(get_page_contents($page), null, 'ref'))
 */
function is_group_member(int $ref, array $table): callable
{
    return fn($item) => isset($item['members']) && in_array($table[$ref], $item['members']);
}

/**
 * Re-order table records in memory in ascending order based on their order_by
 * @template T of array<postive-int, array{ref: postive-int, order_by: int}>
 * @param T $table
 * @return T
 */
function reorder_items_in_memory(array $table): array
{
    $ref_ob_map = array_column($table, 'order_by', 'ref');
    asort($ref_ob_map, SORT_NUMERIC);
    $order_by = 0;
    foreach (array_keys($ref_ob_map) as $item_ref) {
        $order_by += 10;
        $table[$item_ref]['order_by'] = $order_by;
    }
    return $table;
}
