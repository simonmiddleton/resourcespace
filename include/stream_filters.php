<?php
namespace ResourceSpace\Stream\Filter;

class find_in_log_file_tail extends \php_user_filter
    {
    function filter($in, $out, &$consumed, $closing)
        {
        while($bucket = stream_bucket_make_writeable($in)) 
            {
            $consumed += $bucket->datalen;

            $lines = preg_split('/' . PHP_EOL . '/', $bucket->data);
            echo "<pre>";echo print_r($lines, true);echo "</pre>";

            $filtered_data = '';

            if(isset($lines[0]) && preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lines[0]) === 0)
                {
                $filtered_data .= $lines[0] . PHP_EOL;
                }

            foreach($lines as $line)
                {
                if(strpos($line, $this->params) === false)
                    {
                    continue;
                    }

                $filtered_data .= $line . PHP_EOL;
                }
            $bucket->data = $filtered_data;

            stream_bucket_append($out, $bucket);
            }

        return PSFS_PASS_ON;
        }
    }