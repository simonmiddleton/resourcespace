<?php
$barshow = getval("browse_show","") == "show";
exit($barshow ? "TRUE" : "FALSE");
$bb_html  = '<div id="BrowseBar" onclick="ToggleBrowseBar();" class="BrowseBar"' . ($barshow ?  "" : "style:display:none;") .  '>';
$bb_html .= '<span class="BrowseBarText">' . $lang['browse_bar_text'] . '</span>';
$bb_html .= '</div>\n';
   
echo $bb_html;
