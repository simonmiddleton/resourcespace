<?php
include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}

$tms_test_dsn = getval("dsn","");
$tms_test_user = getval("tmsuser","");
$tms_test_password = getval("tmspass","");
$conn=@odbc_connect($tms_test_dsn, $tms_test_user, $tms_test_password);

if(!$conn)
  {
  $error=odbc_errormsg();
  $return["result"]     = $lang["error"];
  $return["message"]    = $lang["tms_link_tms_link_failure"] . ": " . $error;
  }
else
  {
  $return["result"]     = $lang["ok"];
  $return["message"]    = $lang["tms_link_tms_link_success"];
  }

echo json_encode($return);
exit();