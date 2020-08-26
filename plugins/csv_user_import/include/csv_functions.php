<?php
function csv_user_import_process($csv_file, $user_group_id, &$messages, $processcsv = false)
    {
    global $defaultlanguage;

    $mandatory_columns = array('username', 'email');
    $optional_columns  = array('password', 'fullname', 'account_expires', 'comments', 'ip_restrict', 'lang');
    $possible_columns = sql_query("describe user");
    $possible_columns = array_column($possible_columns,"Field");

    $default_columns_values = array(
        'lang' => $defaultlanguage
    );

    $file = fopen($csv_file, 'r');

    // Manipulate headers
    $headers = fgetcsv($file);
    if(!$headers)
        {
        array_push($messages, 'No header found');
        fclose($file);

        return false;
        }

    for($i = 0; $i < count($headers); $i++)
        {
        $headers[$i] = mb_strtolower($headers[$i]);
        }



    // Check header columns
    $header_check_valid = true;
    $mandatory_columns_not_found = array_diff($mandatory_columns,$headers);
    foreach($mandatory_columns_not_found as $column_header)
        {
        array_push($messages, 'Error: Could not find mandatory column "' . $column_header . '"');
        $header_check_valid = false;
        }

    $unknown_columns = array_diff($headers, $possible_columns);
    foreach($unknown_columns as $column_header)
        {
        array_push($messages, 'Info: ResourceSpace has no use (ie. unknown) for column "' . $column_header . '" and as such it will not be taken into account');
        }

    // No point to continue since headers are not right
    if(!$header_check_valid)
        {
        fclose($file);
        return false;
        }



    $line_count      = 0;
    $error_count     = 0;
    $max_error_count = 100;

    array_push($messages, '### Processing ' . count($headers) . ' columns ###');

    while( ( false !== ($line = fgetcsv($file)) ) && $error_count < $max_error_count)
        {
        $line_count++;

        // Check that the current row has the correct number of columns
        if(!$processcsv && count($line) !== count($headers))
            {
            array_push($messages, 'Error: Incorrect number of columns( ' . count($line) . ') found on line ' . $line_count . '. It should be ' . count($headers));
            $error_count++;

            continue;
            }

        $sql_update_col_val_pair = "`usergroup` = '" . escape_check($user_group_id) . "'";
        $cell_count = -1;
        $user_creation_data = array();

        foreach($headers as $header)
            {
            $cell_count++;
            $cell_value = trim($line[$cell_count]);
            
            if(in_array($header, $unknown_columns))
                {
                // Ignore this column;
                continue;
                }                

            // Make sure mandatory fields have a value
            if(in_array($header, $mandatory_columns) && '' === $cell_value)
                {
                array_push($messages, 'Error: Mandatory column "' . $header . '" cannot be empty on line ' . $line_count);
                $error_count++;

                continue;
                }

            if('username' === $header || 'email' === $header)
                {
                $escaped_cell_value = escape_check($cell_value);
                $check = sql_value("SELECT count(*) AS value FROM user WHERE `{$header}` = '{$escaped_cell_value}'", 0);
                if(0 < $check)
                    {
                    array_push($messages, ucfirst($header). ' "' . $cell_value . '" exists already in ResourceSpace');
                    $error_count++;

                    continue;
                    }
                }

            if('password' === $header && '' != $cell_value)
                {
                $password_message = check_password($cell_value);
                if($password_message !== true)
                    {
                    array_push($messages, 'Line ' . ($line_count + 1) . ': ' . $password_message);    
                    $error_count++;

                    continue;
                    }
                }
            $user_creation_data[$header] = $cell_value;
            }

        if($processcsv && 0 === $error_count)
            {
            // Create new user if we can process it and don't have any errors
            $new_user_id = new_user($user_creation_data['username']);
            array_push($messages, 'Info: Created new user "' . $user_creation_data['username'] . '" with ID "' . $new_user_id . '"');

            foreach ($user_creation_data as $key => $value) 
            {
                $sql_update_col_val_pair .= ", `" . escape_check($key) . "` = ";
                if($value === '' && array_key_exists($key, $default_columns_values))
                    {
                    $sql_update_col_val_pair .= "'" . escape_check($default_columns_values[$key]) . "'";
                    }
                elseif($key === 'password' && $value != '')
                    {
                    $sql_update_col_val_pair .= "'" . hash('sha256', md5('RS' . $user_creation_data['username'] . $value)) . "'";
                    }
                elseif($value === '')
                    {
                    $sql_update_col_val_pair .= 'NULL';
                    }
                else
                    {
                    $sql_update_col_val_pair .= "'" . escape_check($value) . "'";
                    }
            }

            $reset_password_email_required = false;
            if(!isset($user_creation_data['password']) || $user_creation_data['password'] === '')
                {
                $sql_update_col_val_pair .= ", password = '" . hash('sha256', md5('RS' . $user_creation_data['username'] . make_password())) . "'";
                $reset_password_email_required = true;
                }

            // Update record
            $sql_query = "UPDATE `user` SET {$sql_update_col_val_pair} WHERE `ref` = '{$new_user_id}'";
            sql_query($sql_query);
            if($reset_password_email_required === true){email_reset_link($user_creation_data['email'],true);}
            }
        } /* end of reading each line found */

    fclose($file);

    if(!$processcsv && $line_count === 0)
        {
        array_push($messages, 'Error: No lines of data found in the uploaded file');

        return false;
        }

    // Consider removing if not much is going on through each line
    if(!$processcsv && 0 < $error_count)
        {
        array_push($messages, 'Warning: Script has found ' . $error_count . ' error(s)!');

        return false;
        }

    if(!$processcsv)
        {
        array_push($messages, 'Info: data successfully validated!');
        }
    else
        {
        array_push($messages, 'Info: data successfully processed!');
        }

    return true;
    }
