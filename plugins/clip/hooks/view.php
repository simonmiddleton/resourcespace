<?php
function HookClipViewCustompanels()
    {
    global $lang,$ref,$baseurl;

    $vectors=ps_value("select count(*) value from resource_clip_vector where resource=?",["i",$ref],0);
    if ($vectors==0) {return false;} // No vectors yet.

    $search_url=generateURL("{$baseurl}/pages/search.php", array("search" => "!clipsimilar{$ref}"));
    ?>
    <div class="RecordBox">
        <div class="RecordPanel">
            <div class="Title"><?php echo escape($lang["clip-ai-smart-search"]); ?></div>
                <p>
                    <a href="<?php echo $search_url ?>" onClick="return CentralSpaceLoad(this,true);">
                    <i class="fa fa-fw fa-search"></i><?php echo $lang["clip-visually-similar-images"]; ?>
                    </a>
                </p>    
        </div>
    </div>
    <?php
    return false; # Allow further custom panels
    }
