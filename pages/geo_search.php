<?php
// Geographic Map Search for Resources Using Leaflet.js and Various Leaflet Plugins

include '../include/db.php';
include '../include/authenticate.php'; 
include '../include/header.php';

if ($geo_search_heatmap && $leaflet_maps_enable)
    { ?>
    <script src="<?php echo $baseurl?>/lib/heatmap.js/heatmap.js"></script>
    <script src="<?php echo $baseurl?>/lib/heatmap.js/leaflet-heatmap.js"></script>
    <?php
    } ?>

<div class="BasicsBox">
<h1><?php echo $lang["geographicsearch"] ?></h1>
<p><?php echo $lang["geographicsearch_help"]; render_help_link("user/geographic-search");?></p>

<?php

if($leaflet_maps_enable)
    {
    // Setup initial map variables.
    $zoomslider = 'false';
    $zoomcontrol = 'true';

    // Set Leaflet map search view height and layer control container height based on $mapheight.
    if (isset($mapsearch_height))
        {
        $map1_height = $mapsearch_height;
        $layer_controlheight = $mapsearch_height - 40;
        }
    else // Default values.
        {
        $map1_height = 500;
        $layer_controlheight = 460;
        }

    // Show zoom slider instead of default Leaflet zoom control?
    if ($map_zoomslider)
        {
        $zoomslider = 'true';
        $zoomcontrol = 'false';
        }

    $display = getvalescaped("display", $default_display);
    if ($default_display == 'map' || $display == 'map')
        {
        $geo_search_modal_results = false;
        }?>

    <!-- Drag mode selector -->
    <div id="GeoDragMode">
        <?php echo $lang['geodragmode'] ?>:&nbsp;
        <input type="radio" name="dragmode" id="dragmodepan" onClick="map1.editTools.stopDrawing();" /><label for="dragmodepan"> <?php echo $lang['geodragmodepan'] ?></label>
            &nbsp;
        <input type="radio" name="dragmode" id="dragmodearea" checked="checked" onClick="map1.editTools.startRectangle();" /><label for="dragmodearea"><?php echo $lang['geodragmodeareaselect'] ?></label>
    </div>

    <!--Setup Leaflet map container with sizing-->
    <div id="search_map" style="width: 99%; margin-top:0px; margin-bottom:0px; height: <?php echo $map1_height; ?>px; display:block; border:1px solid black; float:none; overflow: hidden;">
    </div>

    <script>
        var Leaflet = L.noConflict();
        <?php set_geo_map_centerview(); ?>
        // Setup and define the Leaflet map with the initial view using leaflet.js and L.Control.Zoomslider.js.

        if(typeof map1 !== 'undefined')
            {
            map1.remove();
            }
        var map1 = new Leaflet.map('search_map', {
            editable: true,
            preferCanvas: true,
            renderer: Leaflet.canvas(),
            zoomsliderControl: <?php echo $zoomslider; ?>,
            zoomControl: <?php echo $zoomcontrol; ?>
        }).setView(mapcenterview,mapdefaultzoom);

        // Load available Leaflet basemap groups, layers, and attribute definitions.
        <?php include '../include/map_processing.php'; ?>

        <!--Define default Leaflet basemap layer using leaflet.js, leaflet.providers.js, and L.TileLayer.PouchDBCached.js-->
        var defaultLayer = new Leaflet.tileLayer.provider('<?php echo $map_default;?>', {
            useCache: '<?php echo $map_default_cache;?>', <!--Use browser caching of tiles (recommended)?-->
            detectRetina: '<?php echo $map_retina;?>', <!--Use retina high resolution map tiles?-->
            attribution: default_attribute
        }).addTo(map1);

        // Load Leaflet basemap definitions.
        <?php include '../include/map_basemaps.php'; ?>

        <!--Set styled layer control options for basemaps and add to the Leaflet map using styledLayerControl.js-->
        var options = {
            container_maxHeight: '<?php echo $layer_controlheight; ?>px',
            group_maxHeight: '380px',
            exclusive: false
        };

        var control = Leaflet.Control.styledLayerControl(baseMaps,options);
        map1.addControl(control);

        <!--Add geocoder search bar using control.geocoder.min.js-->
        Leaflet.Control.geocoder().addTo(map1);

        <!--Show zoom history navigation bar and add to Leaflet map using Leaflet.NavBar.min.js-->
        <?php if ($map_zoomnavbar)
            { ?>
            Leaflet.control.navbar().addTo(map1); <?php
            } ?>

        <!--Add a scale bar to the Leaflet map using leaflet.min.js-->
        new Leaflet.control.scale().addTo(map1);

        <?php
        hook("map_additional");
        ?>
        <!--Add a KML overlay to the Leaflet map using leaflet-omnivore.min.js-->
        <?php if ($map_kml)
            { ?>
            omnivore.kml('<?php echo $baseurl_short . $map_kml_file?>').addTo(map1); <?php
            } 

        //Add heatmap to aid searching
        if($geo_search_heatmap)
            {
            $heatmapfile = get_temp_dir() . "/heatmap_" . md5("heatmap" . $scramble_key);
            if(file_exists($heatmapfile))
                {
                echo file_get_contents($heatmapfile);
                ?>
                var cfg = {
                    // radius should be small ONLY if scaleRadius is true (or small radius is intended)
                    // if scaleRadius is false it will be the constant radius used in pixels
                    "radius": 15,
                    "maxOpacity": .5,
                    // scales the radius based on map zoom
                    "scaleRadius": false,
                    // if set to false the heatmap uses the global maximum for colorization
                    // if activated: uses the data maximum within the current map boundaries
                    //   (there will always be a red spot with useLocalExtremas true)
                    "useLocalExtrema": true,
                    latField: 'lat',
                    lngField: 'lng',
                    valueField: 'count'
                };  
    
                var heatmapLayer = new HeatmapOverlay(cfg).addTo(map1);
                heatmapLayer.setData(heatpoints);
                <?php
                }
            }?>

    <!--Fix for Microsoft Edge and Internet Explorer browsers-->
        map1.invalidateSize(true);

        <!--Add an Area of Interest (AOI) selection box to the Leaflet map using leaflet-shades.js-->
        var shades = new Leaflet.LeafletShades().addTo(map1);

        <!--Get AOI coordinates-->
        shades.on('shades:bounds-changed', function(e) {
            <!--Get AOI box coordinates in World Geodetic System of 1984 (WGS84, EPSG:4326)-->
            var trLat = e['bounds']['_northEast']['lat'];
            var trLon = e['bounds']['_northEast']['lng'];
            var blLat = e['bounds']['_southWest']['lat'];
            var blLon = e['bounds']['_southWest']['lng'];

            <!--Create specially encoded geocoordinate search string to avoid keyword splitting-->
            var url = "<?php echo $baseurl_short?>pages/search.php?search=!geo" + (blLat + "b" + blLon + "t" + trLat + "b" + trLon).replace(/\-/gi,'m').replace(/\./gi,'p');
            CentralSpaceLoad(url, true);
        });

        jQuery(document).ready(function()
            {
            map1.editTools.startRectangle();
            });
    </script>
    </div>
    <?php
    }
else
    {
    // Legacy OpenLayers code
    ?>
    <!-- Drag mode selector -->
    <div id="GeoDragMode">
    <?php echo $lang["geodragmode"] ?>:&nbsp;
    <input type="radio" name="dragmode" id="dragmodearea" checked="checked" onClick="control.point.activate()" /><label for="dragmodearea"><?php echo $lang["geodragmodeareaselect"] ?></label>
    &nbsp;&nbsp;
    <input type="radio" name="dragmode" id="dragmodepan" onClick="control.point.deactivate();" /><label for="dragmodepan"><?php echo $lang["geodragmodepan"] ?></label>
    </div>
    
    <?php
     include "../include/geo_map.php"; ?>
    <script>
    
    var control = new OpenLayers.Control();
    OpenLayers.Util.extend(control, {
    draw: function () {
        this.point = new OpenLayers.Handler.Box( control,
            {"done": this.notice});
        this.point.activate();
    },
    
    notice: function (bounds) {
            var blpix = new OpenLayers.Pixel(bounds.left,bounds.bottom);
            var bl=map.getLonLatFromPixel(blpix).transform
                    (
               map.getProjectionObject(), // from Spherical Mercator Projection}
               new OpenLayers.Projection("EPSG:4326")
            )
    
            var trpix = new OpenLayers.Pixel(bounds.right,bounds.top);
            var tr=map.getLonLatFromPixel(trpix).transform
                    (
               map.getProjectionObject(), // from Spherical Mercator Projection}
               new OpenLayers.Projection("EPSG:4326")
            );
         
        // Store the map window position to make it easier when returning for another search
        SetCookie("geobound",map.getCenter().lon + "," + map.getCenter().lat + "," + map.getZoom());
       
        // Specially encoded search string to avoid keyword splitting
        var url="<?php echo $baseurl_short?>pages/search.php?search=!geo" + (bl.lat + "b" + bl.lon + "t" + tr.lat + "b" + tr.lon).replace(/\-/gi,'m').replace(/\./gi,'p');
    
    <?php
    // Show results in a modal
    if($geo_search_modal_results)
        {
        ?>
        ModalClose();
        ModalLoad(url);
    
        return;
        <?php
        }
        ?>
        window.location.href=url;
           
    }
        });map.addControl(control);
    jQuery('#UICenter').scroll(function() {
      map.events.clearMouseCache();
    });
    <?php if (isset($_COOKIE["geobound"]))
            {
            $bounds=$_COOKIE["geobound"];
            }
    else
            {
            $bounds=$geolocation_default_bounds;
            }
    $bounds=explode(",",$bounds);
    ?>
    map.setCenter(new OpenLayers.LonLat(<?php echo htmlspecialchars($bounds[0]) ?>,<?php echo htmlspecialchars($bounds[1]) ?>),<?php echo $bounds[2] ?>);
           
    </script>
    </div>
    <?php
    }

include '../include/footer.php';