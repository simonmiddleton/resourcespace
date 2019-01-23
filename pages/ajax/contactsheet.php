<?php
#
# PDF Contact Sheet Functionality
#
include('../../include/db.php');
include_once('../../include/general.php');
include('../../include/authenticate.php');
include('../../include/search_functions.php');
include('../../include/resource_functions.php');
include_once('../../include/collections_functions.php');
include('../../include/image_processing.php');
include('../../include/pdf_functions.php');
require_once '../../lib/html2pdf/html2pdf.class.php';

$collection        = getvalescaped('c', 0,true);
$size              = getvalescaped('size', '');
if(strpos($size,"x") !== false)
    {
    $size = explode("x",$size);
    }
$columns           = getvalescaped('columns', 1);
$order_by          = getvalescaped('orderby', 'relevance');
$sort              = getvalescaped('sort', 'asc');
$orientation       = getvalescaped('orientation', '');
$sheetstyle        = getvalescaped('sheetstyle', 'thumbnails');
$preview           = ('true' == getvalescaped('preview', '') ? true : false);
$previewpage       = getvalescaped('previewpage', 1, true);
$includeheader     = getvalescaped('includeheader', '');
$addlink           = getvalescaped('addlink', '');
$addlogo           = getvalescaped('addlogo', '');
$addfieldname	   = getvalescaped('addfieldname','');
$force_watermark   = getvalescaped('force_watermark','');
$field_value_limit = getval('field_value_limit', 0, true);

if($force_watermark==='true'){
	$force_watermark=true;
}
elseif($force_watermark==='false'){
	$force_watermark=false;
}

// Check access
if(!collection_readable($collection))
    {
    exit($lang['no_access_to_collection']);
    }

// Contact sheet options:
$contactsheet_header           = ('' != $includeheader ? filter_var($includeheader, FILTER_VALIDATE_BOOLEAN) : $contact_sheet_include_header);
$add_contactsheet_logo         = ('' != $addlogo ?  filter_var($addlogo, FILTER_VALIDATE_BOOLEAN) : $include_contactsheet_logo);
$contact_sheet_add_link        = ('' != $addlink ? filter_var($addlink, FILTER_VALIDATE_BOOLEAN) : $contact_sheet_add_link);
$contact_sheet_field_name      = ('' != $addfieldname ? filter_var($addfieldname, FILTER_VALIDATE_BOOLEAN) : false);
$selected_contact_sheet_fields = getvalescaped('selected_contact_sheet_fields', '');


$pdf_properties = array();
$resources      = array();

$collectiondata = get_collection($collection);
$user           = get_user($collectiondata['user']);
$title          = i18n_get_collection_name($collectiondata) . ' - ' . nicedate(date('Y-m-d H:i:s'), $contact_sheet_date_include_time, $contact_sheet_date_wordy);

// Get data
if(is_numeric($order_by))
    {
    $order_by = "field{$order_by}";
    }
$results = do_search("!collection{$collection}", '', $order_by, 0, -1, $sort);

if($contactsheet_use_field_templates && !isset($contactsheet_field_template))
	{
	$contactsheet_use_field_templates=false;
	}
	
if($contactsheet_use_field_templates)
	{
	$field_template = getvalescaped('field_template', 0, true);
	$getfields = $contactsheet_field_template[$field_template]['fields'];
	}
else
	{
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
	}

// If user has specified which fields to show, then respect it
if('' != $selected_contact_sheet_fields && '' != $selected_contact_sheet_fields[0])
    {
    $getfields = $selected_contact_sheet_fields;
    }

$csf = array();
foreach($getfields as $field_id)
    {
    $csf[] = get_resource_type_field($field_id);
    }

$pdf_template_path = get_template_path("{$sheetstyle}.php", 'contact_sheet');
$filename_uid	= generateUserFilenameUID($userref);
$PDF_filename	= get_temp_dir(false,'') . "/contactsheet_" . $collection . "_" . md5($username . $filename_uid . $scramble_key) . ".pdf";

$placeholders      = array(
    'date'                          			=> nicedate(date('Y-m-d H:i:s'), $contact_sheet_date_include_time, $contact_sheet_date_wordy),
    'titlefontsize'                 			=> $titlefontsize,
    'refnumberfontsize'             			=> $refnumberfontsize,
    'title'                         			=> $title,
    'columns'                       			=> $columns,
    'config_sheetthumb_include_ref' 			=> $config_sheetthumb_include_ref,
    'contact_sheet_metadata_under_thumbnail'	=> $contact_sheet_metadata_under_thumbnail,
    'contact_sheet_include_applicationname'		=> $contact_sheet_include_applicationname
);

if($contactsheet_header)
    {
    $placeholders['contactsheet_header'] = $contactsheet_header;
    }

if($add_contactsheet_logo)
    {
    $placeholders['add_contactsheet_logo'] = $add_contactsheet_logo;
    $placeholders['contact_sheet_logo']    = "$baseurl/$contact_sheet_logo";
    $placeholders['contact_sheet_logo_resize'] = $contact_sheet_logo_resize;
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
$pdf_properties['author']      = $user['fullname'];
$pdf_properties['subject']     = "{$applicationname} - {$lang['contactsheet']}";
$pdf_properties['font']        = $contact_sheet_font;
$pdf_properties['language']    = resolve_pdf_language();
if(isset($contact_sheet_custom_size_settings[$sheetstyle]["margins"]))
    {
    $pdf_properties['margins'] = $contact_sheet_custom_size_settings[$sheetstyle]["margins"];    
    }
else
    {
    $pdf_properties['margins'] = array(10, 12, 10, 7);
    }
if(isset($contact_sheet_custom_size_settings[$sheetstyle]["title"]))
    {
    $pdf_properties['title']       = $contact_sheet_custom_size_settings[$sheetstyle]["title"];    
    }
else
    {    
    $pdf_properties['title']       = $title;    
    }

// Choose the image size requirements
$img_size = ('single' == $sheetstyle ? getvalescaped('ressize', 'lpr') : 'pre');
if($preview)
    {
    $img_size = 'col';
    }
if('single' == $sheetstyle && $preview)
    {
    $img_size = 'pre';
    }

foreach($results as $result_data)
    {
    $access = get_resource_access($result_data);

    // Skip confidential resources
    if(2 == $access)
        {
        continue;
        }

    $placeholders['resources'][$result_data['ref']]['contact_sheet_fields'] = array();

    foreach($csf as $contact_sheet_field)
        {
        $contact_sheet_value = '';

        if(array_key_exists("field{$contact_sheet_field['ref']}", $result_data))
            {
            $contact_sheet_value = trim(get_data_by_field($result_data['ref'], $contact_sheet_field['ref']));

            // By default we don't limit the field but if HTML2PDF throws an error because of TD tags spreading across
            // multiple pages, then truncate the value.
            if(0 < $field_value_limit)
                {
                $contact_sheet_value = mb_substr($contact_sheet_value, 0, $field_value_limit);
                }

            // Clean fixed list types of their front comma
            if(in_array($contact_sheet_field['type'], $FIXED_LIST_FIELD_TYPES))
                {
                $contact_sheet_value = tidylist($contact_sheet_value);
                }
			$field_name='';
			if($contact_sheet_field_name)
			    {
				$field_name.=$contact_sheet_field['title'] . ': ';
			    }
            $placeholders['resources'][$result_data['ref']]['contact_sheet_fields'][$contact_sheet_field['title']] = $field_name . tidylist($contact_sheet_value);
            }
        }

    // Add the preview image
    $use_watermark = $force_watermark;
    if('' == $use_watermark)
        {
        $use_watermark = check_use_watermark();
        }

    // Determine the image path. If no file is found then do not continue.
    $img_path = get_resource_path($result_data['ref'], true, $img_size, false, $result_data['preview_extension'], -1, 1, $use_watermark);

    if(!file_exists($img_path))
        {
        $img_path = get_resource_path($result_data['ref'], true, 'lpr', false, $result_data['preview_extension'], -1, 1, $use_watermark);
        }

    if(!file_exists($img_path))
        {
        $img_path = get_resource_path($result_data['ref'], true, 'scr', false, $result_data['preview_extension'], -1, 1, $use_watermark);
        }

    // If we can't find the size, drop back to preview size
    if(!file_exists($img_path))
        {
        $img_path = get_resource_path($result_data['ref'], true, 'pre', false, $result_data['preview_extension'], -1, 1, $use_watermark);
        }

    if(!file_exists($img_path))
        {
        $img_path = "../../gfx/" . get_nopreview_icon($result_data['resource_type'], $result_data['file_extension'], false, true);
        }

    if(!file_exists($img_path))
        {
        debug("CONTACT_SHEET: could not find image path at '{$img_path}'. Skipping resource!");
        continue;
        }

    // Note: _drawImage from html2pdf.class.php supports paths. If using URLs, allow_url_fopen should be turned ON but on
    // some systems, even with allow_url_fopen On, it still couldn't load the image. Using the path seemed to have fixed
    // the issue.
    $placeholders['resources'][$result_data['ref']]['preview_src'] = $img_path;
    unset($img_path);
    }

if (!headers_sent())
    {
    // If CSRF is enabled it will break the download function unless the vary header is removed.
    header_remove('Vary');
    }

try
    {
    $html2pdf = new Html2Pdf($pdf_properties['orientation'], $pdf_properties['format'], $pdf_properties['language'], true, 'UTF-8', $pdf_properties['margins']);

    $html2pdf->pdf->SetTitle($pdf_properties['title']);
    $html2pdf->pdf->SetAuthor($pdf_properties['author']);
    $html2pdf->pdf->SetSubject($pdf_properties['subject']);
    $html2pdf->setDefaultFont($pdf_properties['font']);

    $available_width  = $html2pdf->pdf->getW() - ($html2pdf->pdf->getlMargin() + $html2pdf->pdf->getrMargin());
    $available_height = $html2pdf->pdf->getH() - ($html2pdf->pdf->gettMargin() + $html2pdf->pdf->getbMargin());
    $placeholders['available_width']  = floor($available_width / (25.4 / 96));
    $placeholders['available_height'] = floor($available_height / (25.4 / 96));

    // Column width is made as "[column width in mm] / (25.4 / 96) - [adjustment]"
    // IMPORTANT: [adjustment] is needed so that the content would be within the margins of the document
    $placeholders['column_width'] = floor(floor($available_width / $columns) / (25.4 / 96) - 10);

    $pdf_content = process_template($pdf_template_path, $placeholders);

    $html2pdf->writeHTML($pdf_content);
    }
catch(Html2PdfException $e)
    {
    $formatter = new ExceptionFormatter($e);

    echo $formatter->getHtmlMessage();

    exit();
    }
catch(Html2Pdf_exception $e)
    {
    debug('CONTACT-SHEET:' . $e->getMessage());
    debug('CONTACT-SHEET:' . $e->getTraceAsString());
	
	// Starting point
    if(0 == $field_value_limit)
        {
        $field_value_limit = 1100;
        }

    $parameters = array(
        'ref'               => $collection,
        'field_value_limit' => $field_value_limit - 100,
    );

	if(strpos($e->getMessage(),"does not fit on only one page") !== false)
		{
		$parameters["error"] = "contactsheet_data_toolong";
		}
	
    redirect(generateURL("{$baseurl}/pages/contactsheet_settings.php", $parameters));

    exit();
    }

// Make AJAX preview
if ($preview && isset($imagemagick_path)) 
    {
	$contact_sheet_rip= get_temp_dir(false,'') . "/contactsheetrip_" . $collection . "_" . md5($username . $filename_uid . $scramble_key) . ".jpg";
    if(file_exists($contact_sheet_rip))
        {
        unlink($contact_sheet_rip);
        }

	$contact_sheet_preview_img= get_temp_dir(false,'') . "/contactsheet_" . $collection . "_" . md5($username . $filename_uid . $scramble_key) . ".jpg";
    if(file_exists($contact_sheet_preview_img))
        {
        unlink($contact_sheet_preview_img);
        }

    if(file_exists($PDF_filename))
        {
        unlink($PDF_filename);
        }

    echo $html2pdf->pdf->GetPage();
    $html2pdf->Output($PDF_filename, 'F');

    // Set up
    putenv("MAGICK_HOME={$imagemagick_path}");
    $ghostscript_fullpath = get_utility_path('ghostscript');
    $convert_fullpath = get_utility_path('im-convert');
    
    if(!$convert_fullpath)
        {
        exit("Could not find ImageMagick 'convert' utility at location '{$imagemagick_path}'");
        }

    $previewpage_escaped = escapeshellarg($previewpage);
    $command = "{$ghostscript_fullpath} -sDEVICE=jpeg -dFirstPage={$previewpage_escaped} -o -r100 -dLastPage={$previewpage_escaped} -sOutputFile=" . escapeshellarg($contact_sheet_rip) . ' ' . escapeshellarg($PDF_filename) . (($config_windows) ? '':' 2>&1');
    run_command($command);

    $command = "{$convert_fullpath} -resize {$contact_sheet_preview_size} -quality 90 -colorspace {$imagemagick_colorspace} \"{$contact_sheet_rip}\" \"$contact_sheet_preview_img\"" . (($config_windows) ? '' : ' 2>&1');
    run_command($command);

    exit();
    }

// Create a resource based on this PDF file or download it?
if($contact_sheet_resource && enforcePostRequest(getval("ajax", false)))
    {
    $new_resource = create_resource($contact_sheet_resource_type, 0);

    update_field($new_resource, 8, i18n_get_collection_name($collectiondata) . ' ' . nicedate(date('Y-m-d H:i:s'), $contact_sheet_date_include_time, $contact_sheet_date_wordy));
    update_field($new_resource, $filename_field, "{$new_resource}.pdf");

    // Relate all resources in collection to the new contact sheet resource
    relate_to_collection($new_resource, $collection);	

    sql_query("UPDATE resource SET file_extension = 'pdf' WHERE ref = '{$new_resource}'");

    // Create the file in the new resource folder:
    $path = get_resource_path($new_resource, true, '', true, 'pdf');
    $html2pdf->Output($path, 'F');

    // Create thumbnails and redirect browser to the new contact sheet resource
    create_previews($new_resource, true, 'pdf');
    redirect("{$baseurl_short}pages/view.php?ref={$new_resource}");
    }

// Generate PDF file
$PDF_filename = i18n_get_collection_name($collectiondata) . '.pdf';
$html2pdf->Output($PDF_filename);

hook('endscript');
