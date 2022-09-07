<?php
command_line_only();


include_once __DIR__ . '/../../include/login_functions.php';

// Set up
$password_hash_info = [
    'algo' => PASSWORD_BCRYPT,
    'options' => ['cost' => 5]
];

$test_user_name = 'test_10300_user';
$plaintext_pass = 'some Super 5ecure-password';
$RS_madeup_pass = "RS{$test_user_name}{$plaintext_pass}";
$pass_data = ['username' => $test_user_name];

$pass_hash_v1 = md5($RS_madeup_pass);
$pass_hash_v2 = hash('sha256', $pass_hash_v1);
$pass_hash_v3 = rs_password_hash($RS_madeup_pass);
$pass_hmac_v3 = hash_hmac('sha256', $RS_madeup_pass, $scramble_key);
// End of set up


// Hash a plain text password
$rs_password_hash = rs_password_hash($plaintext_pass);
$rs_password_hmac = hash_hmac('sha256', $plaintext_pass, $scramble_key);
if(!($rs_password_hash !== false && password_verify($rs_password_hmac , $rs_password_hash)))
    {
    echo 'Hash plain text password - ';
    return false;
    }


// User password is not hashed at all (v0 - stored in plain text in the DB)
if(!rs_password_verify($plaintext_pass, $plaintext_pass, $pass_data))
    {
    echo 'Verify password hash v0 (plain text) - ';
    return false;
    }


// User password is MD5 hashed (v1 - MD5 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v1, $pass_data))
    {
    echo 'Verify password hash v1 (MD5) - ';
    return false;
    }


// User password is SHA256 hashed (v2 - SHA256 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v2, $pass_data))
    {
    echo 'Verify password hash v2 (SHA256) - ';
    return false;
    }


// User password is hashed based on config (v3 - stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v3, $pass_data))
    {
    echo 'Verify password hash v3 - ';
    return false;
    }


// User provided a bad password
if(rs_password_verify('some bad password', $pass_hash_v3, $pass_data))
    {
    echo 'Verify bad password - ';
    return false;
    }


// User provided a password hash - by default this should return FALSE
if(rs_password_verify($pass_hash_v3, $pass_hash_v3, $pass_data))
    {
    echo 'Verify password when user input hash - ';
    return false;
    }

// User provided an old password hash - by default this should return FALSE
if(rs_password_verify($pass_hash_v2, $pass_hash_v2, $pass_data))
    {
    echo 'Verify password when user input old v2 hash - ';
    return false;
    }


// User provided a password hash - in a login context where we can impersonate a user (ie using the hash) - should return TRUE
$extra_pass_data = ['impersonate_user' => true];
if(!rs_password_verify($pass_hash_v3, $pass_hash_v3, array_merge($pass_data, $extra_pass_data)))
    {
    echo 'Verify password when user input hash (impersonating user) - ';
    return false;
    }

// User provided a plain text password - in a login context where we can impersonate a user (ie using the hash) - should return TRUE
if(!rs_password_verify($plaintext_pass, $plaintext_pass, array_merge($pass_data, $extra_pass_data)))
    {
    echo 'Verify password when user input plain text password (impersonating user) - ';
    return false;
    }

// Security Log Tests
// Create user
$user_ref = get_user_by_username($test_user_name);
if (!$user_ref){$user_ref = new_user($test_user_name,2);}

ps_query(" UPDATE user 
            SET 
                `password`='$RS_madeup_pass',
                `approved`=1,
                `login_tries`=0,
                `login_last_try`=now()
            WHERE `ref`=?;",
            ["i",$user_ref]);

// Incorrect Password
$logs = test_security_logs_on_login($test_user_name,'some_wrong_password','Xl');
if ($logs < 1)
    {
    echo "Incorrect password attempt not logged in activity log - ";
    return false;
    }

// Disabled User
ps_query(" UPDATE user 
            SET   `approved`=2 
            WHERE `ref`=?;",
            ["i",$user_ref]);
unset($udata_cache);

$logs = test_security_logs_on_login($test_user_name,$RS_madeup_pass,'Xl','Account Disabled');
if ($logs < 1)
    {
    echo "Disabled user login attempt not logged in activity log - ";
    return false;
    }

// Expired User

ps_query(" UPDATE user 
            SET   `approved`=1,
                  `account_expires`=NOW() - INTERVAL 1 DAY
            WHERE `ref`=?;",
            ["i",$user_ref]);
unset($udata_cache);

$logs = test_security_logs_on_login($test_user_name,$RS_madeup_pass,'Xl','Account Expired');
if ($logs < 1)
    {
    echo "Expired user login attempt not logged in activity log - ";
    return false;
    }

// Exceeded Max Login Attempts

ps_query(" UPDATE user 
            SET   `login_tries`=?,
                  `login_last_try`=NOW()
            WHERE `ref`=?;",
    [
        "i",$max_login_attempts_per_username + 10,
        "i",$user_ref
    ]);

unset($udata_cache);

$logs = test_security_logs_on_login($test_user_name,$RS_madeup_pass,'Xl');
if ($logs < 1)
    {
    echo "Locked out user login attempt not logged in activity log - ";
    return false;
    }

// IP Lockout

ps_query(" UPDATE user 
            SET   `login_tries`=0
            WHERE `ref`=?;",
            ["i",$user_ref]);
unset($udata_cache);

$ip = get_ip();

ps_query("delete from ip_lockout where ip=?",array("s",$ip));
ps_query("insert into ip_lockout (ip,tries,last_try) values (?,?,now())",array("s",$ip,"i",$max_login_attempts_per_ip + 10));

$logs = test_security_logs_on_login($test_user_name,$RS_madeup_pass,'Xl');

ps_query("delete from ip_lockout where ip=?",array("s",$ip));
if ($logs < 1)
{
echo "Locked out IP login attempt not logged in activity log - ";
return false;
}

// Invalid Username
$bad_user_name = 'Some_bad_user_name';

$logs = test_security_logs_on_login($bad_user_name,$RS_madeup_pass,'Xl');
if ($logs < 1)
    {
    echo "Locked out user user login attempt not logged in activity log - ";
    return false;
    }

// End Security Log Tests

// get_activity_log()
$where_statements = array( // Where Statements
        "activity_log" => 
            "activity_log.remote_table='user' 
                AND activity_log.remote_ref={$user_ref} 
                AND ",
        "resource_log" => "",
        "collection_log" => ""
);

if(count(get_activity_log('sorry, your login',0,0,$where_statements,"user",false))==0 
    || count(get_activity_log('disabled',0,0,$where_statements,"user",false))==0
    || count(get_activity_log('expired',0,0,$where_statements,"user",false))==0)
    {
    echo "get_activity_log() did not return the expected values";
    return false;
    }

// Tear down
unset($test_user_name, $plaintext_pass, $RS_madeup_pass, $pass_hash_v1, $pass_hash_v2, $pass_hash_v3, $pass_hmac_v3);
unset($rs_password_hash, $rs_password_hmac, $pass_data, $extra_pass_data);

return true;

/**
 * Count Logs in activity log before and after a login attempt.
 * 
 * The log can be filtered by log code and note
 *
 * @param  string $test_user_name   Username to test, this can be a username that is not in the database.
 *                                  For missing usernames the logs are not limited by user.
 * @param  string $password         Password to use for login attempt, this can be the wrong password to test invalid logins.
 * @param  string $log_code         Log code to filter activity logs.
 * @param  string $note             Fuzzy search will be used if this is provided.
 * @return int                      The number of matching logs that have been added during the login process.
 */
function test_security_logs_on_login($test_user_name,$password,$log_code='',$note='')
    {
    $user_ref = get_user_by_username($test_user_name);

    $sql = "SELECT count(*) value FROM activity_log";
    $sql_where_clauses  = array();
    $sql_params         = array();

    if ($user_ref !== false)
        {
        $sql_where_clauses[] = "`user` = ?";
        $sql_params = array_merge($sql_params,["i",$user_ref]);
        }
    if($log_code != '')
        {
        $sql_where_clauses[] .= "`log_code` = ?";
        $sql_params = array_merge($sql_params,["s",$log_code]);
        }
    if($note     != '')      
        {
        $sql_where_clauses[] .= "`note` like ?";
        $sql_params = array_merge($sql_params,["s","%{$note}%"]);
        }

    if(count($sql_where_clauses)>0)
        {
        $sql .= " WHERE " . implode(" AND ",$sql_where_clauses);
        }

    $logs_before = ps_value($sql,$sql_params,0);
    perform_login($test_user_name,$password);
    $logs_after = ps_value($sql,$sql_params,0);
    
    return $logs_after - $logs_before;
    }