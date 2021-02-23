<?php

include '../../../include/db.php';
include '../../../include/authenticate.php';
if (!checkperm('i'))
    {
    exit ($lang['error-permissiondenied']);
    }

$resources=getvalescaped("resources","");
$collection=getvalescaped("collection","",true);

$valid=true;

if($resources!="")
    {
    $restoreresources=explode(",",$resources);
    array_filter($restoreresources,"is_int_loose");
    }
elseif($collection!="")
    {
    $restoreresources=get_collection_resources($collection);
    $resources=implode(",",$restoreresources);	
    }
else
    {
    $valid=false;
    }
	
if($valid==true)
    {
    $resourceset=implode("','",$restoreresources);
    $title_field="field".$view_title_field;
    $resourcedetails=sql_query("select ref, $title_field, file_size from resource where ref in ('$resourceset')");

    // Handle entered resources
    if(getval("restore_confirm","")!="")
        {
        sql_query("update resource set pending_restore=1 where ref in ('$resourceset') and archive=2");
        foreach($resourcedetails as $resourcedetail)
            {resource_log($resourcedetail["ref"],"",0,$lang['offline_archive_resource_log_restore_set'],"","");}
        $resulttext=$lang["offline_archive_resources_restore_confirmed"];
        }

    }

include '../../../include/header.php';
?>

<div class='BasicsBox'>
<?php
if (isset($resulttext))
    {
    echo "<div class=\"offlinearchiveSaveStatus\">" . $resulttext . "</div>";
    }
?>
<div>
<a href="<?php echo $baseurl ?>/plugins/offline_archive/pages/administer_archive.php" onClick="return CentralSpaceLoad(this,true);" ><?php echo LINK_CARET . $lang["offline_archive_administer_archive"] ?></a>
</div>


<p>
<h1><?php echo $lang["offline_archive_resource_restore"] ?></h1>
</p>

<?php if($resources!="" && $valid==true)
        {
        $codes=array();
        ?>
        <div class="Listview">
            <table id="offline_archive_table" class="ListviewStyle offline_archive_table" border="0" cellspacing="0" cellpadding="0">
                <?php 
                echo '<tr class="ListviewTitleStyle">';
                echo '<td style="width:150px"></td>';
                echo '<td style="width:150px">';
                echo $lang['property-reference'];
                echo '</td><td>';
                echo $lang['property-title'];
                echo '</td><td>';	
                echo $lang['offline_archive_archive_ref'];
                echo '</td>';
                echo "</tr>";	
                    
                foreach ($resourcedetails as $resourcedetail)
                    {
                    $ref=$resourcedetail['ref'];
                    $thumbpath=get_resource_path($ref,false,"col",false,"jpg");
                    $archivecode=sql_value("select value from resource_data where resource='$ref' and resource_type_field='$offline_archive_archivefield'",'');
                    if($archivecode!=""){$codes[]=$archivecode;}
                    echo '<tr onclick="window.location=\'' . $baseurl . '/?r=' . $ref . '\';">
                    <td>
                    <img src="' . $thumbpath . '">
                    </td>
                    <td>
                    ' . $ref . '</td>
                    <td>';
                    echo $resourcedetail[$title_field];
                    echo '</td>
                    <td>';
                    echo $archivecode;
                    echo '</tr>';					
                    }
                
            
                ?>
            </table>
        </div>
    <?php
        }?>

<form id="resource_restore" id="resource_restore_form" name="resource_restore_form" method="post" action="<?php echo $baseurl ?>/plugins/offline_archive/pages/restore.php">

    <?php generateFormToken("resource_restore_form"); ?>
    <?php if($resources=="")
        {
        echo "<div><p>" . $lang['offline_archive_input_text'] . "</p></div>";
        ?>
        <div class="Question">		
            <label for="resources"><?php echo $lang['offline_archive_input_resources'] ?></label>
            <input class="stdwidth" name="resources" id="resources" type="text" >	
        </div>
        <div class="Question">		
            <label for="collection"><?php echo $lang['offline_archive_input_collection'] ?></label>
            <input class="stdwidth" name="collection" id="collection" type="text" >	
        </div>
        <div class="QuestionSubmit">	
            <input name="restore" id="restore" type="submit" value="&nbsp;&nbsp;<?php echo $lang["offline_archive_restore_resources"]; ?>&nbsp;&nbsp;">
        </div>

        <?php
        }
    else
        {
        ?>
        <input name="resources" type="hidden" value="<?php echo $resources ?>">
        <div class="QuestionSubmit">	
            <input name="restore_confirm" id="restore_confirm" type="submit" value="&nbsp;&nbsp;<?php echo $lang["offline_archive_restore_confirm"]; ?>&nbsp;&nbsp;">
        </div>
        <?php
        }
        ?>
</form>
</div>

<?php
include '../../../include/footer.php';