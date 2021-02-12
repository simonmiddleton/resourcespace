<?php
// Collection Geolocation Edit Using Leaflet.js and Various Leaflet Plugins

include '../include/db.php';
include '../include/authenticate.php';
include '../include/header.php';

// The two variables below act like permissions to display or not the page.
if($disable_geocoding || (!$disable_geocoding && !$geo_locate_collection))
    {
    header('HTTP/1.1 403 Forbidden');
    exit($lang['error-permissiondenied']);
    }

$ref = getvalescaped('ref', '', true);
if(!is_numeric($ref))
    {
    header('HTTP/1.1 400 Bad Request');
    exit($lang['error_resource_id_non_numeric']);
    }

if(!collection_readable($ref))
    {
    header('HTTP/1.1 401 Unauthorized');
    die($lang['error-permissiondenied']);
    }

global $baseurl, $geolocate_image_size, $lang, $geo_search_restrict, $baseurl, $baseurl_short, $mapedit_mapheight, $map_default, $map_zoomslider, $map_zoomnavbar, $map_kml, $map_kml_file, $map_retina, $map_polygon_field, $map_centerview, $marker_metadata_field, $marker_colors, $marker_resource_preview, $marker_color1, $marker_color2, $marker_color3, $marker_color4, $marker_color5, $marker_color6, $marker_color7, $marker_color8;

$marker_color_def = array($marker_color1, $marker_color2, $marker_color3, $marker_color4, $marker_color5, $marker_color6, $marker_color7, $marker_color8);
$zoomslider = 'false';
$zoomcontrol = 'true';
$polygon = "";

// Set Leaflet map search view height and layer control container height based on $mapheight.
if (isset($mapedit_mapheight))
    {
    $map1_height = $mapedit_mapheight;
    $layer_controlheight = $mapedit_mapheight - 40;
    }
else // Default values.
    {
    $map1_height = 300;
    $layer_controlheight = 250;
    }

// Show zoom slider instead of default Leaflet zoom control?
if ($map_zoomslider)
    {
    $zoomslider = 'true';
    $zoomcontrol = 'false';
    }

$all_resources = get_collection_resources($ref);
$collection = get_collection($ref);
$collectionname = $collection['name'];
$markers = array();
$check = false;

?>
<h1><?php echo $lang['geolocatecollection']; ?></h1>
<h3><?php echo $lang['collectionname'] . ': ' . $collectionname; ?></h3> <?php

// If the collection is empty, stop here and provide a message.
if (count($all_resources) == 0)
    {
    exit($lang['geoemptycollection']);
    }

// Start looping through the data fetched earlier.
foreach ($all_resources as $value)
    {
    $resource = get_resource_data($value, $cache = true);

    // Hide the resource if it is confidential.
    if (get_resource_access($resource['ref']) == 2)
        {
        continue;
        }

    else // If the resource is not confidential, keep going.
        {
        // Get resource data for resources returned by the current search.
        $url = get_resource_path($resource['ref'], false, $geolocate_image_size, $generate = true, $extension = 'jpg', $scramble = -1, $page = 1, $watermarked = false, $file_modified = '', $alternative = -1, $includemodified = true);
        $parts = explode('?', str_replace($baseurl, '', $url));
        $geomark = get_resource_data($resource['ref'], $cache = false);
        $geomark['preview_path'] = strstr(get_resource_path($resource['ref'], false, 'thm', false, $extension = 'jpg', true, 1, $watermarked = false, $file_modified = ''), '?', true);

        // Get custom metadata field value.
        if (isset($marker_metadata_field))
            {
            $ref1 = $resource['ref'];
            $geomark2 = sql_query("SELECT value FROM resource_data WHERE resource = '$ref1' AND resource_type_field = '$marker_metadata_field'");
            }
        else
            {
            $geomark2[0]['value'] = '';
            }

        if ($resource['geo_long'] == '' || $resource['geo_lat'] == '')
            {
            // Set check to true so the text and the table below are only rendered if geolocation data are missing.
            if (!$check)
                {
                echo $lang['location-missing'];
                $check = true; ?>
                <table class="InfoTable">
                <tr>
                    <td><b><?php echo $lang['resourceid']; ?> </b></td>
                    <td><b><?php echo $lang['action-preview']; ?> </b></td>
                    <td><b><?php echo $lang['location-title']; ?> </b></td>
                    </td>
                </tr> <?php
                } ?>

            <tr>
                <td><?php echo $resource['ref']; ?></td>
                <td><a href=<?php echo $baseurl . "/pages/view.php?ref=" . $resource['ref'] ?> onclick="return <?php echo ($resource_view_modal ? "Modal" : "CentralSpace") ?>Load(this, true);"> <img src=<?php echo '..' . $parts[0]; ?> </img></a></td>
                <?php if (get_edit_access($resource['ref'])) 
                    { ?><td><a href=<?php echo $baseurl . "/pages/geo_edit.php?ref=" . $resource['ref'] . "&geocol=" . $ref ?> > <?php echo $lang['location-add']; ?></a></td> <?php 
                    } 
                else 
                    { ?>
                    <td> <?php echo $lang['location-noneselected']; ?> </td> <?php 
                    } ?>
            </tr> <?php
            }
        else // Create array of geolocation parameters.
            {
            $geomarker[] = "[{$geomark['geo_long']}, {$geomark['geo_lat']}, {$geomark['ref']}, {$geomark['resource_type']}, {$geomark2[0]['value']}]";
            $preview_paths[] = $geomark['preview_path'];
            }
        }
    }

if ($check)
    { ?>
    </table> <?php echo "<br/>";
    }

// Exit if there are no assets to put on the map.
if (count($geomarker) == 0)
    {
    exit;
    }

echo $lang['geolocate_collection_map_text']; ?>

<!--Setup Leaflet map container with sizing-->
<div id="collection_map" style="width: 99%; margin-top:0px; margin-bottom:0px; height: <?php echo $map1_height; ?>px; display:block; border:1px solid black; float:none; overflow: hidden;">
</div>

<script type="text/javascript">
    var Leaflet = L.noConflict();

    <!--Setup and define the Leaflet map with the initial view using leaflet.js and L.Control.Zoomslider.js-->
    var map3 = new Leaflet.map('collection_map', {
        preferCanvas: true,
        renderer: Leaflet.canvas(),
        zoomsliderControl: <?php echo $zoomslider; ?>,
        zoomControl: <?php echo $zoomcontrol; ?>
    }).setView(<?php echo $map_centerview; ?>);

    // Load available Leaflet basemap groups, layers, and attribute definitions.
    <?php include '../include/map_processing.php'; ?>

    <!--Define default Leaflet basemap layer using leaflet.js, leaflet.providers.js, and L.TileLayer.PouchDBCached.js-->
    var defaultLayer = new Leaflet.tileLayer.provider('<?php echo $map_default;?>', {
        useCache: '<?php echo $map_default_cache;?>', <!--Use browser caching of tiles (recommended)?-->
        detectRetina: '<?php echo $map_retina;?>', <!--Use retina high resolution map tiles?-->
        attribution: default_attribute
    }).addTo(map3);

    // Load Leaflet basemap definitions.
    <?php include '../include/map_basemaps.php'; ?>

    <!--Set styled layer control options for basemaps and add to the Leaflet map using styledLayerControl.js-->
    var options = {
        container_maxHeight: '<?php echo $layer_controlheight; ?>px',
        group_maxHeight: '180px',
        exclusive: false
    };

    var control = Leaflet.Control.styledLayerControl(baseMaps,options);
    map3.addControl(control);
    
    <!--Show zoom history navigation bar and add to Leaflet map using Leaflet.NavBar.min.js-->
    <?php if ($map_zoomnavbar && $map1_height >= 400)
        { ?>
        Leaflet.control.navbar().addTo(map3); <?php
        } ?>

    <!--Add a scale bar to the Leaflet map using leaflet.min.js-->
    new Leaflet.control.scale().addTo(map3);
    
    <!--Add download map button to the Leaflet map using bundle.min.js-->
    <?php if ($map1_height >= 335)
        { ?>
        Leaflet.easyPrint({
            title: "<?php echo $lang['leaflet_mapdownload']; ?>",
            position: 'bottomleft',
            sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
            exportOnly: true,
            filename: 'search_results_map',
            customWindowTitle: "<?php echo $lang['map_print_title']; ?>"
        }).addTo(map3); <?php
        } ?>

    <!--Add a KML overlay to the Leaflet map using leaflet-omnivore.min.js-->
    <?php if ($map_kml)
        { ?>
        omnivore.kml('<?php echo $baseurl?>/filestore/system/<?php echo $map_kml_file?>').addTo(map3); <?php
        } ?>

    <!--Limit geocoordinate values to six decimal places for display on marker hover-->
    function georound(num) {
        return +(Math.round(num + "e+6") + "e-6");
    }

    <!--If no data (markers), only show the empty Leaflet map-->
    <?php if (!empty($geomarker))
        { ?>
        <!--Setup and configure initial marker info from resource data-->
        var geomarker = <?php echo str_replace(array('"', '\\'), '', json_encode($geomarker)); ?>;
        var previewPaths = <?php echo json_encode($preview_paths); ?>;
        var markerArray = [];
        var win_url;

        <!--Setup marker clustering using leaflet.markercluster.js for many overlapping markers common in low zoom levels-->
        var markers = Leaflet.markerClusterGroup({
            maxClusterRadius: 75,
            disableClusteringAtZoom: 14,
            chunkedLoading: true, <!--Load markers in chunks to avoid slow browser response-->
            elementsPlacementStrategy: 'original-locations' <!--Cluster items placement strategy-->
        });

        <!--Cycle through the resources to create markers as needed and colored by resource type-->
        for (var i = 0; i < geomarker.length; i++)
            {
            var lon = geomarker[i][0]; <!--Resource longitude value-->
            var lat = geomarker[i][1]; <!--Resource latitude value-->
            var rf = geomarker[i][2]; <!--Resource reference value-->
            var rtype = geomarker[i][3]; <!--Resource type-->
            var cmfm = geomarker[i][4]; <!--Custom metadata field marker coloring-->
            var preview = previewPaths[i]; <!--Resource preview image path-->

            <!--Check for resources without geolocation or invalid coordinates and skip those-->
            if (lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180)
                { <?php
                // Check if using a custom metadata field for coloring the markers and redefine rtype.
                if (isset($marker_metadata_field))
                    {
                    for ($i = 0; $i < 8; $i++)
                        { ?>
                        if (cmfm >= <?php echo $marker_metadata_array[$i]['min']; ?> && cmfm <= <?php echo $marker_metadata_array[$i]['max']; ?>)
                            {
                            rtype = <?php echo ($i + 1); ?>;
                            } <?php
                        }
                    } ?>

                <!--Set each resource marker color based on resource type or metadata field to marker color mapping up to eight-->
                switch(rtype) {
                    case 1:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[0]]);?>Icon;
                        break;
                    case 2:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[1]]);?>Icon;
                        break;
                    case 3:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[2]]);?>Icon;
                        break;
                    case 4:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[3]]);?>Icon;
                        break;
                    case 5:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[4]]);?>Icon;
                        break;
                    case 6:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[5]]);?>Icon;
                        break;
                    case 7:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[6]]);?>Icon;
                        break;
                    case 8:
                        iconColor = <?php echo strtolower($marker_colors[$marker_color_def[7]]);?>Icon;
                        break;
                    default:
                        iconColor = blackIcon;
                    }

                <!--Define the marker arrays for the markers, zoom to the markers, and marker click function using leaflet.js and leaflet-color-markers.js-->
                <!--Create a marker for each resource for map zoom to the markers-->
                markerArray.push(new L.marker([lat, lon], {
                    opacity: 0
                }).addTo(map3));

                <!--Create a marker for each resource-->
                <?php if ($marker_resource_preview)
                    { ?>
                    var marker = new Leaflet.marker([lat, lon], {
                        icon: iconColor,
                        riseOnHover: true,
                        win_url: geomarker[i][2],
                        title: georound(lat) + ", " + georound(lon) + " (WGS84)"
                    }); 
                    
                    <!--Show the resource preview image-->
                    var imagePath = "<img src='" + preview + "'/>";
                    var text1 = "<?php echo $lang['resourceid']; ?>";
                    var imageLink = '<a href=' + baseurl + '/pages/view.php?ref=' + rf + " target='_blank'" + '>' + '<img src=' + preview + '>' + '</a>';
                    marker.bindPopup(imageLink + text1 + " " + rf + "<br>" + georound(lat) + ", " + georound(lon), {
                        minWidth: 155,
                        autoPan: true,
                        autoPanPaddingTopLeft: 5,
                        autoPanPaddingBottomRight: 5
                    }); <?php
                    } 
                else // Show resource ID in marker tooltip.
                    { ?> 
                    var marker = new Leaflet.marker([lat, lon], {
                        icon: iconColor,
                        title: 'ID# ' + rf,
                        riseOnHover: true,
                        win_url: geomarker[i][2]
                    }).on('click', showModal); <?php
                    } ?>

                <!--Add markers to the layer array-->
                markers.addLayer(marker);
                }
            }

        <!--Add the markers layer to the map-->
        map3.addLayer(markers);

        <!--Zoom to the markers on the map regardless of the initial view-->
        var group = Leaflet.featureGroup(markerArray);
        map3.fitBounds(group.getBounds().pad(0.25));

        <!--On marker click, open a modal corresponding to the specific resource-->
        function showModal(e)
            {
            ModalLoad(baseurl + '/pages/view.php?ref=' + this.options.win_url);
            }

        <!--Fix for Microsoft Edge and Internet Explorer browsers-->
        map3.invalidateSize(true);
  <?php } ?>
</script>

<!--Create a map marker legend below the map and only show for defined types up to eight-->
<p style="margin-top:4px;margin-bottom:0px;"> <?php
    leaflet_markers_legend(); ?>
</p>

<?php
include '../include/footer.php';
?>
