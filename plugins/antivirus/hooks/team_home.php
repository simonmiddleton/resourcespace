<?php

function HookAntivirusTeam_homeCustomteamfunctionadmin()
    {
    global $lang,$baseurl_short,$antivirus_path;
    if (!isset($antivirus_path) || trim($antivirus_path) == ''){
        $antivirusalerthtml  = '<li><a href="' . $baseurl_short . '/pages/help.php?page=plugins/antivirus"';
        if (getval("modal","")!="")
            {
            # If a modal, open in the same modal
            $antivirusalerthtml .='onClick="return ModalLoad(this,true);"';
            }
        else
            {
            $antivirusalerthtml .='onClick="return CentralSpaceLoad(this,true);"';
            }
        
        $antivirusalerthtml .= '>';
        $antivirusalerthtml .= '<i aria-hidden="true" class="fa fa-fw fa-exclamation-triangle"></i>';
        $antivirusalerthtml .= '<br />' . $lang['antivirus_av_not_setup_error'] . '</a></li>';
		echo $antivirusalerthtml;}
    }
    