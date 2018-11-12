<?php
function HookTms_linkViewRenderfield($field)
	{
	if(!checkperm("a"))
        {
        return false;
        }

	global $baseurl,$tms_link_object_id_field,$search, $ref;

    if(tms_link_is_rs_uid_field($field["ref"]))
		{			
		$tmsid = $field["value"];
		$value = highlightkeywords($tmsid, $search, $field["partial_index"], $field["name"], $field["keywords_index"]);
		$title = htmlspecialchars($field["title"]);
        $a_href = generateURL(
            "{$baseurl}/plugins/tms_link/pages/tms_details.php",
            array(
                'ref' => $ref,
                'tmsid' => $tmsid,
            )
        );
		?>
        <div class="itemNarrow">
            <h3><?php echo $title; ?></h3>
            <p>
                <a href="<?php echo $a_href; ?>"><?php echo $value; ?></a>
            </p>
        </div>
        <?php

        return true;
		}

	return false;
	}