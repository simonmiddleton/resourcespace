<?php
include_once "../../include/db.php";
include_once "../../include/general.php";

# from
# http://wiki.openstreetmap.org/wiki/ProxySimplePHP
# The main benefit is for SSL sites which don't want to be making HTTP calls which result in content warnings

    if(isset($geo_tile_cache_directory))
        {
        $tilecache = $geo_tile_cache_directory;    
        }
    else
        {
        $tilecache = get_temp_dir()."/tiles";
        if(!is_dir($tilecache))
            {
            mkdir($tilecache,0777);
            }
        }

    $ttl = 86400; //cache timeout in seconds

    $x = intval($_GET['x']);
    $y = intval($_GET['y']);
    $z = intval($_GET['z']);
    $r = strip_tags($_GET['r']);

    $file = $tilecache."/${z}_${x}_$y.png";

    if (!is_file($file) || filemtime($file)<time()-($geo_tile_cache_lifetime) || mime_content_type($file) != "image/png")
        {
        switch ($r)
            {
            case 'mapnik':
                $url = 'https://'.$geo_tile_servers[array_rand($geo_tile_servers)];
                $url .= "/".$z."/".$x."/".$y.".png";
                break;

            }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $cresponse = curl_exec($ch);
        $cerror = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headersize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($cresponse, 0, $headersize);
        $body = substr($cresponse, $headersize);
        curl_close($ch);

        if($cerror == 200)
            {
            file_put_contents($file,$body);
            }
        elseif(!is_file($file) || mime_content_type($file) != "image/png")
            {
            // No valid tile to send
            http_response_code(404);
            exit($lang["error-geotile-server-error"]);
            }
        }

    $exp_gmt = gmdate("D, d M Y H:i:s", time() + $ttl * 60) ." GMT";
    $mod_gmt = gmdate("D, d M Y H:i:s", filemtime($file)) ." GMT";
    header("Expires: " . $exp_gmt);
    header("Last-Modified: " . $mod_gmt);
    header("Cache-Control: public, max-age=" . $ttl * 60);
    header ('Content-Type: image/png');
    readfile($file);

	exit();
