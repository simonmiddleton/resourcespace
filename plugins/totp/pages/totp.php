<?php
include dirname(__DIR__, 3) . "/include/boot.php";
include dirname(__DIR__, 3) . "/include/authenticate.php";
$error="";

$code=getval("code","");
$valid=false;

if (TOTP_tries($userref)>=9) {exit(escape($lang["totp_tries_exceeded"]));} // Need something prettier here.

if ($code!="") {
    if (TOTP_validate($code,$userref)) {
        TOTP_setup_complete($userref);
        $valid=true;
    }
    else
    {
    TOTP_increase_tries($userref);
    }
}
if (getval("skip","")!="" && checkperm("a")) {$valid=true;} // Allow admins to skip for a day. So they can proceed with plugin setup etc.

if ($valid)
    {
    setcookie("totp",TOTP_cookie($userref),0,"/");
    redirect("pages/home.php");
    }

if (!TOTP_is_user_set_up($userref)) { 
include dirname(__DIR__, 3) . "/include/header.php";
?>

<div class="BasicsBox">
<h1><?php echo escape($lang["totp_set_up"]);?> </h1>
<p>
<?php echo escape($lang["totp_set_up_details"]);?> 
</p>
<div id="qrcode"></div>
<script>
var qrcode = new QRCode("qrcode", {
    text: "<?php echo TOTP_get_url($userref) ?>",
    width: 300,
    height: 300,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
});
window.addEventListener('DOMContentLoaded', function () {
    document.getElementById('code').focus();
  });
</script>

<form 
    class="totp"
    method="post"
    action="<?php echo $baseurl_short?>plugins/totp/pages/totp.php"
>
<?php generateFormToken("totpform"); ?>

    <?php if ($error != "") { ?>
        <div class="FormIncorrect" id="LoginError" tabindex="-1"><?php echo strip_tags_and_attributes($error) ?></div>
        <script>window.onload = function() { document.getElementById("LoginError").focus(); }</script>
    <?php }?>

    <div class="Question">
        <label for="code" style="width:138px;"><?php echo escape($lang["totp_code"]); ?> </label>
        <input type="code" name="code" id="code" class="shrtwidth" inputmode="numeric" pattern="[0-9]*" maxlength="6" />
        <div class="clearerleft"></div>
    </div>

    <div class="QuestionSubmit">       
        <input name="Submit" type="submit" value="<?php echo escape($lang["totp_confirm"]); ?>" />
    </div>

    <?php if (checkperm("a")) { ?>
    <p class="LoginLinks">
            <a id="account_apply" href="<?php echo $baseurl_short?>plugins/totp/pages/totp.php?skip=true">
                <i class="fas fa-fw fa-forward"></i>&nbsp;<?php echo escape($lang["totp_skip"]); ?>
            </a>
    </p>
    <?php } ?>
</form>

</div>

<?php
include dirname(__DIR__, 3) . "/include/footer.php";
} else {
    $tries=TOTP_tries($userref);
    // Intentionally minimal page for TOTP entry.
    ?>
    <html><head>
    <style>
    html,body {
        font-family: arial,sans-serif;
        background: #eee;
    }
    form {
        width:500px;
        box-shadow: 5px 5px 10px grey;
        border-radius: 10px;
        padding:20px;text-align:center;
        margin:100px auto 0 auto;
        background: #fff;
    }
    label {
        width:300px;
    }
    </style>
    <script>
    window.addEventListener('DOMContentLoaded', function () {
    document.getElementById('code').focus();
  });
    </script>
    </head><body>
    <form 
    class="totp"
    method="post"
    action="<?php echo $baseurl_short?>plugins/totp/pages/totp.php"
    >
    <?php generateFormToken("totpform"); ?>
    <svg width="80" height="120" viewBox="0 0 80 120" xmlns="http://www.w3.org/2000/svg">
  <rect x="10" y="5" width="60" height="110" rx="10" fill="#e0e0e0" stroke="#999" stroke-width="2"/>
  <rect x="20" y="25" width="40" height="20" fill="#ccc" rx="2"/>
  <text x="40" y="40" font-size="12" text-anchor="middle" fill="#333" font-family="sans-serif">123 456</text>
  <circle cx="40" cy="100" r="4" fill="#666"/>
    </svg>
    <p>
    <?php echo escape($lang["totp_code_details"]);?> 
    </p>
    <p><input type="code" name="code" id="code" class="shrtwidth" inputmode="numeric" pattern="[0-9]*" maxlength="6" /></p>

    <?php if ($tries>0) { ?><?php echo 10-$tries . " " . escape($lang["totp_tries_left"]) ?><?php } ?>
    <div class="QuestionSubmit">       
        <input name="Submit" type="submit" value="<?php echo escape($lang["totp_confirm"]); ?>" />
    </div>
    </form>
    </body></html>
    <?php
}
