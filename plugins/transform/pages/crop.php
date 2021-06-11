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
$saveaction     = getval("saveaction","");

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
    exit();
    }

if (in_array(strtoupper($resource['file_extension']), $cropper_allowed_extensions)==false) 
    {
    error_alert($lang['error_resource_not_image_extension'] . ' (' . implode(', ', $cropper_allowed_extensions) . ')');
    exit();
    }

# Load edit access level
$edit_access=get_edit_access($ref);
$access     =get_resource_access($ref);

$cropperestricted = in_array($usergroup,$cropper_restricteduse_groups);

$sswidth = isset($home_slideshow_width) ? $home_slideshow_width : 517; 
$ssheight = isset($home_slideshow_height) ? $home_slideshow_height : 350;

// Create array to hold errors
$errors = array();

$blockcrop = false;

// Check sufficient access
if ($access!=0 || ($saveaction =="original" && !$edit_access))
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

// retrieve image paths for preview image and original file
$orig_ext = $resource["file_extension"];
hook('transformcropbeforegetsize');
$originalpath = get_resource_path($ref,true,'',false,$orig_ext);
if(file_exists($originalpath))
    {
    // For SVGs it is hard to determine the size (at the moment (PHP7 - 29/12/2017) getimagesize does not support it)
    // Note: if getSvgSize() can extract the width and height then the crop will work as expected, otherwise the output will
    // be the full size (ie. not cropped)
    if(strtoupper($orig_ext) == 'SVG')
        {
        list($origwidth, $origheight) = getSvgSize($originalpath);
        $usesize    = "";
        $useext     = $orig_ext;
        }
    else
        {
        $origsizes  = getimagesize($originalpath);        
        $origwidth  = $origsizes[0];
        $origheight = $origsizes[1];
        $usesize    = "scr";
        $useext     = "jpg";
        }
    }
else
    {
    $errors[] = "Unable to find original file";
    }
    
$previewsourcepath = get_resource_path($ref,true,$usesize,false,$useext);
if(!file_exists($previewsourcepath))
    {
    $previewsourcepath=get_preview_source_file($ref, $orig_ext, false, true,-1,trim($resource["file_path"]) != "");
    }

// Get the actions that have been requested
$imgactions = array();
// Transformations must be carried out in the order the user performed them
$tfactions = getval("tfactions","");
$imgactions["tfactions"] = explode(",",$tfactions);

$imgactions["quality"] = getval("quality",100,TRUE);
$imgactions["resolution"] = getval("resolution",0,TRUE);
$imgactions["gamma"] = getval("gamma",50,TRUE);
$imgactions["srgb"] = ($cropper_jpeg_rgb || ($cropper_srgb_option && getval("use_srgb","") != ""));

// Generate a preview image for the operation if it doesn't already exist
$crop_pre_file = get_temp_dir(false,'') . "/transform_" . $ref . "_" . md5($username . date("Ymd",time()) . $scramble_key) . ".jpg";
$crop_pre_url = $baseurl . "/pages/download.php?tempfile=transform_" . $ref . "_" . date("Ymd",time()) . ".jpg";

$preview_actions = $imgactions;
$preview_actions["new_width"] = 600;
$preview_actions["new_height"] = 600;
$preview_actions["preview"] = true;

$generated = transform_file($previewsourcepath,$crop_pre_file, $preview_actions);

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
    exit();
    }
    
if(file_exists($crop_pre_file))
    {
    $cropsizes  = getimagesize($crop_pre_file);
    }
else
    {
    $errors[] = "Unable to find preview image";
    }

$cropwidth  = $cropsizes[0];
$cropheight = $cropsizes[1];

# check that crop width and crop height are > 0
if ($cropwidth == 0 || $cropheight == 0)
    {
    error_alert($lang['error-dimension-zero'], false); 
    exit();   
    }


// Get parameters from Manage slideshow page
$manage_slideshow_action = getvalescaped('manage_slideshow_action', '');
$manage_slideshow_id = getvalescaped('manage_slideshow_id', '');

$return_to_url = getvalescaped('return_to_url', '');

$terms_url = $baseurl_short."pages/terms.php?ref=".$ref;

if ($saveaction != '' && enforcePostRequest(false))
    {
    $actions["repage"] = $cropper_use_repage;

    // Get values from jcrop selection
    $width       = getvalescaped('width',0,true);
    $height      = getvalescaped('height',0,true);
    $xcoord      = getvalescaped('xcoord',0,true);
    $ycoord      = getvalescaped('ycoord',0,true);
    // Get required size
    $new_width   = getvalescaped('new_width',0,true);
    $new_height  = getvalescaped('new_height',0,true);

    if ($width == 0 && $height == 0)
        {
        if (($new_width > 0 || $new_height > 0) || $cropperestricted)
            {
            // the user did not indicate a crop. presumably they are scaling
            $verb = $lang['scaled'];
            }
        elseif($new_width == 0 && $new_height == 0)
            {
            // No scaling - maybe just rotation/image tweaks
            $verb = $lang['tweaked'];
            }
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
    if ($saveaction == "slideshow" || $saveaction == "preview")
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

    if (($saveaction == "original" && !$edit_access) || ($saveaction == "slideshow" && !checkperm('a')))
        {
        error_alert($lang['error-permissiondenied'], true,200);
        exit();
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

        $name       = getval("filename","");
        $filename   = safe_file_name($name);
        if (trim($filename) == "")
            {
            $filename = $ref . "_" . strtolower($lang['transformed']);
            }
        $filename .= "." . $new_ext;

        // Use the resultant file as requested
        if ($saveaction == "alternative" && $cropper_enable_alternative_files)
            {
            $description    = getval("description","");
            $alt_type       = getval('alt_type','');
            $mpcalc = round(($newfilewidth*$newfileheight)/1000000,1);
            $mptext = $mpcalc == 0 ? "" : " ($mpcalc " . $lang["megapixel-short"] . ")";
            
            $description .= (trim($description) == "" ? "": " - " ) . $newfilewidth . " x " . $newfileheight . " " . $lang['pixels'] . " " . $mptext;
                        
            $newfile = add_alternative_file($ref,$name,$description,$filename,$new_ext,$newfilesize,$alt_type);
            $altpath = get_resource_path($ref, true, "", true, $new_ext, -1, 1, false, "", $newfile);
            rename($newpath,$altpath);
            resource_log($ref,'b','',"$new_ext " . strtolower($verb) . " to $newfilewidth x $newfileheight");
            create_previews($ref,false,$new_ext,false,false,$newfile);
            redirect($view_url);
            exit();
            }
        elseif ($saveaction == "original" && $cropper_transform_original && $editaccess && !$cropperestricted)
            {
            // Replace the original file
            $keep_original = getval("keep_original", "") != "";
            $success = replace_resource_file($ref,$newpath,true,false,$keep_original);
            if (!$success)
                {
                $onload_message = array("title" => $lang["error"],"text" =>str_replace("%res",$ref,$lang['error-transform-failed']));
                }
            hook("transformcropafterreplaceoriginal");

            if('' !== $return_to_url)
                {
                redirect($return_to_url);
                }

            redirect($view_url);
            exit();
            }
        elseif ($saveaction == "slideshow" && !$cropperestricted && checkperm('a'))
            {
            # Produce slideshow.
            $sequence = getval("sequence", 0, true);
            if ($sequence == 0)
                {
                error_alert($lang['error_slideshow_invalid'], false);
                exit();
                }

            if(!checkperm('a'))
                {
                $onload_message = array("title" => $lang["error"],"text" =>$lang['error-permissiondenied']);
                }

            if(file_exists(dirname(__FILE__) . '/../../../' . $homeanim_folder . '/' . $sequence . '.jpg') &&
                !is_writable(dirname(__FILE__) . '/../../../' . $homeanim_folder . '/' . $sequence . '.jpg'))
                {
                error_alert(str_replace("%PATH%",realpath(dirname(__FILE__) . '/../../../' . $homeanim_folder)), $lang['error-file-permissions']);
                exit();
                }
            rename($newpath,dirname(__FILE__) . "/../../../".$homeanim_folder."/" . $sequence . ".jpg");
            set_slideshow($sequence, (getval('linkslideshow', '') == 1 ? $ref : NULL));
            unlink($crop_pre_file);
            redirect($baseurl_short . "pages/home.php");
            exit();
            }
        elseif ($saveaction == "download")
            {
            // we are supposed to download
            # Output file, delete file and exit
            // Redirect to terms page if necessary
            if ($terms_download)
                {
                $terms_accepted=getvalescaped('iaccept', '');
                if('on'!=$terms_accepted)
                    {
                    $crop_url = str_replace($baseurl_short, "/", $_SERVER['REQUEST_URI']);
                    $url_params["url"]=$crop_url;
                    $redirect_to_terms_url=generateURL("pages/terms.php",$url_params);
                    redirect($redirect_to_terms_url);
                    exit();
                    }
                }

            // Download file
            $filesize=filesize_unlimited($newpath);
            ob_flush();

            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
            header("Content-Length: " . $filesize);
            set_time_limit(0);

            $sent = 0;
            $handle = fopen($newpath, "r");

            // Now we need to loop through the file and echo out chunks of file data
            while($sent < $filesize)
                {
                echo fread($handle, $download_chunk_size);
                ob_flush();
                $sent += $download_chunk_size;
                }
            #Delete File:
            unlink($newpath);

            exit();
            }
        elseif ($saveaction == "preview")
            {
            $pretmp = get_resource_path($ref,true,"tmp",true,"jpg");
            $result = rename($newpath, $pretmp);
            # Create previews
            create_previews($ref,false,"jpg",true);
            redirect($view_url);
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
        error_alert($lang['noimagefound']);
        exit();
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
            'title' => $lang["backtoview"] . " #" . $ref,
            'href'  => $view_url,
        )
    );
    }

$links_trail[] = array('title' => $saveaction == "original" ? $lang['imagetoolstransformoriginal'] : $lang['imagetoolstransform'],   
    'help'  => "plugins/transform",
    );

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
            
            function unfocus_widths()
                {
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
            if(typeof jcrop_active != 'undefined' && jcrop_active)
                {
                var curCoords = jcrop_api.tellSelect();
                if(curCoords.w === 0 && curCoords.h === 0)
                    {
                    styledalert('<?php echo $lang['error'] ?>','<?php echo $lang['error_crop_invalid'] ?>');
                    return false;
                    }
                }

            return true;
            }

        function postCrop(download=false)
            {
            cropform = document.getElementById('imagetools_form');
            if(check_cropper_selection() && validate_transform(cropform))
                {
                if(download)
                    {
                    console.log("submitting");
                    cropform.submit();
                    }
                else
                    {
                    console.log("CentralSpacePost");
                    return CentralSpacePost(cropform,false);
                    }
                }
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

            console.log("before load imgheight " + imgheight);
            console.log("before load imgwidth " + imgwidth); 
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
                document.imagetools_form.gamma.value = "50";
                jcropreload = cropper_always;
                console.log("jcropreload " + jcropreload);
                jQuery('#croptools').hide();
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
            var crop_data = {
                ref: '<?php echo $ref; ?>',
                reload_image: 'true',
                gamma: jQuery('#gamma').val(),
                tfactions: tfactions.join(),
                ajax: true,
                <?php echo generateAjaxToken('crop_reload'); ?>
                };
            cropdate = new Date();
            CentralSpaceShowLoading();
            jQuery.ajax({
                type: 'POST',
                url: baseurl_short + 'plugins/transform/pages/crop.php',
                data: crop_data,
                dataType: "json",
                success: function(data) {
                    if (data.message.trim() == "SUCCESS")
                        {
                        console.log('Replacing image');
                        jQuery('#cropimage').attr('src','<?php echo $crop_pre_url ?>&' + cropdate/1000);
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
            CentralSpaceHideLoading();
            console.log("cropimage loaded");
            if(typeof imgwidth === "undefined")
                {
                console.log("getting new size");
                imgheight = jQuery('#cropimage').height();
                imgwidth = jQuery('#cropimage').width();
                }

            // console.log("afterload imgheight " + imgheight);
            // console.log("afterload imgwidth " + imgwidth);
            
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
                    if(!slideshow_edit)
                        {
                        newx = imgwidth - cury2;
                        newy = curx;
                        newx2 = imgwidth - cury;
                        newy2 = curx2;
                        neww = jQuery('#new_width').val();
                        newh = jQuery('#new_height').val();
                        jQuery('#new_width').val(newh);
                        jQuery('#new_height').val(neww);
                        }
                    else if(slideshow_edit)
                        {
                        curw = curx2 - curx;
                        curh = cury2 - cury;
                        newx = 0;
                        newy = 0;
                        newx2 = curw;
                        newy2 = curh;
                        if(newx2 > imgheight)
                            {
                            newx2 = imgheight;
                            }
                        if(newy2 > imgwidth)
                            {
                            newy = imgwidth - curheight;
                            }
                        }
                    }
                else
                    {
                    // Same as before
                    newx = curx;
                    newy = cury;
                    newx2 = curx2;
                    newy2 = cury2;
                    }

                CropManager.attachCropper();
                console.log('Re-adding selection jcrop_api.setSelect([' + newx + ',' + newy + ',' + newx2 + ',' + newy2 + ']);');
                jcrop_api.setSelect([newx,newy,newx2,newy2]);
                }            
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

        function setCropperSize(sizestring)
            {
            cropdims = sizestring.split("x");
            jQuery('#new_width').val(cropdims[0]);
            jQuery('#new_height').val(cropdims[1]);
            evaluate_values();
            }

        <?php 
        if('' === trim($manage_slideshow_action))
            {?>
            slideshow_edit  = false;
            cropper_always  = false;
            jQuery(document).ready(function ()
                {
                jQuery('input[type=radio][name=saveaction]').change(function()
                    {                
                    jQuery('.imagetools_save_action').hide();
                    if(this.value=='alternative')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_alternative_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        cropper_always=false;
                        }
                    else if(this.value=='download')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_download_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        cropper_always=false;
                        }
                    else if(this.value=='slideshow')
                        {
                        slideshow_edit=true;
                        jQuery('#imagetools_slideshow_actions').show();
                        jQuery('#new_width').val('<?php echo (int)$sswidth; ?>');
                        jQuery('#new_height').val('<?php echo (int)$ssheight; ?>');
                        if(typeof jcrop_active == 'undefined' || !jcrop_active)
                            {
                            CropManager.attachCropper();
                            }
                        evaluate_values();
                        cropper_always=true;
                        }
                    else if(this.value=='original')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_original_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        cropper_always=false;
                        }
                    else if(this.value=='preview')
                        {
                        slideshow_edit=false;
                        jQuery('#imagetools_preview_actions').show();
                        jQuery('#new_width').val('');
                        jQuery('#new_height').val('');
                        evaluate_values();
                        cropper_always=false;
                        }
                    });
                });
            <?php
            }
        else
            {
            ?> 
            jQuery('#new_width').val('<?php echo (int)$sswidth; ?>');
            jQuery('#new_height').val('<?php echo (int)$ssheight; ?>');
            CropManager.attachCropper();
            cropper_always=true;
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
    if($edit_access)
        {
        $saveactions["preview"] = $lang['useaspreviewimage'];
        }

    $imagetools = array();
    if('' === trim($manage_slideshow_action))
        {
        $imagetools[] = array(
            "name"      => "Crop",
            "action"    => "toggleCropper();jQuery('.imagetools_actions').hide();jQuery('#croptools').show();jQuery('#imagetools_crop_actions').show();return false;",
            "icon"      => "fa fa-fw fa-crop",
            );
        }
    $imagetools[] = array(
        "name"      => "Reset",
        "action"    => "cropReload('reset');return false;",
        "icon"      => "fa fa-fw fa-history",
        );
    if($cropper_rotation)
        {
        $imagetools[] = array(
            "name"      => "Rotate",
            "action"    => "cropReload('rotate');return false;",
            "icon"      => "fa fa-fw fa-rotate-right",
            );
        }
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
        "action"    => "jQuery('.imagetools_actions').hide();jQuery('#croptools').show();jQuery('#imagetools_corrections_actions').show();return false;",
        "icon"      => "fa fa-fw fa-sliders-h",
        );

    hook("imagetools_extra");
    
    ?>
    <div id="imagetool-toolbar">
        <table style="margin:auto;">
        <?php
        foreach($imagetools as $imagetool)
            {
            echo "<tr class='toolbar-icon'>";
            echo "<td>";
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
        
        echo $lang['originalsize'] . ": ";
        echo htmlspecialchars($origwidth) . "x" . htmlspecialchars($origheight);
        echo "&nbsp;" . $lang['pixels'] . " $orig_mptext";
        ?>
    </p>
    </div>
</div>


<form name='imagetools_form' id="imagetools_form" method="POST" action="<?php echo $baseurl_short?>plugins/transform/pages/crop.php" onsubmit="return validate_transform(this);">
        <!-- Standard form inputs -->
        <input type='hidden' name='xcoord' id='xcoord' value='0' />
        <input type='hidden' name='ycoord' id='ycoord' value='0' />
        <input type='hidden' name='width' id='width' value='' />
        <input type='hidden' name='height' id='height'  value='' />
        <input type='hidden' name='ref' id='ref' value='<?php echo $ref; ?>' />
        <input type='hidden' name='cropsize' id='cropsize' value='<?php echo $cropper_cropsize; ?>' />
        <input type='hidden' name='lastWidthSetting' id='lastWidthSetting' value='' />
        <input type='hidden' name='lastHeightSetting' id='lastHeightSetting' value='' />
        <input type='hidden' name='origwidth' id='origwidth'  value='<?php echo $origwidth ?>' />
        <input type='hidden' name='origheight' id='origheight'  value='<?php echo $origheight ?>' />
        <input type='hidden' name='tfactions' id='tfactions'  value='<?php echo $tfactions ?>' />
        <?php echo generateFormToken("imagetools_form"); ?>

    <div class="FloatingOptions">
        <?php
        hook("cropafterhiddeninputs");
        ?>

        <!-- Save actions -->
        <div id="imagetools_save_actions">
            <?php
            if('' === trim($manage_slideshow_action))
                {
                render_radio_buttons_question($lang["action"],"saveaction",$saveactions,'download','',true);
                }
            else
                {?>
                <input type='hidden' name='saveaction' id='saveaction_slideshow' value='slideshow' />
                <?php
                }

            if($cropper_enable_alternative_files)
                {
                ?>
                <div class="imagetools_save_action"  id='imagetools_alternative_actions' style="display:none;">
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
                    <div class="QuestionSubmit">
                        <label for="submit">&nbsp;</label>
                        <input type='submit' name='savealternative' value="<?php echo $lang['savealternative']; ?>"  onclick="postCrop();return false;" />
                        <div class="clearerleft"></div>
                    </div>
                </div>
                <?php
                }
                ?>

            <div class="imagetools_save_action" id="imagetools_download_actions" <?php if('' !== trim($manage_slideshow_action)) { ?> style="display: none;"<?php } ?>>
                <?php
                render_dropdown_question($lang["format"],"new_ext",array_combine($cropper_formatarray , $cropper_formatarray),strtoupper($orig_ext));
                ?>
                <div class="QuestionSubmit">
                    <label for="submit">&nbsp;</label>
                    <input type='submit' name='download' value="<?php echo $lang["action-download"]; ?>" onclick="postCrop(true);return false;" />
                
                    <div class="clearerleft"></div>
                </div>
               
            </div>
            <?php
            if($cropper_transform_original)
                {?>
                <div class="imagetools_save_action" id="imagetools_original_actions" style="display:none;">
                    <div class="Question">
                        <label for="keep_original"><?php echo $lang["replace_resource_preserve_original"]; ?></label>
                        <input type='checkbox' name='keep_original' value="1" checked />
                        <div class="clearerleft"></div>
                    </div>
                    <div class="Question">
                        <label for="submit">&nbsp;</label>
                        <input type='submit' name='replace' value="<?php echo $lang['transform_original']; ?>"  onclick="postCrop();return false;" />
                        <div class="clearerleft"></div>
                    </div>
                </div>
                <?php
                }?>

            <div class="imagetools_save_action" id="imagetools_preview_actions" style="display:none;">
                <div class="QuestionSubmit">
                    <label for="submit">&nbsp;</label>
                    <input type='submit' name='preview' value="<?php echo $lang['useaspreviewimage']; ?>"   onclick="postCrop();return false;"/>
                    <div class="clearerleft"></div>
                </div>
            </div>

            <div class="imagetools_save_action" id="imagetools_slideshow_actions" <?php if('' === trim($manage_slideshow_action)) { ?> style="display: none;"<?php } ?>>

                <div class="Question textcenter"><strong><?php echo $lang['transformcrophelp'] ?></strong></div>

                <div class="Question">
                    <label><?php echo  $lang["slideshowmakelink"]; ?></label>
                    <input type="checkbox" name='linkslideshow' id='linkslideshow' value="1" checked>
                    <div class="clearerleft"></div>
                </div>
                <?php
                render_text_question($lang["slideshowsequencenumber"],"sequence",'',true,'',$manage_slideshow_id);
                ?>
                <div class="QuestionSubmit">
                    <label></label>
                    <input type="submit"
                        name="submitTransformAction"
                        value="<?php echo $lang['replaceslideshowimage']; ?>" onclick="postCrop();return false;" >
                    <div class="clearerleft"></div>
                </div>
            </div>

        </div>

        <div id="croptools" class="toolbox" style="display:none;">
        <!-- Crop actions -->
        <div class="imagetools_actions" id="imagetools_crop_actions" style="display:none;">
            
            <div class="Question">
                <label for="new_width"><?php echo $lang["width"]; ?></label>
                <input type="number" class="stdwidth" id="new_width" name="new_width" onblur="evaluate_values();">
                <?php echo $lang['px'] ;?>
                <div class="clearerleft"></div>
            </div>
            <div class="QuestionSubmit">
                <label for="new_height"><?php echo $lang["height"]; ?></label>
                <input type="number" class="stdwidth" id="new_height" name="new_height" onblur="evaluate_values();">
                <?php echo $lang['px'] ;?>
                <div class="clearerleft"></div>
            </div>

            <div class="Question">
                <label for="preset"><?php echo $lang["transform_preset_sizes"]; ?></label>
                <select class="stdwidth" onchange="setCropperSize(this.value);" id="size_preset_select">
                    <option value=""><?php echo $lang["select"]?></option>
                    <?php
                    foreach($cropper_preset_sizes as $category=>$categorysizes)
                            {
                            echo "<optgroup label='" . htmlspecialchars($category) . "'>\n";
                            foreach($categorysizes as $description=>$size)
                                {
                                echo "<option value='" . htmlspecialchars($size)  . "'>" .htmlspecialchars($description) . "</option>\n";
                                }

                            echo "</optgroup>";
                            }
                    
                    ?>
                </select>
                <div class="clearerleft"></div>
            </div>
        </div>

        <!-- Correction actions -->
        <div class="imagetools_actions" id="imagetools_corrections_actions" style="display:none;">
            <div class="Question">
                <label for="gamma">Gamma</label>
                <input type="range" class="stdwidth" id="gamma" name="gamma" min="0" max="100">
                <div class="clearerleft"></div>
            </div>

            <?php
            if($cropper_quality_select && count($image_quality_presets) > 0)
                {?>
                <div class="Question">
                    <label for="quality"><?php echo $lang['property-quality']; ?></label>
                    <select name='quality'  class="stdwidth" >
                    <?php 
                    foreach ($image_quality_presets as $image_quality_preset) 
                        {
                        echo "<option value='" . htmlspecialchars($image_quality_preset) . "'>" . htmlspecialchars(isset($lang["image_quality_" . $image_quality_preset]) ? $lang["image_quality_" . $image_quality_preset] : $image_quality_preset) . "&nbsp;</option>\n";
                        }
                        ?>
                    </select>
                    <div class="clearerleft"></div>
                </div>
                <?php
                }        
            if (!$cropper_jpeg_rgb && $cropper_srgb_option)
                {?>
                <div class="Question">
                    <label for="use_srgb"><?php echo $lang["cropper_use_srgb"]; ?>:</label>
                    <input type="checkbox" id="use_srgb" name="use_srgb" val="1" checked>
                    <div class="clearerleft"></div>
                </div>        
                <?php
                }
                
            if (count($cropper_resolutions)>0)
                {?>
                <div class="Question">
                    <label for="resolution"><?php echo $lang['cropper_resolution_select']; ?></label>
                    <select name='resolution' class="stdwidth" >
                    <option value='' selected></option>
                    <?php 
                    foreach ($cropper_resolutions as $cropper_resolution)
                        {
                        echo "<option value='" . htmlspecialchars($cropper_resolution) . "'>" . htmlspecialchars($cropper_resolution) . "&nbsp;</option>\n";
                        }
                        ?>
                    </select>
                    <div class="clearerleft"></div>
                </div>
                <?php
                }
            ?>
            <div class="QuestionSubmit">
                <label for="submit">&nbsp;</label>
                <input type='submit' name='updatepreview' onclick="cropReload();return false;" value="<?php echo $lang['transform_update_preview']; ?>" />
                <div class="clearerleft"></div>
            </div>
        </div>
    </div>

             
</form>

<?php  

    
include "../../../include/footer.php";


