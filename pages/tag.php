<?php
include "../include/db.php";

include "../include/authenticate.php"; if (!checkperm("n")) {exit("Permission denied");}

if (!$speedtagging) {exit("This function is not enabled.");}

if (getval("save","")!="" && enforcePostRequest(false))
    {
    $ref=getvalescaped("ref","",true);
    $keywords=getvalescaped("keywords","");

    # support resource_type based tag fields
    $resource_type=get_resource_data($ref);
    $resource_type=$resource_type['resource_type'];
    if (isset($speedtagging_by_type[$resource_type])){$speedtaggingfield=$speedtagging_by_type[$resource_type];}

    $oldval=get_data_by_field($ref,$speedtaggingfield);

    update_field($ref,$speedtaggingfield,$keywords);

    # Write this edit to the log.
    resource_log($ref,'e',$speedtaggingfield,"",$oldval,$keywords);
    }


# append resource type restrictions based on 'T' permission
    # look for all 'T' permissions and append to the SQL filter.
    global $userpermissions;
    $rtfilter=array();
    $sql_join="";
    $sql_join_params=[];
    $sql_filter="";
    $sql_filter_params=[];
    for ($n=0;$n<count($userpermissions);$n++)
        {
        if (substr($userpermissions[$n],0,1)=="T")
            {
            $rt=substr($userpermissions[$n],1);
            if (is_numeric($rt)) {$rtfilter[]=$rt;}
            }
        }
    if (count($rtfilter)>0)
        {
        $sql_filter.=" and r.resource_type not in (" . ps_param_insert(count($rtfilter)) . ")";
        $sql_filter_params=ps_param_fill($rtfilter,"i");
        }

    # append "use" access rights, do not show restricted resources unless admin
    if (!checkperm("v"))
        {
        $sql_filter.=" and r.access<>'2'";
        }
    # ------ Search filtering: If search_filter is specified on the user group, then we must always apply this filter.
    global $usersearchfilter;
    $sf=explode(";",$usersearchfilter);
    if (strlen($usersearchfilter)>0)
        {
        for ($n=0;$n<count($sf);$n++)
            {
            $s=explode("=",$sf[$n]);
            if (count($s)!=2) {exit ("Search filter is not correctly configured for this user group.");}

            # Support for "NOT" matching. Return results only where the specified value or values are NOT set.
            $filterfield=$s[0];$filter_not=false;
            if (substr($filterfield,-1)=="!")
                {
                $filter_not=true;
                $filterfield=substr($filterfield,0,-1);# Strip off the exclamation mark.
                }

            # Find field(s) - multiple fields can be returned to support several fields with the same name.
            $f=ps_array("select ref value from resource_type_field where name=?",["s",$filterfield], "schema");
            if (count($f)==0)
                {
                exit ("Field(s) with short name '" . $filterfield . "' not found in user group search filter.");
                }

            # Find keyword(s)
            $ks=explode("|",strtolower($s[1]));
            $ks_params=ps_param_fill($ks,"s");

            $modifiedsearchfilter=hook("modifysearchfilter");
            if ($modifiedsearchfilter){$ks=$modifiedsearchfilter;}
			if ($modifiedsearchfilter){$ks=$modifiedsearchfilter;} 
            if ($modifiedsearchfilter){$ks=$modifiedsearchfilter;}
            $kw=ps_array(
                "SELECT ref value FROM keyword WHERE keyword IN ('" . ps_param_insert(count($ks)) . "')",
                $ks_params
            );

            if (!$filter_not)
                {
                # Standard operation ('=' syntax)
                $sql_join.=
                    " JOIN resource_keyword filter" . $n . " ON r.ref=filter" . $n . ".resource
                        AND filter" . $n . ".resource_type_field in ('" . ps_param_insert(count($f)) . "')
                        AND filter" . $n . ".keyword in ('" . ps_param_insert(count($kw)) . "') ";
                $sql_join_params = array_merge($sql_join_params,ps_param_fill($f,"i"),ps_param_fill($kw,"i"));
                }
            else
                {
                # Inverted NOT operation ('!=' syntax)
                $sql_filter .=
                    " AND r.ref NOT IN
                        (SELECT resource
                            FROM resource_keyword
                        WHERE resource_type_field IN ('" . ps_param_insert(count($f)) . "')
                            AND keyword in ('" . ps_param_insert(count($kw)) . "'))";
                        # Filter out resources that do contain the keyword(s)
                $sql_join_params = array_merge($sql_join_params,ps_param_fill($f,"i"),ps_param_fill($kw,"i"));
                }
            }
        }



# Fetch a resource
$ref=ps_value(
"SELECT r.ref value,count(*) c
    FROM resource r
        LEFT OUTER JOIN resource_keyword rk
            ON r.ref=rk.resource AND rk.resource_type_field=?
        $sql_join
    WHERE r.has_image=1 AND archive=0 
        $sql_filter
    GROUP BY r.ref
    ORDER BY c,rand()
    LIMIT 1",
    array_merge(
        ["i",$speedtaggingfield],
        $sql_join_params,
        $sql_filter_params
    ),
    0
);
if ($ref==0) {exit ("No resources to tag.");}




# Load resource data
$resource=get_resource_data($ref);

# Load existing keywords
$existing=array();

$words=ps_value(
    "SELECT value FROM resource_data WHERE resource = ? AND resource_type_field = ?",
    ["i",$ref,"i",$speedtaggingfield],
    ""
    );

include "../include/header.php";
?>
<div class="BasicsBox">

<form method="post" id="mainform" action="<?php echo $baseurl_short?>pages/tag.php">
<input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref)?>">
<?php generateFormToken("mainform"); ?>
<h1><?php echo $lang["speedtagging"]?></h1>
<p><?php echo text("introtext")?></p>

<?php
$imagepath=get_resource_path($ref,false,"pre",false,$resource["preview_extension"]);
?>
<div class="RecordBox"><div class="RecordPanel"><img src="<?php echo $imagepath?>" alt="" class="Picture" />


<!--<div class="Question">
<label for="keywords"><?php echo $lang["existingkeywords"]?></label>
<div class="Fixed"><?php echo join(", ",$existing)?></div>
</div>-->

<div class="clearerleft"> </div>

<div class="Question">
<label for="keywords"><?php echo $lang["extrakeywords"]?></label>
<input type="text" class="stdwidth" rows=6 cols=50 name="keywords" id="keywords" value="<?php echo htmlspecialchars($words)?>">
</div>

<script type="text/javascript">
document.getElementById('keywords').focus();
</script>

<div class="QuestionSubmit">
<label for="buttons"> </label>
<input name="save" type="submit" default value="&nbsp;&nbsp;<?php echo $lang["next"]?>&nbsp;&nbsp;" />
</div>

<div class="clearerleft"> </div>
</div></div>

<p><?php echo $lang["leaderboard"]?><table>
<?php
$lb=ps_query(
    "SELECT u.fullname,count(*) c
        FROM user u
            JOIN resource_log rl ON rl.user=u.ref
        WHERE rl.resource_type_field=?
        GROUP BY u.ref
        ORDER BY c desc
        LIMIT 5;",
        ["i",$speedtaggingfield]
    );
for ($n=0;$n<count($lb);$n++)
    {
    ?>
    <tr><td><?php echo $lb[$n]["fullname"]?></td><td><?php echo $lb[$n]["c"]?></td></tr>
    <?php
    }
?>
</table></p>

</form>
</div>

<?php
include "../include/footer.php";
?>
