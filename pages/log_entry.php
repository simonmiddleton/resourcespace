<?php
include "../include/db.php";

include "../include/authenticate.php";

$logref=getvalescaped("ref","",true);
$k=getvalescaped("k","");
$modal = (getval("modal", "") == "true");

$log_entry = get_resource_log(NULL,-1,array("r.ref" => $logref));
if(!is_array($log_entry) || count($log_entry)==0)
    {
    exit($lang['error_invalid_input']);    
    }
$log_entry = $log_entry[0];

$searchparams = get_search_params();

// Logs can sometimes contain confidential information and the user looking at them must have admin permissions set.
// Some log records can be viewed by all users. Ensure access control by allowing only white listed log codes to bypass 
// permissions checks.
$safe_log_codes = array(LOG_CODE_DOWNLOADED);
$resource_access = get_resource_access($log_entry["resource"]);
$bypass_permission_check = in_array($log_entry["type"], $safe_log_codes) && in_array($resource_access, array(0, 1));
if(!checkperm('v') && !$bypass_permission_check)
    {
    die($lang['log-adminpermissionsrequired']);
    }

?>

<div class="RecordBox">
    <div class="RecordPanel">
        <div class="RecordHeader">
        <p><a href="<?php echo generateurl($baseurl_short . "pages/log.php",$searchparams,array("ref"=>$log_entry["resource"]));?>"  onClick="return ModalLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a></p>   
        <div class="backtoresults"> 
                <a href="#" onClick="ModalClose();" class="closeLink fa fa-times" title="<?php echo $lang["close"] ?>"></a>
            </div>
            <h1><?php echo $lang["log-reference"] . " " . $log_entry["ref"]; ?></h1>
        </div>       
    </div>

    <div class="BasicsBox">
        <div class="Listview">
            <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle" style="margin:0;">
                <tr class="ListviewTitleStyle">
                    <th><?php echo $lang["log_column"] ?></th>
                    <th><?php echo $lang["log_value"] ?></th>
                </tr>
                <?php                
                foreach($log_entry as $column => $value)
                    {
                    $cleanval = true;
                    if(!hook("log_entry_processing","",array($column, $value, $log_entry)))
                        {
                        switch($column)
                            {
                            case "type":
                                $name = $lang["type"];
                                if(isset($lang["log-" . $value]))
                                    {
                                    $value = $lang["log-".$value];
                                    }
                            break;
                            case "date":
                                $name = $lang["date"];
                                $value = nicedate($log_entry["date"],true,true, true);
                            break;
                            case "username":
                                $name = $lang["username"];
                                if($log_entry["access_key"] != "")
                                    {
                                    $value = $lang["externalusersharing"];
                                    }
                            break;
                            case "fullname":
                                $name = $lang["fullname"];
                                if($log_entry["access_key"] != "")
                                    {
                                    // Already shown as username
                                    continue 2;
                                    }
                            break;

                            case "resource_type_field":
                                if (in_array($log_entry["type"], array(LOG_CODE_DOWNLOADED,LOG_CODE_PAID)))
                                    {
                                    // Not relevant
                                    continue 2;
                                    }
                                $name = $lang["admin_resource_type_field"];
                                if(isset($log_entry["title"]))
                                    {
                                    $value = $log_entry["title"];
                                    }                            
                            break;
                            case "usageoption":
                                if($value == -1)
                                    {
                                    // Not relevant
                                    continue 2;
                                    }
                                else
                                    {
                                    $name = $lang["indicateusagemedium"];
                                    if (isset($download_usage_options[$value]))
                                        {
                                        $value = i18n_get_translated($download_usage_options[$value]);
                                        }
                                    }
                            break;

                            case "diff":
                                if (in_array($log_entry["type"], array(LOG_CODE_DOWNLOADED,LOG_CODE_PAID)))
                                    {
                                    // Not relevant, skip
                                    continue 2;
                                    }
                                $name = $lang["difference"];
                                $difftext = $value;
                                if($log_entry["resource_type_field"] != "" && in_array($log_entry["resource_type_field"],$FIXED_LIST_FIELD_TYPES))
                                    {
                                    $transdifflines = array();
                                    $difflines = explode("\n",$value);
                                    foreach($difflines as $diffline)
                                        {
                                        $action = substr($diffline,0,1);
                                        $transdifflines[] = $action . " " . i18n_get_translated(substr($diffline,2));
                                        }
                                    $difftext = implode("\n",$transdifflines);
                                    }
                                $value = nl2br(format_string_more_link(htmlspecialchars(wordwrap($difftext,75,"\n",true))));
                                $cleanval = false;
                            break;

                            case "purchase_price":
                                if ($log_entry["type"]!=LOG_CODE_PAID)
                                    {
                                    // Not relevant
                                    continue 2;
                                    }
                                else
                                    {
                                    $name = $lang["price"];
                                    $value .= " (" . ($log_entry["purchase_size"] == "" ? $lang["collection_download_original"] : $log_entry["purchase_size"]) . ", " . $currency_symbol . number_format($log_entry["purchase_price"],2) . ")";
                                    }
                            break;
                            
                            case "size":
                                $name = $lang["size"];
                                if (!in_array($log_entry["type"], array(LOG_CODE_DOWNLOADED,LOG_CODE_PAID)))
                                    {
                                    // Not relevant
                                    continue 2;
                                    }
                                else
                                    {
                                    if($value=="")
                                        {
                                        $value =$lang["collection_download_original"];
                                        }
                                    }
                            break;                        
                            case "access_key":
                                if ($value == "")
                                    {
                                    continue 2;
                                    }
                                $name = $lang["accesskey"];
                            break;

                            case "shared_by":
                                if ($log_entry["access_key"] == "")
                                    {
                                    continue 2;
                                    }
                                $name = $lang["sharedby"];
                            break;

                            case "ref":
                            case "purchase_size":  // Already converted to 'size'
                            case "title": // Used to store resource_type_field name
                                continue 2;
                            break;
                                
                            default:
                                $name = $column;
                            break;
                            }

                        echo "<tr><td width='50%'>";
                        echo htmlspecialchars($name);
                        echo "</td><td width='50%'>";
                        echo $cleanval ? htmlspecialchars($value) : $value;
                        echo "</td></tr>";
                        }
                    }
                hook("log_entry_extra","",array($log_entry));
                ?>
            </table>
        </div>
    </div>
</div>
