<?php 
function HooksimplesamlCheckAddinstallationcheck()
{
    ?>
    <tr>
        <td class="BorderBottom"; colspan='3'>
            <b>SimpleSAML</b>
        </td>
    </tr>
    <?php
    display_extension_status('openssl');
    display_extension_status('ldap');

    if (isset($GLOBALS["simplesamlconfig"]["metadata"]) && $GLOBALS['simplesaml_check_idp_cert_expiry']) {
        // Check expiry date of IdP certificates
        // Only possible to check if using ResourceSpace stored SAML config
        ?>
        <tr>
            <td colspan='3'>
                <b><?php echo escape($GLOBALS["lang"]['simplesaml_idp_certs']); ?></b>
            </td>
        </tr>
        <?php
        $idpindex = 1; // Some systems have multiple IdPs
        foreach ($GLOBALS["simplesamlconfig"]["metadata"] as $idpid => $idpdata) {
            $idpname = $idpid; // IdP may not have a friendly readable name configured
            $latestexpiry = get_saml_metadata_expiry($idpid);
            if (isset($idpdata["name"])) {
                if (is_string($idpdata["name"])) {
                    $idpfriendlyname = $idpdata["name"];
                } else {
                    $idpfriendlyname = (string) ($idpdata["name"][$GLOBALS['language']] ?? reset($idpdata["name"]));
                }
                $idpname .= " (" . $idpfriendlyname . ")";
            }
            $placeholders = ["%idpname", "%expiretime"];
            $replace = [$idpname, $latestexpiry];
            // show status
            if ($latestexpiry < date("Y-m-d H:i")) {
                $status  = 'FAIL';
                $info = str_replace($placeholders,  $replace, $GLOBALS['lang']['simplesaml_idp_cert_expired']);
            } elseif ($latestexpiry < date("Y-m-d H:i", time()+60*60*24*7)) {
                $status  = 'FAIL';
                $info = str_replace($placeholders,  $replace, $GLOBALS['lang']['simplesaml_idp_cert_expiring']);
            } else {
                $status  = 'OK';
                $info = str_replace($placeholders,  $replace, $GLOBALS['lang']['simplesaml_idp_cert_expires']);
            }
            ?>
            <tr>
                <td><?php echo escape($idpname); ?></td>
                <td><?php echo escape($info); ?></td>
                <td><b><?php echo $status; ?></b></td>
            </tr>
            <?php
        $idpindex++;
        }
    }

}
