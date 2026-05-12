<?php

include_once __DIR__ . '/../include/typesense_search_functions.php';

/**
 * Intercept ResourceSpace searches and optionally handle them using Typesense.
 *
 * Returning false allows normal MySQL-based ResourceSpace search processing
 * to continue.
 *
 * @param string                 $search                  Original search string.
 * @param array                  $keywords                Parsed search keywords.
 * @param array                  $node_bucket             Included node search buckets.
 * @param array                  $node_bucket_not         Excluded node search buckets.
 * @param mixed                  $restypes                Resource type filter.
 * @param mixed                  $order_by                Requested sort field.
 * @param array                  $archive                 Archive state filter.
 * @param mixed                  $fetchrows               Result limit or chunk details.
 * @param string                 $sort                    Sort direction.
 * @param bool                   $access_override         Whether access checks are overridden.
 * @param bool                   $ignore_filters          Whether standard filters are ignored.
 * @param bool                   $return_disk_usage       Whether disk usage totals are requested.
 * @param string                 $recent_search_daylimit  Recent search day limit.
 * @param bool                   $return_refs_only        Whether only resource refs should be returned.
 * @param bool                   $editable_only           Whether only editable resources should be returned.
 * @param bool                   $returnsql               Whether SQL should be returned instead of results.
 * @param mixed                  $access                  Access filter override.
 * @param bool                   $smartsearch             Whether smart search mode is active.
 * @param PreparedStatementQuery $sql_filter              Existing ResourceSpace filter SQL.
 * @param PreparedStatementQuery $sql_join                Existing ResourceSpace JOIN SQL.
 * @param PreparedStatementQuery $select                  Existing ResourceSpace SELECT SQL.
 *
 * @return array|false ResourceSpace-compatible search results, or false to fall back to core search.
 */
function HookTypesense_searchAllExternal_search(
    string $search,
    array $keywords,
    array $node_bucket,
    array $node_bucket_not,
    $restypes,
    $order_by,
    array $archive,
    $fetchrows,
    string $sort,
    bool $access_override,
    bool $ignore_filters,
    bool $return_disk_usage,
    string $recent_search_daylimit,
    bool $return_refs_only,
    bool $editable_only,
    bool $returnsql,
    $access,
    bool $smartsearch,
    PreparedStatementQuery $sql_filter,
    PreparedStatementQuery $sql_join,
    PreparedStatementQuery $select
) {
    if (
        !typesense_search_supported(
            $search,
            $keywords,
            $node_bucket,
            $node_bucket_not,
            $restypes,
            $order_by,
            $archive,
            $fetchrows,
            $sort,
            $access_override,
            $ignore_filters,
            $return_disk_usage,
            $return_refs_only,
            $editable_only,
            $returnsql,
            $access,
            $smartsearch
        )
    ) {
        return false;
    }

    return typesense_search_do_search(
        $search,
        $keywords,
        $restypes,
        $archive,
        $fetchrows,
        $sort,
        $return_refs_only,
        $sql_filter,
        $sql_join,
        $select
    );
}

/**
 * Store resources using a node before the node is deleted.
 *
 * @param int $node Node ID.
 *
 * @return void
 */
function HookTypesense_searchAllBeforenodedelete(int $node): void
{
    $GLOBALS['typesense_search_affected_node_resources'] = ps_array(
        'SELECT DISTINCT resource value FROM resource_node WHERE node = ?',
        array('i', $node)
    );
}


/**
 * Reindex resources that used a node after the node has been deleted.
 *
 * @param int $node Node ID.
 *
 * @return void
 */
function HookTypesense_searchAllAfternodedelete(int $node): void
{
    foreach ($GLOBALS['typesense_search_affected_node_resources'] ?? array() as $resource) {
        typesense_search_index_resource((int)$resource);
    }

    unset($GLOBALS['typesense_search_affected_node_resources']);
}


/**
 * Reindex resource after metadata has been saved.
 *
 * @return void
 */
function HookTypesense_searchAllAftersaveresourcedata()
{
    global $ref;

    if (!isset($ref) || !is_numeric($ref) || (int)$ref <= 0) {
        return false;
    }

    debug('typesense_search: reindexing after save_resource_data(): ' . $ref);

    typesense_search_index_resource((int)$ref);

    return false;
}

function HookTypesense_searchAllAfter_save_related_keywords(string $keyword, string $related)
{
    debug('typesense_search: syncing Typesense synonyms after related keyword update');

    typesense_search_sync_related_keywords();

    return false;
}