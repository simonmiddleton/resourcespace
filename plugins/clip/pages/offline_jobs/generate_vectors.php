<?php
include '../../../../include/boot.php';
include '../../../../include/authenticate.php';

if (!checkperm("a")) {
    exit("Permission denied.");
}

$job_user = getval("job_user", 0, true);

if ($job_user == $userref) {
    $pagetitle   = $lang["my_jobs"];
    $parent_page = generateURL("{$baseurl_short}pages/manage_jobs.php", ["job_user" => $job_user]);
    $breadcrumbs = [
        ['title' => $userfullname == "" ? $username : $userfullname, 'href' => "{$baseurl_short}pages/user/user_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Generate CLIP vectors"]
    ];
} else {
    $pagetitle   = $lang["manage_jobs_title"];
    $parent_page = "{$baseurl_short}pages/manage_jobs.php";
    $breadcrumbs = [
        ['title' => $lang['systemsetup'],       'href' => "{$baseurl_short}pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Generate CLIP vectors"]
    ];
}

// Process form input
$save               = getval('save', '');
$limit              = getval('limit', 10000, "is_positive_or_zero_int_loose");

$job_add_error = false;

if ($save != '' && enforcePostRequest(false)) {
    
    $valid_data = true;
    
    if ((int) $limit > 100000 || (int) $limit <= 0 ) {
        $field_errors['limit'][] = "value must be between 1 and 100000";
        $valid_data = false;
    }

    if ($valid_data) {
        $job_data = [
            "limit" => (int) $limit,
        ];

        // Create the job
        $job_added = job_queue_add("generate_vectors", $job_data);
        
        if (!is_int_loose($job_added)) {
            $job_add_error = true;
        } else {
            redirect($parent_page);
        }
    }
}

include '../../../../include/header.php';

if ($job_add_error) {
    toast_notification(ToastNotificationType::Error, $job_added);
}

?>

<div class='BasicsBox'>
    <h1>
        <?php
        echo escape("Configure Job: Generate CLIP vectors");
        render_help_link('user/manage_jobs'); ?>
    </h1>

    <?php
    $introtext = text("introtext");
    if ($introtext != "") {
        echo "<p>" . escape($introtext) . "</p>";
    }

    renderBreadcrumbs($breadcrumbs);
    ?>

    <form method="post" id="generate_vectors_form" action="<?php echo generateURL("{$baseurl_short}plugins/clip/pages/offline_jobs/generate_vectors.php", ["job_user" => $job_user]); ?>">
        <?php generateFormToken("generate_vectors_form"); ?>
        <div class="Question<?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('limit', $field_errors)) {
                    echo " SaveError";
                } ?>">
            <label for="limit">Limit</label>
            <input type="text" name="limit" class="stdwidth" value="<?php echo escape($limit); ?>">
            <?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('limit', $field_errors)) {
                    foreach ($field_errors['limit'] as $error_message) {
                        echo '<span style="clear: left; display: block; margin-left: 310px; margin-top: 5px; color: red"><i class="fa-solid fa-triangle-exclamation"></i> ' . $error_message . '</span>';
                    }                    
                } 
            ?>            
            <div class="clearerleft"></div>
        </div>
        <div class="QuestionSubmit">
            <label for="save"></label>
            <input type="submit" name="save" value="Create Job" />
        </div>
    </form>
</div>

<?php
include '../../../../include/footer.php';