<?php

$same_page_callback = basename(__FILE__)==basename($_SERVER['PHP_SELF']);
$results_per_page = 20;

if ($same_page_callback)
	{
	include "../../include/db.php";
	include "../../include/authenticate.php";
	}


$callback = getval("callback","");
$actasuser = getval("actasuser","");
$offset = getval("offset",0,true);

if (!checkperm("a") && $callback!="activitylog")		// currently only activity log is allowed for callback
	{
	exit ("Permission denied.");
	}

$error = '';
include_once '../../include/stream_filters.php';
if(!stream_filter_register('resourcespace.tail_search', '\ResourceSpace\Stream\Filter\FindInFileTail'))
    {
    $error = str_replace('%FILTER_NAME', 'resourcespace.tail_search', $lang['error-unable_to_register_filter']);
    }

if (!checkperm_user_edit($userref))	// if not an admin then force act as user as current user
	{
	$actasuser=$userref;
	}

// ----- Main page load -----
if ($callback == "")
	{
	if ($same_page_callback)
		{
		include "../../include/header.php";
        render_top_page_error_style($error);
		}
    
    ?>
    <div class="BasicsBox">
        <h1><?php echo $lang["systemconsole"] ?></h1>
		<?php
		renderBreadcrumbs([
			['title' => $lang["systemsetup"], 'href'  => $baseurl_short . "pages/admin/admin_home.php", 'menu' => true],
			['title' => $lang["systemconsole"]]
		]);
		?>
    </div>    
    <?php

	foreach (array("memorycpu","database","sqllogtransactions") as $section)
	{
		?><script>
			var timeOutControl<?php echo $section; ?> = null;
			var sortBy<?php echo $section; ?> = "";
			var filter<?php echo $section; ?> = "";
			var refreshSecs<?php echo $section; ?> = 0;

			function SystemConsole<?php echo $section; ?>Load(refresh_secs, extra)
			{
				if (extra == undefined)
				{
					extra = "";
				}
				jQuery('#SystemConsole<?php echo $section; ?>').load('team_system_console.php?callback=<?php echo $section; ?>&sortby=' + encodeURIComponent(sortBy<?php echo $section;
				?>) + '&actasuser=<?php echo getval('actasuser',''); ?>&filter=' + encodeURIComponent(filter<?php echo $section; ?>) + extra);
				if (refresh_secs >= 0)
				{
					clearTimeout(timeOutControl<?php echo $section; ?>);
				}
				if (refresh_secs > 0)
				{
					timeOutControl<?php echo $section; ?> = setTimeout(SystemConsole<?php echo $section; ?>Load, refresh_secs * 1000, refresh_secs);
				}
				refreshSecs<?php echo $section; ?> = refresh_secs;
			}
			function SystemConsole<?php echo $section; ?>Stop()
			{
				clearTimeout(timeOutControl<?php echo $section; ?>);
				jQuery('#reload<?php echo $section ?>0').text('<?php echo $lang["reload"]; ?>');
				jQuery('.reload<?php echo $section; ?>class').css('text-decoration', 'none');
			}
		</script>
		<h2 onclick="SystemConsole<?php echo $section; ?>Load(-1); return false;" class="CollapsibleSectionHead collapsed"><?php echo $lang["systemconsole" . $section]; ?></h2>
		<div class="collapsiblesection">
			<?php foreach (array(0,1,5,10,30,60) as $secs)
				{
				?><a href="#" class="reload<?php echo $section; ?>class" id="reload<?php echo $section . $secs ?>" onclick="
					jQuery(this).siblings('a').css( 'text-decoration', 'none');
					if (this.id == 'reload<?php echo $section ?>0')
					{
						jQuery('#reload<?php echo $section ?>0').text('<?php echo $lang["reload"]; ?>');
					} else {
						jQuery(this).css('text-decoration', 'underline');
						jQuery('#reload<?php echo $section ?>0').text('<?php echo $lang["pause"]; ?>');
					}
					SystemConsole<?php echo $section; ?>Load(<?php echo $secs; ?>)"><?php
						echo ($secs == 0 ? $lang['reload'] : "{$secs}s");
					?></a> <?php
				}
			?>			
			<div id="SystemConsole<?php echo $section; ?>">
			</div>
		</div>
	<?php
	}
	?><script>
		registerCollapsibleSections();
	</script>
	<?php
	include "../../include/footer.php";
	return;
	}

// ----- Callbacks -----

$sortby = getval("sortby","");
$sortasc = true;
$sorted = false;

if(strlen($sortby) > 1)
	{
	if ($sortby[0] == "-")
		{
		$sortby = substr($sortby,1);
		$sortasc = false;
		}
	}

$filters = [];
$filter = trim(getval('filter', ''));
if($filter !== '')
    {
    $filters = [
        [
            'name' => 'resourcespace.tail_search',
            'params' => [
                'search_terms' => [$filter]
            ]
        ],
    ];
    }

$results = array();
$actions = array();

switch ($callback)
	{
	case "memorycpu":

		if ($config_windows)		// Windows (tasklist command)
			{
			$lines = run_command("tasklist /v /fo csv");
			$lines = explode("\n", $lines);
			if (is_array($lines) && count($lines) > 1)
				{
				$headings = str_getcsv($lines[0]);
				for ($i = 1; $i < count($lines); $i++)
					{
					$fields = str_getcsv($lines[$i]);
					if (count($fields)!=count($headings))
						{
						continue;
						}
					$filtermatch = false;
					$result = array();
					for ($y = 0; $y < count($fields); $y++)
						{
						$filtermatch = ($filtermatch || $filter == "" || stripos($fields[$y],$filter)!==false);
						$result[$headings[$y]] = $fields[$y];
						}
					if ($filtermatch)
						{
						array_push($results, $result);
						}
					}
				}
			else
				{
				?><p><?php echo $lang["systemconsoleonfailedtasklistcommand"]; ?></p><?php
				}
			}
		else		// UNIX (top command)
			{
			$lines = run_command("top -b -n 1",true);
			$lines = explode("\n", $lines);
			
			if (is_array($lines) && count($lines) > 6)		// need to burn the leading 6 lines of the top command
				{
				$headings = preg_split('/\s+/',$lines[6]);
				array_shift($headings);
				array_pop($headings);

				for ($i = 7; $i < count($lines); $i++)
					{
					$fields = preg_split('/\s+/',$lines[$i]);
					array_shift($fields);
					array_pop($fields);
					if (count($fields)!=count($headings))
						{
						continue;
						}
					$filtermatch = false;
					$result = array();
					for ($y = 0; $y < count($fields); $y++)
						{
						$filtermatch = ($filtermatch || $filter == "" || stripos($fields[$y],$filter)!==false);
						$result[$headings[$y]] = $fields[$y];
						}
					if ($filtermatch)
						{
						array_push($results, $result);
						}
					}
				}
			else
				{
				?><p><?php echo $lang["systemconsoleonfailedtopcommand"]; ?></p><?php
				}
			}

		break;

	case "database":

		$order_by = "";
		if ($sortby)
			{
            //Checking if the sort by is a valid column from the table;
            $fields = ps_query('DESCRIBE INFORMATION_SCHEMA.PROCESSLIST');
            foreach($fields as $field)
                {
                if(strtolower($sortby) == strtolower($field['Field']))
                    {
                    if ($sortasc)
                        {
                        $order_by = " ORDER BY `{$sortby}` ASC";
                        }
                    else
                        {
                        $order_by = " ORDER BY `{$sortby}` DESC";
                        }
                    break;
                    }
                }
			}

		if ($filter == "")
			{
			$results = ps_query("SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST" . $order_by); // select * is fine here as no parameters
			}
		else
			{
			$result_rows = ps_query("SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST" . $order_by); // select * is fine here as no parameters

			foreach ($result_rows as $row)
				{
				foreach ($row as $cell)
					{
					if (stripos($cell,$filter)!==false)
						{
						array_push($results, $row);
						break;
						}
					}
				}
			}

		// $actions = array("Kill" => "stuff"); /* for future implementation */
		$sorted = true;
		break;

	case "sqllogtransactions":

		if (isset($mysql_log_transactions) && isset($mysql_log_location) && file_exists($mysql_log_location) && is_readable($mysql_log_location))
			{
			$data = tail($mysql_log_location,1000);
			$lines = array();
			foreach (preg_split('/\n/',$data) as $line)
				{
				$line = trim($line);
				if ($line == "")
					{
					continue;
					}
				array_push($lines, $line);
				}
			for ($i=count($lines)-1; $i >= 0; $i--)
				{
				if ($filter == "" || stripos($lines[$i],$filter)!==false)
					{
					$entry = array("Tail" => count($lines)-$i, "Line" => $lines[$i]);
					array_push ($results, $entry);
					}
				}
			}
		else
			{
			?><br />
			<?php
			echo $lang["systemconsoleonsqllognotsetorfound"];
			?><br />
			<?php
			}

		break;
	} // end of callback switch

	if($same_page_callback)	// do not display any filters if page being directly included
		{

		?>
			<br/>
			<input type="text" class="stdwidth" placeholder="<?php echo $lang["filterbutton"]; ?>"
				   value="<?php echo $filter; ?>"
				   onblur="SystemConsole<?php echo $callback; ?>Stop();"
				   onkeyup="if(this.value=='')
					   {
					   jQuery('#filterbutton<?php echo $callback; ?>').attr('disabled','disabled');
					   jQuery('#clearbutton<?php echo $callback; ?>').attr('disabled','disabled')
					   } else {
					   jQuery('#filterbutton<?php echo $callback; ?>').removeAttr('disabled');
					   jQuery('#clearbutton<?php echo $callback; ?>').removeAttr('disabled')
					   }
					   filter<?php echo $callback; ?>=this.value;
					   var e = event;
					   if (e.keyCode === 13)
					   {
					   SystemConsole<?php echo $callback; ?>Load(refreshSecs<?php echo $callback; ?>);
					   }"></input>

			<input id="filterbutton<?php echo $callback; ?>" <?php if ($filter == "") { ?>disabled="disabled"
				   <?php } ?>type="button"
				   onclick="SystemConsole<?php echo $callback; ?>Load(refreshSecs<?php echo $callback; ?>);"
				   value="<?php echo $lang['filterbutton']; ?>"></input>
			<input id="clearbutton<?php echo $callback; ?>" <?php if ($filter == "") { ?>disabled="disabled"
				   <?php } ?>type="button"
				   onclick="filter<?php echo $callback; ?>=''; SystemConsole<?php echo $callback; ?>Load(refreshSecs<?php echo $callback; ?>);"
				   value="<?php echo $lang["clearbutton"]; ?>"></input>

		<?php
		}

if (count($results)==0)
	{
	?><br /><?php echo $lang["nothing-to-display"]; ?><br /><br />
	<?php
	return;
	}

if (!$sorted && $sortby)
	{
	usort($results, function ($a, $b)
		{
		global $sortby, $sortasc;
		if ($a[$sortby] == $b[$sortby])
			return 0;
		if ($sortasc)
			{
			return ($a[$sortby] < $b[$sortby]) ? -1 : 1;
			}
		else
			{
			return ($a[$sortby] > $b[$sortby]) ? -1 : 1;
			}
		});
	}
?><div class="Listview">
	<?php
	if ($same_page_callback)
		{
	?><strong><?php echo $lang['total'] . ': ' . count($results); ?></strong>
        <?php
		}
	?>
	<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle">
		<tbody>
			<tr class="ListviewTitleStyle">
				<?php
				foreach (array_keys($results[0]) as $heading)
					{
					?>
					<td><a href="#" onclick="sortBy<?php echo $callback; ?>='<?php
					if ($sortby == $heading && $sortasc)
						{
						?>-<?php echo $heading;
						$span = "ASC";
						}
					elseif ($sortby == $heading && !$sortasc)
						{
						$span = "DESC";
						}
					else
						{
						echo $heading;
						$span = "";
						}
					?>'; SystemConsole<?php echo $callback; ?>Load(-1);"><?php
						echo $heading;
						if ($span != "")
							{
							?><span class="<?php echo $span; ?>"></span><?php
							}
						?></a></td><?php
					}
				if (count($actions) > 0)
					{
					?><td><div class="ListTools">Tools</div></td><?php
					}
				?>
			</tr>
		</tbody>
		<tbody id="resource_type_field_table_body" class="ui-sortable">
			<?php			
			for ($i=0; $i<count($results) && $i<$results_per_page; $i++)
				{				
				?>
				<tr class="resource_type_field_row">
					<?php
					foreach ($results[$i] as $key=>$cell)
						{
						?><td><?php

							$close_anchor=false;
							if(
								$key==$lang['property-table_reference'] &&
								isset($results[$i][$lang['property-table']]) &&
								$results[$i][$lang['property-table']]=='resource' &&
								isset($results[$i][$lang['property-column']]) &&
								$results[$i][$lang['property-column']]=='ref' &&
								$cell!='' &&
								$cell > 0
							)
								{
								?><a href="<?php echo $baseurl; ?>/pages/view.php?ref=<?php echo $cell; ?>" onclick="return ModalLoad(this,true);"><?php
								$close_anchor=true;
								}

							echo htmlspecialchars($cell);

							if ($close_anchor)
								{
								?></a><?php
								}
						?></td><?php
						}
					?>
					<?php
					if (count($actions) > 0)
					{
					?>
						<td>
							<div class="ListTools">
								<?php
								foreach ($actions as $title => $action)
									{
									?>&nbsp;<a href="#"><?php echo LINK_CARET ?><?php echo $title; ?></a><?php
									}
								?>
							</div>
						</td><?php
					}
					?>
				</tr>
				<?php
				}
			?>
		</tbody>
	</table>	
</div>
