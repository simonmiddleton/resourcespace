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
 * @return T
 */
function compute_item_order(array $item, string $direction): array
{
    $item['order_by'] += ($direction === 'up' ? -1 : 1) * 15;
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
 * @param int $ref Page ID
 * @param array{type: BRAND_GUIDELINES_CONTENT_TYPES, fields: array} An item data structure
 */
function create_page_content(int $ref, array $item): bool
{
    if (!acl_can_edit_brand_guidelines()) {
        return false;
    }

    // todo: consider returning error message if need be for any of the use cases

    if (
        $item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['text']
        && create_content_item_text($ref, $item['fields'][0]['value'])
    ) {
        return true;
    }

    return false;
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

    if (
        $item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['text']
        && save_content_item_text($ref, $item['fields'][0]['value'])
    ) {
        return true;
    }

    return false;
}
