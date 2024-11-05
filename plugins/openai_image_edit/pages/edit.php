<?php
include "../../../include/boot.php";
include "../../../include/authenticate.php";


if (!in_array("openai_gpt",$plugins)) 
    {
    exit("The OpenAI GPT plugin must be enabled and configured.");
    }

// Find image
$ref=getval("ref",0,true);
$access=get_resource_access($ref);
$edit_access=get_edit_access($ref);

if ($access!=0)
    {
    // They shouldn't arrive here as the link wouldn't be available.
    exit("Access denied");
    }

function curlprogress($resource,$download_size, $downloaded, $upload_size, $uploaded)
    {
    // Give an estimate of completion based on the % of upload. There is also the DALL-E 2 processing time but the bulk of the time seems to be the upload due to using PNG for images.
    global $lang;
    if ($uploaded>0)
        {
        $percent=floor(($uploaded/$upload_size)*100);
        $percent*=7;$percent+=10; // It lags behind a lot, after experimentation, this gives a more reasonable estimate of completion time.
        if ($percent>=100)
            {
            set_processing_message($lang["openai_image_edit__completing"]);
            }
        else
            {
            set_processing_message($lang["openai_image_edit__sending"] . " (" . $percent . "%)");
            }
        }
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    set_processing_message($lang["openai_image_edit__preparing_images"]);

    $maskData = $_POST['mask'];    // Base64 encoded mask from the frontend
    $prompt = isset($_POST['prompt']) ? $_POST['prompt'] : '';

    // Decode the mask data from base64
    list($type, $maskData) = explode(';', $maskData);
    list(, $maskData)      = explode(',', $maskData);
    $maskData = base64_decode($maskData);

    // Prepare the OpenAI API request using multipart/form-data
    $url = 'https://api.openai.com/v1/images/edits';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'curlprogress');
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $openai_gpt_api_key",
        "Content-Type: multipart/form-data"
    ]);


    // Improve the mask by replacing non transparent areas with black. This significantly reduces the time to send the mask to OpenAI as the compression is much better.
    $mask=imagecreatefromstring($maskData);
    // Set blending mode off to preserve transparency
    imagealphablending($mask, false);
    imagesavealpha($mask, true);

    // Get image dimensions
    $width = imagesx($mask);
    $height = imagesy($mask);

    // Loop through each pixel
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            // Get the color and alpha of the current pixel
            $rgba = imagecolorat($mask, $x, $y);
            $alpha = ($rgba & 0x7F000000) >> 24;

            // Set the pixel to black with the same alpha
            $black = imagecolorallocatealpha($mask, 0, 0, 0, $alpha);
            imagesetpixel($mask, $x, $y, $black);
        }
    }

    // Re-render the mask
    ob_start();
    imagepng($mask);
    $maskDataSimplified = ob_get_contents();
    ob_end_clean();

    // Prepare the data array using CURLFile for both image and mask
    $data = [
        'model' => 'dall-e-2',  // Specify model (if applicable)
        //'image' => new CURLFile($imageFilePath, 'image/png'),
        'image' => new CURLStringFile($maskData, 'image/png'),
        'mask' => new CURLStringFile($maskDataSimplified, 'image/png'),
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024'
    ];

    // Attach the form data
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // Execute the request and get the response
    $response = curl_exec($ch);

    // Check for errors in the cURL request
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        $json=json_decode($response,true);
        $url=$json["data"][0]["url"] ?? "";
        if ($url!="")
            {
            header('Content-Type: application/json');
            echo json_encode(["image_base64"=>base64_encode(file_get_contents($url))]);
            }
        else
            {
            echo $response;
            }
    }

    curl_close($ch);
    exit();
}


include "../../../include/header.php";


?>

<img id="image" src="get_png.php?ref=<?php echo $ref ?>" alt="" hidden>
<div id="canvas-container" class="canvas-container" style="position: relative;visibility:hidden;">
    <canvas id="canvas"></canvas>
    <canvas id="overlayCanvas" style="position: absolute; top: 0; left: 0; pointer-events: none;"></canvas>
</div>

<div id="toolbox" class="toolbox openai-image-edit" style="visibility:hidden;">
<div id="tools">
<label for="penSize"><?php echo escape($lang["openai_image_edit__pensize"]) ?></label><br>
<input type="range" id="penSize" min="10" max="200" value="75">
<br><br>
<label for="prompt"><?php echo escape($lang["openai_image_edit__prompt"]) ?></label><br>
<textarea id="prompt" rows="5" required placeholder="Prompt for regeneration">Complete image as appropriate</textarea>
<br>
<button id="clearBtn" onclick="window.location.reload();"><?php echo escape($lang["openai_image_edit__reset"]) ?></button>
<button id="submitBtn"><?php echo escape($lang["openai_image_edit__generate"]) ?></button>
<br><br><br>


<div id="downloadOptions" style="visibility: hidden;">
<label for="downloadType"><?php echo escape($lang["openai_image_edit__exportoptions"]) ?></label><br>
<select id="downloadType">
    <option value="image/jpeg">JPEG</option>
    <option value="image/png">PNG</option>
    <option value="image/webp">WEBP</option>
</select>
<br>
<select id="downloadAction">
    <option value="download"><?php echo escape($lang["openai_image_edit__download"]) ?></option>
<?php if ($edit_access) { ?><option value="alternative"><?php echo escape($lang["openai_image_edit__alternative"]) ?></option><?php } ?>
</select>
<br>
<button id="downloadBtn"><?php echo escape($lang["openai_image_edit__export"]) ?></button>
</div>

</div>
</div>

<script>
CentralSpaceShowProcessing();
submit_url='../pages/edit.php?ref=<?php echo $ref ?>';
alternative_url='../pages/save_alternative.php?ref=<?php echo $ref ?>';
view_url='<?php echo $baseurl ?>/pages/view.php?ref=<?php echo $ref ?>';
csrf_pair={<?php echo generateAjaxToken("openai_image_edit"); ?>};
defaultLoadingMessage=<?php echo json_encode($lang["openai_image_edit__preparing_images"]) ?>;
</script>
<script src="../js/edit.js"></script>
<?php
include "../../../include/footer.php";

