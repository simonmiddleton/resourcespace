<?php
/**
 * Report creation page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php";if (getval('unsubscribe', '') == '' && !checkperm("t")) {exit ("Permission denied.");}
include "../../include/reporting_functions.php";

set_time_limit(0);
$report=getval("report","");
$show_date_field = true;
if ($report != "")
    {
    $show_date_field = report_has_date_by_id($report);
    }
$period=getval("period",$reporting_periods_default[0]);
$period_init=$period;
$backurl = getval('backurl', '');
$backurl_path = parse_url($backurl, PHP_URL_PATH);
$backurl_query = parse_url($backurl, PHP_URL_QUERY);

if ($period==0)
	{
	# Specific number of days specified.
	$period=getval("period_days","");
	if (!is_numeric($period) || $period<1) {$period=1;} # Invalid period specified.
	}

if ($period==-1)
	{
	# Specific date range specified.
	$from_y = getval("from-y","");
	$from_m = getval("from-m","");
	$from_d = getval("from-d","");
	
	$to_y = getval("to-y","");
	$to_m = getval("to-m","");
	$to_d = getval("to-d","");
	}
else
	{
	# Work out the from and to range based on the provided period in days.
	$start=time()-(60*60*24*$period);

	$from_y = date("Y",$start);
	$from_m = date("m",$start);
	$from_d = date("d",$start);
		
	$to_y = date("Y");
	$to_m = date("m");
	$to_d = date("d");
	}
	
$from=getval("from","");
$to=getval("to","");
$output="";

$search_params = [];
$run_report_on_search_results = false;
if("{$baseurl_short}pages/search.php" === $backurl_path)
    {
    $run_report_on_search_results = true;
    parse_str($backurl_query, $search_params);
    }


# Execute report.
if ($report!="" && (getval("createemail","")==""))
	{
	$download=getval("download","")!="";
	$output=do_report($report, $from_y, $from_m, $from_d, $to_y, $to_m, $to_d, $download, false, false, $search_params);
	}

include "../../include/header.php";	

if (getval('createemail', '') != '' && enforcePostRequest(getval("ajax", false)))
	{
	if ($report!="") 
		{
		$report_receiver      = getval('report_receiver', '');
		$user_group_selection = array();
	
		switch($report_receiver)
			{
			case 'specific_user_groups':
				$user_group_selection = getval('user_group_selection', array());
				break;
			}
	
		# Create a new periodic e-mail report
		create_periodic_email($userref, $report, $period, getval('email_days', ''), $user_group_selection, $search_params);
		?>
		<script type="text/javascript">
		alert("<?php echo $lang["newemailreportcreated"] ?>");
		</script>
		<?php
	
		}
	else 
		{
		?>
		<script type="text/javascript">
		alert("<?php echo $lang["report-select-required"] ?>");
		</script>
		<?php
		}
	}

$delete = getval('delete', '');
if($delete != '')
	{
	if('yes' == getval('delete_confirmed', '') && enforcePostRequest(getval("ajax", false)))
		{
		delete_periodic_report($delete);
		?>
		<div class="BasicsBox">
			<h1><?php echo $lang['deleted']?></h1>
			<p><?php echo $lang['report_periodic_email_deletion_confirmed']?></p>
		</div>
		<?php
		}
	else
		{
		?>
		<div class="BasicsBox">
			<h2><?php echo $lang['report_periodic_email_delete_title']; ?></h2>
			<form method="post" action="<?php echo $baseurl_short; ?>pages/team/team_report.php?delete=<?php echo urlencode($delete); ?>">
                <?php generateFormToken("delete_periodic_report"); ?>
				<div class="Question">
					<label for="delete_confirmed"><?php echo $lang['report_periodic_email_delete_confirmation']; ?></label>
					<input id="delete_confirmed" type="checkbox" name="delete_confirmed" value="yes" />
					<div class="clearerleft"></div>
				</div>
				<div class="QuestionSubmit">
					<label for="buttons"> </label>
					<input name="save" type="submit" value="<?php echo $lang['comments_submit-button-label']; ?>" />
				</div>
			</form>
		</div>
		<?php
		}

	include '../../include/footer.php';
	exit();
	}
	
$unsubscribe = getval('unsubscribe', '');
if($unsubscribe != '')
	{
	if('yes' == getval('unsubscription_confirmed', '') && enforcePostRequest(getval("ajax", false)))
		{
		unsubscribe_user_from_periodic_report($userref, $unsubscribe);
		?>
		<div class="BasicsBox">
			<h1><?php echo $lang["unsubscribed"]?></h1>
			<p><?php echo $lang["youhaveunsubscribedreport"]?></p>
		</div>
		<?php
		}
	else
		{
		?>
		<div class="BasicsBox">
			<h2><?php echo $lang['report_periodic_email_unsubscribe_title']; ?></h2>
			<form method="post" action="<?php echo $baseurl_short; ?>pages/team/team_report.php?unsubscribe=<?php echo urlencode($unsubscribe); ?>">
                <?php generateFormToken("unsubscribe_user_from_periodic_report"); ?>
				<div class="Question">
					<label for="unsubscription_confirmed"><?php echo $lang['report_periodic_email_unsubscribe_confirmation']; ?></label>
					<input id="unsubscription_confirmed" type="checkbox" name="unsubscription_confirmed" value="yes" />
					<div class="clearerleft"></div>
				</div>
				<div class="QuestionSubmit">
					<label for="buttons"> </label>
					<input name="save" type="submit" value="<?php echo $lang['comments_submit-button-label']; ?>" />
				</div>
			</form>
		</div>
		<?php
		}
	}
else
	{
	# Normal behaviour.
    ?>
<div class="BasicsBox">
    <h1><?php echo $lang['viewreports']; ?></h1>
	<?php
	if($run_report_on_search_results)
        {
        $links_trail = [
            [
                'title' => $lang['searchresults'],
                'href'  => generateURL("{$baseurl_short}pages/search.php", $search_params),
            ],
        ];
        }
    else if (mb_strpos($backurl, "pages/admin/admin_report_management.php") !== false)
	    {
	    // Arrived from Manage reports page
	    $links_trail = array(
	        array(
	            'title' => $lang["systemsetup"],
	        	'href'  => $baseurl_short . "pages/admin/admin_home.php",
				'menu' =>  true
	        ),
	        array(
	            'title' => $lang["page-title_report_management"],
	            'href'  => $baseurl_short . "pages/admin/admin_report_management.php"
	        )
	    );
	    }
	else
		{
		$links_trail = array(
	        array(
	            'title' => $lang["teamcentre"],
                'href'  => $baseurl_short . "pages/team/team_home.php",
				'menu' =>  true
	        )
		);
		}

	$links_trail[] = ['title' => $lang['viewreports']];
	renderBreadcrumbs($links_trail);

    $reports = get_reports();
    $report_options = [];
    foreach($reports as $report_opt)
    {
    // Filter out reports not valid for the context you're in:
    // - if running report on search results, then drop the ones that don't have support for non-correlated SQL
    // - if viewing reports normally (from Admin), then remove the ones that support search results
    if($run_report_on_search_results != $report_opt['support_non_correlated_sql'])
        {
        continue;
        }
    $report_options[] = $report_opt;
    }
    $error = (empty($report_options) ? $lang['report_error_no_reports_supporting_search_results'] : '');
	?>
 	<p><?php echo text("introtext");render_help_link('resourceadmin/reports-and-statistics');?></p>
<?php render_top_page_error_style($error); ?>
<form method="post" action="<?php echo $baseurl ?>/pages/team/team_report.php" onSubmit="if (!do_download) {return CentralSpacePost(this);}">
    <?php generateFormToken("team_report"); ?>
    <input type="hidden" name="backurl" value="<?php echo htmlspecialchars($backurl); ?>">
<div class="Question">
    <script>
    function show_hide_date()
        {
        reports = document.getElementById('report');
        selected_report = reports.options[reports.selectedIndex];
        show_date = selected_report.dataset.contains_date;
        if (show_date == 0)
            {
            document.getElementById('date_period').style.display='none';
            }
        else
            {
            document.getElementById('date_period').style.display='block';
            }
        }
    </script>
<label for="report"><?php echo $lang["viewreport"]?></label>
<select id="report" name="report" class="stdwidth" onchange="show_hide_date();">
    <option value="" selected disabled hidden><?php echo $lang['select']; ?></option>
<?php
foreach($report_options as $report_opt)
    {
    echo sprintf(
        '<option value="%s" data-contains_date=%d %s>%s</option>',
        $report_opt['ref'],
        ($report_opt['contains_date'] == true ? 1 : 0),
        ($report_opt['ref'] == $report ? ' selected' : ''),
        htmlspecialchars($report_opt['name']));
    }
	?>
</select>
<div class="clearerleft"> </div>
</div>

<?php include "../../include/date_range_selector.php" ?>

<?php if (!$show_date_field)
    {
    ?>
    <script>
        document.getElementById('date_period').style.display='none';
    </script>
    <?php
    } ?>

<!-- E-mail Me function -->
<div id="EmailMe" <?php if ($period_init==-1) { ?>style="display:none;"<?php } ?>>
	<div class="Question">
		<label for="email"><?php echo $lang['emailperiodically']; ?></label>
		<input type="checkbox" onClick="
		if (this.checked)
			{
			document.getElementById('EmailSetup').style.display='block';
			
			// Copy reporting period to e-mail period
			if (document.getElementById('period').value==0)
				{
				// Copy from specific day box
				document.getElementById('email_days').value=document.getElementById('period_days').value;
				}
			else
				{
				document.getElementById('email_days').value=document.getElementById('period').value;		
				}
			}
		else
			{
			document.getElementById('EmailSetup').style.display='none';
			}
			">
		<div class="clearerleft"></div>
	</div>

	<div id="EmailSetup" style="display:none;">
		<!-- E-mail Period select -->
		<div class="Question">
			<label for="email_days"></label>
			<div class="Fixed" style="width: 400px;">
			<?php
			$textbox="<input type=\"text\" id=\"email_days\" name=\"email_days\" size=\"4\" value=\"7\">";
			echo str_replace("?",$textbox,$lang["emaileveryndays"]);
			?>
	       <br />
	       <br />
	       <label for="report_for_me_only">
				<input id="report_for_me_only" type="radio" name="report_receiver" value="user_only" onClick="document.getElementById('user_group_selection').style.display = 'none';" checked /> <?php echo $lang['report_periodic_email_option_me']; ?>
	       </label>
	       <?php
			if (checkperm('m'))
				{
				?>
				<br />
				<label for="selected_user_groups">
					<input id="selected_user_groups" type="radio" name="report_receiver" value="specific_user_groups" onClick="document.getElementById('user_group_selection').style.display = 'block';" /> <?php echo $lang['report_periodic_email_option_selected_user_groups']; ?>
				</label>
				<?php
				render_user_group_multi_select('user_group_selection', array(), 10, 'display: none;');
				}
			?>
			<div class="clearerleft"></div>
			<br />
			<input name="createemail" type="submit" onClick="do_download=true;" value="&nbsp;&nbsp;<?php echo $lang["create"] ?>&nbsp;&nbsp;" />
			</div>
			<div class="clearerleft"></div>
		</div>
		<!-- End of E-mail Period Select -->
	</div><!-- End of EmailSetup -->
</div>
<!-- End of E-mail Me function -->

<?php hook('customreportform', '', array($report)); ?>

<script language="text/javascript">
var do_download=false;
</script>


<div class="QuestionSubmit" id="SubmitBlock">
<label for="buttons"> </label>			
<input name="save" type="submit" onClick="do_download=false;" value="&nbsp;&nbsp;<?php echo $lang["viewreport"] ?>&nbsp;&nbsp;" />
<input name="download" type="submit" onClick="do_download=true;" value="&nbsp;&nbsp;<?php echo $lang["downloadreport"] ?>&nbsp;&nbsp;" />
</div>
</form>

<?php echo $output; ?>

</div>
<?php
}
include "../../include/footer.php";