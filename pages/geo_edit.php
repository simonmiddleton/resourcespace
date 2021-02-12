<?php
// Resource Map Geolocation Edit Using Leaflet.js and Various Leaflet Plugins

// Check if geolocation/maps have been disabled.
global $disable_geocoding, $lang;
if($disable_geocoding)
    {
    header('HTTP/1.1 403 Forbidden');
    exit($lang['error-geocodingdisabled']);
    }

include '../include/db.php';
include '../include/authenticate.php';
include '../include/header.php';

// Setup initial map variables.
global $default_display, $baseurl, $mapsearch_height, $map_default, $map_centerview, $map_zoomslider, $map_zoomnavbar, $map_kml, $map_kml_file, $map_default_cache, $map_layer_cache, $map_retina, $mapedit_mapheight, $layer_controlheight;
$zoomslider = 'false';
$zoomcontrol = 'true';

// Show zoom slider instead of default Leaflet zoom control?
if ($map_zoomslider)
    {
    $zoomslider = 'true';
    $zoomcontrol = 'false';
    }

// Fetch the resource data.
$ref = getvalescaped('ref', '', true);
$geo_lat = getvalescaped('new_lat', '');
$geo_long = getvalescaped('new_long', '');
$zoom = getvalescaped('new_zoom', '');

set_geo_bounds();

// See if we came from the ../pages/geolocate_collection.php page.
$geocol = getvalescaped('geocol', '', true);
if ($ref == '')
    {
    die;
    }
$resource = get_resource_data($ref);
if ($resource == false)
    {
    die;
    }

// Check if the user is allowed to edit this resource.
if (!get_edit_access($ref, $resource['archive'], false, $resource))
    {
    exit($lang['error-permissiondenied']);
    }

?>
<?php
// Update database with geolocation.
$valid_coords = true;

if (isset($_POST['submit']) && enforcePostRequest(false))
    {
    $s=explode(",",getvalescaped('geo-loc',''));
    
    $lat = isset($s[0]) ? $s[0]: "";
    $lng = isset($s[1]) ? $s[1]: "";

    if (!is_numeric($lat) || $lat < -20037508.34 || $lat > 20037508.34)
        {
        $valid_coords = false;
        }

    if (!is_numeric($lng) || $lng < -20037508.34 || $lng > 20037508.34)
        {
        $valid_coords = false;
        }

    if ( count($s)==2  && $valid_coords == true) 
		{    
        $mapzoom=getvalescaped('map-zoom','');        
		if ($mapzoom>=2 && $mapzoom<=21)
			{
    			sql_query("update resource set geo_lat='" . escape_check($s[0]) . "',geo_long='" . escape_check($s[1]) . "',mapzoom='" . escape_check($mapzoom) . "' where ref='$ref'");    
			}
		else
			{
    			sql_query("update resource set geo_lat='" . escape_check($s[0]) . "',geo_long='" . escape_check($s[1]) . "',mapzoom=null where ref='$ref'");    
			}
		hook("savelocationextras");
		}
	elseif (getval('geo-loc','')=='') 
		{
		# Blank geo-location
		sql_query("update resource set geo_lat=null,geo_long=null,mapzoom=null where ref='$ref'");
		hook("removelocationextras");
		}
	# Reload resource data
	$resource=get_resource_data($ref,false);

    // Reload resource data.
    $resource = get_resource_data($ref, false);
    }


 ?>
  <?php
echo $valid_coords == false ? "<p class='FormIncorrect'>" . $lang['location-validation-error']  . " " . $lang['location-help'] . "</p>" : "";
?>

<div class="RecordBox">
<div class="RecordPanel">
<div class="Title"><?php echo $lang['location-title']; ?></div>

<?php if (!hook('customgeobacklink'))
    { ?>
    <p><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short . ($geocol != '' ? "pages/geolocate_collection.php?ref=" . $geocol : "pages/view.php?ref=" . $ref) ?>"><?php echo LINK_CARET_BACK . ($geocol != '' ? $lang['backtogeolocatecollection'] : $lang['backtoresourceview']) ?></a></p> <?php
    } ?>

<!--Map introtext-->
<div id="map_introtext" style="margin-top:0px; margin-bottom:0px; width: 99%;">
    <p> <?php echo $lang['edit_map_introtext'];?> </p>
</div>

<!--Setup Leaflet map container with sizing-->
<div id="map_edit" style="width: 99%; margin-top:0px; margin-bottom:0px; height: <?php echo $mapedit_mapheight;?>px; display:block; border:1px solid black; float:none; overflow: hidden;">
</div>

<script type="text/javascript">
    var Leaflet = L.noConflict();

    <!--Setup and define the Leaflet map with the initial view using leaflet.js and L.Control.Zoomslider.js-->
    var map2 = new Leaflet.map('map_edit', {
        renderer: Leaflet.canvas(),
        zoomsliderControl: <?php echo $zoomslider?>,
        zoomControl: <?php echo $zoomcontrol?>
    }).setView(<?php echo $map_centerview; ?>);

    // Load available Leaflet basemap groups, layers, and attribute definitions.
    <?php include '../include/map_processing.php'; ?>

    <!--Define default Leaflet basemap layer using leaflet.js, leaflet.providers.js, and L.TileLayer.PouchDBCached.js-->
    var defaultLayer = new Leaflet.tileLayer.provider('<?php echo $map_default;?>', {
        useCache: '<?php echo $map_default_cache;?>', <!--Use browser caching of tiles (recommended)?-->
        detectRetina: '<?php echo $map_retina;?>', <!--Use retina high resolution map tiles?-->
        attribution: default_attribute
    }).addTo(map2);

    // Load Leaflet basemap definitions.
    <?php include '../include/map_basemaps.php'; ?>

    <!--Set styled layer control options for basemaps and add to the Leaflet map using styledLayerControl.js-->
    var options = {
        container_maxHeight: '<?php echo $layer_controlheight?>px',
        group_maxHeight: '180px',
        exclusive: false
    };

    var control = Leaflet.Control.styledLayerControl(baseMaps,options);
    map2.addControl(control);

    <!--Add geocoder search bar using control.geocoder.min.js-->
    Leaflet.Control.geocoder().addTo(map2);

    <!--Show zoom history navigation bar and add to Leaflet map using Leaflet.NavBar.min.js-->
    <?php if ($map_zoomnavbar)
        { ?>
        Leaflet.control.navbar().addTo(map2); <?php
        } ?>

    <!--Add a scale bar to the Leaflet map using leaflet.min.js-->
    new Leaflet.control.scale().addTo(map2);

    <!--Add download map button to the Leaflet map using bundle.min.js-->
    Leaflet.easyPrint({
        title: "<?php echo $lang['map_download'];?>",
        position: 'bottomleft',
        sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
        exportOnly: true,
        filename: 'resource_edit_map',
        customWindowTitle: "<?php echo $lang['map_print_title'];?>"
    }).addTo(map2);

    <!--Add a KML overlay to the Leaflet map using leaflet-omnivore.min.js-->
    <?php if ($map_kml)
        { ?>
        omnivore.kml('<?php echo $baseurl?>/filestore/system/<?php echo $map_kml_file?>').addTo(map2); <?php
        } ?>

    <!--Fix for Microsoft Edge and Internet Explorer browsers-->
    map2.invalidateSize(true);

    <!--Limit geocoordinate values to six decimal places for display on marker hover-->
    function georound(num) {
        return +(Math.round(num + "e+6") + "e-6");
    }

    <!--Add a marker to the map if the resource has valid coordinates-->
    var resourceMarker = {}; <?php
    if (leaflet_coordinate_check($resource['geo_lat'], 'latitude') && leaflet_coordinate_check($resource['geo_long'], 'longitude'))
        {
        $resourcezoom = leaflet_map_zoom($resource['mapzoom']); ?>
        resourceLat = <?php echo htmlspecialchars($resource['geo_lat']); ?>;
        resourceLong = <?php echo htmlspecialchars($resource['geo_long']); ?>;
        resourceZoom = <?php echo $resourcezoom; ?>;

        resourceMarker = Leaflet.marker([resourceLat, resourceLong], {
            title: georound(resourceLat) + ", " + georound(resourceLong) + " (WGS84)"
        }).addTo(map2);
        map2.setView([resourceLat, resourceLong], resourceZoom); <?php
        } ?>

    <!--Place a marker on the map when clicked-->
    currentZoom = map2.getZoom();
    map2.on('click', function(e) {
        geoLat = e.latlng.lat;
        geoLong = e.latlng.lng;
        currentZoom = map2.getZoom();
        console.log(geoLat, geoLong, currentZoom);

        <!--Clear existing marker when locating a new marker as we only want one marker for the resource-->
        if (resourceMarker != undefined) {
            map2.removeLayer(resourceMarker);
        };

        <!--Add a marker to show where you clicked on the map last and center the map on the marker-->
        resourceMarker = L.marker([geoLat, geoLong], {
            title: georound(geoLat) + ", " + georound(geoLong) + " (WGS84)"
        }).addTo(map2);
        map2.setView([geoLat, geoLong], currentZoom);

        <!--Set the resource marker geolocation value-->
        document.getElementById('map-input').value=georound(geoLat) + ', ' + georound(geoLong);
        jQuery.ajax({
            type: "POST",
            url: "<?php echo $baseurl_short; ?>pages/geo_edit.php",
            dataType: "text",
            data: {
                new_lat: geoLat,
                new_long: geoLong,
                new_zoom: currentZoom,
                csrf_identifier: '<?php echo $CSRF_token_identifier; ?>',
                <?php echo generateAjaxToken('geo_edit'); ?>,
            }
        });
    });
</script>

<p></p> <?php
hook('rendermapfooter'); ?>

<!--Resource marker latitude and longitude form-->
<form id="map-form" method="post" action="<?php echo $baseurl_short?>pages/geo_edit.php">
    <?php generateFormToken("map-form"); ?>
    <input name="ref" type="hidden" value="<?php echo $ref; ?>" />
    <input name="geocol" type="hidden" value="<?php echo $geocol; ?>" />
    <input name="map-zoom" type="hidden" value="<?php echo $zoom; ?>" id="map-zoom" />
    <?php echo $lang['marker'] . " " . strtolower($lang['latlong']); ?>: <input name="geo-loc" type="text" size="50" value="<?php echo $resource['geo_long'] == ""?"" : ($resource['geo_lat'] . ',' . $resource['geo_long']) ?>" id="map-input" />
    <?php hook('renderlocationextras'); ?>
    <input name="submit" type="submit" value="<?php echo $lang['save']; ?>" />
</form>



</div>
</div> <?php

include '../include/footer.php';
?>
