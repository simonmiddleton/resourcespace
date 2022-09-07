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

// Test for presence of rsvg-convert and warn if not installed.
$rsvg_version=join("\n",run_external("rsvg-convert -v"));
$rsvg_installed=(strpos($rsvg_version,"version")!==false);

$resource_data=get_resource_data($ref);
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

foreach ($params as $param)
    {
    ?>
    <p><?php echo htmlspecialchars($param) ?><br />
    <input type="text" class="stdwidth" name="<?php echo base64_encode($param) ?>" id="<?php echo base64_encode($param) ?>" value="<?php echo htmlspecialchars($param) ?>" onKeyUp="UpdateSVG();" onChange="onKeyUp="UpdateSVG();" />
    </p>
    <?php
    }
?>

<p>
<input type="radio" name="filetype" value="SVG" id="filetype-svg" checked /><label for="filetype-svg">SVG</label>
<?php foreach ($stencilvg_rsvg_supported_output_formats as $format)
    {
    ?>
    <input type="radio" name="filetype" value="<?php echo $format ?>" id="filetype-<?php echo $format ?>" <?php if (!$rsvg_installed) { ?>disabled<?php } ?> /><label for="filetype-<?php echo $format ?>"><?php echo strtoupper($format) ?></label>
    <?php
    }
?>
</p>

<p>
<button  onclick="PrintSVG();">Print</button>
<button onclick="DownloadSVG(0);">Download</button>
<!--<input type="button" onclick="" value="Download as PDF" />-->
<button onclick="DownloadSVG(1);">Save as new resource</button>
</p>

<style>
.svgouter {float:right;width:50%;}
.svg {overflow-x:auto;padding:20px;}
.svg svg {background-color:white;max-width:100%;width:70%;height:auto;box-shadow:0 10px 16px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);}
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
    if (svg_zoom<10) {svg_zoom=10;}
    jQuery("svg").css('width', svg_zoom + '%');
    }

function DownloadSVG(save)
    {
    document.getElementById('downloadsvg_save').value=save;
    document.getElementById('downloadsvg_svg').value=svg_new;
    document.getElementById('downloadsvg_filename').value=<?php echo json_encode(safe_file_name($resource_data["field" . $view_title_field]) . ".svg") ?>;
    document.getElementById('downloadsvg_filetype').value=jQuery('input[name="filetype"]:checked').val();
    document.getElementById('downloadsvg').submit();
    }

// Start up
UpdateSVG();
</script>

<form id="downloadsvg" action="download_svg.php" method="post">
<?php generateFormToken("downloadsvg") ?>
<input type="hidden" id="downloadsvg_filename" name="filename" />
<input type="hidden" id="downloadsvg_svg" name="svg" />
<input type="hidden" id="downloadsvg_filetype" name="filetype" />
<input type="hidden" id="downloadsvg_save" name="save" />
</form>

<?php
// Display RSVG status.
if (!$rsvg_installed)
    {
    echo "<p>" . $lang["stencilvg-rsvg-not-installed"] . "</p>";
    }
?>

</div> <!-- End of BasicsBox -->
<?php
include_once "../../../include/footer.php";
