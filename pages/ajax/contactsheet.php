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

$collection    = getvalescaped('c', '');
$size          = getvalescaped('size', '');
$columns       = getvalescaped('columns', 1);
$order_by      = getvalescaped('orderby', 'relevance');
$sort          = getvalescaped('sort', 'asc');
$orientation   = getvalescaped('orientation', '');
$sheetstyle    = getvalescaped('sheetstyle', 'thumbnails');
$preview       = ('true' == getvalescaped('preview', '') ? true : false);
$previewpage   = getvalescaped('previewpage', 1);
$includeheader = getvalescaped('includeheader', '');
$addlink       = getvalescaped('addlink', '');

// Check access
if(!collection_readable($collection))
    {
    exit($lang['no_access_to_collection']);
    }

// Contact sheet options:
$contactsheet_header           = ('' != $includeheader ? filter_var($includeheader, FILTER_VALIDATE_BOOLEAN) : $contact_sheet_include_header);
$add_contactsheet_logo         = ('true' == getvalescaped('addlogo', $include_contactsheet_logo) ? true : false);
$contact_sheet_add_link        = ('' != $addlink ? filter_var($addlink, FILTER_VALIDATE_BOOLEAN) : $contact_sheet_add_link);
$selected_contact_sheet_fields = getvalescaped('selected_contact_sheet_fields', '');


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
$PDF_filename      = get_temp_dir() . '/contactsheet.pdf';
$placeholders      = array(
    'date'                          => date('Y-m-d H:i:s'),
    'titlefontsize'                 => $titlefontsize,
    'refnumberfontsize'             => $refnumberfontsize,
    'title'                         => $title,
    'columns'                       => $columns,
    'config_sheetthumb_include_ref' => $config_sheetthumb_include_ref,
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

            // Clean fixed list types of their front comma
            if(in_array($contact_sheet_field['type'], $FIXED_LIST_FIELD_TYPES))
                {
                $contact_sheet_value = tidylist($contact_sheet_value);
                }

            $placeholders['resources'][$result_data['ref']]['contact_sheet_fields'][$contact_sheet_field['title']] = tidylist($contact_sheet_value);
            }
        }

    // Add the preview image
    $use_watermark = check_use_watermark();
    $img_path = get_resource_path($result_data['ref'], true, $img_size, false, $result_data['preview_extension'], -1, 1, $use_watermark);
    if(!file_exists($img_path))
        {
        $img_path = "../../gfx/" . get_nopreview_icon($result_data['resource_type'], $result_data['file_extension'], false, true);
        }

    $placeholders['resources'][$result_data['ref']]['preview_src'] = str_replace($storagedir, $storageurl, $img_path);
    unset($img_path);
    }

try
    {
    $html2pdf = new Html2Pdf($pdf_properties['orientation'], $pdf_properties['format'], 'en', true, 'UTF-8', $pdf_properties['margins']);

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

// Make AJAX preview
if ($preview && isset($imagemagick_path)) 
    {
    $contact_sheet_rip = get_temp_dir() . '/contactsheetrip.jpg';
    if(file_exists($contact_sheet_rip))
        {
        unlink($contact_sheet_rip);
        }

    $contact_sheet_preview_img = get_temp_dir() . '/contactsheet.jpg';
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
    putenv("PATH={$ghostscript_path}:{$imagemagick_path}");
    $ghostscript_fullpath = get_utility_path('ghostscript');
    $convert_fullpath = get_utility_path('im-convert');
    
    if(!$convert_fullpath)
        {
        exit("Could not find ImageMagick 'convert' utility at location '{$imagemagick_path}'");
        }

    $command = "{$ghostscript_fullpath} -sDEVICE=jpeg -dFirstPage={$previewpage} -o -r100 -dLastPage={$previewpage} -sOutputFile=" . escapeshellarg($contact_sheet_rip) . ' ' . escapeshellarg($PDF_filename) . (($config_windows) ? '':' 2>&1');
    run_command($command);

    $command = "{$convert_fullpath} -resize {$contact_sheet_preview_size} -quality 90 -colorspace {$imagemagick_colorspace} \"{$contact_sheet_rip}\" \"$contact_sheet_preview_img\"" . (($config_windows) ? '' : ' 2>&1');
    run_command($command);

    exit();
    }

// Create a resource based on this PDF file or download it?
if($contact_sheet_resource)
    {
    $new_resource = create_resource(1, 0);

    update_field($new_resource, 8, i18n_get_collection_name($collectiondata) . ' ' . date('Y-m-d H:i:s'));
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