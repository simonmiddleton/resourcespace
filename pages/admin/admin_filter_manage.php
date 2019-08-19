<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}


$filterorder    = getval("filterorder","ref");
$filtersort     = getval("filtersort", "ASC");
$revsort        = ($filtersort == "ASC") ? "DESC" : "ASC";
$filterfind     = getval("filterfind","");

$filters = get_filters($filterorder,$filtersort,$filterfind);

$filter_edit_url    = $baseurl . "/pages/admin/admin_filter_edit.php";
$filter_manage_url  = $baseurl . "/pages/admin/admin_filter_manage.php";

$params = array(
    "filterfind"    => $filterfind,
    "filtersort"    => $filtersort, 
    "filterorder"   => $filterorder
    );

include "../../include/header.php";


?>


<div id="CentralSpaceContainer">
    <div id="CentralSpace">

        <div class="BasicsBox">


            <h1><?php echo $lang["filter_manage"];render_help_link("systemadmin/search-filters");?></h1>
            	
            <div class="Listview">
            <table id="filter_list_table" border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
            <tbody>
                <tr class="ListviewTitleStyle">
                    <td>
                        <a href="<?php echo generateURL($filter_manage_url,$params, array("filterorder"=>"ref", "sort"=> $revsort)); ?>" onclick="return CentralSpaceLoad(this);"><?php echo $lang['property-reference']; ?></a>
                    </td>
                    <td><a href="<?php echo generateURL($filter_manage_url,$params, array("filterorder"=>"name", "sort"=> $revsort)); ?>" onclick="return CentralSpaceLoad(this);"><?php echo $lang['property-name']; ?></a>
                    </td>
                    <td><div class="ListTools"><?php echo $lang['tools']; ?></div></td>
                </tr>

            <?php
            for ($n=0;$n<count($filters);$n++)
                {
                ?>
                <tr class="filter_row" id="field_sort_<?php echo $filters[$n]["ref"];?>">
                    <td>
                        <a href="<?php echo generateURL($filter_edit_url,$params, array("ref"=>$filters[$n]["ref"])); ?>" onclick="return CentralSpaceLoad(this);"><?php echo $filters[$n]["ref"]; ?></a>
                    </td>   
                    <td>
                        <div class="ListTitle">
                            <a href="<?php echo generateURL($filter_edit_url,$params, array("filter"=>$filters[$n]["ref"])); ?>" onclick="return CentralSpaceLoad(this);"><?php echo str_highlight(i18n_get_translated($filters[$n]["name"]),$filterfind,STR_HIGHLIGHT_SIMPLE); ?></a>
                        </div>
                    </td>
                    <td>
                        <div class="ListTools">
                            <a href="#" onClick="jQuery('#form_copy_from').val('<?php echo $filters[$n]["ref"]; ?>');return CentralSpacePost(document.getElementById('admin_filter_form'),true)" ><?php echo LINK_CARET ?><?php echo $lang["copy"] ?></a>
                            <a href="<?php echo generateURL($filter_edit_url,$params, array("filter" => $filters[$n]["ref"])); ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["action-edit"]?> </a>
                            <a href="#"
                            onClick='
                                    event.preventDefault();
            
                                    if(confirm("<?php echo $lang["confirm-deletion"]; ?>"))
                                        {
                                        var post_data = {
                                            ajax: true,
                                            filter_manage_page: true,
                                            filter: <?php echo urlencode($filters[$n]['ref']); ?>,
                                            delete_filter: <?php echo urlencode($filters[$n]['ref']); ?>,
                                            <?php echo generateAjaxToken('admin_filter_edit'); ?>
                                        };
            
                                        jQuery.post("<?php echo $filter_edit_url; ?>", post_data, function(response) {
                                            if(response.deleted)
                                                {
                                                var redirect_link = document.createElement("a");
                                                redirect_link.href = "<?php echo generateURL($filter_manage_url,$params, array("deleted"=>$filters[$n]["ref"])); ?>";
                                                CentralSpaceLoad(redirect_link, true);
                                                }
                                            else
                                                {
                                                errors = "";
                                                console.log(response.errors);
                                                for (var i in response.errors) 
                                                    {
                                                    errors += response.errors[i] + "<br />";
                                                    }
                                                 
                                                styledalert("<?php echo urlencode($lang["error"]) ?>",errors);
                                                }
                                        }, "json"); 
            
                                        return false;
                                        }
                                    else
                                        {
                                        return false;
                                        }
                                '><?php echo LINK_CARET ?><?php echo $lang["action-delete"] ?></a>
                            
                        </div>
                    </td>
                </tr>
                <?php
                }?>
            </tbody>
            </table>
            </div>        
        </div> <!-- End of BasicsBox -->
        <div class="BasicsBox">
            <form method="post" id="admin_filter_form" action="<?php echo generateURL($filter_edit_url,$params); ?>" onsubmit="return CentralSpacePost(this,true);">
                <?php generateFormToken("admin_filter_edit"); ?>
                <input type="hidden" name="filter" value="0" />
                <input type="hidden" id="form_copy_from" name="copy_from" value="" />
                <input type="hidden" name="save" value="true" />
                <div class="Question">
                    <label for="filter_name"><?php echo $lang["filter_create_name"]?></label>
                    <div class="tickset">
                    <div class="Inline"><input type=text name="filter_name" id="filter_name" maxlength="100" class="shrtwidth" /></div>
                    <div class="Inline"><input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"]?>&nbsp;&nbsp;" /></div>
                    </div>
                    <div class="clearerleft"> </div>
                </div>
            </form>
        </div>
    </div> <!-- End of CentralSpace -->

</div> <!-- End of CentralSpaceContainer -->


<?php


include("../../include/footer.php");