<?php
include_once __DIR__ . "/../include/boot.php";

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k = getval("k", "");
if (($k == "") || (!check_access_key_collection(getval("collection", "", true), $k))) {
    include_once __DIR__ . "/../include/authenticate.php";
}

if (checkperm("b")) {
    exit($lang["error-permissiondenied"]);
}
?>
<script type="text/javascript">
jQuery(function () {
    var $bar = jQuery('.collection-bar-holder');
    var $toggleThumbsBtn = jQuery('.show-thumbs-button');

    function setBarState(state) {
        $bar.removeClass('state-closed state-actions state-thumbs')
            .addClass(state);
    }

    jQuery('.collection-bar-toggle-button').on('click', function () {
        if ($bar.hasClass('state-closed')) {
            setBarState('state-actions');
        } else {
            setBarState('state-closed');
            $toggleThumbsBtn.find('span').text('Show thumbnails');
        }
    });

    jQuery('.show-thumbs-button').on('click', function () {
        if ($bar.hasClass('state-thumbs')) {
            setBarState('state-actions');
            $toggleThumbsBtn.find('span').text('Show thumbnails');
        } else {
            setBarState('state-thumbs');
            $toggleThumbsBtn.find('span').text('Hide thumbnails');
        }
    });
});
</script>
<div class="collection-bar-holder state-closed">
    <div class="collection-bar-toggle-button"><i class="icon-layout-dashboard"></i></div>
    <div class="collection-bar-actions-section">   
        <div class="collection-bar-actions-form">
            <div class="field current-collections-field">
                <div class="label-holder"><label for="collection">Current collection</label><div class="meta">11 items</div></div>
                    <select id="collection">
                        <option>Spring Campaign '26</option>
                    </select>
            </div>
            <div class="field actions-field">
                <div class="label-holder"><label for="actions">Actions</label></div>
                <select id="actions">
                    <option>Un-relate all resources in this collection</option>
                </select>
            </div>
            <button class="show-thumbs-button"><i class="icon-images"></i><span>Show thumbnails</span></button>
        </div>
    </div>
    <div class="collection-bar-thumbs-section">THUMBNAIL<br /><br /><br />CONTENT</div>
</div>