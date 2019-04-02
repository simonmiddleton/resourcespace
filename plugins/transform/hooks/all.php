<?php 

function HookTransformAllAdditionalheaderjs()
    {
    global $baseurl,$baseurl_short;?>
    <link rel="stylesheet" href="<?php echo $baseurl_short?>plugins/transform/lib/jcrop/css/jquery.Jcrop.min.css" type="text/css" />
    <script type="text/javascript" src="<?php echo $baseurl?>/plugins/transform/lib/jcrop/js/jquery.Jcrop.min.js" language="javascript"></script>
    <?php
    }


function HookTransformAllRender_actions_add_collection_option($top_actions,$options,$collection_data)
    {
	global $cropper_enable_batch,$count_result,$lang,$baseurl_short, $userref;
    
	$c=count($options);
        
    if ($cropper_enable_batch
        && $count_result > 0
        &&  (
            $userref == $collection_data['user']
            || $collection_data['allow_changes'] == 1
            || checkperm('h')
            )
        )
        {
        $data_attribute['url'] = sprintf('%splugins/transform/pages/collection_transform.php?collection=%s',
            $baseurl_short,
            urlencode($collection_data['ref'])
        );
        $options[$c]['value']='transform';
        $options[$c]['label']=$lang["transform"];
        $options[$c]['data_attr']=$data_attribute;
        return $options;
        }
    }
    
function HookTransformAllAdditional_title_pages_array()
    {
    return array("crop","collection_transform");
    }
    
function HookTransformAllAdditional_title_pages()
    {
    global $pagename,$lang,$applicationname;
    switch($pagename)
        {
        case "crop":
            global $original;
            if($original)
                {
                $pagetitle=$lang['transform_original'];
                }
            else
                {
                $pagetitle=$lang['transformimage'];
                }
            break;
        
        case "collection_transform":
            $pagetitle=$lang['batchtransform'];
            break;
        }
    if(isset($pagetitle))
        {
        echo "<script language='javascript'>\n";
        echo "document.title = \"$applicationname - $pagetitle\";\n";
        echo "</script>";
        }
    }

function HookTransformAllAfteruilayout()
    {
    global $CSRF_enabled, $CSRF_token_identifier, $usersession;

    $form_append_csrf = '';
    if($CSRF_enabled)
        {
        $form_append_csrf = sprintf('
            form.append(
                jQuery("<input></input>")
                    .attr("type", "hidden")
                    .attr("name", "%s")
                    .attr("value", "%s")
            );',
            $CSRF_token_identifier,
            generateCSRFToken($usersession, 'transform_download_file')
        );
        }
    ?>
    <!-- Transform plugin custom functions -->
    <script>
    function transform_download_file(resource, url)
        {
        event.preventDefault();

        var iaccept = document.getElementById('iaccept').checked;
        if(iaccept == false)
            {
            return false;
            }

        var crop_url = url + '&iaccept=on';

        var form = jQuery('<form id="TransformDownloadFile"></form>')
            .attr("action", crop_url)
            .attr("method", "post");

        <?php echo $form_append_csrf; ?>

        form.appendTo('body').submit().remove();

        var view_page_anchor = document.createElement("a");
        view_page_anchor.setAttribute("href", baseurl_short + "?r=" + resource);
        CentralSpaceLoad(view_page_anchor, true, false);
        }
    </script>
    <?php
    }