<?php
if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

/*
  
  Test.php
  --------
  Create a test database and storagedir, then run a series of tests in sequence
  A default database will be created but tests can create new fields, resource types, etc as part of testing, and those items
  utilised by later tests.

  */

include_once "../include/db.php";


$suppress_headers=true;
define ("RS_TEST_MODE",1);
$argv=preg_replace('/^(-|--|\/)/','',$argv);    // remove leading /, -- or -

if(array_search('?',$argv)!==false || array_search('help',$argv)!==false)
    {
?>

Command line paramaters:

-nosetup        Do not setup the database, connect user in current state
-noteardown     Do not drop the database once tests have completed
-help or -?     This help information
[n]...          Specific test number(s) to run
<?php
    exit;
    }

# Create an array of tests that were passed from the command line
$specific_tests=array();
foreach($argv as $arg)
    {
    if(is_numeric($arg))
        {
        array_push($specific_tests, str_pad($arg,6,'0',STR_PAD_LEFT));
        }
    }
    
function create_new_db($db_name)
    {
    global $db;
    # Create a database for testing purposes
    echo "Creating database $db_name\n";
    mysqli_query($db["read_write"], "drop database if exists `$db_name`");
    mysqli_query($db["read_write"], "create database `$db_name`");
    mysqli_query($db["read_write"], "CREATE TABLE `{$db_name}`.`sysvars`(`name` VARCHAR(50) NOT NULL, `value` TEXT NULL, PRIMARY KEY (`name`))");
    mysqli_query($db["read_write"], "INSERT INTO `{$db_name}`.`sysvars`(`name`,`value`) VALUE ('upgrade_system_level',999)");
    }

// Used to check that search results return the expected resources
function match_values( $arraya , $arrayb ) 
	{ 
    sort( $arraya ); 
    sort( $arrayb ); 
	return $arraya == $arrayb; 
	} 

$mysql_db = "rs_test_db";
$test_user_name = "admin";
$test_user_password = "admin123";
$inst_plugins = sql_query('SELECT name FROM plugins WHERE inst_version>=0 order by name');

if(array_search('nosetup',$argv)===false)
    {
    # this has to be done in its own function as it includes the config.php and don't want to scope those vars globally
    create_new_db($mysql_db);
    }
    
// Reset any odd config settings by reapplying config.default and config.new_installs.php
// Save any important settings e.g for mysql connections first 
$savedconfigs = array("mysql_db","mysql_server","mysql_server_port","mysql_username","mysql_password","read_only_db_username","read_only_db_password","imagemagick_path","ghostscript_path","exiftool_path");
foreach($savedconfigs as $savedconfig)
    {
    $saved[$savedconfig] = $$savedconfig;
    }
    
include "../include/config.default.php";
eval(file_get_contents("../include/config.new_installs.php"));
   
foreach($saved as $key => $savedsetting)    
    {
    $$key = $savedsetting;
    }
    
sql_connect();

if(array_search('nosetup',$argv)===false)
    {
    # Connect and create standard tables.
    echo "Creating default database tables...";
    ob_flush();
    check_db_structs(true);
    echo "...done\n";
    # Insert a new user and run as them.
    $u = new_user($test_user_name);
    sql_query("UPDATE `user` SET `password`='{$test_user_password }'");
    }
else
    {
    # Try to retrieve the ref of the existing user
    $u = sql_value("SELECT `ref` AS value FROM `user` WHERE `username`='{$test_user_name}'",-1);
    if ($u==-1)
        {
        die("Could not find existing '{$test_user_name}' user");
        }
    }



# Setup user
user_set_usergroup($u, 3);
$userdata = get_user($u);
setup_user($userdata);
echo "Now running as user $userref\n";
ob_flush();

# Use an alternative filestore path
if (!file_exists($storagedir))
    {
    mkdir($storagedir);
    }
$storagedir .= '/rs_test';
if (file_exists($storagedir))
    {
    // Clean up any old test directory
    rcRmdir($storagedir);
    }

mkdir($storagedir);
$storageurl .= '/rs_test';
echo "Filestore is now at $storagedir\n";

# Get a list of core tests
$core_tests = scandir("test_list");
$core_tests = array_filter($core_tests, function ($string)
    {
    global $specific_tests;
    if (substr($string,-4,4) != ".php")
        {
        return false;
        }
    if (count($specific_tests)==0)
        {
        return true;
        }
    foreach($specific_tests as $specific_test)
        {
        if (strpos($string, $specific_test)!==false)
            {
            return true;
            }
        }
    return false;
    }); # PHP files only
asort($core_tests);

$core_tests = array('test_list' => $core_tests);

# Get a list of plugin tests
$plugin_tests = array();
foreach ($inst_plugins as $plugin)
    {
    if (file_exists('../plugins/' . $plugin['name'] . '/tests'))
        {
        $plugin_tests['../plugins/' . $plugin['name'] . '/tests'] = scandir('../plugins/' . $plugin['name'] . '/tests');
        }
    }
foreach ($plugin_tests as $key => $tests)
    {
    $plugin_tests[$key] = array_filter($tests, function ($string)
        {
        global $specific_tests;
        if (substr($string,-4,4) != ".php")
            {
            return false;
            }
        if (count($specific_tests)==0)
            {
            return true;
            }
        return false;
        });
    asort($tests);
    }
	
$plugin_tests = array_filter($plugin_tests); # Remove empty sub arrays

if (!empty($plugin_tests))
    {
    $tests = array_merge($core_tests, $plugin_tests);	
    }
else
    {
    $tests = $core_tests;	
    }		

# Run tests
echo "-----\n";ob_flush();
foreach ($tests as $key => $test_stack)
    {
	foreach ($test_stack as $test)
	    {
        # ------------- RUN THE TEST ------------------------------------------------
		echo "Running test " . str_pad($test,65," ") . " ";ob_flush();
        try
            {
            $result = include $key . '/'. $test;
            }
        catch (Exception $e)
            {
            echo $e;
            $result=false;
            }
        # -------------- Did it work? -----------------------------------------------
		if ($result===false)
		    {
			echo "FAIL\n";ob_flush();
			if (isset($email_test_fails_to))
			    {
				$svnrevision=trim(shell_exec("svnversion .")); 
				send_mail($email_test_fails_to,"Test $test has failed as of r" . $svnrevision,"Hi,\n\nAs of revision " . $svnrevision. " the test '" . $test . "' is failing.\n\nThis e-mail was sent from the installation at $baseurl.");
				}
			if ($key=="test_list")
				{
				exit();	# If a core test fails cancel all other tests
				}
			else
				{
				break;	# If a plugin test fails abort tests for this plugin but continue
				}					
			}
		echo "OK\n";ob_flush();
		}
	echo "-----\n";ob_flush();
	}
echo "All tests complete.\n";

if(array_search('noteardown',$argv)===false)
    {
    # Remove database
    sql_query("drop database `$mysql_db`");
    rcRmdir($storagedir);
    }
