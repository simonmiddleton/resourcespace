<?php

if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


add_keyword_mappings('2','test',1,1);

add_keyword_mappings(2,'test',1,1);

# errors thrown if ref is not an int
#add_keyword_mappings(null,'test',1,1);

#add_keyword_mappings('','test',1,1);

return true;