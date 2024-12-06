<?php
include dirname(__DIR__, 3) . '/include/boot.php';
$k = getval('k','');
$upload_collection = getval('upload_share_active',''); 
if ($k=="" || (!check_access_key_collection($upload_collection,$k))) {
    include dirname(__DIR__, 3) . '/include/authenticate.php';
}
$field    = getval('field', '');
$keyword  = getval('term', '');
$readonly = ('' != getval('readonly', '') ? true : false);
if (checkperm("bdk" . $field)) {
    $readonly =true;
}

$fielddata = get_resource_type_field($field);
if (!$fielddata
    || !metadata_field_view_access($field)
    || (!$readonly && !metadata_field_edit_access($field))
) {
    http_response_code(403);
    exit(escape($lang["error-permissiondenied"]));
}
$nodes = get_nodes($field);

// Return matches
$first      = true;
$exactmatch = false;
$results    = array();
$match_is_deactivated = false;

// Set $keywords_remove_diacritics so as to only add versions with diacritics to return array if none are in the submitted string
$keywords_remove_diacritics = mb_strlen($keyword) === strlen($keyword);
$keyword = normalize_keyword($keyword);

if (!is_array($fielddata)) {
    echo json_encode($results);
    exit();
}

foreach ($nodes as $node) {
    $trans = i18n_get_translated($node['name'], true);
    $compare = normalize_keyword($trans);
    $node_is_active = node_is_active($node);
    if ($dynamic_keyword_suggest_contains) {
        if (
            '' != $trans
            && (
                !isset($dynamic_keyword_suggest_contains_characters)
                || $dynamic_keyword_suggest_contains_characters <= mb_strlen($keyword)
            )
            && mb_strpos(mb_strtolower($compare), mb_strtolower($keyword)) !== false
        ) {
            if (mb_strtolower($compare) == mb_strtolower($keyword)) {
                $exactmatch = true;
                $match_is_deactivated = !$node_is_active;
            }

            if ($node_is_active) {
                $results[] = [
                    'label' => $trans,
                    'value' => $node['ref']
                ];
            }
        }
    } else {
        if ('' != $compare && mb_substr(mb_strtolower($compare), 0, mb_strlen($keyword)) == mb_strtolower($keyword)) {
            if (mb_strtolower($compare) == mb_strtolower($keyword)) {
                $exactmatch = true;
                $match_is_deactivated = !$node_is_active;
            }

            if ($node_is_active) {
                $results[] = [
                    'label' => $trans,
                    'value' => $node['ref']
                ];
            }
        }
    }
}

$keyword = stripslashes($keyword);

$fielderror = false;
if (!$exactmatch && !$readonly) {
    # Ensure regexp filter is honoured if one is present
    if (strlen(trim((string)$fielddata["regexp_filter"])) >= 1) {
        if (preg_match("#^" . str_replace($regexp_slash_replace, '\\', $fielddata["regexp_filter"]) . "$#",$keyword,$matches) <= 0) {
            $fielderror = true;
        }
    }

    if (!$fielderror) {
        $results[] = array(
            'label' => "{$lang['createnewentryfor']} {$keyword}",
            'value' => "{$lang['createnewentryfor']} {$keyword}"
        );
    } else {
        $results[] = array(
            'label' => "{$lang['keywordfailedregexfilter']} {$keyword}",
            'value' => "{$lang['keywordfailedregexfilter']} {$keyword}"
        );
    }
} elseif ($exactmatch && $match_is_deactivated) {
    $text = "{$lang['inactive_entry_matched']} {$keyword}";
    $results = [
        [
            'label' => $text,
            'value' => $text,
        ]
    ];
} elseif ($readonly && empty($results)) {
    $results[] = array(
            'label' => "{$lang['noentryexists']} {$keyword}",
            'value' => "{$lang['noentryexists']} {$keyword}"
        );
}

// We return an array of objects with label and value properties: [ { label: "Node ID 1 - option name", value: "101" }, ... ]
// This will later be used by jQuery autocomplete
echo json_encode($results);
exit();