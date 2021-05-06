<?php

include_once "../../../include/db.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/image_processing.php";
include_once "../../../include/slideshow_functions.php";
include_once "../include/transform_functions.php";

global $cropper_allowed_extensions;

$ref        = getval("ref",0);
$search     = getval("search","");
$offset     = getval("offset",0,true);
$order_by   = getval("order_by","");
$sort       = getval("sort","");
$k          = getval("k","");

$reload_image   = getval('reload_image','') != '';
$reset          = getval('reset','') != '';

// Build view url for redirect
$urlparams = array(
    "ref"       =>  $ref,
    "search"    =>  $search,
    "offset"    =>  $offset,
    "order_by"  =>  $order_by,
    "sort"      =>  $sort,
    "k"         =>  $k
    );
$view_url = generateURL($baseurl_short . 'pages/view.php',$urlparams);

if(is_numeric($ref)==false) {exit($lang['error_resource_id_non_numeric']);}
$resource=get_resource_data($ref);
if ($resource===false || $ref < 0) {exit($lang['resourcenotfound']);}

if (in_array(strtoupper($resource['file_extension']), $cropper_allowed_extensions)==false) 
    {
    exit($lang['error_resource_not_image_extension'] . ' (' . implode(', ', $cropper_allowed_extensions) . ')');
    }

# Load edit access level
$edit_access=get_edit_access($ref);

# Load download access level
$access=get_resource_access($ref);

$cropperestricted=in_array($usergroup,$cropper_restricteduse_groups);

// Create array to hold errors
$errors = array();

// are they requesting to change the original?
if (isset($_REQUEST['mode']) && strtolower($_REQUEST['mode']) == 'original')
    {
    $original = true;
    }
else
    {
    $original = false;
    }

$blockcrop = false;

// Check sufficient access
if ($access!=0 || ($original && !$edit_access))
    {
    $blockcrop= true;
    $error = $lang['error-permissiondenied'];
    }
elseif(intval($user_dl_limit) > 0)
    {
    $download_limit_check = get_user_downloads($userref,$user_dl_days);
    if($download_limit_check >= $user_dl_limit)
        {
        $blockcrop= true;
        $error = $lang['download_limit_error'];
        }
    }

if($blockcrop)
    {
    if(getval("ajax","") != "")
        {
        error_alert($error, true,200); // 200 so that history works
        }
    else
        {
        include "../../../include/header.php";
        $onload_message = array("title" => $lang["error"],"text" => $error);
        include "../../../include/footer.php";    
        }
    exit();
    }


$imversion = get_imagemagick_version();

// Get the actions that have been requested
$actions = explode(",",getval("actions",""));
$actions["quality"] = getval("quality",100,TRUE);
$actions["resolution"] = getval("resolution",0,TRUE);

// if (getval('flipx',0,true) == 1 && !$cropperestricted)
//     {
//     $flipx = true;
//     }
// else
//     {
//     $flipx = false;
//     }
// if (getval('flipy',0,true) == 1 && !$cropperestricted)
//     {
//     $flipy = true;
//     }
// else
//     {
//     $flipy = false;
//     }

// $rotation = getval('rotation',0,true);
// if (($rotation < 0 || $rotation > 360) && !$cropperestricted)
//     {
//     $rotation = 0;
//     }
// generate a preview image for the operation if it doesn't already exist
$crop_pre_file = get_temp_dir(false,'') . "/transform_" . $ref . "_" . md5($username . date("Ymd",time()) . $scramble_key) . ".jpg";
$crop_pre_url = $baseurl . "/pages/download.php?tempfile=transform_" . $ref . "_" . date("Ymd",time()) . ".jpg";

//echo  "generating preview";
//exit($crop_pre_file);
// $options=array(
//     "rotation" => $rotation,
//     "flipx"     => $flipx,
//     "flipy"     => $flipy,
//     );

$generated = generate_transform_preview($ref,$crop_pre_file, $actions);
if($reload_image)
    {
    if($generated)
        {
        $response['message'] = "SUCCESS";
        }
    else
        {
        $response['message'] = $lang["transform_preview_gen_error"];            
        }
    exit(json_encode($response));            
    }
elseif(!$generated)
    {
    error_alert($lang["transform_preview_gen_error"]);
    }
# Locate imagemagick.
if (!isset($imagemagick_path))
    {
	echo "Error: ImageMagick must be configured for crop functionality. Please contact your system administrator.";
	exit;
    }

$command = get_utility_path("im-convert");
if ($command==false) {exit("Could not find ImageMagick 'convert' utility.");}

// retrieve file extensions
$orig_ext = sql_value("select file_extension value from resource where ref = '$ref'",'');
$preview_ext = sql_value("select preview_extension value from resource where ref = '$ref'",'');

// retrieve image paths for preview image and original file
$originalpath= get_resource_path($ref,true,'',false,$orig_ext);

hook('transformcropbeforegetsize');

// retrieve image sizes for original image and preview used for cropping

if(file_exists($crop_pre_file))
    {
    $cropsizes  = getimagesize($crop_pre_file);
    }
else
    {
    $errors[] = "Unable to find preview image";
    }
if(file_exists($originalpath))
    {
    $origsizes  = getimagesize($originalpath);
    }
else
    {
    $errors[] = "Unable to find original file";
    }

unset($GLOBALS["use_error_exception"]);
    
$cropwidth  = $cropsizes[0];
$cropheight = $cropsizes[1];

# check that crop width and crop height are > 0
if ($cropwidth == 0 || $cropheight == 0)
    {
    die($lang['error-dimension-zero']);    
    }
    
$origwidth  = $origsizes[0];
$origheight = $origsizes[1];

// For SVGs it is hard to determine the size (at the moment (PHP7 - 29/12/2017) getimagesize does not support it)
// Note: if getSvgSize() can extract the width and height then the crop will work as expected, otherwise the output will
// be the full size (ie. not cropped)
if($orig_ext == 'svg')
    {
    list($origwidth, $origheight) = getSvgSize($originalpath);
    }

// Get parameters from Manage slideshow page
$manage_slideshow_action = getvalescaped('manage_slideshow_action', '');
$manage_slideshow_id = getvalescaped('manage_slideshow_id', '');

$return_to_url = getvalescaped('return_to_url', '');

$terms_url = $baseurl_short."pages/terms.php?ref=".$ref;

// if we've been told to do something
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'docrop')
    {
    $width       = getvalescaped('width','',true);
    $height      = getvalescaped('height','',true);
    $xcoord      = getvalescaped('xcoord','',true);
    $ycoord      = getvalescaped('ycoord','',true);
    $description = getvalescaped('description','');
    $cropsize    = getvalescaped('cropsize','',true);
    $new_width   = getvalescaped('new_width','',true);
    $new_height  = getvalescaped('new_height','',true);
    $alt_type    = getvalescaped('alt_type','');


    if (isset($_REQUEST['filename']) && $cropper_custom_filename)
        {
        $filename = $_REQUEST['filename'];
        }
    else
        {
        $filename = '';
        }

    // verify that all crop parameters are numeric - just a precaution
    if (!is_numeric($width) || !is_numeric($height) || !is_numeric($xcoord) || !is_numeric($ycoord))
        {
        // should never happen, but if it does, could be bad news
        echo $lang['nonnumericcrop'];
        exit;
        }

    if ($cropper_debug)
        {
        error_log("origwidth: $origwidth, width: $width / origheight = $origheight, height = $height, $xcoord / $ycoord");
        }

    if (($width == 0 && $height == 0 && ($new_width > 0||$new_height > 0)) ||  $cropperestricted)
        {
        // the user did not indicate a crop. presumably they are scaling
        $verb = $lang['scaled'];
        }
else if (!$cropperestricted)
	{
	$verb = $lang['cropped'];
	// now we need to mathematically convert to the original size
	$actions["finalxcoord"] = round ((($origwidth  * $xcoord)/$cropwidth),0);
	$actions["finalycoord"] = round ((($origheight * $ycoord)/$cropheight),0);	
	
    // Ensure that new ratio of crop matches that of the specified size or we may end up missing the target size
    // If landscape crop, set the width first, then base the height on that
    $desiredratio = $width / $height;
    if($desiredratio > 1)
        {
        $finalwidth  = round ((($origwidth  * $width)/$cropwidth),0);
        $finalheight = round ($finalwidth / $desiredratio,0);
        }
    else
        {
        $finalheight = round ((($origheight * $height)/$cropheight),0);
        $finalwidth= round($finalheight *  $desiredratio,0);			
        }
    
    $actions["crop"] = true;
    $actions["finalwidth"]  = $finalwidth;
	$actions["finalheight"] = $finalheight;
    $actions["new_width"]   = $new_width;
	$actions["new_height"]  = $new_height;
    $actions["origwidth"]   = $origwidth;
	$actions["origheight"]  = $origheight;
    }

    // determine output format
    // prefer what the user requested. If nothing, look for configured default. If nothing, use same as original
    if (getval("slideshow","")!=""  && !$cropperestricted){$new_ext="jpg";}
    else if (isset($_REQUEST['new_ext']) && strlen($_REQUEST['new_ext']) == 3)
        {
        // is this an allowed extension?
        $new_ext = strtolower($_REQUEST['new_ext']);
        if (!in_array(strtoupper($new_ext),$cropper_formatarray)){
            $new_ext = strtolower($orig_ext);
        }
    } elseif (isset($cropper_default_target_format)) {
        $new_ext = strtolower($cropper_default_target_format);
    } else {
        $new_ext = strtolower($orig_ext);
    }

    if ( $cropper_custom_filename && strlen($filename) > 0){
        $mytitle = $filename;
    } else{
        $mytitle = escape_check("$verb " . str_replace("?",strtoupper($new_ext),$lang["fileoftype"]));
    }

    if (strlen($alt_type)>0){ $mytitle .= " - $alt_type"; }

    $mydesc = escape_check($description);

    # Is this a download only?
    $download=(getval("download","")!="");

    if (!$download && !$edit_access)
        {
        include "../../../include/header.php";
        echo "Permission denied.";
        include "../../../include/footer.php";
        exit;
        }

    // Redirect to terms page if necessary
    if ($download && $terms_download)
        {
        $terms_accepted=getvalescaped('iaccept', '');
        if('on'!=$terms_accepted)
            {
            $crop_url = str_replace($baseurl_short, "/", $_SERVER['REQUEST_URI']);
            $url_params["url"]=$crop_url;
            $redirect_to_terms_url=generateURL("pages/terms.php",$url_params);
            redirect($redirect_to_terms_url);
            }
        }


    if ($cropper_enable_alternative_files && !$download && !$original && getval("slideshow","")=="")
        {
        $newfile=add_alternative_file($ref,$mytitle,$mydesc,'','',0,escape_check($alt_type));
        $newpath = get_resource_path($ref, true, "", true, $new_ext, -1, 1, false, "", $newfile);
        }
    else
        {
        $tmpdir = get_temp_dir();
        if(!is_dir("$tmpdir/transform_plugin"))
            {
            // If it does not exist, create it.
            mkdir("$tmpdir/transform_plugin", 0777);
            }
        
        $newpath = "$tmpdir/transform_plugin/download_$ref." . $new_ext;
        }


    

    // Perform the actual transformation
    $transformed = transform_file($originalpath, $newpath, $actions);

    // TODO
    // if ($flip || $rotation > 0 && !$cropperestricted)
    //     {
    //     // assume we should reset exif orientation flag since they have rotated to another orientation
    //     $command .= " -orient undefined ";
    //     }


if($transformed)
    {
    // get final pixel dimensions of resulting file
    $newfilesize = filesize_unlimited($newpath);
        $newfiledimensions = getimagesize($newpath);
        $newfilewidth = $newfiledimensions[0];
        $newfileheight = $newfiledimensions[1];

        // generate previews if needed
        global $alternative_file_previews;
        if ($cropper_enable_alternative_files && $alternative_file_previews && !$download && !$original && getval("slideshow","")=="" && !$cropperestricted)
            {
            create_previews($ref,false,$new_ext,false,false,$newfile);
            }

        // strip of any extensions from the filename, since we'll provide that
        if(preg_match("/(.*)\.\w\w\w\\$/",$filename,$matches))
            {
            $filename = $matches[1];
            }

        // avoid bad characters in filenames
        $filename = preg_replace("/[^A-Za-z0-9_\- ]/",'',$filename);
        //$filename = str_replace(' ','_',trim($filename));

        // if there is not a filename, create one
        if ( $cropper_custom_filename && strlen($filename) > 0)
            {
            $filename = "$filename";
            }
        else
            {
            if (!$alternative_file_previews || $download || getval("slideshow","")!="")
                {
                $filename=$ref . "_" . strtolower($lang['transformed']);
                }
            elseif ($original && !$cropperestricted)
                {
                // fixme
                }
            else
                {
                $filename = "alt_$newfile";
                }
            }

        $filename = escape_check($filename);

        $lcext = strtolower($new_ext);

        $mpcalc = round(($newfilewidth*$newfileheight)/1000000,1);

        // don't show  a megapixel count if it rounded down to 0
        if ($mpcalc > 0)
            {
            $mptext = " ($mpcalc " . $lang["megapixel-short"] . ")";
            }
        else
            {
            $mptext = '';
            }

        if (strlen($mydesc) > 0){ $deschyphen = ' - '; } else { $deschyphen = ''; }
            
        // Do something with the final file:
        if ($cropper_enable_alternative_files && !$download && !$original && getval("slideshow","")=="" && !$cropperestricted)
            {
            // we are supposed to make an alternative
            
            // note that we will now record transformation applied to alt files for future use
            $sql  = "update resource_alt_files set file_name='{$filename}.".$lcext."',file_extension='$lcext', file_size = '$newfilesize', description = concat(description,'" . $deschyphen . $newfilewidth . " x " . $newfileheight . " " . $lang['pixels'] . " $mptext') ";
            $sql .= ", transform_scale_w=" . ($new_width>0?"'$new_width'":"null") . ", transform_scale_h=" . ($new_height>0?"'$new_height'":"null") . "";
            $sql .= ", transform_crop_w=" . ($finalwidth>0?"'$finalwidth'":"null") . ", transform_crop_h=" . ($finalheight>0?"'$finalheight'":"null") . ", transform_crop_x=" . ($finalxcoord>0?"'$finalxcoord'":"null") . ", transform_crop_y=" . ($finalycoord>0?"'$finalycoord'":"null") . "";
            $sql .= ", transform_flop=" . ($flip?"'1'":"null") . ", transform_rotation=" . ($rotation>0?"'$rotation'":"null") . "";
            $sql .= " where ref='$newfile'";

            $result = sql_query($sql);
            resource_log($ref,'b','',"$new_ext " . strtolower($verb) . " to $newfilewidth x $newfileheight");

            }
        elseif ($original && getval("slideshow","")=="" && !$cropperestricted)
            {
            // we are supposed to replace the original file

            $origalttitle = $lang['priorversion'];
            $origaltdesc = $lang['replaced'] . " " . strftime("%Y-%m-%d, %H:%M");
            $origfilename = sql_value("select value from resource_data left join resource_type_field on resource_data.resource_type_field = resource_type_field.ref where resource = '$ref' and name = 'original_filename'",$ref . "_original.$orig_ext");
            $origalt  = add_alternative_file($ref,$origalttitle,$origaltdesc);
            $origaltpath = get_resource_path($ref, true, "", true, $orig_ext, -1, 1, false, "", $origalt);
            $mporig =  round(($origwidth*$origheight)/1000000,2);
            $filesizeorig = filesize_unlimited($originalpath);
            rename($originalpath,$origaltpath);
            $result = sql_query("update resource_alt_files set file_name='{$origfilename}',file_extension='$orig_ext',file_size = '$filesizeorig' where ref='$origalt'");
            $neworigpath = get_resource_path($ref,true,'',false,$new_ext);
            rename($newpath,$neworigpath);
            $result = sql_query("update resource set file_extension = '$new_ext' where ref = '$ref' limit 1"); // update extension
            resource_log($ref,'t','','original transformed');
            create_previews($ref, false, $orig_ext, false, false, $origalt);
            create_previews($ref,false,$new_ext);

            # delete existing resource_dimensions
            sql_query("delete from resource_dimensions where resource='$ref'");
            sql_query("insert into resource_dimensions (resource, width, height, file_size) values ('$ref', '$newfilewidth', '$newfileheight', '" . (int)$newfilesize . "')");

            # call remove annotations, since they will not apply to transformed
            hook("removeannotations","",array($ref));

            hook("transformcropafterreplaceoriginal");

            if('' !== $return_to_url)
                {
                redirect($return_to_url);
                }

            redirect($view_url);
            exit;
            }
        elseif (getval("slideshow","")!="" && !$cropperestricted)
            {
            # Produce slideshow.
            $sequence = getval("sequence", "");

            if (!is_numeric($sequence)) {exit("Invalid sequence number. Please enter a numeric value.");}

            if(!checkperm('t'))
                {
                exit('Permission denied.');
                }

            if(file_exists(dirname(__FILE__) . '/../../../' . $homeanim_folder . '/' . $sequence . '.jpg') &&
                !is_writable(dirname(__FILE__) . '/../../../' . $homeanim_folder . '/' . $sequence . '.jpg'))
                {
                exit ("Unable to replace existing slideshow image. Please check file permissions or use different slideshow sequence number");
                }

            copy($newpath,dirname(__FILE__) . "/../../../".$homeanim_folder."/" . $sequence . ".jpg");
            set_slideshow($sequence, (getval('linkslideshow', '') == 1 ? $ref : NULL));

            unlink($newpath);
            unlink($crop_pre_file);
            }
        else
            {
            // we are supposed to download
            # Output file, delete file and exit
            $filename.="." . $new_ext;
            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
            header("Content-Type: application/octet-stream");

            set_time_limit(0);

            daily_stat('Resource download', $ref);
            resource_log($ref, LOG_CODE_DOWNLOADED, 0,$lang['transformimage'], '',  $lang['cropped'] . ": " . (string)$newfilewidth . "x" . (string)$newfileheight);

            readfile($newpath);
            unlink($newpath);	
            unlink($crop_pre_file);

            exit();
            }
        hook("aftercropfinish");

        // If other pages request us to go back to them rather then on the view page, do so
        if('' !== $return_to_url)
            {
            redirect($return_to_url);
            }

        // send user back to view page
        redirect($view_url);
        exit();
        }
    else
        {
        $onload_message = array("title" => $lang["error"],"text" =>str_replace("%res",$ref,$lang['error-transform-failed']));
        }
    }
else
    {

// get resource info
$resource = get_resource_data($ref);

// retrieve path to image and figure out size we're using
if ($resource["has_image"]==1)
    {
    if (!file_exists($crop_pre_file))
        {
        echo $lang['noimagefound'];
        exit;
        }
    $origpresizes = getimagesize($crop_pre_file);
    $origprewidth = $origpresizes[0];
    $origpreheight = $origpresizes[1];
    }
else
    {
	echo $lang['noimagefound'];
	exit;
    }

include "../../../include/header.php";

# slider, sound, controls

if (strpos($return_to_url, "pages/admin/admin_manage_slideshow.php") !== false)
    {
    // Arrived from Manage slideshow page
    $links_trail = array(
        array(
            'title' => $lang["systemsetup"],
            'href'  => $baseurl_short . "pages/admin/admin_home.php",
        ),
        array(
            'title' => $lang["manage_slideshow"],
            'href'  => $baseurl_short . "pages/admin/admin_manage_slideshow.php",
        )
    );
    }
else
    {
    $links_trail = array(
        array(
            'title' => $resource["field" . $view_title_field],
            'href'  => $view_url,
        )
    );
    }

$links_trail[] = array('title' => $original ? $lang['imagetoolstransformoriginal'] : $lang['imagetoolstransform']);

renderBreadcrumbs($links_trail);

?>
<p><?php
  if($cropperestricted)
      {
      echo $lang['transformblurbrestricted'];
      }
  else
      {
      echo ($original ? $lang['transformblurb-original'] : $lang['transformblurb']);
      }?>
</p>


<div id="cropdiv">
    <div id='cropimgdiv' onmouseover='unfocus_widths();' >
        
        <div id='crop_imgholder'>
            <img src="<?php echo $crop_pre_url?>" id='cropimage' />
        </div>
    </div>
    <?php
		
    if(!$cropperestricted)
        {
        ?>
        <script>
            function onEndCrop( coords )
                {
                document.imagetools_form.xcoord.value=coords.x;
                document.imagetools_form.ycoord.value=coords.y;
                document.imagetools_form.width.value=coords.w;
                document.imagetools_form.height.value=coords.h;
                }

            var jcrop_api;

                /**
                 * A little manager that allows us to reset the options dynamically
                 */
                var CropManager = {
                    /**
                     * Holds the current Cropper.Img object
                     * @var obj
                     */
                    curCrop: null,
                                            
                    /** 
                     * Attaches/resets the image cropper
                     *
                     * @access private
                     * @param obj Event object
                     * @return void
                     */
                    attachCropper: function( e ) {

                        document.imagetools_form.lastWidthSetting.value = document.getElementById('new_width').value;
                        document.imagetools_form.lastHeightSetting.value = document.getElementById('new_height').value;
                        
                        this.removeCropper();
                        this.curCrop = jQuery('#cropimage').Jcrop(
                            {
                                onRelease: onEndCrop ,
                                onChange: onEndCrop ,
                                onSelect: onEndCrop ,
                                aspectRatio: jQuery('#new_width').val()/jQuery('#new_height').val()
                            },
                            function()
                                {
                                jcrop_api = this;
                                jcrop_active=true;
                                }
                        );
        
                        if( e != null ) Event.stop( e );
                    },
                    
                    /**
                     * Removes the cropper
                     *
                     * @access public
                     * @return void
                     */
                    removeCropper: function() {
                        if( this.curCrop != null ) {
                            this.curCrop = null;
                        }
                        },
                    
                    /**
                     * Resets the cropper, either re-setting or re-applying
                     *
                     * @access public
                     * @return void
                     */
                    resetCropper: function() {
                        this.attachCropper();
                    }
                };

                // Set defaults
                imgactions = [];
                // flipx = 0;
                // flipy = 0;
                
                function unfocus_widths(){
                    document.getElementById('new_width').blur();
                    document.getElementById('new_height').blur();
                }
                
                function evaluate_values(){
                    // do we need to redraw the cropper?
                    if (
                        (document.getElementById('new_width').value == document.getElementById('lastWidthSetting').value && document.getElementById('new_height').value == document.getElementById('lastHeightSetting').value) 
                        || (document.getElementById('lastWidthSetting').value == '' && document.getElementById('new_width').value == '') 
                        || (document.getElementById('lastHeightSetting').value == '' && document.getElementById('new_height').value == '') 
                        )
                    {
                        return true;
                    } else {
                        CropManager.attachCropper();
                        return true;
                    }
                    
                
                }
                
                function validate_transform(theform)
                    {
                    // make sure that this is a reasonable transformation before we submit the form.
                    // fixme - could add more sophisticated validation here
                    <?php
                    if (!$cropper_allow_scale_up) { ?>
                        if (Number(theform.new_width.value) > Number(theform.origwidth.value) || Number(theform.new_height.value) > Number(theform.origheight.value)){
                            alert('<?php echo addslashes($lang['errorspecifiedbiggerthanoriginal']); ?>');
                            return false;
                        }
                    <?php } ?>
                    
                    return true;
                    }

        // Function used to set the information needed by the cropper and to display/ hide transform options & actions
        function replace_slideshow_set_information(replace_slideshow_checkbox)
            {
            if(replace_slideshow_checkbox.checked)
                {
                document.getElementById('new_width').value  = '<?php if(isset($home_slideshow_width)) { echo $home_slideshow_width; } else { echo "517"; } ?>';
                document.getElementById('new_height').value = '<?php if(isset($home_slideshow_height)) { echo $home_slideshow_height; } else { echo "350"; } ?>';
                
                document.getElementById('transform_options').style.display = 'none';
                document.getElementById('transform_actions').style.display = 'none';
                document.getElementById('transform_slideshow_options').style.display = 'block';
                evaluate_values();
                }
            else
                {
                document.getElementById('transform_options').style.display = 'block';
                document.getElementById('transform_actions').style.display = 'block';
                document.getElementById('transform_slideshow_options').style.display = 'none';
                }

            return;
            }

        function check_cropper_selection()
            {
            var curCoords = jcrop_api.tellSelect();
            if(curCoords.w === 0 && curCoords.h === 0)
                {
                styledalert('Warning!', 'Please select an appropriate size!');

                return false;
                }

            return true;
            }

        function cropReload(action)
            {
            console.log('cropReload');

            // Get current settings
            imgheight = jQuery('#cropimage').height();
            imgwidth = jQuery('#cropimage').width();
            jcropreload = false;
            flippedx = false;
            flippedy = false;
            rotated = false;

            console.log("before load imgheight " + imgheight);
            console.log("before load imgwidth " + imgwidth); 
            if(typeof jcrop_active != 'undefined' && jcrop_active)
                {
                // Disable cropper but record co-ordinates
                curCoords = jcrop_api.tellSelect();
                console.log(curCoords);
                jcrop_api.destroy();
                console.log('killed jcrop');
                jcrop_active=false;
                jcropreload = true;
                }

            if(action=="reset")
                {
                imgactions = [];
                //recrop = false;
                imgheight = <?php echo $origpreheight ?>;
                imgwidth = <?php echo $origprewidth ?>;
                delete curCoords;
                jcropreload = false;
                }
            else if(action == "rotate")
                {
                imgactions.push('r');            
                imgheight = jQuery('#cropimage').width();
                imgwidth = jQuery('#cropimage').height()
                console.log("new rotate imgheight " + imgheight);
                console.log("new rotate  imgwidth " + imgwidth);
                rotated=true;
                }
            else if(action == "flipx")
                {
                imgactions.push('x'); 
                imgheight = jQuery('#cropimage').height();
                imgwidth = jQuery('#cropimage').width();
                if(jcropreload)
                    {
                    flippedx = true;
                    }
                }
            else if(action == "flipy")
                {
                imgactions.push('y'); 
                imgheight = jQuery('#cropimage').height();
                imgwidth = jQuery('#cropimage').width();
                if(jcropreload)
                    {
                    flippedy = true;
                    }
                }

            var crop_data = {
                ref: '<?php echo $ref; ?>',
                reload_image: 'true',
                // rotation: imgrotation,
                // flipx: (flipx ? 1 : 0),
                // flipy: (flipy ? 1 : 0),
                actions: imgactions.join(),
                <?php echo generateAjaxToken('crop_reload'); ?>
                };
            cropdate = new Date();
            jQuery.ajax({
                type: 'POST',
                url: baseurl_short + 'plugins/transform/pages/crop.php',
                data: crop_data,
                dataType: "json",
                success: function(data) {
                    if (data.message.trim() == "SUCCESS")
                        {
                        console.log('Replacing image');
                        jQuery('#cropimage').attr('src','<?php echo $crop_pre_url ?>&' + cropdate.getTime());
                        }
                    },
                error: function (err) {
                    console.log("AJAX error : " + JSON.stringify(err, null, 2));
                    styledalert("Unable to modify image");
                    }
                }); 
            }

        
            jQuery('#cropimage').on("load", function() 
                {
                console.log("cropimage reloaded");
                if(typeof imgwidth === "undefined")
                    {       
                    imgheight = jQuery('#cropimage').height();
                    imgwidth = jQuery('#cropimage').width();
                    }

                console.log("afterload imgheight " + imgheight);
                console.log("afterload imgwidth " + imgwidth);
                
                // Adjust padding and image to match new size
                lpad = imgheight > imgwidth ? ((imgheight-imgwidth)/2) : 0;
                tpad = imgwidth > imgheight ? ((imgwidth-imgheight)/2) : 0;
                console.log("lpad " + lpad);
                console.log("tpad " + tpad);
                jQuery('#crop_imgholder').css("padding-left",lpad);
                jQuery('#crop_imgholder').css("padding-top",tpad);
                jQuery('#cropimage').height(imgheight);
                jQuery('#cropimage').width(imgwidth);

                // re-attach cropper if we had co-ordinates
                if (typeof jcropreload !== "undefined" && jcropreload==true && typeof curCoords !== "undefined")
                    {
                    // Get current preview image co-ordinates
                    curx = curCoords["x"];
                    cury = curCoords["y"];
                    curx2 = curCoords["x2"];
                    cury2 = curCoords["y2"];
                    // Transform based on action
                    if (typeof flippedx !== "undefined" && flippedx==true)
                        {
                        newx = imgwidth - curx2;
                        newy = cury;
                        newx2 = imgwidth - curx;
                        newy2 = cury2;
                        }
                    else if (typeof flippedy !== "undefined" && flippedy==true)
                        {
                        newx = curx;
                        newy = imgheight - cury2;
                        newx2 = curx2;
                        newy2 = imgheight - cury;
                        }
                    else if (typeof rotated !== "undefined" && rotated==true)
                        {
                        newx = imgwidth - cury2;
                        newy = curx;
                        newx2 = imgwidth - cury;
                        newy2 = curx2;
                        }

                    console.log('reattaching cropper');
                    CropManager.attachCropper();
                    console.log('Re-adding selection jcrop_api.setSelect([' + newx + ',' + newy + ',' + newx2 + ',' + newy2 + ']);');
                    jcrop_api.setSelect([newx,newy,newx2,newy2]);
                    }  
                })
            

        function toggleCropper()
            {
            if(typeof jcrop_active != 'undefined' && jcrop_active)
                {
                jcrop_api.destroy();
                //jcrop_api.disable();

                //CropManager.removeCropper();
                jcrop_active=false;
                }
            else
                {
                CropManager.attachCropper();
                //jcrop_api.enable();
                //CropManager.curCrop.setOptions({rotate: imgrotation});
                //jcrop_api.setOptions({
                //     rotate: imgrotation
                // });
                
                
                //rotateimg = jQuery('.jcrop-holder img');
                //padImage(rotateimg);
                }
            }
        
        </script>
        <?php
        }
        ?>	

    <?php
    // Set up available actions 
    $saveactions = array();
    if ($cropper_enable_replace_slideshow && !$cropperestricted && checkperm('t') && is_writable(dirname(__FILE__)."/../../../" . $homeanim_folder)) 
        {
        $saveactions["slideshow"] = $lang['replaceslideshowimage'];
        }
    if ($cropper_enable_alternative_files && $edit_access && !$cropperestricted)
        {
        $saveactions["alternative"] = $lang['savealternative'];
        }

    ?>



    <div id="imagetool-toolbar">
        <table style="margin:auto;">
            
        <tr style="background: #fff;color:#000;">
            <td><a href='#' onclick="cropReload('reset');return false;"><span class="fa fa-undo"></span></a></td>
            </tr>
            <?php
            if (count($saveactions) > 0)
                {?>
                <tr style="background: #fff;color:#000;">
                <td style="margin-left:3px;"><a href='#' onclick="jQuery('.imagetools_actions').hide();jQuery('#imagetools_save_actions').show();return false;"><span class="far fa-save"></span></a></td>
                </tr>
                <?php
                }?>

            <tr style="background: #fff;color:#000;">
            <td style="margin-left:3px;"><a href='#' onclick="jQuery('.imagetools_actions').hide();jQuery('#imagetools_download_actions').show();return false;"><span class="fa fa-file-download"></span></a></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><a href='#' onclick="toggleCropper();jQuery('.imagetools_actions').hide();jQuery('#imagetools_crop_actions').show();return false;"><span class="fa fa-crop"></span></a></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><span class="fa fa-image"></span></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><span class="fa fa-copy"></span></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><a href='#' onclick="cropReload('rotate');return false;"><span class="fa fa-sync"></span></a></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><a href='#' onclick="cropReload('flipx');return false;"><span class="fas fa-arrows-alt-h"></span></a></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><a href='#' onclick="cropReload('flipy');return false;"><span class="fas fa-arrows-alt-v"></span></a></td>
            </tr>
            <tr style="background: #fff;color:#000;">
            <td><a href='#' onclick="jQuery('.imagetools_actions').hide();jQuery('#imagetools_corrections_actions').show();return false;"><span class="fa fa-sliders-h"></span></a></td>
            </tr>
            
        </table>
    </div>
    <div>
    <p>
        <?php
        # MP calculation
        $mp=round(($origwidth*$origheight)/1000000,1);
        if ($mp > 0){
                $orig_mptext = "($mp  " . $lang["megapixel-short"] . ")";
        } else {
                $orig_mptext = '';
        }
        
        echo $lang['originalsize'];
        echo ": $origwidth x $origheight ";
        echo $lang['pixels'];
        echo " $orig_mptext";
        ?>
    </p>
    </div>
</div>

<div class="FloatingOptions">
        <form name='imagetools_form' id="imagetools_form" action="<?php echo $baseurl_short?>plugins/transform/pages/crop.php" onsubmit="return validate_transform(this);">
        <!-- Standard form inputs -->
        <input type='hidden' name='action' value='docrop' />
        <input type='hidden' name='xcoord' id='xcoord' value='0' />
        <input type='hidden' name='ycoord' id='ycoord' value='0' />
        <input type='hidden' name='width' id='width' value='<?php echo $origprewidth ?>' />
        <input type='hidden' name='height' id='height'  value='<?php echo $origpreheight ?>' />
        <input type='hidden' name='ref' id='ref' value='<?php echo $ref; ?>' />
        <input type='hidden' name='cropsize' id='cropsize' value='<?php echo $cropper_cropsize; ?>' />
        <input type='hidden' name='lastWidthSetting' id='lastWidthSetting' value='' />
        <input type='hidden' name='lastHeightSetting' id='lastHeightSetting' value='' />
        <input type='hidden' name='origwidth' id='origwidth'  value='<?php echo $origwidth ?>' />
        <input type='hidden' name='origheight' id='origheight'  value='<?php echo $origheight ?>' />

        <?php
        hook("cropafterhiddeninputs");
        ?>

        <!-- Save actions -->
        <div class="imagetools_actions" id="imagetools_save_actions" style="display:none;">
            <?php

            render_radio_buttons_question("","saveaction",$saveactions);

            // foreach($saveactions as $saveaction=>$actdscrp)
            //     {
            //     echo "<label for='" . $saveaction . "'>" . $actdscrp . "</label>";
            //     echo "<input type='radio' id='" . $saveaction . "' value='" . $actdscrp . "' name='" . $actdscrp . "'>";
            //     }

            ?>
            <div id='alternativeactions' style="display:none;">
                <?php
                render_text_question($lang["name"],"filename");
                render_text_question($lang["description_for_alternative_file"],"description");
                // if the system is configured to support a type selector for alt files, show it
                if (isset($alt_types) && count($alt_types) > 1)
                    {
                    echo "<tr><td style='text-align:right'>\n<label for='alt_type'>".$lang["alternatetype"].":</label></td><td colspan='3'><select name='alt_type' id='alt_type'>";
                    foreach($alt_types as $thealttype)
                        {
                        $thealttype = htmlspecialchars($thealttype,ENT_QUOTES);
                        echo "\n   <option value='$thealttype' >$thealttype</option>";
                        }
                    echo "</select>\n</td></tr>";
                    }
                else
                    {
                    echo "<input type='hidden' name='alt_type' value='' />\n";
                    }
                ?>
            </div>

            <script>
            jQuery('input[type=radio][name=saveaction]').change(function()
                {
                if(this.value=='alternative')
                    {
                    jQuery('#alternativeactions').show();
                    }
                else
                    {
                    jQuery('#alternativeactions').hide();
                    }

                });

            </script>

            <!-- Save actions -->
            <div class="imagetools_actions" id="imagetools_download_actions" style="display:none;">
                <?php
                render_dropdown_question($lang["type"],"new_ext",$cropper_formatarray,$orig_ext);
                ?>
            </div>

            <table id="transform_slideshow_options"<?php if('' === trim($manage_slideshow_action)) { ?> style="display: none;"<?php } ?>>
                <tr><td colspan="4"><p><?php echo $lang['transformcrophelp'] ?></p></td></tr>
                <tr>
                    <td style='text-align:right'><?php echo $lang["slideshowmakelink"]; ?>: </td>
                    <td><input type="checkbox" name='linkslideshow' id='linkslideshow' value="1" checked></td>
                </tr>
                <tr>
                    <td style='text-align:right'><?php echo $lang["slideshowsequencenumber"]; ?>: </td>
                    <td><input type='text' name='sequence' id='sequence' value="<?php if('' !== trim($manage_slideshow_id)) { echo $manage_slideshow_id; } ?>" size='4' /></td>
                </tr>
                <tr>
                    <td colspan="4">
                        <input type="submit"
                            name="submitTransformAction"
                            value="<?php echo $lang['replaceslideshowimage']; ?>"
                            onclick="
                            if(check_cropper_selection() && validate_transform(jQuery('#dimensionsForm')))
                                    {
                                    CentralSpacePost(jQuery('#dimensionsForm'));
                                    };

                                return false;">
                    </td>
                </tr>
            </table>
        </div>


        <!-- Crop actions -->
        <div class="imagetools_actions" id="imagetools_crop_actions" style="display:none;">
            <tr>
                <td style='text-align:right'><?php echo $lang["width"]; ?>: </td>
                <td><input type='text' name='new_width' id='new_width' value='' size='4' <?php ($cropperestricted)?"onblur='evaluate_values()'":"" ?> />
                <?php echo $lang['px']; ?></td>
                <td style='text-align:right'><?php echo $lang["height"]; ?>: </td>
                <td><input type='text' name='new_height'  id='new_height' value='' size='4'  onblur='evaluate_values()' />
                <?php echo $lang['px']; ?></td>
            </tr>        
        </div>

        <!-- Correction actions -->
        <div class="imagetools_actions" id="imagetools_corrections_actions" style="display:none;">
            <p>TODO: Slider here</p>
        </div>

<?php       
        
        if ($original && !$cropperestricted)
            {
                ?> <input type='hidden' name='mode' id='mode'  value='original' /> <?php
            }
        }
       
        if($cropper_quality_select && count($image_quality_presets) > 0)
            {?>
            <tr>
            <td style='text-align:right'><?php echo $lang['property-quality']; ?>: </td>
            <td colspan='3'>
            <select name='quality'>
                    <?php 
                    foreach ($image_quality_presets as $image_quality_preset) 
                        {
                        echo "<option value='" . htmlspecialchars($image_quality_preset) . "'>" . htmlspecialchars(isset($lang["image_quality_" . $image_quality_preset]) ? $lang["image_quality_" . $image_quality_preset] : $image_quality_preset) . "&nbsp;</option>\n";
                        }
                        ?>
            </select>
            </td>
            </tr>
            <?php
            }
        
        if (!$cropper_jpeg_rgb && $cropper_srgb_option)
            {?>
                <tr>
                    <td style='text-align:right'><?php echo $lang["cropper_use_srgb"]; ?>: </td>
                    <td><input type="checkbox" name='use_srgb' id='use_srgb' value="1" checked="checked"></td>
                </tr>
                <?php
                }
            
            if (count($cropper_resolutions)>0)
                {?>
                <tr>
                <td style='text-align:right'><?php echo $lang['cropper_resolution_select']; ?>: </td>
                <td colspan='3'>
                    <select name='resolution'>
                    <option value='' selected></option>
                        <?php 
                        foreach ($cropper_resolutions as $cropper_resolution)
                            {
                            echo "<option value='" . htmlspecialchars($cropper_resolution) . "'>" . htmlspecialchars($cropper_resolution) . "&nbsp;</option>\n";
                            }
                            ?>
                    </select>
                </td>
                </tr>
                <?php
                }
                ?>
        
        </table>
        <?php
    if ($cropper_debug)
        {
        echo "<input type='checkbox'  name='showcommand' value='1'>Debug IM Command</checkbox>";
        }
    ?>
        <p style='text-align:right;margin-top:15px;' id='transform_actions'>
        <input type='button' value="<?php echo $lang['cancel']; ?>" onclick="javascript:return CentralSpaceLoad('<?php echo $view_url ?>',true);" />
        
        <?php if ($original){ ?>
                <input type='submit' name='replace' value="<?php echo $lang['transform_original']; ?>" />
        <?php } else { ?>
            <input type='submit' name='download' value="<?php echo $lang["action-download"]; ?>" />
        
        </p>
    </form>
</div> 


   
<?php

if(!$cropperestricted && '' !== trim($manage_slideshow_action))
    {
    ?>
    <script>
    jQuery(document).ready(function() {
        if(jQuery('#slideshow').is(':checked'))
            {
            replace_slideshow_set_information(jQuery('#slideshow').get(0))
            }
    });
    </script>
    <?php
    }
    
include "../../../include/footer.php";

} // end of if action docrop
