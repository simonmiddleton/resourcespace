<?php
// This file is included fom collection_edit.php and collection_set_category.php

// find current number of themes used
$themecount=1;
foreach($collection as $key=>$value)
    {
    if (substr($key,0,5)=="theme")
        {
        if ($value=="")
            {break 1;} 
        else
            {
            if (substr($key,5)=="")
                {
                $themecount=1;				
                $orig_themecount=$themecount;
                }
            else
                {
                $themecount=substr($key,5);
                $orig_themecount=$themecount;
                }
            }
        }
    }
// find number of theme columns
foreach($collection as $key=>$value)
    {
    if (substr($key,0,5)=="theme")
        {$themecolumns=substr($key,5);}
    }		
if(!hook("overridethemesel"))
    {
    if (checkperm("h") && $enable_themes) 
        { # Only users with the 'h' permission can publish public collections as themes.
        ?>
        <input type=hidden name="addlevel" id="addlevel" value=""/>
        <?php
        if (getval("addlevel","")=="yes")
            {$themecount++;}
        $lastselected=false;
        # Theme category levels
        for ($i=1;$i<=$themecount;$i++)
            {
            if ($theme_category_levels>=$i)
                {
                if ($i==1)
                    {$themeindex="";}
                else
                    {$themeindex=$i;}	

                $themearray=array();
                for($y=0;$y<$i-1;$y++)
                    {
                    if ($y==0)
                        {
                        $themearray[]=$collection["theme"];
                        }
                    else 
                        {
                        $themearray[]=$collection["theme".($y+1)];
                        }
                    }	
                $themes=get_theme_headers($themearray);
                ?>
                <div class="Question">
                    <label for="theme<?php echo $themeindex?>"><?php echo $lang["themecategory"] . " ".$themeindex ?></label>
                    <?php 
                    if (count($themes)>0)
                        {?>
                        <select class="stdwidth" name="theme<?php echo $themeindex?>" id="theme<?php echo $themeindex?>" <?php if ($theme_category_levels>=$themeindex) { ?>onchange="if (document.getElementById('theme<?php echo $themeindex?>').value!=='') {document.getElementById('addlevel').value='yes'; return CentralSpacePost(jQuery('#collectionform')[0])} else {document.getElementById('redirect').value='';return CentralSpacePost(jQuery('#collectionform')[0])}"<?php } ?>>
                            <option value=""><?php echo $lang["select"]?></option>
                            <?php 
                            $lastselected=false;
                            for ($n=0;$n<count($themes);$n++) 
                                { ?>
                                <option <?php if ($collection["theme".$themeindex]==$themes[$n]) { ?>selected<?php } ?>><?php echo htmlspecialchars($themes[$n]) ?></option>
                                <?php 
                                if ($collection["theme".$themeindex]==$themes[$n] && $i==$orig_themecount)
                                    {$lastselected=true;} ?>
                                <?php 
                                } ?>
                        </select>
                        <?php 
                        if (getval("addlevel","")!="yes" && $lastselected)
                            {$themecount++;}?>
                        <div class="clearerleft"> </div>
                        <label><?php echo $lang["newcategoryname"]?></label>
                        <?php 
                        } //end conditional selector ?>
                    <input type=text class="medwidth" name="newtheme<?php echo $themeindex?>" id="newtheme<?php echo $themeindex?>" value="" maxlength="100">
                    <?php 
                    if ($themecount!=1)
                        {?>
                        <input type=button class="medcomplementwidth" value="<?php echo $lang['save'];?>" style="display:inline;" onclick="document.getElementById('addlevel').value='yes';return CentralSpacePost(jQuery('#collectionform')[0])"/>	
                        <?php 
                        } ?>
                    <?php 
                    if ($themecount==1)
                        {?>
                        <input type=button class="medcomplementwidth" value="<?php echo $lang['add'];?>" style="display:inline;" onclick="if (document.getElementById('newtheme<?php echo $themeindex?>').value==''){alert('<?php echo $lang["collectionsnothemeselected"] ?>');return false;}document.getElementById('addlevel').value='yes';return CentralSpacePost(jQuery('#collectionform')[0])"/>
                        <?php 
                        }?>
                        <div class="clearerleft"> </div>
                </div>
                <?php
                }
            }
        }
    else 
        {
        // in case a user can edit collections but doesn't have themes enabled, preserve them
        for ($i=1;$i<=$themecount;$i++)
            {
            if ($theme_category_levels>=$i)	
                {
                if ($i==1)
                    {$themeindex="";}
                else
                    {$themeindex=$i;} ?>
                <input type=hidden name="theme<?php echo $themeindex?>" value="<?php echo htmlspecialchars($collection["theme".$themeindex]) ?>">
                <?php
                }
            }	
        }
    }
