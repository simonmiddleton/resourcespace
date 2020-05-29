<?php
#
# winauth setup page
#

include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

$plugin_name = 'winauth';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}
$plugin_page_heading = $lang['winauth_title'];
	

$page_def[] = config_add_html($lang['winauth_info']);
$page_def[] = config_add_boolean_select('winauth_enable',$lang['winauth_enable']);
$page_def[] = config_add_text_list_input('winauth_domains', $lang['winauth_domains']);
$page_def[] = config_add_boolean_select('winauth_prefer_normal',$lang['winauth_prefer_normal']);

$testhtml = "<script>
			function winauth_test()
				{
				jQuery.ajax({
				url: '" . $baseurl_short . "plugins/winauth/pages/secure/test.php',
				}).done(function(data) {
				styledalert('',data);
			  });
			  }
			</script>";
$testhtml .= "<div class='Question' id='question_test'>
		      <label for='test'>" . $lang['winauth_test'] . "</label>    
		      <input name='test' type='submit' value='&nbsp;&nbsp;" . $lang['winauth_test'] . "&nbsp;&nbsp;' onClick='winauth_test();return false;'>
		      </div>
		    <div class='clearerleft'></div>";
$page_def[] = config_add_html($testhtml);


// Do the page generation ritual -- don't change this section.
$upload_status = config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, $upload_status, $plugin_page_heading);
include '../../../include/footer.php';
