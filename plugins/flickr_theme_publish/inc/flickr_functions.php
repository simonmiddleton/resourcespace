<?php

function sync_flickr($search,$new_only=false,$photoset=0,$photoset_name="",$private=0)
	{
	# For the resources matching $search, synchronise with Flickr.
	
	global $flickr,$flickr_api_key, $flickr_token, $flickr_caption_field, $flickr_keywords_field, $flickr_prefix_id_title, $lang, $flickr_scale_up, $flickr_nice_progress,$flickr_default_size,$flickr_alt_image_sizes;
			
	$results=do_search($search);
	
	if($flickr_nice_progress)
        {
		$results_processed=0;
		$results_new_publish=0;
		$results_no_publish=0;
		$results_update_publish=0;
        }
	
	foreach ($results as $result)
		{
		global $flickr, $view_title_field;

		# Fetch some resource details.
		$title=i18n_get_translated($result["field" . $view_title_field]);
		$description=sql_value("select value from resource_data where resource_type_field=$flickr_caption_field and resource='" . $result["ref"] . "'","");
		$keywords=sql_value("select value from resource_data where resource_type_field=$flickr_keywords_field and resource='" . $result["ref"] . "'","");
		$photoid=sql_value("select flickr_photo_id value from resource where ref='" . $result["ref"] . "'","");
		if($flickr_nice_progress)
            {
			$nice_title=$result["ref"]." - ".$title;
            }
		
		# Prefix ID to title?
		if ($flickr_prefix_id_title)
            {
			$title=$result["ref"] . " - " . $title;
            }
			
		if (!$new_only || $photoid=="")
            {
			// Output: Processing resource...
			if(!$flickr_nice_progress)
                {
                echo "<li>" . $lang["processing"] . ": '" . $title . "'\n";
                }
			else
                {
                flickr_update_progress_file("photo ".$nice_title);
                }
			if(strtolower($flickr_default_size)=="original")
                {
                $flickr_default_size="";
                }
			$im=get_resource_path($result["ref"],true,$flickr_default_size,false,"jpg");
			if(!file_exists($im) && $flickr_scale_up)
				{
				foreach($flickr_alt_image_sizes as $flickr_alt_image_size)
					{
					if(strtolower($flickr_alt_image_size)=="original")
                        {
                        $flickr_alt_image_size="";
                        }
					$im=get_resource_path($resource,true,$flickr_alt_image_size,false);
					if(file_exists($im))
						{
						break;
						}
					}
				}
			if(!file_exists($im))
				{
				// Output: No suitable upload...
				if(!$flickr_nice_progress){echo "<li>" . $lang["flickr-problem-finding-upload"];}
				else
					{
					$results_no_publish++;
					$results_processed++;
					flickr_update_progress_file("no publish ".$nice_title." | processed=".$results_processed." new_publish=".$results_new_publish." no_publish=".$results_no_publish." update_meta=".$results_update_publish);
					}
				continue;
				}
	
			# If replacing, add the photo ID of the photo to replace.
			if ($photoid != "")
                {
				// Output: Already published - updating metadata...
				if(!$flickr_nice_progress)
                    {
                    echo "<li>" . str_replace("%photoid", $photoid, $lang["updating_metadata_for_existing_photoid"]);
                    }
				else
                    {
                    flickr_update_progress_file("updating ".$nice_title);
                    }
				
				# Also resubmit title, description and keywords.
                $flickr->call("flickr.photos.setTags",array("photo_id"=>$photoid, "tags"=>$keywords));
                $flickr->call("flickr.photos.setMeta",array("photo_id"=>$photoid, "title"=>$title, "description"=>$description));
                
			
				if($flickr_nice_progress)
                    {
					$results_update_publish++;
					$results_processed++;
					flickr_update_progress_file("updated ".$nice_title." | processed=".$results_processed." new_publish=".$results_new_publish." no_publish=".$results_no_publish." update_meta=".$results_update_publish);
                    }
                }
			else
                {
                # New uploads only. Send the photo file.
				// Output: Publishing new resource...
				if(!$flickr_nice_progress){echo "<li>" . str_replace("%photoid", $title, $lang["flickr_new_upload"]);}
				else{flickr_update_progress_file("adding ".$nice_title);}
				
                $photoid = $flickr->sync_upload($im, $title, ($description != "" ? $description : null), ($keywords != "" ? $keywords : null), false);
                
				if(!$flickr_nice_progress)
                    {
                    echo "<li>" . str_replace("%photoid", $photoid, $lang["photo-uploaded"]);
                    }
				else
                    {
					$results_new_publish++;
					$results_processed++;
					flickr_update_progress_file("added ".$nice_title." | processed=".$results_processed." new_publish=".$results_new_publish." no_publish=".$results_no_publish." update_meta=".$results_update_publish);
                    }

				# Update Flickr tag ID
				sql_query("update resource set flickr_photo_id='" . escape_check($photoid) . "' where ref='" . $result["ref"] . "'");
                }

			$created_new_photoset=false;
            if ($photoset==0)
                {
                # Photoset must be created.
                $response = $flickr->call("flickr.photosets.create",array("title"=>$photoset_name, "primary_photo_id"=>$photoid));
                $photoset = $response["photoset"]["id"];				
                
                // Output: New photoset created...
                if(!$flickr_nice_progress)
                    {
                    echo "<li>" . str_replace(array("%photoset_name", "%photoset"), array($photoset_name, $photoset), $lang["created-new-photoset"]);
                    }
                else
                    {
                    flickr_update_progress_file("new photoset ".$photoset_name);
                    }
                $created_new_photoset=true;
                }

			# Add to photoset
			if (!$created_new_photoset)
                {
                # If we've just created a photoset then this will already be present within it as the primary photo (added during the create photoset request).
				 $flickr->call("flickr.photosets.addPhoto",array("photoset_id"=>$photoset, "photo_id"=>$photoid));
				// Output: Added new upload to photoset...
				if(!$flickr_nice_progress)
                    {
                    echo "<li>" . str_replace(array("%photoid", "%photoset"), array($photoid, $photoset), $lang["added-photo-to-photoset"]);
                    }
				else
                    {
                    flickr_update_progress_file("adding photo to photoset ".$nice_title);
                    }
				}
						
			# Set permissions
			// Output: Updating permissions...
			if(!$flickr_nice_progress)
                {
                echo "<li>" . str_replace("%permission", $private==0 ? $lang["flickr_public"] : $lang["flickr_private"], $lang["setting-permissions"]);
                }
			else
                {
				$perm=$private==0 ? $lang["flickr_public"] : $lang["flickr_private"];
				flickr_update_progress_file("permissions ".$perm);
                }
			$flickr->call("flickr.photos.setPerms",array("photo_id"=>$photoid, "is_public"=>($private==0?1:0),"is_friend"=>0,"is_family"=>0,"perm_comment"=>0,"perm_addmetadata"=>0));
		}
        }
	// Output: Done with all requests...
	if(!$flickr_nice_progress)
        {
        echo "<li>" . $lang["done"];
        }
	else
        {
		flickr_update_progress_file($lang["done"]." | processed=".$results_processed." new_publish=".$results_new_publish." no_publish=".$results_no_publish." update_meta=".$results_update_publish);
        }
    }
	
function flickr_update_progress_file($note)
    {
	global $progress_file;
	$fp = fopen($progress_file, 'w');		
	$filedata=$note;
	fwrite($fp, $filedata);
	fclose($fp);
    }

function flickr_get_access_token($userref,$fromrequest=false)
    {
	global $flickr, $flickr_api_key,$flickr_api_secret,$flickr_token,$lang,$theme,$baseurl,$last_xml;
	
	$flickr_tokens=sql_query("select flickr_token, flickr_token_secret from user where ref='$userref'");
        
	if(isset($flickr_tokens[0]) && $flickr_tokens[0]["flickr_token"] != "" && $flickr_tokens[0]["flickr_token_secret"] != "")
        {
        debug("flickr_theme_publish - using existing user Flickr access token");
        $OauthToken = $flickr_tokens[0]["flickr_token"];
        $OauthSecretToken = $flickr_tokens[0]["flickr_token_secret"];
        $flickr->setOauthToken ($flickr_tokens[0]['flickr_token'], $flickr_tokens[0]['flickr_token_secret']);
        debug("flickr_theme_publish: OAuth token: " . $flickr_tokens[0]['flickr_token']);
        debug("flickr_theme_publish: OAuth secret token: " . $flickr_tokens[0]['flickr_token_secret']);
        
        if(!$flickr->auth_oauth_checkToken())
            {
            debug("flickr_theme_publish - access token invalid. Deleting");
            flickr_update_tokens($userref,"","");
            flickr_get_request_token("write");
            exit("ERROR - unable to get token for Flickr communication, please reload the page");    
            }
        }
    elseif($fromrequest)
        {
        $flickr->getAccessToken();
        $OauthToken = $flickr->getOauthToken();
        $OauthSecretToken = $flickr->getOauthSecretToken();        
        flickr_update_tokens($userref,$OauthToken,$OauthSecretToken);
        debug("flickr_theme_publish - tokens updated");
        }
    else
        {
        debug("flickr_theme_publish - redirect to obtain new request token");
        flickr_get_request_token("write");
        exit();
        }
    
	return array("flickr_token"=>$OauthToken,"flickr_token_secret"=>$OauthSecretToken);
    }

function flickr_get_photoset()
    {
	global $flickr, $flickr_api_key,$flickr_token,$last_xml,$theme;

	# Make sure a photoset exists for this theme
	$psetinfo = $flickr->call ("flickr.photosets.getList",array());
    $photosets = $psetinfo["photosets"]["photoset"];
	
    # Look for the name of the current collection.
	$photoset_name=sql_value("select name value from collection where ref='$theme'","");
    $photosetid=0;
    foreach($photosets as $photoset)
        {
        if($photoset["title"] == $photoset_name)
            {
            # Name already exists. Just use this photoset ID.
            $photosetid=$photoset["id"];    
            }
        }
        
	$photoset_array=array($photoset_name,$photosetid);
	return $photoset_array;
    }


function flickr_update_tokens($userref = 0, $OauthToken,$OauthSecretToken)
    {
    if((string)(int)$userref != $userref)
        {
        return false;
        }
    sql_query("update user set flickr_token='" . escape_check($OauthToken) . "', flickr_token_secret='" . escape_check($OauthSecretToken)  . "' where ref='$userref'");
    return true;
    }

function flickr_get_request_token($access)
    {
    global $flickr,$baseurl;
    $callback_url = $baseurl . $_SERVER["SCRIPT_NAME"] . "?" . $_SERVER["QUERY_STRING"];
    debug("flickr_theme_publish -  requesting authorisation. Callback URL: " . $callback_url);
    $flickr->getRequestToken($callback_url, $access);
    }
