<?php
function HookRss2SearchResultsbottomtoolbar()
	   {
	   global $k; if ($k!=""){return false;}
	   global $baseurl, $search, $restypes, $archive, $starsearch,$lang,$username,$userref;
	   	   
	   $querystring="user=" . base64_encode($username) . "&search=" . urlencode($search) . "&restypes=" . urlencode($restypes) . "&archive=" . urlencode($archive) . "&starsearch=" . urlencode($starsearch) . "&skey=" . urlencode($skey); 
	   $private_key = get_api_key($userref);
	   // Sign the query using the private key
	   $sign=hash("sha256",$private_key . $query);
	
	
	   //$apikey=make_api_key($username,$userpassword);
      // $skey = md5($api_scramble_key.$apikey.$search.$archive);	   
	   
	   ?>
	   <div class="InpageNavLeftBlock"><a href="<?php echo $baseurl?>/plugins/rss2/pages/rssfilter.php?<?php echo $querystring ?>&sign=<?php echo urlencode($sign);?>">&nbsp;<?php echo $lang["rss_feed_for_search_filter"]; ?></a></div>
	   <?php
	   }

function HookRss2SearchRender_search_actions_add_option($options)
	{
 	global $baseurl_short, $search, $restypes, $archive, $starsearch, $lang,$username,$userref,$api_scramble_key ,$k;
    
    $c=count($options);
    
    if($k=='')
		{   
        $querystring="user=" . base64_encode($username) . "&search=" . urlencode($search) . "&restypes=" . urlencode($restypes) . "&archive=" . urlencode($archive) . "&starsearch=" . urlencode($starsearch); 
        $private_key = get_api_key($userref);
        // Sign the query using the private key
        $sign=hash("sha256",$private_key . $querystring);
        $data_attribute['url'] = $baseurl_short . "plugins/rss2/pages/rssfilter.php?" . $querystring  . "&sign=" . urlencode($sign);
        $options[$c]['value']='rss';
        $options[$c]['label']=$lang["rss_feed_for_search_filter"];
        $options[$c]['data_attr']=$data_attribute;
        $options[$c]['category']  = ACTIONGROUP_ADVANCED;
        $options[$c]['order_by']  = 500;
        return $options;
        }
}

