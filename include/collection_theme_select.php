<?php
// This file is included fom collection_edit.php and collection_set_category.php
if(!hook("overridethemesel") && $enable_themes && checkperm("h"))
    {
    render_featured_collection_category_selector(
        0,
        array(
            "collection" => $collection,
            "depth" => 0,
            "current_branch_path" => get_featured_collection_category_branch_by_leaf((int) $collection["ref"], array()),
        ));
    }