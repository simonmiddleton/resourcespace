<div id="map_canvas" style="width: 100%; height: <?php echo isset($mapheight)?$mapheight:"500" ?>px; display:block; float:none;overflow: hidden;" class="Picture" ></div>
<script>
<?php
if ($geo_override_options == "") 
    {
    ?>
    OpenLayers.Lang.setCode("<?php echo $language?>");
    OpenLayers.ImgPath="<?php echo $baseurl ?>/lib/OpenLayers/img/";

    map = new OpenLayers.Map("map_canvas");

    var osm = new OpenLayers.Layer.OSM("<?php echo $lang["openstreetmap"]?>"
    <?php
    if(count($geo_tile_servers) > 0 && !$geo_tile_caching)
        {
        $tileurl = 'https://'.$geo_tile_servers[array_rand($geo_tile_servers)];
        $tileurl .= "/".$z."/".$x."/".$y.".png";
        echo ",\"" . $tileurl . "\",{transitionEffect: 'resize'}";
        }
    else
        {
        echo ",\"" . $baseurl ."/pages/ajax/tiles.php?z=\${z}&x=\${x}&y=\${y}&\",{transitionEffect: 'resize'}";
        }?>
    );

    map.addLayers([<?php echo $geo_layers ?>]);
    map.addControl(new OpenLayers.Control.LayerSwitcher());
    <?php 
    if(count($geo_tile_servers) == 0)
        {
        // Block zooming beyond the tile resolutions included by default in gfx/geotiles
        echo "
        map.isValidZoomLevel = function(zoomLevel)
            {
            return (zoomLevel != null && zoomLevel <= 3);
            }";
        }
    }
else 
    {
    echo $geo_override_options;
    }
?>
    
</script>
