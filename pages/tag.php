<?php
include "../include/db.php";

include "../include/authenticate.php"; if (!checkperm("n")) {exit("Permission denied");}

if (!$speedtagging) {exit("This function is not enabled.");}

if (getval("save","")!="" && enforcePostRequest(false))
    {
    $ref=getval("ref","",true);
    $keywords=getval("keywords","");

    # support resource_type based tag fields
    $resource_type=get_resource_data($ref);
    $resource_type=$resource_type['resource_type'];
    if (isset($speedtagging_by_type[$resource_type])){$speedtaggingfield=$speedtagging_by_type[$resource_type];}

    $oldval=get_data_by_field($ref,$speedtaggingfield);

    update_field($ref,$speedtaggingfield,$keywords);

    # Write this edit to the log.
    resource_log($ref,'e',$speedtaggingfield,"",$oldval,$keywords);
    }

$resources = do_search("!empty" . $speedtaggingfield . " !hasimage",'','relevance','',500);

$ref = 0;
if(is_array($resources) && count($resources) > 0)
    {    
    # Fetch a random resource
    $idx=array_rand($resources);
    $ref = $resources[$idx]["ref"];
    }

if ($ref==0) {exit ("No resources to tag.");}

# Load resource data
$resource=get_resource_data($ref);

# Load existing keywords
$existing=array();
$words = get_data_by_field($ref,$speedtaggingfield);

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


<div class="Question">
    <label for="resourceid"><?php echo $lang["resourceid"]?></label>
    <div class="fixed stdwidth"><?php echo htmlspecialchars($ref); ?></div>
</div>

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
