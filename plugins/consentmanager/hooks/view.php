<?php
include dirname(__FILE__) . "/../include/consent_functions.php";
function HookConsentmanagerViewCustompanels()
	{
	global $lang,$baseurl_short,$ref,$edit_access,$k,$consent_usage_mediums;

	if($k!=""){return false;}

	$consents=consentmanager_get_consents($ref);
	?>
    <div class="RecordBox">
    <div class="RecordPanel">
    <div class="Title"><?php echo $lang["consent_management"] ?></div>

    <?php if ($edit_access || checkperm("cm")) { 
        $new_consent_url_params = array(
            'ref'        => 'new',
            'resource'   => $ref,
            'search'     => getval('search',''),
            'order_by'   => getval('order_by',''),
            'collection' => getval('collection',''),
            'offset'     => getval('offset',0),
            'restypes'   => getval('restypes',''),
            'archive'    => getval('archive','')
        );
        $new_consent_url = generateURL($baseurl_short . "plugins/consentmanager/pages/edit.php",$new_consent_url_params);
        ?>    
    <p><a href="<?php echo $new_consent_url ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_PLUS . $lang["new_consent"] ?></a></p>	
    <?php } ?>

	<?php if (count($consents)>0) { ?>
		<div class="Listview">
		<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
		<tr class="ListviewTitleStyle">
		<td><?php echo $lang["consent_id"] ?></a></td>
		<td><?php echo $lang["name"] ?></a></td>
		<td><?php echo $lang["usage"] ?></a></td>
		<td><?php echo $lang["fieldtitle-expiry_date"] ?></a></td>

		<?php if ($edit_access || checkperm("cm")) { ?>
		<td><div class="ListTools"><?php echo $lang["tools"] ?></div></td>
		<?php } ?>

		</tr>

		<?php
		foreach ($consents as $consent)
			{
			?>
			<tr>
			<td><?php echo $consent["ref"] ?></td>
			<td><?php echo $consent["name"] ?></td>
			<td><?php
				$consent_usage_mediums = trim_array(explode(", ", $consent["consent_usage"]));
				$translated_mediums = "";
				foreach ($consent_usage_mediums as $medium)
					{
					$translated_mediums = $translated_mediums . lang_or_i18n_get_translated($medium, "consent_usage-") . ", ";
					}
				$translated_mediums = substr($translated_mediums, 0, -2); # Remove the last ", "
				echo $translated_mediums;
				?>
			</td>
			<td><?php echo ($consent["expires"]==""?$lang["no_expiry_date"]:nicedate($consent["expires"])) ?></td>

			<?php if ($edit_access || checkperm("cm")) { ?>
			<td><div class="ListTools">
			<a href="<?php echo $baseurl_short ?>plugins/consentmanager/pages/edit.php?ref=<?php echo $consent["ref"] ?>&resource=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php echo $lang["action-edit"]?></a>
			<a href="<?php echo $baseurl_short ?>plugins/consentmanager/pages/unlink.php?ref=<?php echo $consent["ref"] ?>&resource=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);">&gt;&nbsp;<?php echo $lang["action-unlink"]?></a>
			</div></td>
			<?php } ?>

			</tr>
			<?php
			}
		?>

		</table>
		</div>
	<?php } ?>


    </div>

    </div>
    <?php
	return false; # Allow further custom panels
	}