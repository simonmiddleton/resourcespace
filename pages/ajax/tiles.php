<?php
include_once "../../include/db.php";

$provider   = getval("provider","");
$variant    = getval("variant","");

$provider   = safe_file_name($provider);
$variant    = safe_file_name($variant);

# Originally adapted from
# http://wiki.openstreetmap.org/wiki/ProxySimplePHP
# The main benefit is for SSL sites which don't want to be making HTTP calls which result in content warnings

if(isset($geo_tile_cache_directory))
    {
    $tilecache = $geo_tile_cache_directory;    
    }
else
    {
    $tilecache = get_temp_dir()."/tiles";
    if($provider != "")
        {
        $tilecache .= "/" . $provider;
        }
    if($variant != "")
        {
        $tilecache .= "/" . $variant;
        }
    if(!is_dir($tilecache))
        {
        if(file_exists($tilecache))
            {
            unlink($tilecache);
            }
        mkdir($tilecache,0777,true);
        }
    }

$ttl = 86400; //cache timeout in seconds

$x = intval(getval('x',0,true));
$y = intval(getval('y',0,true));
$z = intval(getval('z',0,true));

$file = $tilecache."/${z}_${x}_$y.png";
$gettile = true;
while((     !is_file($file)
            ||
            (filemtime($file)<time()-$geo_tile_cache_lifetime)
            ||
            !in_array(get_mime_type($file),array("image/png","image/jpeg"))
            )
        && 
            $gettile
        )
    {
    if($leaflet_maps_enable && count($geo_leaflet_sources) > 0)
        {
        $geo_tile_urls = array();
        foreach($geo_leaflet_sources as $geo_leaflet_source)
            {
            // If no provider is specified, default to the first one defined
            if($provider == "")
                {
                $provider = $geo_leaflet_source["code"];
                }
            $geo_tile_urls[$geo_leaflet_source["code"]] = array();
            $geo_tile_urls[$geo_leaflet_source["code"]]["url"] = $geo_leaflet_source["url"];
            $geo_tile_urls[$geo_leaflet_source["code"]]["subdomains"] = isset($geo_leaflet_source["subdomains"]) ? $geo_leaflet_source["subdomains"] : "dd";
            $geo_file_extension = isset($geo_leaflet_source["extension"]) ? $geo_leaflet_source["extension"] : "";
            $geo_tile_urls[$geo_leaflet_source["code"]] ["extension"] = $geo_file_extension;
            foreach($geo_leaflet_source["variants"] as $mapvariant=>$varopts)
                {
                if(isset($varopts["url"]))
                    {
                    $varcode = $geo_leaflet_source["code"] . "_" . mb_strtolower($mapvariant);
                    $geo_tile_urls[$varcode]["url"] = $varopts["url"]; 
                    $geo_tile_urls[$varcode]["subdomains"] = isset($geo_leaflet_source["subdomains"]) ? $geo_leaflet_source["subdomains"] : "#";
                    $geo_tile_urls[$varcode]["extension"] = $geo_file_extension;
                    }
                }
            }
        if(($provider != "" && isset($geo_tile_urls[$provider])))
            {
            $url        = $geo_tile_urls[$provider]["url"];
            $subdomains = isset($geo_tile_urls[$provider]["subdomains"]) ? $geo_tile_urls[$provider]["subdomains"] : "#";
            $extension  = $geo_tile_urls[$provider]["extension"];
            if($variant != "" && isset($geo_tile_urls[$provider . "_" . mb_strtolower($variant)]))
                {
                $url        = $geo_tile_urls[$provider . "_" . mb_strtolower($variant)]["url"]; 
                $subdomains = $geo_tile_urls[$provider . "_" . mb_strtolower($variant)]["subdomains"];                
                }
            while(strlen($subdomains) > 0)
                {
                // Get a random subdomain
                $subidx = substr($subdomains,0,1);
                //$url = $subdomains[$subidx] . "." . $url;
                // Replace placeholders in URL 
                $find = array("{x}","{y}","{z}","{ext}");
                $replace = array($x,$y,$z,$extension);
                if($subidx != "#")
                    {
                    $find[]     = "{s}";
                    $replace[]  = $subidx;
                    }
                
                $url = str_replace($find,$replace,$url);
                //echo($url);
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, $geo_tile_user_agent);
                curl_setopt($ch, CURLOPT_REFERER, $baseurl);

                $cresponse = curl_exec($ch);
                $cerror = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headersize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($cresponse, 0, $headersize);
                $body = substr($cresponse, $headersize);
                curl_close($ch);

                //print_r($cresponse);
        
                if($cerror == 200)
                    {
                    debug("Successfully retrieved tile from " . $url);
                    file_put_contents($file,$body);$gettile = false; $gettile = false;
                    }
                else
                    {
                    debug("failed to retrieve tile from " . $url . ". Response: " . $cresponse);
                    }
                $gettile = false;
                // Remove this subdomain server from the array
                $subdomains = substr($subdomains,1);
                }
            }        
        }
   elseif($gettile && count($geo_tile_servers) > 0)
        {
        while(count($geo_tile_servers) > 0)
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

if(!is_file($file) || !in_array(get_mime_type($file),array("image/png","image/jpeg")))
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
