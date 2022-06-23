<?php
command_line_only();

$use_cases = [
    /*
    Example of a full use case structure. The tags & attributes keys are optional
    [
        'name' => '',
        'input' => [
            'html' => '',
            'tags' => [],
            'attributes' => [],
        ],
        'expected' => '',
    ],
    */
    [
        'name' => 'Empty string',
        'input' => [
            'html' => '',
        ],
        'expected' => '',
    ],
    [
        'name' => 'Text (ie. no HTML present)',
        'input' => [
            'html' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        ],
        'expected' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
    ],
    [
        'name' => 'Text (ie. no HTML present) with new line feeds at the end',
        'input' => [
            'html' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' . PHP_EOL,
        ],
        'expected' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.' . PHP_EOL,
    ],
    [
        'name' => 'Text with a <script> tag in',
        'input' => [
            'html' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. <script>alert("XSS test")</script>',
        ],
        'expected' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. </p>',
    ],
    [
        'name' => 'Tag (header) not allowed by default',
        'input' => [
            'html' => '<header>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</header>',
        ],
        'expected' => '',
    ],
    [
        'name' => 'Default configuration - allowed tag (p)',
        'input' => [
            'html' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>'
        ],
        'expected' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'Default configuration - allowed attribute (id)',
        'input' => [
            'html' => '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
        ],
        'expected' => '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'Stripping code based on default configuration',
        'input' => [
            'html' => '<p id="testID" data-test="testData" onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
        ],
        'expected' => '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'Default configuration - remove <script> tags',
        'input' => [
            'html' => '<p id="testID" onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<script>console.log(true);</script></p>',
        ],
        'expected' => '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'Default configuration - remove onmouse* attributes',
        'input' => [
            'html' => '<p onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
        ],
        'expected' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'Allow extra attributes',
        'input' => [
            'html' => '<p id="testID" data-test="testData" onmousedown="this.style.color=\'#FF0000\';" onmouseup="this.style.color=\'#000000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
            'attributes' => ['data-test'],
        ],
        'expected' => '<p id="testID" data-test="testData">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'Allow extra tags',
        'input' => [
            'html' => '<p id="testID" onmousedown="this.style.color=\'#FF0000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<script>console.log(true);</script></p>',
            'tags' => ['script'],
        ],
        'expected' => '<p id="testID">Lorem ipsum dolor sit amet, consectetur adipiscing elit.<script>console.log(true);</script></p>',
    ],
    [
        'name' => 'Poorly formated tags (e.g. missing closing tags)',
        'input' => [
            'html' => '<p onmousedown="this.style.color=\'#FF0000\';">Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        ],
        'expected' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],
    [
        'name' => 'HTML with new line feeds at the end',
        'input' => [
            'html' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>' . PHP_EOL,
        ],
        'expected' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
    ],    
    [
        'name' => 'HTML which has been through htmlspecialchars()',
        'input' => [
            'html' => htmlspecialchars('<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. <script>alert("XSS test")</script></p>'),
        ],
        'expected' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. </p>',
    ],
];

foreach($use_cases as $use_case)
    {
    $html = $use_case['input']['html'];
    $tags = $use_case['input']['tags'] ?? [];
    $attributes = $use_case['input']['attributes'] ?? [];

    if(strip_tags_and_attributes($html, $tags, $attributes) !== $use_case['expected'])
        {
        echo "Use case: {$use_case['name']} - ";
        return false;
        }
    }



// Tear down
unset($use_cases, $html, $tags, $attributes);

return true;