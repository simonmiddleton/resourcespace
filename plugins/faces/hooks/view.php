<?php
function HookFacesViewCustompanels()
    {
    global $lang,$ref,$baseurl;

    $faces=ps_query("select ref,det_score,bbox from resource_face where resource=? order by ref",["i",$ref]);
    if (count($faces)==0) {return false;} // No face vectors yet.

    $face_path = get_resource_path($ref, true, "scr", false, "jpg");
$face_url  = get_resource_path($ref, false, "scr", false, "jpg");
if (!file_exists($face_path)) {
    $face_path = get_resource_path($ref, true, "", false, "jpg");
    $face_url  = get_resource_path($ref, false, "", false, "jpg");
}

// Get dimensions of the image
list($image_width, $image_height) = getimagesize($face_path);
?>
<div class="RecordBox">
    <div class="RecordPanel">
        <div class="Title"><?php echo escape($lang["faces-detected-faces"]); ?></div>

        <div class="Listview">
        <table class="ListviewStyle">
            <tr>
                <th><?php echo escape($lang["faces-detected-face"]) ?></th>
                <th><?php echo escape($lang["faces-confidence"]) ?></th>
                <th><?php echo escape($lang["actions"]) ?></th>
            </tr>

            <?php foreach ($faces as $face):
                $bbox = json_decode($face["bbox"], true);
                if (!is_array($bbox) || count($bbox) !== 4) { continue; }

                list($x1, $y1, $x2, $y2) = $bbox;

                // Clamp and calculate face box size
                $x1 = max(0, min($image_width, $x1));
                $y1 = max(0, min($image_height, $y1));
                $x2 = max(0, min($image_width, $x2));
                $y2 = max(0, min($image_height, $y2));

                $face_width = $x2 - $x1;
                $face_height = $y2 - $y1;

                // Ensure the DISPLAYED face is at least 100px wide
                $min_display_width = 100;
                $scale = $face_width < $min_display_width ? $min_display_width / $face_width : 1.0;

                // Scaled face box size
                $crop_width = $face_width * $scale;
                $crop_height = $face_height * $scale;

                // Background properties
                $bg_pos_x = -$x1 * $scale;
                $bg_pos_y = -$y1 * $scale;
                $bg_size_x = $image_width * $scale;
                $bg_size_y = $image_height * $scale;

                $style = sprintf(
                    "width:%.0fpx;height:%.0fpx;background-image:url('%s');background-position:%.0fpx %.0fpx;background-size:%.0fpx %.0fpx;background-repeat:no-repeat;border:1px solid #ccc;",
                    $crop_width,
                    $crop_height,
                    $face_url,
                    $bg_pos_x,
                    $bg_pos_y,
                    $bg_size_x,
                    $bg_size_y
                );
            ?>
                <tr>
                    <td>
                        <div style="<?php echo $style ?>"></div>
                    </td>
                    <td><?php echo round($face["det_score"] * 100, 2) ?>%</td>
                    <td>
                    <?php $search_url=generateURL("{$baseurl}/pages/search.php", array("search" => "!face" . $face["ref"])); ?>
                    <a href="<?php echo $search_url ?>" onClick="return CentralSpaceLoad(this,true);">
                    <i class="fa fa-fw fa-search"></i>&nbsp;<?php echo escape($lang["faces-find-matching"]); ?>
                    </a>
                    </td>

                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
    </div>
    <?php
    return false; # Allow further custom panels
    }
