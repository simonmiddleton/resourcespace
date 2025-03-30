<?php
include_once dirname(__FILE__, 4) . '/include/db.php';
command_line_only();

if ($argc < 2)
{
    echo "Usage: php similar_resources.php <resource_id>\n";
    exit(1);
}

$target_ref = (int) $argv[1];
$clip_service_url = 'http://localhost:8000/similar';
$results_limit = 8;

global $mysql_db;

// Send request to Python service
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $clip_service_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'db' => $mysql_db,
    'resource' => $target_ref,
    'top_k' => $results_limit
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response))
{
    echo "❌ Error from CLIP service (HTTP $http_code)\n";
    exit(1);
}

$results = json_decode($response, true);
if (!is_array($results) || empty($results))
{
    echo "No similar resources found.\n";
    exit;
}

// Fetch titles for the returned resource IDs
$ids = array_column($results, 'resource');
$in_sql = implode(',', array_fill(0, count($ids), '?'));
$params = [];
foreach ($ids as $id)
{
    $params[] = 'i';
    $params[] = $id;
}

$sql = "SELECT ref, field8 AS title FROM resource WHERE ref IN ($in_sql)";
$resources = ps_query($sql, $params);

// Build lookup
$title_lookup = [];
foreach ($resources as $res)
{
    $title_lookup[$res['ref']] = $res['title'];
}

// Print results
echo "🔍 Similar to resource ID: {$target_ref}\n\n";
foreach ($results as $result)
{
    $ref = $result['resource'];
    $score = number_format($result['score'], 4);
    $title = $title_lookup[$ref] ?? '[No title]';

    echo "Resource ID: $ref\n";
    echo "Title     : $title\n";
    echo "Score     : $score\n";
    echo "-------------------------\n";
}
