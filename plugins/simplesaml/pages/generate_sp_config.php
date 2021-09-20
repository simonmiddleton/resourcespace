
<?php
#
# simplesaml setup page
#

include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include_once dirname(__FILE__) . '/../include/simplesaml_functions.php';

//exit(($simplesaml_rsconfig ? "TRUE" : "FALSE"));
$plugin_name = 'simplesaml';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}

// Array of required SAML config settings
$saml_settings = array(
    "technicalcontact_name" => "",
    "technicalcontact_email" => "",
    "auth.adminpassword" => "",
    );

$certreqdetails = array(
    "countryName",
    "stateOrProvinceName",
    "localityName",
    "organizationName",
    "organizationalUnitName",
    "commonName",
    "emailAddress"
    );

// set up array to store current and new config
$spconfig = array();

// Get current values from config
if(isset($simplesamlconfig["config"]))
    {
    foreach($simplesamlconfig["config"] as $configopt=>$configvalue)
        {
        //$spconfig[$configopt] = "\$simplesamlconfig[\"config\"][\"" . $configopt . "\"] = '" . htmlspecialchars($configvalue) . "';";
        $saml_settings[$configopt] = $configopt;
        }
    }

// Get SP certificate config
$curcertpath    = "";
$curkeypath     = "";
$curidp         = "";
if(isset($simplesamlconfig['authsources']["resourcespace-sp"]))
    {
    $curcertpath    = isset($simplesamlconfig['authsources']["resourcespace-sp"]["certificate"]) ? $simplesamlconfig['authsources']["resourcespace-sp"]["certificate"] : "";
    $curkeypath     = isset($simplesamlconfig['authsources']["resourcespace-sp"]["privatekey"]) ? $simplesamlconfig['authsources']["resourcespace-sp"]["privatekey"] : "";
    $curidp         = $simplesamlconfig['authsources']["resourcespace-sp"]["idp"];
    }
$certpath   = getval("cert_path",$curcertpath);
$keypath    = getval("key_path",$curkeypath);
$samlidp    = getval("samlidp",$curidp);
$error_text = "";

// Set up array to render all values
foreach($saml_settings as $saml_setting => $configvalue)
    {   
    $curvalue = isset($simplesamlconfig["config"][$saml_setting]) ? $simplesamlconfig["config"][$saml_setting] : "";
    $samlvalue = getval($saml_setting, $curvalue);

    debug("saml_generate_config " . $saml_setting . "="  .  print_r($samlvalue,true));
    if($saml_setting == "auth.adminpassword" && trim($samlvalue) == "") 
        {
        $samlvalue = generateSecureKey(12);
        }
    $simplesamlconfig["config"][$saml_setting] = $samlvalue;    
    
    if(
        (isset($simplesaml_config_defaults[$saml_setting]) && $samlvalue == $simplesaml_config_defaults[$saml_setting])
        || $saml_setting == "metadatadir"
        || is_array($samlvalue)
        )
        {
        // Don't need to add defaults or metadatadir to config
        continue;
        }
    $spconfig[$saml_setting] = "\$simplesamlconfig[\"config\"][\"" . $saml_setting . "\"] = '" . htmlspecialchars($samlvalue) . "';";
    }

if(getval('sp_submit', '') !== '' && enforcePostRequest(false))
    {
    // set up config and format it for admin user to copy into ResourceSpace config file
    if($certpath == "" || $keypath=="")
        {
        foreach($certreqdetails as $certreqdetail)
            {
            $certval =  getval($certreqdetail,"");
            if(trim($certval) == "" || ($certreqdetail == "countryName" && strlen($certval) !== 2))
                {
                $error_text .= $lang['simplesaml_sp_cert_invalid'] . " - '" . $certreqdetail . "'<br/>";
                }
            $dn[$certreqdetail] = $certval;
            }
        if($error_text=="")
            {
            $certinfo = simplesaml_generate_keypair($dn);
            if(is_array($certinfo))
                {
                $certpath = $certinfo["certificate"];
                $keypath = $certinfo["privatekey"];
                }
            else
                {
                $error_text = $lang['simplesaml_sp_cert_gen_error'];
                }
            }
        }
    $spconfigtext = implode("\n",$spconfig);

    // Set up metadata
    // Code below copied directly from SimpleSAMLphp metadata-converter.php
    $metadataoutput = "";
    $metadata_xml = trim(getval("metadata_xml",""));

    if($metadata_xml != "")
        {
        require_once(simplesaml_get_lib_path() . '/lib/_autoload.php');
        \SimpleSAML\Utils\XML::checkSAMLMessage($metadata_xml, 'saml-meta');
        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsString($metadata_xml);

        // get all metadata for the entities
        foreach ($entities as &$entity)
            {
            $entity = [
                'shib13-sp-remote'  => $entity->getMetadata1xSP(),
                'shib13-idp-remote' => $entity->getMetadata1xIdP(),
                'saml20-sp-remote'  => $entity->getMetadata20SP(),
                'saml20-idp-remote' => $entity->getMetadata20IdP(),
            ];
            }

        // transpose from $entities[entityid][type] to $output[type][entityid]
        $output = \SimpleSAML\Utils\Arrays::transpose($entities);

        // merge all metadata of each type to a single string which should be added to the corresponding file
        foreach ($output as $type => &$entities)
            {
            $text = '';
            foreach ($entities as $entityId => $entityMetadata)
                {
                if ($entityMetadata === null)
                    {
                    continue;
                    }

                // remove the entityDescriptor element because it is unused, and only makes the output harder to read
                unset($entityMetadata['entityDescriptor']);

                $text .= '$metadata[' . var_export($entityId, true) . '] = ' .
                    var_export($entityMetadata, true) . ";\n";
                }
            }
        $metadataoutput = str_replace("\$metadata","\$simplesamlconfig[\"metadata\"]",$text);
        $samlidp = $entityId;
        }


    // Set up authsources config
    $spauthsourcestext = "\$simplesamlconfig['authsources'] = 
        [
        'admin' => ['core:AdminPassword'],
        'resourcespace-sp' => [
        'saml:SP',
        'privatekey' => '" . htmlspecialchars($keypath) . "',
        'certificate' => '" . htmlspecialchars($certpath) . "',
        'entityID' => null,
        'idp' => '" . htmlspecialchars($samlidp) . "',
        'discoURL' => null,
        ]
    ];";

    $spconfigtext = $spauthsourcestext . "\n\n" . $spconfigtext . "\n\n" . $metadataoutput;
    $showoutput = true;
    }


$links_trail = array(
    array(
        'title' => $lang["systemsetup"],
        'href'  => "{$baseurl_short}pages/admin/admin_home.php"
    ),
    array(
        'title' => $lang["pluginmanager"],
        'href'  => "{$baseurl_short}pages/team/team_plugins.php"
    ),
    array(
        'title' => $lang['simplesaml_configuration'],
        'href'  => "{$baseurl_short}plugins/simplesaml/pages/setup.php"
    ),
    array(
        'title' => $lang["simplesaml_sp_config"],
        'help'  => 'plugins/simplesaml'
    ),
);
include '../../../include/header.php';


?>
<div class="BasicsBox"> 
<?php 
renderBreadcrumbs($links_trail);

if ($error_text != "") { ?><div class="PageInformal"><?php echo $error_text?></div><?php }
?>
<form id="simplesamlSPForm" method="post" class="FormWide" action="<?php echo $baseurl_short . "plugins/simplesaml/pages/generate_sp_config.php" ?>">
        <input name="sp_submit" id="sp_submit" type="hidden" value="" ></input>
        <?php generateFormToken("simplesaml_sp_config");

        // Output the config text that can be copied into RS
        if(isset($spconfigtext) && isset($showoutput) && $showoutput)
            {
            ?>
            <div class="Question">
                <label for="rs_saml_config"><?php echo $lang["simplesaml_saml_config_output"]; ?></label>
                <textarea id="rs_saml_config" rows="20" class="stdwidth" name="rs_saml_config"><?php echo trim(htmlspecialchars($spconfigtext));?>
                </textarea>
                <div class="clearerleft"></div>
            </div>
            <?php
            }

        foreach($simplesamlconfig["config"] as $saml_setting => $samlvalue)
            {
            if($saml_setting == "metadatadir" || is_array($samlvalue))
                {
                continue;
                }
            render_text_question(isset($lang["simplesaml_sp_" . $saml_setting]) ? $lang["simplesaml_sp_" . $saml_setting] : $saml_setting,$saml_setting,'',false,'class="stdwidth"',$samlvalue);
            }
            
        render_text_question($lang['simplesaml_sp_cert_path'],"cert_path",'',false,'class="stdwidth certinput"',$certpath);
        render_text_question($lang['simplesaml_sp_key_path'],"key_path",'',false,'class="stdwidth certinput"',$keypath);
        render_text_question($lang['simplesaml_sp_idp'],"samlidp",'',false,'class="stdwidth"',$samlidp);
        
        ?>
        <div id="certificate_info_questions" <?php if($certpath != "" && $keypath != ""){echo "style='display:none;'";}?>>
            <div class="Question" >
                <br><h2><?php echo $lang['simplesaml_sp_cert_info'] ?></h2>
                    <div class="clearerleft"></div>
                </div>
                <?php
                foreach($certreqdetails as $certreqdetail)
                    {
                    render_text_question($lang["simplesaml_sp_cert_" . strtolower($certreqdetail)] . " *",$certreqdetail,'',false,'class="stdwidth"',htmlspecialchars(getval($certreqdetail,"")));    
                    }?>
        </div>
        <div class="Question">
        <br><h2><?php echo $lang['simplesaml_idp_section'] ?></h2>
            <div class="clearerleft"></div>
        </div>
        <div class="Question">
            <label for="metadata_xml"><?php echo $lang["simplesaml_idp_metadata_xml"]; ?></label>
            <textarea id="metadata_xml" rows="20" class="stdwidth" name="metadata_xml"></textarea>
            <div class="clearerleft"></div>
        </div>

        <div class="QuestionSubmit">
            <label for="buttons"></label>
            <input name="sp_submit" type="submit" value="<?php echo $lang["simplesaml_sp_generate_config"]; ?>" onclick="jQuery('#sp_submit').val('true');return CentralSpacePost(this.form,true);">
        </div>
</form>

<script>
    jQuery(".certinput").change(function(){
        if(jQuery('#cert_path_input').val() == "" || jQuery('#key_path_input').val() == "")
            {
            jQuery('#certificate_info_questions').slideDown();
            }
        else
            {
            jQuery('#certificate_info_questions').slideUp();
            }
        });
</script>


</div><!-- End of BasicsBox -->

<?php


include '../../../include/footer.php';