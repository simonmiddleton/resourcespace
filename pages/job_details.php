<?php
include '../include/boot.php';
include '../include/authenticate.php';

if (!checkperm('a')) {
    error_alert($lang["error-permissiondenied"], false, 401);
    exit();
}

$job = getval("job", 0, true);
$job_details = job_queue_get_job($job);
$hide_data = array("failure_text","success_text");

$log_file_path      = get_job_queue_log_path($job_details["type"], $job_details["job_code"], $job_details["ref"]);
$log_file_streaming = false;
$log_file_download  = false;

if (file_exists($log_file_path)) {
    if ($job_details["status"] == STATUS_INPROGRESS) {
        $log_file_streaming = true;
    } else {
        $log_file_download = true;
    }

    var_dump(get_job_progress_from_log_file($log_file_path));

}

if (!is_array($job_details) || count($job_details) == 0) {
    exit("Invalid job reference");
}

?>
<div class="RecordBox">
    <div class="RecordPanel">
        <div class="RecordHeader">
            <div class="backtoresults"> 
                <a href="#" onclick="ModalClose();" class="closeLink fa fa-times" title="<?php echo escape($lang["close"]); ?>"></a>
            </div>
            <h1><?php echo escape($lang["job_text"] . " #" . $job_details["ref"]); ?></h1>

            <?php

            if ($log_file_streaming) {
            ?>
            <h2 class="CollapsibleSectionHead collapsed" onclick="UICenterScrollBottom();" id="StreamLogSectionHead">Stream log file</h2>
<div class="CollapsibleSection" id="StreamLogSection" style="">


<style>
  #log {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    white-space: pre-wrap;
    background: #111;
    color: #ddd;
    padding: 1rem;
    height: 25vh;
    overflow-y: auto;
  }
  .line { display: block; }
  .error { color: #f66; }
  .info { color: #6f6; }
</style>

<div id="log"></div>

<script>
  const logEl  = document.getElementById('log');
  let offset   = 0;
  let polling  = false;
  let errorCnt = 0;

  function appendLine(text, cls) {
    const div = document.createElement('div');
    div.className = 'line' + (cls ? ' ' + cls : '');
    div.textContent = text;
    const atBottom = logEl.scrollHeight - logEl.scrollTop - logEl.clientHeight < 80;
    logEl.appendChild(div);
    if (atBottom) logEl.scrollTop = logEl.scrollHeight;
  }

  async function pollLog(initial = false) {
    if (polling) return;
    polling = true;

    const params = new URLSearchParams({
      job: <?php echo $job_details["ref"]; ?>,
      type: "stream",
      offset: String(offset),
    });
    if (initial) params.set('lines', '200'); // like tail -n 200

    try {
      const res = await fetch('ajax/job_log_file.php?' + params.toString(), {
        cache: 'no-store'
      });
      if (!res.ok) {
        appendLine(`HTTP ${res.status}`, 'error');
        return;
      }

      const data = await res.json();

      if (data.error) {
        appendLine(`${data.error}`, 'error');
        return;
      }

      if (data.rotated) {
        appendLine('Log file rotated or truncated; restarting from beginning', 'info');
      }

      if (Array.isArray(data.lines)) {
        for (const line of data.lines) {
          appendLine(line);
        }
      }

      if (typeof data.offset === 'number') {
        offset = data.offset;
      }

      if (data.done === true) {
        appendLine(`Job status: ${data.status}; stopping updates`, 'info');
        if (timerId !== null) {
          clearInterval(timerId);
          timerId = null;
        }
      }

      errorCnt = 0; // reset on success

    } catch (e) {
      errorCnt++;
      appendLine(`Request failed (${errorCnt}): ${e}`, 'error');
    } finally {
      polling = false;
    }
  }

  // Initial load with backlog
  pollLog(true);

  // Poll every second (adjust if needed)
  timerId = setInterval(() => pollLog(false), 1000);
</script>
</div>


            <?php

            } elseif ($log_file_download) {
                echo $log_file_path;
            }

            ?>
        </div>
    </div>

    <div class="BasicsBox">
        <div class="Listview">
            <table class="ListviewStyle">
                <tr class="ListviewTitleStyle">
                    <th><?php echo escape($lang["job_data"]); ?></th>
                    <th><?php echo escape($lang["job_value"]); ?></th>
                </tr>
                <?php

                $job_status = $job_details["status"];
                $job_details["status"] = isset($lang["job_status_" . $job_status]) ? $lang["job_status_" . $job_status] : $job_status;

                $job_user = $job_details["user"];
                $job_username = $job_details["username"];
                $job_fullname = $job_details["fullname"];

                unset($job_details["user"], $job_details["username"], $job_details["fullname"]);

                $job_details["user"] = "$job_user - $job_username";

                if ($job_fullname != "") {
                    $job_details["user"] .= " ($job_fullname)";
                }

                foreach ($job_details as $name => $value) {
                    if (in_array($name, $hide_data)) {
                        continue;
                    }

                    echo "<tr><td width='50%'>";
                    echo escape(ucwords(str_replace("_", " ", $name)));
                    echo "</td><td width='50%'>";
                    
                    if ($name == "job_data") {
                        $job_data = json_decode($value, true);
                        foreach ($job_data as $job_data_name => &$job_data_value) {
                            if (is_array($job_data_value) && count($job_data_value) > 100) {
                                $job_data_short = array();
                                $job_data_count = count($job_data_value);
                                $job_data_short[$job_data_name] = array_slice($job_data_value, 0, 10);
                                $job_data_short["(additional elements)"] = $job_data_count . " total elements";
                                $job_data_value = $job_data_short;
                            } elseif (is_string($job_data_value) && strlen($job_data_value) > 100) {
                                // If a job data element is e.g. a search result set it can be very large
                                $job_data_value = mb_strcut($job_data_value, 0, 100);
                            }
                        }
                        render_array_in_table_cells($job_data);
                    } elseif ($name == "progress") {
                        echo "REFRESH BUTTON HERE <span class='fa fa-fw fa-refresh'></span>";
                    } elseif ($name == "priority") {
                        switch ($value) {
                            case JOB_PRIORITY_IMMEDIATE:
                                $priorityicon = "fas fa-fw fa-bolt";
                                $prioritytitle = $lang["job_priority_immediate"];
                            break;

                            case JOB_PRIORITY_USER:
                                $priorityicon = "fa fa-fw fa-arrow-circle-up";
                                $prioritytitle = $lang["job_priority_user"];
                            break;

                            case JOB_PRIORITY_SYSTEM:
                                $priorityicon = "fa fa-fw fa-arrow-circle-right";
                                $prioritytitle = $lang["job_priority_system"];
                            break;

                            case JOB_PRIORITY_COMPLETED:
                            default:
                                $priorityicon = "fa fa-fw fa-arrow-circle-down";
                                $prioritytitle = $lang["job_priority_completed"];
                            break;
                        }
                        echo "<span class='" . $priorityicon . "' title='" . escape($prioritytitle) . "'></span>"  . escape($prioritytitle);
                    } else {
                        echo escape($value);
                    }
                    echo "</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>