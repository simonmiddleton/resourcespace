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

            // TODO: need a better way to split out proper complete lines from the first incomplete ones


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

class filter_test extends \php_user_filter
    {
    const MAX_BUCKETS_DATA = 3;

    private $i = 0; # used for debugging only
    private $pattern;
    private $data = '';
    private $data_queue = [];
    private $filtered_data = '';

    public function onCreate()
        {
        if(is_array($this->params) && !empty($this->params))
            {
            // Prepare the pattern so that each full line will match all searched terms
            $pattern = '';
            foreach($this->params['search_terms'] as $st)
                {
                if(trim($st) !== '')
                    {
                    $pattern .= sprintf('(?=.*?\b%s\b)', preg_quote($st));
                    }
                }

            if($pattern !== '')
                {
                $this->pattern = sprintf('/^%s.*$/m', $pattern);
                }
            }

        return true;
        }


    public function filter($in, $out, &$consumed, $closing)
        {
        while($bucket = stream_bucket_make_writeable($in))
            {
            ++$this->i;
            // echo sprintf('<h2>Filter %s - bucket #%s</h2>%s', $this->filtername, $this->i, nl2br($bucket->data));
            $consumed += $bucket->datalen;
            array_unshift($this->data_queue, $bucket->data);
            }

        // Keep the data buffer small. Prevent exhausting memory if we can't find what we're looking for.
        if(count($this->data_queue) > filter_test::MAX_BUCKETS_DATA)
            {
            array_pop($this->data_queue);
            }
        $this->data = implode('', $this->data_queue);
        // echo sprintf('<h2>Filter %s - data</h2>%s', $this->filtername, nl2br($this->data));


        // Find lines that match our search
        if(!is_null($this->pattern))
            {
            $matches = [];
            preg_match_all($this->pattern, $this->data, $matches, PREG_OFFSET_CAPTURE);
            // echo "<pre>";print_r($matches);echo "</pre>";
            $matches = (isset($matches[0]) && !empty($matches[0]) ? $matches[0] : []);
            $first_match_processed = false;
            foreach($matches as $matched_line)
                {
                if(empty($matched_line))
                    {
                    continue;
                    }

                // anything before the first match needs to be passed back out. This will allow us to concatenate it with the next chunk
                // and it may then result in another matched line
                if(!$first_match_processed)
                    {
                    $match_offset = $matched_line[1];
                    $this->filtered_data .= mb_strcut($this->data, 0, $match_offset);
                    $first_match_processed = true;
                    }

                $this->filtered_data .= $matched_line[0] . "\n";
                }
            }


        if($this->filtered_data !== '')
            {
            // echo sprintf('<h2>Filter %s - filtered data</h2>%s', $this->filtername, nl2br($this->filtered_data));
            $filtered_bucket = stream_bucket_new($this->stream, $this->filtered_data);
            stream_bucket_append($out, $filtered_bucket);

            // Reset
            $this->filtered_data = '';
            $this->data_queue = [];

            return PSFS_PASS_ON;
            }

        // echo '<br>---PSFS_FEED_ME';
        return PSFS_FEED_ME;
        }
    }