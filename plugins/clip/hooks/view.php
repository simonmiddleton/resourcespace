<?php
function HookClipViewCustompanels()
{
    global $lang,$ref,$baseurl,$clip_service_url,$mysql_db;

    $vectors = ps_value("select count(*) value from resource_clip_vector where resource=?", ["i",$ref], 0);
    if ($vectors == 0) {
        return false;
    } // No vectors yet.

    $search_url = generateURL("{$baseurl}/pages/search.php", array("search" => "!clipsimilar{$ref}"));
    $duplicate_url = generateURL("{$baseurl}/pages/search.php", array("search" => "!clipduplicate{$ref}"));
    ?>
    <div class="RecordBox">
        <div class="RecordPanel">
            <div class="Title"><?php echo escape($lang["clip-ai-smart-search"]); ?></div>
                <p>
                    <a href="<?php echo $search_url ?>" onClick="return CentralSpaceLoad(this,true);">
                    <i class="fa fa-fw fa-search"></i>&nbsp;<?php echo escape($lang["clip-visually-similar-images"]); ?>
                    </a>
                    &nbsp;&nbsp;&nbsp;
                    <a href="<?php echo $duplicate_url ?>" onClick="return CentralSpaceLoad(this,true);">
                    <i class="fa fa-fw fa-search"></i>&nbsp;<?php echo escape($lang["clip-duplicate-images"]); ?>
                    </a>
                </p>    

    <?php
    /* For debug

    $clip_service_call = $clip_service_url . "/tag";

    // Send search to Python service
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $clip_service_call);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Connection: keep-alive',
        'Expect:' // Prevents "100-continue" delay
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'db' => $mysql_db,
            'resource' => $ref,
            'url' => 'https://www.resourcespace.com/downloads/clip/taggable_nouns.tagdb',
            'top_k' => 7,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    foreach (json_decode($response) as $result) {
        echo $result->tag . ", ";
    }

    */
    ?>

        </div>
    </div>
    <?php
    return false; # Allow further custom panels
}
