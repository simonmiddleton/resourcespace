<?php
/**
* Encrypts data
* 
* @uses generateSecureKey()
* 
* @param  string  $data  Data to be encypted
* @param  string  $key
* @todo Add a third parameter to use with custom metadata (NOT ResourceSpace metadata) for generating MAC. this should
* add extra security by making MAC harder to be forged
* 
* @return  string  Encrypted data
*/
function rsEncrypt($data, $key)
    {
    global $scramble_key;

    /*
    Encrypt-then-MAC (EtM)
    ======================
    PlainText
        |
    Encryption <-- Key
        |_________   |
        |         |  |
        |      HashFunction
        |           |
    --------------------
    | Ciphertext | MAC |
    --------------------
    The plaintext is first encrypted, then a MAC is produced based on the resulting ciphertext.  The ciphertext and its 
    MAC are sent together.
    */
    $method  = "AES-128-CTR";
    $options = OPENSSL_RAW_DATA;
    $nonce   = generateSecureKey(128);

    // Get 2 derived subkeys, one for message authentication code (MAC) and the other one for encryption/ decryption.
    $mac_key = hash_hmac("sha256", "mac_key", $scramble_key, true);
    $enc_key = hash_hmac("sha256", "enc_key", $scramble_key, true);

    // Synthetic Initialization Vector (SIV)
    $siv = substr(hash_hmac("sha256", "{$nonce}{$scramble_key}{$key}", $mac_key, true), 0, 16);

    $cyphertext = bin2hex(openssl_encrypt($data, $method, $enc_key, $options, $siv));

    $mac = hash_hmac("sha256", "{$cyphertext}{$nonce}{$scramble_key}", $mac_key);

    return "{$nonce}@@{$cyphertext}@@{$mac}";
    }


/**
* Decrypts data
* 
* @param  string  $data  Data to be decrypted
* @param  string  $key
* @todo Add a third parameter to use with custom metadata (NOT ResourceSpace metadata) for generating MAC. this should
* add extra security by making MAC harder to be forged
* 
* @return  false|string  Returns FALSE if MAC check failed, plaintext otherwise
*/
function rsDecrypt($data, $key)
    {
    global $scramble_key;

    $method  = "AES-128-CTR";
    $options = OPENSSL_RAW_DATA;

    // Get 2 derived subkeys, one for message authentication code (MAC) and the other one for encryption/ decryption.
    $mac_key = hash_hmac("sha256", "mac_key", $scramble_key, true);
    $enc_key = hash_hmac("sha256", "enc_key", $scramble_key, true);

    if (count(explode("@@", $data))<3){return false;}
    list($nonce, $cyphertext, $mac) = explode("@@", $data);

    // Check MAC
    if($mac !== hash_hmac("sha256", "{$cyphertext}{$nonce}{$scramble_key}", $mac_key))
        {
        debug("rsCrypt: MAC did not match!");
        return false;
        }

    // Synthetic Initialization Vector (SIV)
    $siv = substr(hash_hmac("sha256", "{$nonce}{$scramble_key}{$key}", $mac_key, true), 0, 16);

    $plaintext = openssl_decrypt(hex2bin($cyphertext), $method, $enc_key, $options, $siv);

    return $plaintext;
    }


/**
* Prior to eval() checks to make sure the code has been signed first, by the offline script / migration script.
* 
* @param  string  $code  The code to check 
* 
* @return  string  The code, if correctly signed, or an empty string if not.
*/
function eval_check_signed($code)
    {
    global $scramble_key;

    // Check goes here.
    $code_split=explode("\n",$code);if (count($code_split)<2) {return "echo 'UNSIGNED CODE ERROR';";} // Not enough lines to include a key, exit
    $signature=str_replace("//","",$code[0]); // Extract signature
    
    // Code not signed correctly? Exit early.
    if ($signature!=sign_code($code)) {return "echo 'UNSIGNED CODE ERROR';";}

    // All is as expected, return the code ready for execution.
    return $code;
    }

/**
* Returns a signature for a given block of code.
* 
* @param  string  $code  The code to sign
* 
* @return  string  The signature
*/
function sign_code($code)
    {
    global $scramble_key;
    return hash_hmac("sha256",$code,$scramble_key);
    }