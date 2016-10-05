<?php
include dirname(__FILE__) . '/../../../include/db.php';
include_once dirname(__FILE__) . '/../../../include/general.php';
include dirname(__FILE__) . '/../../../include/authenticate.php';
include_once dirname(__FILE__) . '/../../../include/node_functions.php';

$field    = getvalescaped('field', '');
$keyword  = getvalescaped('term', '');
$readonly = ('' != getval('readonly', '') ? true : false);

$fielddata = get_resource_type_field($field);
node_field_options_override($fielddata);

// Return matches
$first      = true;
$exactmatch = false;
$results    = array();

foreach($fielddata['nodes'] as $node)
    {
    $trans = i18n_get_translated($node['name']);

    if('' != $trans && substr(strtolower($trans), 0, strlen($keyword)) == strtolower($keyword))
        {
        if(strtolower($trans) == strtolower($keyword))
            {
            $exactmatch = true;
            }

        $results[] = array(
                'label' => $trans,
                'value' => $node['ref']
            );
        }
    }

if(!$exactmatch && !$readonly)
    {
    $results[] = array(
            'label' => "{$lang['createnewentryfor']} {$keyword}",
            'value' => "{$lang['createnewentryfor']} {$keyword}"
        );
    }
elseif($readonly && empty($results))
    {
    $results[] = array(
            'label' => "{$lang['noentryexists']} {$keyword}",
            'value' => "{$lang['noentryexists']} {$keyword}"
        );
    }

// We return an array of objects with label and value properties: [ { label: "Node ID 1 - option name", value: "101" }, ... ]
// This will later be used by jQuery autocomplete
echo json_encode($results);
exit();