<?php
// Update cached geographic points 
if($geo_search_heatmap)
    {
    $defaultarchive = get_default_search_states();
    $allgeopoints = sql_query("SELECT geo_lat, geo_long FROM resource WHERE ref>0 AND archive IN ('" . implode("','",$defaultarchive) . "') AND geo_lat IS NOT NULL");
    $heatpoints = array();
    foreach($allgeopoints as $geopoint)
        {
        $heatpoints[] = array($geopoint["geo_lat"],$geopoint["geo_long"]);
        }

    $heatmap_cache = get_temp_dir() . "/heatmap_" . md5("heatmap" . $scramble_key);
    $heatmapjson = "var heatpoints = " . json_encode($heatpoints) . ";";
    file_put_contents($heatmap_cache,$heatmapjson);
    }

