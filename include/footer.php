<?php 
hook("before_footer_always");

if(getval("loginmodal",""))
	{
	$login_url=$baseurl."/login.php?url=".urlencode(getvalescaped("url",""))."&api=".urlencode(getval("api",""))."&error=".urlencode(getval("error",""))."&auto=".urlencode(getval("auto",""))."&nocookies=".urlencode(getval("nocookies",""))."&logout=".urlencode(getval("logout",true));
	?><script>
		jQuery(document).ready(function(){
			ModalLoad('<?php echo $login_url?>',true);
		});
	</script>
	<?php
	}
	
# Do not display header / footer when dynamically loading CentralSpace contents.
if (getval("ajax","") == "" && !hook("replace_footer")) 
	{ 
	hook("beforefooter");
    ?>
    <div class="clearer"></div>

    <!-- Use aria-live assertive for high priority changes in the content: -->
    <span role="status" aria-live="assertive" class="ui-helper-hidden-accessible"></span>

    <!-- Global Trash Bin -->
    <?php if (!hook("replacetrashbin")) 
    	{
    	render_trash("trash", "");
    	} ?>

    <div class="clearerleft"></div>
    </div><!--End div-CentralSpace-->

    <div class="clearer"></div>

    <?php hook("footertop");

    if ($pagename == "login") 
        {
        ?>
        <!--Global Footer-->
        <div id="Footer">
        <?php
        if ($responsive_ui)
            {
            ?>
            <div class="ResponsiveViewFullSite">
                <a href="#" onClick="SetCookie('ui_view_full_site', true, 1, true); location.reload();"><?php echo $lang['responsive_view_full_site']; ?></a>
            </div>
            <?php
            }

        if (!hook("replace_footernavrightbottom"))
        	{
            ?>
            <div id="FooterNavRightBottom"><?php echo text("footer")?></div>
            <?php
        	}
        ?>
        <div class="clearer"></div>
        </div>
        <?php 
        }

    echo $extrafooterhtml;

    } // end ajax

/* always include the below as they are perpage */

if (($pagename!="login") && ($pagename!="user_password") && ($pagename!="preview_all") && ($pagename!="user_request"))
    {
    echo "</div><!-- End CentralSpaceContainer -->";
    }
    
echo "</div><!-- End UICenter -->";

hook("footerbottom");
draw_performance_footer();

//titlebar modifications

if ($show_resource_title_in_titlebar){
$general_title_pages=array("admin_content","team_archive","team_resource","team_user","team_request","team_research","team_plugins","team_mail","team_export","team_stats","team_report","team_user_log","research_request","team_user_edit","admin_content_edit","team_request_edit","team_research_edit","requests","edit","themes","collection_public","collection_manage","team_home","help","home","tag","upload_java_popup","upload_java","contact","geo_search","search_advanced","about","contribute","user_preferences","view_shares","check","index");
$search_title_pages=array("contactsheet_settings","search","preview_all","collection_edit","edit","collection_download","collection_share","collection_request");
$resource_title_pages=array("view","delete","log","alternative_file","alternative_files","resource_email","edit","preview");
$additional_title_pages=array(hook("additional_title_pages_array"));

    // clear resource or search title for pages that don't apply:
    if (!in_array($pagename,array_merge($general_title_pages,$search_title_pages,$resource_title_pages)) && (empty($additional_title_pages) || !in_array($pagename,$additional_title_pages))){
		echo "<script language='javascript'>\n";
		echo "document.title = \"$applicationname\";\n";
		echo "</script>";
    }
    // place resource titles
    else if (in_array($pagename,$resource_title_pages) && !isset($_GET['collection']) && !isset($_GET['java'])) /* for edit page */{
        $title =  str_replace('"',"''",i18n_get_translated(get_data_by_field($ref,$view_title_field)));
        echo "<script type=\"text/javascript\" language='javascript'>\n";
        
        if ($pagename=="edit"){$title=$lang['action-edit']." - ".$title;}
        
        echo "document.title = \"$applicationname - $title\";\n";

        if($pagename=='edit' && $distinguish_uploads_from_edits) {

			$js = sprintf("
				jQuery(document).ready(function() {
					var h1 = jQuery(\"h1\").text();

					if(h1 == \"%s\") {
						document.title = \"%s - \" + h1;\n
					}
				});
			",
				$lang["addresourcebatchbrowser"],
				$applicationname);

			echo $js;

        }
        
        echo "</script>";
    }

    // place collection titles
    else if (in_array($pagename,$search_title_pages)){
        $collection=getval("ref","");
        if (isset($search_title)){
            $title=str_replace('"',"''",$lang["searchresults"]." - ".html_entity_decode(strip_tags($search_title)));
        }
        else if (($pagename=="collection_download") || $pagename=="edit" && getval("collection","")!=""){
            $collectiondata=get_collection($collection);
            $title = strip_tags(str_replace('"',"''",i18n_get_collection_name($collectiondata)));
            }  
        else {
            $collectiondata=get_collection($collection);
            $title = strip_tags(str_replace('"',"''",i18n_get_collection_name($collectiondata)));
            }
        // add a hyphen if title exists  
        if (strlen($title)!=0){$title="- $title";}    
        if ($pagename=="edit"){$title=" - ".$lang['action-editall']." ".$title;}
        if ($pagename=="collection_share"){$title=" - ".$lang['share']." ".$title;}
        if ($pagename=="collection_edit"){$title=" - ".$lang['action-edit']." ".$title;}
        if ($pagename=="preview_all"){$title=" - ".$lang['preview_all']." ".$title;}
        if ($pagename=="collection_download"){$title=" - ".$lang['download']." ".$title;}
        echo "<script language='javascript'>\n";
        echo "document.title = \"$applicationname $title\";\n";
        echo "</script>";
    }
    
      // place page titles
    else if (in_array($pagename,$general_title_pages)){ 
		
		if (isset($lang[$pagename])){
			$pagetitle=$lang[$pagename];
		} 
		else if (isset($lang['action-'.$pagename])){
			$pagetitle=$lang["action-".$pagename];
			if (getval("java","")!=""){$pagetitle=$lang['upload']." ".$pagetitle;}
		}
		else if (isset($lang[str_replace("_","",$pagename)])){
			$pagetitle=$lang[str_replace("_","",$pagename)];
		}
		else if ($pagename=="admin_content"){
			$pagetitle=$lang['managecontent'];
		}
		else if ($pagename=="collection_public"){
			$pagetitle=$lang["publiccollections"];
		}
		else if ($pagename=="collection_manage"){
			$pagetitle=$lang["mycollections"];
		}
		else if ($pagename=="team_home"){
			$pagetitle=$lang["teamcentre"];
		}
		else if ($pagename=="help"){
			$pagetitle=$lang["helpandadvice"];
		}
		else if ($pagename=="tag"){
			$pagetitle=$lang["tagging"];
		}
		else if (strpos($pagename,"upload")!==false){
			$pagetitle=$lang["upload"];
		}
		else if ($pagename=="contact"){
			$pagetitle=$lang["contactus"];
		}
		else if ($pagename=="geo_search"){
			$pagetitle=$lang["geographicsearch"];
		}
		else if ($pagename=="search_advanced"){
			$pagetitle=$lang["advancedsearch"];
			if (getval("archive","")==2){$pagetitle.=" - ".$lang['archiveonlysearch'];}
		}	
		else if ($pagename=="about"){
			$pagetitle=$lang["aboutus"];
		}	
		else if ($pagename=="contribute"){
			$pagetitle=$lang["mycontributions"];
		}	
		else if ($pagename=="user_preferences"){
			$pagetitle=$lang["user-preferences"];
		}	
		else if ($pagename=="requests"){
			$pagetitle=$lang["myrequests"];
		}	
		else if ($pagename=="team_resource"){
			$pagetitle=$lang["manageresources"];
		}	
		else if ($pagename=="team_archive"){
			$pagetitle=$lang["managearchiveresources"];
		}	
		else if($pagename=="view_shares"){
			$pagetitle=$lang["shared_collections"];
		}	
		else if($pagename=="team_user"){
			$pagetitle=$lang["manageusers"];
		}
		else if($pagename=="team_request"){
			$pagetitle=$lang["managerequestsorders"];
		}
		else if($pagename=="team_research"){
			$pagetitle=$lang["manageresearchrequests"];
		}
		else if($pagename=="team_plugins"){
			$pagetitle=$lang["pluginmanager"];
		}
		else if($pagename=="team_mail"){
			$pagetitle=$lang["sendbulkmail"];
		}
		else if($pagename=="team_export"){
			$pagetitle=$lang["exportdata"];
		}
		else if($pagename=="team_export"){
			$pagetitle=$lang["exportdata"];
		}
		else if($pagename=="team_stats"){
			$pagetitle=$lang["viewstatistics"];
		}
		else if($pagename=="team_report"){
			$pagetitle=$lang["viewreports"];
		}
		else if($pagename=="check"){
			$pagetitle=$lang["installationcheck"];
		}
		else if($pagename=="index"){
			$pagetitle=$lang["systemsetup"];
		}
		else if($pagename=="team_user_log"){
			global $userdata;
			$pagetitle=$lang["userlog"] . ": " . $userdata["fullname"];
		}
		else if($pagename=="team_user_edit"){
			global $userdata,$display_useredit_ref;
			$pagetitle=$lang["edituser"];
			if($display_useredit_ref){
				$pagetitle.=" ".$ref;
			}
		}
		else if($pagename=="admin_content_edit"){
			$pagetitle=$lang["editcontent"];
		}
		else if($pagename=="team_request_edit"){
			$pagetitle=$lang["editrequestorder"];
		}
		else if($pagename=="team_research_edit"){
			$pagetitle=$lang["editresearchrequest"];
		}
		else {
			$pagetitle="";
		}
		if (strlen($pagetitle)!=0){$pagetitle="- $pagetitle";} 
        echo "<script language='javascript'>\n";
        echo "document.title = \"$applicationname $pagetitle\";\n";
        echo "</script>";
    }
    hook("additional_title_pages");
}   

if(isset($onload_message["text"]))
	{?>
	<script>
	jQuery(document).ready(function()
		{
		styledalert(<?php echo (isset($onload_message["title"]) ? json_encode($onload_message["title"]) : "''") . "," . json_encode($onload_message["text"]) ;?>);
		});
	</script>
	<?php
	}
if (getval("ajax","") == "")
	{
	// don't show closing tags if we're in ajax mode
	echo "<!--CollectionDiv-->";
	$omit_collectiondiv_load_pages=array("login","user_request","user_password","index","preview_all");
	
	$more_omit_collectiondiv_load_pages=hook("more_omit_collectiondiv_load_pages");
	if(is_array($more_omit_collectiondiv_load_pages))
		{
		$omit_collectiondiv_load_pages=array_merge($omit_collectiondiv_load_pages,$more_omit_collectiondiv_load_pages);
		}
	?></div>
	
	<?php # Work out the current collection (if any) from the search string if external access
	
	if (isset($k) && $k!="" && isset($search) && !isset($usercollection))
		{
		if (substr($search,0,11)=="!collection")
			{
			// Search may include extra terms after a space so need to make sure we extract only the ID
			$searchparts = explode(" ",substr($search,11));
			$usercollection = trim($searchparts[0]);
			}
		}
	?>
	<script>
	<?php
	if (!isset($usercollection))
		{?>
		usercollection='';
		<?php
		}
	else
		{?>
		usercollection='<?php echo htmlspecialchars($usercollection) ?>';
		var collections_popout = <?php echo $collection_bar_popout? "true": "false"; ?>;
		<?php
		} ?>
	</script><?php 
	if (!hook("replacecdivrender"))
		{
        $col_on = $collections_footer && !in_array($pagename,$omit_collectiondiv_load_pages) && !checkperm("b") && isset($usercollection);
		if ($col_on) 
			{
			// Footer requires restypes as a string because it is urlencoding them
			if(isset($restypes) && is_array($restypes))
				{
				$restypes = implode(',', $restypes);
				}
				?>
			<div id="CollectionDiv" class="CollectBack AjaxCollect ui-layout-south"></div>
			
			<script type="text/javascript">
			var collection_frame_height=<?php echo $collection_frame_height?>;
			var thumbs="<?php echo htmlspecialchars($thumbs); ?>";									
			function ShowThumbs()
				{
				myLayout.sizePane("south", collection_frame_height);
				jQuery('.ui-layout-south').animate({scrollTop:0}, 'fast');
				jQuery('#CollectionMinDiv').hide();
				jQuery('#CollectionMaxDiv').show();
				SetCookie('thumbs',"show",1000);
				ModalCentre();
				if(typeof chosenCollection !== 'undefined' && chosenCollection)
					{
					jQuery('#CollectionMaxDiv select').chosen({disable_search_threshold:chosenCollectionThreshold});
					}
				}
			function HideThumbs()
				{
				myLayout.sizePane("south", 40);
				jQuery('.ui-layout-south').animate({scrollTop:0}, 'fast');			
				jQuery('#CollectionMinDiv').show();
				jQuery('#CollectionMaxDiv').hide();
				SetCookie('thumbs',"hide",1000);
				ModalCentre();
	
				if(typeof chosenCollection !== 'undefined' && chosenCollection)
					{
					jQuery('#CollectionMinDiv select').chosen({disable_search_threshold:chosenCollectionThreshold});
					}
				}
			function ToggleThumbs()
				{
				thumbs = getCookie("thumbs");
				if (thumbs=="show")
					{
				HideThumbs();
					}
				else
					{ 
					ShowThumbs();
					}
				}
			function InitThumbs()
				{
				<?php if ($collection_bar_hide_empty)
					{					
					echo "CheckHideCollectionBar();";
					}?>
				if(thumbs!="hide")
					{
					ShowThumbs();
					}
				else if(thumbs=="hide")
					{
					HideThumbs();
					}
                }

            jQuery(document).ready(function()
                {
                CollectionDivLoad('<?php echo $baseurl_short?>pages/collections.php?thumbs=<?php echo urlencode($thumbs); ?>&collection='+usercollection+'<?php echo (isset($k) ? "&k=".urlencode($k) : ""); ?>&order_by=<?php echo (isset($order_by) ? urlencode($order_by) : ""); ?>&sort=<?php echo (isset($sort) ? urlencode($sort) : ""); ?>&search=<?php echo (isset($search) ? urlencode($search) : ""); ?>&restypes=<?php echo (isset($restypes) ? urlencode($restypes) : "") ?>&archive=<?php echo (isset($archive) ? urlencode($archive) : "" ) ?>&daylimit=<?php echo (isset($daylimit) ? urlencode($daylimit) : "" ) ?>&offset=<?php echo (isset($offset) ? urlencode($offset) : "" );echo (isset($resources_count) ? "&resources_count=$resources_count" :""); ?>');
                InitThumbs();
                });
			
			</script>
			<?php
            } // end omit_collectiondiv_load_pages 
        ?>    
        <script type="text/javascript">
        var resizeTimer;
        myLayout=jQuery('body').layout(
            {
            livePaneResizing:true,
            triggerEventsDuringLiveResize: false,
            resizerTip: '<?php echo $lang["resize"]?>',

            east__spacing_open:0,
            east__spacing_closed:8,
            east_resizable: true,
            east__closable: false,
            east__size: 295,

            north_resizable: false,
            north__closable:false,
            north__spacing_closed: 0,
            north__spacing_open: 0,
            
            <?php
            if ($col_on)
                {?>
                south__resizable:true,
                south__minSize:40,
                south__spacing_open:8,
                south__spacing_closed:8, 
                south__togglerLength_open:"200",
                south__togglerTip_open: '<?php echo $lang["toggle"]?>',
                south__onclose_start: function(pane)
                    {
                    if (pane=="south" && (typeof colbarresizeon === "undefined" || colbarresizeon==true))
                        {
                        if(jQuery('.ui-layout-south').height()>40 && thumbs!="hide")
                            {
                            HideThumbs();
                            }
                        else if(jQuery('.ui-layout-south').height()<=40 && thumbs=="hide")
                            {
                            ShowThumbs();
                            }
                        return false;
                        }
                    ModalCentre();
                    },
                south__onresize: function(pane)
                    {
                    if (pane=="south" && (typeof colbarresizeon === "undefined" || colbarresizeon==true))
                        {
                        thumbs = getCookie("thumbs");
                        if(jQuery('.ui-layout-south').height() < collection_frame_height && thumbs!="hide")
                            {
                            HideThumbs();
                            }
                        else if(jQuery('.ui-layout-south').height()> 40 && thumbs=="hide")
                            {
                            ShowThumbs();
                            }
                        }
                    ModalCentre();
                    },
                <?php
                }
            else
                {?>                
                south__initHidden: true,
                <?php
                }

            if($browse_on) 
                {
                $browsesize = $browse_show ? $browse_width : "30";
                echo "
                west__closable:false,
                west__resizable:false,
                west__liveContentResizing: true,
                west__resizeContentWhileDragging: true,
                west__spacing_open: 0,
                west__minSize:30,
                west__size: " . $browsesize . ",
                ";
                }?>
            });
        <?php
        if($browse_on) 
            {?>
            window.onload = function()
                {
                if(document.getElementById('BrowseBarContainer'))
                    {
                    myLayout.sizePane("west", <?php echo $browsesize ?>);
                    jQuery('#BrowseBarContainer').show();
                    jQuery('#BrowseBarTab').show();
                    jQuery('#BrowseBarContent').width(<?php echo $browsesize ?>-30);
                    }
                }
                <?php
                }?>
        </script>
        <?php
        }
	
	if($responsive_ui && !hook("responsive_footer"))
		{
		?>
		<!-- Responsive -->
		<script src="<?php echo $baseurl_short; ?>lib/js/responsive.js?css_reload_key=<?php echo $css_reload_key; ?>"></script>
		<script>
        function toggleSimpleSearch()
            {
            if(jQuery("#searchspace").hasClass("ResponsiveSimpleSearch"))
                {
                jQuery("#searchspace").removeClass("ResponsiveSimpleSearch");
                jQuery("#SearchBarContainer").removeClass("FullSearch");
                jQuery("#Rssearchexpand").val("<?php echo $lang["responsive_more"];?>");
                jQuery('#UICenter').show(0);
                search_show = false;
                }
            else
                {
                jQuery("#searchspace").addClass("ResponsiveSimpleSearch");
                jQuery("#SearchBarContainer").addClass("FullSearch");
                jQuery("#Rssearchexpand").val(" <?php echo $lang["responsive_less"];?> ");
                jQuery('#UICenter').hide(0);
                search_show = true;
                }
            }
		
		function toggleResultOptions()
			{
			jQuery("#CentralSpace .TopInpageNavLeft .InpageNavLeftBlock").slideToggle(100);
			jQuery("#ResponsiveResultCount").toggle();
			jQuery("#SearchResultFound").hide();
			jQuery("#CentralSpace .TopInpageNavLeft .InpageNavLeftBlock.icondisplay").css('display', 'inline-block');
			}
		
		/* Responsive Stylesheet inclusion based upon viewing device */
		if(document.createStyleSheet)
			{
			document.createStyleSheet('<?php echo $baseurl ;?>/css/responsive/slim-style.css?rcsskey=<?php echo $css_reload_key; ?>');
			}
		else
			{
			jQuery("head").append("<link rel='stylesheet' href='<?php echo $baseurl ;?>/css/responsive/slim-style.css?rcsskey=<?php echo $css_reload_key; ?>' type='text/css' media='screen' />");
			}
		
		if(!is_touch_device() && jQuery(window).width() <= 1280)
			{
			if(document.createStyleSheet)
				{
				document.createStyleSheet('<?php echo $baseurl; ?>/css/responsive/slim-non-touch.css?rcsskey=<?php echo $css_reload_key; ?>');
				}
			else
				{
				jQuery("head").append("<link rel='stylesheet' href='<?php echo $baseurl; ?>/css/responsive/slim-non-touch.css?rcsskey=<?php echo $css_reload_key; ?>' type='text/css' media='screen' />");
				}
			}
		
		var responsive_show = "<?php echo $lang['responsive_collectiontogglehide'];?>";
		var responsive_hide;
		var responsive_newpage = true;
		
		if(jQuery(window).width() <= 1100)
			{
			jQuery('.ResponsiveViewFullSite').css('display', 'block');
			SetCookie("browse_show","hide");
			}
		else
			{
			jQuery('.ResponsiveViewFullSite').css('display', 'none');
			}
		
		if(jQuery(window).width()<=700)
			{
			touchScroll("UICenter");
			}
        
        var lastWindowWidth = jQuery(window).width();

		jQuery(window).resize(function()
			{
            // Check if already resizing
            if(typeof rsresize !== 'undefined')
                {
                return;
                }

            newwidth = jQuery( window ).width();

            if(lastWindowWidth > 1100 && newwidth < 1100 && (typeof browse_show === 'undefined' || browse_show != 'hide'))
                {
                // Set flag to prevent recursive loop
                rsresize = true;
                ToggleBrowseBar();
                rsresize = undefined;
                }
            else if(lastWindowWidth < 1100 && newwidth > 1100 && typeof browse_show !== 'undefined' && browse_show == 'show')
                {
                rsresize = true;
                ToggleBrowseBar('open');
                rsresize = undefined;
                }
            else if(lastWindowWidth > 900 && newwidth < 900)
                {
                rsresize = true;
                console.log("hiding collections");
                hideMyCollectionsCols();
                responsiveCollectionBar();
                jQuery('#CollectionDiv').hide(0);
                rsresize = undefined;
                }
            else if(lastWindowWidth < 900 && newwidth > 900)
                {
                rsresize = true;
                showResponsiveCollection();
                rsresize = undefined;
                }

            lastWindowWidth = newwidth;            
            });
		
		jQuery("#HeaderNav1Click").click(function(event)
			{
			event.preventDefault();
			if(jQuery(this).hasClass("RSelectedButton"))
				{
				jQuery(this).removeClass("RSelectedButton");
				jQuery("#HeaderNav1").slideUp(0);
				jQuery("#Header").removeClass("HeaderMenu");
				}
			else
				{
				jQuery("#HeaderNav2Click").removeClass("RSelectedButton");
				jQuery("#HeaderNav2").slideUp(80);				
				jQuery("#Header").addClass("HeaderMenu");				
				jQuery(this).addClass("RSelectedButton");
				jQuery("#HeaderNav1").slideDown(80);
				}
			if(jQuery("#searchspace").hasClass("ResponsiveSimpleSearch"))
				{
				toggleSimpleSearch();
				}      
			});
		
		jQuery("#HeaderNav2Click").click(function(event)
			{
			event.preventDefault();
			if(jQuery(this).hasClass("RSelectedButton"))
				{
				jQuery(this).removeClass("RSelectedButton");
				jQuery("#HeaderNav2").slideUp(0);
				jQuery("#Header").removeClass("HeaderMenu");
				
				}
			else
				{
				jQuery("#Header").addClass("HeaderMenu");
				jQuery("#HeaderNav1Click").removeClass("RSelectedButton");
				jQuery("#HeaderNav1").slideUp(80);
				jQuery(this).addClass("RSelectedButton");
				jQuery("#HeaderNav2").slideDown(80);
				} 
			if(jQuery("#searchspace").hasClass("ResponsiveSimpleSearch"))
				{
				toggleSimpleSearch();
				}  
			});
		
		jQuery("#HeaderNav2").on("click","a",function()
			{
			if(jQuery(window).width() <= 1200)
				{
				jQuery("#HeaderNav2").slideUp(0);
				jQuery("#HeaderNav2Click").removeClass("RSelectedButton");
				}
			});
		jQuery("#HeaderNav1").on("click","a",function()
			{
			if(jQuery(window).width() <= 1200)
				{
				jQuery("#HeaderNav1").slideUp(00);
				jQuery("#HeaderNav1Click").removeClass("RSelectedButton");
				}
			});
		jQuery("#SearchBarContainer").on("click","#Rssearchexpand",toggleSimpleSearch);
		jQuery("#CentralSpaceContainer").on("click","#Responsive_ResultDisplayOptions",function(event)
			{
			if(jQuery(this).hasClass("RSelectedButton"))
				{
				jQuery(this).removeClass("RSelectedButton");
				}
			else
				{
				jQuery(this).addClass("RSelectedButton");
				}
			toggleResultOptions();
			});
		
		if(jQuery(window).width() <= 700 && jQuery(".ListviewStyle").length && is_touch_device())
			{
			jQuery("td:last-child,th:last-child").hide();
			}
		</script>
		<!-- end of Responsive -->
		<?php
		} /* end of if $responsive_ui*/
	
	hook('afteruilayout');
	?>
	<!-- Start of modal support -->
	<div id="modal_overlay" onClick="ModalClose();"></div>
	<div id="modal_outer">
	<div id="modal">
	</div>
	</div>
	<div id="modal_dialog" style="display:none;"></div>
	<script type="text/javascript">
	jQuery(window).bind('resize.modal', ModalCentre);
	</script>
	<!-- End of modal support -->
	
	<script>
	
	try
		{
		top.history.replaceState(document.title+'&&&'+jQuery('#CentralSpace').html(), applicationname);
		}
	catch(e){console.log(e);
	}
	
	</script>

	<script>

		/* Destroy tagEditor if below breakpoint window size (doesn't work in responsize mode */

		window_width = jQuery(window).width();
		window_width_breakpoint = 1100;
		simple_search_pills_view = <?php if($simple_search_pills_view) { echo "true"; } else { echo "false"; } ?>;

		/* Page load */

		if(window_width <= window_width_breakpoint && simple_search_pills_view == true)
			{
			jQuery('#ssearchbox').tagEditor('destroy');
			}

		/* Page resized to below breakpoint */
		
		jQuery(window).resize(function() 
			{
			window_width = jQuery(window).width();
			if(window_width <= window_width_breakpoint && simple_search_pills_view == true)
				{
				jQuery('#ssearchbox').tagEditor('destroy');
				}
			});

	</script>
	
	<?php if ($chosen_dropdowns)
		{ ?>
		<!-- Chosen support -->
		<script>
		jQuery(document).ready(function()
			{
			for (var selector in chosen_config)
				{
				console.log("selector="+selector);
				jQuery(selector).each(function()
					{
					ChosenDropdownInit(this, selector);
					});
				}
			});
		</script>
		<!-- End of chosen support -->
		<?php
		}
	?>
	</body>
	</html><?php
	} // end if !ajax ?>
	
	
