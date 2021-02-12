<?php
// Geographic Map Search for Resources Using Leaflet.js and Various Leaflet Plugins

include '../include/db.php';
include '../include/authenticate.php'; 
include '../include/header.php';

// Setup initial map variables.
global $default_display, $geo_search_modal_results, $baseurl, $mapsearch_height, $map_default, $map_centerview, $map_zoomslider, $map_zoomnavbar, $map_kml, $map_kml_file, $map_default_cache, $map_layer_cache, $map_retina;
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
    }

set_geo_bounds();
?>
<div class="BasicsBox">
<h1><?php echo $lang['geographicsearch']; ?></h1>

<!--Map introtext-->
<div id="map_introtext" style="margin-top:0px; margin-bottom:0px; width: 99%;">
    <p> <?php echo $lang['search_map_introtext']; ?> </p>
</div>

<!-- Drag mode selector -->
<div id="GeoDragMode">
    <?php echo $lang['geodragmode'] ?>:&nbsp;
    <input type="radio" name="dragmode" id="dragmodepan" checked="checked" onClick="" /><label for="dragmodepan"> <?php echo $lang['geodragmodepan'] ?></label>
        &nbsp;
    <input type="radio" name="dragmode" id="dragmodearea" onClick="map1.editTools.startRectangle()" /><label for="dragmodearea"><?php echo $lang['geodragmodeareaselect'] ?></label>
</div>

<!--Setup Leaflet map container with sizing-->
<div id="search_map" style="width: 99%; margin-top:0px; margin-bottom:0px; height: <?php echo $map1_height; ?>px; display:block; border:1px solid black; float:none; overflow: hidden;">
</div>

<script type="text/javascript">
    var Leaflet = L.noConflict();

    // Setup and define the Leaflet map with the initial view using leaflet.js and L.Control.Zoomslider.js.
    var map1 = new Leaflet.map('search_map', {
        editable: true,
        preferCanvas: true,
        renderer: Leaflet.canvas(),
        zoomsliderControl: <?php echo $zoomslider; ?>,
        zoomControl: <?php echo $zoomcontrol; ?>
    }).setView(<?php echo $map_centerview;?>);

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

    <!--Add download map button to the Leaflet map using bundle.min.js-->
    Leaflet.easyPrint({
        title: '<?php echo $lang['map_download'];?>',
        position: 'bottomleft',
        sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
        exportOnly: true,
        filename: 'search_results_map',
        customWindowTitle: '<?php echo $lang['map_print_title'];?>'
    }).addTo(map1);

    <!--Add a KML overlay to the Leaflet map using leaflet-omnivore.min.js-->
    <?php if ($map_kml)
        { ?>
        omnivore.kml('<?php echo $baseurl?>/filestore/system/<?php echo $map_kml_file?>').addTo(map1); <?php
        } ?>

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

        <!--Store the map window coordinate position to make it easier when returning for another search-->
        var mapCenter = map1.getCenter();
        SetCookie("geobound", mapCenter[1] + "," + mapCenter[0] + "," + map1.getZoom());

        <?php // Show the map in a modal.
        if ($geo_search_modal_results)
            { ?>
            ModalClose();
            ModalLoad(url); <?php
            }
        else 
            {?>
            CentralSpaceLoad(url, true); 
            <?php
            }?>
    });
</script>
</div>

<?php
include '../include/footer.php';
?>
