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

        return str_replace(PHP_EOL, "", print_r($value, true));
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