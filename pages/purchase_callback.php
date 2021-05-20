<?php
include "../include/db.php";

# Handle the callback from PayPal and mark the collection items as purchased.
$paypal_url_parts = parse_url($paypal_url);

# Fetch the raw IPN message sent from PayPal
$raw_ipn_post = file_get_contents('php://input');
$raw_ipn_array = explode('&', $raw_ipn_post);

$ipnPost = array();
foreach ($raw_ipn_array as $raw_entry) {
  $raw_entry = explode ('=', $raw_entry);
  if (count($raw_entry) == 2)
    $ipnPost[$raw_entry[0]] = urldecode($raw_entry[1]);
}

# Now construct a request consisting of a copy of the IPN message prefixed with 'cmd=_notify-validate'
$req = 'cmd=_notify-validate';
foreach ($ipnPost as $key => $value)
		{
		$value = urlencode($value); 
		$req .= "&$key=$value";
		}

debug("PAYPAL CALLBACK START");

# Send the request back to PayPal for verification; now uses HTTP/1.1 as required by PayPal since October 2013
# Note the use of single end-of-line markers and final double end-of-line marker
$header = "";
$header .= "POST /cgi-bin/webscr HTTP/1.1\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Host: ".$paypal_url_parts['host']."\r\n"; 
$header .= "Connection: close\r\n\r\n";

# Communicate via socket
$fp = fsockopen ("ssl://".$paypal_url_parts['host'], 443, $errno, $errstr, 30);

# Process the validation response from PayPal
if (!$fp)
	{ // HTTP ERROR
	
	debug("PAYPAL CALLBACK HTTP ERROR=".$errno." - ".$errstr);
	
	echo "HTTP error.";
	}
else
	{

	debug("PAYPAL CALLBACK NO HTTP ERROR");


	// NO HTTP ERROR
	fputs ($fp, $header . $req);
	while (!feof($fp))
		{
		$res = fgets ($fp, 1024);		

		debug("PAYPAL CALLBACK RESPONSE=".$res);

		if (strcmp(trim($res), "VERIFIED") == 0)
			{
			echo "Verified.";
			
			// Mark these items as purchase complete
			$emailconfirmation=getvalescaped("emailconfirmation","");
			payment_set_complete(getvalescaped("custom",""),$emailconfirmation);
			
			hook("payment_complete");
			} 
		else
			{
			echo "Not verified";
			}
		}
	}

?>