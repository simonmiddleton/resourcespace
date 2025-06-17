<?php
include_once __DIR__ . '/../include/totp_functions.php';


function HookTotpAllPreheaderoutput() {
    global $userref,$pagename;
    $cookie=getval("totp","");
    if ($pagename!="totp" && is_numeric($userref) && $userref>0 && $cookie!=TOTP_cookie($userref)) {
        redirect("plugins/totp/pages/totp.php");
    }
}

function HookTotpAllAdditionalheaderjs() {
    global $baseurl_short;
    ?><script src="<?php echo $baseurl_short ?>plugins/totp/js/qrcode.min.js"></script><?php
}

function HookTotpTeam_user_editAdditionaluserfields() {
    global $lang;
    ?>
    <div class="Question">
    <label><?php echo escape($lang["totp_reset"])?></label>
    <input
        name="totp_reset"
        type="checkbox"
        value="yes"
    >
    <div class="clearerleft"></div>
</div>
    <?php
}

function HookTotpTeam_user_editAdditionaluserfieldssave() {
    if (getval("totp_reset","")!="") {
        return new PreparedStatementQuery(',totp=0,totp_tries=0');
    }
}
