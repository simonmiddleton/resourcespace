<?php
// Collection Geolocation Edit Using Leaflet.js and Various Leaflet Plugins

include '../include/db.php';
include '../include/authenticate.php';

$ref = getvalescaped('ref', '', true);
if(!is_numeric($ref))
    {
    header('HTTP/1.1 400 Bad Request');
    exit($lang['error_resource_id_non_numeric']);
    }
if($leaflet_maps_enable)
    {
    redirect($baseurl_short . "pages/search.php?search=!collection" . $ref);
    }

include '../include/header.php';
    
// The two variables below act like permissions to display or not the page.
if($disable_geocoding || (!$disable_geocoding && !$geo_locate_collection))
    {
    header('HTTP/1.1 403 Forbidden');
    exit($lang['error-permissiondenied']);
    }


if(!collection_readable($ref))
    {
    header('HTTP/1.1 401 Unauthorized');
    die($lang['error-permissiondenied']);
    }

$all_resources = get_collection_resources($ref);
$collection = get_collection($ref);
$collectionname = $collection['name'];
$markers = array();
$check = false;

//If the collection is empty stop here and provide a message
if ( count($all_resources) == 0 ) {  exit( $lang["geoemptycollection"]);  }

//Start looping through the data fetched earlier
foreach ($all_resources as $value)
        {
    $resource = get_resource_data($value,$cache=true);
    
    //hide the resource if it is confidential
        if ( get_resource_access($resource['ref'])==2 ) {continue;}
    
    //If the resource is not confidential keep going
    else
    {
        $forthumb = get_resource_data($resource['ref']);
        $url = get_resource_path($resource['ref'],false,"thm",$generate=true,$extension="jpg",$scramble=-1,$page=1,$watermarked=false,$file_modified="",$alternative=-1,$includemodified=true);
        $new = str_replace($baseurl,"", $url);
        $parts =  explode('?',$new);
        
        if (  $resource['geo_long'] == '' || $resource['geo_lat'] == '' )
                {
                if (!$check)
                        {
                        echo $lang['location-missing'] ;
                        //Set check to true so the text above and the table below
                        //are only rendered if geolocation data are missing
                        $check = true;
                        ?>
                        <table class="InfoTable">
                        <tr>
                        <td><?php echo $lang["resourceid"]?></td>
                        <td><?php echo $lang["action-preview"]?></td>
                        <td><?php echo $lang['location-title']?></td>
                        </td>
                        </tr>
                        <?php
                        }
                        ?>

                <tr>
                <td><?php echo $resource['ref']?></td>
                <td><a href=<?php echo $baseurl . "/pages/view.php?ref=" . $resource['ref'] ?> onclick="return <?php echo ($resource_view_modal?'Modal':'CentralSpace') ?>Load(this, true);"> <img  src=<?php echo '..' . $parts[0]?>></a></td>
                <?php if (get_edit_access($resource['ref'])){?><td><a href=<?php echo $baseurl . "/pages/geo_edit.php?ref=" . $resource['ref'] . "&geocol=" . $ref ?> > <?php echo $lang['location-add']?></a></td><?php } else { ?><td> <?php echo $lang['location-noneselected'];?> </td><?php } ?>
                </tr>
                
                <?php
                }
        else
                {
                //These arrays are going to be passed to Javascript below to plot
                //echo $resource['field8'];
                $markers[] = "[{$resource['geo_long']}, {$resource['geo_lat']}, {$resource['ref']}, {$forthumb['thumb_width']}, {$forthumb['thumb_height']}]";
                $paths[]   = $parts[0];
                
                }
        }
}

?>
<?php if ($check){?></table><?php echo "<br />";}

//exit if there are no assets to put on the map
if (count($markers)==0) {exit;}?>

<div class="BasicsBox">
<div id="map_canvas_col" style="width: 100%; height: <?php echo isset($mapheight)?$mapheight:"500" ?>px; display:block; float:none;overflow: hidden;" class="Picture" ></div>

<script>

    map = new OpenLayers.Map("map_canvas_col");
    
    map.addControl(new OpenLayers.Control.LayerSwitcher({'ascending':false}));
    map.addLayer(new OpenLayers.Layer.OSM("<?php echo $lang["openstreetmap"]?>"
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
        }?>));
    
    
    epsg4326 =  new OpenLayers.Projection("EPSG:4326"); //WGS 1984 projection
    projectTo = map.getProjectionObject(); //The map projection (Spherical Mercator)
            
    var vectorLayer = new OpenLayers.Layer.Vector("Thumbnails");
    var vectorLayer2 = new OpenLayers.Layer.Vector("Markers");
    
    //Unloading values to Javascript, some cases require stripping
    //of backslashes because Javascript was complaining
    var markers = <?php echo str_replace(array('"','\\'),'',json_encode($markers)) ?>;
    var paths = <?php echo str_replace('\\','',json_encode($paths)) ?>;
    
    var baseurl = <?php echo str_replace('\\','',json_encode($baseurl) )?>;

    for (var i=0; i<markers.length; i++)
                {
        var lon = markers[i][0];
        var lat = markers[i][1];
        var rf = markers[i][2];
        var width = markers[i][3];
        var height = markers[i][4];
        var reslink = paths[i];
        
        
        var feature = new OpenLayers.Feature.Vector(
                        new OpenLayers.Geometry.Point( lon, lat ).transform(epsg4326, projectTo),
                        {description: baseurl +  '/pages/view.php?ref=' + rf},
                        
                        {externalGraphic: '..' + reslink, graphicHeight: height*0.45, graphicWidth: width*0.45 }
                );
                
                var feature2 = new OpenLayers.Feature.Vector(
                        new OpenLayers.Geometry.Point( lon, lat ).transform(epsg4326, projectTo),
                        {description: baseurl +  '/pages/view.php?ref=' + rf},
                        
                        {externalGraphic: '../lib/OpenLayers/img/marker.png', graphicHeight: 25, graphicWidth: 21 }
                );  
                
                vectorLayer.addFeatures(feature);
                vectorLayer2.addFeatures(feature2);
        }
                
                
        //Hide by default the thumbnails and display markers
        vectorLayer.setVisibility(false)               

        vectorLayer.events.register("featureselected", null, function(event){
        ModalLoad(event.feature.attributes.description)
        selectControl.unselectAll();
                });

        vectorLayer2.events.register("featureselected", null, function(event){
        ModalLoad(event.feature.attributes.description)
        selectControl.unselectAll();
                });
        
        // Add select feature control required to trigger events on the vector layer.
    var selectControl = new OpenLayers.Control.SelectFeature(vectorLayer);
    map.addControl(selectControl);
    selectControl.activate();
    
    var selectControl2 = new OpenLayers.Control.SelectFeature(vectorLayer2);
    map.addControl(selectControl2);
    selectControl2.activate();  
    
        map.addLayer(vectorLayer);
    map.addLayer(vectorLayer2);
        
    <?php
    if(count($geo_tile_servers) == 0)
        {
        // Block zoom beyond the tiles included in gfx/geotiles
        echo "
        map.isValidZoomLevel = function(zoomLevel)
            {
                console.log(zoomLevel);
                if(zoomLevel != null && zoomLevel <= 3)
                    {
                    console.log('OK');
                    }
            return (zoomLevel != null && zoomLevel <= 3);
            }
        map.zoomTo(2);
        ";
        }
    else
        {
        echo "map.zoomToExtent(vectorLayer2.getDataExtent());";
        }?>
</script>
</div>
<?php
include '../include/footer.php';
?>
