<?php

/** Retrieve the current config for the feedback plugin, if the plugin is being used for the first time the default config file
 *  is loaded and added to the database
 * 
 *  @param string $dir the directory for the default config file
 *  
 *  @return array config array containing the promopt text and question data
 */
function get_feedback_config($dir = '')
    {
    global $feedback_prompt_text, $feedback_questions;
    $config = get_plugin_config('feedback');
    if(empty($config))
        {
        if (file_exists($dir)) {include $dir;}
        $config = ['prompt_text' => $feedback_prompt_text, 'questions' => $feedback_questions];
        set_plugin_config('feedback', $config);
        update_feedback_fields($feedback_questions);
        }
    return $config;
    }

function make_new_results_file($storagedir)
    {
    
    $filename = get_feedback_results_file($storagedir . '/feedback/', 'results', false);

    if(file_exists($storagedir . '/feedback/' . $filename) && filesize($storagedir . '/feedback/' . $filename) !== 0)
        {
        rename($storagedir . '/feedback/' . $filename,$storagedir . '/feedback/'.get_feedback_results_file($storagedir . '/feedback/','results'));
        touch($storagedir  . '/feedback/' . $filename);
        chmod($storagedir  . '/feedback/' . $filename,0777);
	    }

    }

function get_feedback_results_file($dir, $basename, $create_new_file = true)
    {
    global $scramble_key;
    
    srand((int)$scramble_key);
    $n = 0;
    $filename = md5(str_shuffle($basename) . $n);
    while(file_exists($dir.$filename.'.csv') && $create_new_file === true)
        {
        $n++;
        $filename = md5(str_shuffle($basename) . $n);
        }
    srand();

    return $filename . '.csv';
    }

/** This function is used to save data from the feedback plugin back to the database
 * 
 *  @param array $data an associative array of data that will be entered into the database where the key of each
 *  element is the field name for that element
 * 
 */
function save_feedback_data(array $data)
    {
    if(is_array($data))
        {
        $offset = sql_value('SELECT MIN(id) `value` FROM feedback_fields WHERE version = (SELECT MAX(version) FROM feedback_fields) ORDER BY VERSION DESC LIMIT 1', '1');
        $n      = 0;
        foreach($data as $key => $datum)
            {
            if($key == 'user' || $key == 'date')
                {
                $$key = $datum;
                }
            else
                {
                $type = sql_value('SELECT type AS value FROM feedback_fields WHERE id = '. ($offset+$n) ,'');
                while($type == 4)
                    {
                    $n++;
                    # This is to skip fields that are marked as lables so that the question ids match up correctly 
                    $type = sql_value('SELECT type AS value FROM feedback_fields WHERE id = '. ($offset+$n) ,'');;
                    }
                sql_query('INSERT INTO feedback_data (field_id, value, date, user) VALUES("'. ($offset+$n) .'","'. escape_check($datum) .'","'. escape_check($date) .'", "'. escape_check($user) .'")');
                $n++;
                }
            }
        }
    }

/** Function used to save the fields configured to the database
 * 
 *  @param array $fielddata the array of fields to save to the database
 */
function update_feedback_fields(array $fielddata)
    {
    if(is_array($fielddata))
        {
        $version = sql_value('SELECT (IFNULL(MAX(version), 0) + 1) as value FROM feedback_fields', '');
        if($version !== '')
            {
            $n=0;
            foreach($fielddata as $data)
                {
                sql_query('INSERT INTO feedback_fields (version, text, type, options) VALUES ("'. escape_check($version) .'","'. escape_check($data['text']) .'","'. escape_check($data['type']) .'","'. escape_check($data['options']) .'")');
                }
            }
        }
    }