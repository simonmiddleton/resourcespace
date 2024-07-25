<?php
/**
 * Manage resource request page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */


include "../../include/boot.php";

include "../../include/authenticate.php";if (!checkperm("R")) {exit ("Permission denied.");}
include "../../include/request_functions.php";
include "../../include/header.php";

$offset=getval("offset",0,true);
?>

<div class="BasicsBox"> 
<h1><?php echo escape($lang["managerequestsorders"]); ?></h1>
<?php
$links_trail = array(
    array(
        'title' => $lang["teamcentre"],
        'href'  => $baseurl_short . "pages/team/team_home.php",
        'menu' =>  true
    ),
    array(
        'title' => $lang["managerequestsorders"],
        'help'  => "resourceadmin/user-resource-requests"
    )
);

renderBreadcrumbs($links_trail);
?>

<?php 
$requests=get_requests();

# pager
$per_page=20;
$results=count($requests);
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$url="team_request.php?";
$jumpcount=1;

?><div class="TopInpageNav"><?php pager();  ?> <br style="clear:left" /><br />
</div>


<div class="Listview">
<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
<tr class="ListviewTitleStyle">
<?php if(!hook("requestlistheader")): ?>
<td><?php echo escape($lang["requestorderid"]); ?></td>
<td><?php echo escape($lang["username"]); ?></td>
<td><?php echo escape($lang["fullname"]); ?></td>
<td><?php echo escape($lang["date"]); ?></td>
<td><?php echo escape($lang["itemstitle"]); ?></td>
<td><?php echo escape($lang["type"]); ?></td>
<td><?php echo escape($lang["assignedto"]); ?></td>
<td><?php echo escape($lang["status"]); ?></td>
<td><div class="ListTools"><?php echo escape($lang["tools"]); ?></div></td>
<?php endif; ?>
</tr>

<?php
$statusname=array("","","","");
$requesttypes=array("","","","");

for ($n=$offset;(($n<count($requests)) && ($n<($offset+$per_page)));$n++)
    {
    ?>
    <tr>
<?php if(!hook("requestlistitems")): ?>
    <td><?php echo escape($requests[$n]["ref"]); ?></td>
    <td><?php echo escape($requests[$n]["username"]); ?></td>
    <td><?php echo escape((string) $requests[$n]["fullname"]); ?></td>
    <td><?php echo escape(nicedate($requests[$n]["created"],true, true, true));?></td>
    <td><?php echo escape($requests[$n]["c"]); ?></td>
    <td><?php echo escape($lang["resourcerequesttype" . $requests[$n]["request_mode"]]); ?></td>
    <td><?php echo escape((string) $requests[$n]["assigned_to_username"]); ?></td>
    <td><?php echo escape($lang["resourcerequeststatus" . $requests[$n]["status"]]); ?></td>
    <td><div class="ListTools"><a href="<?php echo $baseurl_short?>pages/team/team_request_edit.php?ref=<?php echo escape($requests[$n]["ref"]); ?>" onClick="return <?php echo $modal_default ? "Modal" : "CentralSpace"; ?>Load(this,true);"><i class="fas fa-edit"></i>&nbsp;<?php echo escape($lang["action-edit"]); ?></a></a></div></td>
<?php endif; ?>
    </tr>
    <?php
    }
?>

</table>
</div><!--end of Listview -->
<div class="BottomInpageNav"><?php pager(false); ?></div>
</div><!-- end of BasicsBox -->

<?php

include "../../include/footer.php";
