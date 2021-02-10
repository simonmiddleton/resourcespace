<?php
include "../../include/db.php";
include "../../include/authenticate.php";

# Get username. Spaces have been replaced with undescores when tagging.
$username=str_replace("_"," ",getvalescaped("username",""));

# Resolve the username and make sure the user has access.
$users=get_users(0,$username,"u.username",true,-1,"",false,"",true); // use permissions = TRUE and exact match on username
if (count($users)==0)
    {
    exit("User not found.");
    }

$user=$users[0]["ref"];
$userdetails=$users[0];

$profile_text = get_profile_text($user);
$profile_image = get_profile_image($user);

include "../../include/header.php";
?><meta http-equiv="Cache-control" content="no-cache">

<div class="BasicsBox">

  <h1><?php echo htmlspecialchars($userdetails["fullname"]) ?></h1>

  <?php if ($profile_image) { ?><p><img src="<?php echo $profile_image ?>" alt="Current profile image"></p><?php } ?>

  <p><?php echo nl2br(htmlspecialchars($profile_text)) ?></p>

  </form>
  </div>

</div>

<?php
include "../../include/footer.php";

