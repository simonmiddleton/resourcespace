<?php 
if (!hook("renderresultthumb")) 
    {
    $thumbs_displayed_fields_height = ($display == "xlthumbs" ? 350 : 202) + (28 * count($thumbs_display_fields));
    if($annotate_enabled)
        {
        $thumbs_displayed_fields_height += 28;
        }
    if($resource_type_icons)
        {
        $thumbs_displayed_fields_height += 16;
        }
    # Increase height of search panel for each extended field
    if(isset($search_result_title_height))
        {
        for ($i=0; $i<count($df); $i++)
            {
            if(in_array($df[$i]['ref'],$thumbs_display_fields) && in_array($df[$i]['ref'],$thumbs_display_extended_fields))
                {
                $thumbs_displayed_fields_height += ($search_result_title_height - 18);
                }
            }
        }
    hook('thumbs_resourceshell_height');
    
    ?>
    <!--Resource Panel-->
    <div class="ResourcePanel <?php echo ($display == 'xlthumbs' ? 'ResourcePanelLarge' : '') ?> ArchiveState<?php echo $result[$n]['archive'];?> <?php hook('thumbsviewpanelstyle'); ?> ResourceType<?php echo $result[$n]['resource_type']; ?>" id="ResourceShell<?php echo htmlspecialchars($ref)?>" <?php echo hook('resourcepanelshell_attributes')?>
    style="height: <?php echo $thumbs_displayed_fields_height; ?>px;"
    >
        <?php  
        if ($resource_type_icons && !hook("replaceresourcetypeicon")) 
            {
            ?>
            <div class="ResourceTypeIcon<?php
            if (array_key_exists($result[$n]['resource_type'], $resource_type_icons_mapping))
                {
                echo ' fa fa-fw fa-' . $resource_type_icons_mapping[$result[$n]['resource_type']];  
                }
            ?>" ></div>
            <?php 
            }
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

            $thm_url = get_resource_path(
                $ref,
                false,
                ($display == "xlthumbs" ? ($retina_mode ? 'scr' : 'pre') : ($retina_mode ? 'pre' : 'thm')),
                false,
                $result[$n]['preview_extension'],
                true,
                1,
                $use_watermark,
                $result[$n]['file_modified']
            );

            if(isset($result[$n]['thm_url']))
                {
                $thm_url = $result[$n]['thm_url'];
                } # Option to override thumbnail image in results, e.g. by plugin using process_Search_results hook above

                $show_flv=false;
                $use_mp3_player=false;
                if((in_array($result[$n]["file_extension"],$ffmpeg_supported_extensions) || $result[$n]["file_extension"]=="flv") && $video_player_thumbs_view){ 
                    $flvfile=get_resource_path($ref,true,"pre",false,$ffmpeg_preview_extension);
                    if (!file_exists($flvfile)){
                        $flvfile=get_resource_path($ref,true,"",false,$ffmpeg_preview_extension);
                    }
                    elseif(!(isset($result[$n]['is_transcoding']) && $result[$n]['is_transcoding']!=0) && file_exists($flvfile) && (strpos(strtolower($flvfile),".".$ffmpeg_preview_extension)!==false)){
                        $show_flv=true;
                    }
                }
                else
                    {
                    // Set $use_mp3_player switch if appropriate
                    $use_mp3_player = ($mp3_player_thumbs_view && !(isset($result[$n]['is_transcoding']) && $result[$n]['is_transcoding']==1) && ((in_array($result[$n]["file_extension"],$ffmpeg_audio_extensions) || $result[$n]["file_extension"]=="mp3") && $mp3_player));
                    if ($use_mp3_player)
                        {
                        $mp3realpath=get_resource_path($ref,true,"",false,"mp3");
                        if (file_exists($mp3realpath))
                            {$mp3path=get_resource_path($ref,false,"",false,"mp3");}
                        }
                    }
                if(isset($flvfile) && hook("replacevideoplayerlogic","",array($flvfile,$result,$n))){
                
                }
                elseif($show_flv){
                    # Include the Flash player if an FLV file exists for this resource.
                    if(!hook("customflvplay")){
                        include "video_player.php";
                    }
                }
                elseif ($use_mp3_player && file_exists($mp3realpath) && !hook("custommp3player"))
                    {
                    $thumb_path=get_resource_path($ref,true,"pre",false,"jpg");
                    if(file_exists($thumb_path))
                        {$thumb_url=get_resource_path($ref,false,"pre",false,"jpg"); }
                    else
                        {$thumb_url=$baseurl_short . "gfx/" . get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],false);}

                    include "mp3_play.php";
                    }
                else { ?>
                <a
                    class="<?php echo ($display == 'xlthumbs' ? 'ImageWrapperLarge' : 'ImageWrapper') ?>"
                    href="<?php echo $url?>"  
                    onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                    title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($result[$n]["field".$view_title_field])))))?>"
                >
                        <?php 
                        if(1 == $result[$n]['has_image'])
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
                                var video_snapshots     = <?php echo json_encode(get_video_snapshots($ref)); ?>;
                                var snapshot_segment_px = Math.ceil(jQuery(this).width() / Object.keys(video_snapshots).length);
                                var snapshot_number     = Math.ceil(x_coord / snapshot_segment_px);
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
                            src="<?php echo $baseurl_short?>gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],false) ?>" style="margin-top:<?php echo ($display == "xlthumbs" ? "90px" : "10px")?>;"

                        />
                        <?php 
                        }
                    hook("aftersearchimg","",array($result[$n]))?>
                </a>
                <?php } ?>
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
                {eval($df[$x]['value_filter']);}
            else if (file_exists($plugin)) 
                {include $plugin;}

            # swap title fields if necessary
            if (isset($metadata_template_resource_type) && isset ($metadata_template_title_field))
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
                    <div class="ResourcePanelInfo ResourceTypeField<?php echo $df[$x]['ref']?>">
                        <div class="extended">
                        <?php 
                        if ($x==0)
                            { // add link if necessary ?>
                            <a 
                                href="<?php echo $url?>"  
                                onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                                title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))))?>"
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
                    <div class="ResourcePanelInfo  ResourceTypeField<?php echo $df[$x]['ref']?>">
                        <?php 
                        if ($x==0)
                            { // add link if necessary ?>
                            <a 
                                href="<?php echo $url?>"  
                                onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
                                title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))))?>"

                            >
                            <?php 
                            } //end link
                        echo highlightkeywords(tidy_trim(TidyList(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))),$search_results_title_trim),$search,$df[$x]['partial_index'],$df[$x]['name'],$df[$x]['indexed']);
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
            if(!hook("thumbscheckboxes"))
            {
            if ($use_checkboxes_for_selection)
                {
                if(!in_array($result[$n]['resource_type'],$collection_block_restypes))  
                    {?>
                    <input 
                        type="checkbox" 
                        id="check<?php echo htmlspecialchars($ref)?>" 
                        class="checkselect" 
                        <?php 
                        if (in_array($ref,$collectionresources))
                            { ?>
                            checked
                            <?php 
                            } ?> 
                        onclick="if (jQuery('#check<?php echo htmlspecialchars($ref)?>').prop('checked')){ AddResourceToCollection(event,<?php echo htmlspecialchars($ref)?>); } else if (jQuery('#check<?php echo htmlspecialchars($ref)?>').prop('checked')==false){ RemoveResourceFromCollection(event,<?php echo htmlspecialchars($ref)?>); }"
                    >
                    <?php 
                    }
                else
                    {
                    ?>
                    <input type="checkbox" style="opacity: 0;">
                    <?php
                    }
                }
            } # end hook thumbscheckboxes
        if(!hook("replacethumbsidinthumbnail"))
            {
            if ($display_resource_id_in_thumbnail && $ref>0) 
                { echo "<span class='ResourcePanelResourceID'>" . htmlspecialchars($ref) . "</span>"; } 
            else 
                { ?>&nbsp;<?php }
            } # end hook("replacethumbsidinthumbnail")

        if (!hook("replaceresourcetools"))
            { 
            include "resource_tools.php";
            } // end hook replaceresourcetools ?>
        </div>  
    </div>

    <?php 
    } # end hook renderresultthumb


