<?php

function HookConditional_termsViewDownloadlink($baseparams)
    {
    global $baseurl, $resource, $conditional_terms_field, $conditional_terms_value, $fields, $search, $order_by, $archive, $sort, $offset;

    $showterms=false;

    $resource_value_to_test=trim( get_data_by_field($resource['ref'],$conditional_terms_field) );

    if( $conditional_terms_value==$resource_value_to_test )
        {
        $showterms=true;
        }

    if(!$showterms)
        {
        return false;
        }
    
    ?>href="<?php echo $baseurl ?>/pages/terms.php?<?php echo $baseparams ?>&amp;search=<?php
            echo urlencode($search) ?>&amp;url=<?php
            echo urlencode("pages/download_progress.php?" . $baseparams . "&search=" . urlencode($search)
                    . "&offset=" . $offset . "&archive=" . $archive . "&sort=".$sort."&order_by="
                    . urlencode($order_by))?>&noredir=true"<?php
    
    return true;
    }
