<?php 

function HookAnnotateDatabase_pruneDbprune(){
    sql_query("delete from resource_keyword where annotation_ref > 0 and annotation_ref not in (select note_id from annotate_notes)");
    echo sql_affected_rows() . " orphaned annotation resource-keyword relationships deleted.<br/><br/>";
}

function HookAnnotateAllAfterreindexresource($ref){
    // make sure annotation indexing isn't lost when doing a reindex.
    $ref_escaped = escape_check($ref);
    $notes=sql_query("select * from annotate_notes where ref='$ref_escaped'");
    global $pagename;

    foreach($notes as $note){
        #Add annotation to keywords
        $keywordtext = substr(strstr($note['note'],": "),2); # don't add the username to the keywords

        add_keyword_mappings($ref,i18n_get_indexable($keywordtext),-1,false,false,"annotation_ref",$note['note_id']);
    }
}

function HookAnnotateAllModifyselect(){
return (" ,r.annotation_count ");

}

function HookAnnotateAllRemoveannotations(){
    global $ref;

    $ref_escaped = escape_check($ref);
    sql_query("delete from annotate_notes where ref='$ref_escaped'");
    sql_query("update resource set annotation_count=0 where ref='$ref_escaped'");   
    sql_query("delete from resource_keyword where resource='$ref_escaped' and annotation_ref>0");;
}

function HookAnnotateAllRender_actions_add_collection_option($top_actions, array $options, $collection_data, array $urlparams){
    global $lang,$pagename,$annotate_pdf_output,$annotate_pdf_output_only_annotated,$baseurl,$collection,$count_result;

    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

    if ($annotate_pdf_output || $count_result!=0){
        $annotate_option = array(
            "value" => "annotate",
            "label" => $lang["pdfwithnotes"],
            "data_attr" => array(
                "url" => generateURL(
                    "{$baseurl}/plugins/annotate/pages/annotate_pdf_config.php",
                    $urlparams,
                    array(
                        "col" => $collection,
                    )),
            ),
        );
        $options[] = $annotate_option;
        
        return $options;
    }
}
function HookAnnotateAllAdditionalheaderjs(){
    global $baseurl,$k,$baseurl_short,$css_reload_key;
?>
<link rel="stylesheet" type="text/css" media="screen,projection,print" href="<?php echo $baseurl_short?>plugins/annotate/lib/jquery/css/annotation.css?css_reload_key=<?php echo $css_reload_key?>"/>

<script type="text/javascript" src="<?php echo $baseurl_short?>plugins/annotate/lib/jquery/js/jquery.annotate.js?css_reload_key=<?php echo $css_reload_key?>"></script>
<script language="javascript">
    function annotate(ref,k,w,h,annotate_toggle,page, modal){

        // Prevent duplication of image if loading is interrupted:
        var canvasExists = document.getElementsByClassName("image-annotate-canvas");
        if (canvasExists.length != 0)
            {
            return
            }

        // Set function's optional arguments:
        page = typeof page !== 'undefined' ? page : 1;
        modal = typeof modal !== 'undefined' ? modal : false;

        // Set defaults:
        var url_params = '';

        if(page != 1) 
            {
            url_params = '&page=' + page;
            }

        var target = jQuery("#toAnnotate");
        if(modal)
            {
            target = jQuery("#modal #toAnnotate");
            }
 
        target.annotateImage({
            getUrl: "<?php echo $baseurl_short?>plugins/annotate/pages/get.php?ref="+ref+"&k="+k+"&pw="+w+"&ph="+h + url_params,
            saveUrl: "<?php echo $baseurl_short?>plugins/annotate/pages/save.php?ref="+ref+"&k="+k+"&pw="+w+"&ph="+h + url_params,
            deleteUrl: "<?php echo $baseurl_short?>plugins/annotate/pages/delete.php?ref="+ref+"&k="+k + url_params,
            useAjax: true,
            <?php  
            if ($k=="")
                {?> 
                editable: true, 
                <?php 
                }
            else
                { ?> 
                editable: false, 
                <?php 
                } ?>  
            toggle: annotate_toggle,
            modal: modal,
        });
    }
</script>
<?php }

function HookAnnotateAllExport_add_tables()
    {
    return array("annotate_notes"=>array());
    }