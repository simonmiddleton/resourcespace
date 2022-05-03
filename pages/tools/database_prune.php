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

$public_types = join(", ", $COLLECTION_PUBLIC_TYPES);
sql_query("DELETE FROM collection WHERE `type` NOT IN ({$public_types}) AND user NOT IN (SELECT ref FROM user)");
echo number_format(sql_affected_rows()) . " orphaned collections deleted." . $newline;

sql_query("DELETE FROM collection_keyword WHERE collection NOT IN (SELECT ref FROM collection) OR keyword NOT IN (SELECT ref FROM keyword)");
echo number_format(sql_affected_rows()) . " orphaned collection keywords deleted." . $newline;

sql_query("DELETE FROM collection_log WHERE collection NOT IN (SELECT ref FROM collection)");
echo number_format(sql_affected_rows()) . " orphaned collection log rows deleted." . $newline;

sql_query("DELETE FROM collection_resource WHERE collection NOT IN (SELECT ref FROM collection) OR resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned collection log rows deleted." . $newline;

sql_query("DELETE FROM collection_savedsearch WHERE collection NOT IN (SELECT ref FROM collection)");
echo number_format(sql_affected_rows()) . " orphaned collection saved searches deleted." . $newline;

sql_query("DELETE FROM external_access_keys WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned external access keys deleted." . $newline;

sql_query("DELETE FROM ip_lockout");
echo number_format(sql_affected_rows()) . " IP address lock-outs deleted." . $newline;

sql_query("DELETE FROM resource_keyword WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource-keyword relationships deleted." . $newline;

sql_query("DELETE FROM keyword WHERE ref NOT IN (SELECT keyword FROM resource_keyword) AND ref NOT IN (SELECT keyword FROM keyword_related) AND ref NOT IN (SELECT related FROM keyword_related) AND ref NOT IN (SELECT keyword FROM collection_keyword)");
echo number_format(sql_affected_rows()) . " unused keywords deleted." . $newline;

sql_query("DELETE FROM resource_alt_files WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned alternative files deleted." . $newline;

sql_query("DELETE FROM resource_custom_access WHERE resource NOT IN (SELECT ref FROM resource) OR (user NOT IN (SELECT ref FROM user) AND usergroup NOT IN (SELECT ref FROM usergroup))");
echo number_format(sql_affected_rows()) . " orphaned resource custom access rows deleted." . $newline;

sql_query("DELETE FROM resource_dimensions WHERE resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource dimension rows deleted." . $newline;

sql_query("DELETE FROM resource_log WHERE resource<>0 AND resource NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource log rows deleted." . $newline;

sql_query("DELETE FROM resource_related WHERE resource NOT IN (SELECT ref FROM resource) OR related NOT IN (SELECT ref FROM resource)");
echo number_format(sql_affected_rows()) . " orphaned resource related rows deleted." . $newline;

sql_query("DELETE FROM resource_type_field WHERE resource_type<>999 AND resource_type<>0 AND resource_type NOT IN (SELECT ref FROM resource_type)");
echo number_format(sql_affected_rows()) . " orphaned fields deleted." . $newline;

sql_query("DELETE FROM user_collection WHERE user NOT IN (SELECT ref FROM user) OR collection NOT IN (SELECT ref FROM collection)");
echo number_format(sql_affected_rows()) . " orphaned user-collection relationships deleted." . $newline;

remove_invalid_node_keyword_mappings();
echo number_format(sql_affected_rows()) . " orphaned node-keyword relationships deleted." . $newline;

hook("dbprune");