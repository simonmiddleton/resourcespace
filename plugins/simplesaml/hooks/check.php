<?php 
function HooksimplesamlCheckAddinstallationcheck()
    {
    display_extension_status('openssl');
    display_extension_status('ldap');
    }
