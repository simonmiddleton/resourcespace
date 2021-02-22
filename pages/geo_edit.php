<?php
include "../include/db.php";

include "../include/authenticate.php"; 
include "../include/header.php";

if ($disable_geocoding){exit("Geomapping disabled.");}

# Fetch resource data.
$ref = getvalescaped('ref','',true);

# Fetch 'order_by' and 'search' parameters so we pass back to url when navigating
$order_by = getvalescaped('order_by','resourceid');
$search = getvalescaped('search','!last1000');

# See if we came from the geolocate_collection page
$geocol = getvalescaped('geocol','',true);
if ($ref=='') {die;}
$resource=get_resource_data($ref);
if ($resource==false) {die;}

# Not allowed to edit this resource?
if (!get_edit_access($ref,$resource["archive"],false,$resource)) {exit ("Permission denied.");}

?>
<?php

if (isset($_POST['submit']) && enforcePostRequest(false))
    {
    $s=explode(",",getvalescaped('geo-loc',''));
    if (count($s)==2) 
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


 ?>

<div class="RecordBox">
<div class="RecordPanel">
<div class="Title"><?php echo $lang['location-title']; render_help_link("user/geolocation");?></div>
<?php if (!hook("customgeobacklink")) { ?>
<p><a onClick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short . ($geocol != '' ? "pages/geolocate_collection.php?ref=" . $geocol : "pages/view.php?ref=" . $ref) ?>&search=<?php echo $search; ?>&order_by=<?php echo $order_by; ?>"><?php echo LINK_CARET_BACK . ($geocol != '' ? $lang['backtogeolocatecollection'] : $lang['backtoresourceview']) ?></a></p>
<?php } ?>

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

	//dragfeature = new OpenLayers.Control.DragFeature(markers,{'onComplete': onCompleteMove});
	//map.addControl(dragfeature);
	//dragfeature.activate();

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
        
        //marker.lonlat=new OpenLayers.LonLat(bounds.x,bounds.y);
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
hook("rendermapfooter");
?>
<p><?php echo $lang['location-details']; ?></p>
<form id="map-form" method="post" action="<?php echo $baseurl_short?>pages/geo_edit.php">
    <?php generateFormToken("map-form"); ?>
<input name="ref" type="hidden" value="<?php echo $ref; ?>" />
<input name="geocol" type="hidden" value="<?php echo $geocol; ?>" />
<input name="search" type="hidden" value="<?php echo $search; ?>" />
<input name="order_by" type="hidden" value="<?php echo $order_by; ?>" />
<input name="map-zoom" type="hidden" value="<?php echo $zoom ?>" id="map-zoom" />
<?php echo $lang['latlong']; ?>: <input name="geo-loc" type="text" size="50" value="<?php echo $resource["geo_long"]==""?"":($resource["geo_lat"] . "," . $resource["geo_long"]) ?>" id="map-input" />
<?php hook("renderlocationextras"); ?>
<input name="submit" type="submit" value="<?php echo $lang['save']; ?>" />
</form>

</div>

</div>
<?php
include "../include/footer.php";
?>
