<?php
/**
 * Manage related keywords page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php";if (!checkperm("k")) {exit ("Permission denied.");}
include "../../include/research_functions.php";

$offset=getval("offset",0,true);
$find=getval("find","");

if (array_key_exists("find",$_POST)) {$offset=0;} # reset page counter when posting


include "../../include/header.php";
?>


<div class="BasicsBox"> 
  <h1><?php echo $lang["managerelatedkeywords"];render_help_link('resourceadmin/related-keywords');?></h1>
  <p><?php echo text("introtext")?></p>
 
<?php 
$keywords=get_grouped_related_keywords($find);

# pager
$per_page = $default_perpage_list;
$results=count($keywords);
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$url="team_related_keywords.php?find=" . urlencode($find);
$jumpcount=1;

?><div class="TopInpageNav"><?php pager();	?></div>

<div class="Listview">
<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<tr class="ListviewTitleStyle">
<td><?php echo $lang["keyword"]?></td>
<td><?php echo $find==""?$lang["relatedkeywords"]:$lang["matchingrelatedkeywords"]?></td>
<td><div class="ListTools"><?php echo $lang["tools"]?></div></td>
</tr>

<?php
for ($n=$offset;(($n<count($keywords)) && ($n<($offset+$per_page)));$n++)
    {
    $edit_url = generateURL(
        $baseurl_short . "pages/team/team_related_keywords_edit.php",
        ["keyword" => $keywords[$n]["keyword"]]
    );
    ?>
    <tr>
    <td>
        <div class="ListTitle">
            <a href="<?php echo $edit_url?>"><?php echo htmlspecialchars($keywords[$n]["keyword"])?>
        </div>
    </td>
    <td><?php echo tidy_trim(htmlspecialchars($keywords[$n]["related"]),45)?></td>
    <td>
        <div class="ListTools">
            <a 
                onClick="return CentralSpaceLoad(this,true);" 
                href="<?php echo $edit_url?>"
                ><?php echo '<i class="fas fa-edit"></i>&nbsp' . htmlspecialchars($lang["action-edit"])?>
            </a>
        </div>
    </td>
    </tr>
    <?php
    }
?>

</table>
</div>
<div class="BottomInpageNav"><?php pager(false); ?></div>
</div>


<div class="BasicsBox">
    <form method="GET" action="<?php echo $baseurl_short?>pages/team/team_related_keywords.php">
        <div class="Question">
            <label for="find"><?php echo $lang["searchkeyword"]?></label>
            <div class="tickset">
             <div class="Inline"><input type=text name="find" id="find" value="<?php echo $find?>" maxlength="100" class="shrtwidth" /></div>
             <div class="Inline"><input name="Submit" type="submit" value="<?php echo $lang["searchbutton"]?>" /></div>
            </div>
            <div class="clearerleft"> </div>
        </div>
    </form>
</div>

<div class="BasicsBox">
    <form method="post" action="<?php echo $baseurl_short?>pages/team/team_related_keywords_edit.php">
        <?php generateFormToken("related_keywords"); ?>
        <div class="Question">
            <label for="create"><?php echo $lang["newkeywordrelationship"]?></label>
            <div class="tickset">
             <div class="Inline"><input type=text name="keyword" id="keyword" value="" maxlength="100" class="shrtwidth" /></div>
             <div class="Inline"><input name="createsubmit" type="submit" value="<?php echo $lang["create"]?>" /></div>
            </div>
            <div class="clearerleft"> </div>
        </div>
    </form>
</div>


<?php
include "../../include/footer.php";
?>
