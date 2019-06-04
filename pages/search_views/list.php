<?php 
if (!hook("replacelistitem")) 
    { ?>
    <!--List Item-->
    <tr id="ResourceShell<?php echo htmlspecialchars($ref)?>" <?php hook("listviewrowstyle");?>>
    <?php 
    if(!hook("listcheckboxes"))
        {
        if ($use_checkboxes_for_selection)
            {
            if(!in_array($result[$n]['resource_type'],$collection_block_restypes))
                {?>
                <td width="30px">
                    <input 
                        type="checkbox" 
                        style="position:relative;margin-bottom:-4px;top:-3px;height:21px;" 
                        id="check<?php echo htmlspecialchars($ref)?>" 
                        class="checkselect" 
                        <?php 
                        if (in_array($ref,$collectionresources))
                            { ?>checked<?php } ?> 
                        onclick="if (jQuery('#check<?php echo htmlspecialchars($ref)?>').prop('checked')){ AddResourceToCollection(event,<?php echo htmlspecialchars($ref)?>); } else if (jQuery('#check<?php echo htmlspecialchars($ref)?>').prop('checked')==false) { RemoveResourceFromCollection(event,<?php echo htmlspecialchars($ref)?>); <?php if (isset($collection)){?>document.location.href='?search=<?php echo urlencode($search)?>&order_by=<?php echo urlencode($order_by)?>&archive=<?php echo urlencode($archive)?>&offset=<?php echo urlencode($offset)?>';<?php } ?> }"
                    >
                </td>
                <?php 
                }
            else
                {
                ?>
                <td width="30px">
                </td>
                <?php
                }
            }
        } #end hook listcheckboxes 
        for ($x=0;$x<count($df);$x++)
            {
            if(!in_array($df[$x]['ref'],$list_display_fields))
                {continue;}

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
                        echo highlightkeywords(tidy_trim(TidyList(i18n_get_translated(strip_tags(strip_tags_and_attributes($value)))),$results_title_trim),$search,$df[$x]['partial_index'],$df[$x]['name'],$df[$x]['indexed']);
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
        
        if (isset($rating_field))
            { ?>
            <td <?php hook("listviewcolumnstyle");?> >
                <?php 
                if (isset($result[$n][$rating])&& $result[$n][$rating]>0) 
                    { 
                    for ($y=0;$y<$result[$n][$rating];$y++)
                        { ?> 
                        <div class="IconStar"></div><?php 
                        }
                    } 
                else 
                    { ?>
                    &nbsp;
                    <?php 
                    } 
            ?>
            </td>
            <?php 
            }

        if ($id_column)
            { ?>
            <td <?php hook("listviewcolumnstyle");?> >
                <?php echo $result[$n]["ref"]?>
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
        if(isset($watermark))
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
