<?php

/**
 * Replace download links with code to support importing resources into Adobe CC
 */	
function HookAdobe_linkViewBefore_footer_always()
    {
    global $lang;
    if(!isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] !== "InDesign-DAMConnect")
        {
        return false;
        }
    ?>
    <script>

    jQuery(document).ready(function(){
        
        window.addEventListener("message", function(event) {
            if(event.origin == 'file://') {
                var data = event.data;
                extractJSON(data,' ');
            }
        });

    });

    function extractJSON(obj, indent) {
        for (const i in obj) {
            if (Array.isArray(obj[i]) || typeof obj[i] === 'object') {
                console.log(indent + i + ' is array or object');
                extractJSON(obj[i], indent + ' > ' + i + ' > ');
            } else {
                var content = jQuery('.js-textarea').val();
                if(content != '')
                    content = content + "\n";
                jQuery('.js-textarea').val(content + indent + i + ': ' + obj[i]);
            }
        }
    }

    function sendDownloadDocumentMessageToCCApp(objData) {
        var objMessage = {};
        objMessage.items = [];
        objMessage.items.push({
                'assetId' : objData.asset_id,
                'versionId' : objData.version_id,
                'documentName' : objData.document_name,
                'url' : objData.url	
        });
        
        objMessage.count = objMessage.items.length;
        objMessage.eventType = "downloadDocument"

        window.postMessage(objMessage, '*');
        styledalert('<?php echo htmlspecialchars($lang["adobe_link_title"]); ?>','<?php echo htmlspecialchars($lang["adobe_link_import_successful"]); ?>');
    }

        function sendDownloadAssetMessageToCCApp(objData) {

        var objMessage = {};

        objMessage.items = [];
        objMessage.items.push({
                'assetId' : objData.asset_id,
                'versionId' : objData.version_id,
                'assetName' : objData.asset_name,
                'url' : objData.url,
                'placeOnExisting' : objData.placeOnExisting
        });
        
        objMessage.count = objMessage.items.length;
        objMessage.eventType = "downloadAsset"

        window.postMessage(objMessage, '*');
        styledalert('<?php echo htmlspecialchars($lang["adobe_link_title"]); ?>','<?php echo htmlspecialchars($lang["adobe_link_copy_successful"]); ?>');
    }
    
    function sendResizePanelMessageToCCApp(objData) {
        var objMessage = {};

        objMessage.items = [];
        objMessage.items.push({
                'width' : objData.width,
                'height' : objData.height						
        });
        
        objMessage.count = objMessage.items.length;
        objMessage.eventType = "resizePanel"

        window.postMessage(objMessage, '*');
    }

    function AdobeLinkAssetDownload(link){
        var objData = {};
        objData.url = jQuery(link).attr('data-attribute-path');
        objData.asset_id = jQuery(link).attr('data-attribute-ref');
        objData.version_id = jQuery(link).attr('data-attribute-ref');
        objData.asset_name = jQuery(link).attr('data-attribute-name');
        objData.placeOnExisting = true;
        sendDownloadAssetMessageToCCApp(objData);
    }

    function AdobeLinkDocumentDownload(link){
        var objData = {};
        objData.url = jQuery(link).attr('data-attribute-path');
        objData.asset_id = jQuery(link).attr('data-attribute-ref');
        objData.version_id = jQuery(link).attr('data-attribute-ref');
        objData.document_name = jQuery(link).attr('data-attribute-name');
        objData.placeOnExisting = true;
        sendDownloadDocumentMessageToCCApp(objData);
    }
    

    jQuery('#resizePanel').click(function(){
        var objData = {};
        var size = jQuery(this).parents('.js-group').find('input').val();
        objData.width = size.split(",")[0];
        objData.height = size.split(",")[1];
        sendResizePanelMessageToCCApp(objData);
    });           
        
    </script>
    <?php
    }
    
function HookAdobe_linkViewDownloadbuttonreplace()
	{
    global $userref, $baseurl ,$baseurl_short, $urlparams, $ref, $resource, $size_info_array, $lang, $adobe_link_asset_extensions, $adobe_link_document_extensions; 

    # Adds a special link to the download button.
    $adb_ext = $size_info_array["id"] == "" ? $resource['file_extension'] : $size_info_array["extension"];
    
    if(!isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] !== "InDesign-DAMConnect"
        ||
        (!in_array(strtolower($adb_ext),$adobe_link_asset_extensions) && !in_array(strtolower($adb_ext),$adobe_link_document_extensions))
        )
        {
        return false;
        }

    $adobefilename = get_download_filename($ref,'',-1,$adb_ext);
    $extraparams = array();
    $extraparams["noattach"] = "true";
    $extraparams["size"] = $size_info_array["id"];
    $extraparams["ext"] = $adb_ext;
    
    // Add a temporary key for this resource download that will be checked by hook in download.php
    $adb_tmp_key = adobe_link_genkey($userref,$ref);
    $extraparams["adb_key"] = $userref . ":" . $adb_tmp_key;
    $path = generateURL($baseurl . "/pages/download.php", $urlparams, $extraparams);


    $adobelink = "<a href='" . $path . "'";
    $adobelink .= "data-attribute-path='" . htmlspecialchars($path) . "' ";
    $adobelink .= "data-attribute-ref='" . htmlspecialchars($ref) . "' ";
    $adobelink .= "data-attribute-name='" . htmlspecialchars($adobefilename) . "' ";
    
    if(in_array(strtolower($adb_ext),$adobe_link_asset_extensions))
        {
        $adobelink .= "onclick='AdobeLinkAssetDownload(this);return false;' ";
        }
    else
        {
        $adobelink .= "onclick='AdobeLinkDocumentDownload(this);return false;' ";
        }

    $adobelink .= ">" . $lang["adobe_link_import"]. "</a>";
    // Show the link
    echo $adobelink;
    return true;
	}
	
function HookAdobe_linkViewOrigdownloadlink()
	{
	# Adds a special link to the download button.
	global $userref, $baseurl, $usergroup, $lang, $ref, $access, $resource, $k, $size_info, $baseurl_short, $urlparams, $path, $direct_download, $alternative;
	global $adobe_link_document_extensions, $adobe_link_asset_extensions, $lang;
    
    if(!isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] !== "InDesign-DAMConnect"
        ||
        (!in_array(strtolower($resource['file_extension']),$adobe_link_asset_extensions) && !in_array(strtolower($resource['file_extension']),$adobe_link_document_extensions))
        )
        {
        return false;
        }

    $extraparams = array();
    $extraparams["noattach"] = "true";
    $extraparams["ext"] = $resource['file_extension'];
    $adb_tmp_key = adobe_link_genkey($userref,$ref);
    $extraparams["adb_key"] = $userref . ":" . $adb_tmp_key;
    
    $path = generateURL($baseurl . "/pages/download.php", $urlparams, $extraparams);
    $adobefilename = get_download_filename($ref,'',-1,$resource['file_extension']);    
    
    $adobelink = "<a href='" . $path . "'";
    $adobelink .= "data-attribute-path='" . htmlspecialchars($path) . "' ";
    $adobelink .= "data-attribute-ref='" . htmlspecialchars($ref) . "' ";
    $adobelink .= "data-attribute-name='" . htmlspecialchars($adobefilename) . "' ";

    if(in_array(strtolower($resource['file_extension']),$adobe_link_asset_extensions))
        {
        $adobelink .= "onclick='AdobeLinkAssetDownload(this);return false;' ";
        }
    else
        {
        $adobelink .= "onclick='AdobeLinkDocumentDownload(this);return false;' ";
        }

    $adobelink .= ">" . $lang["adobe_link_import"]. "</a>";

    // Show the link
    ?>
    <tr class="DownloadDBlend">
    <td class="DownloadFileName"><h2><?php echo (isset($original_download_name)) ? str_replace_formatted_placeholder("%extension", $resource["file_extension"], $original_download_name, true) : str_replace_formatted_placeholder("%extension", $resource["file_extension"], $lang["originalfileoftype"]); ?></h2></td>
    <td class="DownloadFileSize"><?php echo formatfilesize(filesize_unlimited($path))?></td>
    <td class="DownloadButton">
    <?php echo $adobelink; 
    ?>
    </td>
    </tr>
    <?php
    return true;
	}

