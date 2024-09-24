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

/**
 * Check if a page item is a section
 * @param array{parent: int} $I Generic page data structure
 */
function is_section(array $I): bool {
    return (int) $I['parent'] === 0;
}

/**
 * Compute moving (for re-ordering purposes) an item based on the requested direction (up/down). Example:
 * 
 * ```php
 * $changed_list = array_replace(
 *     $all_pages_index,
 *     [$ref => compute_item_order($all_pages_index[$ref], $reorder)]
 * );
 * ```
 * 
 * @phpstan-template T of array
 * @param T $item Generic item record with at least the 'order_by' key
 * @param string $direction The direction of the re-order - up/down
 * @return T
 */
function compute_item_order(array $item, string $direction): array
{
    $item['order_by'] += ($direction === 'up' ? -1 : 1) * 15;
    return $item;
}

function render_individual_menu() {
    if (!acl_can_edit_brand_guidelines()) {
        return;
    }
    ?>
    <div id="menu-individual" class="context-menu-container" style="display:none;">
        <button class="context-menu-row" onclick="return edit_item(this);">
            <i class="fa-solid fa-fw fa-pen-to-square"></i>
            <span><?php echo escape($GLOBALS['lang']['action-edit']); ?></span>
        </button>
        <button class="context-menu-row" onclick="return delete_item(this);">
            <i class="fa-solid fa-fw fa-trash-can"></i>
            <span><?php echo escape($GLOBALS['lang']['action-delete']); ?></span>
        </button>
        <button class="context-menu-row" onclick="return reorder_item(this, 'up');">
            <i class="fa-solid fa-fw fa-chevron-up"></i>
            <span><?php echo escape($GLOBALS['lang']['action-move-up']); ?></span>
        </button>
        <button class="context-menu-row" onclick="return reorder_item(this, 'down');">
            <i class="fa-solid fa-fw fa-chevron-down"></i>
            <span><?php echo escape($GLOBALS['lang']['action-move-down']); ?></span>
        </button>
    </div>
    <?php
}

function render_content_menu() {
    ?>
    <div id="menu-content" class="context-menu-container" style="width: 180px; display:none;">
        <button class="context-menu-row">
            <i class="fa-solid fa-fw fa-align-left"></i>
            <span><?php echo escape($GLOBALS['lang']['text']); ?></span>
        </button>
        <button class="context-menu-row">
            <i class="fa-solid fa-fw fa-upload"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_new_resource']); ?></span>
        </button>
        <button class="context-menu-row">
            <i class="fa-solid fa-fw fa-photo-film"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_existing_resource']); ?></span>
        </button>
        <button class="context-menu-row">
            <i class="fa-solid fa-fw fa-palette"></i>
            <span><?php echo escape($GLOBALS['lang']['colour']); ?></span>
        </button>
    </div>
    <?php
}

function render_new_content_button(string $id) {
    if (!acl_can_edit_brand_guidelines()) {
        return;
    }
    ?>
    <div id="<?php echo escape($id); ?>" class="add-new-content-container" onclick="showOptionsMenu(this, 'menu-content');">
        <i class="fa-solid fa-plus"></i>
        <span><?php echo escape($GLOBALS['lang']['brand_guidelines_new_content']); ?></span>
    </div>
    <?php
}

function render_new_block_element_button(string $class) {
    if (!acl_can_edit_brand_guidelines()) {
        return;
    }
    ?>
    <div class="<?php echo escape($class); ?>">
        <i class="fa-solid fa-plus"></i>
    </div>
    <?php
}

/**
 * todo: consider using a dedicated colour object instead. Example:
 * ```php
 * render_block_colour_item([
 *     'name' => 'Black',
 *     'hex' => '000000',
 *     'rgb' => [22, 71, 85],
 *     'cmyk' => [74, 16, 0, 67],
 * ]);
 * ```
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

function render_navigation_item(array $item, bool $is_current = false)
{
    $can_edit_brand_guidelines = acl_can_edit_brand_guidelines();
    $show_individual_menu = true;

    if ($item['parent'] > 0 && $item['ref'] !== 0) {
        // Table of content navigation
        $onclick = sprintf(
            'return CentralSpaceLoad(\'%s\');',
            generateURL(
                "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/guidelines.php",
                ['spage' => $item['ref']]
            )
        );
    } elseif($item['ref'] === 0 && $can_edit_brand_guidelines) {
        // Manage table of content
        $onclick = sprintf(
            'return ModalLoad(\'%s\', true, true);',
            generateURL(
                "{$GLOBALS['baseurl']}/plugins/brand_guidelines/pages/manage/toc.php",
                $item['parent'] > 0 ? ['parent' => $item['parent']] : []
            )
        );
        $show_individual_menu = false;
    } else {
        $onclick = '';
    }

    if (is_section($item)) {
        ?>
        <h2 onclick="<?php echo $onclick; ?>"><?php echo escape(i18n_get_translated($item['name'])); ?></h2>
        <?php
    } else {
        ?>
        <h3 <?php echo $is_current ? 'class="current"' : ''; ?> onclick="<?php echo $onclick; ?>"><?php
                echo escape(i18n_get_translated($item['name']));
        ?></h3>
        <?php
    }

    if ($can_edit_brand_guidelines && $show_individual_menu) {
        ?>
        <div
            class="top-right-menu"
            onclick="showOptionsMenu(this, 'menu-individual');"
            data-item-ref="<?php echo escape($item['ref']); ?>"
        >
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </div>
        <?php
    }
}

/* 

function render_() {
    ?>
    <?php
}
 */
