<?php

include_once dirname(__FILE__, 4) . '/include/boot.php';
include_once dirname(__FILE__, 2) . '/include/faces_functions.php';

command_line_only();

// Find all faces that have not been identified.
$faces = ps_query("SELECT ref,resource FROM resource_face WHERE (node is null or node=0) ORDER BY ref desc");

foreach ($faces as $face) {
    logScript("Processing face " . $face["ref"] . " in resource " . $face["resource"]);

    $function = "find_similar_faces";
    $faces_service_call = $faces_service_endpoint . "/" . $function;

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
        'ref' => (int)$face["ref"],
        'threshold' => $faces_tag_threshold,
        'k' => 200
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Connection: keep-alive',
        'Expect:'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || empty($response)) {
        echo "Error from faces_service (HTTP $http_code)\n";
        exit(1);
    }

    $results = json_decode($response, true);
    if (!is_array($results)) {
        logScript("Invalid response from faces_service.");
        exit(1);
    }

    if (count($results) == 0) {
        logScript("No matching faces found.");
        continue;
    }
    print_r($results);
    // Find all nodes set for matching faces.
    $nodes = array_column($results, 'node');

    // Filter out non-numeric or null values
    $filtered_nodes = array_filter($nodes, static function ($value) {
        return is_numeric($value);
    });

    // Check if the filtered list is empty
    if (empty($filtered_nodes)) {
        logScript("No valid node values in the matching faces.");
        continue;
    }

    // Count frequency of each node
    $counts = array_count_values($filtered_nodes);

    // Find the node with the highest frequency
    arsort($counts);
    $most_common_node = array_key_first($counts);
    $count = reset($counts);

    logScript("Most common node: $most_common_node (occurs $count times)");

    // Tag this face with the node.
    add_resource_nodes($face["resource"], [$most_common_node]); // Add to the resource metadata
    ps_query("update resource_face set node=? where ref=?", ["i",$most_common_node,"i",$face["ref"]]); // Attach the node to this face

    logScript("Tagged with node: " . $most_common_node);
}
