<?php
include "../../../include/db.php";


global $baseurl, $wordpress_url, $wordpress_secret, $global_cookies;

if (isset($_GET["logout"]))
        {
         #blank cookie
			if ($global_cookies){
				setcookie("wordpress","",0,"/");
				setcookie("user","",0,"/");
			}
			else {
				setcookie("wordpress","",0);
				setcookie("user","",0);
				}

        header("Location: $wordpress_url/wp-login.php?action=logout");
        exit();
        }


$wordpress_user="";
$wordpress=getval("wordpress_user","");
 exit ("TEST" . htmlspecialchars($wordpress));
 if ($wordpress!="")
	{
	exit (htmlspecialchars($wordpress));
	$s=explode("|",$wordpress);
	if (count($s)==2)
			{
			$wordpress_user=$s[0];
			$hash=$s[1];
			if ($hash!=md5($wordpress_secret . $baseurl))
					{
					# Invalid hash. Redirect to Wordpress to reauthenticate.
					##header("Location: $wordpress_url?rsauth=true"); /* Redirect browser */
					exit(htmlspecialchars($wordpress_user) . " " . $hash);
					}
			}
	}
	
if ($wordpress_user!="")
        {
        # Set a  cookie and redirect back to the site.

        echo htmlspecialchars($wordpress_user);
        ##setcookie("wordpress",$wordpress_user . "|" . md5("sjCx32lLPPa2" . $wordpress_user),0,"/");

        header("Location: $wordpress_url/wp-login.php");
        }
else
        {
		header("Location: $wordpress_url?rsauth=true"); /* Redirect browser */
        exit("Wordpress authentication failed.");
        }
