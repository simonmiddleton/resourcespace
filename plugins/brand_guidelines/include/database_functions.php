<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

/**
 * Get all Brand Guidelines pages
 */
function get_all_pages(): array
{
    return ps_query(
        "SELECT {$GLOBALS['rs_const'](BRAND_GUIDELINES_DB_COLS_PAGES)} FROM brand_guidelines_pages ORDER BY parent ASC, order_by ASC",
        [],
        'brand_guidelines_pages'
    );
}

function get_page_contents(int $id): array
{
    return ps_query(
        "SELECT {$GLOBALS['rs_const'](BRAND_GUIDELINES_DB_COLS_CONTENT)} FROM brand_guidelines_content WHERE `page` = ? ORDER BY order_by ASC",
        ['i', $id]
    );
}

/**
 * Create new Brand Guidelines page/section.
 * @param string $name Page/Section name
 * @param int $parent The sections' ID a page belongs to. Use zero for sections (i.e. root pages)
 */
function create_page(string $name, int $parent): int
{
    db_begin_transaction('brand_guidelines_create_page');
    ps_query(
        'INSERT INTO `brand_guidelines_pages` (`name`, `parent`, `order_by`)
        SELECT ?, ?, coalesce(max(order_by), 0) + 10 FROM brand_guidelines_pages WHERE `parent` = ?',
        [
            's', sql_truncate_text_val($name, 255),
            'i', $parent,
            'i', $parent,
        ]
    );
    $ref = sql_insert_id();
    log_activity(null, LOG_CODE_CREATED, $name, 'brand_guidelines_pages', 'name', $ref, null, '');
    db_end_transaction('brand_guidelines_create_page');
    clear_query_cache('brand_guidelines_pages');
    return $ref;
}

/**
 * Edit Brand Guidelines page/section.
 * @param int $ref Page ID
 * @param string $name Page name
 * @param int $parent The sections' ID a page belongs to. Use zero for sections (i.e. root pages)
 */
function save_page(int $ref, string $name, int $parent)
{
    db_begin_transaction('brand_guidelines_save_page');
    log_activity(null, LOG_CODE_EDITED, $name, 'brand_guidelines_pages', 'name', $ref, null, null, null, true);
    log_activity(null, LOG_CODE_EDITED, $parent, 'brand_guidelines_pages', 'parent', $ref, null, null, null, true);
    ps_query(
        'UPDATE `brand_guidelines_pages` SET `name` = ?, `parent` = ? WHERE `ref` = ?',
        [
            's', sql_truncate_text_val($name, 255),
            'i', $parent,
            'i', $ref,
        ]
    );
    db_end_transaction('brand_guidelines_save_page');
    clear_query_cache('brand_guidelines_pages');
}

/**
 * Delete Brand Guidelines pages
 *
 * @param list<int> $refs List of page IDs
 * @return bool True if it executed the query, false otherwise
 */
function delete_pages(array $refs): bool
{
    $batch_activity_logger = fn($ref) => log_activity(null, LOG_CODE_DELETED, null, 'brand_guidelines_pages', 'name', $ref);
    $refs_chunked = db_chunk_id_list($refs);

    db_begin_transaction('brand_guidelines_delete_pages');
    foreach ($refs_chunked as $refs_list) {
        $done = ps_query(
            'DELETE FROM brand_guidelines_pages WHERE ref IN (' . ps_param_insert(count($refs_list)) . ')',
            ps_param_fill($refs_list, 'i')
        );

        array_walk($refs_list, $batch_activity_logger);
    }
    db_end_transaction('brand_guidelines_delete_pages');
    clear_query_cache('brand_guidelines_pages');

    return isset($done);
}

/**
 * Re-order databse records (where order_by exists)
 * @param string $table Database table (where the operation takes place)
 * @param array{ref: int|numeric-string, order_by: int|numeric-string}
 * @param null|callable $filter Optional filter, if the $list has unnecessary elements
 */
function reorder_items(string $table, array $list, ?callable $filter)
{
    $ref_ob_map = array_map(
        'intval',
        array_column(array_filter($list, $filter), 'order_by', 'ref')
    );
    asort($ref_ob_map, SORT_NUMERIC);
    sql_reorder_records($table, array_keys($ref_ob_map));
    clear_query_cache($table);
}

function create_content_item_text(int $page, string $text): int
{
    $content = json_encode(['text-content' => $text]);
    if ($content === false) {
        debug(json_last_error_msg());
        return 0;
    }

    db_begin_transaction('brand_guidelines_create_content_item_text');
    ps_query(
        'INSERT INTO `brand_guidelines_content` (`page`, `type`, `content`, `order_by`)
        SELECT ?, ?, ?, coalesce(max(order_by), 0) + 10 FROM brand_guidelines_content WHERE `page` = ?',
        [
            'i', $page,
            'i', BRAND_GUIDELINES_CONTENT_TYPES['text'],
            's', $content,
            'i', $page,
        ]
    );
    $ref = sql_insert_id();
    log_activity(null, LOG_CODE_CREATED, $text, 'brand_guidelines_content', 'content', $ref, null, '');
    db_end_transaction('brand_guidelines_create_content_item_text');
    clear_query_cache('brand_guidelines_content');
    return $ref;
}

