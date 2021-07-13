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
<div class="BasicsBox"><h1><?php echo $lang["stencilvg-go"] ?></h1>
<?php

// Load SVG source
$svg_path=get_resource_path($ref,true,"",false,"svg");
$svg_source=file_get_contents($svg_path);

echo "<div class='svg' id='svgpreview'></div>";

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
    <input type="text" class="stdwidth" name="<?php echo base64_encode($param) ?>" id="<?php echo base64_encode($param) ?>" onKeyUp="UpdateSVG();" onChange="onKeyUp="UpdateSVG();" />
    </p>
    <?php
    }


?>
<style>
.svg {float:right;}
.svg svg {background-color:white;max-width:50%;max-height:600px;}
</style>

<script>
var svg_source=<?php echo json_encode($svg_source) ?>;

function UpdateSVG()
    {
    var svg_new=svg_source;

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
    UpdateSVG();
</script>


</div> <!-- End of BasicsBox -->
<?php
include_once "../../../include/footer.php";
