<?php
include_once '../include/db.php';
include_once '../include/general.php';
include_once '../include/resource_functions.php';

$k   = getvalescaped('k', '');
$ref = getvalescaped('ref', 0, true);

if($k == '' || !check_access_key($ref, $k))
    {
    include '../include/authenticate.php';
    }

$resource = get_resource_data($ref);
if($resource === false)
    {
    exit($lang['resourcenotfound']);
    }
resource_type_config_override($resource['resource_type']);

$access = get_resource_access($resource);
if($access != 0)
    {
    http_response_code(403);
    exit($lang["error-403-forbidden"]);
    }


$searchtext = trim(getval('searchtext' , ''));
$findtext = (getval('findtext' , '') != '' ? true : false);
if($findtext && $searchtext != '')
    {
    // IMPORTANT: never show the real file path with this feature
    $hide_real_filepath_initial = $hide_real_filepath;
    $hide_real_filepath = true;
    $pdfjs_original_file_path = get_resource_path($ref, false, '', false, $resource['file_extension']);
    $hide_real_filepath = $hide_real_filepath_initial;

    $pdfjs_viewer_url = generateURL(
        "{$baseurl_short}lib/pdfjs-1.9.426/web/viewer.php",
        array(
            'ref'  => $ref,
            'file' => $pdfjs_original_file_path
        )
    );
    // IMPORTANT: intentionally not urlencoding the searchtext param. This is because PDFjs will not support multiple words
    // if urlencoded
    $pdfjs_viewer_url .= "#search={$searchtext}";

    redirect($pdfjs_viewer_url);
    exit();
    }
?>
<div class="BasicsBox">
    <h1><?php echo $lang["findtextinpdf"]; ?></h1>
    <form id="FindTextInPDF" class="modalform" method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <?php generateFormToken("FindTextInPDF"); ?>
        <input type="hidden" name="ref" value="<?php echo $ref; ?>">
        <div class="Question">
            <label for="searchtext"><?php echo $lang["searchbytext"]; ?></label>
            <input type="text" id="searchtext" name="searchtext"></input>
            <div class="clearleft"></div>
        </div>
        <div class="QuestionSubmit" >
            <label></label>
            <input type="submit" name="findtext" value="<?php echo $lang["searchbutton"]; ?>" onclick="if(jQuery('#searchtext').val().trim()==''){return false;}"></input>
            <div class="clearleft"></div>
        </div>
    </form>
</div>
