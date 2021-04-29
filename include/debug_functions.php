<?php

	function check_debug_log_override()
		{
		global $debug_log_override, $userref;

		if (isset($debug_log_override) || !isset($userref))
			{
			return;
			}

		$debug_log_override = false;

		$debug_user = get_sysvar('debug_override_user','');
		$debug_expires = get_sysvar('debug_override_expires','');

		if ($debug_user == "" || $debug_expires == "")
			{
			return;
			}

		if ($debug_expires < time())
			{
			sql_query("DELETE FROM sysvars WHERE name='debug_override_user' OR name='debug_override_expires'");
			return;
			}

		if ($debug_user == -1 || $debug_user == $userref)
			{
			$debug_log_override = true;
			}
		}

	function create_debug_log_override($debug_user = -1, $debug_expires = 60)
		{
		sql_query("DELETE FROM sysvars WHERE name='debug_override_user' OR name='debug_override_expires'");
		$debug_expires += time();
		$debug_user_escaped = escape_check($debug_user);
		$debug_expires_escaped = escape_check($debug_expires);
		sql_query("INSERT INTO sysvars VALUES ('debug_override_user','{$debug_user_escaped}'), ('debug_override_expires','{$debug_expires_escaped}')");
		}


/**
* Debug called function and its arguments
* 
* The best way to use this function is to call it on the first line of a function definition:
* 
* function some_test($required, $num, $optional_bool = false)
*     {
*     debug_function_call("some_test", func_get_args());
* 
*     echo "called some_test" . PHP_EOL;
* 
*     return;
*     }
* 
* @param string $name The function name
* @param array $args The "runtime" args
* 
* @return boolean|void @see debug()
*/
function debug_function_call($name, array $args)
    {
    global $debug_log, $debug_log_override;
    if(!$debug_log && !$debug_log_override)
        {
        return false;
        }

    $args_str = "";
    $fct = new ReflectionFunction($name);
    foreach($fct->getParameters() as $param)
        {
        $value = null;

        if(!$param->isOptional() && isset($args[$param->getPosition()]))
            {
            $value = $args[$param->getPosition()];
            }
        else if($param->isOptional() && isset($args[$param->getPosition()]))
            {
            $value = $args[$param->getPosition()];
            }
        else if($param->isOptional() && $param->isDefaultValueAvailable())
            {
            $value = $param->getDefaultValue();
            }

        $args_str .= sprintf("\$%s = %s, ", $param->getName(), debug_stringify($value));
        }
    $args_str = rtrim($args_str, ", ");

    return debug("{$name}( {$args_str} );");
    }


/**
* Clear sysvar entries used for tracking variables in ResourceSpace
* 
* @param array $users List of user IDs
* 
*/
function clear_tracking_vars_info(array $users)
    {
    foreach($users as $uref)
        {
        if(!is_numeric($uref))
            {
            continue;
            }

        set_sysvar("track_var_{$uref}", null);
        set_sysvar("track_var_{$uref}_duration", null);
        set_sysvar("track_var_{$uref}_start_datetime", null);
        }
    }


/**
* Stringify variables for use in the debug log. This is used more as fallback to json_encode() failing to maintain quick
* readability of the logs.
* 
* @param mixed $value Any value that needs stringified
* 
* @return string
*/
function debug_stringify($value)
    {
    if(is_bool($value))
        {
        return ($value ? 'true' : 'false');
        }

    return trim(preg_replace('/\s+/m', ' ', print_r($value, true)));
    }


/**
* Check if ResourceSpace is still tracking variables for debug purposes.
* 
* @uses get_sysvar() to return global scope data.
* 
* @param int $user User ID for which we check if tracking vars is active
* 
* @return boolean
*/
function is_tracking_vars_active(int $user)
    {
    $duration = (int) get_sysvar("track_var_{$user}_duration", 0) ?? 0;
    $start = new DateTime(get_sysvar("track_var_{$user}_start_datetime", ''));
    $now = new DateTime();

    $diff_in_min = abs($now->getTimestamp() - $start->getTimestamp()) / 60;

    return $duration > (int) $diff_in_min;
    }


/**
* Get all tracked variables (for debug) for user
* 
* @param int $user User ID
* 
* @return array List of variable names
*/
function get_tracked_vars(int $user)
    {
    if($user <= 0)
        {
        return [];
        }

    $vars_csv = get_sysvar("track_var_{$user}", '');
    $vars_list = explode(',', $vars_csv);
    $vars_trimmed = array_map('trim', $vars_list);
    return array_filter($vars_trimmed);
    }


/**
* Debug log tracked variables (as configured in System > System console).
* 
* IMPORTANT: the debug log will contain the JSON encoded version of the tracked variable. For further analysis, just copy
* the value (ie. everything within double quotes) and prettyfi it in your IDE.
* 
* Partially implements structured data from RFC 5424 @see https://tools.ietf.org/html/rfc5424
* 
* @param string $place The place/entity this is being logged at. For functions use a position keyword (before, begin, end,
*                      after), the @ sign and then the function name (e.g end@include_plugin_config). In other cases it
*                      might be better to record the line "e.g line-59@include/db.php".
*                      IMPORTANT: it is important to keep the location format consistent to help developers filter faster
*                      when processing the debug log for tracked vars.
* @param array $vars   Defined variables within the scope the function was called from
* 
*/
function debug_track_vars(string $place, array $vars)
    {
    $pid = getmypid() ?: 'Undefined';
    $format = 'tracking var: [pid=%1$s place="%2$s"][%3$s="%4$s"]';
    $format_json_err = 'tracking var: [pid=%1$s place="%2$s"][error] JSON error "%3$s" when $%4$s = %5$s';

    // For readability reasons, we show each tracked var on a new line in the debug log. If performance is badly affected,
    // we can switch to combine all tracked vars in the last SD-ELEMENT (ie [var1="value" var2="value"])
    $tracked_vars = get_tracked_vars($GLOBALS['userref'] ?? 0);
    foreach($tracked_vars as $tracked_var)
        {
        if(!isset($vars[$tracked_var]))
            {
            continue;
            }

        // JSON encode the tracked variables' value. If it fails, attempt to log this in the debug log.
        $tracked_var_value = json_encode($vars[$tracked_var], JSON_NUMERIC_CHECK);
        if(json_last_error() !== JSON_ERROR_NONE)
            {
            $json_last_error_msg = json_last_error_msg();

            debug(
                sprintf(
                    $format_json_err,
                    $pid,
                    $place,
                    json_last_error_msg(),
                    $tracked_var,
                    debug_stringify($vars[$tracked_var])
                )
            );

            continue;
            }

        debug(sprintf($format, $pid, $place, $tracked_var, $tracked_var_value));
        }
    }