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
		}
	foreach (array("debuglog","memorycpu","database","sqllogtransactions", 'trackVars') as $section)
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
		<h2 onclick="SystemConsole<?php echo $section; ?>Load(-1); return false;" class="CollapsibleSectionHead collapsed expanded"><?php echo $lang["systemconsole" . $section]; ?></h2>
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

$sortby = getvalescaped("sortby","");
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

$filter = getval("filter","");

$results = array();
$actions = array();

switch ($callback)
	{
	case "debuglog":

		$debug_user = getval("debuguser","");
		$debug_expires = getval("debugexpires","");

		if ($debug_user != "" && $debug_expires != "")
			{
			include_once "../../include/debug_functions.php";
			create_debug_log_override($debug_user, $debug_expires);
			global $debug_log_override;
			unset ($debug_log_override);
			}

		$debug_user = sql_value("SELECT value FROM sysvars WHERE name='debug_override_user'", "");
		$debug_expires = sql_value("SELECT value FROM sysvars WHERE name='debug_override_expires'", "");

		if ($debug_expires != "")
			{
			$remaining_time = $debug_expires - time();
			if ($remaining_time < 0)
				{
				$remaining_time = 0;
				}
			}
		else
			{
			$remaining_time = 60;
			}

		?>
		<br />

		<input type="radio" value="" name="debugconsole<?php echo $callback; ?>control" <?php if($debug_log) { ?> checked="checked"<?php } ?> disabled="disabled"><?php echo $lang["systemconsoleonpermallusers"]; ?> <br />

		<input type="radio" value="-1" name="debugconsole<?php echo $callback; ?>control" <?php
		if(!$debug_log && ($debug_log_override && $debug_user == -1)) { ?> checked="checked"<?php }
		if($debug_log || $debug_log_override) { ?> disabled="disabled"<?php }
		?> onclick="SystemConsole<?php echo $callback; ?>Stop(); jQuery('#debugconsole<?php echo $callback; ?>').show();" ><?php echo $lang["systemconsoleonallusers"]; ?><br />

		<input type="radio" value="<?php echo $userref; ?>" name="debugconsole<?php echo $callback; ?>control" <?php
		if(!$debug_log && ($debug_log_override && $debug_user != -1)) { ?> checked="checked"<?php }
		if($debug_log || $debug_log_override) { ?> disabled="disabled"<?php }
		?> onclick="SystemConsole<?php echo $callback; ?>Stop(); jQuery('#debugconsole<?php echo $callback; ?>').show();"><?php echo $lang["on"]; ?> (<?php echo $username; ?>)<br />

		<input type="radio" value="" name="debugconsole<?php echo $callback; ?>control" <?php
		if(!$debug_log && !$debug_log_override) { ?> checked="checked"<?php }
		if($debug_log || !$debug_log_override) { ?> disabled="disabled"<?php }
		?> onclick="SystemConsoledebuglogLoad(-1,'&debuguser=-1&debugexpires=-1');"><?php echo $lang["off"]; ?><br />

		<div id="debugconsole<?php echo $callback; ?>" style="display: none;" >
		<?php if(!$debug_log && !$debug_log_override)
			{
			?><script>
				SystemConsole<?php echo $callback; ?>Stop();
			</script>
			<br /><?php echo $lang["systemconsoleturnoffafter"]; ?> <input id="duration" type="text" class="stdwidth" style="width: 50px; text-align: right;" name="duration" onchange="if (isNaN(value)) value=60;" value="60"> <?php echo $lang["seconds"]; ?>.<br />
			<br />
			<input type="button" value="Start" onclick="SystemConsoledebuglogLoad(-1,
				'&debuguser=' + jQuery('input[name=debugconsole<?php echo $callback; ?>control]:checked').val() +
				'&debugexpires=' +
				document.getElementById('duration').value);" />
			<input type="button" value="Cancel" onclick="SystemConsole<?php echo $callback; ?>Load(0)" />
			<?php
			}
			?>
		</div>
		<?php
		if(!$debug_log && $debug_log_override)
			{
			?><br />
			<?php echo $remaining_time; ?>s remaining &mdash; <a href="#" onclick="SystemConsoledebuglogLoad(-1);"><?php echo $lang['reload']; ?></a>.<br />
			<br />
			<input type="button" value="<?php echo $lang["stopbutton"]; ?>" onclick="SystemConsole<?php echo $callback; ?>Stop(); SystemConsole<?php echo $callback; ?>Load(-1,'&debuguser=-1&debugexpires=-1');" />
			<br />
			<?php
			}

		// ----- start of tail read
		if(!isset($debug_log_location))
			{
			$debug_log_location = get_debug_log_dir() . "/debug.txt";
			}
		if (file_exists($debug_log_location) && is_readable($debug_log_location))
			{
			$data = tail($debug_log_location,1000);
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
			echo $lang["systemconsoleondebuglognotsetorfound"];
			?><br />
			<?php
			}

		// ----- end of tail read

		break;

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
			if ($sortasc)
				{
				$order_by = " ORDER BY `{$sortby}` ASC";
				}
			else
				{
				$order_by = " ORDER BY `{$sortby}` DESC";
				}
			}

		if ($filter == "")
			{
			$results = sql_query("SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST" . $order_by);
			}
		else
			{
			$result_rows = sql_query("SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST" . $order_by);

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

        case 'trackVars':
            if(!checkperm('v'))
                {
                clear_tracking_vars_info([$userref]);
                break;
                }

            $track_vars = trim(getval('track_vars', ''));
            $track_var_duration = (int) getval('track_var_duration', 0);

            // Stop tracking variables if session expired
            $tracking_vars_session_active = is_tracking_vars_active($userref);
            if(!$tracking_vars_session_active)
                {
                clear_tracking_vars_info([$userref]);
                }

            // Start/Stop tracking variables in ResourceSpace
            if(getval('save', '') === '1' && $track_vars !== '' && is_int($track_var_duration))
                {
                set_sysvar("track_var_{$userref}", $track_vars);
                set_sysvar("track_var_{$userref}_duration", $track_var_duration);
                set_sysvar("track_var_{$userref}_start_datetime", date('Y-m-d H:i:s'));
                }
            else if(getval('cancel', '') === '1')
                {
                clear_tracking_vars_info([$userref]);
                }

            render_text_question(
                $lang['systemconsole_label_input_vars'],
                'track_vars',
                sprintf('<div id="help_track_vars" class="FormHelp" style="display: none;"><div class="FormHelpInner">%s</div></div>', htmlspecialchars($lang['systemconsole_help_track_vars'])),
                false,
                ' id="track_vars" class="stdwidth" onblur="HideHelp(\'track_vars\'); return false;" onfocus="ShowHelp(\'track_vars\'); return false;"',
                get_sysvar("track_var_{$userref}", '')
            );

            render_text_question(
                $lang['systemconsole_label_input_track_period'],
                'track_var_duration',
                sprintf('<div id="help_track_var_duration" class="FormHelp" style="display: none;"><div class="FormHelpInner">%s</div></div>', htmlspecialchars($lang['systemconsole_help_track_period'])),
                true,
                ' id="track_var_duration" class="stdwidth" min="0" onblur="HideHelp(\'track_var_duration\'); return false;" onfocus="ShowHelp(\'track_var_duration\'); return false;"',
                get_sysvar("track_var_{$userref}_duration", 0) ?? 0
            );

            ?>
            <div class="Question">
                <label for="submit">&nbsp;</label>
                <input type="submit"
                       name="save"
                       value="<?php echo htmlspecialchars($lang['save']); ?>"
                       onclick="SystemConsoletrackVarsLoad(-1, '&save=1&track_vars=' + encodeURIComponent(jQuery('#track_vars').val()) + '&track_var_duration=' + jQuery('#track_var_duration').val());">
                <input class="ClearSelectedButton"
                       type="submit"
                       name="cancel"
                       value="<?php echo htmlspecialchars($lang['cancel']); ?>"
                       onclick="SystemConsoletrackVarsLoad(-1, '&cancel=1');">
                <div class="clearerleft"></div>
            </div>
            <?php
            // ----- start of tail read
            $track_vars_dbg_log_path = $debug_log_location ?? get_debug_log_dir() . '/debug.txt';
            if(!$tracking_vars_session_active)
                {
                // DO NOT process the log file since tracking session expired
                }
            else if(file_exists($track_vars_dbg_log_path) && is_readable($track_vars_dbg_log_path))
                {
                $lines = preg_split('/' . PHP_EOL . '/', tail($track_vars_dbg_log_path, 1000));
                foreach($lines as $line)
                    {
                    $line = trim($line);
                    if($line === '' || strpos($line, 'tracking var:') === false)
                        {
                        continue;
                        }
                    // Remove the identifying string as it's just poluting the log entry from this point on
                    $line = str_replace('tracking var:', '', $line);

                    if($filter === '' || strpos($line, $filter) !== false)
                        {
                        $entry = [$lang['log'] => $line];

                        // Preprocess the line and extract out to their own columns common structured data (e.g PID, 
                        // RID - request ID, User and the place the tracking took place)
                        if(
                            preg_match_all('/(\w+)="([a-zA-Z0-9_@\/.\[\]-]+)"/', $line, $matches) !== false
                            && !empty($matches)
                            // safety checks (preg_match_all might ensure internally the below)
                            && isset($matches[0], $matches[1], $matches[2])
                            && count($matches[0]) === count($matches[1])
                            && count($matches[1]) === count($matches[2])
                        )
                            {
                            $entry = [];
                            // Iterate through param names in the structured data (for the data that we know about the event)
                            foreach($matches[1] as $idx => $param_name)
                                {
                                if(in_array($param_name, ['pid', 'rid', 'user', 'place']))
                                    {
                                    $entry[$param_name] = $matches[2][$idx];
                                    $line = str_replace($matches[0][$idx], '', $line);
                                    }
                                }
                            $line = preg_replace('/\[\s*\]/', '', $line);
                            $entry[$lang['log']] = $line;
                            }

                        array_push($results, $entry);
                        }
                    }
                }
            else
                {
                ?><br />
                <?php
                echo $lang["systemconsoleondebuglognotsetorfound"];
                ?><br />
                <?php
                }
            // ----- end of tail read
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
