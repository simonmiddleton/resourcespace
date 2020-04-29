<?php

function HookLicensemanagerAllExport_add_tables()
    {
    return array("license"=>array("scramble"=>array("holder","license_usage","description")));
    return array("resource_license"=>array("scramble"=>array()));
    }

function HookLicensemanagerAllRender_actions_add_collection_option($top_actions,array $options)
    {
    // Add the options to link a license and unlike the license
    global $search,$lang,$k,$baseurl_short;

    if($k != '' || !checkperm("a"))
        {
        return array();
        }

    $data_attr_url = generateURL(
        $baseurl_short . "plugins/licensemanager/pages/batch.php",
        array(
            'collection'   => $search,
            'unlink' => 'true'
        )
    );

    $option = array(
        'value'     => 'license_batch',
        'label'     => $lang['unlinklicense'],
        'data_attr' => array(
            'url' => $data_attr_url,
        ),
        'category' => ACTIONGROUP_ADVANCED
    );


    array_push($options, $option);

    $data_attr_url = generateURL(
        $baseurl_short . "plugins/licensemanager/pages/batch.php",
        array(
            'collection'   => $search
        )
    );

    $option = array(
        'value'     => 'license_batch',
        'label'     => $lang['linklicense'],
        'data_attr' => array(
            'url' => $data_attr_url,
        ),
        'category' => ACTIONGROUP_ADVANCED
    );

    array_push($options, $option);



    return $options;
    }
