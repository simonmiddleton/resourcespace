<?php 
include "../include/boot.php";

include "../include/authenticate.php";
if (checkperm("b"))
    {exit("Permission denied");}

$offset=getval("offset",0,true);
$per_page=getval("per_page_list",$default_perpage_list,true);rs_setcookie('per_page_list', $per_page);

include "../include/header.php";

?>
<div class="BasicsBox">
<p><a href="<?php echo $baseurl_short?>pages/collection_manage.php" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo escape($lang["managecollectionslink"]); ?></a></p>  
<h1><?php echo escape($lang["shared_collections"]);render_help_link("user/sharing-resources");?></h1>
<?php

$collections=get_user_collections($userref,"!shared");
$results=count($collections);
$totalpages=ceil($results/$per_page);
$curpage=floor($offset/$per_page)+1;
$jumpcount=1;

$url=$baseurl_short."pages/view_shares.php?coluser=" . $userref;

?><div class="TopInpageNav"><?php pager(false); ?></div><?php

for ($n=$offset;(($n<count($collections)) && ($n<($offset+$per_page)));$n++)
    {   
    ?>
    <div class="RecordBox">
    <div class="RecordPanel">
        <div class="RecordHeader">
            <table>
            <tr>
                <td style="margin:0px;padding:0px;">
                    <h1 class="shared_collection_title"><a href="<?php echo $baseurl_short?>pages/search.php?search=!collection<?php echo $collections[$n]['ref']; ?>" onclick="return CentralSpaceLoad(this);" ><?php echo i18n_get_collection_name($collections[$n]);  ?></a></h1>
                </td>
            </tr>
            </table>
        
            <div class="clearerright"> </div>
        </div><!-- End of RecordHeader --> 
        <div class="Listview" style="margin-top:10px;margin-bottom:5px;clear:left;">
            <table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
                <tr class="ListviewBoxedTitleStyle">
                    <td width="15%">
                    <?php echo escape($lang["sharedwith"]); ?>
                    </td>
                    <td width="15%">
                    <?php echo escape($lang["access"]); ?>
                    </td>
                    <td width="40%">
                    <?php echo escape($lang["fieldtitle-notes"]); ?>
                    </td>
                    <td width="30%"><div class="ListTools"><?php echo escape($lang["tools"]); ?></div></td>
                </tr>
            <?php
            // Display row for each share/attached user
            $colref=$collections[$n]["ref"];
            
            // Check for external shares
            $extshares=ps_query("SELECT access, expires 
								   FROM external_access_keys 
								  WHERE collection=? and (expires is null or expires>now()) group by collection", array("i",$colref));
            
            if(count($extshares)!=0)
                {           
                foreach($extshares as $extshare)
                    {
                    echo "<tr>";
                    echo "<td>" . "External " . "</td>";
                    echo "<td>" . escape($extshare["access"]==0 ? $lang["access0"] : $lang["access1"]) . "</td>";
                    echo "<td>" .  escape(str_replace("%date%",(($extshare["expires"]!="")?nicedate($extshare["expires"]):$lang["never"]),$lang["expires-date"])) . "</td>";
                    echo "<td><div class=\"ListTools\"><a onclick=\"return CentralSpaceLoad(this,true);\" href=\"" . $baseurl . "/pages/collection_share.php?ref=" . escape($collections[$n]["ref"]) . "\"><?php echo LINK_CARET ?>" . escape($lang["action-edit"]) . "</a></div></td>";
                    echo "</tr>";
                    }                   
                }
                
            // Check for attached users
            $colusers=ps_query("SELECT u.fullname, u.username 
						          FROM user_collection uc LEFT JOIN user u on u.ref=uc.user and user<>? 
								 WHERE uc.collection=?",array("i",$userref, "i",$colref));
            
            if(count($colusers)!=0)
                {
                echo "<tr>";
                echo "<td>" . escape($lang["users"]) . "</td>";
                echo "<td>" . escape(($collections[$n]["allow_changes"]==0)?$lang["view"]:$lang["addremove"]) . "</td>";
                echo "<td>" . escape($lang["users"]) . ":<br />";
                foreach($colusers as $coluser)
                    {
                    echo escape(($coluser["fullname"]!="")?$coluser["fullname"]:$coluser["username"]) . "<br />";                                         
                    }
                echo "</td>";
                echo "<td><div class=\"ListTools\"><a onclick=\"return CentralSpaceLoad(this,true);\" href=\"" . $baseurl . "/pages/collection_edit.php?ref=" . escape($collections[$n]["ref"]) . "\"><?php echo LINK_CARET ?>" . escape($lang["action-edit"]) . "</a></div></td>";
                echo "</tr>";
                }
                
            if(in_array($collections[$n]["type"], $COLLECTION_PUBLIC_TYPES))
                {
                if ($collections[$n]["type"] == COLLECTION_TYPE_FEATURED)
                    {
                    echo "<tr>";
                    echo "<td>" . escape($lang["theme"]) . "</td>";
                    echo "<td>" . escape(($collections[$n]["allow_changes"]==0)?$lang["view"]:$lang["addremove"])  . "</td>";
                    echo "<td>" . escape($lang["notavailableshort"]) . "</td>";
                    echo "<td><div class=\"ListTools\"><a onclick=\"return CentralSpaceLoad(this,true);\" href=\"" . $baseurl . "/pages/collection_edit.php?ref=" . escape($collections[$n]["ref"]) . "\"><?php echo LINK_CARET ?>" . escape($lang["action-edit"]) . "</a></div></td>";
                    echo "</tr>";
                    }
                else
                    {
                    echo "<tr>";
                    echo "<td>" . escape($lang["public"]) . "</td>";
                    echo "<td>" . escape(($collections[$n]["allow_changes"]==0)?$lang["view"]:$lang["addremove"])  . "</td>";
                    echo "<td>" . escape($lang["notavailableshort"]) . "</td>";
                    echo "<td><div class=\"ListTools\"><a onclick=\"return CentralSpaceLoad(this,true);\" href=\"" . $baseurl . "/pages/collection_edit.php?ref=" . escape($collections[$n]["ref"]) . "\"><?php echo LINK_CARET ?>" . escape($lang["action-edit"]) . "</a></div></td>";
                    echo "</tr>";           
                    }
                }
                ?>
        
            </table>
        </div><!-- End of Listview --> 
        <div class="PanelShadow"> </div>
    </div> <!-- End of RecordPanel -->
    </div> <!--  End of RecordBox -->
    <?php
    }
    ?>  


<div class="BottomInpageNav"><?php pager(false); ?></div>

</div><!--  End of BasicsBox -->
<?php	  
include "../include/footer.php";
?>
