<?php

include_once "../../../include/db.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/header.php";

$ref        = getval("ref",0,true);
$search     = getval("search","");
$offset     = getval("offset",0,true);
$order_by   = getval("order_by","");
$sort       = getval("sort","");
$k          = getval("k","");

?>
<div class="BasicsBox">
<?php

// Load SVG source
$svg_path=get_resource_path($ref,true,"",false,"svg");
$svg_source=file_get_contents($svg_path);

?>
<div class="svgouter">
<p>
    <a href="#" onClick="ZoomSVG(10);return false;"><i class="fa fa-2x fa-search-plus"></i></a>
    <a href="#" onClick="ZoomSVG(-10);return false;"><i class="fa fa-2x fa-search-minus"></i></a>
</p>
    <div class="svg" id="svgpreview"></div>
</div>
<h1><?php echo $lang["stencilvg-go"] ?></h1>
<?php

# Fetch parameters
$e=0;$params=array();
while($s=strpos($svg_source,"[",$e))
    {
    $e=strpos($svg_source,"]",$s);
    if ($e===false) {break;}
    $params[]=substr($svg_source,$s+1,$e-$s-1);
    }
if (count($params)==0) exit("No template parameters found.");

//print_r($params);
//echo json_encode($params);

foreach ($params as $param)
    {
    ?>
    <p><?php echo htmlspecialchars($param) ?><br />
    <input type="text" class="stdwidth" name="<?php echo base64_encode($param) ?>" id="<?php echo base64_encode($param) ?>" value="<?php echo htmlspecialchars($param) ?>" onKeyUp="UpdateSVG();" onChange="onKeyUp="UpdateSVG();" />
    </p>
    <?php
    }
?>
<input type="button" onclick="PrintSVG();" value="Print" />
<input type="button" onclick="" value="Download as SVG" />
<input type="button" onclick="" value="Download as PDF" />
<input type="button" onclick="" value="Save as new resource" />


<style>
.svgouter {float:right;width:50%;}
.svg {overflow-x:auto;}
.svg svg {background-color:white;max-width:80%;width:70%;height:auto;box-shadow:0 10px 16px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);}
</style>

<script>
var svg_source=<?php echo json_encode($svg_source) ?>;
var svg_new;
var svg_zoom=70;

function UpdateSVG()
    {
    svg_new=svg_source;

    <?php
    // For each parameter, find and replace it in the SVG source.
    foreach ($params as $param) { ?>
    var value=document.getElementById('<?php echo base64_encode($param) ?>').value;
    value=value.split("__").join("<br />");
    svg_new=svg_new.split('[<?php echo $param ?>]').join(value);
    <?php } ?>
    console.log(svg_new);
    document.getElementById('svgpreview').innerHTML=svg_new;
    }

function PrintSVG()
    {
    var printsvg=window.open();printsvg.document.body.innerHTML=svg_new;
    printsvg.print();printsvg.close();
    }

function ZoomSVG(zoom)
    {
    svg_zoom+=zoom;
    if (svg_zoom>100) {svg_zoom=100;}
    jQuery("svg").css('width', svg_zoom + '%');
    }

// Start up
UpdateSVG();
</script>


</div> <!-- End of BasicsBox -->
<?php
include_once "../../../include/footer.php";
