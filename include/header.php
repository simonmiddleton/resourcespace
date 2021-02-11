<?php 
hook ("preheaderoutput");
 
$k=getvalescaped("k","");
if(!isset($internal_share_access))
	{
	// Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
    $internal_share_access = internal_share_access();
	}

$logout=getvalescaped("logout","");
$loginas=getvalescaped("loginas","");

# Do not display header / footer when dynamically loading CentralSpace contents.
$ajax=getval("ajax","");

if ($ajax=="" && !hook("replace_header")) { 

if(!isset($thumbs) && ($pagename!="login") && ($pagename!="user_password") && ($pagename!="user_request"))
    {
    $thumbs=getval("thumbs","unset");
    if($thumbs == "unset")
        {
        $thumbs = $thumbs_default;
        rs_setcookie("thumbs", $thumbs, 1000,"","",false,false);
        }
    }

	
?><!DOCTYPE html>
<html lang="<?php echo $language ?>">	

<!--

 ResourceSpace version <?php echo $productversion?>

 For copyright and license information see /documentation/licenses/resourcespace.txt
 https://www.resourcespace.com
 -->

<head>
<?php if(!hook("customhtmlheader")): ?>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
<?php if ($search_engine_noindex || (getval("k","")!="" && $search_engine_noindex_external_shares))
    {
    ?>
    <meta name="robots" content="noindex,nofollow">
    <?php
    }
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
<?php hook('extra_meta'); ?>

<title><?php echo htmlspecialchars($applicationname)?></title>
<?php
if('' == $header_favicon)
    {
    $header_favicon = 'gfx/interface/favicon.png';
    }

$favicon = "{$baseurl}/{$header_favicon}";

if(strpos($header_favicon, '[storage_url]') !== false)
    {
    $favicon = str_replace('[storage_url]', $storageurl, $header_favicon);
    }
?>
<link rel="icon" type="image/png" href="<?php echo $favicon; ?>" />

<!-- Load jQuery and jQueryUI -->
<script src="<?php echo $baseurl . $jquery_path; ?>?css_reload_key=<?php echo $css_reload_key; ?>"></script>
<script src="<?php echo $baseurl. $jquery_ui_path?>?css_reload_key=<?php echo $css_reload_key; ?>" type="text/javascript"></script>
<script src="<?php echo $baseurl; ?>/lib/js/jquery.layout.js?css_reload_key=<?php echo $css_reload_key?>"></script>
<link type="text/css" href="<?php echo $baseurl?>/css/smoothness/jquery-ui.min.css?css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" />
<script src="<?php echo $baseurl?>/lib/js/jquery.ui.touch-punch.min.js"></script>
<?php if ($pagename=="login") { ?><script type="text/javascript" src="<?php echo $baseurl?>/lib/js/jquery.capslockstate.js"></script><?php } ?>
<?php if ($image_preview_zoom) { ?><script src="<?php echo $baseurl?>/lib/js/jquery.zoom.js"></script><?php } ?>
<script type="text/javascript" src="<?php echo $baseurl?>/lib/js/jquery.tshift.min.js"></script>
<script type="text/javascript" src="<?php echo $baseurl?>/lib/js/jquery-periodical-updater.js"></script>

<?php 
if ($slideshow_big) 
    { ?>
    <script type="text/javascript">StaticSlideshowImage=<?php echo $static_slideshow_image?"true":"false";?>;</script>
    <script type="text/javascript" src="<?php echo $baseurl?>/lib/js/slideshow_big.js?css_reload_key=<?php echo $css_reload_key?>"></script>
    <link type="text/css" href="<?php echo $baseurl?>/css/slideshow_big.css?css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" />
    <?php 
    }


if ($contact_sheet)
    {?>
    <script type="text/javascript" src="<?php echo $baseurl?>/lib/js/contactsheet.js"></script>
    <script>
    contactsheet_previewimage_prefix = '<?php echo addslashes($storageurl)?>';
    </script>
    <script type="text/javascript">
    jQuery.noConflict();
    </script>
    <?php 
    } ?>

<script type="text/javascript">
	ajaxLoadingTimer=<?php echo $ajax_loading_timer;?>;
</script>
<?php
global $enable_ckeditor;
if ($enable_ckeditor){?>
<script type="text/javascript" src="<?php echo $baseurl?>/lib/ckeditor/ckeditor.js"></script><?php } ?>
<?php if (!$disable_geocoding)
    { ?>
    <script src="<?php echo $baseurl ?>/lib/OpenLayers/OpenLayers.js"></script>
    <?php
    } ?>
<?php if (!hook("ajaxcollections")) { ?>
<script src="<?php echo $baseurl;?>/lib/js/ajax_collections.js?css_reload_key=<?php echo $css_reload_key?>" type="text/javascript"></script>
<?php } ?>

<script type="text/javascript" src="<?php echo $baseurl_short;?>lib/plupload_2.1.8/plupload.full.min.js?<?php echo $css_reload_key;?>"></script>
<?php if ($plupload_widget){?>
	<link href="<?php echo $baseurl_short;?>lib/plupload_2.1.8/jquery.ui.plupload/css/jquery.ui.plupload.css?<?php echo $css_reload_key;?>" rel="stylesheet" type="text/css" media="screen,projection,print"  />	
	<script type="text/javascript" src="<?php echo $baseurl_short;?>lib/plupload_2.1.8/jquery.ui.plupload/jquery.ui.plupload.min.js?<?php echo $css_reload_key;?>"></script>
<?php } else { ?>
	<link href="<?php echo $baseurl_short;?>lib/plupload_2.1.8/jquery.plupload.queue/css/jquery.plupload.queue.css?<?php echo $css_reload_key;?>" rel="stylesheet" type="text/css" media="screen,projection,print"  />
	<script type="text/javascript" src="<?php echo $baseurl_short;?>lib/plupload_2.1.8/jquery.plupload.queue/jquery.plupload.queue.min.js?<?php echo $css_reload_key;?>"></script>
<?php } ?>
<?php
if ($keyboard_navigation_video_search || $keyboard_navigation_video_view || $keyboard_navigation_video_preview)
    {
    ?>
	<script type="text/javascript" src="<?php echo $baseurl_short?>lib/js/videojs-extras.js?<?php echo $css_reload_key?>"></script>
    <?php
    }

if($simple_search_pills_view)
    {
    ?>
    <script src="<?php echo $baseurl_short; ?>lib/jquery_tag_editor/jquery.caret.min.js"></script>
    <script src="<?php echo $baseurl_short; ?>lib/jquery_tag_editor/jquery.tag-editor.min.js"></script>
    <link type="text/css" rel="stylesheet" href="<?php echo $baseurl_short; ?>lib/jquery_tag_editor/jquery.tag-editor.css" />
    <?php
    }
?>

<!-- FLOT for graphs -->
<script language="javascript" type="text/javascript" src="<?php echo $baseurl_short; ?>lib/flot/jquery.flot.js"></script> 
<script language="javascript" type="text/javascript" src="<?php echo $baseurl_short; ?>lib/flot/jquery.flot.time.js"></script> 
<script language="javascript" type="text/javascript" src="<?php echo $baseurl_short; ?>lib/flot/jquery.flot.pie.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $baseurl_short; ?>lib/flot/jquery.flot.tooltip.min.js"></script>

<!-- jsTree -->
<link rel="stylesheet" href="<?php echo $baseurl_short; ?>lib/jstree/themes/default/style.min.css">
<script src="<?php echo $baseurl_short; ?>lib/jstree/jstree.min.js"></script>
<script src="<?php echo $baseurl_short; ?>lib/js/category_tree.js?css_reload_key=<?php echo $css_reload_key; ?>"></script>

<!-- Chosen support -->
<?php 
if ($chosen_dropdowns || $chosen_dropdowns_collection) 
        { 
        ?>
        <script src="<?php echo $baseurl_short ?>lib/chosen/chosen.jquery.min.js" type="text/javascript"></script>
        <link rel="stylesheet" href="<?php echo $baseurl_short ?>lib/chosen/chosen.min.css">
        <?php
        }
        
global $not_authenticated_pages;
$not_authenticated_pages = array('login', 'user_change_password','user_password','user_request');

$browse_on = has_browsebar();
if($browse_on)
    {
    $browse_width   = $browse_default_width;
    $browse_show    = getval("browse_show","") == "show";
    ?>
    <script src="<?php echo $baseurl_short ?>lib/js/browsebar_js.php" type="text/javascript"></script>
    <?php
    }
?>

<script type="text/javascript">
var baseurl_short="<?php echo $baseurl_short?>";
var baseurl="<?php echo $baseurl?>";
var pagename="<?php echo $pagename?>";
var errorpageload = "<h1><?php echo $lang["error"] ?></h1><p><?php echo str_replace(array("\r","\n"),'',nl2br($lang["error-pageload"])) ?></p>";
var errortext = "<?php echo $lang["error"] ?>";
var applicationname = "<?php echo $applicationname?>";
var branch_limit="<?php echo $cat_tree_singlebranch?>";
var branch_limit_field = new Array();
var global_cookies = "<?php echo $global_cookies?>";
var global_trash_html = '<!-- Global Trash Bin (added through CentralSpaceLoad) -->';
var TileNav = <?php echo ($tilenav?"true":"false") ?>;
<?php
if (!hook("replacetrashbin", "", array("js" => true)))
    {
    echo "global_trash_html += '" . render_trash("trash","", true) . "';\n";
    }
?>
oktext="<?php echo $lang["ok"] ?>";
var scrolltopElementCentral='.ui-layout-center';
var scrolltopElementCollection='.ui-layout-south';
var scrolltopElementModal='#modal'
collection_bar_hide_empty=<?php echo $collection_bar_hide_empty?"true":"false"; ?>;
<?php 
if ($chosen_dropdowns) 
	{ 
	?>
	var chosen_config = 
		{
		"#CentralSpace select"           : {disable_search_threshold:<?php echo $chosen_dropdowns_threshold_main ?>, allow_single_deselect: true, width:"0px"},
		"#SearchBox select"           : {disable_search_threshold:<?php echo $chosen_dropdowns_threshold_simplesearch ?>, allow_single_deselect: true, width:"0px"}
		}
	<?php
	}
?>
var chosenCollection='<?php echo ($chosen_dropdowns_collection)?>';
<?php
if($chosen_dropdowns_collection)
    {
    ?>
    var chosenCollectionThreshold='<?php echo $chosen_dropdowns_threshold_collection ?>';
	<?php
	}

if($browse_on)
    {
    echo "browse_width = '" . $browse_width . "';
    browse_clicked = false;";     
    }
?>
</script>

<script src="<?php echo $baseurl_short?>lib/js/global.js?css_reload_key=<?php echo $css_reload_key?>" type="text/javascript"></script>
<script src="<?php echo $baseurl_short?>lib/js/polyfills.js?css_reload_key=<?php echo $css_reload_key; ?>"></script>

<?php if ($keyboard_navigation)
    {
    include (dirname(__FILE__) . "/keyboard_navigation.php");
    }
hook("additionalheaderjs");?>

<?php
echo $headerinsert;
$extrafooterhtml="";
?>

<!-- Structure Stylesheet -->
<link href="<?php echo $baseurl?>/css/global.css?css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" type="text/css" media="screen,projection,print" />
<!-- Colour stylesheet -->
<link href="<?php echo $baseurl?>/css/colour.css?css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" type="text/css" media="screen,projection,print" />
<!-- Override stylesheet -->
<link href="<?php echo $baseurl?>/css/css_override.php?k=<?php echo htmlspecialchars($k); ?>&css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" type="text/css" media="screen,projection,print" />
<!--- FontAwesome for icons-->
<link rel="stylesheet" href="<?php echo $baseurl?>/lib/fontawesome/css/all.min.css?css_reload_key=<?php echo $css_reload_key?>">
<link rel="stylesheet" href="<?php echo $baseurl?>/lib/fontawesome/css/v4-shims.min.css?css_reload_key=<?php echo $css_reload_key?>">
<!-- Load specified font CSS -->
<link id="global_font_link" href="<?php echo $baseurl?>/css/fonts/<?php echo $global_font ?>.css?css_reload_key=<?php echo $css_reload_key?>" rel="stylesheet" type="text/css" />

<?php
echo get_plugin_css();
// after loading these tags we change the class on them so a new set can be added before they are removed (preventing flickering of overridden theme)
?>
<script>jQuery('.plugincss').attr('class','plugincss0');</script>
<?php

hook("headblock");
 
endif; # !hook("customhtmlheader") 
?>
</head>
<body lang="<?php echo $language ?>" class="<?php echo implode(' ', $body_classes); ?>" <?php if (isset($bodyattribs)) { ?><?php echo $bodyattribs?><?php } ?>>

<!-- Loading graphic -->
<?php
if(!hook("customloadinggraphic"))
	{
	?>
	<div id="LoadingBox"><i aria-hidden="true" class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i></div>
	<?php
	}
?>

<?php hook("bodystart"); ?>

<!--Global Header-->
<?php
if (($pagename=="terms") && (getval("url","")=="index.php")) {$loginterms=true;} else {$loginterms=false;}
if (($pagename!="preview" || $preview_header_footer) && $pagename!="preview_all") { ?>

<?php
$homepage_url=$baseurl."/pages/".$default_home_page;
if($use_theme_as_home) { $homepage_url = $baseurl."/pages/collections_featured.php"; }
if ($use_recent_as_home){$homepage_url=$baseurl."/pages/search.php?search=".urlencode('!last'.$recent_search_quantity);}
if ($pagename=="login" || $pagename=="user_request" || $pagename=="user_password"){$homepage_url=$baseurl."/index.php";}

hook("beforeheader");

# Calculate Header Image Display
if(isset($usergroup))
    {
    //Get group logo value
    $curr_group = get_usergroup($usergroup);
    if (!empty($curr_group["group_specific_logo"]))
        {
        $linkedheaderimgsrc = (isset($storageurl)? $storageurl : $baseurl."/filestore"). "/admin/groupheaderimg/group".$usergroup.".".$curr_group["group_specific_logo"];
        }
    }

$linkUrl=isset($header_link_url) ? $header_link_url : $homepage_url;
?>
<div id="Header" class="<?php
        echo in_array($pagename, $not_authenticated_pages) ? ' LoginHeader ' : ' ui-layout-north ';
        echo ((isset($slimheader_darken) && $slimheader_darken) ? 'slimheader_darken' : '');
        echo ((isset($slimheader_fixed_position) && $slimheader_fixed_position) ? ' SlimHeaderFixedPosition' : '');
        echo " " . $header_size;
?>">

<?php
if($responsive_ui)
    {
    ?>
    <div id="HeaderResponsive">
    <?php
    }
	
hook('responsiveheader');

if(!hook('replace_header_text_logo'))
	{
	if($header_text_title) 
		{?>
		<div id="TextHeader"><?php if ($k=="" || $internal_share_access){?><a href="<?php echo $homepage_url?>"  onClick="return CentralSpaceLoad(this,true);"><?php } ?><?php echo $applicationname;?><?php if ($k=="" || $internal_share_access){?></a><?php } ?></div>
		<?php if ($applicationdesc!="")
				{?>
				<div id="TextDesc"><?php echo i18n_get_translated($applicationdesc);?></div>
				<?php 
				}
		}
	else
		{
        $header_img_src = get_header_image();
		if($header_link && ($k=="" || $internal_share_access))
			{?>
			<a href="<?php echo $linkUrl; ?>" onClick="return CentralSpaceLoad(this,true);" class="HeaderImgLink"><img src="<?php echo $header_img_src; ?>" id="HeaderImg" ></img></a>
			<?php
			}
		else
			{?>
			<div class="HeaderImgLink"><img src="<?php echo $header_img_src; ?>" id="HeaderImg"></img></div>
			<?php
			}
		}
	}

$user_profile_image = get_profile_image($userref,false);

// Responsive
if($responsive_ui)
    {
    if (isset($username) && ($pagename!="login") && ($loginterms==false) && getval("k","")=="") 
        { 
        ?>   
        <div id="HeaderButtons" style="display:none;">
            <a href="#" id="HeaderNav1Click" class="ResponsiveHeaderButton ResourcePanel ResponsiveButton">
                <span class="rbText"><?php echo $allow_password_change == false ? htmlspecialchars(($userfullname=="" ? $username : $userfullname)) : $lang["responsive_settings_menu"]; ?></span>
                <?php if ($user_profile_image != "")
                    {
                    ?><img src='<?php echo $user_profile_image; ?>' alt='Profile icon'  id='UserProfileImage'> <?php
                    }
                else
                    {
                    ?><span class="fa fa-fw fa-lg fa-user"></span> <?php
                    }
                ?></a>
            <a href="#" id="HeaderNav2Click" class="ResponsiveHeaderButton ResourcePanel ResponsiveButton">
                <span class="rbText"><?php echo $lang["responsive_main_menu"]; ?></span>
                <span class="fa fa-fw fa-lg fa-bars"></span>
            </a>
        </div>
        <?php
        }
        ?>
    </div>
    <?php
    } // end of Responsive

hook("headertop");

if (!isset($allow_password_change)) {$allow_password_change=true;}
if(isset($username) && !in_array($pagename, $not_authenticated_pages) && false == $loginterms && '' == $k || $internal_share_access)
    {
	?>
	<div id="HeaderNav1" class="HorizontalNav">

<?php
hook("beforeheadernav1");
if (checkPermission_anonymoususer())
	{
	if (!hook("replaceheadernav1anon")) 
        {
    	?>
    	<ul>
    	<li><a href="<?php echo $baseurl?>/login.php"<?php if($anon_login_modal){?> onClick="return ModalLoad(this,true,true);" <?php } ?>><?php echo $lang["login"]?></a></li>
    	<?php hook("addtoplinksanon");?>
    	<?php if ($contact_link) { ?><li><a href="<?php echo $baseurl?>/pages/contact.php" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang["contactus"]?></a></li><?php } ?>
    	</ul>
    	<?php
    	} /* end replaceheadernav1anon */
	}
else
	{
	if (!hook("replaceheadernav1")) {
	?>
    <ul>
        
    <?php if ($header_search && $k=="") { ?>
    <li>
	<form class="HeaderSearchForm" id="header_search_form" method="post" action="<?php echo $baseurl?>/pages/search.php" onSubmit="return CentralSpacePost(this,true);">
    <?php
    generateFormToken("header_search_form");
    ?>
    <input id="ssearchbox" name="search" type="text" class="searchwidth" placeholder="<?php echo $lang['simplesearch'] . '...'; ?>" value="<?php echo (isset($quicksearch)?$htmlspecialchars($quicksearch):"") ?>" />
    
    <a href="<?php echo $baseurl; ?>/pages/simple_search.php" onClick="ModalClose(); return ModalLoad(this, true, true, 'right');">
                <i aria-hidden="true" class="fa fa-filter fa-lg fa-fw"></i>
            </a>
    </form>
        
    </li>
    <?php }

    if (($top_nav_upload && checkperm("c")) || ($top_nav_upload_user && checkperm("d")))
        {?>
        <li class="HeaderLink UploadButton">
            <a href="<?php echo $baseurl ?>/?upload=true" onClick="return CentralSpaceLoad(this,true);"><?php echo UPLOAD_ICON ?><?php echo $lang["upload"]; ?></a>
        </li><?php
        }

    
    if(!hook('replaceheaderfullnamelink'))
        {
        ?>
        <li>
            <a href="<?php echo $baseurl; ?>/pages/user/user_home.php" onClick="ModalClose(); return ModalLoad(this, true, true, 'right');">
            <?php
			if (isset($header_include_username) && $header_include_username)
                {
                if ($user_profile_image != "")
                    {                    
                    ?><img src='<?php echo $user_profile_image; ?>' alt='Profile icon' id='UserProfileImage'> &nbsp;<?php echo htmlspecialchars($userfullname=="" ? $username : $userfullname) ?>
                    <span class="MessageTotalCountPill Pill" style="display: none;"></span>
                    <?php
                    }
                else
                    {
                    ?>
                    <i aria-hidden="true" class="fa fa-user fa-fw"></i>&nbsp;<?php echo htmlspecialchars($userfullname=="" ? $username : $userfullname) ?>
                    <span class="MessageTotalCountPill Pill" style="display: none;"></span>
                    <?php
                    }
                }
            else
                {
                if ($user_profile_image != "")
                    {
                    ?><img src='<?php echo $user_profile_image; ?>' alt='Profile icon'  id='UserProfileImage'> <span class="MessageTotalCountPill Pill" style="display: none;"></span>
                    <?php
                    }
                else
                    {
                    ?>
                    <i aria-hidden="true" class="fa fa-user fa-lg fa-fw"></i><span class="MessageTotalCountPill Pill" style="display: none;"></span>
                    <?php
                    }
                }
            ?> 
            </a>
            <div id="MessageContainer" style="position:absolute; "></div>
        <?php
        }
        ?>
        </li>
	
	<!-- Admin menu link -->
	<?php if (checkperm("t")) { ?><li><a href="<?php echo $baseurl?>/pages/team/team_home.php" onClick="ModalClose();return ModalLoad(this,true,true,'right');"><i aria-hidden="true" class="fa fa-lg fa-bars fa-fw"></i></a>
	<?php if (!$actions_on && $team_centre_alert_icon && (checkperm("R")||checkperm("r")))
			{
			# Show pill count if there are any pending requests
			$pending=sql_value("select sum(thecount) value from (select count(*) thecount from request where status = 0 union select count(*) thecount from research_request where status = 0) as theunion",0);
			if ($pending>0)
				{
				?><span class="Pill"><?php echo $pending ?></span><?php
				}
			}
		?>
	</li><?php } ?>
	<!-- End of team centre link -->
	
	<?php hook("addtoplinks"); ?>
	
	</ul>
	<?php
	} /* end replaceheadernav1 */
	}
hook("afterheadernav1");
include_once __DIR__ . '/../pages/ajax/message.php';
?>
</div>
<?php hook("midheader"); ?>
<div id="HeaderNav2" class="HorizontalNav HorizontalWhiteNav">
<?php
if(!($pagename == "terms" && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"],"login") !== false && $terms_login))
    {
        include (dirname(__FILE__) . "/header_links.php");
    }
?>
</div> 

<?php } else if (!hook("replaceloginheader")) { # Empty Header?>
<div id="HeaderNav1" class="HorizontalNav ">&nbsp;</div>
<div id="HeaderNav2" class="HorizontalNav HorizontalWhiteNav">&nbsp;</div>
<?php } ?>

<?php } ?>

<?php hook("headerbottom"); ?>

<div class="clearer"></div><?php if ($pagename!="preview" && $pagename!="preview_all") { ?></div><?php } #end of header ?>

<?php
 $omit_searchbar_pages = array(
        'index',
        'preview_all',
        'search_advanced',
        'preview',
        'admin_header',
        'login',
        'user_request',
        'user_password',
        'user_change_password',
        'document_viewer'
    );

if($pagename == "terms" && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"],"login") !== false && $terms_login)
    {
        array_push($omit_searchbar_pages, 'terms');
        $collections_footer = false;
    }
 
# if config set to display search form in header or (usergroup search permission omitted and anonymous login panel not to be displayed, then do not show simple search bar    
if ($header_search || (!checkperm("s") && !($show_anonymous_login_panel && isset($anonymous_login) && (isset($username)) && ($username==$anonymous_login)) ) )
    { }
    else 
    {
    # Include simple search sidebar?
   
    $modified_omit_searchbar_pages=hook("modifyomitsearchbarpages");
    if ($modified_omit_searchbar_pages){$omit_searchbar_pages=$modified_omit_searchbar_pages;}
        
    if (!in_array($pagename,$omit_searchbar_pages) && ($loginterms==false) && ($k == '' || $internal_share_access) && !hook("replace_searchbarcontainer") ) 	
        {
        ?>
        <div id="SearchBarContainer" class="ui-layout-east" >
        <?php
        include dirname(__FILE__)."/searchbar.php";
        
        ?>
        </div>
        <?php
        }
    }
?>

<?php
# Determine which content holder div to use
if (($pagename=="login") || ($pagename=="user_password") || ($pagename=="user_request") || ($pagename=="user_change_password"))
    {
    $div="CentralSpaceLogin";
    $uicenterclass="NoSearch";
    }
else
    {
    $div="CentralSpace";
    if (in_array($pagename,$omit_searchbar_pages))
        {
        $uicenterclass="NoSearch";
        }
    else
        {
        $uicenterclass="Search";
        }
    }
?>
<!--Main Part of the page-->
<?php

if($browse_on && checkperm("s") === true)
    {
    render_browse_bar();
    }
        
echo '<div id="UICenter" class="ui-layout-center ' . $uicenterclass . '">';

hook('afteruicenter');

if (!in_array($pagename, $not_authenticated_pages))
    {
    // Set classes for CentralSpaceContainer
    $csc_classes = array();
    if(isset($username) && !in_array($pagename, $not_authenticated_pages) && false == $loginterms && ('' == $k || $internal_share_access) && $browse_bar) 
        {
        if($header_search)
            {
            $csc_classes[] = "NoSearchBar";
            }
        }
    echo '<div id="CentralSpaceContainer" ' . (count($csc_classes) > 0 ? 'class="' . implode(' ', $csc_classes) . '"' : '' ) . '>';
    }

hook("aftercentralspacecontainer");
?>
<div id="<?php echo $div?>">


<?php

hook("afterheader");

} // end if !ajax

// Update header links to add a class that indicates current location
// We parse URL for systems that are one level deep under web root
$parsed_url = parse_url($baseurl);

$scheme = @$parsed_url['scheme'];
$host   = @$parsed_url['host'];
$port   = (isset($parsed_url['port']) ? ":{$parsed_url['port']}" : "");

$activate_header_link = "{$scheme}://{$host}{$port}" . urlencode($_SERVER["REQUEST_URI"]);
?>
<script>
 
<?php
echo "linkreload = " . (($k != "" || $internal_share_access) ? "false" : "true") . ";";
?>

jQuery(document).ready(function()
    {
    ActivateHeaderLink(<?php echo json_encode($activate_header_link); ?>);

    jQuery(document).mouseup(function(e) 
        {
        var linksContainer = jQuery("#DropdownCaret");
        if (linksContainer.has(e.target).length === 0 && !linksContainer.is(e.target)) 
            {
            jQuery('#OverFlowLinks').hide();
            }
        });
    });

window.onresize=function()
    {
    ReloadLinks();
    }
</script>
<?php
// Non-ajax specific hook 
hook("start_centralspace");

	

if ($k!="" && !$internal_share_access) { ?>
<style>
#CentralSpaceContainer  {padding-right:0;margin: 0px 10px 20px 25px;}
</style>
<?php }
// Ajax specific hook
if ($ajax) {
    // remove Spectrum colour picker as it is out of CentralSpace div scope
    ?><script>
        jQuery('.sp-container').remove();
    </script>
    <?php
    hook("afterheaderajax");
}
?>
