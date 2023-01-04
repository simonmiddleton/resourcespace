<?php

function HookRse_versionLog_entryGet_resource_log_extra_fields()
    {
    # Extend get_resource_log so that the state of the previous value is fetched also.
    return new PreparedStatementQuery(
        ",((r.previous_value IS NOT NULL AND (r.type='e' OR r.type='m' OR r.type='N')) 
            OR (r.previous_file_alt_ref IS NOT NULL AND r.type='u')) revert_enabled"
        );
    }


function HookRse_versionLog_entryLog_entry_processing($column, $value, $logentry)
    {
    global $lang, $baseurl;    
    if($logentry["type"]==LOG_CODE_UPLOADED && $column == "diff")
        {
        // For images, display the uploaded image in the "Difference" section of the log.
        $name = $lang["difference"];
        $resource = $logentry["resource"];
        $image="";
       
        # Attempt to find the image. For the latest upload, this is the current file.
        $parameters=array("i",$resource, "i",$logentry["ref"]);
        $latest=ps_query("SELECT previous_file_alt_ref from resource_log 
                where resource=? and type='u' and ref>? 
                order by ref limit 1", $parameters);

        if (count($latest)==0)
            {
            # There are no subsequent uploads. The current file is the latest one.
            $image_path=get_resource_path($resource,true,"thm");
            if (file_exists($image_path)) 
                {
                $image=get_resource_path($resource,false,"thm");
                }
            else{
                $res = get_resource_data($resource);
                $res_ext = $res['file_extension'];
                $image = $baseurl . 'gfx/' . get_nopreview_icon('', $res_ext, '');
                }
            }
        else
            {
            # We've found a more recent upload; the upload therefore is represented in the alternative file for this.
            $alt_file=$latest[0]["previous_file_alt_ref"];

            if (isset($alt_file))
                {
                $alter_data = get_alternative_file($resource,$alt_file);
                }            
            
            $image_path=get_resource_path($resource, true, 'thm', true, "", -1, 1, false, "", $alt_file);
            if (file_exists($image_path))
                {
                $image=get_resource_path($resource, false, 'thm', true, "", -1, 1, false, "", $alt_file);
                }
            else
                {
                // If an image does not exist, get a nopreview image by looking at the extension of the alternative file    
                $image = $baseurl . '/gfx/' . get_nopreview_icon('', $alter_data['file_extension'], '');
                }
            }
        
        echo "<tr><td>" . $name . "</td><td>";
        echo "<img src='" . $image . "' />";
        if (isset($alt_file))
            {
            //Only add the download link if this is an alternative file
            $altdlparams = array(
                "ref" => $resource,
                "url" =>  generateURL($baseurl . "/pages/download_progress.php", array("ref"=>$resource,
                        "alternative" => $alt_file,
                        "ext" => $alter_data['file_extension']
                        ))
                );
            
            $altdl_link = generateurl($baseurl . "/pages/terms.php",$altdlparams);
            echo "<br/><a href='" . $altdl_link . "'  onClick='return CentralSpaceLoad(this,true);'>" . LINK_CARET . $lang['logdownloadearlierversion'] . "</a>";
            }
        echo "</td></tr>";
        return true;
        }
    elseif($column == "revert_enabled" && $value > 0)
        {
        $show_revert_link = false;
        if ($logentry["type"] == LOG_CODE_UPLOADED) // Show revert link for Replace file.
            {
            $show_revert_link = true;
            }
        else
            {
            $field_info = get_resource_type_field($logentry["field"]);
            if((bool)$field_info["required"] === false || trim($logentry["previous_value"]) != "")
                {
                $show_revert_link = true;
                }
            }
        if($show_revert_link)
            {
            ?>
            <td><?php echo $lang["actions"]; ?></td>
            <td><a href="<?php echo $baseurl; ?>/plugins/rse_version/pages/revert.php?ref=<?php echo $logentry["ref"] ?>" onClick="CentralSpaceLoad(this,true);return false;"><?php echo LINK_CARET . $lang["revert"] ?></a></td>
            <?php
            return true;
            }
        }
    return false;
    }
