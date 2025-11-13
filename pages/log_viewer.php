<!doctype html>
<meta charset="utf-8" />
<title>Live log</title>
<style>
  body { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  #log { white-space: pre-wrap; background: #111; color: #ddd; padding: 1rem; height: 70vh; overflow: auto; }
  .line { display: block; }
</style>

<div id="log"></div>
<script>
  const el = document.getElementById('log');
  const es = new EventSource('job_log_file.php?file=/var/www/html/trunk2/filestore/tmp/offline_job_logs/dummy_job/156_dummy_job_4bb59ce8063fe99c77187e.log&lines=200');

  const append = (txt) => {
    const div = document.createElement('div');
    div.className = 'line';
    div.textContent = txt;
    el.appendChild(div);
    // autoscroll if near bottom
    if (el.scrollHeight - el.scrollTop - el.clientHeight < 80) {
      el.scrollTop = el.scrollHeight;
    }
  };

  es.addEventListener('log', (e) => append(e.data));
  es.addEventListener('info', (e) => append(`[info] ${e.data}`));
  es.onerror = () => append('[error] connection issue; retrying…');

  // Optional: stop
  // setTimeout(() => es.close(), 30 * 60 * 1000);
</script>
