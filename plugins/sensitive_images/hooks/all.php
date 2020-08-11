<?php

function HookSensitive_imagesAllAdditionaljoins()
    {
    global $sensitive_images_field;
    if ($sensitive_images_field>0)
        {
        // If plugin is configured, add the sensitive images field to the array of join fields so it's returned in the results.
        return array($sensitive_images_field);
        }
    }


function HookSensitive_imagesSearchResourcethumbtop()
    {
    global $sensitive_images_field,$result,$n;
    if ($sensitive_images_field>0)
        {
        $sensitive=$result[$n]["field" . $sensitive_images_field];
        if (strlen($sensitive)>0)
            {
            echo "sensitive";
            }
        }
    }