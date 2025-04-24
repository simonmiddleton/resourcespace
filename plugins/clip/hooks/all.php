<?php

/*
function HookClipAllAftersearchbox()
    {
    global $lang,$search;
    ?>
    <div class="tick"><input type="checkbox" id="naturalsearch" name="naturalsearch" <?php if (substr(getval("search",""),0,11)=="!clipsearch") { ?>checked<?php } ?>>&nbsp;<label for="naturalsearch"><?php echo escape($lang["clip-natural-language-search"]) ?></label></div>

    <?php
    }
*/

function HookClipAllAddspecialsearch($search, $select, $sql_join, $sql_filter)
{
    global $clip_search_cutoff, $clip_similar_cutoff, $clip_duplicate_cutoff, $clip_results_limit_search, $clip_results_limit_similar, $clip_service_url, $clip_query_time;
    if (substr($search, 0, 11) == '!clipsearch') {
        $function = "search";
        $search = substr($search, 12);
        $search = "A photo of a " . $search;
        $min_score = $clip_search_cutoff;
    } elseif (substr($search, 0, 12) == '!clipsimilar') {
        $function = "search";
        $resource = substr($search, 12);
        if (!is_numeric($resource)) {
            return false;
        }
        $min_score = $clip_similar_cutoff;
    } elseif (substr($search, 0, 13) == '!clipspecific') {
        $function = "search";
        $ref = substr($search, 13);
        if (!is_numeric($ref)) {
            return false;
        }
        $min_score = $clip_similar_cutoff;
    } elseif (substr($search, 0, 15) == '!clipduplicates') {
        $function = "duplicates";
        $min_score = $clip_duplicate_cutoff;
    } elseif (substr($search, 0, 14) == '!clipduplicate') {
        $function = "search";
        $resource = substr($search, 14);
        if (!is_numeric($resource)) {
            return false;
        }
        $min_score = $clip_duplicate_cutoff;

        if (checkperm("a")) {
            // Admin only - show resources in all states
            $sql_filter->parameters = [];
            $sql_filter->sql = "true";
        }
    } else {
        return null;
    }

    $clip_service_call = $clip_service_url . "/" . $function;
    global $mysql_db;

    // Send search to Python service
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $clip_service_call);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Connection: keep-alive',
        'Expect:' // Prevents "100-continue" delay
    ]);


    if ($function == "search" && !isset($resource) && !isset($ref)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'text' => $search,
            'top_k' => $clip_results_limit_search
        ]);
    }
    if ($function == "search" && isset($resource)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'resource' => $resource,
            'top_k' => $clip_results_limit_similar
        ]);
    }
    if ($function == "search" && isset($ref)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'ref' => $ref,
            'top_k' => $clip_results_limit_similar
        ]);
    }
    if ($function == "duplicates") {
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'threshold' => $clip_duplicate_cutoff
        ]);
    }

    $start_time = microtime(true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $end_time = microtime(true);
    $clip_query_time = round(($end_time - $start_time) * 1000);
    curl_close($ch);

    if ($http_code !== 200 || empty($response)) {
        echo "Error from CLIP service (HTTP $http_code)\n";
        exit(1);
    }

    $results = json_decode($response, true);
    if (!is_array($results)) {
        echo "Invalid response from CLIP service.\n";
        exit(1);
    }

    // Filter out results with score below the threshold
    $results = array_filter($results, static function ($result) use ($min_score) {
        return isset($result['score']) && $result['score'] >= $min_score;
    });

    // Fetch titles from the resource table
    $ids = array_column($results, 'resource');

    // No results - we must still run a query but one that returns no results.
    if (count($ids) == 0) {
        $ids = [-1];
    }

    $in_sql = ps_param_insert(count($ids));
    $params = ps_param_fill($ids, "i");
    $clipsql = new PreparedStatementQuery();
    $clipsql->sql = "SELECT DISTINCT r.hit_count score, $select->sql FROM resource r " . $sql_join->sql . " WHERE r.ref > 0 AND r.ref in ($in_sql) AND " . $sql_filter->sql . " ORDER BY FIELD(r.ref, $in_sql)";
    $clipsql->parameters = array_merge($select->parameters, $sql_join->parameters, $params, $sql_filter->parameters, $params);
    return $clipsql;
}

function HookClipAllSearch_pipeline_setup($search, $select, $sql_join, $sql_filter)
{
    if (substr($search, 0, 11) != '!clipsearch' && (substr($search, 0, 12) != '!clipsimilar')) {
        return false;
    }
    global $keysearch;
    $keysearch = false; // Disable keyword searching if using natural language search
}


function HookClipAllSearchbarafterbuttons()
{
    global $lang,$baseurl;
    ?>
    <p><i aria-hidden="true" class="fa fa-fw fa-brain"></i>&nbsp;<a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl ?>/plugins/clip/pages/search.php"><?php echo escape($lang["clip-ai-smart-search"]) ?></a></p>
    <?php
}