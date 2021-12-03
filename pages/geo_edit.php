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
global $default_display, $baseurl, $mapsearch_height, $map_default, $map_zoomslider, $map_zoomnavbar, $map_kml, $map_kml_file, $map_default_cache, $map_layer_cache, $map_retina, $mapedit_mapheight, $layer_controlheight;
$zoomslider = 'false';
$zoomcontrol = 'true';

// Show zoom slider instead of default Leaflet zoom control?
if ($map_zoomslider)
    {
    $zoomslider = 'true';
    $zoomcontrol = 'false';
    }

// Fetch the resource data.
$ref = getvalescaped('ref', 0, true);

// See if we came from the ../pages/geolocate_collection.php page.
$geocol = getvalescaped('geocol', '', true);
$resource = get_resource_data($ref);
if ($resource == false)
    {
    $onload_message = array("title" => $lang["error"],"text" => $lang['resourcenotfound']);
    include "../include/footer.php";
    exit();
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

    if (!is_numeric($lat) || $lat < -90 || $lat > 90)
        {
        $valid_coords = false;
        }

    if (!is_numeric($lng) || $lng < -180 || $lng > 180)
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
    }

// $geo_lat = getval('new_lat', $resource["geo_lat"]);
// $geo_long = getval('new_long',  $resource["geo_long"]);
$zoom = getval('new_zoom',  $resource["mapzoom"]);

echo $valid_coords == false ? "<p class='FormIncorrect'>" . $lang['location-validation-error']  . "</p>" : "";
?>

<div class="RecordBox">
<div class="RecordPanel">
<div class="Title"><?php echo $lang['location-title']; render_help_link("user/geolocation");?></div>

<?php if (!hook('customgeobacklink'))
    { ?>
    <p><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short . ($geocol != '' ? "pages/geolocate_collection.php?ref=" . $geocol : "pages/view.php?ref=" . $ref) ?>"><?php echo LINK_CARET_BACK . ($geocol != '' ? $lang['backtogeolocatecollection'] : $lang['backtoresourceview']) ?></a></p> <?php
    }

if($leaflet_maps_enable)
    {
    ?>
    <!--Setup Leaflet map container with sizing-->
    <div id="map_edit" style="width: 99%; margin-top:0px; margin-bottom:0px; height: <?php echo $mapedit_mapheight;?>px; display:block; border:1px solid black; float:none; overflow: hidden;">
    </div>

    <script type="text/javascript">
        var Leaflet = L.noConflict();    
        
        <!--Setup and define the Leaflet map with the initial view using leaflet.js and L.Control.Zoomslider.js-->
        <?php set_geo_map_centerview(); ?>
        var map2 = new Leaflet.map('map_edit', {
            renderer: Leaflet.canvas(),
            zoomsliderControl: <?php echo $zoomslider?>,
            zoomControl: <?php echo $zoomcontrol?>,
            worldCopyJump: true
        }).setView(mapcenterview,mapdefaultzoom);

        map2.on('baselayerchange', function (e) {
            currentLayerID = e.layer._leaflet_id;
            SetCookie('geo_layer', e.layer.options.name);
            });

        // Load available Leaflet basemap groups, layers, and attribute definitions.
        <?php include '../include/map_processing.php'; ?>

        <!--Define default Leaflet basemap layer using leaflet.js, leaflet.providers.js, and L.TileLayer.PouchDBCached.js-->
        var defaultLayer = new Leaflet.tileLayer.provider('<?php echo $map_default;?>', {
            useCache: '<?php echo $map_default_cache;?>', <!--Use browser caching of tiles (recommended)?-->
            detectRetina: '<?php echo $map_retina;?>', <!--Use retina high resolution map tiles?-->
            attribution: default_attribute
        }).addTo(map2);

        // Load Leaflet basemap definitions.
        <?php include '../include/map_basemaps.php'; 
        // Get the resource type to determine the icon to use   
        $maprestype = get_resource_types($resource['resource_type']);
        $markercolour = (isset($maprestype[0]) && isset($MARKER_COLORS[$maprestype[0]["colour"]])) ? (int)$maprestype[0]["colour"] : ($resource['resource_type'] % count($MARKER_COLORS));
        $markercolourjs =  strtolower($MARKER_COLORS[$markercolour])  . "Icon";
        ?>

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
        
        <?php
        hook("map_additional");
        ?>
        <!--Add a KML overlay to the Leaflet map using leaflet-omnivore.min.js-->
        <?php if ($map_kml)
            { ?>
            omnivore.kml('<?php echo $baseurl?>/filestore/system/<?php echo $map_kml_file?>').addTo(map1); <?php
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
                icon: <?php echo $markercolourjs  . ","?>
                title: georound(resourceLat) + ", " + georound(resourceLong) + " (WGS84)"
            }).addTo(map2);
            map2.setView([resourceLat, resourceLong], resourceZoom); <?php
            } ?>

        <!--Place a marker on the map when clicked-->

        map2.on('click', function(e) 
            {
            currentZoom = map2.getZoom();        
            console.log('Zoom: ' + currentZoom);
            realpoint = map2.wrapLatLng(e.latlng);
            geoLat = realpoint.lat;
            geoLong = realpoint.lng;

            <!--Clear existing marker when locating a new marker as we only want one marker for the resource-->
            if (resourceMarker != undefined) {
                map2.removeLayer(resourceMarker);
            };

            <!--Add a marker to show where you clicked on the map last and center the map on the marker-->
            resourceMarker = L.marker([geoLat, geoLong], {
                icon: <?php echo $markercolourjs  . ","?>
                title: georound(geoLat) + ", " + georound(geoLong) + " (WGS84)"
            }).addTo(map2);


            <!--Set the resource marker geolocation value-->
            document.getElementById('map-input').value=georound(geoLat) + ', ' + georound(geoLong);
            document.getElementById('map-zoom').value=currentZoom;
            map2.setView([geoLat, geoLong], currentZoom);
                  
            <?php if ($edit_autosave)
                {
                ?>
                jQuery.ajax({
                    type: "POST",
                    url: "<?php echo $baseurl_short; ?>pages/geo_edit.php",
                    dataType: "text",
                    data: {
                        'submit': 'true',
                        'ajax': 'true',
                        'ref': '<?php echo $ref ; ?>',
                        'geo-loc': geoLat + ',' + geoLong,
                        'map-zoom': currentZoom,
                        csrf_identifier: '<?php echo $CSRF_token_identifier; ?>',
                        <?php echo generateAjaxToken('geo_edit'); ?>,
                        }
                    });
                <?php
                }?>                
            });
    </script>
    <?php
    }
else
    {
    // OpenLayers legacy code
    ?>
    <!-- Drag mode selector -->
    <div id="GeoDragMode">
    <?php echo $lang["geodragmode"] ?>:&nbsp;
    <input type="radio" name="dragmode" id="dragmodearea" checked="checked" onClick="control.point.activate()" /><label for="dragmodearea"><?php echo $lang["geodragmodearea"] ?></label>
    &nbsp;&nbsp;
    <input type="radio" name="dragmode" id="dragmodepan" onClick="control.point.deactivate();" /><label for="dragmodepan"><?php echo $lang["geodragmodepan"] ?></label>
    </div>

    <?php include "../include/geo_map.php";
    if ($resource["geo_long"]!="") {
            $zoom = $resource["mapzoom"];
            if (!($zoom>=2 && $zoom<=21)) {
                    // set $zoom based on precision of specified position
                    $zoom = 18;
                    $siglon = round(100000*abs($resource["geo_long"]))%100000;
                    $siglat = round(100000*abs($resource["geo_lat"]))%100000;
                    if ($siglon%100000==0 && $siglat%100000==0) {
                            $zoom = 3;
                    } elseif ($siglon%10000==0 && $siglat%10000==0) {
                            $zoom = 6;
                    } elseif ($siglon%1000==0 && $siglat%1000==0) {
                            $zoom = 10;
                    } elseif ($siglon%100==0 && $siglat%100==0) {
                            $zoom = 15;
                    }
            }
    } else {
            $zoom = 2;
    }
    ?>
    <script>
            var zoom = <?php echo $zoom ?>;
        <?php if ($resource["geo_long"]!=="") {?>
        var lonLat = new OpenLayers.LonLat(<?php echo $resource["geo_long"] ?>, <?php echo $resource["geo_lat"] ?>)
            .transform(
                new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
                map.getProjectionObject() // to Spherical Mercator Projection
            );
            <?php } else { ?>
            var lonLat = new OpenLayers.LonLat(0,0);
            <?php } ?>
            function zoomListener (theEvent) {
                    document.getElementById('map-zoom').value=map.getZoom();
            }
            map.events.on({"zoomend": zoomListener});
    
        var markers = new OpenLayers.Layer.Markers("<?php echo $lang["markers"]?>");
        map.addLayer(markers);
    <?php  
    if (!hook("makemarker")) {
    ?>
            var marker = new OpenLayers.Marker(lonLat);
    <?php
    }
    ?>
        markers.addMarker(marker);

        var control = new OpenLayers.Control();
        OpenLayers.Util.extend(control, {
        draw: function () {
            this.point = new OpenLayers.Handler.Point( control,
                {"done": this.notice});
            this.point.activate();
        },

        notice: function (bounds) {
            marker.lonlat.lon=(bounds.x);
            marker.lonlat.lat=(bounds.y);
            markers.addMarker(marker);
            
            // Update control
            var translonlat=new OpenLayers.LonLat(bounds.x,bounds.y).transform
                (
            map.getProjectionObject(), // from Spherical Mercator Projection}
                new OpenLayers.Projection("EPSG:4326") // to WGS 1984
            );
        
            document.getElementById('map-input').value=translonlat.lat + ',' + translonlat.lon;
            
        }
            });map.addControl(control);
    jQuery('#UICenter').scroll(function() {
    map.events.clearMouseCache();
    });
        <?php if ($resource["geo_long"]!=="") {?>                    
        map.setCenter (lonLat, Math.min(zoom, map.getNumZoomLevels() - 1));
        <?php } else { ?>

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
                    map.setCenter(new OpenLayers.LonLat(<?php echo $bounds[0] ?>,<?php echo $bounds[1] ?>),<?php echo $bounds[2] ?>);

        <?php } ?>

    </script> 
    <?php
    }

hook('rendermapfooter'); ?>

<!--Resource marker latitude and longitude form-->
<form id="map-form" method="post" action="<?php echo $baseurl_short?>pages/geo_edit.php">
    <?php generateFormToken("map-form"); ?>
    <input name="ref" type="hidden" value="<?php echo $ref; ?>" />
    <input name="submit" type="hidden" value="true" />
    <input name="geocol" type="hidden" value="<?php echo $geocol; ?>" />
    <input name="map-zoom" type="hidden" value="<?php echo $zoom; ?>" id="map-zoom" />
    <?php echo $lang['marker'] . " " . strtolower($lang['latlong']); ?>: <input name="geo-loc" type="text" size="50" value="<?php echo $resource['geo_long'] == ""?"" : ($resource['geo_lat'] . ',' . $resource['geo_long']) ?>" id="map-input" />
    <?php hook('renderlocationextras'); ?>
    <input name="submit" type="submit" value="<?php echo $lang['save']; ?>" onclick="return CentralSpacePost(this.form,true);" />
</form>



</div>
</div>
<?php

include '../include/footer.php';