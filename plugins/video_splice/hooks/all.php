<?php 

function HookVideo_spliceAllInitialise()
    {
    global $videosplice_fieldvars;
    config_register_core_fieldvars("Video splice plugin",$videosplice_fieldvars);
    }

function HookVideo_spliceAllRender_actions_add_collection_option($top_actions, array $options){
	global $collection,$count_result,$lang,$pagename,$baseurl_short;

    if(isset($GLOBALS["hook_return_value"]) && is_array($GLOBALS["hook_return_value"]))
        {
        // @see hook() for an explanation about the hook_return_value global
        $options = $GLOBALS["hook_return_value"];
        }

	if ($pagename=="collections" && $count_result!=0)
		{
        $option = array(
            "value" => "video_splice",
            "label" => $lang["action-splice"],
            "data_attr" => array(
                "url" => generateURL("{$baseurl_short}plugins/video_splice/pages/splice.php", array("collection" => $collection)),
            ),
            "category" => ACTIONGROUP_ADVANCED
        );

        $options[] = $option;

		return $options;
	}
}
