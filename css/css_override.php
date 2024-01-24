<?php
/**
* Add in CSS overrides for UI elements
*
* @package ResourceSpace
*/

include_once "../include/db.php";

$k = getval('k', '');
if((is_array($k) || trim($k) === '') && getval('noauth','') != true) 
    {
    include '../include/authenticate.php';
    }

header("Content-type: text/css");

global $header_colour_style_override, $header_link_style_override, $home_colour_style_override, $collection_bar_background_override,
$collection_bar_foreground_override, $button_colour_override;

# Override the header background colour
if ((isset($header_colour_style_override) && $header_colour_style_override != ''))
    {
    ?>
    #Header, #OverFlowLinks, #LoginHeader
        {
        background: <?php echo $header_colour_style_override; ?>;
        }
    <?php
    }

# Override the header link colour
if ((isset($header_link_style_override) && $header_link_style_override != ''))
    {
    ?>

    #HeaderNav1, #HeaderNav1 li a, #HeaderNav2 li a, #HiddenLinks li.HeaderLink a
        {
        color: <?php echo $header_link_style_override; ?>;
        }
    #HeaderNav2 li
        {
        border-color: <?php echo $header_link_style_override; ?>;
        }
    #HeaderNav1 li.UploadButton a 
        {
        color: #FFFFFF;
        }
    
    <?php
    }

# Override home UI elements colour (intro text, dash tiles, simple search)
if ((isset($home_colour_style_override) && $home_colour_style_override != ''))
    {
    ?>
    #SearchBox, #HomeSiteText.dashtext, .HomePanelIN, #BrowseBar, #NewsPanel.BasicsBox, #remote_assist #SearchBoxPanel,
    .SearchBarTab.SearchBarTabSelected
        {
        background: <?php echo $home_colour_style_override; ?>;
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

/**
 * Override the collection bar foreground colour
 * 
 * optgroup and option background-color set to #474747 for consistency across platforms as 
 * Firefox/Windows does not recognise rgb() colour properties for optgroup element
 * */

if ((isset($collection_bar_foreground_override) && $collection_bar_foreground_override != ''))
    {
    ?>
    .CollectionPanelShell, #CollectionDiv select
        {
        background-color: <?php echo $collection_bar_foreground_override; ?>;
        }    
    
    #CollectionDiv option, #CollectionDiv optgroup 
        {
        font-style:normal;
        background-color: #474747;
        color: #fff;
        }    

    .ui-layout-resizer
        {
        background: <?php echo $collection_bar_foreground_override; ?>;
        }
    <?php
    }

// Override the button colour
if ((isset($button_colour_override) && $button_colour_override != ''))
    {
    ?>
    button,
    input[type=submit],
    input[type=button],
    .RecordPanel .RecordDownloadSpace .DownloadDBlend a,
    .UploadButton a,
    .uppy-StatusBar-actionBtn,
    .uppy-Dashboard-browse,
    .uppy-StatusBar.is-waiting .uppy-StatusBar-actionBtn--upload,
    .uppy-StatusBar.is-waiting .uppy-StatusBar-actionBtn--upload:hover,
    .uppy-DashboardContent-back, .uppy-DashboardContent-back:focus,
    .uppy-DashboardContent-addMore, .uppy-DashboardContent-addMore:focus {
        background-color: <?php echo $button_colour_override; ?>;
    }
    <?php
    }

// Apply user uploaded custom font
if (isset($custom_font) && $custom_font != '')
    {
    $custom_font_url = str_replace('[storage_url]', $storageurl, $custom_font);
    ?>
    @font-face {
    font-family: "custom_font";
    src: url("<?php echo $custom_font_url; ?>");
    }
    h1,h2,h3,h4,h5,h6,.Title {font-family: custom_font, Arial, Helvetica, sans-serif;}
    .ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button,.ui-widget, body, input, textarea, select, button {font-family: custom_font, Arial, Helvetica, sans-serif;}
    <?php
    }

// Higher contrast mode changes
if (isset($high_contrast_mode) && $high_contrast_mode == true)
    {
    ?>
    body, html {
        background: white;
        color: black;
    }
    a:link, a:visited,
    #modal .RecordHeader h1, #modal .BasicsBox h1,
    .RecordPanel .RecordDownloadSpace .DownloadDBlend p, .RecordPanel .RecordDownload .DownloadDBlend td,
    .HorizontalWhiteNav a:link, .HorizontalWhiteNav a:visited, .HorizontalWhiteNav a:active,
    .BasicsBox .VerticalNav a:link, .BasicsBox .VerticalNav a:visited, .BasicsBox .VerticalNav a:active,
    .ListTitle a:link, .ListTitle a:visited, .ListTitle a:active,
    .search-icon, .search-icon:hover, .search-icon:active, .jstree-default-dark .jstree-anchor {
        color: black;
    }
    a:hover, a:active {
        text-decoration: underline !important;
        text-underline-position: under;
    }
    input[type="checkbox"], input[type="radio"] {
        transform: scale(1.5);
    }
    h1, h2, .Tab a, h2.CollapsibleSectionHead, h1.CollapsibleSectionHead {
        font-weight: 500;
    }
    table {
        border-collapse: collapse;
    }
    .Listview tr, .NavUnderline, .Question {
        border-bottom: 1px solid black;
    }
    .Listview tr:last-child {
        border-bottom: 0;
    }
    #SearchBox, #HomeSiteText.dashtext, .HomePanelIN, .PopupCategoryTree, #BrowseBar {
        background: black;
    }
    .SearchBarTab.SearchBarTabSelected {
        background: white;
        color: black;
        border-radius: 8px;
    }
    .TopInpageNav select, .update_result_order_button, select, .sp-replacer {
        box-shadow: none;
        border: 1px solid black;
    }
    .TopInpageNavLeft select, .TopInpageNavLeft select:focus, .comment_form_container,
    .Listview, .user_message_text, .CategoryBox {
        border: 1px solid black;
    }
    #Header {
        border-bottom: 1px solid black;
        background: white;
    }
    #Header li a {
        opacity: 1;
    }
    #Header .current {
        font-weight: bold;
    }
    #modal, .RecordPanel, .ResourcePanelSmall, .ResourcePanelLarge, div#Metadata div.Title, .TabBar {
        background-color: white;
    }
    .BasicsBox.SearchSticky {
        background: white;
        border-bottom: 1px solid black;
    }
    .QuestionSubmit.QuestionSticky {
        background: white;
        border-top: 1px solid black;
    }
    .Question.QuestionStickyRight {
        background: white;
        border-left: 1px solid black;
    }
    .BreadcrumbsBoxTheme {
        background-color: black;
    }
    .icondisplay {
        box-shadow: none;
    }
    .ResourcePanel {
        box-shadow: 0 0px 0px 1px black;
    }
    .ResourcePanel:hover {
        box-shadow: 0 0px 0px 3px black;
    }
    .ResourcePanelIcons a:hover {
        background: black;
        border-radius: 3px;
        color: white;
    }
    .thumbs-file-extension,
    .ResourceTypeIcon.fa-fw {
        background: white;
    }
    .RecordDownloadSpace {
        border: 1px solid black;
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    button, input[type=submit], input[type=button], .RecordPanel .RecordDownloadSpace .DownloadDBlend a,
    .UploadButton a, .uppy-StatusBar-actionBtn, .uppy-Dashboard-browse,
    .uppy-StatusBar.is-waiting .uppy-StatusBar-actionBtn--upload,
    .uppy-StatusBar.is-waiting .uppy-StatusBar-actionBtn--upload:hover, .uppy-DashboardContent-back,
    .uppy-DashboardContent-back:focus, .uppy-DashboardContent-addMore, .uppy-DashboardContent-addMore:focus,
    input:checked + .customFieldLabel, .keywordselected {
        background-color: #146cab;
    }
    button:hover, input[type=submit]:hover, input[type=button]:hover,
    .RecordPanel .RecordDownloadSpace .DownloadDBlend a:hover, .UploadButton a:hover {
        text-decoration: underline;
        text-underline-position: under;
    }
    .TabSelected a {
        color: black;
        border-left: 1px solid black;
        border-right: 1px solid black;
        border-top: 1px solid black;
    }
    .RecordPanel .item h3, .RecordPanel .itemNarrow h3, th {
        color: black;
        font-weight: 500;
    }
    .ListviewStyle thead, .ListviewTitleStyle {
        background: rgb(75 75 75);
    }
    .ListviewTitleStyle a, .ListviewTitleStyle a:hover {
        color: white;
    }
    input[type="text"], input[type="password"], input[type="number"], input[type="email"],
    textarea, select, .sp-replacer {
        border: 1px solid black;
        box-shadow: none;
    }
    .FormHelpInner {
        border: 1px solid #146cab;
    }
    .uppy-Dashboard-inner {
        border: 1px solid black;
        background-color: white;
    }
    #iconpicker-button {
        border: 1px solid black;
        box-shadow: unset;
    }
    select, .sp-replacer {
        background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMjIiIGhlaWdodD0iMTMiIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDIyIDEzIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAgLTQpIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxyZWN0IHk9IjIiIHdpZHRoPSIyMiIgaGVpZ2h0PSIxMSIvPjxwb2x5bGluZSB0cmFuc2Zvcm09InJvdGF0ZSg0NSA2LjAzNiA1LjAzNikiIHBvaW50cz0iOS41OTYgMS40NzUgOS41OTYgOC41OTYgMi40NzUgOC41OTYiIHN0cm9rZT0iIzAwMDAwNCIgc3Ryb2tlLXdpZHRoPSIyIi8+PC9nPjwvc3ZnPgo=);
    }
    .CollectBack {
        background: black;
        color: white;
    }
    .prevLink, .nextLink, .prevPageLink, .nextPageLink {
        padding: 2px 5px;
        border-radius: 3px;
    }
    .backtoresults .maxLink {
        margin-left: 20px;
        padding: 4px 4px 3px 4px;
        border-radius: 3px;
    }
    .backtoresults .closeLink {
        margin-left: 5px;
        padding: 2px 6px;
        border-radius: 3px;
    }
    .backtoresults .maxLink:hover, .backtoresults .closeLink:hover,
    .prevLink:hover, .nextLink:hover, .prevPageLink:hover, .nextPageLink:hover {
        background-color: black;
        color: white;
    }
    .NonMetadataProperties {
        border: 1px solid black;
        border-radius: 4px;
    }
    .StyledTabbedPanel {
        border: 1px solid black;
        padding-bottom: 8px;
    }
    .NonMetadataProperties + .TabbedPanel {
        margin-top: 8px;
        padding-bottom: 6px;
        border-radius: 4px;
        border: 1px solid black;
    }
    .lock_icon {
        min-width: unset;
    }
    .InfoTable {
        border: 1px solid black;
        border-collapse: separate;
        border-spacing: 0;
    }
    .InfoTable tr+tr>td {
        border-top: 1px solid black;
    }
    .InfoTable td+td {
        border-left:  1px solid black;
    }
    .CommentEntry {
        border: 1px solid black;
    }
    div.MessageBox {
        color: black;
        border: 1px solid black;
        background: white; 
    }
    .jstree-default-dark .jstree-hovered {
        color: white;
    }
    .jstree-default-dark .jstree-wholerow-hovered {
        background: black;
    }
    .jstree-default-dark .jstree-anchor>.jstree-icon {
        opacity: 1;
    }
    .ui-widget.ui-widget-content {
        border: 1px solid black;
    }
    .ui-dialog .ui-dialog-title, .ui-widget-content {
        color: black;
    }
    .delete-dialog .ui-dialog-titlebar, .ui-dialog-titlebar {
        background: white;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    #modal_dialog {
        border-top: 1px solid black;
        border-radius: 0;
    }
    .ui-dialog {
        padding: 0;
    }
    .ui-widget.ui-button {
        background: white;
        border: 1px solid black;
        color: black;
        font-weight: 500;
    }
    .ui-widget.ui-button:hover {
        background: black;
        color: white;
        text-decoration: underline;
    }
    /* CKEditor */
    .ckeditorEdit .cke_chrome {
        border: 1px solid black;
    }
    .ckeditorEdit .cke_top {
        border-bottom: 1px solid black;
        background: white;
    }
    .ckeditorEdit .cke_bottom {
        border-top: 1px solid black;
        background: white;
    }
    
    <?php
    }

// Simple Search pills using jQuery tag editor
if ($simple_search_pills_view) { ?>
    .search-icon, .search-icon:hover, .search-icon:active {
        background-color: #ffffff00;
        margin-top: -36px;
        margin-left: 221px;
    }
<?php
}