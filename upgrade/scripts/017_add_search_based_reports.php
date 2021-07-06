<?php

// Add the new reports created for report-from-search functionality
$path=dirname(__FILE__)."/../../dbstruct/data_report.txt";

sql_query("select ref,support_non_correlated_sql from report limit 1"); // Ensure new column created first.

$f=fopen($path,"r");
while (($row = fgetcsv($f,5000)) !== false)
    {
    # Escape values

    // Pull off the ref - not inserted but used to check we're inserting the new reports only.
    $ref=$row[0];
    array_shift($row);

    for ($n=0;$n<count($row);$n++)
        {
        $row[$n]=escape_check($row[$n]);
        $row[$n]="'" . $row[$n] . "'";
        if ($row[$n]=="''") {$row[$n]="null";}
        }
    if (in_array($ref,array(22,23,24)))
        {
        $sql="insert into report (ref, name, query, support_non_correlated_sql) values (null," . join (",",$row) . ")";
        sql_query($sql,false,-1,false);
        }
    }
