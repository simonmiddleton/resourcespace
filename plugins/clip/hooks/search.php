<?php





function HookClipSearchBeforesearchresults()
    {
    global $lang,$ref,$baseurl,$search;

    // Don't display for search terms containing larger numbers - this is for natural search only
    if (preg_match('/\d{2,}/', $search)) {return false;}

    // Reject if it contains any symbols *except* comma, full stop, hyphen, and space
    if (preg_match('/[^a-zA-Z0-9 ,.\-]/', $search)) {return false;}

    $search_url=generateURL("{$baseurl}/pages/search.php", array("search" => "!clipsearch {$search}"));
    ?>
    <p>
        <a href="<?php echo $search_url ?>" onClick="return CentralSpaceLoad(this,true);">
        <i class="fa fa-fw fa-search"></i>&nbsp;<?php echo $lang["clip-natural-language-search"]; ?>
        </a>
    </p>    
    <?php
    return false; # Allow further custom panels
    }

/*
function HookClipSearchEndofsearchpage()
    {
    global $clip_query_time;
    if (isset($clip_query_time))
        {
        ?>
        <p>CLIP query took <?php echo $clip_query_time ?>ms.</p>
        <?php
        }
    }
*/
