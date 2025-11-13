<?php

include '../include/boot.php';
include '../include/authenticate.php';

$job        = getval("job", 0, true);
$type       = getval("type", "stream");
//$log_lines  = getval("lines", 50, true);

$log_path = get_job_queue_log_path_by_ref($job);

// TODO PERMISSIONS CHECKS!

if (!is_file($log_path) || !is_readable($log_path)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Invalid log file.";
    exit;
}

if ($type == "progress") {
    $progress = get_job_progress_from_log_file($log_path, "/\[PROGRESS\]/i");

    if ($progress["found"] !== null) {
        $output = $progress["found"];
    } elseif ($progress["last_line"] !== null) {
        $output = $progress["last_line"];
    } else {
        $output = "";
    }

    header('Content-Type: text/plain');
    echo $output;
    exit;

}

$job_details = job_queue_get_job($job);

$job_status = isset($lang["job_status_" . $job_details['status']]) ? $lang["job_status_" . $job_details['status']] : $job_details['status'];
$job_done = ($job_details['status'] !== 3);



// -------- config -------------------------------------------------------------
$logPath    = $log_path;
$offset     = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$backlog    = isset($_GET['lines'])  ? max(0, (int)$_GET['lines'])  : 0;

// -------- helpers ------------------------------------------------------------
function tail_last_lines(string $path, int $lines = 50, int $chunk = 4096): array 
{
    if ($lines <= 0) return [];
    $f = fopen($path, 'rb');
    if (!$f) return [];
    fseek($f, 0, SEEK_END);
    $pos     = ftell($f);
    $buffer  = '';
    $lineCnt = 0;

    while ($pos > 0 && $lineCnt <= $lines) {
        $seek = max($pos - $chunk, 0);
        $read = $pos - $seek;
        fseek($f, $seek);
        $buffer = fread($f, $read) . $buffer;
        $pos    = $seek;
        $lineCnt = substr_count($buffer, "\n");
        if ($seek === 0) break;
    }
    fclose($f);

    $all = explode("\n", rtrim($buffer, "\n"));
    return array_slice($all, -$lines);
}


// -------- core logic ---------------------------------------------------------
clearstatcache(true, $log_path);
$size  = filesize($log_path);
$inode = fileinode($log_path);
$rotated = false;

if ($size === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Cannot stat log file']);
    exit;
}

// handle truncate/rotation: our offset is past EOF
if ($offset > $size) {
    $offset = 0;
    $rotated = true;
}

$lines = [];
$newOffset = $offset;

// initial load with backlog (like tail)
if ($offset === 0 && $backlog > 0) {
    $lines = tail_last_lines($log_path, $backlog);
    $newOffset = $size;
} else {
    // read from offset to EOF
    $fp = fopen($log_path, 'rb');
    if ($fp) {
        fseek($fp, $offset);
        $chunk = '';
        if ($size > $offset) {
            $chunk = fread($fp, $size - $offset);
        }
        $newOffset = ftell($fp);
        fclose($fp);

        if ($chunk !== '') {
            // normalize line endings, split into lines
            $chunk = str_replace(["\r\n", "\r"], "\n", $chunk);
            $parts = explode("\n", $chunk);

            // if file did not end with newline, last element is partial;
            // send it anyway; it will get completed next poll.
            foreach ($parts as $line) {
                if ($line === '') continue;
                $lines[] = $line;
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'offset'  => $newOffset,
    'inode'   => $inode,
    'rotated' => $rotated,
    'lines'   => $lines,
    'status'  => $job_status,
    'done'    => $job_done,
]);
