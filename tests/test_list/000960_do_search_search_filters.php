<?php

include_once(__DIR__ . '/../../include/search_functions.php');

if (php_sapi_name()!=="cli") {exit("This utility is command line only.");}

// ----- This test is dependent on successful execution of 950 -----

update_field(951,'subject','Building');
update_field(953,'subject','Building');
update_field(955,'subject','Building');

reindex_resource(951);
reindex_resource(953);
reindex_resource(955);

// ----- Equals (=)(Equals Character) -----

$usersearchfilter='title=library';

$results=do_search('');  // this should return all the library assets
if(count($results)!=3 || !isset($results[0]['ref']) || !isset($results[1]['ref']) || !isset($results[2]['ref']) ||
    (
        ($results[0]['ref']!=951 && $results[1]['ref']!=953 && $results[2]['ref']!=955) &&
        ($results[0]['ref']!=951 && $results[1]['ref']!=955 && $results[2]['ref']!=953) &&
        ($results[0]['ref']!=953 && $results[1]['ref']!=951 && $results[2]['ref']!=955) &&
        ($results[0]['ref']!=953 && $results[1]['ref']!=955 && $results[2]['ref']!=951) &&
        ($results[0]['ref']!=955 && $results[1]['ref']!=951 && $results[2]['ref']!=953) &&
        ($results[0]['ref']!=955 && $results[1]['ref']!=953 && $results[2]['ref']!=951)
    )
) return false;

// ----- Or (|)(Pipe character) -----

// mobile and billy are indexed within resource_keyword

$usersearchfilter='title=mobile|billy';

$results=do_search('');  // this should return all the library assets
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    (
        ($results[0]['ref']!=952 && $results[1]['ref']!=953) &&
        ($results[0]['ref']!=953 && $results[1]['ref']!=952)
    )
) return false;

// ----- Not (!=)(Exclamation Mark and Equals Characters combined) -----

$usersearchfilter='title!=library';

$results=do_search('');  // this should return all the library assets
foreach ($results as $result)
    {
    if (isset($result['ref']) && ($result['ref']==951 || $result['ref']==953 || $result['ref']==955)) return false;
    }

// ----- And Or Combination ------

$usersearchfilter='subject=building;title=district|mobile';

$results=do_search('');
if(count($results)!=2 || !isset($results[0]['ref']) || !isset($results[1]['ref']) ||
    (
        ($results[0]['ref']!=953 && $results[1]['ref']!=955) &&
        ($results[0]['ref']!=955 && $results[1]['ref']!=953)
    )
) return false;

// ----- Or Multiple Fields -----

$usersearchfilter='subject|title=building|goat;title!=test;title!=style';
$results=do_search('');

if(count($results)!=5) return false;
foreach ($results as $result)       // 954 is omitted as it contains "style" and 950 is omitted as it contains "test"
    {
    if (isset($result['ref']) && !in_array($result['ref'],array(950,951,952,953,955))) return false;
    }

return true;
