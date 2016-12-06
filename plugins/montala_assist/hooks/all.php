<?php
function HookMontala_assistAllSearchbarbottomtoolbar()
        {
    # Load HTML from the ResourceSpace site, passing the base URL. The base URL allows Montala to feed appropriate options based
    # on whether the installation is a registered trial system, user installation, etc.
    global $baseurl;
        ?>
    <script>
    jQuery(document).ready(function()
        {
        if (jQuery('montala_assist').children().length==0) {jQuery('#montala_assist').load('https://resourcespace.montala.com/montala_assist_plugin.php?baseurl=<?php echo base64_encode($baseurl) ?>');}
        });
    </script>
    <div id="montala_assist"></div>
        <?php
        }
