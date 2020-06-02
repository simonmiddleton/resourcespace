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

    <?php if ($use_google_maps) { ?>
    var gphy = new OpenLayers.Layer.Google(
    "<?php echo $lang["google_terrain"]?>",
    {type: google.maps.MapTypeId.TERRAIN}
    // used to be {type: G_PHYSICAL_MAP}
    );
    var gmap = new OpenLayers.Layer.Google(
    "<?php echo $lang["google_default_map"]?>", // the default
    {numZoomLevels: 20}
    // default type, no change needed here
    );
    var gsat = new OpenLayers.Layer.Google(
    "<?php echo $lang["google_satellite"]?>",
    {type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22}
    // used to be {type: G_SATELLITE_MAP, numZoomLevels: 22}
    );
    <?php } ?>

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
