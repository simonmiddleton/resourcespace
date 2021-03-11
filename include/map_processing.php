<?php
// Leaflet.js Basemap Processing
if($geo_leaflet_maps_sources)
    {
    echo leaflet_osm_basemaps();
    echo leaflet_usgs_basemaps();
    echo leaflet_esri_basemaps();
    echo leaflet_stamen_basemaps();
    echo leaflet_hydda_basemaps();
    echo leaflet_nasa_basemaps();
    echo leaflet_thunderforest_basemaps();
    echo leaflet_mapbox_basemaps(); 
    ?>
    <!-- Define Leaflet default basemap attribution-->
    <?php switch ($map_default)
        {
        case ('OpenStreetMap.Mapnik' || 'OpenStreetMap.DE' || 'OpenStreetMap.BZH' || 'OpenStreetMap.HOT' || 'MtbMap' || 'HikeBike.HikeBike'):
            ?> var default_attribute = osm_attribute; <?php
            break;

        case ('OpenStreetMap.France'):
            ?> var default_attribute = osm_fr_attribute; <?php
            break;

        case ('OpenTopoMap'):
            ?> var default_attribute = osm_otm_attribute; <?php
            break;

        case ('OpenMapSurfer.Roads'):
            ?> var default_attribute = oms_attribute; <?php
            break;

        default:
            ?> var default_attribute = ''; <?php
        }       
    }
else
    {
    foreach($geo_leaflet_sources as $leaflet_source)
        {
            //var osm_mapnik = L.tileLayer.provider('OpenStreetMap.Mapnik', {
            //     useCache: ' echo $map_default_cache;',
            //     detectRetina: ' echo $map_retina;',
            //     maxZoom: 19,
            //     attribution: osm_attribute
            // });
            // var osm_de = L.tileLayer.provider('OpenStreetMap.DE', {
            //     useCache: 'echo $map_default_cac',
            //     detectRetina: 'echo $map_retina;',
            //     maxZoom: 18,
            //     attribution: osm_attribute
            // });
        if(isset($leaflet_source["default"]) && $leaflet_source["default"])
            {
            echo "var default_attribute = '" . $leaflet_source["code"] . "_attribute';\n";
            }

        foreach($leaflet_source["variants"] as $variant=>$varopts)
            {
            $varcode = $leaflet_source["code"] . "_" . mb_strtolower($variant);
            //$geolang = isset($lang['map_' . $leaflet_source["code"] . "_" . mb_strtolower($variant)]) ? $lang['map_' . $leaflet_source["code"] . "_" . mb_strtolower($variant)] : $leaflet_source["code"] . "_" . mb_strtolower($variant);
            //echo $geolang . " : " . $leaflet_source["code"] . "_" . mb_strtolower($variant) . ",\n";
            
            $varoptions = array();
            $varoptions["maxZoom"] = $leaflet_source["maxZoom"];
            $varoptions["attribution"] = $leaflet_source["attribution"];

            // Options can be set at root or overridden by a variant
            if(isset($varopts["options"]))
                {
                foreach($varopts["options"] as $option => $optval)
                    {
                    $varoptions[$option] = $optval;
                    }
                }
            $attribution = isset($varoptions["options"]["attribution"]) ? $varoptions["options"]["attribution"] : $varoptions["attribution"]; 
            echo "var " . $varcode . "_attribute = '" . htmlspecialchars($attribution) . "';\n";
            echo "var " . $varcode . " = L.tileLayer.provider('" . $leaflet_source["group"] . "."  . $variant . "', {\n";
            echo "    useCache: '" . ($map_default_cache ? "true" : "false") . "',\n";
            echo "    detectRetina: '" . ($map_retina ? "true" : "false") . "',\n";
            echo "    name : '" . $leaflet_source["group"] . "." . $variant . "',\n";
            foreach ($varoptions as  $varoption => $optval)
                {
                if($varoption == "attribution")
                    {
                    echo "    " . htmlspecialchars($varoption) . ": '" . htmlspecialchars($optval) . "',\n";
                    }
                else
                    {
                    echo "    " . htmlspecialchars($varoption) . ": " . htmlspecialchars($optval) . ",\n";
                    }
                }
            echo "});\n";
            }
        }
    }