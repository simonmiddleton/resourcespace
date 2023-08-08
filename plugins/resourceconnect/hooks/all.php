<?php

# Check access keys
function HookResourceconnectAllCheck_access_key($resource,$key)
    {
    # Generate access key and check that the key is correct for this resource.
    global $scramble_key;
    $access_key=md5("resourceconnect" . $scramble_key);

    # Strip out the username if it has been passed.
    if (strpos($key,"-")!==false)
    {
    $s=explode("-",$key);
    $key=end($s);
    }

    if ($key !== substr(md5($access_key . $resource),0,10)) 
        {
        debug("resourceconnect: invalid key $key when requesting resource $resource");
        debug("resourceconnect: expecting " . substr(md5($access_key . $resource),0,10) . " for access_key $access_key");
        return false; # Invalid access key. Fall back to user logins.
        }

    global $resourceconnect_user; # Which user to use for remote access?
    $user_select_sql = new PreparedStatementQuery();
    $user_select_sql->sql = "u.ref = ?";
    $user_select_sql->parameters = ["i",$resourceconnect_user];
    $user_data = validate_user($user_select_sql);
    if(!is_array($user_data) || !isset($user_data[0]))
        {
        return false;
        }
    setup_user($user_data[0]);
    
    # Set that we're being accessed via resourceconnect.
    global $is_resourceconnect;
    $is_resourceconnect=true;

    # To disable collections - not needed when accessed remotely. Don't load collection bar.
    global $usercollection;
    $usercollection = null;

    # Disable maps
    global $disable_geocoding;
    $disable_geocoding=true;

    return true;
    }

function HookResourceConnectAllInitialise()
    {
    # Work out the current affiliate
    global $lang,$language,$resourceconnect_affiliates,$baseurl,$resourceconnect_selected,$resourceconnect_this,$pagename,$collection;

    # Work out which affiliate this site is
    $resourceconnect_this="";
    for ($n=0;$n<count($resourceconnect_affiliates);$n++)           
        {
        if ($resourceconnect_affiliates[$n]["baseurl"]==$baseurl) {$resourceconnect_this=$n;break;}
        }
    if ($resourceconnect_this==="") {exit($lang["resourceconnect_error-affiliate_not_found"]);}
    
    $resourceconnect_selected=getval("resourceconnect_selected","");
    if ($resourceconnect_selected=="" || !isset($resourceconnect_affiliates[$resourceconnect_selected]))
        {
        # Not yet set, default to this site
        $resourceconnect_selected=$resourceconnect_this;
        }
#   setcookie("resourceconnect_selected",$resourceconnect_selected);
    setcookie("resourceconnect_selected",$resourceconnect_selected,0,"/",'',false,true);
    
    // Language string manipulation to warn on certain pages if necessary, e.g. where collection actions will not include remote assets
    switch ($pagename)
        {
        case "contactsheet_settings":
        ResourceConnectCollectionWarning("contactsheetintrotext",getval("ref",""));
        break;

        case "edit":
        ResourceConnectCollectionWarning("edit__multiple",getval("collection",""));
        break;
    
        case "collection_log":
        ResourceConnectCollectionWarning("collection_log__introtext",getval("ref",""));
        break;
    
        case "collection_edit_previews":
        ResourceConnectCollectionWarning("collection_edit_previews__introtext",getval("ref",""));
        break;
        
        case "search_disk_usage":
        ResourceConnectCollectionWarning("search_disk_usage__introtext",str_replace("!collection","",getval("search","")));
        break;
    
        case "collection_download":
        ResourceConnectCollectionWarning("collection_download__introtext",getval("collection",""));
        break;
        }
    
    
    
    }

function HookResourceConnectAllAfterregisterplugin($plugin = "")
    {
    if ($plugin !== "" && $plugin == "resourceconnect")
        {
        # Plugin's group access has been set to a specific group so hook("initialise"); is skipped.
        # After authenticating the user, the plugin is registered in authenticate.php so we need to initialise
        # it here to finish setting up variables for use as globals e.g. $resourceconnect_this
        HookResourceConnectAllInitialise();
        }
    }


function ResourceConnectCollectionWarning($languagestring,$collection)
    {
    global $lang;
    # Are there any remote assets?
    $c=ps_value("select count(*) value from resourceconnect_collection_resources where collection=?",array("i",$collection),0);
    if ($c>0)
        {
        # Add a warning.
        if (!isset($lang[$languagestring])) {$lang[$languagestring]="";}
        $lang[$languagestring].="<p>" . $lang["resourceconnect_collectionwarning"] . "</p>";
        }   
    }

function HookResourceConnectAllSearchfiltertop()
    {
    # Option to search affiliate systems in the basic search panel
    global $lang,$language,$resourceconnect_affiliates,$baseurl,$resourceconnect_selected;
    if (!checkperm("resourceconnect")) {return false;}
    ?>

    <div class="SearchItem ResourceConnectSearch"><?php echo $lang["resourceconnect_search_database"];?>&nbsp;<a href="#" onClick="styledalert('<?php echo $lang["resourceconnect_search_database"] ?>','<?php echo $lang["resourceconnect_search_info"] ?>');" title="<?php echo $lang["resourceconnect_search_info"] ?>"><i class="fa fa-info-circle"></i></a><br />
    <select class="SearchWidth" name="resourceconnect_selected">
    
    <?php for ($n=0;$n<count($resourceconnect_affiliates);$n++)
        {
        ?>
        <option value="<?php echo $n ?>" <?php if ($resourceconnect_selected==$n) { ?>selected<?php } ?>><?php echo i18n_get_translated($resourceconnect_affiliates[$n]["name"]) ?></option>
        <?php       
        }
    ?>
    </select>
    </div>
    <?php
    }


function HookResourceConnectAllGenerate_collection_access_key($collection,$k,$userref,$feedback,$email,$access,$expires)
    {
    # When sharing externally, add the external access key to an empty row if the collection is empty, so the key still validates.
    $c=ps_value("select count(*) value from collection_resource where collection=?",array("i",$collection), 0);
    if ($c>0) {return false;} # Contains resources, key already present
    
    $sql="insert into external_access_keys(resource,access_key,collection,user,request_feedback,email,date,access,expires) values (-1,?,?,?,?,?,now(),?,";
    $params=array("s",$k,"i",$collection,"i",$userref,"i",$feedback,"s",$email,"i",$access);

    if ($expires=="") 
            {
            $sql.="null";
            }
    else    
            {
            $sql.="?";
            $params[]="s";$params[]=$expires;
            }
    $sql.=")";
    ps_query($sql,$params);
    }

function HookResourceconnectAllGenerateurl($url)
    {
    # Always use complete URLs when accessing a remote system. This ensures the user stays on the affiliate system and doesn't get diverted back to the base system.
    global $baseurl,$baseurl_short,$pagename,$resourceconnect_fullredir_pages;
    
    if (!in_array($pagename,$resourceconnect_fullredir_pages)) {return $url;} # Only fire for certain pages as needed.
    
    # Trim off the short base URL if it's been set, use $baseurl instead
    if (substr($url,0,strlen($baseurl_short))==$baseurl_short) {$url=substr($url,strlen($baseurl_short));$url=$baseurl . "/" . $url;}
    return ($url);
    }


    /**
     * This functions checks for the existence of global var $userpermissions and sets it to empty array if it doesn't already exist
     * 
     * Used in      */

    function HookResourceconnectAllModifyUserPermissions()
        {
        global $userpermissions;
        $userpermissions = (isset($userpermissions)) ?  $userpermissions : array();
        }
    
    /**
     * This function is called from include/collection_functions.php line 169
     * 
     * When ResourceConnect is enabled the function returns resource ids for resources in a collection. 
     * The resources may be from a local or remote instance of ResourceConnect
     * 
     * @param   array   $params     array containing function parameters $param[0] = collection id
     * @return  array               array containing resource ids for resources (both local an remote) in a collection
     * 
     */

    function HookResourceconnectAllreplace_get_collection_resources($collection)
        {
           
        $sql = "
    
    SELECT 
        value
    FROM  
    (
    SELECT 
        resource AS value 
    FROM 
        collection_resource 
    WHERE 
        collection=?
    ORDER BY 
        sortorder asc, 
        date_added desc, 
        resource desc
    ) AS MAIN
    UNION ALL
    (
    SELECT
        ref AS value
    FROM 
        resourceconnect_collection_resources 
    WHERE 
        collection=? 
    )
    ";
    
        return ps_array($sql,array("i",$collection,"i",$collection));
    
        }  

function HookResourceconnectAllUserdisplay($log)
    {
    // Better rendering for ResourceConnect rows in log.
    if (strpos($log["access_key"],"-")===false) {return false;}
    $s=explode("-",$log["access_key"]);

    echo "<strong>" . htmlspecialchars($s[0]) . "</strong> remotely accessing via " . htmlspecialchars($log["username"] . " user");

    return true;
    }  

/*
function HookResourceConnectAllAdvancedsearchlink()
    {
    global $resourceconnect_selected,$resourceconnect_this;
    if (!checkperm("resourceconnect")) {return false;}

    # Hide 'advanced search' link when current affiliate not selected.
    return ($resourceconnect_selected!=$resourceconnect_this);
        
    }
*/
function HookResourceConnectAllAftersearchimg($resource, $img_url="")
    {
        
    // If function called from preview image code with just resource id then return
    if (!is_array($resource))
        {
        return;
        }

    /** 
     * If query of local collection from remote location via remote_results.php, or
     * image data contains ref and the ref is negative, then query of local collection containing remote resource, or
     * resource identified as being from remote source
     * then display overlay icon */    
    if ((getval("affiliatename", "") != "") || (isset($resource["ref"]) && $resource["ref"] < 1) || isset($resource["remote"]))
        {
        list($width,$height,$margin) = calculate_image_display($resource, $img_url);
       
        $margin = (is_numeric($margin)) ? $margin . "px" : $margin;
        // ResourcePanel width - ResourcePanel margin - image width 
        $right = ($height > $width) ?  "right:" . (175 - 14 - intval($width)) . "px" : "";
        
        echo "<i style=\"margin-top:$margin;$right\" class=\"resourceconnect-link overlay-link fas fa-link\"></i>";
        }
    }
function HookResourceconnectAllGetResourcesToCheck($collection)
    {
    /* if resourceconnect is enabled there may be
        1/ a collection with just remote resources 
        2/ a collection containing a mix of local and remote resources 
        access key check only relevant for local resources therefore retrieve local resources only
    */   
    global $userrequestmode;
    if(is_array($collection) && isset($collection["ref"]))
        {
        $collection = $collection["ref"];
        }
    # retrieve only local resources from collection for access key validation
    $resources = ps_array('SELECT resource AS value FROM collection_resource WHERE collection = ?',array("i",$collection));    
    
    return $resources;
    }


function HookResourceconnectAllCountresult($collection,$count)
	{
	return $count+ps_value("select count(*) value from resourceconnect_collection_resources where collection=?",array("i",$collection),0);
	}

function HookResourceConnectAllgetRemoteResources($collection)
    {
    return count(ps_array("SELECT ref AS value FROM resourceconnect_collection_resources WHERE collection=?",array("i",$collection)));
    }

function HookResourceConnectAllrenderadditionalthumbattributes($resource)
    {
    if($resource['ref'] == -87412 && !empty($resource['source_ref']))
        {
        echo "data-identifier='".htmlspecialchars($resource['source_ref'])."'";
        }
    elseif($resource['ref'] == -87412 && empty($resource['source_ref']))
        {
        echo "data-identifier='".htmlspecialchars($resource['ref_tab'])."'";
        }
    }  

    function HookResourceConnectAllaftercopycollection($copied,$current)
    {   
        $copied_rc_collection=ps_query("SELECT ref FROM resourceconnect_collection_resources WHERE collection=?",array("i",$copied),"");
        #put all the copied collection records in
        foreach($copied_rc_collection as $col_resource)
        {
            $copied_rc_collection=ps_query("SELECT title, thumb, large_thumb, xl_thumb, url, source_ref FROM resourceconnect_collection_resources WHERE ref=?",
            array("i",$col_resource['ref']),"");
                    
            # Add to collection
            $params = [
                'i', $current,
                's', $copied_rc_collection[0]['title'],
                's', $copied_rc_collection[0]['thumb'],
                's', $copied_rc_collection[0]['large_thumb'],
                's', $copied_rc_collection[0]['xl_thumb'],
                's', $copied_rc_collection[0]['url'],
                'i', $copied_rc_collection[0]['source_ref']
            ];
            
            ps_query("INSERT INTO resourceconnect_collection_resources (collection,title,thumb,large_thumb,xl_thumb,url,source_ref) VALUES (?,?,?,?,?,?,?)", $params);
    
        }
    }

function HookResourceConnectAllListviewcolumnid($result,$n)
    {   
    return $result[$n]["ref"] == -87412 ? $result[$n]["source_ref"] : false;
    }