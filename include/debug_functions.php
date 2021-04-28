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

    $stringify = function($value)
        {
        if(is_bool($value))
            {
            return ($value ? "true" : "false");
            }

        return trim(preg_replace('/\s+/m', ' ', print_r($value, true)));
        };

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

        $args_str .= sprintf("\$%s = %s, ", $param->getName(), $stringify($value));
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
* 
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
* Partially implements structured data from RFC 5424 @see https://tools.ietf.org/html/rfc5424
*/
function debug_track_vars(string $ns)
    {
    /*
    SYSLOG-MSG      = HEADER SP STRUCTURED-DATA [SP MSG]

    STRUCTURED-DATA = NILVALUE / 1*SD-ELEMENT
    SD-ELEMENT      = "[" SD-ID *(SP SD-PARAM) "]"
    SD-PARAM        = PARAM-NAME "=" %d34 PARAM-VALUE %d34
    SD-ID           = SD-NAME
    PARAM-NAME      = SD-NAME
    PARAM-VALUE     = UTF-8-STRING ; characters '"', '\' and
                                 ; ']' MUST be escaped.
    SD-NAME         = 1*32PRINTUSASCII
                    ; except '=', SP, ']', %d34 (")
    */

    $pid = getmypid();
    $format = 'tracking var: [pid=%s ns="%s"][%s="%s"]';

    // For readability reasons, we show each tracked var on a new line in the debug log. If performance is badly affected,
    // we can switch to combine all tracked vars in the last SD-ELEMENT (ie [var1="value" var2="value"])
    $tracked_vars = get_tracked_vars($GLOBALS['userref'] ?? 0);
    foreach($tracked_vars as $tracked_var)
        {
        // TODO: continue work here. We need to get the value of the tracked_var from input or global scope
        debug(sprintf($format, $pid, $ns, $tracked_var, 'none'));
        }







    // return debug($msg);
    }