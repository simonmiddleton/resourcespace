<?php

/**
* Decodes a base64 URL-safe encoded string.
*
* @param string $data The base64 URL encoded data.
* @return string|false The decoded data or false on failure.
*/
function base64UrlDecode($data)
{
    // Replace URL-safe characters with standard Base64 characters
    $data = strtr($data, '-_', '+/');
    // Pad with '=' characters if necessary
    $padding = strlen($data) % 4;

    if ($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }

    return base64_decode($data);
}

/**
 * Retrieve all users who have given consent for Canva access.
 *
 * @return array List of Canva user consent records.
 */
function get_canva_users()
{
    $data = ps_query("SELECT * FROM canva_user_consent");
    return $data;
}

/**
 * Delete a Canva user consent record by reference ID.
 *
 * @param string $ref The reference ID of the Canva user to delete.
 * @return bool True on success, false on failure.
 */
function delete_canva_users($ref)
{
    $status = ps_query("DELETE FROM canva_user_consent WHERE ref = ?", array("s", $ref));
    return $status;
}

/**
 * Retrieve the API key for a Canva user by their Canva ID.
 *
 * @param string $canva_id The Canva user ID.
 * @param string $is_logout If set to "1", the user's status will be set to inactive.
 * @return array|null An array containing the API key and username if found, otherwise null.
 */
function get_key_by_canva_userid($canva_id, $is_logout)
{
    if ($is_logout == "1") {
        ps_query("UPDATE canva_user_consent SET status = 0 WHERE canva_id = ?", array("s", $canva_id));
        return null;
    }

    $data = ps_query("SELECT * FROM canva_user_consent WHERE canva_id= ? AND status = 1", array("s",$canva_id));

    if ($data) {
        $api_key = get_api_key($data[0]['user']);
        ps_query("UPDATE canva_user_consent SET last_used = NOW(), hit = hit + 1 WHERE canva_id = ?", array("s", $canva_id));
        return ['api_key' => $api_key, 'username' => $data[0]['username']];
    }

    return null;
}

/**
 * Check if a Canva user exists in the consent table and activate their status if found.
 *
 * @param string $canva_id The Canva user ID.
 * @return array|null The Canva user consent record if found, otherwise null.
 */
function check_canva_user_id($canva_id)
{
    $data = ps_query("SELECT * FROM canva_user_consent WHERE canva_id= ?", array("s", $canva_id));

    if ($data) {
        ps_query("UPDATE canva_user_consent SET status = 1 WHERE canva_id = ?", array("s", $canva_id));
        return $data[0];
    } else {
        return null;
    }
}

/**
 * Check if a Canva user exists, and if not, save their consent details.
 *
 * @param string $canva_id The Canva user ID.
 * @return void
 */
function check_and_save_canva_user($canva_id)
{
    global $username;

    $currentuser = ps_query("SELECT ref, usergroup, last_active FROM user WHERE username=?", array("s", $username));
    $userid = $currentuser[0]["ref"];
    $is_exist = check_canva_user_id($canva_id);

    if (!$is_exist) {
        ps_query(
            "INSERT INTO `canva_user_consent` (`user`, `status` , canva_id, username, approved) VALUES (?, ?, ?, ?, ?)",
            array("s", $userid, "s", 1, "s", $canva_id, "s", $username, "s", 1)
        );
    }
}
