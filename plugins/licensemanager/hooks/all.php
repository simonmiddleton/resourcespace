<?php

function HookLicensemanagerAllExport_add_tables()
    {
    return array("license"=>array("scramble"=>array("holder","license_usage","description")));
    return array("resource_license"=>array("scramble"=>array()));
    }

function HookLicensemanagerAllRender_actions_add_collection_option($top_actions,array $options, array $collection_data)
    {
    // Add the options to link a license and unlink the license
    global $search,$lang,$k,$baseurl_short;

    if($k != '' || !checkperm("a"))
        {
        return false;
        }

    $collection = (isset($collection_data["ref"]) ? $collection_data["ref"] : null);

    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

    $data_attr_url = generateURL(
        $baseurl_short . "plugins/licensemanager/pages/batch.php",
        array(
            'collection' => $collection,
            'unlink'     => 'true',
            'search'     => getval('search', $search),
            'order_by'   => getval('order_by',''),
            'offset'     => getval('offset',0),
            'restypes'   => getval('restypes',''),
            'archive'    => getval('archive','')
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
            'collection' => $collection,
            'search'     => getval('search', $search),
            'order_by'   => getval('order_by',''),
            'offset'     => getval('offset',0),
            'restypes'   => getval('restypes',''),
            'archive'    => getval('archive','')
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
