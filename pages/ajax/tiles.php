<?php
include_once "../../include/db.php";


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
        if(file_exists($tilecache))
            {
            unlink($tilecache);
            }
        mkdir($tilecache,0777);
        }
    }

$ttl = 86400; //cache timeout in seconds

$x = intval($_GET['x']);
$y = intval($_GET['y']);
$z = intval($_GET['z']);

$file = $tilecache."/${z}_${x}_$y.png";
$gettile = true;
while((     !is_file($file)
            ||
            (filemtime($file)<time()-($geo_tile_cache_lifetime) && count($geo_tile_servers) > 0)
            ||
            mime_content_type($file) != "image/png"
            )
        && 
            $gettile
        )
    {
    if(count($geo_tile_servers) > 0)
        {
        // Try to get an updated tile from a tile server
        $rnd = rand(0,count($geo_tile_servers)-1);
        $url = 'https://'.$geo_tile_servers[$rnd];
        $url .= "/".$z."/".$x."/".$y.".png";
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

        // Remove this tile server from the array
        unset($geo_tile_servers[$rnd]);
        $geo_tile_servers = array_values($geo_tile_servers);
        continue;
        }
    elseif($z <= 3)
        {
        // Use included tiles
        $file = __DIR__ . "/../../gfx/geotiles/${z}_${x}_$y.png";
        $gettile = false;
        continue;
        }
    else
        {
        debug("Request for a map tile at resolution " . $z . " received but no tiles available at this resolution");
        $gettile = false;
        continue;
        }
    }

if(!is_file($file) || mime_content_type($file) != "image/png")
    {
    // No tiles available at requested resolution
    http_response_code(404);
    exit($lang["error-geotile-server-error"]);
    }   

$exp_gmt = gmdate("D, d M Y H:i:s", time() + $ttl * 60) ." GMT";
$mod_gmt = gmdate("D, d M Y H:i:s", filemtime($file)) ." GMT";
header("Expires: " . $exp_gmt);
header("Last-Modified: " . $mod_gmt);
header("Cache-Control: public, max-age=" . $ttl * 60);
header ('Content-Type: image/png');
readfile($file);

exit();
