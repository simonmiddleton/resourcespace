<?php
include '../../include/boot.php';
include '../../include/authenticate.php';

$job_user = getval("job_user", 0, true);

if ($job_user == $userref) {
    $pagetitle   = $lang["my_jobs"];
    $parent_page = generateURL("{$baseurl_short}pages/manage_jobs.php", ["job_user" => $job_user]);
    $breadcrumbs = [
        ['title' => $userfullname == "" ? $username : $userfullname, 'href' => "{$baseurl_short}pages/user/user_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Purge duplicate resources"]
    ];
} else {
    $pagetitle   = $lang["manage_jobs_title"];
    $parent_page = "{$baseurl_short}pages/manage_jobs.php";
    $breadcrumbs = [
        ['title' => $lang['systemsetup'],       'href' => "{$baseurl_short}pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Purge duplicate resources"]
    ];
}

// Process form input
$save               = getval('save', '');
$collection_refs    = getval('collection_refs', '');
$manage_method      = getval('manage_method', 'lifo');
$dry_run            = (bool) getval('dry_run', false);
$delete_perm        = (bool) getval('delete_perm', false);


$job_add_error = false;

if ($save != '' && enforcePostRequest(false)) {
    
    $valid_data = true;

    $processed_collection_refs = parse_int_ranges($collection_refs, 0, true);
       
    if (!empty($processed_collection_refs['errors'])) {
        $field_errors['collection_refs'] = $processed_collection_refs['errors'];
        $valid_data = false;
    }

    if (!in_array($manage_method, ['lifo', 'fifo'])) {
        $field_errors['manage_method'][] = "invalid value passed for field";
        $valid_data = false;
    }

    if ($valid_data) {
        $job_data = [
            "collection_refs" => $collection_refs,
            "manage_method" => $manage_method,
            "dry_run" => $dry_run,
            "delete_perm" => $delete_perm,
        ];

        // Create the job
        $job_added = job_queue_add("purge_duplicates", $job_data);
        
        if (!is_int_loose($job_added)) {
            $job_add_error = true;
        } else {
            redirect($parent_page);
        }
    }
}

include '../../include/header.php';

if ($job_add_error) {
    toast_notification(ToastNotificationType::Error, $job_added);
}

?>

<div class='BasicsBox'>
    <h1>
        <?php
        echo escape("Configure Job: Purge duplicate resources");
        render_help_link('user/manage_jobs'); ?>
    </h1>

    <?php
    $introtext = text("introtext");
    if ($introtext != "") {
        echo "<p>" . escape($introtext) . "</p>";
    }

    renderBreadcrumbs($breadcrumbs);
    ?>

    <form method="post" id="purge_duplicates_form" action="<?php echo generateURL("{$baseurl_short}pages/offline_jobs/purge_duplicates.php", ["job_user" => $job_user]); ?>">
        <?php generateFormToken("purge_duplicates_form"); ?>
        <div class="Question<?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('collection_refs', $field_errors)) {
                    echo " SaveError";
                } ?>">
            <label for="collection_refs">Collection Refs</label>
            <input type="text" name="collection_refs" class="stdwidth" value="<?php echo escape($collection_refs); ?>">
            <?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('collection_refs', $field_errors)) {
                    foreach ($field_errors['collection_refs'] as $error_message) {
                        echo '<span style="clear: left; display: block; margin-left: 310px; margin-top: 5px; color: red"><i class="fa-solid fa-triangle-exclamation"></i> ' . $error_message . '</span>';
                    }                    
                } 
            ?>            
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="manage_method">Manage method</label>
            <select class="stdwidth" name="manage_method">
                <option value="lifo" <?php echo ($manage_method == "lifo") ? "selected" : ''; ?>>Last in first out</option>
                <option value="fifo" <?php echo ($manage_method == "fifo") ? "selected" : ''; ?>>First in first out</option>
            </select>
            <?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('manage_method', $field_errors)) {
                    foreach ($field_errors['manage_method'] as $error_message) {
                        echo '<span style="clear: left; display: block; margin-left: 310px; margin-top: 5px; color: red"><i class="fa-solid fa-triangle-exclamation"></i> ' . $error_message . '</span>';
                    }                    
                } 
            ?>   
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="dry_run">Dry run</label>
            <input name="dry_run" id="dry_run" type="checkbox" value="1"<?php echo ($dry_run ? 'checked="checked"' : ''); ?>>
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="delete_perm">Delete permanently</label>
            <input name="delete_perm" id="delete_perm" type="checkbox" value="1"<?php echo ($delete_perm ? 'checked="checked"' : ''); ?>>
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