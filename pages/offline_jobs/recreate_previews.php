<?php
include '../../include/boot.php';
include '../../include/authenticate.php';

include '../../include/header.php';


$job_user = getval("job_user", 0, true);


if ($job_user == $userref) {
    $pagetitle   = $lang["my_jobs"];
    $parent_page = generateURL("{$baseurl_short}pages/manage_jobs.php", ["job_user" => $job_user]);
    $breadcrumbs = [
        ['title' => $userfullname == "" ? $username : $userfullname, 'href' => "{$baseurl_short}pages/user/user_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Recreate Previews"]
    ];
} else {
    $pagetitle   = $lang["manage_jobs_title"];
    $parent_page = "{$baseurl_short}pages/manage_jobs.php";
    $breadcrumbs = [
        ['title' => $lang['systemsetup'],       'href' => "{$baseurl_short}pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Recreate Previews"]
    ];
}

// Form input options
$image_size_options    = ['all' => 'All'] + array_column(get_all_image_sizes(true, false),'name','id');
$resource_type_options = ['all' => 'All'] + array_column(get_resource_types(),'name','ref');

// Process form input
$save            = getval('save', '');
$process_type    = getval('process_type', 'resources');
$refs            = getval('refs', '');
$image_sizes     = getval('image_sizes', array_keys($image_size_options), false, "is_array");
$resource_types  = getval('resource_types', array_keys($resource_type_options), false, "is_array");
$use_existing    = (bool) getval('use_existing', false);
$video_update    = (bool) getval('video_update', false);
$delete_existing = (bool) getval('delete_existing', false);

if ($save != '' && enforcePostRequest(false)) {

    $valid_data = true;

    // Validate here
    $processed_refs = parse_int_ranges($refs, 0);

    if ($refs == '') {
        $field_errors['refs'] = ["field required"];
        toast_notification(ToastNotificationType::Error, "Refs: field required");

        $valid_data = false;
    } elseif (!empty($processed_refs['errors'])) {
        $field_errors['refs'] = $processed_refs['errors'];

        foreach ($processed_refs['errors'] as $ref_error) {
            toast_notification(ToastNotificationType::Error, "Refs: $ref_error");
        }

        $valid_data = false;
    }


    if ($valid_data) {
        $job_data = [
            "process_type" => $process_type,
            "refs" => $refs,
            "sizes" => $image_sizes,
            "types" => $resource_types,
            "use_existing" => $use_existing,
            "video_update" => $video_update,
            "delete_existing" => $delete_existing,
        ];

        var_dump($job_data);
        // Create Job here

        $job_added = job_queue_add("recreate_previews", $job_data);
        if ((string)(int) $job_added !== (string) $job_added) {
            toast_notification(ToastNotificationType::Error, "Error adding job to queue");
        } else {
            redirect($parent_page);
        }
    }
}


?>

<div class='BasicsBox'>
    <h1>
        <?php
        echo escape("Configure Job: Recreate Previews");
        render_help_link('user/manage_jobs'); ?>
    </h1>

    <?php
    $introtext = text("introtext");
    if ($introtext != "") {
        echo "<p>" . escape($introtext) . "</p>";
    }

    renderBreadcrumbs($breadcrumbs);
    ?>

    <form method="post" id="dummy_form" action="<?php echo generateURL("{$baseurl_short}pages/offline_jobs/recreate_previews.php", ["job_user" => $job_user]); ?>">
        <?php generateFormToken("dummy_form"); ?>
        <div class="Question">
            <label for="process_type">Target *</label>
            <select class="stdwidth" name="process_type">
                <option value="resources" <?php echo ($process_type == "resources") ? "selected" : ''; ?>>Resources</option>
                <option value="collections" <?php echo ($process_type == "collections") ? "selected" : ''; ?>>Collections</option>
            </select>
            <div class="clearerleft"></div>
        </div>
        <div class="Question<?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('refs', $field_errors)) {
                    echo " SaveError";
                } ?>">
            <label for="refs">Refs *</label>
            <input type="text" name="refs" class="stdwidth" value="<?php echo escape($refs); ?>">
            <?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('refs', $field_errors)) {
                    foreach ($field_errors['refs'] as $error_message) {
                        echo '<span style="clear: left; display: block; margin-left: 310px; margin-top: 5px; color: red"><i class="fa-solid fa-triangle-exclamation"></i> ' . $error_message . '</span>';
                    }                    
                } 
            ?>            
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label>Image Sizes *</label>
            <div class="tickset">
                <div class="Inline">
                    <?php
                    foreach ($image_size_options as $size_id => $image_size_option) {
                    ?>
                    <input type="checkbox" name="image_sizes[]" 
                            id="image_sizes_<?php echo $size_id; ?>"
                            <?php echo ($size_id != "all") ? 'class="image_sizes"' : ""; ?>
                            value="<?php echo $size_id; ?>"
                            <?php echo (in_array($size_id, $image_sizes) ? " checked " : ""); ?>/>
                    <label for="image_sizes_<?php echo $size_id; ?>"><?php echo $image_size_option; ?></label>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <div class="clearerleft"></div>
            <script>
                jQuery(document).ready(function () {
                    jQuery('#image_sizes_all').on('change', function () {
                        jQuery('.image_sizes').prop('checked', this.checked);
                    });

                    jQuery('.image_sizes').on('change', function () {
                        jQuery('#image_sizes_all').prop(
                            'checked',
                            jQuery('.image_sizes:checked').length === jQuery('.image_sizes').length
                        );
                    });

                    jQuery('.image_sizes').trigger('change');
                });
            </script>
        </div>
        <div class="Question">
            <label>Resource Types *</label>
            <div class="tickset">
                <div class="Inline">
                    <?php
                    foreach ($resource_type_options as $type_id => $resource_type_option) {
                    ?>
                    <input type="checkbox" name="resource_types[]" 
                            id="resource_types_<?php echo $type_id; ?>"
                            <?php echo ($type_id != "all") ? 'class="resource_types"' : ""; ?>
                            value="<?php echo $type_id; ?>"
                            <?php echo (in_array($type_id, $resource_types) ? " checked " : ""); ?>/>
                    <label for="resource_types_<?php echo $type_id; ?>"><?php echo $resource_type_option; ?></label>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <div class="clearerleft"></div>
            <script>
                jQuery(document).ready(function () {
                    jQuery('#resource_types_all').on('change', function () {
                        jQuery('.resource_types').prop('checked', this.checked);
                    });

                    jQuery('.resource_types').on('change', function () {
                        jQuery('#resource_types_all').prop(
                            'checked',
                            jQuery('.resource_types:checked').length === jQuery('.resource_types').length
                        );
                    });

                    jQuery('.resource_types').trigger('change');
                });
            </script>
        </div>
        <div class="Question">
            <label for="use_existing">Use existing uploaded previews?</label>
            <input name="use_existing" id="use_existing" type="checkbox" value="1"<?php echo ($use_existing ? 'checked="checked"' : ''); ?>>
            <div class="clearerleft"></div>
        </div>
            <div class="Question">
            <label for="video_update">Update previews for all video reosurces that do not have the required '$ffmpeg_preview_extension' extension?</label>
            <input name="video_update" id="video_update" type="checkbox" value="1"<?php echo ($video_update ? 'checked="checked"' : ''); ?>>
            <div class="clearerleft"></div>
        </div>
        </div>
            <div class="Question">
            <label for="delete_existing">Delete all existing previews</label>
            <input name="delete_existing" id="delete_existing" type="checkbox" value="1"<?php echo ($delete_existing ? 'checked="checked"' : ''); ?>>
            <div class="clearerleft"></div>
        </div>
        <div class="QuestionSubmit">
            <label for="save"></label>
            <input type="submit" name="save" value="Create Job" />
        </div>
    </form>
</div>

<?php
include '../../include/footer.php';