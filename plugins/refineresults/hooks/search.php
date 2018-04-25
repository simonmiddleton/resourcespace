<?php

function HookRefineresultsSearchSearch_header_after_actions()
    {
  global $baseurl_short, $lang, $k, $search, $parameters_string, $result, $collections;
	
    $results = 0;
    if(is_array($result))
        {
        $results = count($result);
        }

    if(is_array($collections))
        {
        $results += count($collections);
        }

    # External sharing search support. Clear search drops back to the collection only search.
    $default_search = '';
    if($k != '' || ($k == '' && substr($search, 0, 1) == '!'))
        {
        $s = explode(' ', $search);
        $default_search = str_replace(',','',$s[0]);
        }
	
    // Search within these results option
    if ($results > 0)
        {
        ?>
        <div id="refine_results_button" class="InpageNavLeftBlock">
        <a href="#" onClick="jQuery('#RefineResults').slideToggle();jQuery('#refine_keywords').focus();"><div class="fa fa-fw fa-search-plus"></div><?php echo $lang["refineresults"]; ?></a>
        </div>
        <?php
        }
    }
