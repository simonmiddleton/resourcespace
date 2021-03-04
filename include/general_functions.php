<?php
#
#
# General functions, useful across the whole solution; not specific to one area
#
# PLEASE NOTE - Don't add search/resource/collection/user etc. functions here - use the separate include files.
#

/**
 * Retrieve a user-submitted parameter from the browser, via post/get/cookies in that order.
 *
 * @param  string $val              The parameter name
 * @param  string $default          A default value to return if no matching parameter was found
 * @param  boolean $force_numeric   Ensure a number is returned
 * @return string
 */
function getval($val,$default,$force_numeric=false)
    {
    # return a value from POST, GET or COOKIE (in that order), or $default if none set
    if (array_key_exists($val,$_POST)) {return ($force_numeric && !is_numeric($_POST[$val])?$default:$_POST[$val]);}
    if (array_key_exists($val,$_GET)) {return ($force_numeric && !is_numeric($_GET[$val])?$default:$_GET[$val]);}
    if (array_key_exists($val,$_COOKIE)) {return ($force_numeric && !is_numeric($_COOKIE[$val])?$default:$_COOKIE[$val]);}
    return $default;
    }

/**
* Return a value from get/post/cookie, escaped and SQL-safe
* 
* It should not be relied upon for XSS. Sanitising output should be done when needed by developer
* 
* @param string        $val
* @param string|array  $default        The fallback value if not found
* @param boolean       $force_numeric  Set to TRUE if we want only numeric values. If returned value is not numeric
*                                      the function will return the default value
* 
* @return string|array
*/
function getvalescaped($val, $default, $force_numeric = false)
    {
    $value = getval($val, $default, $force_numeric);

    if(is_array($value))
        {
        foreach($value as &$item)
            {
            $item = escape_check($item);
            }
        }
    else
        {
        $value = escape_check($value);
        }

    return $value;
    }

/**
 * Escape a value prior to using it in SQL. Only escape a string if we need to,
 * to prevent escaping an already escaped string.
 *
 * @param  string $text
 * @return string  
 */
function escape_check($text)
    {
    global $db;

    $db_connection = $db["read_write"];
    if(db_use_multiple_connection_modes() && db_get_connection_mode() == "read_only")
        {
        $db_connection = $db["read_only"];
        db_clear_connection_mode();
        }

    $text = mysqli_real_escape_string($db_connection, $text);

    # turn all \\' into \'
    while (!(strpos($text,"\\\\'")===false))
        {
        $text=str_replace("\\\\'","\\'",$text);
        }

    # Remove any backslashes that are not being used to escape single quotes.
    $text=str_replace("\\'","{bs}'",$text);
    $text=str_replace("\\n","{bs}n",$text);
    $text=str_replace("\\r","{bs}r",$text);

	if (!$GLOBALS['mysql_verbatim_queries'])
		{
		$text=str_replace("\\","",$text);
		}
		
    $text=str_replace("{bs}'","\\'",$text);            
    $text=str_replace("{bs}n","\\n",$text);            
    $text=str_replace("{bs}r","\\r",$text);  
                      
    return $text;
    }

/**
 * For comparing escape_checked strings against mysql content because	
 * just doing $text=str_replace("\\","",$text);	does not undo escape_check
 *
 * @param  mixed $text
 * @return string
 */
function unescape($text) 
    {
    # Remove any backslashes that are not being used to escape single quotes.
    $text=str_replace("\\'","\'",$text);
    $text=str_replace("\\n","\n",$text);
    $text=str_replace("\\r","\r",$text);
    $text=str_replace("\\","",$text);    
    return $text;
    }

/**
* Escape each elements' value of an array to safely use any of the values in SQL statements
* 
* @uses escape_check()
* 
* @param array $unsafe_array Array of values that should be escaped
* 
* @return array Returns an array with its values escaped for SQLi
*/
function escape_check_array_values(array $unsafe_array)
    {
    $escape_array_element = function($value)
        {
        if(is_array($value))
            {
            return escape_check_array_values($value);
            }

        return escape_check($value);
        };

    $escaped_array = array_map($escape_array_element, $unsafe_array);

    return $escaped_array;
    }


/**
* Formats a MySQL ISO date
* 
* Always use the 'wordy' style from now on as this works better internationally.
* 
* @uses offset_user_local_timezone()
* 
* @var  string   $date
* @var  boolean  $time
* @var  boolean  $wordy
* @var  boolean  $offset_tz  Set to TRUE to offset based on time zone, FALSE otherwise
* 
* @return string Returns an empty string if date not set/invalid
*/
function nicedate($date, $time = false, $wordy = true, $offset_tz = false)
    {
    global $lang, $date_d_m_y, $date_yyyy;

    if($date == '' || strtotime($date) === false)
        {
        return '';
        }

    $original_time_part = substr($date, 11, 5);
    if($offset_tz && ($original_time_part !== false || $original_time_part != ''))
        {
        $date = offset_user_local_timezone($date, 'Y-m-d H:i');
        }

    $y = substr($date, 0, 4);
    if(!$date_yyyy)
        {
        $y = substr($y, 2, 2);
        }

    if($y == "")
        {
        return "-";
        };

    $month_part = substr($date, 5, 2);
    $m = $wordy ? (@$lang["months"][$month_part - 1]) : $month_part;
    if($m == "")
        {
        return $y;
        }

    $d = substr($date, 8, 2);    
    if($d == "" || $d == "00")
        {
        return "{$m} {$y}";
        }

    $t = $time ? " @ " . substr($date, 11, 5) : "";

    if($date_d_m_y)
        {
        return $d . " " . $m . " " . $y . $t;
        }
    else
        {
        return $m . " " . $d . " " . $y . $t;
        }
    }


/**
 * Redirect to the provided URL using a HTTP header Location directive. Exits after redirect
 *
 * @param  string $url  URL to redirect to
 * @return void
 */
function redirect($url)
	{
	global $baseurl,$baseurl_short;
	if (getval("ajax","")!="")
		{
		# When redirecting from an AJAX loaded page, forward the AJAX parameter automatically so headers and footers are removed.	
		if (strpos($url,"?")!==false)
			{
			$url.="&ajax=true";
			}
		else
			{
			$url.="?ajax=true";
			}
		}
	
	if (substr($url,0,1)=="/")
		{
		# redirect to an absolute URL
		header ("Location: " . str_replace('/[\\\/]/D',"",$baseurl) . str_replace($baseurl_short,"/",$url));
		}
	else
		{	
		if(strpos($url,$baseurl)!==false)
			{
			// exit($url);	
			// Base url has already been added
			header ("Location: " . $url);	
			exit();
			}

		# redirect to a relative URL
		header ("Location: " . $baseurl . "/" . $url);
		}
	exit();
	}



/**
 * replace multiple spaces with a single space
 *
 * @param  mixed $text
 * @return string
 */
function trim_spaces($text)
    {
    while (strpos($text,"  ")!==false)
        {
        $text=str_replace("  "," ",$text);
        }
    return trim($text);
    }   
        

/**
 *  Removes whitespace from the beginning/end of all elements in an array
 *
 * @param  array $array
 * @param  string $trimchars
 * @return array
 */
function trim_array($array,$trimchars='')
    {
    if(isset($array[0]) && empty($array[0]) && !(emptyiszero($array[0]))){$unshiftblank=true;}
    $array = array_filter($array,'emptyiszero');
    $array_trimmed=array();
    $index=0;
    
    foreach($array as $el)
        {
        $el=trim($el);
        if (strlen($trimchars) > 0)
            {
            // also trim off extra characters they want gone
            $el=trim($el,$trimchars);
            }
        // Add to the returned array if there is anything left
        if (strlen($el) > 0)
            {
            $array_trimmed[$index]=$el;
            $index++;
            }
        }
    if(isset($unshiftblank)){array_unshift($array_trimmed,"");}
    return $array_trimmed;
    }


/**
 * Takes a value as returned from a check-list field type and reformats to be more display-friendly.
 *  Check-list fields have a leading comma.
 *
 * @param  string $list
 * @return string
 */
function tidylist($list)
    {
    $list=trim($list);
    if (strpos($list,",")===false) {return $list;}
    $list=explode(",",$list);
    if (trim($list[0])=="") {array_shift($list);} # remove initial comma used to identify item is a list
    $op=join(", ",trim_array($list));
    #if (strpos($op,".")!==false) {$op=str_replace(", ","<br/>",$op);}
    return $op;
    }

/**
 * Trims $text to $length if necessary. Tries to trim at a space if possible. Adds three full stops if trimmed...
 *
 * @param  string $text
 * @param  integer $length
 * @return string
 */
function tidy_trim($text,$length)
    {
    $text=trim($text);
    if (strlen($text)>$length)
        {
        $text=mb_substr($text,0,$length-3,'utf-8');
        # Trim back to the last space
        $t=strrpos($text," ");
        $c=strrpos($text,",");
        if ($c!==false) {$t=$c;}
        if ($t>5) 
            {
            $text=substr($text,0,$t);
            }
        $text=$text . "...";
        }
    return $text;
    }
    
/**
 * Returns the average length of the strings in an array
 *
 * @param  array $array
 * @return float
 */
function average_length($array)
    {
    if (count($array)==0) {return 0;}
    $total=0;
    for ($n=0;$n<count($array);$n++)
        {
        $total+=strlen(i18n_get_translated($array[$n]));
        }
    return ($total/count($array));
    }
    


/**
 * Returns a list of activity types for which we have stats data (Search, User Session etc.)
 *
 * @return array
 */
function get_stats_activity_types()
    {
    return sql_array("SELECT DISTINCT activity_type `value` FROM daily_stat ORDER BY activity_type");
    }

/**
 * Replace escaped newlines with real newlines.
 *
 * @param  string $text
 * @return string
 */
function newlines($text)
    {
    $text=str_replace("\\n","\n",$text);
    $text=str_replace("\\r","\r",$text);
    return $text;
    }


/**
 * Returns a list of all available editable site text (content). If $find is specified
 * a search is performed across page, name and text fields.
 *
 * @param  string $findpage
 * @param  string $findname
 * @param  string $findtext
 * @return array
 */
function get_all_site_text($findpage="",$findname="",$findtext="")
    {
    global $defaultlanguage,$languages,$applicationname,$storagedir,$homeanim_folder;

    $findname = trim($findname);
    $findpage = trim($findpage);
    $findtext = trim($findtext);

    $return = array();

    // en should always be included as it is the fallback language of the system
    $search_languages = array('en');

    if('en' != $defaultlanguage)
        {
        $search_languages[] = $defaultlanguage;
        }

    // When searching text, search all languages to pick up matches for languages other than the default. Add array so that default is first then we can skip adding duplicates.
    if('' != $findtext)
        {
        $search_languages = $search_languages + array_keys($languages); 
        }

        global $language, $lang; // Need to save these for later so we can revert after search
        $languagesaved=$language;
        $langsaved=$lang;
        
        foreach ($search_languages as $search_language)
            {
            # Reset $lang and include the appropriate file to search.
            $lang=array();

            # Include language file
            $searchlangfile = dirname(__FILE__)."/../languages/" . safe_file_name($search_language) . ".php";
            if(file_exists($searchlangfile))
                {
                include $searchlangfile;
                }
            include dirname(__FILE__)."/../languages/" . safe_file_name($search_language) . ".php";
            
            # Include plugin languages in reverse order as per db.php
            global $plugins;
            $language = $search_language;
            for ($n=count($plugins)-1;$n>=0;$n--)
                {        
                if (!isset($plugins[$n])) { continue; }       
                register_plugin_language($plugins[$n]);
                }       
            
            # Find language strings.
            ksort($lang);
            foreach ($lang as $key=>$text)
                {
                $pagename="";
                $s=explode("__",$key);
                if (count($s)>1) {$pagename=$s[0];$key=$s[1];}
                
                if
                    (
                    !is_array($text) # Do not support overrides for array values (used for months)... complex UI needed and very unlikely to need overrides.
                    &&
                    ($findname=="" || stripos($key,$findname)!==false)
                    &&            
                    ($findpage=="" || stripos($pagename,$findpage)!==false)
                    &&
                    ($findtext=="" || stripos($text,$findtext)!==false)
                    )
                    {
                    $testrow=array();
                    $testrow["page"]=$pagename;
                    $testrow["name"]=$key;
                    $testrow["text"]=$text;
                    $testrow["language"]=$defaultlanguage;
                    $testrow["group"]="";
                    // Make sure this isn't already set for default/another language
                    if(!in_array($testrow,$return))
                        {
                        $row["page"]=$pagename;
                        $row["name"]=$key;
                        $row["text"]=$text;
                        $row["language"]=$search_language;
                        $row["group"]="";
                        $return[]=$row;
                        }
                    }
                }
            }

        // Need to revert to saved values
        $language=$languagesaved;
        $lang=$langsaved;
        
        # If searching, also search overridden text in site_text and return that also.
        if ($findtext!="" || $findpage!="" || $findname!="")
            {
            if ($findtext!="") {$search="text like '%" . escape_check($findtext) . "%'";}
            if ($findpage!="") {$search="page like '%" . escape_check($findpage) . "%'";}         
            if ($findname!="") {$search="name like '%" . escape_check($findname) . "%'";}          
            
            $site_text=sql_query ("select * from site_text where $search");
            
            foreach ($site_text as $text)
                {
                $row["page"]=$text["page"];
                $row["name"]=$text["name"];
                $row["text"]=$text["text"];
                $row["language"]=$text["language"];
                $row["group"]=$text["specific_to_group"];
                // Make sure we dont'include the default if we have overwritten 
                $customisedtext=false;
                for($n=0;$n<count($return);$n++)
                    {
                    if ($row["page"]==$return[$n]["page"] && $row["name"]==$return[$n]["name"] && $row["language"]==$return[$n]["language"] && $row["group"]==$return[$n]["group"])
                        {
                        $customisedtext=true;
                        $return[$n]=$row;
                        }                       
                    }
                if(!$customisedtext)
                    {$return[]=$row;}               
                }
            }

    // Clean returned array so it contains unique records by name
    $unique_returned_records = array(); 
    $existing_lang_names     = array();
    $i                       = 0; 
    foreach(array_reverse($return) as $returned_record)
        {
        if(!in_array($returned_record['name'], $existing_lang_names))
            { 
            $existing_lang_names[$i]     = $returned_record['name']; 
            $unique_returned_records[$i] = $returned_record; 
            }

        $i++;
        }
    $return = array_values($unique_returned_records);

    return $return;
    }

/**
 * Returns a specific site text entry.
 *
 * @param  string $page
 * @param  string $name
 * @param  string $getlanguage
 * @param  string $group
 * @return string
 */
function get_site_text($page,$name,$getlanguage,$group)
    {
    global $defaultlanguage, $lang, $language; // Registering plugin text uses $language and $lang  
    global $applicationname, $storagedir, $homeanim_folder; // These are needed as they are referenced in lang files
    if ($group=="") {$g="null";$gc="is";} else {$g="'" . $group . "'";$gc="=";}
    
    $text=sql_query ("select * from site_text where page='$page' and name='$name' and language='$getlanguage' and specific_to_group $gc $g");
    if (count($text)>0)
        {
                return $text[0]["text"];
                }
        # Fall back to default language.
    $text=sql_query ("select * from site_text where page='$page' and name='$name' and language='$defaultlanguage' and specific_to_group $gc $g");
    if (count($text)>0)
        {
                return $text[0]["text"];
                }
                
        # Fall back to default group.
    $text=sql_query ("select * from site_text where page='$page' and name='$name' and language='$defaultlanguage' and specific_to_group is null");
    if (count($text)>0)
        {
        return $text[0]["text"];
        }
        
    # Fall back to language strings.
    if ($page=="") {$key=$name;} else {$key=$page . "__" . $name;}
    
    # Include specific language(s)
    $defaultlangfile = dirname(__FILE__)."/../languages/" . safe_file_name($defaultlanguage) . ".php";
    if(file_exists($defaultlangfile))
        {
        include $defaultlangfile;
        }
    $getlangfile = dirname(__FILE__)."/../languages/" . safe_file_name($getlanguage) . ".php";
    if(file_exists($getlangfile))
        {
        include $getlangfile;
        }
        
    # Include plugin languages in reverse order as per db.php
    global $plugins;    
    $language = $defaultlanguage;
    for ($n=count($plugins)-1;$n>=0;$n--)
        {     
        if (!isset($plugins[$n])) { continue; }          
        register_plugin_language($plugins[$n]);
        }

    $language = $getlanguage;
    for ($n=count($plugins)-1;$n>=0;$n--)
        {  
        if (!isset($plugins[$n])) { continue; }             
        register_plugin_language($plugins[$n]);
        }
            
    if (array_key_exists($key,$lang))
        {
        return $lang[$key];
        }
    elseif (array_key_exists("all_" . $key,$lang))
        {
        return $lang["all_" . $key];
        }
    else
        {
        return "";
        }
    }

/**
 * Check if site text section is custom, i.e. deletable.
 *
 * @param  mixed $page
 * @param  mixed $name
 * @return void
 */
function check_site_text_custom($page,$name)
    {    
    $check=sql_query ("select custom from site_text where page='$page' and name='$name'");
    if (isset($check[0]["custom"])){return $check[0]["custom"];}
    }

/**
 * Saves the submitted site text changes to the database.
 *
 * @param  string $page
 * @param  string $name
 * @param  string $language
 * @param  integer $group
 * @return void
 */
function save_site_text($page,$name,$language,$group)
    {
    global $lang;

    if ($group=="") {$g="null";$gc="is";} else {$g="'" . $group . "'";$gc="=";}
    
    global $custom,$newcustom,$defaultlanguage;
    
    if($newcustom)
        {
        $test=sql_query("select * from site_text where page='$page' and name='$name'");
        if (count($test)>0){return true;}
        }
    if ($custom==""){$custom=0;}
    if (getval("deletecustom","")!="")
        {
        sql_query("delete from site_text where page='$page' and name='$name'");
        }
    elseif (getval("deleteme","")!="")
        {
        sql_query("delete from site_text where page='$page' and name='$name' and specific_to_group $gc $g");
        }
    elseif (getval("copyme","")!="")
        {
        sql_query("insert into site_text(page,name,text,language,specific_to_group,custom) values ('$page','$name','" . getvalescaped("text","") . "','$language',$g,'$custom')");
        }
    elseif (getval("newhelp","")!="")
        {
        global $newhelp;
        $check=sql_query("select * from site_text where page = 'help' and name='$newhelp'");
        if (!isset($check[0])){
            sql_query("insert into site_text(page,name,text,language,specific_to_group) values ('$page','$newhelp','','$language',$g)");
            }
        }   
    else
        {
        $text=sql_query ("select * from site_text where page='$page' and name='$name' and language='$language' and specific_to_group $gc $g");
        if (count($text)==0)
            {
            # Insert a new row for this language/group.
            sql_query("insert into site_text(page,name,language,specific_to_group,text,custom) values ('$page','$name','$language',$g,'" . getvalescaped("text","") . "','$custom')");
            log_activity($lang["text"],LOG_CODE_CREATED,getvalescaped("text",""),'site_text',null,"'{$page}','{$name}','{$language}',{$g}");
            }
        else
            {
            # Update existing row
            sql_query("update site_text set text='" . getvalescaped("text","") . "' where page='$page' and name='$name' and language='$language' and specific_to_group $gc $g");
            log_activity($lang["text"],LOG_CODE_EDITED,getvalescaped("text",""),'site_text',null,"'{$page}','{$name}','{$language}',{$g}");
            }
                        
                # Language clean up - remove all entries that are exactly the same as the default text.
                $defaulttext=sql_value ("select text value from site_text where page='$page' and name='$name' and language='$defaultlanguage' and specific_to_group $gc $g","");
                sql_query("delete from site_text where page='$page' and name='$name' and language!='$defaultlanguage' and trim(text)='" . trim(escape_check($defaulttext)) . "'");
                
        }

    // Clear cache
    clear_query_cache("sitetext");
    }


/**
 * Return a human-readable string representing $bytes in either KB or MB.
 *
 * @param  integer $bytes
 * @return string
 */
function formatfilesize($bytes)
    {
    # Binary mode
    $multiple=1024;$lang_suffix="-binary";
    
    # Decimal mode, if configured
    global $byte_prefix_mode_decimal;
    if ($byte_prefix_mode_decimal)
        {
        $multiple=1000;
        $lang_suffix="";
        }
    
    global $lang;
    if ($bytes<$multiple)
        {
        return number_format((double)$bytes) . "&nbsp;".$lang["byte-symbol"];
        }
    elseif ($bytes<pow($multiple,2))
        {
        return number_format((double)ceil($bytes/$multiple)) . "&nbsp;".$lang["kilobyte-symbol" . $lang_suffix];
        }
    elseif ($bytes<pow($multiple,3))
        {
        return number_format((double)$bytes/pow($multiple,2),1) . "&nbsp;".$lang["megabyte-symbol" . $lang_suffix];
        }
    elseif ($bytes<pow($multiple,4))
        {
        return number_format((double)$bytes/pow($multiple,3),1) . "&nbsp;".$lang["gigabyte-symbol" . $lang_suffix];
        }
    else
        {
        return number_format((double)$bytes/pow($multiple,4),1) . "&nbsp;".$lang["terabyte-symbol" . $lang_suffix];
        }
    }


/**
 * Converts human readable file size (e.g. 10 MB, 200.20 GB) into bytes.
 *
 * @param string $str
 * @return int the result is in bytes
 */
function filesize2bytes($str)
    {

    $bytes = 0;

    $bytes_array = array(
        'b' => 1,
        'kb' => 1024,
        'mb' => 1024 * 1024,
        'gb' => 1024 * 1024 * 1024,
        'tb' => 1024 * 1024 * 1024 * 1024,
        'pb' => 1024 * 1024 * 1024 * 1024 * 1024,
    );

    $bytes = floatval($str);

    if (preg_match('#([KMGTP]?B)$#si', $str, $matches) && !empty($bytes_array[strtolower($matches[1])])) {
        $bytes *= $bytes_array[strtolower($matches[1])];
    }

    $bytes = intval(round($bytes, 2));
    
    #add leading zeroes (as this can be used to format filesize data in resource_data for sorting)
    return sprintf("%010d",$bytes);
    } 

/**
 * Get the mime type for a file on disk
 *
 * @param  string $path
 * @param  string $ext
 * @return string
 */
function get_mime_type($path, $ext = null)
    {
    global $mime_type_by_extension;
    if (empty($ext))
        $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (isset($mime_type_by_extension[$ext]))
        {
        return $mime_type_by_extension[$ext];
        }

    # Get mime type via exiftool if possible
    $exiftool_fullpath = get_utility_path("exiftool");
    if ($exiftool_fullpath!=false)
        {
        $command=$exiftool_fullpath . " -s -s -s -t -mimetype " . escapeshellarg($path);
        return run_command($command);
        }

    return "application/octet-stream";
    }


    
/**
 * Send a mail - but correctly encode the message/subject in quoted-printable UTF-8.
 * 
 * NOTE: $from is the name of the user sending the email,
 * while $from_name is the name that should be put in the header, which can be the system name
 * It is necessary to specify two since in all cases the email should be able to contain the user's name.
 * 
 * Old mail function remains the same to avoid possible issues with phpmailer
 * send_mail_phpmailer allows for the use of text and html (multipart) emails,
 * and the use of email templates in Manage Content.
 * 
 * @param  string $email            Email address to send to 
 * @param  string $subject          Email subject
 * @param  string $message          Message text
 * @param  string $from             From address - defaults to $email_from or user's email if $always_email_from_user enabled
 * @param  string $reply_to         Reply to address - defaults to $email_from or user's email if $always_email_from_user enabled
 * @param  string $html_template    Optional template (this is a $lang entry with placeholders)
 * @param  string $templatevars     Used to populate email template placeholders
 * @param  string $from_name        Email from name
 * @param  string $cc               Optional CC addresses
 * @param  string $bcc              Optional BCC addresses
 * @param  array $files             Optional array of file paths to attach in the format [filename.txt => /path/to/file.txt]
 * @return void
 */
function send_mail($email,$subject,$message,$from="",$reply_to="",$html_template="",$templatevars=null,$from_name="",$cc="",$bcc="",$files = array())
    {
    global $always_email_from_user;
    if($always_email_from_user)
        {
        global $username, $useremail, $userfullname;
        $from_name=($userfullname!="")?$userfullname:$username;
        $from=$useremail;
        $reply_to=$useremail;
        }

    global $always_email_copy_admin;
    if($always_email_copy_admin)
        {
        global $email_notify;
        $bcc.="," . $email_notify;
        }

    /*
    Checking email is valid. Email argument can be an RFC 2822 compliant string so handle multi addresses as well
    IMPORTANT: FILTER_VALIDATE_EMAIL is not fully RFC 2822 compliant, an email like "Another User <anotheruser@example.com>"
    will be invalid
    */
    $rfc_2822_multi_delimiters = array(', ', ',');
    $email = str_replace($rfc_2822_multi_delimiters, '**', $email);
    $check_emails = explode('**', $email);
    $valid_emails = array();
    foreach($check_emails as $check_email)
        {
        if(!filter_var($check_email, FILTER_VALIDATE_EMAIL))
            {
            debug("send_mail: Invalid e-mail address - '{$check_email}'");
            continue;
            }

        $valid_emails[] = $check_email;
        }
    // No/invalid email address? Exit.
    if(empty($valid_emails))
        {
        debug("send_mail: No valid e-mail address found!");
        return false;
        }
    // Valid emails? then make it back into an RFC 2822 compliant string
    $email = implode(', ', $valid_emails);
    
    // Validate all files to attach are valid and copy any that are URLs locally
    $attachfiles = array();
    $deletefiles = array();
    foreach ($files as $filename=>$file)
        {
        if (substr($file,0,4)=="http")
            {
            $ctx = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'timeout' => 2,
                    "ignore_errors" => true,
                    )
                ));                                    
            $filedata = file_get_contents($file, false, $ctx); # File is a URL, not a binary object. Go and fetch the file.
            $file = get_temp_dir() . "/mail_" . uniqid() . ".bin";
            file_put_contents($file,$filedata);
            $deletefiles[]=$file;
            }
        elseif(!file_exists($file))
            {
            debug("file missing: " . $file);
            continue;
            }
        $attachfiles[$filename] = $file;
        }

            
    # Send a mail - but correctly encode the message/subject in quoted-printable UTF-8.
    global $use_phpmailer;
    if ($use_phpmailer)
        {
        send_mail_phpmailer($email,$subject,$message,$from,$reply_to,$html_template,$templatevars,$from_name,$cc,$bcc,$attachfiles); 
        cleanup_files($deletefiles);
        return true;
        }
    
    # Include footer
    global $email_footer;
    global $disable_quoted_printable_enc;
    
    # Work out correct EOL to use for mails (should use the system EOL).
    if (defined("PHP_EOL")) {$eol=PHP_EOL;} else {$eol="\r\n";}

    $headers = '';

    if (count($attachfiles)>0)
        {
        //add boundary string and mime type specification
        $random_hash = md5(date('r', time()));
        $headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"" . $eol;
        
        $body="This is a multi-part message in MIME format." . $eol . "--PHP-mixed-" . $random_hash . $eol;
        $body.="Content-Type: text/plain; charset=\"utf-8\"" . $eol . "Content-Transfer-Encoding: 8bit" . $eol . $eol;
        $body.=$message. $eol . $eol . $eol;        
        # Attach all the files (paths have already been checked)
        foreach ($attachfiles as $filename=>$file)
            {
            $filedata = file_get_contents($file);
            $attachment = chunk_split(base64_encode($filedata));
            $body.="--PHP-mixed-" . $random_hash . $eol;
            $body.="Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol; 
            $body.="Content-Transfer-Encoding: base64" . $eol;
            $body.="Content-Disposition: attachment; filename=\"" . $filename . "\"'" . $eol . $eol;
            $body.=$attachment;
            }
        $body.="--PHP-mixed-" . $random_hash . "--" . $eol; # Final terminating boundary.

        $message = $body;
        $disable_quoted_printable_enc = true; // If false then attachment names and utf8 text get corrupted
        }

    
    $message.=$eol.$eol.$eol . $email_footer;
    
    if (!$disable_quoted_printable_enc)
        {
        $message=rs_quoted_printable_encode($message);
        $subject=rs_quoted_printable_encode_subject($subject);
        }
   
    global $email_from;
    if ($from=="") {$from=$email_from;}
    if ($reply_to=="") {$reply_to=$email_from;}
    global $applicationname;
    if ($from_name==""){$from_name=$applicationname;}
    
    if (substr($reply_to,-1)==","){$reply_to=substr($reply_to,0,-1);}
    
    $reply_tos=explode(",",$reply_to);

    $headers .= "From: ";
    #allow multiple emails, and fix for long format emails
    for ($n=0;$n<count($reply_tos);$n++){
        if ($n!=0){$headers.=",";}
        if (strstr($reply_tos[$n],"<")){ 
            $rtparts=explode("<",$reply_tos[$n]);
            $headers.=$rtparts[0]." <".$rtparts[1];
        }
        else {
            mb_internal_encoding("UTF-8");
            $headers.=mb_encode_mimeheader($from_name, "UTF-8") . " <".$reply_tos[$n].">";
        }
    }
    $headers.=$eol;
    $headers .= "Reply-To: $reply_to" . $eol;
    
    if ($cc!=""){
        global $userfullname;
        #allow multiple emails, and fix for long format emails
        $ccs=explode(",",$cc);
        $headers .= "Cc: ";
        for ($n=0;$n<count($ccs);$n++){
            if ($n!=0){$headers.=",";}
            if (strstr($ccs[$n],"<")){ 
                $ccparts=explode("<",$ccs[$n]);
                $headers.=$ccparts[0]." <".$ccparts[1];
            }
            else {
                mb_internal_encoding("UTF-8");
                $headers.=mb_encode_mimeheader($userfullname, "UTF-8"). " <".$ccs[$n].">";
            }
        }
        $headers.=$eol;
    }
    
    if ($bcc!=""){
        global $userfullname;
        #add bcc 
        $bccs=explode(",",$bcc);
        $headers .= "Bcc: ";
        for ($n=0;$n<count($bccs);$n++){
            if ($n!=0){$headers.=",";}
            if (strstr($bccs[$n],"<")){ 
                $bccparts=explode("<",$bccs[$n]);
                $headers.=$bccparts[0]." <".$bccparts[1];
            }
            else {
                mb_internal_encoding("UTF-8");
                $headers.=mb_encode_mimeheader($userfullname, "UTF-8"). " <".$bccs[$n].">";
            }
        }
        $headers.=$eol;
    }
    
    $headers .= "Date: " . date("r") .  $eol;
    $headers .= "Message-ID: <" . date("YmdHis") . $from . ">" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "X-Mailer: PHP Mail Function" . $eol;
    if (!is_html($message))
        {
        $headers .= "Content-Type: text/plain; charset=\"UTF-8\"" . $eol;
        }
    else
        {
        $headers .= "Content-Type: text/html; charset=\"UTF-8\"" . $eol;
        }
    $headers .= "Content-Transfer-Encoding: quoted-printable" . $eol;
    log_mail($email,$subject,$reply_to);
    mail ($email,$subject,$message,$headers);
    cleanup_files($deletefiles);
    }

/**
 * if ($use_phpmailer==true) this function is used instead.
 * 
 * Mail templates can include lang, server, site_text, and POST variables by default
 * ex ( [lang_mycollections], [server_REMOTE_ADDR], [text_footer] , [message]
 * 
 * additional values must be made available through $templatevars
 * For example, a complex url or image path that may be sent in an 
 * email should be added to the templatevars array and passed into send_mail.
 * available templatevars need to be well-documented, and sample templates
 * need to be available.
 *
 * @param  string $email           Email address to send to 
 * @param  string $subject          Email subject
 * @param  string $message          Message text
 * @param  string $from             From address - defaults to $email_from or user's email if $always_email_from_user enabled
 * @param  string $reply_to         Reply to address - defaults to $email_from or user's email if $always_email_from_user enabled
 * @param  string $html_template    Optional template (this is a $lang entry with placeholders)
 * @param  string $templatevars     Used to populate email template placeholders
 * @param  string $from_name        Email from name
 * @param  string $cc               Optional CC addresses
 * @param  string $bcc              Optional BCC addresses
 * @param  array $files             Optional array of file paths to attach in the format [filename.txt => /path/to/file.txt]
 * @return void
 */

function send_mail_phpmailer($email,$subject,$message="",$from="",$reply_to="",$html_template="",$templatevars=null,$from_name="",$cc="",$bcc="", $files=array())
    {
    # Include footer
    global $email_footer, $storagedir, $mime_type_by_extension;
    include_once(__DIR__ . '/../lib/PHPMailer/PHPMailer.php');
    include_once(__DIR__ . '/../lib/PHPMailer/Exception.php');
    include_once(__DIR__ . '/../lib/PHPMailer/SMTP.php');
    
    global $email_from;
    $from_system = false;
    if ($from=="")
        {
        $from=$email_from;
        $from_system=true;
        }
    if ($reply_to=="") {$reply_to=$email_from;}
    global $applicationname;
    if ($from_name==""){$from_name=$applicationname;}
    
    #check for html template. If exists, attempt to include vars into message
    if ($html_template!="")
        {
        # Attempt to verify users by email, which allows us to get the email template by lang and usergroup
        $to_usergroup=sql_query("select lang,usergroup from user where email ='" . escape_check($email) . "'","");
        
        if (count($to_usergroup)!=0)
            {
            $to_usergroupref=$to_usergroup[0]['usergroup'];
            $to_usergrouplang=$to_usergroup[0]['lang'];
            }
        else 
            {
            $to_usergrouplang="";   
            }
            
        if ($to_usergrouplang==""){global $defaultlanguage; $to_usergrouplang=$defaultlanguage;}
            
        if (isset($to_usergroupref))
            {   
            $modified_to_usergroupref=hook("modifytousergroup","",$to_usergroupref);
            if (is_int($modified_to_usergroupref)){$to_usergroupref=$modified_to_usergroupref;}
            $results=sql_query("select language,name,text from site_text where page='all' and name='$html_template' and specific_to_group='$to_usergroupref'");
            }
        else 
            {   
            $results=sql_query("select language,name,text from site_text where page='all' and name='$html_template' and specific_to_group is null");
            }
            
        global $site_text;
        for ($n=0;$n<count($results);$n++) {$site_text[$results[$n]["language"] . "-" . $results[$n]["name"]]=$results[$n]["text"];} 
                
        $language=$to_usergrouplang;
                                
        if (array_key_exists($language . "-" . $html_template,$site_text)) 
            {
            $template=$site_text[$language . "-" .$html_template];
            } 
        else 
            {
            global $languages;

            # Can't find the language key? Look for it in other languages.
            reset($languages);
            foreach ($languages as $key=>$value)
                {
                if (array_key_exists($key . "-" . $html_template,$site_text)) {$template = $site_text[$key . "-" . $html_template];break;}      
                }
            // Fall back to language file if not in site text
            global $lang;
            if(!isset($template))
                {
                if(isset($lang["all__" . $html_template])){$template=$lang["all__" . $html_template];}
                elseif(isset($lang[$html_template])){$template=$lang[$html_template];}
                }
            }       


        if (isset($template) && $template!="")
            {
            preg_match_all('/\[[^\]]*\]/',$template,$test);
            foreach($test[0] as $variable)
                {
            
                $variable=str_replace("[","",$variable);
                $variable=str_replace("]","",$variable);
            
                
                # get lang variables (ex. [lang_mycollections])
                if (substr($variable,0,5)=="lang_"){
                    global $lang;
                    $$variable=$lang[substr($variable,5)];
                }
                
                # get server variables (ex. [server_REMOTE_ADDR] for a user request)
                else if (substr($variable,0,7)=="server_"){
                    $$variable=$_SERVER[substr($variable,7)];
                }
                
                # [embed_thumbnail] (requires url in templatevars['thumbnail'])
                else if (substr($variable,0,15)=="embed_thumbnail"){
                    $thumbcid=uniqid('thumb');
                    $$variable="<img style='border:1px solid #d1d1d1;' src='cid:$thumbcid' />";
                }
                
                # deprecated by improved [img_] tag below
                # embed images (find them in relation to storagedir so that templates are portable)...  (ex [img_storagedir_/../gfx/whitegry/titles/title.gif])
                else if (substr($variable,0,15)=="img_storagedir_"){
                    $$variable="<img src='cid:".basename(substr($variable,15))."'/>";
                    $images[]=dirname(__FILE__).substr($variable,15);
                }

                // embed images - ex [img_gfx/whitegry/titles/title.gif]
                else if('img_headerlogo' == substr($variable, 0, 14))
                    {
                    $img_url = get_header_image(true);                    
                    $$variable = '<img src="' . $img_url . '"/>';
                    }
                else if('img_' == substr($variable, 0, 4))
                    {
                    $image_path = substr($variable, 4);

                    // absolute paths
                    if('/' == substr($image_path, 0, 1))
                        {
                        $images[] = $image_path;
                        }
                    // relative paths
                    else
                        {
                        $image_path = str_replace('../', '', $image_path);
                        $images[]   = dirname(__FILE__) . '/../' . $image_path;
                        }

                    $$variable = '<img src="cid:' . basename($image_path) . '"/>';
                    }

                # attach files (ex [attach_/var/www/resourcespace/gfx/whitegry/titles/title.gif])
                else if (substr($variable,0,7)=="attach_"){
                    $$variable="";
                    $attachments[]=substr($variable,7);
                }
                
                # get site text variables (ex. [text_footer], for example to 
                # manage html snippets that you want available in all emails.)
                else if (substr($variable,0,5)=="text_"){
                    $$variable=text(substr($variable,5));
                }

                # try to get the variable from POST
                else{
                    $$variable=getval($variable,"");
                }
                
                # avoid resetting templatevars that may have been passed here
                if (!isset($templatevars[$variable])){$templatevars[$variable]=$$variable;}
                }

            if (isset($templatevars))
                {
                foreach($templatevars as $key=>$value)
                    {
                    $template=str_replace("[" . $key . "]",nl2br($value),$template);
                    }
                }
            $body=$template;    
            } 
        }

    if (!isset($body)){$body=$message;}

    global $use_smtp,$smtp_secure,$smtp_host,$smtp_port,$smtp_auth,$smtp_username,$smtp_password,$debug_log,$smtpautotls, $smtp_debug_lvl;
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    // use an external SMTP server? (e.g. Gmail)
    if ($use_smtp) {
        $mail->IsSMTP(); // enable SMTP
        $mail->SMTPAuth = $smtp_auth;  // authentication enabled/disabled
        $mail->SMTPSecure = $smtp_secure; // '', 'tls' or 'ssl'
        $mail->SMTPAutoTLS = $smtpautotls;
        $mail->SMTPDebug = ($debug_log ? $smtp_debug_lvl : 0);
        $mail->Debugoutput = function(string $msg, int $debug_lvl) { debug("SMTPDebug: {$msg}"); };
        $mail->Host = $smtp_host; // hostname
        $mail->Port = $smtp_port; // port number
        $mail->Username = $smtp_username; // username
        $mail->Password = $smtp_password; // password
    }
    $reply_tos=explode(",",$reply_to);

    if (!$from_system)
        {
        // only one from address is possible, so only use the first one:
        if (strstr($reply_tos[0],"<"))
            {
            $rtparts=explode("<",$reply_tos[0]);
            $mail->From = str_replace(">","",$rtparts[1]);
            $mail->FromName = $rtparts[0];
            }
        else {
            $mail->From = $reply_tos[0];
            $mail->FromName = $from_name;
            }
        }
    else
        {
        $mail->From = $from;
        $mail->FromName = $from_name;
        }
    
    // if there are multiple addresses, that's what replyto handles.
    for ($n=0;$n<count($reply_tos);$n++){
        if (strstr($reply_tos[$n],"<")){
            $rtparts=explode("<",$reply_tos[$n]);
            $mail->AddReplyto(str_replace(">","",$rtparts[1]),$rtparts[0]);
        }
        else {
            $mail->AddReplyto($reply_tos[$n],$from_name);
        }
    }
    
    # modification to handle multiple comma delimited emails
    # such as for a multiple $email_notify
    $emails = $email;
    $emails = explode(',', $emails);
    $emails = array_map('trim', $emails);
    foreach ($emails as $email){
        if (strstr($email,"<")){
            $emparts=explode("<",$email);
            $mail->AddAddress(str_replace(">","",$emparts[1]),$emparts[0]);
        }
        else {
            $mail->AddAddress($email);
        }
    }
    
    if ($cc!=""){
        # modification for multiple is also necessary here, though a broken cc seems to be simply removed by phpmailer rather than breaking it.
        $ccs = $cc;
        $ccs = explode(',', $ccs);
        $ccs = array_map('trim', $ccs);
        global $userfullname;
        foreach ($ccs as $cc){
            if (strstr($cc,"<")){
                $ccparts=explode("<",$cc);
                $mail->AddCC(str_replace(">","",$ccparts[1]),$ccparts[0]);
            }
            else{
                $mail->AddCC($cc,$userfullname);
            }
        }
    }
    if ($bcc!=""){
        # modification for multiple is also necessary here, though a broken cc seems to be simply removed by phpmailer rather than breaking it.
        $bccs = $bcc;
        $bccs = explode(',', $bccs);
        $bccs = array_map('trim', $bccs);
        global $userfullname;
        foreach ($bccs as $bccemail){
            if (strstr($bccemail,"<")){
                $bccparts=explode("<",$bccemail);
                $mail->AddBCC(str_replace(">","",$bccparts[1]),$bccparts[0]);
            }
            else{
                $mail->AddBCC($bccemail,$userfullname);
            }
        }
    }
    
    
    $mail->CharSet = "utf-8"; 
    
    if (is_html($body)) {$mail->IsHTML(true);}      
    else {$mail->IsHTML(false);}
    
    $mail->Subject = $subject;
    $mail->Body    = $body;
    
    if (isset($embed_thumbnail)&&isset($templatevars['thumbnail'])){
        $mail->AddEmbeddedImage($templatevars['thumbnail'],$thumbcid,$thumbcid,'base64','image/jpeg'); 
        }

    if(isset($images))
        {
        foreach($images as $image)
            {
            $image_extension = pathinfo($image, PATHINFO_EXTENSION);

            // Set mime type based on the image extension
            if(array_key_exists($image_extension, $mime_type_by_extension))
                {
                $mime_type = $mime_type_by_extension[$image_extension];
                }

            $mail->AddEmbeddedImage($image, basename($image), basename($image), 'base64', $mime_type);
            }
        }

    if (isset($attachments))
        {
        foreach ($attachments as $attachment){
        $mail->AddAttachment($attachment,basename($attachment));}
        }
        
    if (count($files)>0)
        {
        # Attach all the files
        foreach ($files as $filename=>$file)
            {
            if (substr($file,0,4)=="http")
                {
                $ctx = stream_context_create(array(
                    'http' => array(
                        'method' => 'POST',
                        'timeout' => 2,
                        "ignore_errors" => true,
                        )
                    ));                                    
                $file = file_get_contents($file, false, $ctx); # File is a URL, not a binary object. Go and fetch the file.
                }
            elseif(!file_exists($file))
                {
                debug("file missing: " . $file);
                continue;
                }
            
            $mail->AddAttachment($file,$filename);
            }
        }

    if (is_html($body))
        {
        $mail->AltBody = $mail->html2text($body); 
        }
        
    log_mail($email,$subject,$reply_to);

    $GLOBALS["use_error_exception"] = true;
    try
        {
        $mail->Send();
        }
    catch (Exception $e)
        {
        echo "Message could not be sent. <p>";
        debug("PHPMailer Error: email: " . $email . " - " . $e->errorMessage());
        exit;
        }
    catch (\Exception $e)
        {
        echo "Message could not be sent. <p>";
        debug("PHPMailer Error: email: " . $email . " - " . $e->errorMessage());
        exit;
        }
    unset($GLOBALS["use_error_exception"]);
    hook("aftersendmailphpmailer","",$email);   
}


/**
 *  Log email 
 * 
 * Data logged is:
 * Time
 * To address
 * From, User ID or 0 for system emails (cron etc.)
 * Subject
 *
 * @param  string $email
 * @param  string $subject
 * @param  string $sender    The email address of the sender
 * @return void
 */
function log_mail($email,$subject,$sender)
    {
    global $userref;
    $to = escape_check($email);
    if (isset($userref))
        {
        $from = $userref;
        }
    else
        {
        $from = 0;
        }
    $sub = escape_check(mb_substr($subject,0,100));

    // Write log to database
    sql_query("
        INSERT into
            mail_log (
                date,
                mail_to,
                mail_from,
                subject,
                sender_email
                )
            VALUES (
                NOW(),
                '" . $to . "',
                '" . $from . "',
                '" . $sub . "',
                '" . $sender . "'
        );
        ");
    }


/**
 * Quoted printable encoding is rather simple.
 * Each character in the string $string should be encoded if:
 *      Character code is <0x20 (space)
 *      Character is = (as it has a special meaning: 0x3d)
 *      Character is over ASCII range (>=0x80)
 *
 * @param  string $string
 * @param  integer $linelen
 * @param  string $linebreak
 * @param  integer $breaklen
 * @param  boolean $encodecrlf
 * @return string
 */
function rs_quoted_printable_encode($string, $linelen = 0, $linebreak="=\r\n", $breaklen = 0, $encodecrlf = false)
    {
    $len = strlen($string);
    $result = '';
    for($i=0;$i<$len;$i++) {
            if (($linelen >= 76) && (false)) { // break lines over 76 characters, and put special QP linebreak
                    $linelen = $breaklen;
                    $result.= $linebreak;
            }
            $c = ord($string[$i]);
            if (($c==0x3d) || ($c>=0x80) || ($c<0x20)) { // in this case, we encode...
                    if ((($c==0x0A) || ($c==0x0D)) && (!$encodecrlf)) { // but not for linebreaks
                            $result.=chr($c);
                            $linelen = 0;
                            continue;
                    }
                    $result.='='.str_pad(strtoupper(dechex($c)), 2, '0');
                    $linelen += 3;
                    continue;
            }
            $result.=chr($c); // normal characters aren't encoded
            $linelen++;
    }
    return $result;
    }


/**
 * As rs_quoted_printable_encode() but for e-mail subject
 *
 * @param  string $string
 * @param  string $encoding
 * @return string
 */
function rs_quoted_printable_encode_subject($string, $encoding='UTF-8')
    {
    // use this function with headers, not with the email body as it misses word wrapping
       $len = strlen($string);
       $result = '';
       $enc = false;
       for($i=0;$i<$len;++$i) {
        $c = $string[$i];
        if (ctype_alpha($c))
            $result.=$c;
        else if ($c==' ') {
            $result.='_';
            $enc = true;
        } else {
            $result.=sprintf("=%02X", ord($c));
            $enc = true;
        }
       }
       //L: so spam agents won't mark your email with QP_EXCESS
       if (!$enc) return $string;
       return '=?'.$encoding.'?q?'.$result.'?=';
    }


/**
 * A generic pager function used by many display lists in ResourceSpace.
 * 
 * Requires the following globals to be set or passed inb the $options array
 * $url         - Current page url
 * $curpage     - Current page
 * $totalpages  - Total number of pages
 *
 * @param  boolean $break
 * @param  boolean $scrolltotop
 * @param  array   $options - array of options to use instead of globals
 * @return void
 */
function pager($break=true,$scrolltotop=true,$options=array())
    {
    global $curpage,$url,$totalpages,$offset,$per_page,$lang,$jumpcount,$pager_dropdown,$pagename;
    $validoptions = array(
        "curpage",
        "url",
        "url_params",
        "totalpages",
        "offset",
        "per_page",
        "jumpcount",
        "pager_dropdown",
    );
    foreach($validoptions as $validoption)
        {
        global $$validoption;
        if(isset($options[$validoption]))
            {
            $$validoption = $options[$validoption];
            }        
        }

    $modal  = ('true' == getval('modal', ''));
    $scroll =  $scrolltotop ? "true" : "false"; 
    $jumpcount++;

    // If pager URL includes query string params, remove them and store in $url_params array
    if(!isset($url_params) && strpos($url,"?") !== false)
        {
        $urlparts = explode("?",$url);
        parse_str($urlparts[1],$url_params);
        $url = $urlparts[0];
        }

    if(!hook("replace_pager")){
        if ($totalpages!=0 && $totalpages!=1){?>     
            <span class="TopInpageNavRight"><?php if ($break) { ?>&nbsp;<br /><?php } hook("custompagerstyle"); if ($curpage>1) { ?><a class="prevPageLink" title="<?php echo $lang["previous"]?>" href="<?php echo generateURL($url, (isset($url_params) ? $url_params : array()), array("go"=>"prev","offset"=> ($offset-$per_page)));?>" <?php if(!hook("replacepageronclick_prev")){?>onClick="return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this, <?php echo $scroll; ?>);" <?php } ?>><?php } ?><i aria-hidden="true" class="fa fa-arrow-left"></i><?php if ($curpage>1) { ?></a><?php } ?>&nbsp;&nbsp;

            <?php if ($pager_dropdown)
                {
                $id=rand();?>
                <select id="pager<?php echo $id;?>" class="ListDropdown" style="width:50px;" <?php if(!hook("replacepageronchange_drop","",array($id))){?>onChange="var jumpto=document.getElementById('pager<?php echo $id?>').value;if ((jumpto>0) && (jumpto<=<?php echo $totalpages?>)) {return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load('<?php echo generateURL($url, (isset($url_params) ? $url_params : array()), array("go"=>"page")); ?>&amp;offset=' + ((jumpto-1) * <?php echo urlencode($per_page) ?>), <?php echo $scroll; ?>);}" <?php } ?>>
                <?php for ($n=1;$n<$totalpages+1;$n++){?>
                    <option value='<?php echo $n?>' <?php if ($n==$curpage){?>selected<?php } ?>><?php echo $n?></option>
                <?php } ?>
                </select><?php
                }
            else
                {?>
                <div class="JumpPanel" id="jumppanel<?php echo $jumpcount?>" style="display:none;"><?php echo $lang["jumptopage"]?>: <input type="text" size="1" id="jumpto<?php echo $jumpcount?>" onkeydown="var evt = event || window.event;if (evt.keyCode == 13) {var jumpto=document.getElementById('jumpto<?php echo $jumpcount?>').value;if (jumpto<1){jumpto=1;};if (jumpto><?php echo $totalpages?>){jumpto=<?php echo $totalpages?>;};<?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load('<?php echo generateURL($url, (isset($url_params) ? $url_params : array()), array("go"=>"page")); ?>&amp;offset=' + ((jumpto-1) * <?php echo urlencode($per_page) ?>), <?php echo $scroll; ?>);}">
            &nbsp;<a aria-hidden="true" class="fa fa-times-circle" href="#" onClick="document.getElementById('jumppanel<?php echo $jumpcount?>').style.display='none';document.getElementById('jumplink<?php echo $jumpcount?>').style.display='inline';"></a></div>
            
                <a href="#" id="jumplink<?php echo $jumpcount?>" title="<?php echo $lang["jumptopage"]?>" onClick="document.getElementById('jumppanel<?php echo $jumpcount?>').style.display='inline';document.getElementById('jumplink<?php echo $jumpcount?>').style.display='none';document.getElementById('jumpto<?php echo $jumpcount?>').focus(); return false;"><?php echo $lang["page"]?>&nbsp;<?php echo htmlspecialchars($curpage) ?>&nbsp;<?php echo $lang["of"]?>&nbsp;<?php echo $totalpages?></a><?php
                } ?>

            &nbsp;&nbsp;<?php
            if ($curpage<$totalpages)
                {
                ?><a class="nextPageLink" title="<?php echo $lang["next"]?>" href="<?php echo generateURL($url, (isset($url_params) ? $url_params : array()), array("go"=>"next","offset"=> ($offset+$per_page)));?>" <?php if(!hook("replacepageronclick_next")){?>onClick="return <?php echo $modal ? 'Modal' : 'CentralSpace'; ?>Load(this, <?php echo $scroll; ?>);" <?php } ?>><?php
                }?><i aria-hidden="true" class="fa fa-arrow-right"></i>
            <?php if ($curpage<$totalpages) { ?></a><?php } hook("custompagerstyleend"); ?>
            </span>
            
        <?php } else { ?><span class="HorizontalWhiteNav">&nbsp;</span><div <?php if ($pagename=="search"){?>style="display:block;"<?php } else { ?>style="display:inline;"<?php }?>>&nbsp;</div><?php } ?>
        <?php
        }
    }
    

/**
 * If configured, send two metrics to Montala to get an idea of general software usage.
 *
 * @return void
 */
function send_statistics()
    {
    $last_sent_stats  = get_sysvar('last_sent_stats', '1970-01-01');
    
    # No need to send stats if already sent in last week.
    if (time()-strtotime($last_sent_stats) < 7*24*60*60)
        {
        return false;
        }
    
    # Gather stats
    $total_users=sql_value("select count(*) value from user",0);
    $total_resources=sql_value("select count(*) value from resource",0);
    
    # Send stats
    @file("https://www.montala.com/rs_stats.php?users=" . $total_users . "&resources=" . $total_resources);
    
    # Update last sent date/time.
    set_sysvar("last_sent_stats",date("Y-m-d H:i:s")); 
    }

/**
 * Remove the extension part of a filename
 *
 * @param  mixed $strName   The filename
 * @return string           The filename minus the extension
 */
function remove_extension($strName)
    {
    $ext = strrchr($strName, '.');
    if($ext !== false)
    {
    $strName = substr($strName, 0, -strlen($ext));
    }
    return $strName;
    }

    
/**
 * Retrieve a list of permitted extensions for the given resource type.
 *
 * @param  integer $resource_type
 * @return string
 */
function get_allowed_extensions_by_type($resource_type)
    {
    $allowed_extensions=sql_value("select allowed_extensions value from resource_type where ref='$resource_type'","", "schema");
    return $allowed_extensions;
    }

/**
 * Detect if a path is relative or absolute.
 * If it is relative, we compute its absolute location by assuming it is
 * relative to the application root (parent folder).
 * 
 * @param string $path A relative or absolute path
 * @param boolean $create_if_not_exists Try to create the path if it does not exists. Default to False.
 * @access public
 * @return string A absolute path
 */
function getAbsolutePath($path, $create_if_not_exists = false)
    {
    if(preg_match('/^(\/|[a-zA-Z]:[\\/]{1})/', $path)) // If the path start by a '/' or 'c:\', it is an absolute path.
        {
        $folder = $path;
        }
    else // It is a relative path.
        {
        $folder = sprintf('%s%s..%s%s', dirname(__FILE__), DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
        }

    if ($create_if_not_exists && !file_exists($folder)) // Test if the path need to be created.
        {
        mkdir($folder,0777);
        } // Test if the path need to be created.

    return $folder;
    } 



/**
 * Find the files present in a folder, and sub-folder.
 * 
 * @param string $path The path to look into.
 * @param boolean $recurse Trigger the recursion, default to True.
 * @param boolean $include_hidden Trigger the listing of hidden files / hidden directories, default to False.
 * @access public
 * @return array A list of files present in the inspected folder (paths are relative to the inspected folder path).
 */
function getFolderContents($path, $recurse = true, $include_hidden = false)
    {
    if(!is_dir($path)) // Test if the path is not a folder.
        {
            return array();
        } // Test if the path is not a folder.

    $directory_handle = opendir($path);
    if($directory_handle === false) // Test if the directory listing failed.
        {
        return array();
        } // Test if the directory listing failed.

    $files = array();
    while(($file = readdir($directory_handle)) !== false) // For each directory listing entry.
        {
        if(! in_array($file, array('.', '..'))) // Test if file is not unix parent and current path.
            {
            if($include_hidden || ! preg_match('/^\./', $file)) // Test if the file can be listed.
                {
                $complete_path = $path . DIRECTORY_SEPARATOR . $file;
                if(is_dir($complete_path) && $recurse) // If the path is a directory, and need to be explored.
                    {
                    $sub_dir_files = getFolderContents($complete_path, $recurse, $include_hidden);
                    foreach($sub_dir_files as $sub_dir_file) // For each subdirectory contents.
                        {
                        $files[] = $file . DIRECTORY_SEPARATOR . $sub_dir_file;
                        } // For each subdirectory contents.
                    }
                elseif(is_file($complete_path)) // If the path is a file.
                    {
                    $files[] = $file;
                    }
                } // Test if the file can be listed.
            } // Test if file is not unix parent and current path.
        } // For each directory listing entry.

    // We close the directory handle:
    closedir($directory_handle);

    // We sort the files alphabetically.
    natsort($files);

    return $files;
    } 



/**
 * Returns filename component of path
 * This version is UTF-8 proof.
 * 
 * @param string $file A path.
 * @access public
 * @return string Returns the base name of the given path.
 */
function mb_basename($file)
    {
    $regex_file = preg_split('/[\\/]+/',$file);
    return end($regex_file);
    } 

/**
 * Remove the extension part of a filename.
 * 
 * @param string $name A file name.
 * @access public
 * @return string Return the file name without the extension part.
 */
function strip_extension($name,$use_ext_list=false)
    {
        $s = strrpos($name, '.');
        if ($s===false) 
        {
            return $name;
        }
        else
        {
            global $download_filename_strip_extensions;
            if ($use_ext_list == true && isset($download_filename_strip_extensions))
            {
                // Use list of specified extensions if config present.
                $fn_extension=substr($name,$s+1);
                if (in_array(strtolower($fn_extension),$download_filename_strip_extensions))
                    {
                        return substr($name, 0, $s);
                    }
                else
                    {
                        return $name;
                    }
            }
            else
            {
                // Attempt to remove file extension from string where download_filename_strip_extensions is not configured.
                return substr($name,0,$s);
            }
        }
    }

/**
 * Checks to see if a process lock exists for the given process name.
 *
 * @param  string $name     Name of lock to check
 * @return boolean TRUE if a current process lock is in place, false if not
 */
function is_process_lock($name)
    { 
    global $storagedir,$process_locks_max_seconds;
    
    # Check that tmp/process_locks exists, create if not.
    # Since the get_temp_dir() method does this checking, omit: if(!is_dir($storagedir . "/tmp")){mkdir($storagedir . "/tmp",0777);}
    if(!is_dir(get_temp_dir() . "/process_locks")){mkdir(get_temp_dir() . "/process_locks",0777);}
    
    # No lock file? return false
    if (!file_exists(get_temp_dir() . "/process_locks/" . $name)) {return false;}
    if (!is_readable(get_temp_dir() . "/process_locks/" . $name)) {return true;} // Lock exists and cannot read it so must assume it's still valid
    
    $GLOBALS["use_error_exception"] = true;
    try {
        $time=trim(file_get_contents(get_temp_dir() . "/process_locks/" . $name));
        if ((time() - (int) $time)>$process_locks_max_seconds) {return false;} # Lock has expired
        }
    catch (Exception $e) {
        debug("is_process_lock: Attempt to get file contents '$result' failed. Reason: {$e->getMessage()}");
        }
    unset($GLOBALS["use_error_exception"]);
    
    return true; # Lock is valid
    }
    
/**
 * Set a process lock
 *
 * @param  string $name
 * @return boolean
 */
function set_process_lock($name)
    {
    file_put_contents(get_temp_dir() . "/process_locks/" . $name,time());
    // make sure this is editable by the server in case a process lock could be set by different system users
    chmod(get_temp_dir() . "/process_locks/" . $name,0777);
    return true;
    }
    
/**
 * Clear a process lock
 *
 * @param  string $name
 * @return boolean
 */
function clear_process_lock($name)
    {
    if (!file_exists(get_temp_dir() . "/process_locks/" . $name)) {return false;}
    unlink(get_temp_dir() . "/process_locks/" . $name);
    return true;
    }

/**
 * Custom function for retrieving a file size. A resolution for PHP's issue with large files and filesize(). 
 *
 * @param  string $path
 * @return integer  The file size in bytes
 */
function filesize_unlimited($path)
    {
    hook("beforefilesize_unlimited","",array($path));

    if('WINNT' == PHP_OS)
        {
        if(class_exists('COM'))
            {
            try
                {
                $filesystem = new COM('Scripting.FileSystemObject');
                $file       =$filesystem->GetFile($path);

                return $file->Size();
                }
            catch(com_exception $e)
                {
                return false;
                }
            }

        return exec('for %I in (' . escapeshellarg($path) . ') do @echo %~zI' );
        }
    else if('Darwin' == PHP_OS || 'FreeBSD' == PHP_OS)
        {
        $bytesize = exec("stat -f '%z' " . escapeshellarg($path));
        }
    else 
        {
        $bytesize = exec("stat -c '%s' " . escapeshellarg($path));
        }

    if(!is_int($bytesize))
        {
        $bytesize = @filesize($path); # Bomb out, the output wasn't as we expected. Return the filesize() output.
        }

    hook('afterfilesize_unlimited', '', array($path));

    return $bytesize;
    }

/**
 * Strip the leading comma from a string
 *
 * @param  string $val
 * @return string
 */
function strip_leading_comma($val)
    {
    return preg_replace('/^\,/','',$val);
    }


/**
 * Determines where the tmp directory is.  There are three options here:
 * 1. tempdir - If set in config.php, use this value.
 * 2. storagedir ."/tmp" - If storagedir is set in config.php, use it and create a subfolder tmp.
 * 3. generate default path - use filestore/tmp if all other attempts fail.
 * 4. if a uniqid is provided, create a folder within tmp and return the full path
 * 
 * @param bool $asUrl - If we want the return to be like http://my.resourcespace.install/path set this as true.
 * @return string Path to the tmp directory.
 */
function get_temp_dir($asUrl = false,$uniqid="")
    {
    global $storagedir, $tempdir;
    // Set up the default.
    $result = dirname(dirname(__FILE__)) . "/filestore/tmp";
    
    // if $tempdir is explicity set, use it.
    if(isset($tempdir))
    {
        // Make sure the dir exists.
        if(!is_dir($tempdir))
        {
            // If it does not exist, create it.
            mkdir($tempdir, 0777);
        }
        $result = $tempdir;
    }
    // Otherwise, if $storagedir is set, use it.
    else if (isset($storagedir))
    {
        // Make sure the dir exists.
        if(!is_dir($storagedir . "/tmp"))
        {
            // If it does not exist, create it.
            mkdir($storagedir . "/tmp", 0777);
        }
        $result = $storagedir . "/tmp";
    }
    else
    {
        // Make sure the dir exists.
        if(!is_dir($result))
        {
            // If it does not exist, create it.
            mkdir($result, 0777);
        }
    }
    
    if ($uniqid!=""){
        $uniqid=str_replace("../","",$uniqid);//restrict to forward-only movements
        $result.="/$uniqid";
        if(!is_dir($result))
            {
            // If it does not exist, create it.
            try {
                mkdir($result, 0777,true);
            } 
            catch (Exception $e) {
                debug("get_temp_dir: Attempt to create folder '$result' failed. Reason: {$e->getMessage()}");  
            }
        }
    }
    
    // return the result.
    if($asUrl==true)
    {
        $result = convert_path_to_url($result);
    $result = str_replace('\\','/',$result);
    }
    return $result;
    }

/**
 * Converts a path to a url relative to the installation.
 * 
 * @param string $abs_path: The absolute path.
 * @return Url that is the relative path.
 */
function convert_path_to_url($abs_path)
    {
    // Get the root directory of the app:
    $rootDir = dirname(dirname(__FILE__));
    // Get the baseurl:
    global $baseurl;
    // Replace the $rootDir with $baseurl in the path given:
    return str_ireplace($rootDir, $baseurl, $abs_path);
    }


/**
* Escaping an unsafe command string
* 
* @param  string  $cmd   Unsafe command to run
* @param  array   $args  List of placeholders and their values which will have to be escapedshellarg()d.
* 
* @return string Escaped command string
*/
function escape_command_args($cmd, array $args)
    {
    debug("escape_command_args(\$cmd = '{$cmd}', \$args = " . str_replace(PHP_EOL, "", print_r($args, true)) . ")");

    if(empty($args))
        {
        return $cmd;
        }

    foreach($args as $placeholder => $value)
        {
        if(strpos($cmd, $placeholder) === false)
            {
            trigger_error("Unable to find arg '{$placeholder}' in '{$cmd}'. Make sure the placeholder exists in the command string");
            }

        $cmd = str_replace($placeholder, escapeshellarg($value), $cmd);
        }

    return $cmd;
    }


/**
* Utility function which works like system(), but returns the complete output string rather than just the last line of it.
* 
* @uses escape_command_args()
* 
* @param  string   $command    Command to run
* @param  boolean  $geterrors  Set to TRUE to include errors in the output
* @param  array    $params     List of placeholders and their values which will have to be escapedshellarg()d.
* 
* @return string Command output
*/
function run_command($command, $geterrors = false, array $params = array())
    {
    global $debug_log;

    $command = escape_command_args($command, $params);
    debug("CLI command: $command");

    $descriptorspec = array(
        1 => array("pipe", "w") // stdout is a pipe that the child will write to
    );
    if($debug_log || $geterrors) 
        {
        $descriptorspec[2] = array("pipe", "w"); // stderr is a file to write to
        }
    $process = @proc_open($command, $descriptorspec, $pipe, NULL, NULL, array('bypass_shell' => true));

    if (!is_resource($process)) { return ''; }

    $output = trim(stream_get_contents($pipe[1]));
    if($geterrors)
        {
        $output .= trim(stream_get_contents($pipe[2]));
        }
    if ($debug_log)
        {
        debug("CLI output: $output");
        debug("CLI errors: " . trim(stream_get_contents($pipe[2])));
        }
    proc_close($process);
    return $output;
    }

/**
 * Similar to run_command but returns an array with the resulting output (stdout & stderr) fetched concurrently
 * for improved performance.
 *
 * @param  mixed $command   Command to run
 *
 * @return array Command output
 */
function run_external($command)
    {
    global $debug_log;
    
    $pipes = array();
    $output = array();
    # Pipes for stdin, stdout and stderr
    $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));

    # Execute the command
    $process = proc_open($command, $descriptorspec, $pipes);

    # Ensure command returns an external PHP resource
    if (!is_resource($process))
        {
        return false;
        }
    
    # Immediately close the input pipe
    fclose($pipes[0]);
    
    # Set both output streams to non-blocking mode
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    while (true)
        {
        $read = array();

        if (!feof($pipes[1]))
            {
            $read[] = $pipes[1];
            }

        if (!feof($pipes[2]))
            {
            $read[] = $pipes[2];
            }
 
        if (!$read)
            {
            break;
            }
 
        $write = NULL;
        $except = NULL;
        $ready = stream_select($read, $write, $except, 2);
 
        if ($ready === false)
            {
            break;
            }
 
        foreach ($read as $r)
            {
            # Read a line and strip newline and carriage return from the end
            $line = rtrim(fgets($r, 1024),"\r\n");  
            $output[] = $line;
            }
        }
 
    # Close the output pipes
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    if ($debug_log)
        {
        debug("CLI output: ". implode("\n", $output));
        }
 
    proc_close($process);
 
    return $output;
    }

/**
 * Display a styledalert() modal error and optionally return the browser to the previous page after 2 seconds
 *
 * @param  string $error    Error text to display
 * @param  boolean $back    Return to previous page?
 * @param  integer $code    (Optional) HTTP code to return
 * 
 * @return void
 */
function error_alert($error, $back = true, $code = 403)
    {
    foreach($GLOBALS as $key => $value)
        {
        $$key=$value;
        }

    http_response_code($code);

    if($back)
        {
        include(dirname(__FILE__)."/header.php");
        }

    echo "<script type='text/javascript'>
        jQuery(document).ready(function()
            {
            ModalClose();
            styledalert('" . $lang["error"] . "', '$error');
            " . ($back ? "window.setTimeout(function(){history.go(-1);},2000);" : "") ."
            });
        </script>";
    if($back)
        {
        include(dirname(__FILE__)."/footer.php");
        }
    }


/**
 * When displaying metadata, applies trim/wordwrap/highlights.
 *
 * @param  string $value
 * @return string
 */
function format_display_field($value)
    {
    global $results_title_trim,$results_title_wordwrap,$df,$x,$search;

    $value = strip_tags_and_attributes($value);

    $string=i18n_get_translated($value);
    $string=TidyList($string);
    
    if(isset($df[$x]['type']) && $df[$x]['type'] == FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR)
        {
        $string = strip_tags_and_attributes($string); // This will allow permitted tags and attributes
        }
    else
        {
        $string=htmlspecialchars($string);
        }

    $string=highlightkeywords($string,$search,$df[$x]['partial_index'],$df[$x]['name'],$df[$x]['indexed']);
    
    return $string;
    }

/**
 * Formats a string with a collapsible more / less section
 *
 * @param  string $string
 * @param  integer $max_words_before_more
 * @return string
 */
function format_string_more_link($string,$max_words_before_more=-1)
    {
    $words=preg_split('/[\t\f ]/',$string);
    if ($max_words_before_more==-1)
        {
        global $max_words_before_more;
        }
    if (count($words) < $max_words_before_more)
        {
        return $string;
        }
    global $lang;
    $unique_id=uniqid();
    $return_value = "";
    for ($i=0; $i<count($words); $i++)
        {
        if ($i>0)
            {
            $return_value .= ' ';
            }
        if ($i==$max_words_before_more)
            {
            $return_value .= '<a id="' . $unique_id . 'morelink" href="#" onclick="jQuery(\'#' . $unique_id . 'morecontent\').show(); jQuery(this).hide();">' .
                strtoupper($lang["action-more"]) . ' &gt;</a><span id="' . $unique_id . 'morecontent" style="display:none;">';
            }
        $return_value.=$words[$i];
        }
    $return_value .= ' <a href="#" onclick="jQuery(\'#' . $unique_id . 'morelink\').show(); jQuery(\'#' . $unique_id . 'morecontent\').hide();">&lt; ' .
        strtoupper($lang["action-less"]) . '</a></span>';
    return $return_value;
    }

/**
 * Render a performance footer with metrics.
 *
 * @return void
 */
function draw_performance_footer()
    {
    global $config_show_performance_footer,$querycount,$querytime,$querylog,$pagename,$hook_cache_hits,$hook_cache;
    $performance_footer_id=uniqid("performance");
    if ($config_show_performance_footer){   
    # --- If configured (for debug/development only) show query statistics
    ?>
    <?php if ($pagename=="collections"){?><br/><br/><br/><br/><br/><br/><br/>
    <br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><div style="float:left;"><?php } else { ?><div style="float:right; margin-right: 10px;"><?php } ?>
    <table class="InfoTable" style="float: right;margin-right: 10px;">
    <tr><td>Page Load</td><td><?php show_pagetime();?></td></tr>
    <?php 
        if(isset($hook_cache_hits) && isset($hook_cache)) {         
        ?>
        <tr><td>Hook cache hits</td><td><?php echo $hook_cache_hits;?></td></tr>    
        <tr><td>Hook cache entries</td><td><?php echo count($hook_cache); ?></td></tr>
        <?php
        }
    ?>
    <tr><td>Query count</td><td><?php echo $querycount?></td></tr>
    <tr><td>Query time</td><td><?php echo round($querytime,4)?></td></tr>
    <?php $dupes=0;
    foreach ($querylog as $query=>$values){
            if ($values['dupe']>1){$dupes++;}
        }
    ?>
    <tr><td>Dupes</td><td><?php echo $dupes?></td></tr>
    <tr><td colspan=2><a href="#" onClick="document.getElementById('querylog<?php echo $performance_footer_id?>').style.display='block';return false;"><?php echo LINK_CARET ?>details</a></td></tr>
    </table>
    <table class="InfoTable" style="float: right;margin-right: 10px;display:none;" id="querylog<?php echo $performance_footer_id?>">
    <?php foreach ($querylog as $query=>$details) { ?>
    <tr><td><?php echo($query) ?></td></tr>
    <?php } ?>
    </table>
    </div>
    </div>
    <?php
    }
    }
    

/**
 * Abstracted mysqli_affected_rows()
 *
 * @return mixed
 */
function sql_affected_rows()
    {
    global $db;
    return mysqli_affected_rows($db["read_write"]);
    }

/**
 * Returns the path to the ImageMagick utilities such as 'convert'.
 *
 * @param  string $utilityname
 * @param  string $exeNames
 * @param  string $checked_path
 * @return string
 */
function get_imagemagick_path($utilityname, $exeNames, &$checked_path)
    {
    global $imagemagick_path;
    if (!isset($imagemagick_path))
        {
        # ImageMagick convert path not configured.
        return false;
        }
    $path=get_executable_path($imagemagick_path, $exeNames, $checked_path);
    if ($path===false)
        {
        # Support 'magick' also, ie. ImageMagick 7+
        return get_executable_path($imagemagick_path, array("unix"=>"magick", "win"=>"magick.exe"),
                $checked_path) . ' ' . $utilityname;
        }
    return $path;
    }

/**
* Returns the full path to a utility, if installed or FALSE otherwise.
* Note: this function doesn't check that the utility is working.
* 
* @uses get_imagemagick_path()
* @uses get_executable_path()
* 
* @param string $utilityname 
* @param string $checked_path
* 
* @return string|boolean Returns full path to utility tool or FALSE
*/
function get_utility_path($utilityname, &$checked_path = null)
    {
    global $ghostscript_path, $ghostscript_executable, $ffmpeg_path, $exiftool_path, $antiword_path, $pdftotext_path,
           $blender_path, $archiver_path, $archiver_executable, $python_path, $fits_path;

    $checked_path = null;

    switch(strtolower($utilityname))
        {
        case 'im-convert':
            return get_imagemagick_path(
                'convert',
                array(
                    'unix' => 'convert',
                    'win'  => 'convert.exe'
                ),
                $checked_path);

        case 'im-identify':
            return get_imagemagick_path(
                'identify',
                array(
                    'unix' => 'identify',
                    'win'  => 'identify.exe'
                ),
                $checked_path);

        case 'im-composite':
            return get_imagemagick_path(
                'composite',
                array(
                    'unix' => 'composite',
                    'win'  => 'composite.exe'
                ),
                $checked_path);

        case 'im-mogrify':
            return get_imagemagick_path(
                'mogrify',
                array(
                    'unix' => 'mogrify',
                    'win'  => 'mogrify.exe'
                ),
                $checked_path);

        case 'ghostscript':
            // Ghostscript path not configured
            if(!isset($ghostscript_path))
                {
                return false;
                }

            // Ghostscript executable not configured
            if(!isset($ghostscript_executable))
                {
                return false;
                }

            // Note that $check_exe is set to true. In that way get_utility_path()
            // becomes backwards compatible with get_ghostscript_command()
            return get_executable_path(
                $ghostscript_path,
                array(
                    'unix' => $ghostscript_executable,
                    'win'  => $ghostscript_executable
                ),
                $checked_path,
                true) . ' -dPARANOIDSAFER'; 

        case 'ffmpeg':
            // FFmpeg path not configured
            if(!isset($ffmpeg_path))
                {
                return false;
                }

            $return = get_executable_path(
                $ffmpeg_path,
                array(
                    'unix' => 'ffmpeg',
                    'win'  => 'ffmpeg.exe'
                ),
                $checked_path);

            // Support 'avconv' as well
            if(false === $return)
                {
                return get_executable_path(
                    $ffmpeg_path,
                    array(
                        'unix' => 'avconv',
                        'win'  => 'avconv.exe'
                    ),
                    $checked_path);
                }
            return $return;

        case 'ffprobe':
            // FFmpeg path not configured
            if(!isset($ffmpeg_path))
                {
                return false;
                }

            $return = get_executable_path(
                $ffmpeg_path,
                array(
                    'unix' => 'ffprobe',
                    'win'  => 'ffprobe.exe'
                ),
                $checked_path);

            // Support 'avconv' as well
            if(false === $return)
                {
                return get_executable_path(
                    $ffmpeg_path,
                    array(
                        'unix' => 'avprobe',
                        'win'  => 'avprobe.exe'
                    ),
                    $checked_path);
                }
            return $return;       

        case 'exiftool':
            global $exiftool_global_options;

            return get_executable_path(
                $exiftool_path,
                array(
                    'unix' => 'exiftool',
                    'win'  => 'exiftool.exe'
                ),
                $checked_path) . " {$exiftool_global_options} ";

        case 'antiword':
        case 'pdftotext':
        case 'blender':
            break;

        case 'archiver':
            // Archiver path not configured
            if(!isset($archiver_path))
                {
                return false;
                }

            // Archiver executable not configured
            if(!isset($archiver_executable))
                {
                return false;
                }

            return get_executable_path(
                $archiver_path,
                array(
                    'unix' => $archiver_executable,
                    'win'  => $archiver_executable
                ),
                $checked_path);

        case 'python':
            // Python path not configured
            if(!isset($python_path) || '' == $python_path)
                {
                return false;
                }

            return get_executable_path(
                $python_path,
                array(
                    'unix' => 'python',
                    'win'  => 'python.exe'
                ),
                $checked_path,
                true);

        case 'fits':
            // FITS path not configured
            if(!isset($fits_path) || '' == $fits_path)
                {
                return false;
                }

            return get_executable_path(
                $fits_path,
                array(
                    'unix' => 'fits.sh',
                    'win'  => 'fits.bat'
                ),
                $checked_path);

        case 'php':
            global $php_path;

            if(!isset($php_path) || $php_path == '')
                {
                return false;
                }

            $executable = array(
                'unix' => 'php',
                'win'  => 'php.exe'
            );

            return get_executable_path($php_path, $executable, $checked_path);
        }

    // No utility path found
    return false;
    }


/**
* Get full path to utility
* 
* @param string  $path
* @param array   $executable
* @param string  $checked_path
* @param boolean $check_exe
* 
* @return string|boolean
*/
function get_executable_path($path, $executable, &$checked_path, $check_exe = false)
    {
    global $config_windows;

    $os = php_uname('s');

    if($config_windows || stristr($os, 'windows'))
        {
        $checked_path = $path . "\\" . $executable['win'];

        if(file_exists($checked_path))
            {
            return escapeshellarg($checked_path) . hook('executable_add', '', array($path, $executable, $checked_path, $check_exe));
            }

        // Also check the path with a suffixed ".exe"
        if($check_exe)
            {
            $checked_path_without_exe = $checked_path;
            $checked_path             = $path . "\\" . $executable['win'] . '.exe'; 

            if(file_exists($checked_path))
                {
                return escapeshellarg($checked_path) . hook('executable_add', '', array($path, $executable, $checked_path, $check_exe));
                }

            // Return the checked path without the suffixed ".exe"
            $checked_path = $checked_path_without_exe;
            }
        }
    else
        {
        $checked_path = stripslashes($path) . '/' . $executable['unix'];

        if(file_exists($checked_path))
            {
            return escapeshellarg($checked_path) . hook('executable_add', '', array($path, $executable, $checked_path, $check_exe));
            }
        }

    // No path found
    return false;
    }


/**
 * Clean up the resource data cache to keep within $cache_array_limit
 *
 * @return void
 */
function truncate_cache_arrays()
    {
    $cache_array_limit = 2000;
    if (count($GLOBALS['get_resource_data_cache']) > $cache_array_limit)
        {
        $GLOBALS['get_resource_data_cache'] = array();
        // future improvement: get rid of only oldest, instead of clearing all?
        // this would require a way to guage the age of the entry.
        }
    if (count($GLOBALS['get_resource_path_fpcache']) > $cache_array_limit)
        {
        $GLOBALS['get_resource_path_fpcache'] = array();
        }
    }

/**
 * Work out of a string is likely to be in HTML format.
 *
 * @param  mixed $string
 * @return boolean
 */
function is_html($string)
    {
    return preg_match("/<[^<]+>/",$string,$m) != 0;
    }

/**
 * Set a cookie.
 * 
 * Note: The argument $daysexpire is not the same as the argument $expire in the PHP internal function setcookie.
 * Note: The $path argument is not used if $global_cookies = true
 *
 * @param  string $name
 * @param  string $value
 * @param  integer $daysexpire
 * @param  string $path
 * @param  string $domain
 * @param  boolean $secure
 * @param  boolean $httponly
 * @return void
 */
function rs_setcookie($name, $value, $daysexpire = 0, $path = "", $domain = "", $secure = false, $httponly = true)
    {
    global $baseurl_short;
    
    if($path == "")
        {
        $path =  $baseurl_short;     
        }
        
    if (php_sapi_name()=="cli") {return true;} # Bypass when running from the command line (e.g. for the test scripts).
    
    global $baseurl_short, $global_cookies;
    if ($daysexpire==0) {$expire = 0;}
    else {$expire = time() + (3600*24*$daysexpire);}

    if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === getservbyname("https", "tcp")))
        {
        $secure=true;
        }
        
    // Set new cookie, first remove any old previously set pages cookies to avoid clashes;           
    if ($global_cookies)
        {
        setcookie($name, "", time() - 3600, "/pages", $domain, $secure, $httponly);
        setcookie($name, $value, (int) $expire, "/", $domain, $secure, $httponly);
        }
    else
        {
        setcookie($name, "", time() - 3600, $path . "pages", $domain, $secure, $httponly);
        setcookie($name, $value, (int) $expire, $path, $domain, $secure, $httponly);
        }
    }

/**
 * Get an array of all the states that a user has edit access to
 *
 * @param  integer $userref
 * @return array
 */
function get_editable_states($userref)
    {
    global $additional_archive_states, $lang;
    if($userref==-1){return false;}
    $editable_states=array();
    $x=0;
    for ($n=-2;$n<=3;$n++)
        {
        if (checkperm("e" . $n)) {$editable_states[$x]['id']=$n;$editable_states[$x]['name']=$lang["status" . $n];$x++;}        
        }
    foreach ($additional_archive_states as $additional_archive_state)
        {
        if (checkperm("e" . $additional_archive_state)) { $editable_states[$x]['id']=$additional_archive_state;$editable_states[$x]['name']=$lang["status" . $additional_archive_state];$x++;}      
        }
    return $editable_states;
    }
        
/**
 * Returns true if $html is valid HTML, otherwise an error string describing the problem.
 *
 * @param  mixed $html
 * @return void
 */
function validate_html($html)
    {
    $parser=xml_parser_create();
    xml_parse_into_struct($parser,"<div>" . str_replace("&","&amp;",$html) . "</div>",$vals,$index);
    $errcode=xml_get_error_code($parser);
    if ($errcode!==0)
    {
    $line=xml_get_current_line_number($parser);
        
    $error=htmlspecialchars(xml_error_string($errcode)) . "<br />Line: " . $line . "<br /><br />";
    $s=explode("\n",$html);
    $error.= "<pre>" . trim(htmlspecialchars(@$s[$line-2])) . "<br />";
    $error.= "<strong>" . trim(htmlspecialchars(@$s[$line-1])) . "</strong><br />";
    $error.= trim(htmlspecialchars(@$s[$line])) . "<br /></pre>";       
    return $error;
    }
    else
        {
        return true;
        }
    }


/**
* Utility function to generate URLs with query strings easier, with the ability
* to override existing query string parameters when needed.
* 
* @param  string  $url
* @param  array   $parameters  Default query string params (e.g "k", which appears on most of ResourceSpace URLs)
* @param  array   $set_params  Override existing query string params
* 
* @return string
*/
function generateURL($url, array $parameters = array(), array $set_params = array())
    {
    foreach($set_params as $set_param => $set_value)
        {
        if('' != $set_param)
            {
            $parameters[$set_param] = $set_value;
            }
        }

    $query_string_params = array();

    foreach($parameters as $parameter => $parameter_value)
        {
        $query_string_params[] = $parameter . '=' . urlencode($parameter_value);
        }

    # Ability to hook in and change the URL.
    $hookurl=hook("generateurl","",array($url));
    if ($hookurl!==false) {$url=$hookurl;}
    
    return $url . '?' . implode ('&', $query_string_params);
    }



/**
 * Tails a file using native PHP functions.
 * 
 * First introduced with system console.
 * Credit to:
 * http://www.geekality.net/2011/05/28/php-tail-tackling-large-files
 * 
 * As of 2020-06-29 the website is showing that all contents/code are CC BY 3.0
 * https://creativecommons.org/licenses/by/3.0/
 *
 * @param  string $filename
 * @param  integer $lines
 * @param  integer $buffer
 * @return string
 */
function tail($filename, $lines = 10, $buffer = 4096)
    {
    $f = fopen($filename, "rb");        // Open the file
    fseek($f, -1, SEEK_END);        // Jump to last character

    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if(fread($f, 1) != "\n") $lines -= 1;

    // Start reading
    $output = '';
    $chunk = '';

    // While we would like more
    while(ftell($f) > 0 && $lines >= 0)
        {
        $seek = min(ftell($f), $buffer);        // Figure out how far back we should jump
        fseek($f, -$seek, SEEK_CUR);        // Do the jump (backwards, relative to where we are)
        $output = ($chunk = fread($f, $seek)).$output;      // Read a chunk and prepend it to our output
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);        // Jump back to where we started reading
        $lines -= substr_count($chunk, "\n");       // Decrease our line counter
        }

    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while($lines++ < 0)
        {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
        }

    // Close file and return
    fclose($f);
    return $output;
    }   
    


/**
* Utility function used to move the element of one array from a position 
* to another one in the same array
* Note: the manipulation is done on the same array
*
* @param  array    $array
* @param  integer  $from_index  Array index we are moving from
* @param  integer  $to_index    Array index we are moving to
*
* @return void
*/
function move_array_element(array &$array, $from_index, $to_index)
    {
    $out = array_splice($array, $from_index, 1);
    array_splice($array, $to_index, 0, $out);

    return;
    }
    
/**
 * Check if a value that may equate to false in PHP is actually a zero
 *
 * @param  mixed $value
 * @return boolean  
 */
function emptyiszero($value)
    {
    return ($value !== null && $value !== false && trim($value) !== '');
    }


/**
* Get data for each image that should be used on the slideshow.
* The format of the returned array should be: 
* Array
* (
*     [0] => Array
*         (
*             [ref] => 1
*             [resource_ref] => 
*             [homepage_show] => 1
*             [featured_collections_show] => 0
*             [login_show] => 1
*             [file_path] => /var/www/filestore/system/slideshow_1bf4796ac6f051a/1.jpg
*             [checksum] => 1539875502
*         )
* 
*     [1] => Array
*         (
*             [ref] => 4
*             [resource_ref] => 19
*             [homepage_show] => 1
*             [featured_collections_show] => 0
*             [login_show] => 0
*             [file_path] => /var/www/filestore/system/slideshow_1bf4796ac6f051a/4.jpg
*             [checksum] => 1542818794
*             [link] => http://localhost/?r=19
*         )
* 
* )
* 
* @return array
*/
function get_slideshow_files_data()
    {
    global $baseurl, $homeanim_folder;

    $homeanim_folder_path = dirname(__DIR__) . "/{$homeanim_folder}";

    $query = "SELECT ref, resource_ref, homepage_show, featured_collections_show, login_show FROM slideshow";
    $slideshow_records = sql_query($query, "slideshow");

    $slideshow_files = array();

    foreach($slideshow_records as $slideshow)
        {
        $slideshow_file = $slideshow;

        $image_file_path = "{$homeanim_folder_path}/{$slideshow['ref']}.jpg";

        if(!file_exists($image_file_path) || !is_readable($image_file_path))
            {
            continue;
            }

        $slideshow_file['checksum'] = filemtime($image_file_path);
        $slideshow_file['file_path'] = $image_file_path;
        $slideshow_file['file_url'] = generateURL(
            "{$baseurl}/pages/download.php",
            array(
                'slideshow' => $slideshow['ref'],
                'nc' => $slideshow_file['checksum'],
            ));

        if((int) $slideshow['resource_ref'] > 0)
            {
            $slideshow_file['link'] = generateURL($baseurl, array('r' => $slideshow['resource_ref']));
            }

        $slideshow_files[] = $slideshow_file;
        }

    return $slideshow_files;
    }
        
/**
 * Returns a sanitised row from the table in a safe form for use in a form value, 
 * suitable overwritten by POSTed data if it has been supplied.
 *
 * @param  array $row
 * @param  string $name
 * @param  string $default
 * @return string
 */
function form_value_display($row,$name,$default="")
    {
    if (!is_array($row)) {return false;}
    if (array_key_exists($name,$row)) {$default=$row[$name];}
    return htmlspecialchars(getval($name,$default));
    }

/**
 * Adds a job to the job_queue table.
 *
 * @param  string $type
 * @param  array $job_data
 * @param  string $user
 * @param  string $time
 * @param  string $success_text
 * @param  string $failure_text
 * @param  string $job_code
 * @return string|integer ID of newly created job or error text
 */
function job_queue_add($type="",$job_data=array(),$user="",$time="", $success_text="", $failure_text="", $job_code="")
    {
    global $lang, $userref;
    if($time==""){$time=date('Y-m-d H:i:s');}
    if($type==""){return false;}
    if($user==""){$user=isset($userref)?$userref:0;}
    $job_data_json=json_encode($job_data,JSON_UNESCAPED_SLASHES); // JSON_UNESCAPED_SLASHES is needed so we can effectively compare jobs
    
    if($job_code == "")
        {
        // Generate a code based on job data to avoid incorrect duplicate job detection
        $job_code = $type . "_" . substr(md5(serialize($job_data)),10);
        }

    // Check for existing job matching
    $existing_user_jobs=job_queue_get_jobs($type,STATUS_ACTIVE,"",$job_code);
    if(count($existing_user_jobs)>0)
            {
            return $lang["job_queue_duplicate_message"];
            }
    sql_query("INSERT INTO job_queue (type,job_data,user,start_date,status,success_text,failure_text,job_code) VALUES('" . escape_check($type) . "','" . escape_check($job_data_json) . "','" . $user . "','" . $time . "','" . STATUS_ACTIVE .  "','" . escape_check($success_text) . "','" . escape_check($failure_text) . "','" . escape_check($job_code) . "')");
    return sql_insert_id();
    }
    
/**
 * Update the data/status/time of a job queue record.
 *
 * @param  integer $ref
 * @param  array $job_data - pass empty array to leave unchanged
 * @param  string $newstatus
 * @param  string $newtime
 * @return void
 */
function job_queue_update($ref,$job_data=array(),$newstatus="", $newtime="")
    {
    $update_sql = array();
    if (count($job_data) > 0)
        {
        $update_sql[] = "job_data='" . escape_check(json_encode($job_data)) . "'";
        } 
    if($newtime!="")
        {
        $update_sql[] = "start_date='" . escape_check($newtime) . "'";
        }
    if($newstatus!="")
        {
        $update_sql[] = "status='" . escape_check($newstatus) . "'";
        }
    if(count($update_sql) == 0)
        {
        return false;
        }
    
    $sql = "UPDATE job_queue SET " . implode(",",$update_sql) . " WHERE ref='" . $ref . "'";
    sql_query($sql);
    }

/**
 * Delete a job queue entry if user owns job or user is admin
 *
 * @param  mixed $ref
 * @return void
 */
function job_queue_delete($ref)
    {
    global $userref;
    $limitsql = (checkperm('a') || php_sapi_name() == "cli") ? "" : " AND user='" . $userref . "'";
    sql_query("DELETE FROM job_queue WHERE ref='" . $ref . "' " .  $limitsql);
    }

/**
 * Gets a list of offline jobs
 *
 * @param  string $type         Job type
 * @param  string $status       Job status - see definitions.php
 * @param  int    $user         Job user
 * @param  string $job_code     Unique job code
 * @param  string $job_order_by 
 * @param  string $job_sort
 * @param  string $find
 * @return array
 */
function job_queue_get_jobs($type="", $status="", $user="", $job_code="", $job_order_by="ref", $job_sort="desc", $find="")
    {
    global $userref;
    $condition=array();
    if($type!="")
        {
        $condition[] = " type ='" . escape_check($type) . "'";
        }
    if(!checkperm('a') && PHP_SAPI != 'cli')
        {
        // Don't show certain jobs for normal users
        $hiddentypes = array();
        $hiddentypes[] = "delete_file";
        $condition[] = " type NOT IN ('" . implode("','",$hiddentypes) . "')";  
        }
    if($status != "" && (int)$status > -1){$condition[] =" status ='" . escape_check($status) . "'";}
    if($user!="" && (int)$user > 0 && ($user == $userref || checkperm_user_edit($user)))
        {
        $condition[] = " user ='" . escape_check($user) . "'";
        }
    elseif(PHP_SAPI != "CLI" && isset($userref))
        {
        $condition[] = " user ='" . $userref . "'";
        }
    if($job_code!=""){$condition[] =" job_code ='" . escape_check($job_code) . "'";}
    if($find!="")
        {
        $find=escape_check($find);
        $condition[] = " (j.ref LIKE '%" . $find . "%'  OR j.job_data LIKE '%" . $find . "%' OR j.success_text LIKE '%" . $find . "%' OR j.failure_text LIKE '%" . $find . "%' OR j.user LIKE '%" . $find . "%' OR u.username LIKE '%" . $find . "%' or u.fullname LIKE '%" . $find . "%')";
        }
    $conditional_sql="";
    if (count($condition)>0){$conditional_sql=" where " . implode(" and ",$condition);}
        
    $sql = "SELECT j.ref,j.type,j.job_data,j.user,j.status, j.start_date, j.success_text, j.failure_text,j.job_code, u.username, u.fullname FROM job_queue j LEFT JOIN user u ON u.ref=j.user " . $conditional_sql . " ORDER BY " . escape_check($job_order_by) . " " . escape_check($job_sort);
    $jobs=sql_query($sql);
    return $jobs;
    }

/**
 * Get details of specified offline job
 *
 * @param  int $job identifier
 * @return array
 */
function job_queue_get_job($ref)
    {
    $sql = "SELECT j.ref,j.type,j.job_data,j.user,j.status, j.start_date, j.success_text, j.failure_text,j.job_code, u.username, u.fullname FROM job_queue j LEFT JOIN user u ON u.ref=j.user WHERE j.ref='" . (int)$ref . "'";
    $job_data=sql_query($sql);

    return (is_array($job_data) && count($job_data)>0) ? $job_data[0] : array();
    }    

/**
 * Delete all jobs in the specified state
 *
 * @param  int $status to purge, whole queue will be purged if not set
 * @return void
 */
function job_queue_purge($status=0)
    {
    $deletejobs = job_queue_get_jobs('',$status == 0 ? '' : $status);
    if(count($deletejobs) > 0)
        {
        sql_query("DELETE FROM job_queue WHERE ref IN ('" . implode("','",array_column($deletejobs,"ref")) . "')");
        }
    }

/**
* Run offline job
* 
* @param  array    $job                 Metadata of the queued job as returned by job_queue_get_jobs()
* @param  boolean  $clear_process_lock  Clear process lock for this job
* 
* @return void
*/
function job_queue_run_job($job, $clear_process_lock)
    {
    // Runs offline job using defined job handler
    $jobref = $job["ref"];
    
    // Control characters in job_data can cause decoding issues
    //$job["job_data"] = escape_check($job["job_data"]);

    $job_data=json_decode($job["job_data"], true);

    $jobuser = $job["user"];
    if (!isset($jobuser) || $jobuser == 0 || $jobuser == "")
        {
        $logmessage = " - Job could not be run as no user was supplied #{$jobref}" . PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        job_queue_update($jobref,$job_data,STATUS_ERROR);
        return;
        }

    $jobuserdata = get_user($jobuser);
    setup_user($jobuserdata);
    $job_success_text=$job["success_text"];
    $job_failure_text=$job["failure_text"];

    // Variable used to avoid spinning off offline jobs from an already existing job.
    // Example: create_previews() is using extract_text() and both can run offline.
    global $offline_job_in_progress, $plugins;
    $offline_job_in_progress = false;

    if(is_process_lock('job_' . $jobref) && !$clear_process_lock)
        {
        $logmessage =  " - Process lock for job #{$jobref}" . PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        return;
        }
    else if($clear_process_lock)
        {
        $logmessage =  " - Clearing process lock for job #{$jobref}" . PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        clear_process_lock("job_{$jobref}");
        }
    
    set_process_lock('job_' . $jobref);
    
    $logmessage =  "Running job #" . $jobref . PHP_EOL;
    echo $logmessage;
    debug($logmessage);

    $logmessage =  " - Looking for " . __DIR__ . "/job_handlers/" . $job["type"] . ".php" . PHP_EOL;
    echo $logmessage;
    debug($logmessage);

    if (file_exists(__DIR__ . "/job_handlers/" . $job["type"] . ".php"))
        {
        $logmessage=" - Attempting to run job #" . $jobref . " using handler " . $job["type"]. PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        job_queue_update($jobref, $job_data,STATUS_INPROGRESS);
        $offline_job_in_progress = true;
        include __DIR__ . "/job_handlers/" . $job["type"] . ".php";
        job_queue_update($jobref, $job_data,STATUS_COMPLETE);
        }
    else
        {
        // Check for handler in plugin
        $offline_plugins = $plugins;

        // Include plugins for this job user's group
        $group_plugins = sql_query("SELECT name, config, config_json, disable_group_select FROM plugins WHERE inst_version>=0 AND disable_group_select=0 AND find_in_set('" . $jobuserdata["usergroup"] . "',enabled_groups) ORDER BY priority","plugins");
        foreach($group_plugins as $group_plugin)
            {
            include_plugin_config($group_plugin['name'],$group_plugin['config'],$group_plugin['config_json']);
            register_plugin($group_plugin['name']);
            register_plugin_language($group_plugin['name']);
            $offline_plugins[]=$group_plugin['name'];
            }	

        foreach($offline_plugins as $plugin)
            {
            if (file_exists(__DIR__ . "/../plugins/" . $plugin . "/job_handlers/" . $job["type"] . ".php"))
                {
                $logmessage=" - Attempting to run job #" . $jobref . " using handler " . $job["type"]. PHP_EOL;
                echo $logmessage;
                debug($logmessage);
                job_queue_update($jobref, $job_data,STATUS_INPROGRESS);
                $offline_job_in_progress = true;
                include __DIR__ . "/../plugins/" . $plugin . "/job_handlers/" . $job["type"] . ".php";
                job_queue_update($jobref, $job_data,STATUS_COMPLETE);
                break;
                }
            }
        }
    
    if(!$offline_job_in_progress)
        {
        $logmessage="Unable to find handlerfile: " . $job["type"]. PHP_EOL;
        echo $logmessage;
        debug($logmessage);
        job_queue_update($jobref,$job_data,STATUS_ERROR);
        }
    
    $logmessage =  " - Finished job #" . $jobref . PHP_EOL;
    echo $logmessage;
    debug($logmessage);
    
    clear_process_lock('job_' . $jobref);
    }
        
/**
 * Change the user's user group
 *
 * @param  integer $user
 * @param  integer $usergroup
 * @return void
 */
function user_set_usergroup($user,$usergroup)
    {
    sql_query("update user set usergroup='" . escape_check($usergroup) . "' where ref='" . escape_check($user) . "'");
    }


/**
 * Generates a random string of requested length.
 * 
 * Used to generate initial spider and scramble keys.
 * 
 * @param  int    $length Lenght of desired string of bytes
 * @return string         Random character string
 */
function generateSecureKey($length = 64)
    {
    $bytes = openssl_random_pseudo_bytes($length / 2);
    $hex   = substr(bin2hex($bytes), 0, 64); 

    return $hex;
    }

/**
 * Check if current page is a modal and set global $modal variable if not already set
 *
 * @return boolean  true if modal, false otherwise
 */
function IsModal()
    {
    global $modal;
    if(isset($modal) && $modal)
        {
        return true;
        }
    $modal = (getval("modal","") == "true");
    return $modal;
    }

/**
* Generates a CSRF token (Encrypted Token Pattern)
* 
* @uses generateSecureKey()
* @uses rsEncrypt()
* 
* @param  string  $session_id  The current user session ID
* @param  string  $form_id     A unique form ID
* 
* @return  string  Token
*/
function generateCSRFToken($session_id, $form_id)
    {
    // IMPORTANT: keep nonce at the beginning of the data array
    $data = json_encode(array(
        "nonce"     => generateSecureKey(128),
        "session"   => $session_id,
        "timestamp" => time(),
        "form_id"   => $form_id
    ));

    return rsEncrypt($data, $session_id);
    }

/**
* Checks if CSRF Token is valid
* 
* @uses rsDecrypt()
* 
* @return boolean  Returns TRUE if token has been decrypted or CSRF is not enabled, FALSE otherwise
*/
function isValidCSRFToken($token_data, $session_id)
    {
    global $CSRF_enabled;

    if(!$CSRF_enabled)
        {
        return true;
        }

    if($token_data === "")
        {
        debug("CSRF: INVALID - no token data");
        return false;
        }

    $plaintext = rsDecrypt($token_data, $session_id);

    if($plaintext === false)
        {
        debug("CSRF: INVALID - unable to decrypt token data");
        return false;
        }

    $csrf_data = json_decode($plaintext, true);

    if($csrf_data["session"] == $session_id)
        {
        return true;
        }

    debug("CSRF: INVALID - session ID did not match: {$csrf_data['session']} vs {$session_id}");

    return false;
    }


/**
* Render the CSRF Token input tag
* 
* @uses generateCSRFToken()
* 
* @param string $form_id The id/ name attribute of the form
* 
* @return void
*/
function generateFormToken($form_id)
    {
    global $CSRF_enabled, $CSRF_token_identifier, $usersession;

    if(!$CSRF_enabled)
        {
        return;
        }

    $token = generateCSRFToken($usersession, $form_id);
    ?>
    <input type="hidden" name="<?php echo $CSRF_token_identifier; ?>" value="<?php echo $token; ?>">
    <?php
    return;
    }


/**
* Render the CSRF Token for AJAX use
* 
* @uses generateCSRFToken()
* 
* @param string $form_id The id/ name attribute of the form or just the calling function for this type of request
* 
* @return string
*/
function generateAjaxToken($form_id)
    {
    global $CSRF_enabled, $CSRF_token_identifier, $usersession;

    if(!$CSRF_enabled)
        {
        return "";
        }

    $token = generateCSRFToken($usersession, $form_id);

    return "{$CSRF_token_identifier}: \"{$token}\"";
    }


/**
* Enforce using POST requests
* 
* @param  boolean  $ajax  Set to TRUE if request is done via AJAX
* 
* @return  boolean|void  Returns true if request method is POST or sends 405 header otherwise
*/
function enforcePostRequest($ajax)
    {
    if($_SERVER["REQUEST_METHOD"] === "POST")
        {
        return true;
        }

    header("HTTP/1.1 405 Method Not Allowed");

    $ajax = filter_var($ajax, FILTER_VALIDATE_BOOLEAN);
    if($ajax)
        {
        global $lang;

        $return["error"] = array(
            "status" => 405,
            "title"  => $lang["error-method-not_allowed"],
            "detail" => $lang["error-405-method-not_allowed"]
        );

        echo json_encode($return);
        exit();
        }

    return false;
    }



/**
* Check if ResourceSpace is up to date or an upgrade is available
* 
* @uses get_sysvar()
* @uses set_sysvar()
* 
* @return boolean
*/
function is_resourcespace_upgrade_available()
    {
    $cvn_cache = get_sysvar('centralised_version_number');
    $last_cvn_update = get_sysvar('last_cvn_update');

    $centralised_version_number = $cvn_cache;
    debug("RS_UPGRADE_AVAILABLE: cvn_cache = {$cvn_cache}");
    debug("RS_UPGRADE_AVAILABLE: last_cvn_update = $last_cvn_update");
    if($last_cvn_update !== false)
        {
        $cvn_cache_interval = DateTime::createFromFormat('Y-m-d H:i:s', $last_cvn_update)->diff(new DateTime());

        if($cvn_cache_interval->days >= 1)
            {
            $centralised_version_number = false;
            }
        }

    if($centralised_version_number === false)
        {
        $default_socket_timeout_cache = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout',5); //Set timeout to 5 seconds incase server cannot access resourcespace.com
        $centralised_version_number = @file_get_contents('https://www.resourcespace.com/current_release.txt');
        ini_set('default_socket_timeout',$default_socket_timeout_cache);
        debug("RS_UPGRADE_AVAILABLE: centralised_version_number = $centralised_version_number");
        if($centralised_version_number === false)
            {
            debug("RS_UPGRADE_AVAILABLE: unable to get centralised_version_number from https://www.resourcespace.com/current_release.txt");
            set_sysvar('last_cvn_update', date('Y-m-d H:i:s'));
            return false; 
            }

        set_sysvar('centralised_version_number', $centralised_version_number);
        set_sysvar('last_cvn_update', date('Y-m-d H:i:s'));
        }

    $get_version_details = function($version)
        {
        $version_data = explode('.', $version);

        if(empty($version_data))
            {
            return array();
            }

        $return = array(
            'major' => isset($version_data[0]) ? (int) $version_data[0] : 0,
            'minor' => isset($version_data[1]) ? (int) $version_data[1] : 0,
            'revision' => isset($version_data[2]) ? (int) $version_data[2] : 0,
        );

        if($return['major'] == 0)
            {
            return array();
            }

        return $return;
        };

    $product_version = trim(str_replace('SVN', '', $GLOBALS['productversion']));
    $product_version_data = $get_version_details($product_version);

    $cvn_data = $get_version_details($centralised_version_number);

    debug("RS_UPGRADE_AVAILABLE: product_version = $product_version");
    debug("RS_UPGRADE_AVAILABLE: centralised_version_number = $centralised_version_number");

    if(empty($product_version_data) || empty($cvn_data))
        {
        return false;
        }

    if($product_version_data['major'] != $cvn_data['major'] && $product_version_data['major'] < $cvn_data['major'])
        {
        return true;
        }
    else if(
        $product_version_data['major'] == $cvn_data['major']
        && $product_version_data['minor'] != $cvn_data['minor']
        && $product_version_data['minor'] < $cvn_data['minor'])
        {
        return true;
        }
    else if(
        $product_version_data['major'] < $cvn_data['major']
        && $product_version_data['minor'] != $cvn_data['minor']
        && $product_version_data['minor'] < $cvn_data['minor'])
        {
        return true;
        }

    return false;
    }


/**
 * Fetch a count of recently active users
 *
 * @param  mixed $days  How many days to look back
 * @return integer
 */
function get_recent_users($days)
    {
    return (sql_value("select count(*) value from user where datediff(now(),last_active) <= '" . escape_check($days) . "'",0));
    }


/**
* Check if script last ran more than the failure notification days
* Note: Never/ period longer than allowed failure should return false
* 
* @param string   $name                   Name of the sysvar to check the record for
* @param integer  $fail_notify_allowance  How long to allow (in days) before user can consider script has failed
* @param string   $last_ran_datetime      Datetime (string format) when script was last run
* 
* @return boolean
*/
function check_script_last_ran($name, $fail_notify_allowance, &$last_ran_datetime)
    {
    $last_ran_datetime = (trim($last_ran_datetime) === '' ? $GLOBALS['lang']['status-never'] : $last_ran_datetime);

    if(trim($name) === '')
        {
        return false;
        }
    $name = escape_check($name);

    $script_last_ran = sql_value("SELECT `value` FROM sysvars WHERE name = '{$name}'", '');
    $script_failure_notify_seconds = intval($fail_notify_allowance) * 24 * 60 * 60;

    if('' != $script_last_ran)
        {
        $last_ran_datetime = date('l F jS Y @ H:m:s', strtotime($script_last_ran));

        // It's been less than user allows it to last run, meaning it is all good!
        if(time() < (strtotime($script_last_ran) + $script_failure_notify_seconds))
            {
            return true;
            }
        }

    return false;
    }


/**
* Counting errors found in a collection of items. An error is found when an item has an "error" key.
* 
* @param  array  $a  Collection of items that may contain errors.
* 
* @return integer
*/
function count_errors(array $a)
    {
    return array_reduce(
        $a,
        function($carry, $item)
            {
            if(isset($item["error"]))
                {
                return ++$carry;
                }

            return $carry;
            },
        0);
    }




/**
 * Function can be used to order a multi-dimensional array using a key and corresponding value
 * 
 * @param   array   $array2search   multi-dimensional array in which the key/value pair may be present
 * @param   string  $search_key     key of the key/value pair used for search
 * @param   string  $search_value   value of the key/value pair to search
 * @param   array   $return_array   array to which the matching elements in the search array are pushed - also returned by function
 * 
 * @return  array   $return_array
 */
function search_array_by_keyvalue($array2search, $search_key, $search_value, $return_array)    
    {
    if (!isset($search_key,$search_value,$array2search,$return_array) || !is_array($array2search) || !is_array($return_array))
        {
        exit("Error: invalid input to search_array_by_keyvalue function");
        }    

    // loop through array to search    
    foreach($array2search as $sub_array)
        {
        // if the search key exists and its value matches the search value    
        if (array_key_exists($search_key, $sub_array) && ($sub_array[$search_key] == $search_value))
            {
            // push the sub array to the return array    
            array_push($return_array, $sub_array);
            }
        }

    return $return_array; 
    }


/**
* Temporary bypass access controls for a particular function
* 
* When functions check for permissions internally, in order to keep backwards compatibility it may be better if we 
* temporarily bypass the permissions instead of adding a parameter to the function for this. It will allow developers to 
* keep the code clean.
* 
* IMPORTANT: never make this function public to the API.
* 
* Example code:
* $log = bypass_permissions(array("v"), "get_resource_log", array($ref));
* 
* @param array     $perms  Permission list to be bypassed
* @param callable  $f      Callable that we need to bypas permissions for
* @param array     $p      Parameters to be passed to the callable if required
* 
* @return mixed
*/
function bypass_permissions(array $perms, callable $f, array $p = array())
    {
    global $userpermissions;

    if(empty($perms))
        {
        return call_user_func_array($f, $p);
        }

    // fake having these permissions temporarily
    $o_perm = $userpermissions;
    $userpermissions = array_values(array_merge($userpermissions, $perms));

    $result = call_user_func_array($f, $p);

    $userpermissions = $o_perm;

    return $result;
    }

/**
 * Set a system variable (which is stored in the sysvars table) - set to null to remove
 *
 * @param  mixed $name      Variable name
 * @param  mixed $value     String to set a new value; null to remove any existing value.
 * @return void
 */
function set_sysvar($name,$value=null)
    {
    global $sysvars;
    $name=escape_check($name);
    db_begin_transaction("set_sysvar");
    sql_query("DELETE FROM `sysvars` WHERE `name`='{$name}'");
    if($value!=null)
        {
        $safevalue=escape_check($value);
        sql_query("INSERT INTO `sysvars`(`name`,`value`) values('{$name}','{$safevalue}')");
        }
    db_end_transaction("set_sysvar");

    //Update the $sysvars array or get_sysvar() won't be aware of this change
    $sysvars[$name] = $value;
    }

/**
 * Get a system variable (which is received from the sysvars table)
 *
 * @param  string $name
 * @param  string $default  Returned if no matching variable was found
 * @return string
 */
function get_sysvar($name, $default=false)
    {
	// Check the global array.
	global $sysvars;
    if (isset($sysvars) && array_key_exists($name,$sysvars))
        {
        return $sysvars[$name];
        }

    // Load from db or return default
    $name=escape_check($name);
    return sql_value("SELECT `value` FROM `sysvars` WHERE `name`='{$name}'",$default);
    }

/**
 * Plugin architecture.  Look for hooks with this name (and corresponding page, if applicable) and run them sequentially.
 * Utilises a cache for significantly better performance.
 * Enable $draw_performance_footer in config.php to see stats.
 *
 * @param  string $name
 * @param  string $pagename
 * @param  string $params
 * @param  boolean $last_hook_value_wins
 * @return mixed
 */
function hook($name,$pagename="",$params=array(),$last_hook_value_wins=false)
	{

	global $hook_cache;
	if($pagename == '')
		{
		global $pagename;
		}
	
	# the index name for the $hook_cache
	$hook_cache_index = $name . "|" . $pagename;
	
	# we have already processed this hook name and page combination before so return from cache
	if (isset($hook_cache[$hook_cache_index]))
		{
		# increment stats
		global $hook_cache_hits;
		$hook_cache_hits++;

		unset($GLOBALS['hook_return_value']);
		$empty_global_return_value=true;
		// we use $GLOBALS['hook_return_value'] so that hooks can directly modify the overall return value

		foreach ($hook_cache[$hook_cache_index] as $function)
			{
			$function_return_value = call_user_func_array($function, $params);

			if ($function_return_value === null)
				{
				continue;	// the function did not return a value so skip to next hook call
				}

			if (!$last_hook_value_wins && !$empty_global_return_value &&
				isset($GLOBALS['hook_return_value']) &&
				(gettype($GLOBALS['hook_return_value']) == gettype($function_return_value)) &&
				(is_array($function_return_value) || is_string($function_return_value) || is_bool($function_return_value)))
				{
				if (is_array($function_return_value))
					{
					// We merge the cached result with the new result from the plugin and remove any duplicates
					// Note: in custom plugins developers should work with the full array (ie. superset) rather than just a sub-set of the array.
					//       If your plugin needs to know if the array has been modified previously by other plugins use the global variable "hook_return_value"
					$numeric_key=false;
					foreach($GLOBALS['hook_return_value'] as $key=> $value){
						if(is_numeric($key)){
							$numeric_key=true;
						}
						else{
							$numeric_key=false;
						}
						break;
					}
					if($numeric_key){
						$GLOBALS['hook_return_value'] = array_values(array_unique(array_merge_recursive($GLOBALS['hook_return_value'], $function_return_value), SORT_REGULAR));
					}
					else{
						$GLOBALS['hook_return_value'] = array_unique(array_merge_recursive($GLOBALS['hook_return_value'], $function_return_value), SORT_REGULAR);
					}
					}
				elseif (is_string($function_return_value))
					{
					$GLOBALS['hook_return_value'] .= $function_return_value;		// appends string
					}
				elseif (is_bool($function_return_value))
					{
					$GLOBALS['hook_return_value'] = $GLOBALS['hook_return_value'] || $function_return_value;		// boolean OR
					}
				}
			else
				{
				$GLOBALS['hook_return_value'] = $function_return_value;
				$empty_global_return_value=false;
				}
			}

		return (isset($GLOBALS['hook_return_value']) ? $GLOBALS['hook_return_value'] : false);
		}

	# we have not encountered this hook and page combination before so go add it
	global $plugins;
	
	# this will hold all of the functions to call when hitting this hook name and page combination
	$function_list = array();

	for ($n=0;$n<count($plugins);$n++)
		{	
		# "All" hooks
        $function= isset($plugins[$n]) ? "Hook" . ucfirst($plugins[$n]) . "All" . ucfirst($name) : "";	
        	
		if (function_exists($function)) 
			{			
			$function_list[]=$function;
			}
		else 
			{
			# Specific hook	
			$function= isset($plugins[$n]) ? "Hook" . ucfirst($plugins[$n]) . ucfirst($pagename) . ucfirst($name) : "";
			if (function_exists($function)) 
				{
				$function_list[]=$function;
				}
			}
		}	
	
	# add the function list to cache
	$hook_cache[$hook_cache_index] = $function_list;

	# do a callback to run the function(s) - this will not cause an infinite loop as we have just added to cache for execution.
	return hook($name, $pagename, $params, $last_hook_value_wins);
    }
    

/**
* Utility function to remove unwanted HTML tags and attributes.
* Note: if $html is a full page, developers should allow html and body tags.
* 
* @param string $html       HTML string
* @param array  $tags       Extra tags to be allowed
* @param array  $attributes Extra attributes to be allowed
*  
* @return string
*/
function strip_tags_and_attributes($html, array $tags = array(), array $attributes = array())
    {
	global $permitted_html_tags, $permitted_html_attributes;
	
    if(!is_string($html) || 0 === strlen($html))
        {
        return $html;
        }

    //Convert to html before loading into libxml as we will lose non-ASCII characters otherwise
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

    // Basic way of telling whether we had any tags previously
    // This allows us to know that the returned value should actually be just text rather than HTML
    // (DOMDocument::saveHTML() returns a text string as a string wrapped in a <p> tag)
    $is_html = ($html != strip_tags($html));
    
    $allowed_tags = array_merge($permitted_html_tags, $tags);
    $allowed_attributes = array_merge($permitted_html_attributes, $attributes);

    // Step 1 - Check DOM
    libxml_use_internal_errors(true);

    $doc           = new DOMDocument();
    $doc->encoding = 'UTF-8';

    $process_html = $doc->loadHTML($html);

    if($process_html)
        {
        foreach($doc->getElementsByTagName('*') as $tag)
            {
            if(!in_array($tag->tagName, $allowed_tags))
                {
                $tag->parentNode->removeChild($tag);

                continue;
                }

            if(!$tag->hasAttributes())
                {
                continue;
                }

            foreach($tag->attributes as $attribute)
                {
                if(!in_array($attribute->nodeName, $allowed_attributes))
                    {
                    $tag->removeAttribute($attribute->nodeName);
                    }
                }
            }

        $html = $doc->saveHTML();

        if(false !== strpos($html, '<body>'))
            {
            $body_o_tag_pos = strpos($html, '<body>');
            $body_c_tag_pos = strpos($html, '</body>');

            $html = substr($html, $body_o_tag_pos + 6, $body_c_tag_pos - ($body_o_tag_pos + 6));
            }
        }

    // Step 2 - Use regular expressions
    // Note: this step is required because PHP built-in functions for DOM sometimes don't
    // pick up certain attributes. I was getting errors of "Not yet implemented." when debugging
    preg_match_all('/[a-z]+=".+"/iU', $html, $attributes);

    foreach($attributes[0] as $attribute)
        {
        $attribute_name = stristr($attribute, '=', true);

        if(!in_array($attribute_name, $allowed_attributes))
            {
            $html = str_replace(' ' . $attribute, '', $html);
            }
        }

    $html = trim($html, "\r\n");

    if(!$is_html)
        {
        // DOMDocument::saveHTML() returns a text string as a string wrapped in a <p> tag
        $html = strip_tags($html);
        }

    // Revert back to UTF-8
    $html = mb_convert_encoding($html, 'UTF-8','HTML-ENTITIES');

    return $html;
    }


/**
* Helper function to quickly return the inner HTML of a specific tag element from a DOM document.
* Example usage:
* get_inner_html_from_tag(strip_tags_and_attributes($unsafe_html), "p");
* 
* @param string $txt HTML string
* @param string $tag DOM document tag element (e.g a, div, p)
* 
* @return string Returns the inner HTML of the first tag requested and found. Returns empty string if caller code 
*                requested the wrong tag.
*/
function get_inner_html_from_tag(string $txt, string $tag)
    {
    //Convert to html before loading into libxml as we will lose non-ASCII characters otherwise
    $html = mb_convert_encoding($txt, "HTML-ENTITIES", "UTF-8");

    if($html == strip_tags($txt))
        {
        return $txt;
        }

    $inner_html = "";

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->encoding = "UTF-8";
    $process_html = $doc->loadHTML($html);
    $found_tag_elements = $doc->getElementsByTagName($tag);

    if($process_html && $found_tag_elements->length > 0)
        {
        $found_first_tag_el = $found_tag_elements->item(0);

        foreach($found_first_tag_el->childNodes as $child_node)
            {
            $tmp_doc = new DOMDocument();
            $tmp_doc->encoding = "UTF-8";

            // Import the node, and all its children, to the temp document and then append it to the doc
            $tmp_doc->appendChild($tmp_doc->importNode($child_node, true));

            $inner_html .= $tmp_doc->saveHTML();
            }
        }

    // Revert back to UTF-8
    $inner_html = mb_convert_encoding($inner_html, "UTF-8","HTML-ENTITIES");

    return $inner_html;
    }


/**
 * Returns the page load time until this point.
 *
 * @return string
 */
function show_pagetime()
    {
    global $pagetime_start;
    $time = microtime();
    $time = explode(' ', $time);
    $time = $time[1] + $time[0];
    $total_time = round(($time - $pagetime_start), 4);
    echo $total_time." sec";
    }

/**
 * Determines where the debug log will live.  Typically, same as tmp dir (See general.php: get_temp_dir().
 * Since general.php may not be included, we cannot use that method so I have created this one too.
 * 
 * @return string - The path to the debug_log directory.
 */
function get_debug_log_dir()
    {
    global $tempdir, $storagedir;
    
    // Set up the default.
    $result = dirname(dirname(__FILE__)) . "/filestore/tmp";

    // if $tempdir is explicity set, use it.
    if(isset($tempdir))
    {
        // Make sure the dir exists.
        if(!is_dir($tempdir))
        {
            // If it does not exist, create it.
            mkdir($tempdir, 0777);
        }
        $result = $tempdir;
    }
    // Otherwise, if $storagedir is set, use it.
    else if (isset($storagedir))
    {
        // Make sure the dir exists.
        if(!is_dir($storagedir . "/tmp"))
        {
            // If it does not exist, create it.
            mkdir($storagedir . "/tmp", 0777);
        }
        $result = $storagedir . "/tmp";
    }
    else
    {
        // Make sure the dir exists.
        if(!is_dir($result))
        {
            // If it does not exist, create it.
            mkdir($result, 0777);
        }
    }
    // return the result.
    return $result;
    }


/**
 * Output debug information to the debug log, if debugging is enabled.
 *
 * @param  string $text
 * @param  mixed $resource_log_resource_ref Update the resource log if resource reference passed.
 * @param  string $resource_log_code    If updating the resource log, the code to use
 * @return boolean
 */
function debug($text,$resource_log_resource_ref=null,$resource_log_code=LOG_CODE_TRANSFORMED)
	{
    # Update the resource log if resource reference passed.
	if(!is_null($resource_log_resource_ref))
        {
        resource_log($resource_log_resource_ref,$resource_log_code,'','','',$text);
        }

	# Output some text to a debug file.
	# For developers only
	global $debug_log, $debug_log_override, $debug_log_location, $debug_extended_info;
	if (!$debug_log && !$debug_log_override) {return true;} # Do not execute if switched off.
	
	# Cannot use the general.php: get_temp_dir() method here since general may not have been included.
	if (isset($debug_log_location))
		{
		$debugdir = dirname($debug_log_location);
		if (!is_dir($debugdir)){mkdir($debugdir, 0755, true);}
		}
	else 
		{
		$debug_log_location=get_debug_log_dir() . "/debug.txt";
		}
	if(!file_exists($debug_log_location))
		{
		// Set the permissions if we can to prevent browser access (will not work on Windows)
		$f=fopen($debug_log_location,"a");
		chmod($debug_log_location,0333);
		}
    else
        {
		$f=fopen($debug_log_location,"a");
		}
	
	$extendedtext = "";	
	if(isset($debug_extended_info) && $debug_extended_info && function_exists("debug_backtrace"))
		{
		$backtrace = debug_backtrace(0);
		$btc = count($backtrace);
		$callingfunctions = array();
		$page = "";
		for($n=$btc;$n>0;$n--)
			{
			if($page == "" && isset($backtrace[$n]["file"]))
				{
				$page = $backtrace[$n]["file"];
				}
				
			if(isset($backtrace[$n]["function"]) && !in_array($backtrace[$n]["function"],array("sql_connect","sql_query","sql_value","sql_array")))
				{
				if(in_array($backtrace[$n]["function"],array("include","include_once","require","require_once")) && isset($backtrace[$n]["args"][0]))
					{
					$callingfunctions[] = $backtrace[$n]["args"][0];
					}
				else
					{
					$callingfunctions[] = $backtrace[$n]["function"];
					}
				}
			}
		$extendedtext .= "[" . $page . "] " . (count($callingfunctions)>0 ? "(" . implode("->",$callingfunctions)  . ") " : " ");
		}
		
    fwrite($f,date("Y-m-d H:i:s") . " " . $extendedtext . $text . "\n");
    fclose ($f);
	return true;
	}
    
/**
 * Recursively removes a directory.
 *  
 * @param string $path Directory path to remove.
 *
 * @return boolean
 */
function rcRmdir ($path)
    {
    debug("rcRmdir: " . $path);
    if (is_dir($path))
        {
        $foldercontents = new DirectoryIterator($path);
        foreach($foldercontents as $objectindex => $object)
            {
            if($object->isDot())
                {
                continue;
                }
            $objectname = $object->getFilename();

            if ($object->isDir() && $object->isWritable())
                {
                $success = rcRmdir($path . DIRECTORY_SEPARATOR . $objectname);
                }				
            else
                {
                $success = @unlink($path . DIRECTORY_SEPARATOR . $objectname);
                }

            if(!$success)
                {
                debug("rcRmdir: Unable to delete " . $path . DIRECTORY_SEPARATOR . $objectname);
                return false;
                }
            }
        }
    $success = @rmdir($path);
    debug("rcRmdir: " . $path . " - " . ($success ? "SUCCESS" : "FAILED"));
    return $success;
    }
    
/**
 * Update the daily statistics after a loggable event.
 * 
 * The daily_stat table contains a counter for each 'activity type' (i.e. download) for each object (i.e. resource) per day.
 *
 * @param  string $activity_type
 * @param  integer $object_ref
 * @return void
 */
function daily_stat($activity_type,$object_ref)
    {
    global $disable_daily_stat;
    
    if($disable_daily_stat===true){return;}  //can be used to speed up heavy scripts when stats are less important
    $date=getdate();$year=$date["year"];$month=$date["mon"];$day=$date["mday"];
        
    if ($object_ref=="") {$object_ref=0;}

    
    # Find usergroup
    global $usergroup;
    if ((!isset($usergroup)) || ($usergroup == "")) 
        {
        $usergroup=0;
        }
    
    # External or not?
    global $k;$external=0;
    if (getval("k","")!="") {$external=1;}
    
    # First check to see if there's a row
    $count=sql_value("select count(*) value from daily_stat where year='$year' and month='$month' and day='$day' and usergroup='$usergroup' and activity_type='$activity_type' and object_ref='$object_ref' and external='$external'",0);
    if ($count==0)
        {
        # insert
        sql_query("insert into daily_stat(year,month,day,usergroup,activity_type,object_ref,external,count) values ('$year','$month','$day','$usergroup','$activity_type','$object_ref','$external','1')",false,-1,true,0);
        }
    else
        {
        # update
        sql_query("update daily_stat set count=count+1 where year='$year' and month='$month' and day='$day' and usergroup='$usergroup' and activity_type='$activity_type' and object_ref='$object_ref' and external='$external'",false,-1,true,0);
        }
    }

/**
 * Returns the current page name minus the extension, e.g. "home" for pages/home.php
 *
 * @return string
 */
function pagename()
	{
	$name=safe_file_name(getvalescaped('pagename', ''));
	if (!empty($name))
		return $name;
	$url=str_replace("\\","/", $_SERVER["PHP_SELF"]); // To work with Windows command line scripts
	$urlparts=explode("/",$url);
    $url=$urlparts[count($urlparts)-1];
    return escape_check($url);
    }
    
/**
 *  Returns the site content from the language strings. These will already be overridden with site_text content if present.
 *
 * @param  string $name
 * @return string
 */
function text($name)
	{
	global $pagename,$lang;

	$key=$pagename . "__" . $name;	
    if (array_key_exists($key,$lang))
        {return $lang[$key];}
    else if(array_key_exists("all__" . $name,$lang))
        {return $lang["all__" . $name];}
    else if(array_key_exists($name,$lang))
        {return $lang[$name];}	

	return "";
	}
    
/**
 * Gets a list of site text sections, used for a multi-page help area.
 *
 * @param  mixed $page
 * @return void
 */
function get_section_list($page)
	{
	return sql_array("select distinct name value from site_text where page='$page' and name<>'introtext' order by name");
	}

/**
 * Returns a more friendly user agent string based on the passed user agent. Used in the user area to establish browsers used.
 *
 * @param  mixed $agent The user agent string
 * @return string
 */
function resolve_user_agent($agent)
    {
    if ($agent=="") {return "-";}
    $agent=strtolower($agent);
    $bmatches=array( # Note - order is important - first come first matched
                    "firefox"=>"Firefox",
                    "chrome"=>"Chrome",
                    "opera"=>"Opera",
                    "safari"=>"Safari",
                    "applewebkit"=>"Safari",
                    "msie 3."=>"IE3",
                    "msie 4."=>"IE4",
                    "msie 5.5"=>"IE5.5",
                    "msie 5."=>"IE5",
                    "msie 6."=>"IE6",
                    "msie 7."=>"IE7",
                    "msie 8."=>"IE8",
                    "msie 9."=>"IE9",
                    "msie 10."=>"IE10",
                    "trident/7.0"=>"IE11",
		    "msie"=>"IE",
		    "trident"=>"IE",
                    "netscape"=>"Netscape",
                    "mozilla"=>"Mozilla"
                    #catch all for mozilla references not specified above
                    );
    $osmatches=array(
                    "iphone"=>"iPhone",
					"nt 10.0"=>"Windows 10",
					"nt 6.3"=>"Windows 8.1",
					"nt 6.2"=>"Windows 8",
                    "nt 6.1"=>"Windows 7",
                    "nt 6.0"=>"Vista",
                    "nt 5.2"=>"WS2003",
                    "nt 5.1"=>"XP",
                    "nt 5.0"=>"2000",
                    "nt 4.0"=>"NT4",
                    "windows 98"=>"98",
                    "linux"=>"Linux",
                    "freebsd"=>"FreeBSD",
                    "os x"=>"OS X",
                    "mac_powerpc"=>"Mac",
                    "sunos"=>"Sun",
                    "psp"=>"Sony PSP",
                    "api"=>"Api Client"
                    );
    $b="???";$os="???";
    foreach($bmatches as $key => $value)
        {if (!strpos($agent,$key)===false) {$b=$value;break;}}
    foreach($osmatches as $key => $value)
        {if (!strpos($agent,$key)===false) {$os=$value;break;}}
    return $os . " / " . $b;
    }
    

/**
 * Returns the current user's IP address, using HTTP proxy headers if present.
 *
 * @return string
 */
function get_ip()
	{
	global $ip_forwarded_for;
	
	if ($ip_forwarded_for)
		{
		if (isset($_SERVER) && array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {return $_SERVER["HTTP_X_FORWARDED_FOR"];}
		}
		
	# Returns the IP address for the current user.
	if (array_key_exists("REMOTE_ADDR",$_SERVER)) {return $_SERVER["REMOTE_ADDR"];}


	# Can't find an IP address.
	return "???";
	}


/**
 * For a value such as 10M return the kilobyte equivalent such as 10240. Used  by check.php
 *
 * @param  mixed $value
 * @return void
 */
function ResolveKB($value)
{
$value=trim(strtoupper($value));
if (substr($value,-1,1)=="K")
    {
    return substr($value,0,strlen($value)-1);
    }
if (substr($value,-1,1)=="M")
    {
    return substr($value,0,strlen($value)-1) * 1024;
    }
if (substr($value,-1,1)=="G")
    {
    return substr($value,0,strlen($value)-1) * 1024 * 1024;
    }
return $value;
}


/**
* Trim a filename that is longer than 255 characters while keeping its extension (if present)
* 
* @param string s File name to trim
* 
* @return string
*/
function trim_filename(string $s)
    {
    $str_len = mb_strlen($s);
    if($str_len <= 255)
        {
        return $s;
        }

    $extension = pathinfo($s, PATHINFO_EXTENSION);
    if(is_null($extension) || $extension == "")
        {
        return mb_strcut($s, 0, 255);
        }
    
    $ext_len = mb_strlen(".{$extension}");
    $len = 255 - $ext_len;
    $s = mb_strcut($s, 0, $len);
    $s .= ".{$extension}";

    return $s;
    }

/**
* Flip array keys to use one of the keys of the values it contains. All elements (ie values) of the array must contain 
* the key (ie. they are arrays). Helper function to greatly increase search performance on huge PHP arrays.
* Normal use is: array_flip_by_value_key($huge_array, 'ref');
* 
* 
* IMPORTANT: make sure that for the key you intend to use all elements will have a unique value set.
* 
* Example: Result after calling array_flip_by_value_key($nodes, 'ref');
*     [20382] => Array
*         (
*             [ref] => 20382
*             [name] => Example node
*             [parent] => 20381
*         )
* 
* @param array  $a
* @param string $k A values' key to use as an index/key in the main array, ideally an integer
* 
* @return array
*/
function array_flip_by_value_key(array $a, string $k)
    {
    $return = array();
    foreach($a as $val)
        {
        $return[$val[$k]] = $val;
        }
    return $return;
    }

/**
* Reshape array using the keys of its values. All values must contain the selected keys.
* 
* @param array  $a Array to reshape
* @param string $k The current elements' key to be used as the KEY in the new array. MUST be unique otherwise elements will be lost
* @param string $v The current elements' key to be used as the VALUE in the new array
* 
* @return array
*/
function reshape_array_by_value_keys(array $a, string $k, string $v)
    {
    $return = array();
    foreach($a as $val)
        {
        $return[$val[$k]] = $val[$v];
        }
    return $return;
    }

/**
* Permission check for "j[ref]"
* 
* @param integer $ref Featured collection category ref
* 
* @return boolean
*/
function permission_j(int $ref)
    {
    return checkperm("j{$ref}");
    }

/**
* Permission check for "-j[ref]"
* 
* @param integer $ref Featured collection sub-category ref
* 
* @return boolean
*/
function permission_negative_j(int $ref)
    {
    return checkperm("-j{$ref}");
    }

/**
 * Delete temporary files
 *
 * @param  array $files array of file paths
 * @return void
 */
function cleanup_files($files)
    {
    // Clean up any temporary files
    $GLOBALS["use_error_exception"] = true;
    foreach($files as $deletefile)
        {
        try
            {
            unlink($deletefile);
            }
        catch(Exception $e)
            {
            debug("Unable to delete - file not found: " . $deletefile);
            }
        }
    unset($GLOBALS["use_error_exception"]);
    }

/**
 * Validate if value is integer or string integer
 *
 * @param  mixed $var - variable to check
 * @return boolean true if variable resolves to integer value
 */
function is_int_loose($var)
    {
    if(is_array($var))
        {
        return false;
        }
    return (string)(int)$var === (string)$var;
     }

/**
 * Does the provided $ip match the string $ip_restrict? Used for restricting user access by IP address.
 *
 * @param  string $ip
 * @param  string $ip_restrict
 * @return boolean|integer
 */
function ip_matches($ip, $ip_restrict)
{
global $system_login;
if ($system_login){return true;}	

if (substr($ip_restrict, 0, 1)=='!')
    return @preg_match('/'.substr($ip_restrict, 1).'/su', $ip);

# Allow multiple IP addresses to be entered, comma separated.
$i=explode(",",$ip_restrict);

# Loop through all provided ranges
for ($n=0;$n<count($i);$n++)
    {
    $ip_restrict=trim($i[$n]);

    # Match against the IP restriction.
    $wildcard=strpos($ip_restrict,"*");

    if ($wildcard!==false)
        {
        # Wildcard
        if (substr($ip,0,$wildcard)==substr($ip_restrict,0,$wildcard))
            return true;
        }
    else
        {
        # No wildcard, straight match
        if ($ip==$ip_restrict)
            return true;
        }
    }
return false;
}

/**
 * Ensures filename is unique in $filenames array and adds resulting filename to the array
 *
 * @param  string $filename     Requested filename to be added. Passed by reference
 * @param  array $filenames     Array of filenames already in use. Passed by reference
 * @return string               New filename 
 */
function set_unique_filename(&$filename,&$filenames)
    {
    global $lang;
    if(in_array($filename,$filenames))
        {
        $path_parts = pathinfo($filename);
        if(isset($path_parts['extension']) && isset($path_parts['filename']))
            {
            $filename_ext = $path_parts['extension'];
            $filename_wo  = $path_parts['filename'];
            // Run through function to guarantee unique filename
            $filename = makeFilenameUnique($filenames, $filename_wo, $lang["_dupe"], $filename_ext);
            }
        }
    $filenames[] = $filename; 
    return $filename;
    }