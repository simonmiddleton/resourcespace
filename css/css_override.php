<?php
/**
* Add in CSS overrides for UI elements
*
* @package ResourceSpace
*/

include_once "../include/db.php";
include_once "../include/general.php";

header("Content-type: text/css");

global $header_colour_style_override, $header_link_style_override, $home_colour_style_override, $collection_bar_background_override,
$collection_bar_foreground_override;
$browse_on = has_browsebar();

# Override the header background colour
if ((isset($header_colour_style_override) && $header_colour_style_override != ''))
    {
    ?>
    #Header
        {
        background: <?php echo $header_colour_style_override; ?>;
        }
    #OverFlowLinks
        {
        background: <?php echo $header_colour_style_override; ?>;
        }
    <?php
    }

# Override the header link colour
if ((isset($header_link_style_override) && $header_link_style_override != ''))
    {
    ?>
    #HeaderNav1, #HeaderNav1 li a, #HeaderNav2 li a
        {
        color: <?php echo $header_link_style_override; ?>;
        }
    #HeaderNav2 li
        {
        border-color: <?php echo $header_link_style_override; ?>;
        }
    <?php
    }

# Override home UI elements colour (intro text, dash tiles, simple search)
if ((isset($home_colour_style_override) && $home_colour_style_override != ''))
    {
    ?>
    #SearchBox, #HomeSiteText.dashtext, .HomePanelIN, #BrowseBar, #BrowseBarTab, #NewsPanel, #remote_assist #SearchBoxPanel
        {
        background: <?php echo $home_colour_style_override; ?> !important;
        }
    <?php
    }

# Override the collection bar background colour
if ((isset($collection_bar_background_override) && $collection_bar_background_override != ''))
    {
    ?>
    .CollectBack
        {
        background: <?php echo $collection_bar_background_override; ?>;
        }
    <?php
    }

# Override the collection bar foreground colour
if ((isset($collection_bar_foreground_override) && $collection_bar_foreground_override != ''))
    {
    ?>
    .CollectionPanelShell, #CollectionDiv select
        {
        background-color: <?php echo $collection_bar_foreground_override; ?>;
        }

    .ui-layout-resizer
        {
        background: <?php echo $collection_bar_foreground_override; ?>;
        }
    <?php
    }

if ($browse_on)
    {
    ?>
    #CentralSpaceContainer
        {
        padding-left: 30px;
        }   
    #Footer
        {
        clear: both;
        } 

    <?php
    }