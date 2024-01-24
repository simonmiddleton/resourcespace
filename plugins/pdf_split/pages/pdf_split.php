<?php
include '../../../include/db.php';
include '../../../include/authenticate.php'; 
include_once '../../../include/image_processing.php';

$ref=getval("ref","");

# Decide which size we are looking for
$size=$resource_view_use_pre?"pre":"scr";

# Original file path
$file=get_resource_path($ref,true,"",true,"pdf");

# Extract PDF file information
$pdfinfocommand = "pdfinfo " . escapeshellarg($file);
$pdfinfo = shell_exec($pdfinfocommand);

# Split into information line array
$pdfinfoarray = explode("\n", $pdfinfo);

# Extract the number of pages
$pdfpagesvaluepair = preg_grep("/\bPages\b.+/", $pdfinfoarray);
sort($pdfpagesvaluepair);

$page=0;
if(count($pdfpagesvaluepair) > 0) {
    $pdfpages = explode(":", $pdfpagesvaluepair[0]);
    if(count($pdfpages) === 2) {
        $page = trim($pdfpages[1]);
    }
}

# Split action
if (getval("method","")!="" && enforcePostRequest(false))
	{
	$ranges=getval("ranges","");
	$rs=explode(",",$ranges);

	# Original file path
	$file=get_resource_path($ref,true,"",true,"pdf");

	foreach ($rs as $r)
		{
		# For each range
		$s=explode(":",$r);
		$from=$s[0];
		$to=$s[1];

		if (getval("method","")=="alternativefile")
			{
			$aref=add_alternative_file($ref,$lang["pages"] . " " . $from . " - " . $to,"","","pdf");
			
			$copy_path=get_resource_path($ref,true,"",true,"pdf",-1,1,false,"",$aref);
			}
		else
			{
			# Create a new resource based upon the metadata/type of the current resource.
			$copy=copy_resource($ref, -1,$lang["createdfromsplittingpdf"]);
				
			# Find out the path to the original file.
			$copy_path=get_resource_path($copy,true,"",true,"pdf");
			}		
			
		# Extract this one page to a new resource.
        $ghostscript_fullpath = get_utility_path("ghostscript");
        $gscommand = $ghostscript_fullpath . " -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile=" . escapeshellarg($copy_path) . "  -dFirstPage=" . escapeshellarg($from) . " -dLastPage=" . escapeshellarg($to) . " " . escapeshellarg($file);
        $output = run_command($gscommand);


		if (getval("method","")=="alternativefile")
			{
			# Preview creation for alternative files (enabled via config)
			global $alternative_file_previews;
			if ($alternative_file_previews)
				{
				create_previews($ref,false,"pdf",false,false,$aref);
				}
			# Update size.
			ps_query("UPDATE resource_alt_files SET file_size = ? WHERE ref = ?", ['i', filesize_unlimited($copy_path), 'i', $aref]);
			}
		else
			{
			# Update the file extension
			ps_query("UPDATE resource SET file_extension ='pdf' WHERE ref = ?", ['i', $copy]);
			
			# Create preview for the page.
			create_previews($copy,false,"pdf");
			}
		}
	redirect("pages/view.php?ref=" . $ref);	
	}

include "../../../include/header.php";
   
?>

<div class="BasicsBox">
<h1><?php echo $lang["splitpdf"]?></h1>

<p><?php echo $lang["splitpdf_pleaseselectrange"]?></p>

<script>

function DrawRanges()
	{
	var ranges_html="";
	var ranges = document.getElementById("ranges").value;
	var rs=ranges.split(",");
	for (var n=0;n<rs.length;n++)
		{
		// for each range
		var range=rs[n].split(":");
		
		// draw some HTML for this range
		ranges_html += '<?php echo $lang["range"] ?> ' + (n+1) + ': <?php echo $lang["pages"] ?> <input onChange="UpdateRanges();return false;" type="text" size="8" id="range' + n + '_from" value="' + range[0] + '"> <?php echo $lang["to-page"]?> <input onChange="UpdateRanges();return false;" type="text" size="8" id="range' + n + '_to" value="' + range[1] + '">';
		// Remove page option for ranges > 1
		if (n>0) {ranges_html+='&nbsp;&nbsp;<a href="#" onClick="RemoveRange('+n+');return false;">&gt;&nbsp;<?php echo $lang["removerange"] ?></a>';}
		ranges_html+='<br/>';
		}

	document.getElementById('ranges_html').innerHTML=ranges_html;
	}

function AddRange()
	{
	document.getElementById("ranges").value+=",1:<?php echo $page ?>";
	DrawRanges();
	}

function RemoveRange(r)
	{
	var ranges = document.getElementById("ranges").value;
	var rs=ranges.split(",");
	var new_ranges="";
	for (var n=0;n<rs.length;n++)
		{
		if (n!=r)
			{
			if (new_ranges!="") {new_ranges+=",";}
			new_ranges+=rs[n];
			}
		}
	document.getElementById("ranges").value=new_ranges;
	DrawRanges();
	}

function UpdateRanges()
	{
	var ranges = document.getElementById("ranges").value;
	var rs=ranges.split(",");
	var new_ranges="";
	for (var n=0;n<rs.length;n++)
		{
		if (new_ranges!="") {new_ranges+=",";}
		
		var rfrom=parseInt(document.getElementById('range' + n + '_from').value);
		var rto=parseInt(document.getElementById('range' + n + '_to').value);		
		
		if (rfrom<1 || rfrom ><?php echo $page ?>) {alert('<?php echo $lang["outofrange"] ?>');DrawRanges();return false;}
		if (rto  <1 || rto   ><?php echo $page ?>) {alert('<?php echo $lang["outofrange"] ?>');DrawRanges();return false;}
		if (rto < rfrom) {alert('<?php echo $lang["invalidrange"] ?>');DrawRanges();return false;}
		
		new_ranges+=rfrom + ':' + rto;
		}
	document.getElementById("ranges").value=new_ranges;
	DrawRanges();
	
	}

</script>

<form method="post" action="pdf_split.php">
<?php generateFormToken("pdf_split"); ?>
<input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref); ?>">
<input type="hidden" name="ranges" id="ranges" value="<?php echo htmlspecialchars(getval("ranges","1:$page")); ?>">
<div id="ranges_html">
</div>
<p>&gt;&nbsp;<a href="#" onclick="AddRange();return false;"><?php echo htmlspecialchars($lang["addrange"]); ?></a></p>
<br />
<p>
<input type="radio" name="method" checked value="alternativefile"><?php echo htmlspecialchars($lang["splitpdf_createnewalternativefile"]); ?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="radio" name="method" value="resource"><?php echo htmlspecialchars($lang["splitpdf_createnewresource"]); ?>
</p>
<p><input type="submit" value="<?php echo htmlspecialchars($lang["splitpdf"]); ?>"></p>
</form>








</div>
<?php
include "../../../include/footer.php";
?>
<script>
DrawRanges();
</script>

<?php
