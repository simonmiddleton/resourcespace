<?php
namespace ResourceSpace\Stream\Filter;

class FindInFileTail extends \php_user_filter
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
            $consumed += $bucket->datalen;
            array_unshift($this->data_queue, $bucket->data);
            }

        // Keep the data buffer small. Prevent exhausting memory if we can't find what we're looking for.
        if(count($this->data_queue) > FindInFileTail::MAX_BUCKETS_DATA)
            {
            array_pop($this->data_queue);
            }
        $this->data = implode('', $this->data_queue);

        // Find lines that match our search
        if(!is_null($this->pattern))
            {
            $matches = [];
            preg_match_all($this->pattern, $this->data, $matches, PREG_OFFSET_CAPTURE);
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
            $filtered_bucket = stream_bucket_new($this->stream, $this->filtered_data);
            stream_bucket_append($out, $filtered_bucket);

            // Reset
            $this->filtered_data = '';
            $this->data_queue = [];

            return PSFS_PASS_ON;
            }

        return PSFS_FEED_ME;
        }
    }