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
        ['title' => "Configure Job: Update extracted text"]
    ];
} else {
    $pagetitle   = $lang["manage_jobs_title"];
    $parent_page = "{$baseurl_short}pages/manage_jobs.php";
    $breadcrumbs = [
        ['title' => $lang['systemsetup'],       'href' => "{$baseurl_short}pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Update extracted text"]
    ];
}

// Process form input
$save               = getval('save', '');
$collection_refs    = getval('collection_refs', '');
$update_all         = (bool) getval('update_all', false);

$job_add_error = false;

if ($save != '' && enforcePostRequest(false)) {
    
    $valid_data = true;

    $processed_collection_refs = parse_int_ranges($collection_refs, 0, true);
    
    if (!empty($processed_collection_refs['errors'])) {
        $field_errors['collection_refs'] = $processed_collection_refs['errors'];
        $valid_data = false;
    }

    if ($valid_data) {
        $job_data = [
            "collection_refs" => $collection_refs,
            "update_all" => $update_all,
        ];

        // Create the job
        $job_added = job_queue_add("update_extracted_text", $job_data);
        
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
        echo escape("Configure Job: Update extracted text");
        render_help_link('user/manage_jobs'); ?>
    </h1>

    <?php
    $introtext = text("introtext");
    if ($introtext != "") {
        echo "<p>" . escape($introtext) . "</p>";
    }

    renderBreadcrumbs($breadcrumbs);
    ?>

    <form method="post" id="update_extracted_text_form" action="<?php echo generateURL("{$baseurl_short}pages/offline_jobs/update_extracted_text.php", ["job_user" => $job_user]); ?>">
        <?php generateFormToken("update_extracted_text_form"); ?>
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
            <label for="update_all">Update even if there is existing data, and overwrite old values</label>
            <input name="update_all" id="update_all" type="checkbox" value="1"<?php echo ($update_all ? 'checked="checked"' : ''); ?>>
            <div class="clearerleft">
        </div>
        <div class="QuestionSubmit">
            <label for="save"></label>
            <input type="submit" name="save" value="Create Job" />
        </div>
    </form>
</div>

<?php
include '../../include/footer.php';