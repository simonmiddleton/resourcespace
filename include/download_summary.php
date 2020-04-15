<?php 
$download_summary=download_summary($ref);
$total=0;
foreach ($download_summary as $usage)
	{ 
	if (array_key_exists($usage["usageoption"],$download_usage_options))
		{
		$total+=$usage["c"];
		} else if ($usage['usageoption'] == '-1') {
			$total+=$usage['c'];
		}
	}

$rl_url = "{$baseurl}/pages/log.php";
$rl_params = array(
    "ref" => $ref,
    "search" => $search,
    "order_by" => $order_by,
    "sort" => $sort,
    "archive" => $archive,
    "filter_by_type" => "d",
);
$rl_params_override = array(
    "filter_by_usageoption" => null,
);
?>
<div class="RecordDownload" id="RecordDownloadSummary" style="margin-right:10px;">
<div class="RecordDownloadSpace">

<h2><?php echo $lang["usagehistory"] ?></h2>


<table cellpadding="0" cellspacing="0">
<tr>
    <td colspan=2>
        <a href="<?php echo generateURL($rl_url, $rl_params, $rl_params_override); ?>" onclick="return ModalLoad(this, true);"><?php echo LINK_CARET . $lang["usagetotal"]; ?></a>
    </td>
</tr>
<tr class="DownloadDBlend" >
<td><?php echo $lang["usagetotalno"] ?></td>
<td width="20%"><?php echo $total ?></th>		
</tr>
</table>
<?php if($total>0 && $download_usage && $usage['usageoption'] != '-1')	{ ?>
<table cellpadding="0" cellspacing="0">
<tr><td colspan=2><?php echo $lang["usagebreakdown"] ?></td></tr>
<?php foreach ($download_summary as $usage)
	{ 
	if (array_key_exists($usage["usageoption"],$download_usage_options))
		{
        $rl_params_override["filter_by_usageoption"] = $usage["usageoption"];
		?>
		<tr>
		<td>
            <a href="<?php echo generateURL($rl_url, $rl_params, $rl_params_override); ?>" onclick="return ModalLoad(this, true);"><?php echo LINK_CARET . htmlspecialchars($download_usage_options[$usage["usageoption"]]); ?></a>
        </td>
		<td width="20%"><?php echo $usage["c"]?></th>		
		</tr>
		<?php
		}
	}
?>
</table>
<?php } ?>
</div>
</div>