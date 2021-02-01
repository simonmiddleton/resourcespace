<?php
include "../../include/db.php";
include "../../include/authenticate.php";  //FIX CSRF ERROR
include_once '../../include/config_functions.php';
include_once "../../include/user_functions.php";
include_once "../../include/general_functions.php";

// Do not allow access to anonymous users
if (isset($anonymous_login) && ($anonymous_login == $username))
    {
    header('HTTP/1.1 401 Unauthorized');
    die('Permission denied!');
    }

global $userref;

if (getval("save", "") != "" && enforcePostRequest(false))  //check is enforcepostrequest needed
    {
    $image_path = "";
    $profile_text = getval("profile_bio", "");
    if ($_FILES['profile_image']['name'] != "")
        {
        $file_type = $_FILES['profile_image']['type'];
        if ($file_type == 'image/jpeg')
            {
            $image_path = get_temp_dir(false) . '/' . $userref . '_' . $_FILES['profile_image']['name'];
            $result = move_uploaded_file($_FILES['profile_image']['tmp_name'], $image_path);
            if ($result === false)
                {
                error_alert($lang["error_upload_failed"]);
                exit();
                }
            }
        else
            {
            error_alert($lang["error_not_jpeg"]);
            exit();
            }
        }
    set_user_profile($userref,$profile_text,$image_path);
    }

if (getval("delete", "") != "" && enforcePostRequest(false))  //check is enforcepostrequest needed
    {
    delete_profile_image($userref);
    }

$profile_text = get_profile_text($userref);
$profile_image = get_profile_image($userref);

include "../../include/header.php";
?>

<div class="BasicsBox">

  <h1><?php echo $lang["profile"]?></h1>
  <p><?php echo $lang["profile_introtext"];?>&nbsp;<?php render_help_link('user/profile'); ?></p>
  </p>

  <form method="post" action="<?php echo $baseurl_short?>pages/user/user_profile_edit.php" enctype="multipart/form-data">

  <?php generateFormToken("user_profile_edit"); ?>

  <div class="Question">
  <label><?php echo $lang["profile_bio"] ?></label>
  <textarea name="profile_bio" class="stdwidth" rows=7 cols=50><?php echo $profile_text ?></textarea>
  <div class="clearerleft"> </div>
  </div>

  <div class="Question">
  <label><?php echo $lang["profile_image"] ?></label>
  <input type="file" accept ="jpg,jpeg" name="profile_image" size="20">
  <div class="clearerleft"> </div>
  </div>

  <div class="QuestionSubmit">
  <label for="save"> </label>
  <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
  <div class="clearerleft"> </div>
  </div>

  <?php
if ($profile_image != "")
    {
		?>
  </p></p>
  <div class="Question">
  <label><?php echo $lang["current_profile"] ?></label>
  <img src="<?php echo $profile_image ?>" alt="Current profile image" height="200">
  <div class="clearerleft"> </div>
  </div>

  <div class="QuestionSubmit">
  <label for="delete current"> </label>
  <input name="delete" type="submit" value="&nbsp;&nbsp;<?php echo $lang["delete_current"]?>&nbsp;&nbsp;" />
  <div class="clearerleft"> </div>
  </div>
    <?php } ?>

  </form>
  </div>

</div>

<?php
include "../../include/footer.php";

