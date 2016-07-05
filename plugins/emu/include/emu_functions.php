<?php
/*
Example 1:
TEXQL Query:  SELECT MovieTitle, Year FROM Movies
<?xml version="1.0" ?>
<results status="success" matches="3">
    <record>
        <MovieTitle>Star Wars</MovieTitle>
        <Year>1977</Year>
    </record>
    <record>
        <MovieTitle>The Empire Strikes Back</MovieTitle>
        <Year>1980</Year>
    </record>
    <record>
        <MovieTitle>Return of the Jedi</MovieTitle>
        <Year>1983</Year>
    </record>
</results>

Example 2:
TEXQL Query:  SELECT MovieTitle, Actors_tab FROM Movies
<?xml version="1.0" ?>
<results status="success" matches="3">
    <record>
        <MovieTitle>Star Wars</MovieTitle>
        <Actors_tab>
            <Actors>J. Smith</Actors>
            <Actors>B. J. Jones</Actors>
        </Actors_tab>
    </record>
    <record>
        <MovieTitle>Empire Strikes Back</MovieTitle>
        <Actors_tab>
            <Actors>J. Smith</Actors>
            <Actors>Karen Smith</Actors>
        </Actors_tab>
    </record>
    <record>
        <MovieTitle>Return of the Jedi</MovieTitle>
        <Actors_tab>
            <Actors>J. Smith</Actors>
            <Actors>A. Mc Donald</Actors>
        </Actors_tab>
    </record>
</results>
*/
/**
* Function used to parse an XML string to an array
* 
* @param string $xml
* 
* @return array
*/
function parse_xml_into_array($xml)
    {
    $parents = array();

    $parser = xml_parser_create();
    xml_parse_into_struct($parser, $xml, $values, $index);

    $error = xml_get_error_code($parser);
    if(0 !== $error)
        {
        $line = xml_get_current_line_number($parser);
        echo 'XML parse error: ' . xml_error_string($error) . "<br>Line: {$line}<br><br>";
        
        $s = explode("\n", $xml);
        echo '<pre>' . trim(htmlspecialchars(@$s[$line - 2])) . '<br>';
        echo '<b>' . trim(htmlspecialchars(@$s[$line - 1])) . '</b><br>';
        echo trim(htmlspecialchars(@$s[$line])) . '<br></pre>';     

        exit();
        }

    // For each element, attach the attributes from the separate $values array, plus the ID and the ID of the parent node.
    // This makes it much more useful.
    foreach($index as $tag => $instances)    
        {
        foreach($instances as $id => $instance)
            {
            // Copy the $values node into the $index tree at this point, replacing the reference number (this is more useful).
            $index[$tag][$id]       = $values[$instance];
            $index[$tag][$id]['id'] = $instance;
            
            // Find the parent for this tree.
            // Traverse back up until the level changes. This is the parent element.
            $level = $values[$instance]['level'];

            for($n = $instance; $n > 0; $n--)
                {
                if($level > $values[$n]['level'] && 'cdata' != $values[$n]['type'])
                    {
                    break;
                    }
                }

            $index[$tag][$id]['parent'] = $n;

            // Store to handy parents index, used for quickly establishing node ancestors
            $parents[$instance] = $n;

            // Remove CDATA and CLOSE tag types as they are not needed.
            if('cdata' == $index[$tag][$id]['type'])
                {
                unset($index[$tag][$id]);
                }
            elseif('close' == $index[$tag][$id]['type'])
                {
                unset($index[$tag][$id]);
                }
            }
        }

    // Append parents index as a tree
    $index['parents'] = $parents;
    
    return $index;
    }