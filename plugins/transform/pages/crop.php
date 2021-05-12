<?php

include_once "../../../include/db.php";
include_once "../../../include/authenticate.php";
include_once "../../../include/image_processing.php";
include_once "../../../include/slideshow_functions.php";
include_once "../include/transform_functions.php";

global $cropper_allowed_extensions;

$ref        = getval("ref",0,true);
$search     = getval("search","");
$offset     = getval("offset",0,true);
$order_by   = getval("order_by","");
$sort       = getval("sort","");
$k          = getval("k","");

$reload_image   = getval('reload_image','') != '';
$reset          = getval('reset','') != '';
$saveaction         = getval("saveaction","");

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

$resource=get_resource_data($ref);
if ($resource===false || $ref <= 0)
    {    
    error_alert($lang['resourcenotfound']);
    }

if (in_array(strtoupper($resource['file_extension']), $cropper_allowed_extensions)==false) 
    {
    error_alert($lang['error_resource_not_image_extension'] . ' (' . implode(', ', $cropper_allowed_extensions) . ')');
    }

# Load edit access level
$edit_access=get_edit_access($ref);
$access=get_resource_access($ref);

$cropperestricted = in_array($usergroup,$cropper_restricteduse_groups);

//TODO
// Check if trying to replace original
// $action == "replace" && !$cropperestricted;

// Create array to hold errors
$errors = array();

$blockcrop = false;

// Check sufficient access
if ($access!=0 || ($saveaction =="replace" && !$edit_access))
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
$imgactions = array();
// Transformations must be carried out in the order the user performed them
$tfactions = getval("tfactions","");
$imgactions["tfactions"] = explode(",",$tfactions);

$imgactions["quality"] = getval("quality",100,TRUE);
$imgactions["resolution"] = getval("resolution",0,TRUE);
$imgactions["gamma"] = getval("gamma",0,TRUE);

// Generate a preview image for the operation if it doesn't already exist
$crop_pre_file = get_temp_dir(false,'') . "/transform_" . $ref . "_" . md5($username . date("Ymd",time()) . $scramble_key) . ".jpg";
$crop_pre_url = $baseurl . "/pages/download.php?tempfile=transform_" . $ref . "_" . date("Ymd",time()) . ".jpg";

$generated = generate_transform_preview($ref,$crop_pre_file, $imgactions);
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
    
$cropwidth  = $cropsizes[0];
$cropheight = $cropsizes[1];

# check that crop width and crop height are > 0
if ($cropwidth == 0 || $cropheight == 0)
    {
    error_alert($lang['error-dimension-zero']);    
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

if ($saveaction != '')
    {
    // Get values from jcrop selection
    $width       = getvalescaped('width','',true);
    $height      = getvalescaped('height','',true);
    $xcoord      = getvalescaped('xcoord','',true);
    $ycoord      = getvalescaped('ycoord','',true);
    // Get required size
    $new_width   = getvalescaped('new_width','',true);
    $new_height  = getvalescaped('new_height','',true);

    //$cropsize    = getvalescaped('cropsize','',true);
    
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

        $imgactions["crop"]        = true;
        // Get jCrop selection info
        $imgactions["xcoord"]      = $xcoord;
        $imgactions["ycoord"]      = $ycoord;
        $imgactions["height"]      = $height;
        $imgactions["width"]       = $width;
        // Required dimensions for new image
        $imgactions["new_height"]  = $new_height;
        $imgactions["new_width"]   = $new_width;
        // Pass dimensions of crop preview to allow calculations
        $imgactions["cropheight"]  = $cropheight; 
        $imgactions["cropwidth"]   = $cropwidth; 
        }

    // Determine output format
    // prefer what the user requested. If nothing, look for configured default. If nothing, use same as original
    $new_ext = getval("new_ext","");
    if ($saveaction == "slideshow")
        {
        $new_ext = "jpg";
        }
    elseif ($new_ext != "")
        {
        // is this an allowed extension?
        if (!in_array(strtoupper($new_ext),$cropper_formatarray))
            {
            $new_ext = strtolower($orig_ext);
            }
        }
    elseif (isset($cropper_default_target_format))
        {
        $new_ext = strtolower($cropper_default_target_format);
        }
    else
        {
        $new_ext = strtolower($orig_ext);
        }   

    if (($saveaction == "replace" && !$edit_access) || ($saveaction == "slideshow" && !checkperm('a')))
        {
        error_alert($lang['error-permissiondenied'], true,200);
        }

    // Redirect to terms page if necessary
    if ($saveaction =="download" && $terms_download)
        {
        $terms_accepted=getvalescaped('iaccept', '');
        if('on'!=$terms_accepted)
            {
            $crop_url = str_replace($baseurl_short, "/", $_SERVER['REQUEST_URI']);
            $urlparams["url"]=$crop_url;
            $redirect_to_terms_url=generateURL("pages/terms.php",$urlparams);
            redirect($redirect_to_terms_url);
            }
        }

    // Set path to output file
    $tmpdir = get_temp_dir();
    if(!is_dir("$tmpdir/transform_plugin"))
        {
        // If it does not exist, create it.
        mkdir("$tmpdir/transform_plugin", 0777);
        }
    
    $newpath = "$tmpdir/transform_plugin/download_" . $ref . uniqid() . "." . $new_ext;


    // Perform the actual transformation
    $transformed = transform_file($originalpath, $newpath, $imgactions);

    if($transformed)
        {
        // get final pixel dimensions of resulting file
        $newfilesize = filesize_unlimited($newpath);
        $newfiledimensions = getimagesize($newpath);
        $newfilewidth = $newfiledimensions[0];
        $newfileheight = $newfiledimensions[1];

        $filename        = getval("filename","");
        $filename        = safe_file_name($filename);
        if (trim($filename) == "")
            {
            $filename = $ref . "_" . strtolower($lang['transformed']);
            }

        if ($saveaction == "alternative" && $cropper_enable_alternative_files)
            {
            $description    = getval("description","");
            $alt_type       = getval('alt_type','');
            $mpcalc = round(($newfilewidth*$newfileheight)/1000000,1);
            $mptext = $mpcalc == 0 ? "" : " ($mpcalc " . $lang["megapixel-short"] . ")";
            
            $description .= (trim($description) == "" ? "": " - " ) . $newfilewidth . " x " . $newfileheight . " " . $lang['pixels'] . " " . $mptext;
                        
            $newfile = add_alternative_file($ref,$filename,$description,'','',0,$alt_type);
            $altpath = get_resource_path($ref, true, "", true, $new_ext, -1, 1, false, "", $newfile);
            rename($newpath,$altpath);
            resource_log($ref,'b','',"$new_ext " . strtolower($verb) . " to $newfilewidth x $newfileheight");
            create_previews($ref,false,$new_ext,false,false,$newfile);
            }
        elseif ($saveaction == "replace" && $cropper_transform_original && !$cropperestricted)
            {
            // Replace the original file
            save_original_file_as_alternative($ref);
            $success = replace_resource_file($ref,$newpath,true,false,$keep_original);
            if (!$success)
                {
                $onload_message = array("title" => $lang["error"],"text" =>str_replace("%res",$ref,$lang['error-transform-failed']));
                }
                
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
        elseif ($saveaction == "slideshow" && !$cropperestricted)
            {
            # Produce slideshow.
            $sequence = getval("sequence", 0, true);
            if ($sequence == 0)
                {
                error_alert($lang['error_slideshow_invalid']);
                }

            if(!checkperm('a'))
                {
                $onload_message = array("title" => $lang["error"],"text" =>$lang['error-permissiondenied']);
                }

            if(file_exists(dirname(__FILE__) . '/../../../' . $homeanim_folder . '/' . $sequence . '.jpg') &&
                !is_writable(dirname(__FILE__) . '/../../../' . $homeanim_folder . '/' . $sequence . '.jpg'))
                {
                error_alert(str_replace("%PATH%",realpath(dirname(__FILE__) . '/../../../' . $homeanim_folder)), $lang['error-file-permissions']);
                }

            rename($newpath,dirname(__FILE__) . "/../../../".$homeanim_folder."/" . $sequence . ".jpg");
            set_slideshow($sequence, (getval('linkslideshow', '') == 1 ? $ref : NULL));
            unlink($crop_pre_file);
            }
        elseif ($saveaction == "download")
            {
            // we are supposed to download
            # Output file, delete file and exit
            $filename .= "." . $new_ext;
            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
            header("Content-Type: application/octet-stream");

            set_time_limit(0);

            daily_stat('Resource download', $ref);
            resource_log($ref, LOG_CODE_DOWNLOADED, 0,$lang['transformimage'], '',  $lang['cropped'] . ": " . (string)$newfilewidth . "x" . (string)$newfileheight);

            readfile($newpath);
            unlink($newpath);
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

$links_trail[] = array('title' => $saveaction == "original" ? $lang['imagetoolstransformoriginal'] : $lang['imagetoolstransform']);

renderBreadcrumbs($links_trail);

?>
<p><?php
  if($cropperestricted)
      {
      echo $lang['transformblurbrestricted'];
      }
  else
      {
      echo ($saveaction == "original" ? $lang['transformblurb-original'] : $lang['transformblurb']);
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
                        //console.log("attaching cropper");
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
                clear_jcrop();
                
                function unfocus_widths(){
                    document.getElementById('new_width').blur();
                    document.getElementById('new_height').blur();
                }

                function clear_jcrop()
                    {
                    imgheight = <?php echo (int)$cropheight ?>;
                    imgwidth  = <?php echo (int)$cropwidth?>;
                    tfactions = [];
                    rotated   = false;
                    flippedx  = false;
                    flippedy  = false;
                    imgloadinprogress = false;
                    if (typeof curCoords !== "undefined")
                        {
                        delete curCoords;
                        }
                    if(typeof jcrop_active != 'undefined' && jcrop_active)
                        {
                        delete jcrop_active;
                        }
                    }
                
                function evaluate_values()
                    {
                    // do we need to redraw the cropper?
                    if (
                        (document.getElementById('new_width').value == document.getElementById('lastWidthSetting').value && document.getElementById('new_height').value == document.getElementById('lastHeightSetting').value) 
                        || (document.getElementById('lastWidthSetting').value == '' && document.getElementById('new_width').value == '') 
                        || (document.getElementById('lastHeightSetting').value == '' && document.getElementById('new_height').value == '') 
                        )
                        {
                        return true;
                        }
                    else
                        {
                        CropManager.attachCropper();
                        return true;
                        }
                    }
                
                function validate_transform(theform)
                    {
                    <?php
                    if (!$cropper_allow_scale_up) 
                        { ?>
                        if (Number(theform.new_width.value) > Number(theform.origwidth.value) || Number(theform.new_height.value) > Number(theform.origheight.value))
                            {
                            alert('<?php echo addslashes($lang['errorspecifiedbiggerthanoriginal']); ?>');
                            return false;
                            }
                        <?php
                        }?>
                    return true;
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
            lastaction = (tfactions.length > 0 ? tfactions[tfactions.length - 1] : "");

            //console.log("before load imgheight " + imgheight);
            //console.log("before load imgwidth " + imgwidth); 
            if(typeof jcrop_active != 'undefined' && jcrop_active)
                {
                // Disable cropper but record co-ordinates
                curCoords = jcrop_api.tellSelect();
                jcrop_api.destroy();
                console.log('killed jcrop');
                jcrop_active=false;
                jcropreload = true;
                }

            if(action=="reset")
                {
                tfactions = [];
                imgheight = <?php echo $origpreheight ?>;
                imgwidth = <?php echo $origprewidth ?>;
                document.imagetools_form.lastWidthSetting.value = "";
                document.imagetools_form.lastHeightSetting.value ="";
                delete curCoords;
                jcropreload = false;
                <?php
                if('' === trim($manage_slideshow_action))
                    {
                    $sswidth = isset($home_slideshow_width) ? $home_slideshow_width : 517; 
                    $ssheight = isset($home_slideshow_height) ? $home_slideshow_height : 350;
                    ?>                    
                    jQuery('#new_width').val('<?php echo (int)$sswidth; ?>');
                    jQuery('#new_height').val('<?php echo (int)$ssheight; ?>');
                    evaluate_values();
                    <?php
                    }?>
                }
            else if(action == "rotate")
                {
                console.log("last action: " + lastaction);
                // If last action was also rotation just change the value
                if(lastaction.substring(0,1) == "r")
                    {
                    lastrotation = parseInt(lastaction.substring(1));
                    newrotation = lastrotation + 90;
                    if (newrotation == 360)
                        {
                        tfactions.pop();
                        }
                    else
                        {
                        tfactions[tfactions.length - 1] = "r" + newrotation;
                        }
                    }
                else
                    {
                    tfactions.push("r90");
                    console.log("standard rotate");  
                    }
                // Set the width to height and vice-versa so that new co-ordinates can be calculated
                imgheight = jQuery('#cropimage').width();
                imgwidth  = jQuery('#cropimage').height();
                rotated=true;
                }
            else if(action == "flipx")
                {
                tfactions.push("x"); 
                imgheight = jQuery('#cropimage').height();
                imgwidth = jQuery('#cropimage').width();
                if(jcropreload)
                    {
                    flippedx = true;
                    }
                }
            else if(action == "flipy")
                {
                tfactions.push("y"); 
                imgheight = jQuery('#cropimage').height();
                imgwidth = jQuery('#cropimage').width();
                if(jcropreload)
                    {
                    flippedy = true;
                    }
                }
            // Update form input
            jQuery("#tfactions").val(tfactions.join());
            if(!imgloadinprogress)
                {
                var crop_data = {
                    ref: '<?php echo $ref; ?>',
                    reload_image: 'true',
                    gamma: jQuery('#gamma').val(),
                    tfactions: tfactions.join(),
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
                            imgloadinprogress=true;
                            jQuery('#cropimage').attr('src','<?php echo $crop_pre_url ?>&' + cropdate/1000);
                            }
                        },
                    error: function (err) {
                        console.log("AJAX error : " + JSON.stringify(err, null, 2));
                        styledalert("Unable to modify image");
                        }
                    }); 
                }
            }
        
        jQuery('#cropimage').on("load", function() 
            {
            console.log("cropimage loaded");
            if(typeof imgwidth === "undefined")
                {
                console.log("getting new size");
                imgheight = jQuery('#cropimage').height();
                imgwidth = jQuery('#cropimage').width();
                }

            console.log("afterload imgheight " + imgheight);
            console.log("afterload imgwidth " + imgwidth);
            
            // Adjust padding and image to match new size
            lpad = imgheight > imgwidth ? ((imgheight-imgwidth)/2) : 0;
            tpad = imgwidth > imgheight ? ((imgwidth-imgheight)/2) : 0;
            jQuery('#crop_imgholder').css("padding-left",lpad);
            jQuery('#crop_imgholder').css("padding-top",tpad);
            jQuery('#cropimage').height(imgheight);
            jQuery('#cropimage').width(imgwidth);

            // re-attach cropper if we have saved co-ordinates
            if (typeof jcropreload !== "undefined" && jcropreload==true && typeof curCoords !== "undefined")
                {
                // Get current preview image co-ordinates
                curx = curCoords["x"];
                cury = curCoords["y"];
                curx2 = curCoords["x2"];
                cury2 = curCoords["y2"];
                // console.log("curx: " + curx);
                // console.log("cury: " + cury);
                // console.log("curx2: " + curx2);
                // console.log("cury2 " + cury2);
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
                else if (typeof rotated !== "undefined" && rotated==true && !slideshow_edit)
                    {
                    newx = imgwidth - cury2;
                    newy = curx;
                    newx2 = imgwidth - cury;
                    newy2 = curx2;
                    }
                else if(slideshow_edit)
                    {
                    newx    = imgheight - cury2;
                    newy    = curx;
                    newx2 = imgwidth - cury;
                    newy2 = curx2;
                    if(newx2 > imgheight)
                        {
                        newx = imgheight - (cury2 -cury);
                        newx2 = imgheight;
                        }
                    if(newy2 > imgwidth)
                        {
                        newy = imgwidth - (curx2 -curx);
                        newy2 = imgwidth;
                        }
                    }

                // console.log("newx: " + newx);
                // console.log("newy: " + newy);
                // console.log("newx2: " + newx2);
                // console.log("newy2 " + newy2);
                CropManager.attachCropper();
                console.log('Re-adding selection jcrop_api.setSelect([' + newx + ',' + newy + ',' + newx2 + ',' + newy2 + ']);');
                jcrop_api.setSelect([newx,newy,newx2,newy2]);
                }
            
            imgloadinprogress=false;
            });
            

        function toggleCropper()
            {
            if(typeof jcrop_active != 'undefined' && jcrop_active)
                {
                jcrop_api.destroy();
                jcrop_active=false;
                }
            else
                {
                CropManager.attachCropper();
                }
            }

        <?php 
        if('' === trim($manage_slideshow_action))
            {?>
            slideshow_edit=false;
            jQuery(document).ready(function ()
                {
                jQuery('input[type=radio][name=saveaction]').change(function()
                    {                
                    jQuery('.imagetools_actions').hide();
                    if(this.value=='alternative')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_alternative_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        }
                    else if(this.value=='download')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_download_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        }
                    else if(this.value=='slideshow')
                        {
                        slideshow_edit=true;
                        jQuery('#imagetools_slideshow_actions').show();
                        CropManager.attachCropper();
                        jQuery('#new_width').val('<?php echo (int)$sswidth; ?>');
                        jQuery('#new_height').val('<?php echo (int)$ssheight; ?>');
                        evaluate_values();
                        }
                    else if(this.value=='original')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_original_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        }                    
                    });
                });
            <?php
            }            
        else
            {
            ?> 
            slideshow_edit=true;
            <?php
            }?>

        </script>
        <?php
        }
        ?>	

    <?php
    // Set up available actions 
    $saveactions = array("download"=>$lang["download"]);
    if ($cropper_enable_replace_slideshow && !$cropperestricted && checkperm('t') && is_writable(dirname(__FILE__)."/../../../" . $homeanim_folder)) 
        {
        $saveactions["slideshow"] = $lang['replaceslideshowimage'];
        }
    if ($cropper_enable_alternative_files && $edit_access && !$cropperestricted)
        {
        $saveactions["alternative"] = $lang['savealternative'];
        }
    if($cropper_transform_original && !$cropperestricted)
        {
        $saveactions["original"] = $lang['transform_original'];
        }

    $imagetools = array();
    if('' === trim($manage_slideshow_action))
        {
        // No need to show other options or crop link if coming from manage slideshow
        $imagetools[] = array(
            "name"      => "Save",
            "action"    => "jQuery('.imagetools_actions').hide();jQuery('#imagetools_save_actions').show();return false;",
            "icon"      => "far fa-fw fa-save",
            );
        $imagetools[] = array(
            "name"      => "Crop",
            "action"    => "toggleCropper();jQuery('.imagetools_actions').hide();jQuery('#imagetools_crop_actions').show();return false;",
            "icon"      => "fa fa-fw fa-crop",
            );
        }
    $imagetools[] = array(
        "name"      => "Reset",
        "action"    => "cropReload('reset');return false;",
        "icon"      => "fa fa-fw fa-undo",
        );
    $imagetools[] = array(
        "name"      => "Rotate",
        "action"    => "cropReload('rotate');return false;",
        "icon"      => "fa fa-fw fa-sync",
        );
    $imagetools[] = array(
        "name"      => "Flip horizontally",
        "action"    => "cropReload('flipx');return false;",
        "icon"      => "fas fa-fw fa-arrows-alt-h",
        );
    $imagetools[] = array(
        "name"      => "Flip vertically",
        "action"    => "cropReload('flipy');return false;",
        "icon"      => "fas fa-fw fa-arrows-alt-v",
        );
    $imagetools[] = array(
        "name"      => "Adjustments",
        "action"    => "jQuery('.imagetools_actions').hide();jQuery('#imagetools_corrections_actions').show();return false;",
        "icon"      => "fa fa-fw fa-sliders-h",
        );

    hook("imagetools_extra");

    ?>
    <div id="imagetool-toolbar">
        <table style="margin:auto;">
        <?php
        foreach($imagetools as $imagetool)
            {
            echo "<tr style=\"background: #fff;color:#000;\">";
            echo "<td style=\"margin-left:3px;\">";
            echo "<a href=\"#\" onclick=\"" . htmlspecialchars($imagetool["action"]) . "\" title=\"" . htmlspecialchars($imagetool["name"]) . "\">";
            echo "<span class=\"" . htmlspecialchars($imagetool["icon"]) . "\"></span>";
            echo "</a></td>";
            echo "</tr>";
            }
            ?>            
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
        <!--<input type='hidden' name='action' value='docrop' />-->
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
        <input type='hidden' name='tfactions' id='tfactions'  value='<?php echo $tfactions ?>' />

        <?php
        hook("cropafterhiddeninputs");
        ?>

        <!-- Save actions -->
        <div id="imagetools_save_actions" <?php if('' === trim($manage_slideshow_action)) { ?> style="display: none;"<?php } ?>>
            <?php
            if('' === trim($manage_slideshow_action))
                {
                render_radio_buttons_question($lang["action"],"saveaction",$saveactions,'','',true);
                }
            else
                {?>
                <input type='hidden' name='action' id='acxtion' value='slideshow' />
                <?php
                }
            ?>
            <div class="imagetools_actions"  id='imagetools_alternative_actions' style="display:none;">
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
                <div class="Question">
                    <label for="submit">&nbsp;</label>
                    <input type='submit' name='savealternative' value="<?php echo $lang['savealternative']; ?>" />
                    <div class="clearerleft"></div>
                </div>
            </div>

            <div class="imagetools_actions" id="imagetools_download_actions" style="display:none;">
                <?php
                render_dropdown_question($lang["type"],"new_ext",array_combine($cropper_formatarray , $cropper_formatarray));
                ?>
                <div class="Question">
                    <label for="submit">&nbsp;</label>
                    <input type='submit' name='download' value="<?php echo $lang["action-download"]; ?>" />
                
                    <div class="clearerleft"></div>
                </div>
               
            </div>

            <div class="imagetools_actions" id="imagetools_original_actions" style="display:none;">
                <div class="Question">
                    <label for="submit">&nbsp;</label>
                    <input type='submit' name='replace' value="<?php echo $lang['transform_original']; ?>" />
                    <div class="clearerleft"></div>
                </div>
            </div>

            <div class="imagetools_actions" id="imagetools_slideshow_actions">

                <div class="Question textcenter"><strong><?php echo $lang['transformcrophelp'] ?></strong></div>

                <div class="Question">
                    <label><?php echo  $lang["slideshowmakelink"]; ?></label>
                    <input type="checkbox" name='linkslideshow' id='linkslideshow' value="1" checked>
                    <div class="clearerleft"></div>
                </div>
                <?php
                render_text_question($lang["slideshowsequencenumber"],"sequence",'',true);
                ?>
                <div class="Question">
                    <label></label>
                    <input type="submit"
                        name="submitTransformAction"
                        value="<?php echo $lang['replaceslideshowimage']; ?>"
                        onclick="
                        if(check_cropper_selection() && validate_transform(jQuery('#dimensionsForm')))
                                {
                                CentralSpacePost(jQuery('#dimensionsForm'));
                                };
                            return false;">
                    <div class="clearerleft"></div>
                </div>
                            
                        </td>
                    </tr>
                </table>
            </div>

        </div>


        <!-- Crop actions -->
        <div class="imagetools_actions" id="imagetools_crop_actions" style="display:none;">
            
            <?php
            $onblur = "onblur=\"evaluate_values();\"";
            render_text_question($lang["width"],"new_width",$lang['px'],true, " id=\"new_width\" " . $onblur);
            render_text_question($lang["height"],"new_height",$lang['px'],true," id=\"new_height\" " .$onblur);
            ?>
        </div>

        <!-- Correction actions -->
        <div class="imagetools_actions" id="imagetools_corrections_actions" style="display:none;">
            <p>TODO: Slider here</p>

            <div class="Question">
                <label for="gamma">Gamma</label>
                <input type="range" id="gamma" name="gamma" min="0" max="100">
                <div class="clearerleft"></div>
            </div>


            <div class="Question">
                    <label for="submit">&nbsp;</label>
                    <input type='submit' name='updatepreview' onclick="cropReload();return false;" value="<?php echo $lang['transform_update_preview']; ?>" />
                    <div class="clearerleft"></div>
                </div>
        </div>

    <?php          
        
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
       
    </form>
</div> 

<?php  

    
include "../../../include/footer.php";


