<?php
include dirname(__FILE__) . '/../../../include/db.php';
$k = getvalescaped('k','');
$upload_collection = getval('upload_share_active',''); 
if ($k=="" || (!check_access_key_collection($upload_collection,$k)))
    {
    include dirname(__FILE__) . '/../../../include/authenticate.php';
    }
$field    = getvalescaped('field', '');
$keyword  = getvalescaped('term', '');
$readonly = ('' != getval('readonly', '') ? true : false);

$fielddata = get_resource_type_field($field);
node_field_options_override($fielddata);

// Return matches
$first      = true;
$exactmatch = false;
$results    = array();

if(!is_array($fielddata))
    {
    echo json_encode($results);
    exit();
    }

foreach($fielddata['nodes'] as $node)
    {
    $trans = i18n_get_translated($node['name']);
    
    if($dynamic_keyword_suggest_contains)
		{
		if('' != $trans && (!isset($dynamic_keyword_suggest_contains_characters) || $dynamic_keyword_suggest_contains_characters <= strlen($keyword)) && strpos(strtolower($trans), strtolower($keyword)) !== false)
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
	else
		{
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
    }

$keyword=stripslashes($keyword);

$fielderror = false;
if(!$exactmatch && !$readonly)
    {
    
    # Ensure regexp filter is honoured if one is present
    if (trim(strlen($fielddata["regexp_filter"]))>=1)
        {
        if(preg_match("#^" . $fielddata["regexp_filter"] . "$#",$keyword,$matches) <= 0)
            {
            $fielderror = true;
            }
        }

    if(!$fielderror)
        {
        $results[] = array(
            'label' => "{$lang['createnewentryfor']} {$keyword}",
            'value' => "{$lang['createnewentryfor']} {$keyword}"
        );
        }
    else
        {
        $results[] = array(
            'label' => "{$lang['keywordfailedregexfilter']} {$keyword}",
            'value' => "{$lang['keywordfailedregexfilter']} {$keyword}"
        );
        }

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