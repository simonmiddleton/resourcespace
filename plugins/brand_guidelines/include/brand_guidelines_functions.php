<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

/**
 * Helper function for access control purposes to check if user can view the Brand Guidelines
 */
function acl_can_view_brand_guidelines(): bool {
    return !checkperm('bgv');
}

/**
 * Helper function for access control purposes to check if user can edit the Brand Guidelines
 */
function acl_can_edit_brand_guidelines(): bool {
    return checkperm('a') || checkperm('bge');
}

function get_pages() {
    return ps_query(
        'SELECT ' . columns_in('brand_guidelines_pages', null, 'brand_guidelines')
        . 'FROM brand_guidelines_pages'
    );
}

function render_individual_menu() {
    ?>
    <div id="menu-individual" class="context-menu-container" style="display:none;">
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-pen-to-square"></i>
            <span><?php echo escape($GLOBALS['lang']['action-edit']); ?></span>
        </div>
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-trash-can"></i>
            <span><?php echo escape($GLOBALS['lang']['action-delete']); ?></span>
        </div>
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-chevron-up"></i>
            <span><?php echo escape($GLOBALS['lang']['action-move-up']); ?></span>
        </div>
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-chevron-down"></i>
            <span><?php echo escape($GLOBALS['lang']['action-move-down']); ?></span>
        </div>
    </div>
    <?php
}

function render_content_menu() {
    ?>
    <div id="menu-content" class="context-menu-container" style="width: 180px; display:none;">
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-align-left"></i>
            <span><?php echo escape($GLOBALS['lang']['text']); ?></span>
        </div>
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-upload"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_new_resource']); ?></span>
        </div>
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-photo-film"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_existing_resource']); ?></span>
        </div>
        <div class="context-menu-row">
            <i class="fa-solid fa-fw fa-palette"></i>
            <span><?php echo escape($GLOBALS['lang']['colour']); ?></span>
        </div>
    </div>
    <?php
}

function render_new_content_button(string $id) {
    ?>
    <div id="<?php echo escape($id); ?>" class="add-new-content-container" onclick="showOptionsMenu(this, 'menu-content');">
        <i class="fa-solid fa-plus"></i>
        <span><?php echo escape($GLOBALS['lang']['brand_guidelines_new_content']); ?></span>
    </div>
    <?php
}

function render_new_block_element_button(string $class) {
    ?>
    <div class="<?php echo escape($class); ?>">
        <i class="fa-solid fa-plus"></i>
    </div>
    <?php
}

/**
 * todo: consider using a dedicated colour object instead
 * Example:
 * render_block_colour_item([
 *     'name' => 'Black',
 *     'hex' => '000000',
 *     'rgb' => [22, 71, 85],
 *     'cmyk' => [74, 16, 0, 67],
 * ]);
 */
function render_block_colour_item(array $value) {
    ?>
    <div class="guidelines-colour-block">
        <div class="guidelines-colour-block--colour" style="background-color: #<?php echo escape($value['hex']); ?>"></div>
        <div class="guidelines-colour-block--details">
            <span><?php echo escape($value['name']); ?></span>
            <br>
            <div class="type">HEX:</div>
            <span>#<?php echo escape($value['hex']); ?></span>
            <br>
            <div class="type">RGB:</div>
            <span><?php echo escape(implode(', ', $value['rgb'])); ?></span>
            <br>
            <div class="type">CMYK:</div>
            <span><?php echo escape(implode(', ', $value['cmyk'])); ?></span>
        </div>
    </div>
    <?php
}

function render_navigation_item(array $item, bool $is_current = false) {
    if ($item['parent'] == 0) {
        printf('<h2>%s</h2>', escape(i18n_get_translated($item['name'])));
    } else {
        ?>
        <h3
            <?php echo $is_current ? 'class="current"' : ''; ?> 
            onclick="CentralSpaceLoad('<?php echo generateURL(
                "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php",
                ['spage' => $item['ref']]
            ); ?>');"
        ><?php echo escape(i18n_get_translated($item['name'])); ?></h3>
        <?php
    }
}

/* 

function render_() {
    ?>
    <?php
}
 */
