<?php
include '../../include/boot.php';
include '../../include/authenticate.php';

if (!checkperm("a")) {
    exit("Permission denied.");
}

include '../../include/header.php';

$job_user = getval("job_user", 0, true);


if ($job_user == $userref) {
    $pagetitle  = $lang["my_jobs"];
    $breadcrumbs = [
        ['title' => $userfullname == "" ? $username : $userfullname, 'href' => "{$baseurl_short}pages/user/user_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => generateURL("{$baseurl_short}pages/manage_jobs.php", ["job_user" => $job_user]), 'menu' => false],
        ['title' => "Configure Job: Dummy Job"]
    ];
} else {
    $pagetitle  = $lang["manage_jobs_title"];
    $breadcrumbs = [
        ['title' => $lang['systemsetup'],       'href' => "{$baseurl_short}pages/admin/admin_home.php", 'menu' => true],
        ['title' => $lang["manage_jobs_title"], 'href' => "{$baseurl_short}pages/manage_jobs.php", 'menu' => false],
        ['title' => "Configure Job: Dummy Job"]
    ];
}

// Process form input

$save                   = getval('save', '');
$dummy_text_input       = getval('dummy_text_input', '');
$optional_text_input    = getval('optional_text_input', '');
$dummy_dropdown_input   = getval('dummy_dropdown_input', 0);
$dummy_toggle           = getval('dummy_toggle', false) ? "1" : "0";
$dummy_radio_button     = getval('dummy_radio_button', 1);
$dummy_checkbox_list    = getval('dummy_checkbox_list', [0], false, "is_array");

if ($save != '' && enforcePostRequest(false)) {

    var_dump($_POST);

    // Validate here

    if ($dummy_text_input == '') {
        $field_errors['dummy_text_input'] = "Field required";

        toast_notification(ToastNotificationType::Error, "Dummy text field: Field required");
    }

    // Create Job here

    // Redirect if job gets created here

}





?>

<div class='BasicsBox'>
    <h1>
        <?php
        echo "Configure Job: Dummy Job";
        render_help_link('user/manage_jobs'); ?>
    </h1>

    <?php
    $introtext = text("introtext");
    if ($introtext != "") {
        echo "<p>" . escape($introtext) . "</p>";
    }

    renderBreadcrumbs($breadcrumbs);
    ?>

    <form method="post" id="dummy_form" action="<?php echo generateURL("{$baseurl_short}pages/offline_jobs/dummy_job.php", ["job_user" => $job_user]); ?>">
        <?php generateFormToken("dummy_form"); ?>
        <div class="Question<?php
                if (isset($field_errors) && is_array($field_errors) && array_key_exists('dummy_text_input', $field_errors)) {
                    echo " FieldSaveError";
                } ?>">
            <label for="dummy_text_input">Dummy Text Input *</label>
            <input type=text name="dummy_text_input" class="stdwidth" value="<?php echo escape($dummy_text_input); ?>">
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="optional_text_input">Optional Text Input</label>
            <input type=text name="optional_text_input" class="stdwidth" value="<?php echo escape($optional_text_input); ?>">
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="dummy_dropdown_input">Dummy Dropdown Input</label>
            <select class="stdwidth" name="dummy_dropdown_input">
                <option value="1" <?php echo ($dummy_dropdown_input == 1) ? "selected" : ''; ?>>Test Value 1</option>
                <option value="2" <?php echo ($dummy_dropdown_input == 2) ? "selected" : ''; ?>>Test Value 2</option>
            </select>
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="dummy_toggle">Dummy Toggle</label>
            <input name="dummy_toggle" id="dummy_toggle" type="checkbox" value="1"<?php echo ($dummy_toggle ? 'checked="checked"' : ''); ?>>
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label>Dummy Radio Button</label>
            <div class="tickset">
                <div class="Inline">
                    <input type="radio" name="dummy_radio_button" id="dummy_radio_button_opt1" value="1" <?php echo ($dummy_radio_button == 1) ? " checked " : ''; ?>/>
                    <label for="dummy_radio_button_opt1">Option 1</label>
                    <input type="radio" name="dummy_radio_button" id="dummy_radio_button_opt2" value="2" <?php echo ($dummy_radio_button == 2) ? " checked " : ''; ?>/>
                    <label for="dummy_radio_button_opt2">Option 2</label>
                    <input type="radio" name="dummy_radio_button" id="dummy_radio_button_opt3" value="3" <?php echo ($dummy_radio_button == 3) ? " checked " : ''; ?>/>
                    <label for="dummy_radio_button_opt3">Option 3</label>
                </div>
            </div>
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label>Dummy Checkbox List</label>
            <div class="tickset">
                <div class="Inline">
                    <input type="checkbox" name="dummy_checkbox_list[]" id="dummy_checkbox_list_opt0" value="0" <?php echo (in_array(0, $dummy_checkbox_list)) ? " checked " : ""; ?>/>
                    <label for="dummy_checkbox_list_opt0">All</label>
                    <input type="checkbox" name="dummy_checkbox_list[]" class="dummy_checkbox_list" id="dummy_checkbox_list_opt1" value="1" <?php echo (in_array(1, $dummy_checkbox_list)) ? " checked " : ""; ?>/>
                    <label for="dummy_checkbox_list_opt1">Option 1</label>
                    <input type="checkbox" name="dummy_checkbox_list[]" class="dummy_checkbox_list" id="dummy_checkbox_list_opt2" value="2" <?php echo (in_array(2, $dummy_checkbox_list)) ? " checked " : ""; ?>/>
                    <label for="dummy_checkbox_list_opt2">Option 2</label>
                    <input type="checkbox" name="dummy_checkbox_list[]" class="dummy_checkbox_list" id="dummy_checkbox_list_opt3" value="3" <?php echo (in_array(3, $dummy_checkbox_list)) ? " checked " : ""; ?>/>
                    <label for="dummy_checkbox_list_opt3">Option 3</label>
                </div>
            </div>
            <div class="clearerleft"></div>
            <script>
                jQuery(document).ready(function () {
                    jQuery('#dummy_checkbox_list_opt0').on('change', function () {
                        jQuery('.dummy_checkbox_list').prop('checked', this.checked);
                    });

                    jQuery('.dummy_checkbox_list').on('change', function () {
                        jQuery('#dummy_checkbox_list_opt0').prop(
                            'checked',
                            jQuery('.dummy_checkbox_list:checked').length === jQuery('.dummy_checkbox_list').length
                        );
                    });

                    jQuery('.dummy_checkbox_list').trigger('change');
                });
            </script>
        </div>
        <div class="QuestionSubmit">
            <label for="save"></label>
            <input type="submit" name="save" value="<?php echo escape($lang["oj_common_create_job"]); ?>" />
        </div>
    </form>
</div>

<?php
include '../../include/footer.php';