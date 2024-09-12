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
    ps_query(
        'INSERT INTO `brand_guidelines_pages` (`name`, `parent`, `order_by`)
        SELECT ?, ?, MAX(order_by) + 10 FROM brand_guidelines_pages WHERE `parent` = ?',
        [
            's', sql_truncate_text_val($name, 255),
            'i', $parent,
            'i', $parent,
        ]
    );
    $ref = sql_insert_id();
    log_activity(null, LOG_CODE_CREATED, $name, 'brand_guidelines_pages', 'name', $ref, null, '');
    clear_query_cache('brand_guidelines_pages');
    return $ref;
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
