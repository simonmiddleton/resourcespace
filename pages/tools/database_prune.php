<?php
#
# database_prune.php
#
# Cleans the database of unused / orphaned rows
#

require dirname(__FILE__) . "/../../include/db.php";

if('cli' != php_sapi_name())
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied');
    }

$newline = PHP_EOL;

$public_types = join(", ", $COLLECTION_PUBLIC_TYPES); // NB Not user entered, does not need to be a parameter in SQL below
ps_query("DELETE FROM collection WHERE `type` NOT IN ({$public_types}) AND user NOT IN (SELECT ref FROM user)");
echo number_format(sql_affected_rows()) . " orphaned collections deleted." . $newline;

ps_query("DELETE FROM collection_keyword WHERE collection NOT IN (SELECT ref FROM collection) OR keyword NOT IN (SELECT ref FROM keyword)");
echo number_format(sql_affected_rows()) . " orphaned collection keywords deleted." . $newline;

ps_query("DELETE FROM collection_log WHERE collection NOT IN (SELECT ref FROM collection)");
echo number_format(sql_affected_rows()) . " orphaned collection log rows deleted." . $newline;

ps_query("DELETE FROM collection_resource WHERE collection NOT IN (SELECT ref FROM collection) OR resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned collection log rows deleted." . $newline;

ps_query("DELETE FROM collection_savedsearch WHERE collection NOT IN (SELECT ref FROM collection)");
echo number_format(sql_affected_rows()) . " orphaned collection saved searches deleted." . $newline;

ps_query("DELETE FROM external_access_keys WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned external access keys deleted." . $newline;

ps_query("DELETE FROM ip_lockout");
echo number_format(sql_affected_rows()) . " IP address lock-outs deleted." . $newline;

ps_query("DELETE FROM resource_keyword WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource-keyword relationships deleted." . $newline;

ps_query("DELETE FROM keyword WHERE ref NOT IN (SELECT keyword FROM resource_keyword) AND ref NOT IN (SELECT keyword FROM keyword_related) AND ref NOT IN (SELECT related FROM keyword_related) AND ref NOT IN (SELECT keyword FROM collection_keyword)");
echo number_format(sql_affected_rows()) . " unused keywords deleted." . $newline;

ps_query("DELETE FROM resource_alt_files WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned alternative files deleted." . $newline;

ps_query("DELETE FROM resource_custom_access WHERE resource NOT IN (SELECT ref FROM resource) OR (user NOT IN (SELECT ref FROM user) AND usergroup NOT IN (SELECT ref FROM usergroup))");
echo number_format(sql_affected_rows()) . " orphaned resource custom access rows deleted." . $newline;

ps_query("DELETE FROM resource_dimensions WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource dimension rows deleted." . $newline;

ps_query("DELETE FROM resource_log WHERE resource<>0 AND resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource log rows deleted." . $newline;

ps_query("DELETE FROM resource_related WHERE resource NOT IN (SELECT ref FROM resource) OR related NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource related rows deleted." . $newline;

ps_query("DELETE FROM resource_type_field WHERE resource_type<>999 AND resource_type<>0 AND resource_type NOT IN (SELECT ref FROM resource_type)");
echo number_format(sql_affected_rows()) . " orphaned fields deleted." . $newline;

ps_query("DELETE FROM user_collection WHERE user NOT IN (SELECT ref FROM user) OR collection NOT IN (SELECT ref FROM collection)");
echo number_format(sql_affected_rows()) . " orphaned user-collection relationships deleted." . $newline;

ps_query("DELETE FROM resource_data WHERE resource NOT IN (SELECT ref FROM resource) OR resource_type_field NOT IN (SELECT ref FROM resource_type_field)");
echo number_format(sql_affected_rows()) . " orphaned resource data rows deleted." . $newline;

# Clean out and resource data that is set for fields not applicable to a given resource type.
$r = get_resource_types();
for ($n=0;$n<count($r);$n++)
    {
    $rt = $r[$n]["ref"];
    $fields = ps_array("SELECT ref value FROM resource_type_field WHERE resource_type=0 OR resource_type=999 OR resource_type=?",array("i",$rt));
    if (count($fields) > 0)
        {
        $params=
        ps_query("DELETE FROM resource_data WHERE resource in (SELECT ref FROM resource WHERE resource_type=?) AND resource_type_field NOT IN (" . ps_param_insert(count($fields)) . ")",array_merge(array("i",$rt),ps_param_fill($fields,"i")));

        echo number_format(sql_affected_rows()) . " orphaned resource data rows deleted for resource type $rt." . $newline;
        }
    }

hook("dbprune");

?>