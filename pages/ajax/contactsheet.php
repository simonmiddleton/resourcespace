<?php
#
# PDF Contact Sheet Functionality
#
foreach ($_POST as $key => $value) {$$key = stripslashes(utf8_decode(trim($value)));}

// create new PDF document
include('../../include/db.php');
include_once('../../include/general.php');
include('../../include/authenticate.php');
include('../../include/search_functions.php');
include('../../include/resource_functions.php');
include_once('../../include/collections_functions.php');
include('../../include/image_processing.php');
include('../../include/pdf_functions.php');
require_once '../../lib/html2pdf/html2pdf.class.php';

$collection  = getvalescaped('c', '');
$size        = getvalescaped('size', '');
$columns     = getvalescaped('columns', '');
$order_by    = getvalescaped('orderby', 'relevance');
$sort        = getvalescaped('sort', 'asc');
$orientation = getvalescaped('orientation', '');
$sheetstyle  = getvalescaped('sheetstyle', 'thumbnails');
$preview     = ('true' == getvalescaped('preview', '') ? true : false);
$previewpage = getvalescaped('previewpage', 1);

// Check access
if(!collection_readable($collection))
    {
    exit($lang['no_access_to_collection']);
    }

// Contact sheet options:
$contactsheet_header    = ('yes' == getvalescaped('includeheader', '') ? true : $contact_sheet_include_header);
$add_contactsheet_logo  = ('true' == getvalescaped('addlogo', $include_contactsheet_logo) ? true : false);
$contact_sheet_add_link = ('true' == getvalescaped('addlink', $contact_sheet_add_link) ? true : false);



$html2pdf       = ('true' == getval('html2pdf', '') ? true : false);
$pdf_properties = array();
$resources      = array();

$collectiondata = get_collection($collection);
$user           = get_user($collectiondata['user']);
$title          = i18n_get_collection_name($collectiondata) . ' - ' . nicedate(date('Y-m-d H:i:s'), true, true);

// Get data
if(is_numeric($order_by))
    {
    $order_by = "field{$order_by}";
    }
$results = do_search("!collection{$collection}", '', $order_by, 0, -1, $sort);

switch($sheetstyle)
    {
    case 'thumbnails':
        $getfields = $config_sheetthumb_fields;
        break;

    case 'list':
        $getfields = $config_sheetlist_fields;
        break;

    case 'single':
        $getfields = $config_sheetsingle_fields;
        break;
    }

$csf = array();
foreach($getfields as $field_id)
    {
    $csf[] = get_resource_type_field($field_id);
    }


if($html2pdf)
    {
    $pdf_template_path = get_template_path("{$sheetstyle}.php", 'contact_sheet');
    $PDF_filename      = 'contactsheet.pdf';
    $placeholders      = array(
        'date'              => date('Y-m-d H:i:s'),
        'titlefontsize'     => $titlefontsize,
        'refnumberfontsize' => $refnumberfontsize,
        'title'             => $title,
        'columns'           => $columns,
    );

    if($contactsheet_header)
        {
        $placeholders['contactsheet_header'] = $contactsheet_header;
        }

    if($add_contactsheet_logo)
        {
        $placeholders['add_contactsheet_logo'] = $add_contactsheet_logo;
        $placeholders['contact_sheet_logo']    = "$baseurl/$contact_sheet_logo";
        }

    if($contact_sheet_add_link)
        {
        $placeholders['contact_sheet_add_link'] = $contact_sheet_add_link;
        }

    if($contact_sheet_footer)
        {
        $placeholders['contact_sheet_footer'] = $contact_sheet_footer;
        }

    // Set PDF properties:
    $pdf_properties['orientation'] = $orientation;
    $pdf_properties['format']      = $size;
    $pdf_properties['margins']     = array(10, 12, 10, 7);
    $pdf_properties['title']       = $title;
    $pdf_properties['author']      = $user['fullname'];
    $pdf_properties['subject']     = "{$applicationname} - {$lang['contactsheet']}";
    $pdf_properties['font']        = $contact_sheet_font;


    // 
    $imgsize = ('single' == $sheetstyle ? getvalescaped('ressize', 'lpr') : 'pre');
    if($preview)
        {
        $imgsize = 'col';
        }
    if('single' == $sheetstyle && $preview)
        {
        $imgsize = 'pre';
        }

    foreach($results as $result_data)
        {
        $placeholders['resources'][$result_data['ref']]['contact_sheet_fields'] = array();

        foreach($csf as $contact_sheet_field)
            {
            $contact_sheet_value = '';

            if(array_key_exists("field{$contact_sheet_field['ref']}", $result_data))
                {
                $contact_sheet_value = trim(get_data_by_field($result_data['ref'], $contact_sheet_field['ref']));

                // Clean fixed list types of their front comma
                if(in_array($contact_sheet_field['type'], $FIXED_LIST_FIELD_TYPES))
                    {
                    $contact_sheet_value = tidylist($contact_sheet_value);
                    }

                $placeholders['resources'][$result_data['ref']]['contact_sheet_fields'][] = tidylist($contact_sheet_value);
                }
            }
        }


    try
        {
        $html2pdf = new Html2Pdf($pdf_properties['orientation'], $pdf_properties['format'], 'en', true, 'UTF-8', $pdf_properties['margins']);

        $html2pdf->pdf->SetTitle($pdf_properties['title']);
        $html2pdf->pdf->SetAuthor($pdf_properties['author']);
        $html2pdf->pdf->SetSubject($pdf_properties['subject']);
        $html2pdf->setDefaultFont($pdf_properties['font']);

        $available_width = $html2pdf->pdf->getW() - ($html2pdf->pdf->getlMargin() + $html2pdf->pdf->getrMargin());
        $column_width    = floor($available_width / $columns) / (25.4 / 96) - 3;

        // Column width is made as "column width in mm / (25.4 / 96)"
        $placeholders['column_width'] = $column_width;

        $pdf_content = process_template($pdf_template_path, $placeholders);

        $html2pdf->writeHTML($pdf_content);
        $html2pdf->Output($PDF_filename);
        }
    catch(Html2PdfException $e)
        {
        $formatter = new ExceptionFormatter($e);

        echo $formatter->getHtmlMessage();
        }

    exit();
    }


function contact_sheet_add_fields($resourcedata)
	{
	global $pdf, $n, $getfields, $sheetstyle, $imagesize, $refnumberfontsize, $leading, $csf, $pageheight, $currentx, $currenty, $topx, $topy, $bottomx, $bottomy, $logospace, $deltay,$width,$config_sheetsingle_include_ref,$contactsheet_header,$cellsize,$ref,$pagewidth; 

	if ($sheetstyle=="single" && $config_sheetsingle_include_ref=="true"){
		$pdf->SetY($bottomy);
		$pdf->MultiCell($pagewidth-2,0,'','','L',false,1);	
		$pdf->ln();
		$pdf->MultiCell($pagewidth-2,0,$ref,'','L',false,1);	
	}


	for($ff=0; $ff<count($getfields); $ff++){
		$value="";
		$value=str_replace("'","\'", $resourcedata['field'.$getfields[$ff]]);
			
		$plugin="../../plugins/value_filter_" . $csf[$ff]['name'] . ".php";
		if ($csf[$ff]['value_filter']!=""){
			eval($csf[$ff]['value_filter']);
			}
		else if (file_exists($plugin)) {include $plugin;}
		$value=TidyList($value);
	
		if ($sheetstyle=="thumbnails") 
			{
			$pdf->Cell($imagesize,(($refnumberfontsize+$leading)/72),$value,0,2,'L',0,'',1);			
			$bottomy=$pdf->GetY();
			$bottomx=$pdf->GetX();
			}
		else if ($sheetstyle=="list")
			{
			$pdf->SetXY($pdf->GetX()+$imagesize+0.1,$pdf->GetY()+(0.2*($ff+$deltay)));
			$pdf->MultiCell($pagewidth-3,0.15,$value,0,"L");
			$pdf->SetXY($currentx,$currenty);
			}
		else if ($sheetstyle=="single")
			{
			$query = sprintf(
					   "SELECT rd.`value`, 
					           rtf.`type` AS field_type
					      FROM resource_data AS rd
					INNER JOIN resource_type_field AS rtf ON rd.resource_type_field = rtf.ref AND rtf.ref = '%s'
					     WHERE rd.resource = '%s';"
				,
				$getfields[$ff],
				$resourcedata['ref']
			);
			$raw_value = sql_query($query);

			// Default value:
			if (isset($raw_value[0])){
			$value = $raw_value[0]['value'];
			// When values have been saved using CKEditor make sure to remove html tags and decode html entitities:
			if($raw_value[0]['field_type'] == '8')
				{
				$value = strip_tags($raw_value[0]['value']);
				$value = mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
				}
			$pdf->MultiCell($pagewidth-2,0,$value,'','L',false,1);		
			}
			}
			
		}
	}
	
function contact_sheet_add_image()
	{	
	global $pdf, $imgpath, $sheetstyle, $imagesize, $pageheight, $pagewidth, $imagewidth, $imageheight, $preview_extension, $baseurl, $contact_sheet_add_link, $ref, $extralines, $refnumberfontsize, $cellsize, $topx, $topy, $bottomy, $align,$thumbsize,$logospace,$width,$contactsheet_header; 
	$nextline="";
	if ($sheetstyle=="single")
		{
		# Centre on page
		//$posx="C";
		$align="C";		
		$nextline="N";
			
			$posx=((($width-2)/2)-($cellsize[0])/2);
			if($contactsheet_header=="true"){
				$posy=1.2 + $logospace;
			} else {
				$posy=0.8 + $logospace;
			}
		}
	elseif ($sheetstyle=="list")
		{
		global $currenty;
		$posx=$pdf->GetX();
		$posy=$currenty;
		$align="";
		}
	elseif ($sheetstyle=="thumbnails")
		{
		if ($imagewidth==0)
			{$posx=$pdf->GetX()+ $cellsize[0]/2 - ($cellsize[1] * $thumbsize[0])/($thumbsize[1]*2);}
		else
			{$posx=$pdf->GetX();}
		if ($imageheight==0)
			{$posy=$pdf->GetY()+0.025 + $cellsize[1]/2 - ($cellsize[0] * $thumbsize[1])/($thumbsize[0]*2);}
		else
			{$posy=$pdf->GetY()+0.025;}
			$align="";
		}
		
	# Add the image
	if ($contact_sheet_add_link=="true")
		{$pdf->SetMargins(.7,1.2,.7);
		$imageinfo=$pdf->Image($imgpath,$posx,$posy,$imagewidth,$imageheight,$preview_extension,$baseurl. '/?r=' . $ref,$nextline,false,300,$align,false,false,0);
		$pdf->SetMargins(1,1.2,.7);
		}
	else
		{
		$pdf->Image($imgpath,$posx,$posy,$imagewidth,$imageheight,$preview_extension,'',$nextline,false,300,$align,false,false,0);
		}	
	
	$bottomy=$pdf->GetY();
	# Add spacing cell
	if ($sheetstyle=="list")
		{		
		$pdf->Cell($cellsize[0],$cellsize[1],'',0,0);    
		}
	/*else if ($sheetstyle=="single")
		{		
		$pdf->Setx($posx+$cellsize[0]);
		$pdf->Cell($cellsize[0],($bottomy-$topy)+$imagesize+.2,'',0,0);		
		}*/
	else if ($sheetstyle=="thumbnails")
		{			
		$pdf->Setx($topx);
		$pdf->Cell($cellsize[0],($bottomy-$topy)+$imagesize+.2,'',0,0);		
		}
	}

$deltay=1;
do_contactsheet_sizing_calculations();


$pdf = new rsPDF($orientation , 'in', $size, true, 'UTF-8', false); 

#Begin loop through resources, collecting Keywords too.
$i=0;
$j=0;


for ($n=0;$n<count($result);$n++){
	$ref=$result[$n]["ref"];
	$preview_extension=$result[$n]["preview_extension"];
	$resourcetitle="";
    $i++;
	$currentx=$pdf->GetX();
	$currenty=$pdf->GetY();
	if ($ref!==false){
		# Find image
		# Load access level
		
		$access=get_resource_access($result[$n]); // feed get_resource_access the resource array rather than the ref, since access is included.
		$use_watermark=check_use_watermark();
		$imgpath = get_resource_path($ref,true,$imgsize,false,$preview_extension,-1,1,$use_watermark);
		if (!file_exists($imgpath) && $preview_extension=="jpg" && $imgsize=='hpr'){$imgpath = get_resource_path($ref,true,'',false,$preview_extension,-1,1,$use_watermark);}
		if (!file_exists($imgpath) && $imgsize!='pre'){$imgpath = get_resource_path($ref,true,'pre',false,$preview_extension,-1,1,$use_watermark);}
		if (!file_exists($imgpath)){
			$imgpath="../../gfx/".get_nopreview_icon($result[$n]['resource_type'],$result[$n]['file_extension'],false,true); 
			$preview_extension=explode(".",$imgpath);
			if(count($preview_extension)>1){
				$preview_extension=trim(strtolower($preview_extension[count($preview_extension)-1]));
			} 
		}	
		if (file_exists($imgpath)){
			# cells are used for measurement purposes only
			# Two ways to size image, either by height or by width.
			$thumbsize=getimagesize($imgpath);			
			if ($thumbsize[0]>$thumbsize[1]){ ################# landscape image
				$imagewidth=$imagesize;
				$imageheight=0;
				if ($sheetstyle=="thumbnails"){
					$topy=$pdf->GetY();	$topx=$pdf->GetX();	
					if ($config_sheetthumb_include_ref){
						$pdf->Cell($imagesize,(($refnumberfontsize+$leading)/72),$ref,0,2,'L',0,'',1);
					}
					##render fields
					contact_sheet_add_fields($result[$n]);
					$bottomy=$pdf->GetY();	
					$bottomx=$pdf->GetX();	
					#Add image
					contact_sheet_add_image();
					$pdf->SetXY($topx,$topy);
					$pdf->Cell($cellsize[0],($bottomy-$topy)+$imagesize+.2,'',0,0);
					
					}				
				else if ($sheetstyle=="list")
					{					
					if ($config_sheetlist_include_ref){
					    $pdf->SetXY($currentx,$currenty);
					    $pdf->Text($pdf->GetX()+$imagesize+0.1,$pdf->GetY(),$ref);
						 $deltay=1;
						}
					$pdf->SetXY($currentx,$currenty);	
					#render fields				
					contact_sheet_add_fields($result[$n]);
					#Add image
					contact_sheet_add_image();					
					}
				else if ($sheetstyle=="single")
					{									
					#Add image
					contact_sheet_add_image();
					contact_sheet_add_fields($result[$n]);
					}
				}
					
			else
				{ # portrait
				$imagewidth=0;
				$imageheight=$imagesize;
				
				if ($sheetstyle=="thumbnails")
					{
					$topy=$pdf->GetY();	
					$topx=$pdf->GetX();	
					if ($config_sheetthumb_include_ref){
						$pdf->Cell($imagesize,(($refnumberfontsize+$leading)/72),$ref,0,2,'L',0,'',1);
						}
					##render fields
					contact_sheet_add_fields($result[$n]);
					#Add image
					contact_sheet_add_image();
					$pdf->SetXY($topx,$topy);
					$pdf->Setx($topx);
					$pdf->Cell($cellsize[0],($bottomy-$topy)+$imagesize+.2,'',0,0);
					}
				else if ($sheetstyle=="list"){
					
					if ($config_sheetlist_include_ref){
					    $pdf->SetXY($currentx,$currenty);
					    $pdf->Text($pdf->GetX()+$imagesize+0.1,$pdf->GetY()+0.2,$ref);
						$deltay=2;
					}
					$pdf->SetXY($currentx,$currenty);		
					#render fields								
					contact_sheet_add_fields($result[$n]);
					#Add image
					contact_sheet_add_image();
					
					}
				
				else if ($sheetstyle=="single"){					
					#Add image
					contact_sheet_add_image();			
					#render fields	
					
					contact_sheet_add_fields($result[$n]);			
					
					}			
				}
			$n=$n++;
			if ($i == $columns){
					
				$pdf->ln();
				$i=0;$j++;	
				if ($j > $rowsperpage || $sheetstyle=="single"){
					$j=0; 
							
							
					if ($n<count($result)-1){ //avoid making an additional page if it will be empty							
						$pdf->AddPage();
						$pdf->SetX(1);$pdf->SetY(1.2 + $logospace);
					}
				}			
			}
		}
	}
}	

#Make AJAX preview?:
	if ($preview==true && isset($imagemagick_path)) 
		{
		if (file_exists(get_temp_dir() . "/contactsheetrip.jpg")){unlink(get_temp_dir() . "/contactsheetrip.jpg");}
		if (file_exists(get_temp_dir() . "/contactsheet.jpg")){unlink(get_temp_dir() . "/contactsheet.jpg");}
		if (file_exists(get_temp_dir() . "/contactsheet.pdf")){unlink(get_temp_dir() . "/contactsheet.pdf");}
		echo ($pdf->GetPage());
		$pdf->Output(get_temp_dir() . "/contactsheet.pdf","F");
		
		# Set up  
		putenv("MAGICK_HOME=" . $imagemagick_path); 
		putenv("PATH=" . $ghostscript_path . ":" . $imagemagick_path); # Path 

        $ghostscript_fullpath = get_utility_path("ghostscript");
        $command = $ghostscript_fullpath . " -sDEVICE=jpeg -dFirstPage=$previewpage -o -r100 -dLastPage=$previewpage -sOutputFile=" . escapeshellarg(get_temp_dir() . "/contactsheetrip.jpg") . " " . escapeshellarg(get_temp_dir() . "/contactsheet.pdf") . (($config_windows)?"":" 2>&1");
		run_command($command);

        $convert_fullpath = get_utility_path("im-convert");
        if ($convert_fullpath==false) {exit("Could not find ImageMagick 'convert' utility at location '$imagemagick_path'");}

        $command = $convert_fullpath . " -resize ".$contact_sheet_preview_size." -quality 90 -colorspace ".$imagemagick_colorspace." \"".get_temp_dir() . "/contactsheetrip.jpg\" \"".get_temp_dir() . "/contactsheet.jpg\"" . (($config_windows)?"":" 2>&1");

		run_command($command);
		exit();
		}

#check configs, decide whether PDF outputs to browser or to a new resource.
if ($contact_sheet_resource==true){
	$newresource=create_resource(1,0);

	update_field($newresource,8,i18n_get_collection_name($collectiondata)." ".$date);
	update_field($newresource,$filename_field,$newresource.".pdf");

#Relate all resources in collection to the new contact sheet resource
relate_to_collection($newresource,$collection);	

	#update file extension
	sql_query("update resource set file_extension='pdf' where ref='$newresource'");
	
	# Create the file in the new resource folder:
	$path=get_resource_path($newresource,true,"",true,"pdf");
	
	$pdf->Output($path,'F');

	#Create thumbnails and redirect browser to the new contact sheet resource
	create_previews($newresource,true,"pdf");
	redirect($baseurl_short."pages/view.php?ref=" .$newresource);
	}

else{ 
	$out1 = ob_get_contents();
	if ($out1!=""){
	ob_end_clean();
	}
$pdf->Output(i18n_get_collection_name($collectiondata).".pdf","D");
}

hook("endscript");
