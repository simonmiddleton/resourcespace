<?php

include "../../include/db.php";

include "../../include/authenticate.php";

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

$find=getval("find","");
$order_by=getval("orderby","");
$url_params= ($order_by ? "&orderby={$order_by}" : "") . ($find ? "&find={$find}" : "");

# create new record from callback
$new_size_id=getval("newsizeid","");
if ($new_size_id!="" && enforcePostRequest(false))
	{
	ps_query("insert into preview_size(id,name,internal,width,height) values(?,?,0,0,0)",array("s",strtolower($new_size_id),"s",$new_size_id));
	$ref=sql_insert_id();
	log_activity(null,LOG_CODE_CREATED,$new_size_id,'preview_size','id',$ref,null,'');
	redirect("{$baseurl_short}pages/admin/admin_size_management_edit.php?ref={$ref}{$url_params}");	// redirect to prevent repost and expose form data
	exit;
	}

$ref = getval('ref', '');

if (!ps_value("select ref as value from preview_size where ref=? and internal<>'1'",array("i",$ref), false) && !$internal_preview_sizes_editable)		// note that you are not allowed to edit internal sizes without $internal_preview_sizes_editable=true
	{
	redirect("{$baseurl_short}pages/admin/admin_size_management.php?{$url_params}");		// fail safe by returning to the size management page if duff ref passed
	exit;
	}

if (getval("deleteme", false) && enforcePostRequest(false))
	{
	ps_query("delete from preview_size where ref=?",array("i",$ref));
	log_activity(null,LOG_CODE_DELETED,null,'preview_size',null,$ref);
	redirect("{$baseurl_short}pages/admin/admin_size_management.php?{$url_params}");		// return to the size management page
	exit;
	}

if (getval("save", false) && enforcePostRequest(false))
	{
	$cols=array();

	$name=getval("name","");
	if ($name!="") $cols["name"]=$name;

	$width=getval("width",-1,true);
	if ($width>=0) $cols["width"]=$width;

	$height=getval("height",-1,true);
	if ($height>=0) $cols["height"]=$height;

	$cols["allow_preview"]=(getval('allowpreview',false) ? "1" : "0");
	$cols["allow_restricted"]=(getval('allowrestricted',false) ? "1" : "0");

	foreach ($cols as $col=>$val)
		{
		if (isset($sql_columns))
			{
			$sql_columns.=",";
			}
		else
			{
			$sql_columns="";$params=array();
			}
		$sql_columns.="{$col}=?";$params[]="s";$params[]=$val;
		log_activity(null,LOG_CODE_EDITED,$val,'preview_size',$col,$ref);
		}

	if (isset($sql_columns))
		{
		$params[]="i";$params[]=$ref;
		ps_query("update preview_size set {$sql_columns} where ref=?",$params);
		}
	redirect("{$baseurl_short}pages/admin/admin_size_management.php?{$url_params}");		// return to the size management page
	exit;
	}

$record = ps_query("SELECT ref, id, width, height, padtosize, `name`, internal, allow_preview, allow_restricted, quality FROM preview_size WHERE ref = ?",array("i",$ref));
$record = $record[0];
include "../../include/header.php";
?>
<form method="post" 
      enctype="multipart/form-data" 
      action="<?php echo $baseurl_short; ?>pages/admin/admin_size_management_edit.php?ref=<?php echo $ref . $url_params ?>"
      id="mainform"
      onSubmit="return CentralSpacePost(this, true);">
    <?php generateFormToken("mainform"); ?>
	<div class="BasicsBox">
    <h1><?php echo htmlspecialchars($lang["page-title_size_management_edit"]); ?></h1>
	<?php
	$links_trail = array(
	    array(
	        'title' => $lang["systemsetup"],
	        'href'  => $baseurl_short . "pages/admin/admin_home.php",
			'menu' =>  true
	    ),
	    array(
	        'title' => $lang["page-title_size_management"],
			'href'  => $baseurl_short . "pages/admin/admin_size_management.php?" . $url_params
	    ),
		array(
	        'title' => $lang["page-title_size_management_edit"]
	    )
	);

	renderBreadcrumbs($links_trail);
	?>
	<p><?php echo htmlspecialchars($lang['page-subtitle_size_management_edit']);render_help_link('systemadmin/manage_sizes');?></p>

		<input type="hidden" name="save" value="1">

		<div class="Question">
			<label for="reference"><?php echo htmlspecialchars($lang["property-id"]); ?></label>
			<div class="Fixed"><?php echo $record['id']; ?></div>
			<div class="clearerleft"></div>
		</div>

		<div class="Question">
			<label for="name"><?php echo htmlspecialchars($lang["property-name"]); ?></label>
			<input name="name" type="text" class="stdwidth" value="<?php echo $record['name']; ?>">	
			<div class="clearerleft"></div>
		</div>

		<div class="Question">
			<label for="name"><?php echo htmlspecialchars($lang["property-width"]); ?></label>
			<input name="width" type="text" class="shrtwidth" value="<?php echo $record['width']; ?>">
			<div class="clearerleft"></div>
		</div>

		<div class="Question">
			<label for="name"><?php echo htmlspecialchars($lang["property-height"]); ?></label>
			<input name="height" type="text" class="shrtwidth" value="<?php echo $record['height']; ?>">
			<div class="clearerleft"></div>
		</div>
		
		<div class="Question">
			<label><?php echo htmlspecialchars($lang['property-allow_preview']); ?></label>
			<input name="allowpreview" type="checkbox" value="1"<?php if($record['allow_preview']) {?> checked="checked"<?php }?>>
			<div class="clearerleft"></div>
		</div>

		<div class="Question">
			<label><?php echo htmlspecialchars($lang['property-allow_restricted_download']); ?></label>
			<input name="allowrestricted" type="checkbox" value="1"<?php if($record['allow_restricted']) {?> checked="checked"<?php }?>>
			<div class="clearerleft"></div>
		</div>
		
		<?php
		if(!$record['internal'])
			{
			?>
			<div class="Question">
				<label><?php echo htmlspecialchars($lang["fieldtitle-tick_to_delete_size"])?></label>
				<input name="deleteme" type="checkbox" value="1">
				<div class="clearerleft"></div>
			</div>
			<?php
			}

        hook('render_extra_columns', '', array($record));
		?>

		<div class="QuestionSubmit">
			<input name="buttonsave" type="submit" value="&nbsp;&nbsp;<?php echo escape($lang["save"]); ?>&nbsp;&nbsp;">
		</div>

	</div>

</form>

<?php
include "../../include/footer.php";
