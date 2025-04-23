<?php
function HookFacesAllAddspecialsearch($search,$select,$sql_join,$sql_filter)
    {
    global $faces_service_endpoint, $faces_match_threshold;

    if(substr($search, 0, 5) == '!face') 
        {
        $function="find_similar_faces";
        $face=substr($search,5);
        }
    else
        {
        return false;
        }

    $faces_service_call = $faces_service_endpoint. "/" . $function;
    global $mysql_db;

    // Send search to Python service
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $faces_service_call);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Connection: keep-alive',
        'Expect:' // Prevents "100-continue" delay
    ]);


    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'db' => $mysql_db,
        'ref' => (int)$face,
        'threshold' => $faces_match_threshold,
        'k' => 200
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Connection: keep-alive',
        'Expect:'
    ]);
    

    $start_time = microtime(true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $end_time = microtime(true);
    $query_time = round(($end_time - $start_time) * 1000);
    curl_close($ch);

    if ($http_code !== 200 || empty($response))
    {
        echo "Error from faces_service (HTTP $http_code)\n";
        exit(1);
    }

    $results = json_decode($response, true);
    if (!is_array($results))
    {
        echo "Invalid response from faces_service.\n";
        exit(1);
    }
    
    //print_r($results);
    $ids = array_column($results, 'resource');
    
    // No results - we must still run a query but one that returns no results.
    if (count($ids)==0) {$ids=[-1];}

    $in_sql=ps_param_insert(count($ids));
    $params=ps_param_fill($ids,"i");
    $sql = new PreparedStatementQuery();
    $sql->sql = "SELECT DISTINCT r.hit_count score, $select->sql FROM resource r " . $sql_join->sql . " WHERE r.ref > 0 AND r.ref in ($in_sql) AND " . $sql_filter->sql . " ORDER BY FIELD(r.ref, $in_sql)";
    $sql->parameters = array_merge($select->parameters, $sql_join->parameters, $params, $sql_filter->parameters, $params);
    return $sql;
    }

/*
Update the named person for a given face when using the dropdown.
*/
function api_faces_tag($face,$node)
    {
    debug("API: faces_tag(" . $face . ", " . $node);
    ps_query("update resource_face set node=? where ref=?",["i",$node,"i",$face]);
    return true;
    }