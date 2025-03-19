<?php
#
# canva_user_consent setup page
#

include '../../../include/boot.php';
include '../../../include/authenticate.php';

if (!checkperm('a')) {
    exit(escape($lang['error-permissiondenied']));
}

include_once dirname(__FILE__) . "/../include/canva_user_consent_functions.php";
global $baseurl, $lang, $USER_SELECTION_COLLECTION, $CSRF_token_identifier, $usersession;
global $plugins;

if (!in_array("canva_user_consent", $plugins)) {
    header("Status: 403 plugin not activated");
    exit(escape($lang["error-plugin-not-activated"]));
}

include '../../../include/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (empty($_GET['auth'])) {
        ?>
        <div class="BasicsBox">
            <p><?php echo escape($lang['canva_user_consent_invalid_request']); ?></p>
        </div>
        <div class="clearer"></div>
        <?php
        exit;
    } else {
        $auth_code  = $_GET['auth'];
        $tokenParts = explode('.', $auth_code);

        if (count($tokenParts) !== 3) {
            ?>
            <div class="BasicsBox">
                <p><?php echo escape($lang['canva_user_consent_invalid_token']); ?></p>
            </div>
            <div class="clearer"></div>
            <?php
            exit;
        }

        // Decode the header and payload (which are JSON)
        $payload = json_decode(base64UrlDecode($tokenParts[1]), true);
        $userId  = $payload['userId'];

        if (!$userId) {
            ?>
            <div class="BasicsBox">
                <p><?php echo escape($lang['canva_user_consent_invalid_request']); ?></p>
            </div>
            <div class="clearer"></div>
            <?php
        } else {
            $userdata = check_canva_user_id($userId);

            if ($userdata) {
                ?>
                <div class="consent-container">
                    <h2><?php escape($lang['canva_user_consent_authentication_successful']); ?></h2>
                    <p>
                        <a href= "<?php echo $baseurl; ?>/plugins/canva_user_consent/pages/setup.php"><?php echo escape($lang['canva_user_consent_click_here']); ?></a>
                        <?php echo escape($lang['canva_user_consent_to_manage_access']); ?>
                    </p>
                </div>
                <?php
            } else {
                ?>
                <div class="BasicsBox">
                    <div class="consent-container">
                        <h2><?php echo escape($lang['canva_user_consent_required']); ?></h2>
                        <p><?php echo escape($lang['canva_user_consent_description']); ?></p>
                        <div class="button-group-consent">
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?php echo escape($userId); ?>">
                                <input type="hidden" name="<?php echo $CSRF_token_identifier; ?>" value="<?php echo generateCSRFToken($usersession, "canva_confirm"); ?>">
                                <button class="continue-button"><?php echo escape($lang['canva_user_consent_continue']); ?></button>
                            </form>
                            <form>
                                <button onclick="window.close();" class="reject-button"><?php echo escape($lang['canva_user_consent_reject']); ?></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }
} else {
    check_and_save_canva_user($_POST['user_id']);
    ?>
    <div class="consent-container">
        <h2><?php echo escape($lang['canva_user_consent_authentication_successful']); ?></h2>
        <p>
            <a href= "<?php echo $baseurl; ?>/plugins/canva_user_consent/pages/setup.php"><?php echo escape($lang['canva_user_consent_click_here']); ?></a>
            <?php echo escape($lang['canva_user_consent_to_manage_access']); ?>
        </p>
    </div>
    <?php
}
?>

<style>
    .consent-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        max-width: 400px;
        width: 100%;
        padding: 30px;
        margin-top:20px;
        text-align: center;
    }

    .consent-container h2 {
        margin-bottom: 20px;
        color: #333;
    }

    .consent-container p {
        color: #555;
        line-height: 1.5;
        margin-bottom: 30px;
    }

    .button-group-consent{
        display:flex;
        flex-direction: row;
        justify-content: space-evenly;
    }
</style>

<?php
include '../../../include/footer.php';
