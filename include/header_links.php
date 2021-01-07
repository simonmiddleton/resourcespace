<?php if (!hook("replaceheadernav2")) { ?>
    <ul id="HeaderLinksContainer">
        <?php if (!hook("replacehomelinknav")) { ?>
            <?php if (!$use_theme_as_home && !$use_recent_as_home) { ?>
                <li class="HeaderLink">
                    <a href="<?php echo $baseurl?>/pages/<?php echo $default_home_page?>" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo ($default_home_page=="home.php"?DASH_ICON . $lang["dash"]:HOME_ICON . $lang["home"]) ?>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>

        <?php hook("topnavlinksafterhome"); ?>

        <?php if ($advanced_search_nav) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/search_advanced.php" onClick="return CentralSpaceLoad(this,true);">
                    <i aria-hidden="true" class="fa fa-fw fa-search-plus"></i>
                    <?php echo $lang["advancedsearch"]?>
                </a>
            </li>
        <?php } ?>

        <?php if ($search_results_link) { ?>
            <li class="HeaderLink">
            <?php if((checkperm("s")) &&  ((isset($_COOKIE["search_form_submit"]) )   || (isset($_COOKIE["search"]) && strlen($_COOKIE["search"])>0) || (isset($search) && (strlen($search)>0) && (strpos($search,"!")===false)))) { # active search present ?>
                    <a href="<?php echo $baseurl?>/pages/search.php" onClick="return CentralSpaceLoad(this,true);">
                        <i aria-hidden="true" class="fa fa-fw fa-search"></i>
                        <?php echo $lang["searchresults"]?>
                    </a>
                <?php } else { ?>
                <a class="SearchResultsDisabled">
                    <i aria-hidden="true" class="fa fa-fw fa-search"></i>
                    <?php echo $lang["searchresults"]?>
                </a>
                <?php } ?>
            </li>
        <?php } ?>

        <?php if (!hook("replacethemelink")) { ?>
            <?php if (checkperm("s") && $enable_themes && !$theme_direct_jump && $themes_navlink) { ?>
                <li class="HeaderLink">
                    <a href="<?php echo $baseurl?>/pages/collections_featured.php" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo FEATURED_COLLECTION_ICON . $lang["themes"]; ?>
                    </a>
                </li>
            <?php } ?>
        <?php } /* end hook replacethemelink */?>

        <?php if (checkperm("s") && ($public_collections_top_nav || $public_collections_header_only)) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/collection_public.php" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo $lang["publiccollections"]?>
                </a>
            </li>
        <?php } ?>

        <?php if (checkperm("s") && $mycollections_link && !checkperm("b")) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/collection_manage.php" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo $lang["mycollections"]?>
                </a>
            </li>
        <?php } ?>

        <?php if (!hook("replacerecentlink")) { ?>
            <?php if (checkperm("s") && $recent_link) { ?>
                <li class="HeaderLink">
                    <a href="<?php echo $baseurl?>/pages/search.php?search=<?php if ($recent_search_by_days) {echo "&amp;recentdaylimit=" . $recent_search_by_days_default . "&amp;order_by=resourceid&amp;sort=desc";} else {echo urlencode("!last".$recent_search_quantity);}?>&order_by=resourceid" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo RECENT_ICON . $lang["recent"]?>
                    </a>
                </li>
            <?php } ?>
        <?php } /* end hook replacerecentlink */?>

        <?php if (checkperm("s") && $myrequests_link && checkperm("q")) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/requests.php" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo $lang["myrequests"]?>
                </a>
            </li>
        <?php } ?>

        <?php if (!hook("replacemycontributionslink")) { ?>
            <?php if ((checkperm("d") && $mycontributions_userlink)||($mycontributions_link && checkperm("c"))) { ?>
                <li class="HeaderLink">
                    <a href="<?php echo $baseurl?>/pages/contribute.php" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo $lang["mycontributions"]?>
                    </a>
                </li>
            <?php } ?>
        <?php } /* end hook replacemycontributionslink */?>

        <?php if (!hook("replaceresearchrequestlink")) { ?>
            <?php if (($research_request) && ($research_link) && (checkperm("s")) && (checkperm("q"))) { ?>
                <li class="HeaderLink">
                    <a href="<?php echo $baseurl?>/pages/research_request.php" onClick="return CentralSpaceLoad(this,true);">
                        <?php echo $lang["researchrequest"]?>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>

        <?php if ($speedtagging && checkperm("s") && checkperm("n")) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/tag.php" onClick="return CentralSpaceLoad(this,true);">
                    <i aria-hidden="true" class="fa fa-fw fa-tags"></i>
                    <?php echo $lang["tagging"]?>
                </a>
            </li>
        <?php } ?>
                
        <?php 
        /* ------------ Customisable top navigation ------------------- */
        if(isset($custom_top_nav))
            {
            for($n = 0; $n < count($custom_top_nav); $n++)
                {
                // External links should open in a new tab
                if (strpos($custom_top_nav[$n]['link'], $baseurl) === false)
                    {
                    $on_click = '';
                    $target   = ' target="_blank"';
                    }
                    //Internal links can still open in the same tab             
                else
                    {
                    if(isset($custom_top_nav[$n]['modal']) && $custom_top_nav[$n]['modal'] )
                        {
                        $on_click = ' onClick="return ModalLoad(this, true);"';
                        $target   = '';
                        }   
                    else if (!isset($custom_top_nav[$n]['modal']) || (isset($custom_top_nav[$n]['modal'])&& !$custom_top_nav[$n]['modal']))
                        {
                        $on_click = ' onClick="return CentralSpaceLoad(this, true);"';
                        $target   = '';
                        }
                    }
                    if(strpos($custom_top_nav[$n]['title'], '(lang)') !== false)
                        {
                        $custom_top_nav_title=str_replace("(lang)","",$custom_top_nav[$n]["title"]);
                        $custom_top_nav[$n]["title"]=$lang[$custom_top_nav_title];
                        }
                        ?>
                    <li class="HeaderLink">
                        <a href="<?php echo $custom_top_nav[$n]["link"] ?>"<?php echo $target . $on_click; ?>>
                            <?php echo i18n_get_translated($custom_top_nav[$n]["title"]) ?>
                        </a>
                    </li>
                    <?php
                }
            } ?>
                
        <?php if ($help_link) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/help.php" onClick="return <?php if (!$help_modal) { ?>CentralSpaceLoad(this,true);<?php } else { ?>ModalLoad(this,true);<?php } ?>">
                    <?php echo HELP_ICON . $lang["helpandadvice"]?>
                </a>
            </li>
        <?php } ?>

        <?php global $nav2contact_link; if ($nav2contact_link) { ?>
            <li class="HeaderLink">
                <a href="<?php echo $baseurl?>/pages/contact.php" onClick="return CentralSpaceLoad(this,true);">
                    <?php echo $lang["contactus"]?>
                </a>
            </li>
        <?php }

        hook("toptoolbaradder"); ?>

    </ul><!-- close HeaderLinksContainer -->

    <script>
    headerLinksDropdown();
    </script>

<?php } /* end replaceheadernav1 */