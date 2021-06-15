<?php
namespace ResourceSpace\Stream\Filter;

class find_in_log_file_tail extends \php_user_filter
    {
    function filter($in, $out, &$consumed, $closing)
        {
        $search_needle = $this->params;
        while($bucket = stream_bucket_make_writeable($in)) 
            {
            $consumed += $bucket->datalen;

            // echo sprintf('<h2>filtering for "%s"</h2>', $search_needle);
            // echo sprintf('<br>bucket->data = "%s"', nl2br(PHP_EOL.$bucket->data));
            $lines = preg_split('/' . PHP_EOL . '/', $bucket->data);
            // echo "<pre>";echo print_r($lines, true);echo "</pre>";

            $incomplete_lines_data = '';
            $filtered_data = '';
            $found_complete_line = false;

            foreach($lines as $line)
                {
                if(preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line) === 1 && strpos($line, $search_needle) !== false)
                    {
                    $found_complete_line = true;
                    $filtered_data .= $line . PHP_EOL;
                    }
                else if(!$found_complete_line && preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line) === 0)
                    {
                    // echo sprintf('<br>incomplete data line = "%s"', $line);
                    $incomplete_lines_data .= $line . PHP_EOL;
                    // continue;
                    }
                }
            $bucket->data = $incomplete_lines_data . $filtered_data;
            // echo sprintf('<br>filtered_data = "%s"', nl2br(PHP_EOL.$filtered_data));

            stream_bucket_append($out, $bucket);
            }

        return PSFS_PASS_ON;
        }
    }