<?php


function HookClipSearchSearch_pipeline_setup($search,$select,$sql_join,$sql_filter)
    {
    if(substr($search, 0, 11) != '!clipsearch' && (substr($search, 0, 12) != '!clipsimilar') ) 
        {
        return false;
        }
    
    global $keysearch;
    $keysearch=false; // Disable keyword searching if using natural language search
    }

function HookClipSearchAddspecialsearch($search,$select,$sql_join,$sql_filter)
    {
    if(substr($search, 0, 11) == '!clipsearch') 
        {
        $function="search";
        $search=substr($search,12);
        $search="A photo of a " . $search;
        $min_score = 0.25; // TODO - config
        }
    elseif (substr($search, 0, 12) == '!clipsimilar') 
        {
        $function="similar";
        $resource=substr($search,12);
        if (!is_numeric($resource)) {return false;}
        $min_score = 0.6; // TODO - config
        }
    else
        {
        return false;
        }

    $clip_service_url = 'http://localhost:8000/' . $function; // TODO config
    $results_limit = 120; // TODO config
    global $mysql_db;

    // Send search to Python service
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $clip_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    if ($function=="search")
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'text' => $search,
            'top_k' => $results_limit
        ]);
    }
    if ($function=="similar")
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'resource' => $resource,
            'top_k' => $results_limit
        ]);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || empty($response))
    {
        echo "Error from CLIP service (HTTP $http_code)\n";
        exit(1);
    }

    $results = json_decode($response, true);
    if (!is_array($results))
    {
        echo "Invalid response from CLIP service.\n";
        exit(1);
    }

    // Filter out results with score below the threshold
    $results = array_filter($results, function($result) use ($min_score) {
        return isset($result['score']) && $result['score'] >= $min_score;
    });
    if (count($results)==0) return true;

    // Fetch titles from the resource table
    $ids = array_column($results, 'resource');
    

    $params = [];
    foreach ($ids as $id)
    {
        $params[] = 'i';
        $params[] = $id;
    }

    $in_sql = implode(',', array_fill(0, count($ids), '?'));

    $clipsql = new PreparedStatementQuery();
    $clipsql->sql = "SELECT DISTINCT r.hit_count score, $select->sql FROM resource r " . $sql_join->sql . " WHERE r.ref > 0 AND r.ref in ($in_sql) AND " . $sql_filter->sql . " ORDER BY FIELD(r.ref, $in_sql)";
    $clipsql->parameters = array_merge($select->parameters, $sql_join->parameters, $params, $sql_filter->parameters, $params);
    return $clipsql;
    }



function HookClipSearchBeforesearchresults()
    {
    global $lang,$ref,$baseurl,$search;

    // Don't display for search terms containing larger numbers - this is for natural search only
    if (preg_match('/\d{2,}/', $search)) {return false;}

    // Reject if it contains any symbols *except* comma, full stop, hyphen, and space
    if (preg_match('/[^a-zA-Z0-9 ,.\-]/', $search)) {return false;}

    $search_url=generateURL("{$baseurl}/pages/search.php", array("search" => "!clipsearch {$search}"));
    ?>
    <p>
        <a href="<?php echo $search_url ?>" onClick="return CentralSpaceLoad(this,true);">
        <i class="fa fa-fw fa-search"></i>&nbsp;<?php echo $lang["clip-natural-language-search"]; ?>
        </a>
    </p>    
    <?php
    return false; # Allow further custom panels
    }
