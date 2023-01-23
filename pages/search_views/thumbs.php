<?php 
if (!hook("renderresultthumb")) 
    {
    # Establish various metrics for use in thumbnail rendering
    $resolved_title_trim=0; 
    $field_height = 24;
    $resource_id_height = 21;
    $workflow_state_height = 31;

    hook("thumbstextheight");

    if ($display == "xlthumbs")
        {
        $resolved_title_trim = $xl_search_results_title_trim;
        $resource_panel_height = 351;
        }
    else
        {
        $resolved_title_trim = $search_results_title_trim;
        $resource_panel_height = 231;
        }

    $thumbs_displayed_fields_height = $resource_panel_height + ($field_height * (count($thumbs_display_fields))) + 2;
    
    # Add space for number of annotations
    if($annotate_enabled || (isset($annotate_enabled_adjust_size_all) && $annotate_enabled_adjust_size_all == true))
        {
        $thumbs_displayed_fields_height += $field_height;
        }

    # Increase height of search panel for each extended field
    if(isset($search_result_title_height))
        {
        for ($i=0; $i<count($df); $i++)
            {
            if(in_array($df[$i]['ref'],$thumbs_display_fields) && in_array($df[$i]['ref'],$thumbs_display_extended_fields))
                {
                if ($df[$i]['ref'] == $thumbs_display_fields[0])
                    {
                    # If extending the taller first field take off more height
                    $thumbs_displayed_fields_height -= 2;
                    }
                $thumbs_displayed_fields_height += ($search_result_title_height - 19);
                }
            }
        }

    hook('thumbs_resourceshell_height');
    
    if($display_resource_id_in_thumbnail)
        { 
        $thumbs_displayed_fields_height += $resource_id_height;
        $br = '<br />';
        };
    if($thumbs_display_archive_state)
        { 
        $thumbs_displayed_fields_height += $workflow_state_height;
        }; 

    $class = array();
    if($use_selection_collection && in_array($ref, $selection_collection_resources))
        {
        $class[] = "Selected";
        }

    $thumbs_displayed_fields_height = $resource_panel_height_max = max($thumbs_displayed_fields_height,$resource_panel_height_max);
    ?>

    <!--Resource Panel -->    
    <div class="ResourcePanel <?php echo implode(" ", $class); ?> <?php echo ($display == 'xlthumbs' ? 'ResourcePanelLarge' : '') ?> ArchiveState<?php echo $result[$n]['archive'];?> <?php hook('thumbsviewpanelstyle'); ?> ResourceType<?php echo $result[$n]['resource_type']; ?>" id="ResourceShell<?php echo htmlspecialchars($ref)?>" <?php echo hook('resourcepanelshell_attributes')?>
    style="height: <?php echo (int)$thumbs_displayed_fields_height; ?>px;"
    <?php hook('renderadditionalthumbattributes', '', [$result[$n]]);?>
    >
        <div class="ResourcePanelTop">
            <?php
            if (isset($result[$n]['file_extension']) && $result[$n]['file_extension'] != "")
                { ?>
                <div class="thumbs-file-extension"><?php echo strtoupper(htmlspecialchars($result[$n]['file_extension'])) ?></div>
                <?php
                }

            if (!hook("replaceresourcetypeicon"))
                {
                foreach ($types as $type)
                    {
                    if (($type["ref"] == $result[$n]['resource_type']) && isset($type["icon"]) && $type["icon"] != "")
                        {
                        echo '<div class="ResourceTypeIcon fa-fw ' . htmlspecialchars($type["icon"]) . '" title="' . htmlspecialchars($type["name"]) . '"></div>';  
                        }
                    }
                }
            ?>
        </div>
        <?php
        hook ("resourcethumbtop");
        if (!hook("renderimagethumb")) 
            {
            # Work out image to use.
            if(isset($watermark))
                {
                $use_watermark=check_use_watermark();
                }
            else
                {
                $use_watermark=false;   
                }

            $image_size = $display == "xlthumbs" ? ($retina_mode ? "scr" : "pre") : ($retina_mode ? "pre" : "thm");

            $thm_url = get_resource_path(
                $ref,
                true,
                $image_size,
                false,
                $result[$n]['preview_extension'],
                true,
                1,
                $use_watermark,
                $result[$n]['file_modified']
            );

            // If no screen size found try preview
            if ($image_size == "scr" && !file_exists($thm_url))
                {
                $image_size = "pre";
                $thm_url=get_resource_path($ref,true,$image_size ,false,$result[$n]['preview_extension'],true,1,$use_watermark,$result[$n]['file_modified']);
                }

            // If no preview size found try thumbnail
            if ($image_size == "pre" && !file_exists($thm_url))     
                {
                $image_size = "thm";
                $thm_url=get_resource_path($ref,true,$image_size ,false,$result[$n]['preview_extension'],true,1,$use_watermark,$result[$n]['file_modified']);
                }

            $thm_url=get_resource_path($ref,false,$image_size ,false,$result[$n]['preview_extension'],true,1,$use_watermark,$result[$n]['file_modified']);
                
            if(isset($result[$n]['thm_url']))
                {
                $thm_url = $result[$n]['thm_url'];
                } # Option to override thumbnail image in results, e.g. by plugin using process_Search_results hook above

                ?>
                <a
                    class="<?php echo ($display == 'xlthumbs' ? 'ImageWrapperLarge' : 'ImageWrapper') ?>"
                    href="<?php echo $url?>"  
                    onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                    title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated($result[$n]["field".$view_title_field])))?>"
                >
                        <?php 
                        if($result[$n]['has_image'] == 1 && !resource_has_access_denied_by_RT_size($result[$n]['resource_type'], $image_size))
                        {
                        render_resource_image($result[$n],$thm_url,$display);
                        // For videos ($ffmpeg_supported_extensions), if we have snapshots set, add code to fetch them from the server
                        // when user hovers over the preview thumbnail
                        if(1 < $ffmpeg_snapshot_frames && in_array($result[$n]['file_extension'], $ffmpeg_supported_extensions) && 0 < get_video_snapshots($ref, false, true))
                            {
                            ?>
                            <script>
                            jQuery('#CentralSpace #ResourceShell<?php echo $ref; ?> a img').mousemove(function(event)
                                {
                                var x_coord             = event.pageX - jQuery(this).offset().left;
                                var video_snapshots     = <?php echo json_encode(get_video_snapshots($ref, false, false, true)); ?>;
                                var snapshot_segment_px = Math.ceil(jQuery(this).width() / Object.keys(video_snapshots).length);
                                var snapshot_number     = x_coord == 0 ? 1 : Math.ceil(x_coord / snapshot_segment_px);
                                if(typeof(ss_img_<?php echo $ref; ?>) === "undefined")
                                    {
                                    ss_img_<?php echo $ref; ?> = new Array();
                                    }
                                ss_img_<?php echo $ref; ?>[snapshot_number] = new Image();
                                ss_img_<?php echo $ref; ?>[snapshot_number].src = video_snapshots[snapshot_number];
                                jQuery(this).attr('src', ss_img_<?php echo $ref; ?>[snapshot_number].src);
                                }
                            ).mouseout(function(event)
                                {
                                jQuery(this).attr('src', "<?php echo $thm_url; ?>");
                                }
                            );
                            </script>
                            <?php
                            }
                        } 
                    else 
                        { ?>
                        <img 
                            border=0 
                            src="<?php echo $baseurl_short?>gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],false) ?>" style="margin-top:<?php echo ($display == "xlthumbs" ? "90px" : "35px")?>;"

                        />
                        <?php 
                        }
                   hook("aftersearchimg","",array($result[$n], $thm_url, $display))
                   ?>
                </a>
            <?php 
            } ?> 
        <!-- END HOOK Renderimagethumb-->

        <?php 

        if (!hook("replaceicons")) 
            {
            hook("icons");
            } //end hook replaceicons
        if (!hook("rendertitlethumb")) {} ?> <!-- END HOOK Rendertitlethumb -->
        <?php

        if($thumbs_display_archive_state)
            {
            $workflow_html = "<div class='ResourcePanelInfo WorkflowState'>";
            // Add icon
            $icon = $workflowicons[$result[$n]['archive']] ?? (WORKFLOW_DEFAULT_ICONS[$result[$n]['archive']] ?? WORKFLOW_DEFAULT_ICON);
            $workflow_html .= "<i class='" . escape_quoted_data($icon) . "'></i>&nbsp;";
            // Add text for workflow state
            $workflow_html .= isset($lang["status" . $result[$n]['archive']]) ? (htmlspecialchars($lang["status" . $result[$n]['archive']])) : ($lang["status"] . "&nbsp;" . $result[$n]['archive']);
            $workflow_html .= "</div>";
            echo $workflow_html;
            }

        if($annotate_enabled)
            {
            $annotations_count = getResourceAnnotationsCount($ref);
            $message           = '';

            if(1 < $annotations_count)
                {
                $message = $annotations_count . ' ' . mb_strtolower($lang['annotate_annotations_label']);
                }
            else if(1 == $annotations_count)
                {
                $message = $annotations_count . ' ' . mb_strtolower($lang['annotate_annotation_label']);
                }
            ?>
            <div class="ResourcePanelInfo AnnotationInfo">
            <?php
            if(0 < $annotations_count)
                {
                ?>
                <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                <span><?php echo $message; ?></span>
                <?php
                }
                ?>
            &nbsp;
            </div>
            <?php
            }

        $df_alt=hook("displayfieldsalt");
        $df_normal=$df;
        if ($df_alt){$df=$df_alt;}
        # thumbs_display_fields
        for ($x=0;$x<count($df);$x++)
            {
            if(!in_array($df[$x]['ref'],$thumbs_display_fields))
                {continue;}
            
            #value filter plugin -tbd   
            $value=@$result[$n]['field'.$df[$x]['ref']];
            $plugin="../plugins/value_filter_" . $df[$x]['name'] . ".php";
            if ($df[$x]['value_filter']!="")
                {eval(eval_check_signed($df[$x]['value_filter']));}
            else if (file_exists($plugin)) 
                {include $plugin;}

            # swap title fields if necessary
            if (isset($metadata_template_resource_type) && isset($metadata_template_title_field) && is_int_loose($metadata_template_title_field))
                {
                if (($df[$x]['ref']==$view_title_field) && ($result[$n]['resource_type']==$metadata_template_resource_type))
                    {
                    $value=$result[$n]['field'.$metadata_template_title_field];
                    }
                }
            // extended css behavior 
            if (in_array($df[$x]['ref'],$thumbs_display_extended_fields) &&
            ((isset($metadata_template_title_field) && $df[$x]['ref']!=$metadata_template_title_field) || !isset($metadata_template_title_field)))
                {
                if (!hook("replaceresourcepanelinfo"))
                    { ?>
                    <div class="ResourcePanelInfo ResourceTypeField<?php echo $df[$x]['ref']; echo $x == 0 ? ' ResourcePanelTitle' : ''?>"
                    title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated($value)))?>"
                    >
                        <div class="extended">
                        <?php 
                        if ($x==0)
                            { // add link if necessary ?>
                            <a 
                                href="<?php echo $url?>"  
                                onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                            >
                            <?php 
                            } //end link
                        echo format_display_field($value);
                        if ($show_extension_in_search) 
                            { 
                            echo " " . str_replace_formatted_placeholder("%extension", $result[$n]["file_extension"], $lang["fileextension-inside-brackets"]);
                            }
                        if ($x==0)
                            { // add link if necessary ?>
                            </a>
                            <?php 
                            } //end link?> 
                        &nbsp;
                        </div>
                    </div>
                    <?php 
                    } /* end hook replaceresourcepanelinfo */ ?>
                <?php 
                // normal behavior
                } 
            else if  ((isset($metadata_template_title_field)&&$df[$x]['ref']!=$metadata_template_title_field) || !isset($metadata_template_title_field) ) 
                {
                if (!hook("replaceresourcepanelinfonormal"))
                    { ?>
                    <div class="ResourcePanelInfo  ResourceTypeField<?php echo $df[$x]['ref']; echo $x == 0 ? ' ResourcePanelTitle' : ''?>"
                    title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated($value))); ?>"
                    >
                        <?php 
                        if ($x==0)
                            { // add link if necessary ?>
                            <a 
                                href="<?php echo $url?>"  
                                onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                            >
                            <?php 
                            } //end link
                        echo highlightkeywords(htmlspecialchars(tidy_trim(TidyList(i18n_get_translated($value)),$resolved_title_trim)),$search,$df[$x]['partial_index'],$df[$x]['name'],$df[$x]['indexed']);
                        if ($x==0)
                            { // add link if necessary ?>
                            </a>
                            <?php 
                            } //end link ?>
                        &nbsp;
                    </div>
                    <div class="clearer"></div>
                    <?php 
                    }
                } /* end hook replaceresourcepanelinfonormal */
                hook("processthumbsfields");
            }
        hook("afterthumbfields");
        $df=$df_normal;
        ?>
        <!-- Checkboxes -->
        <div class="ResourcePanelIcons">
        <?php
        hook("thumblistextras");  // add icons for resourceconnect

        if($use_selection_collection)
            {
            if(!hook("thumbscheckboxes"))
                {
                if(!in_array($result[$n]['resource_type'],$collection_block_restypes))  
                    {?>
                    <input 
                        type="checkbox" 
                        id="check<?php echo htmlspecialchars($ref)?>" 
                        class="checkselect" 
                        data-resource="<?php echo htmlspecialchars($result[$n]["ref"]); ?>"
                        aria-label="<?php echo escape_quoted_data($lang["action-select"])?>"
                        <?php echo render_csrf_data_attributes("ToggleCollectionResourceSelection_{$result[$n]["ref"]}"); ?>
                        <?php 
                        if (in_array($ref, $selection_collection_resources))
                            { ?>
                            checked
                            <?php 
                            } ?>
                    >
                    <?php 
                    }
                else
                    {
                    ?>
                    <input type="checkbox" class="checkselect" style="opacity: 0;">
                    <?php
                    }
                } # end hook thumbscheckboxes
            }
        if(!hook("replacethumbsidinthumbnail"))
            {
            if ($display_resource_id_in_thumbnail && $ref>0) 
                { echo "<span class='ResourcePanelResourceID'>" . htmlspecialchars($ref) . "</span>$br"; } 
            else 
                { ?><?php }
            } # end hook("replacethumbsidinthumbnail")

        if (!hook("replaceresourcetools"))
            { 
            include "resource_tools.php";
            } // end hook replaceresourcetools ?>

    </div>
</div>
    <?php 
    } # end hook renderresultthumb


