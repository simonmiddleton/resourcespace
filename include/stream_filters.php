<?php
namespace ResourceSpace\Stream\Filter;

class find_in_log_file_tail extends \php_user_filter
    {
    private $fulldata;

    function onCreate()
        {
        $this->fulldata = '';
        return true;
        }

    function filter($in, $out, &$consumed, $closing)
        {
        while($bucket = stream_bucket_make_writeable($in)) 
            {
            $consumed += $bucket->datalen;
            // $this->fulldata .= $bucket->data;

            $lines = preg_split('/' . PHP_EOL . '/', $bucket->data);
            echo "<pre>";echo print_r($lines, true);echo "</pre>";
/*
            if(isset($lines[0]) && preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lines[0]) === 0)
                {
                $incomplete_data_bucket = stream_bucket_new($this->stream, $lines[0] . PHP_EOL);
                stream_bucket_prepend($out, $incomplete_data_bucket);
                echo "incomplete_data = {$lines[0]}<br>";
                }*/

            $filtered_data = '';
            foreach($lines as $line)
                {
                if(strpos($line, $this->params) === false)
                    {
                    continue;
                    }

                $filtered_data .= $line . PHP_EOL;
                }
            // $bucket->data = $filtered_data;
            // $consumed += mb_strlen($filtered_data);

            stream_bucket_append($out, $bucket);
            }

        return PSFS_PASS_ON;
        }
    }