<?php

function HookApi_webhooksEditExtra_edit_buttons()
    {
    // Add configured buttons as appropriate.
    global $api_webhooks_urls;

    ?>
    <input type="hidden" name="api_webhooks_submitted_button" id="api_webhooks_submitted_button" value="" />
    <?php
    $counter=0;
    foreach ($api_webhooks_urls as $url)
        {
        ?>
        <input  name="savex"
        class="editsave APIWebhooksEditButton"
        type="submit"
        value="&nbsp;&nbsp;&#8634;&nbsp;<?php echo i18n_get_translated($url["buttontext"]); ?>&nbsp;&nbsp;"
        onclick="document.getElementById('api_webhooks_submitted_button').value=<?php echo $counter ?>"
        />
        <?php
        $counter++;
        }
    }


function HookApi_webhooksEditRedirectaftersave()
    {
    // Process the saved form and access the remote script.
    global $api_webhooks_urls,$ref;

    // Fetch appropriate button
    $button_index=getval("api_webhooks_submitted_button","");if ($button_index=="") {return false;} // No button pressed? Redirect to view page as normal.
    $button=$api_webhooks_urls[$button_index];
    
    // Perform API call.
    $url=$button["url"] . $ref;
    $options = array
            (
            'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'ignore_errors' => true
            )
        );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    // Handle any errors.
    if (strpos($http_response_header[0],"200 OK")===false)
        {
        ?>
        <script>alert('Error - POST to <?php echo $url ?> returned <?php echo $http_response_header[0] ?>');</script>
        <?php
        }

    return true;
    }