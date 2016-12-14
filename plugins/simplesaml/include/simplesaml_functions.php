<?php
function simplesaml_get_lib_path()
    {
    global $simplesaml_lib_path;

    $lib_path = dirname(__FILE__) . '/../lib';

    if('' == $simplesaml_lib_path)
        {
        return $lib_path;
        }

    $lib_path2 = $simplesaml_lib_path;

    if('/' == substr($lib_path2, -1))
        {
        $lib_path2 = rtrim($lib_path2, '/');
        }

    if(file_exists($lib_path2))
        {
        $lib_path = $lib_path2;
        }

    return $lib_path;
    }

function simplesaml_authenticate()
	{
	global $as,$simplesaml_sp;
    if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple($simplesaml_sp);
		}
	$as->requireAuth();
	return true;
	}
	
function simplesaml_getattributes()
	{
	global $as,$simplesaml_sp;
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple($simplesaml_sp);
		}
	$as->requireAuth();
	$attributes = $as->getAttributes();
	return $attributes;
	}
	

function simplesaml_signout()
	{
	global $baseurl, $as, $simplesaml_sp;
	if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple($simplesaml_sp);
	
		}
	if($as->isAuthenticated())
		{
		$as->logout($baseurl . "/login.php"); 
		}
	
	}
	
function simplesaml_is_authenticated()
	{
	global $as,$simplesaml_sp;
	if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
    if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple($simplesaml_sp);
		}
	if(isset($as) && $as->isAuthenticated())
		{
		return true;
		}
	return false;	
	}

function simplesaml_getauthdata($value)
	{
    if(!(file_exists(simplesaml_get_lib_path() . '/config/config.php')))
        {
        debug("simplesaml: plugin not configured.");
        return false;
        }
	global $as,$simplesaml_sp;
	if(!isset($as))
		{
		require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
		$as = new SimpleSAML_Auth_Simple($simplesaml_sp);
		}
	$as->requireAuth();
	$authdata = $as->getAuthData($value);
	return $authdata;
	}
