			<?php 
			# Work out image to use.
			if(isset($watermark))
				{
				$use_watermark=check_use_watermark();
				}
			else
				{
				$use_watermark=false;	
				}
			$thm_url=get_resource_path($ref,false,("pre"),false,$result[$n]["preview_extension"],-1,1,$use_watermark,$result[$n]["file_modified"]);
			if (isset($result[$n]["thm_url"])) {$thm_url=$result[$n]["thm_url"];} # Option to override thumbnail image in results, e.g. by plugin using process_Search_results hook above
			?><a 
					href="<?php echo $url?>"  
					onClick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" 
					title="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated($result[$n]["field".$view_title_field])))?>"
				><?php 
                        if(1 == $result[$n]['has_image'])
                        {
                        ?><img 
                        <?php
                        if('' != $result[$n]['thumb_width'] && 0 != $result[$n]['thumb_width'] && '' != $result[$n]['thumb_height'])
                            {
                            ?>
                            width="<?php echo $result[$n]["thumb_width"]?>" 
                            height="<?php echo $result[$n]["thumb_height"]?>" 
                            <?php
                            }
                            ?>
                        src="<?php echo $thm_url ?>" 
                        class="ImageBorder ImageStrip" 
                        alt="<?php echo str_replace(array("\"","'"),"",htmlspecialchars(i18n_get_translated($result[$n]["field".$view_title_field]))); ?>"
                        /><?php } ?></a>
