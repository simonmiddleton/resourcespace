<?php

/**
 * purpose: tests for str_highlight() function
 */


if('cli' !== php_sapi_name())
    {
    exit('This utility is command line only.');
    }


    $haystack = 'Sometimes the text can (contain) <a href="test">HTML</a> entities and can break the highlighting feature';
    $needle = array("times", "HTmL", "(", ")");


// Case 1: highlight simple

$expected_output    = 'Some<span class="highlight">times</span> the text can (contain) <a href="test"><span class="highlight">HTML</span></a> entities and can break the highlighting feature';
$result             = str_highlight($haystack, $needle, STR_HIGHLIGHT_SIMPLE);

if ($result != $expected_output )
    {
        echo "case 1:" . $result;
    return false;
    }


// Case 2: highlight whole words

$expected_output    = 'Some<span class="highlight">times</span> the text can (contain) <a href="test"><span class="highlight">HTML</span></a> entities and can break the highlighting feature';
$result             = str_highlight($haystack, $needle, STR_HIGHLIGHT_WHOLEWD);

if ($result != $expected_output )
    {
    echo "case 2" . $result;
    return false;
    }


// Case 3: highlight case-sensitive

$expected_output    = 'Some<span class="highlight">times</span> the text can (contain) <a href="test"><span class="highlight">HTML</span></a> entities and can break the highlighting feature';
$result             = str_highlight($haystack, $needle, STR_HIGHLIGHT_CASESENS);

if ($result != $expected_output )
    {
    echo "case 3" . $result;
    return false;
    }


// Case 4: highlight strip links

$expected_output    = 'Some<span class="highlight">times</span> the text can (contain) <span class="highlight">HTML</span> entities and can break the highlighting feature';
$result             = str_highlight($haystack, $needle, STR_HIGHLIGHT_STRIPLINKS);

if ($result != $expected_output )
    {
    echo "case 4" . $result;
    return false;
    }


return true;

?>