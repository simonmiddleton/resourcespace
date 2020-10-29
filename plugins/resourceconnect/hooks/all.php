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

    if ($key!=substr(md5($access_key . $resource),0,10)) {return false;} # Invalid access key. Fall back to user logins.

    global $resourceconnect_user; # Which user to use for remote access?
    $userdata=validate_user("u.ref='$resourceconnect_user'");
    setup_user($userdata[0]);
    
    
    
    # Set that we're being accessed via resourceconnect.
    global $is_resourceconnect;
    $is_resourceconnect=true;

    # Disable collections - not needed when accessed remotely
    global $collections_footer; 
    $collections_footer=false;
    
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
        ResourceConnectCollectionWarning("contactsheetintrotext",getvalescaped("ref",""));
        break;

        case "edit":
        ResourceConnectCollectionWarning("edit__multiple",getvalescaped("collection",""));
        break;
    
        case "collection_log":
        ResourceConnectCollectionWarning("collection_log__introtext",getvalescaped("ref",""));
        break;
    
        case "collection_edit_previews":
        ResourceConnectCollectionWarning("collection_edit_previews__introtext",getvalescaped("ref",""));
        break;
        
        case "search_disk_usage":
        ResourceConnectCollectionWarning("search_disk_usage__introtext",str_replace("!collection","",getvalescaped("search","")));
        break;
    
        case "collection_download":
        ResourceConnectCollectionWarning("collection_download__introtext",getvalescaped("collection",""));
        break;
        }
    
    
    
    }

function ResourceConnectCollectionWarning($languagestring,$collection)
    {
    global $lang;
    # Are there any remote assets?
    $c=sql_value("select count(*) value from resourceconnect_collection_resources where collection='" . escape_check($collection) . "'",0);
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
<script>
  jQuery(document).ready(function(){
    jQuery( document ).tooltip();
  } );
  </script>
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
    $c=sql_value("select count(*) value from collection_resource where collection='$collection'",0);
    if ($c>0) {return false;} # Contains resources, key already present
    
    sql_query("insert into external_access_keys(resource,access_key,collection,user,request_feedback,email,date,access,expires) values (-1,'$k','$collection','$userref','$feedback','" . escape_check($email) . "',now(),$access," . (($expires=="")?"null":"'" . $expires . "'"). ");");
    
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
        collection='" . escape_check($collection) . "'
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
        collection='$collection'    
    )
    ";
    
        return sql_array($sql);
    
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
    $resources = sql_array('SELECT resource AS value FROM collection_resource WHERE collection = ' . escape_check($collection) . ';');    
    
    return $resources;
    }


function HookResourceconnectAllCountresult($collection,$count)
	{
	return $count+sql_value("select count(*) value from resourceconnect_collection_resources where collection='$collection'",0);

	}
