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
        ['title' => "Configure Job: Process unprocessed resources with Whisper"]
    ];
} else {
    $pagetitle   = $lang["manage_jobs_title"];
    $parent_page = "{$baseurl_short}pages/manage_jobs.php";
    $breadcrumbs = [
        ['title' => $lang['systemsetup'],       'href' => "{$baseurl_short}pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => $parent_page, 'menu' => false],
        ['title' => "Configure Job: Process unprocessed resources with Whisper"]
    ];
}

// Process form input
$save = getval('save', '');

$job_add_error = false;

if ($save != '' && enforcePostRequest(false)) {
    
    $valid_data = true;

    if ($valid_data) {
        $job_data = [];

        // Create the job
        $job_added = job_queue_add("process_whisper", $job_data);
        
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
        echo escape("Configure Job: Process unprocessed resources with Whisper");
        render_help_link('user/manage_jobs'); ?>
    </h1>

    <?php
    $introtext = text("introtext");
    if ($introtext != "") {
        echo "<p>" . escape($introtext) . "</p>";
    }

    renderBreadcrumbs($breadcrumbs);
    ?>

    <form method="post" id="process_whisper_form" action="<?php echo generateURL("{$baseurl_short}plugins/whisper/pages/offline_jobs/process_whisper.php", ["job_user" => $job_user]); ?>">
        <?php generateFormToken("process_whisper_form"); ?>
        <div class="QuestionSubmit">
            <label for="save"></label>
            <input type="submit" name="save" value="Create Job" />
        </div>
    </form>
</div>

<?php
include '../../../../include/footer.php';