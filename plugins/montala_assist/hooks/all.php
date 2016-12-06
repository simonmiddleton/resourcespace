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
        jQuery('#montala_assist').load('https://resourcespace.montala.com/montala_assist_plugin.php?baseurl=<?php echo base64_encode($baseurl) ?>',function ()
            {
            jQuery('#montala_assist').fadeIn("fast");
            });
        });
    </script>
    <div id="montala_assist" style="display:none;"></div>
	<?php
	}

