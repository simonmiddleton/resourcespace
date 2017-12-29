<?php
if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Command line execution only');
    }

$_SERVER["HTTP_HOST"] = $argv[2];

require dirname(__FILE__) . "/../../include/db.php";
require_once dirname(__FILE__) . "/../../include/general.php";
require_once dirname(__FILE__) . "/../../include/resource_functions.php";

set_time_limit(0);

if(defined('STDIN'))
    {
    if(empty($argv[1]))
        {
        exit("Resource Refs missing");
        }

    $rlist = explode(",", $argv[1]);
    }
else
    {
    if(getval("resource_refs","") === "")
        {
        exit("Resource Refs missing");
        }

    $rlist = getval("resource_refs", "");
    }

#Cleanse Input
foreach($rlist as $k => $v)
    {
    if(!is_numeric($v))
        {
        unset($rlist[$k]);
        continue;
        }

    if(sql_value("SELECT count(ref) AS `value` FROM resource WHERE ref = '" . escape_check($v) . "'", 0) != 1)
        {
        unset($rlist[$k]);
        }
    }

for($n = 0; $n < count($rlist); $n++)
    {
    for($m = 0; $m < count($rlist); $m++)
        {
        // Don't relate a resource to itself
        if($rlist[$n] != $rlist[$m])
            {
            $rlist_n_escaped = escape_check($rlist[$n]);
            $rlist_m_escaped = escape_check($rlist[$m]);

            $sql = "
                SELECT count(1) AS `value`
                  FROM resource_related
                 WHERE resource = '{$rlist_n_escaped}'
                   AND related = '{$rlist_m_escaped}'
                 LIMIT 1";

            if(sql_value($sql, 0) != 1)
                {
                sql_query("
                    INSERT INTO resource_related (resource, related)
                         VALUES ('{$rlist_n_escaped}','{$rlist_m_escaped}')");
                }
            }
        }
    }

echo "Completed Relating Resources";
