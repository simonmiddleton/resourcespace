<?php
$class = array();
if($use_selection_collection && in_array($ref, $selection_collection_resources))
    {
    $class[] = "Selected";
    }

$html_class = (empty($class) ? "" : 'class="' . implode(" ", $class) . '"');

if (!hook("replacelistitem")) 
    {
    $resource_view_title = i18n_get_translated($result[$n]["field" . $view_title_field]);
    ?>
    <!--List Item-->
    <tr id="ResourceShell<?php echo htmlspecialchars($ref)?>" <?php echo $html_class; hook("listviewrowstyle");?>>
    <?php 
    if(!hook("listcheckboxes"))
        {
        if ($use_selection_collection)
            {
            if(!in_array($result[$n]['resource_type'],$collection_block_restypes))
                {?>
                <td width="30px">
                    <input 
                        type="checkbox" 
                        id="check<?php echo htmlspecialchars($ref)?>" 
                        class="checkselect"
                        title="<?php echo escape($lang['action-select'] . " - " . $resource_view_title) ?>"
                        data-resource="<?php echo htmlspecialchars($result[$n]["ref"]); ?>"
                        aria-label="<?php echo escape($lang["action-select"])?>"
                        <?php echo render_csrf_data_attributes("ToggleCollectionResourceSelection_{$result[$n]["ref"]}"); ?>
                        <?php if (in_array($ref, $selection_collection_resources)) { ?> checked <?php } ?>
                    >
                </td>
                <?php 
                }
            else
                {
                ?>
                <td width="30px"></td>
                <?php
                }
            }
        } #end hook listcheckboxes 

        # Display thumbnail of resource
        $watermark = check_use_watermark($ref);
        ?>
        <td width="40px">
            <a href="<?php echo $url?>" onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);">
                <?php
                $thm_url = get_resource_path($ref, false, 'col', false, $result[$n]['preview_extension'], true, 1, $watermark, $result[$n]['file_modified']);

                if(isset($result[$n]['thm_url']))
                    {
                    $thm_url = $result[$n]['thm_url'];
                    } # Option to override thumbnail image in results

                if($result[$n]['has_image'] == 1 && !resource_has_access_denied_by_RT_size($result[$n]['resource_type'], 'col'))
                    {
                    render_resource_image($result[$n],$thm_url,"list");
                    }
                else
                    {
                    ?>
                    <img border=0 
                        src="<?php echo $baseurl_short?>gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],false) ?>" style="margin-top:0; height: 40px;"/>
                    <?php 
                    }
                ?>
            </a>
        </td>
        <?php
        # End of thumbnail display

        for ($x=0;$x<count($df);$x++)
            {
            if(!in_array($df[$x]['ref'],$list_display_fields))
                {
                # Field not present on this resource, so insert a blank element to preserve column integrity 
                ?>
                <td>&nbsp;</td> 
                <?php         
                continue;
                }

            $value=@$result[$n]['field'.$df[$x]['ref']];
            $plugin="../plugins/value_filter_" . $df[$x]['name'] . ".php";

            if ($df[$x]['value_filter']!="")
                {eval(eval_check_signed($df[$x]['value_filter']));}
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
            if ( (isset($metadata_template_title_field)&& $df[$x]['ref']!=$metadata_template_title_field ) || !isset($metadata_template_title_field) ) 
                {
                if (!hook("replacelisttitle")) 
                    { ?>
                    <td 
                        nowrap 
                        <?php 
                        hook("listviewcolumnstyle");?>
                    >
                        <?php 
                        if ($x==0)
                            { // add link to first item only ?>
                            <div class="ListTitle">
                                <a href="<?php echo $url?>" 
                                    onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);"
                                >
<?php 
                            } //end link conditional
                        echo highlightkeywords(htmlspecialchars(tidy_trim(TidyList(i18n_get_translated($value)),$results_title_trim)),$search,$df[$x]['partial_index'],$df[$x]['name'],$df[$x]['indexed']);
                        if ($x==0)
                            { // add link to first item only ?>
                            </a>
<?php 
                            } //end link conditional ?>
                        </div>
                    </td>
<?php } 
                } //end replace list title
            }
        
        hook("searchbeforeratingfield");
        
        if ($id_column)
            { ?>
            <td <?php hook("listviewcolumnstyle");?> >
                <?php 
                $plugin_column_id = hook("listviewcolumnid",'',array($result, $n));
                echo $plugin_column_id !== false ? $plugin_column_id : $result[$n]["ref"];             
                ?>
            </td>
            <?php
            }
        
        if ($resource_type_column)
            { ?>
            <td <?php hook("listviewcolumnstyle");?>>
                <?php 
                if (array_key_exists($result[$n]["resource_type"],$rtypes)) 
                    { 
                    echo $rtypes[$result[$n]["resource_type"]];
                    } 
            ?>
            </td>
            <?php 
            }
        ?>

        <td <?php hook("listviewcolumnstyle");?>>
            <?php echo strtoupper(htmlspecialchars((string) $result[$n]["file_extension"])); ?>
        </td>

        <?php

        if ($list_view_status_column)
            { ?>
            <td <?php hook("listviewcolumnstyle");?> >
                <?php 
                echo $lang["status" . $result[$n]["archive"]];
                ?>
            </td>
            <?php 
            }
        
        if ($date_column)
            { ?>
            <td <?php hook("listviewcolumnstyle");?> >
                <?php 
                echo nicedate($result[$n]["creation_date"],false,true);
                ?>
            </td>
            <?php 
            }
        
        hook("addlistviewcolumn");
        ?>
        <td <?php hook("listviewcolumnstyle");?> >
        <div class="ListTools">
        <?php 
        # Work out image to use, otherwise preview will always use un-watermarked image.
        if($watermark !== '')
            {
            $use_watermark=check_use_watermark();
            }
        else
            {
            $use_watermark=false;
            }

        include "resource_tools.php"; ?>
        </div>
        </td>
    </tr>
    <!--end hook replacelistitem--> 
    <?php
    }
