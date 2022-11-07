<?php

function HookApi_webhooksEditExtra_edit_buttons()
    {
    // Add configured buttons as appropriate.
    global $api_webhooks_urls;

    foreach ($api_webhooks_urls as $url)
        {
        ?>
        <input  name="savex"
        class="editsave APIWebhooksEditButton"
        type="submit"
        value="&nbsp;&nbsp;&#8634;&nbsp;<?php echo $url["buttontext"]; ?>&nbsp;&nbsp;" />
        <?php
        }
    }


function HookApi_webhooksEditRedirectaftersave()
    {
    // Process the saved form and access the remote script.
    echo "XXX" . getval("savex","");
    return true;
    }