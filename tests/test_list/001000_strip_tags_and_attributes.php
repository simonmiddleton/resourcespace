<?php
if('cli' !== php_sapi_name())
    {
    exit('This utility is command line only.');
    }

// Case 1: 
if('' !== strip_tags_and_attributes(''))
    {
    return false;
    }


// Case 2: Tag not allowed by default
$html_input  = '<header>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</header>';
$html_output = '';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 3: 
$html_input  = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$html_output = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 4: 
$html_input  = '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$html_output = '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 5: 
$html_input  = '<p id="testID" data-test="testData" onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$html_output = '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 5.1: 
$html_input  = '<p id="testID" onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<script>console.log(true);</script></p>';
$html_output = '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 5.2: 
$html_input  = '<p id="testID" data-test="testData" onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$html_output = '<p id="testID" data-test="testData">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$allow_attrs = array('data-test');

if($html_output !== strip_tags_and_attributes($html_input, array(), $allow_attrs))
    {
    return false;
    }


// Case 5.3: 
$html_input  = '<p id="testID" onmousedown="this.style.color=\'#FF0000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<script>console.log(true);</script></p>';
$html_output = '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<script>console.log(true);</script></p>';
$allow_tags  = array('script');

if($html_output !== strip_tags_and_attributes($html_input, $allow_tags))
    {
    return false;
    }


// Case 6: 
$html_input  = '<p onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$html_output = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 7: 
$html_input  = '<p onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';
$html_output = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 8: Poorly formated tags (e.g. missing closing tags)
$html_input  = '<p onmousedown="this.style.color=\'#FF0000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
$html_output = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case 9: Text with new line feeds at the end
$html_input  = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>' . PHP_EOL;
$html_output = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }


// Case: Normal text (ie. no HTML present)
$html_input  = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
$html_output = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

if($html_output !== strip_tags_and_attributes($html_input))
    {
    return false;
    }

return true;