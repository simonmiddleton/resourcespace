<?php
include_once dirname(__FILE__) . "/../include/consent_functions.php";

function HookConsentmanagerAllExport_add_tables()
    {
    return array("consent"=>array("scramble"=>array("name"=>"mix_text","email"=>"mix_email","telephone"=>"mix_text","consent_usage"=>"mix_text","expires"=>"mix_date")));
    }

function HookConsentmanagerAllRender_actions_add_collection_option($top_actions,array $options, array $collection_data)
    {
    // Add the options to link/unlink consent
    global $search,$lang,$k,$baseurl_short;

    // Make sure this check takes place before $GLOBALS["hook_return_value"] can be unset by subsequent calls to hook()
    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
    {
    // @see hook() for an explanation about the hook_return_value global
    $options = $GLOBALS["hook_return_value"];
    }

    if($k != '' || !(checkperm("a") || checkperm("cm")))
        {
        return $options;
        }

    $collection = (isset($collection_data["ref"]) ? $collection_data["ref"] : null);

    $data_attr_url = generateURL(
        $baseurl_short . "plugins/consentmanager/pages/batch.php",
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
        'value'     => 'consent_batch',
        'label'     => $lang['unlinkconsent'],
        'data_attr' => array(
            'url' => $data_attr_url,
        ),
        'category' => ACTIONGROUP_ADVANCED
    );


    array_push($options, $option);

    $data_attr_url = generateURL(
        $baseurl_short . "plugins/consentmanager/pages/batch.php",
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
        'value'     => 'consent_batch',
        'label'     => $lang['linkconsent'],
        'data_attr' => array(
            'url' => $data_attr_url,
        ),
        'category' => ACTIONGROUP_ADVANCED
    );

    array_push($options, $option);



    return $options;
    }

function HookConsentmanagerAllTopnavlinksafterhome()
    {
    global $baseurl,$lang;
    if (!checkperm("t") && checkperm("cm"))
        {
        ?><li class="HeaderLink"><a href="<?php echo $baseurl ?>/plugins/consentmanager/pages/list.php" onClick="CentralSpaceLoad(this,true);return false;"><?php echo '<i aria-hidden="true" class="fa fa-fw fa-user-check"></i>&nbsp;' . htmlspecialchars($lang["manageconsent"]); ?></a></li>
        <?php
        }
    }
