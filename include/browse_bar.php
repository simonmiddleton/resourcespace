<?php
$barclass = getval("rsbrowse","") != "show" ? "BrowseBarHidden" : "BrowseBarVisible";

$bb_html  = '<div id="BrowseBar" class=' . $barclass . ' onclick="ToggleBrowseBar();" class="BrowseBar">';
$bb_html .= '<span class="BrowseBarText">' . $lang['browse_bar_text'] . '</span>';
$bb_html .= '</div>\n';
   
echo $bb_html;
