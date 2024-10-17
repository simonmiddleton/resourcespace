<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

function render_individual_menu()
{
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

function render_content_menu()
{
    ?>
    <div id="menu-content" class="context-menu-container" style="width: 180px; display:none;">
        <button
            class="context-menu-row"
            onclick="return new_content_item(this);"
            data-item-type="<?php echo escape((string) BRAND_GUIDELINES_CONTENT_TYPES['text']); ?>"
        >
            <i class="fa-solid fa-fw fa-align-left"></i>
            <span><?php echo escape($GLOBALS['lang']['text']); ?></span>
        </button>
        <button class="context-menu-row">
            <i class="fa-solid fa-fw fa-upload"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_new_resource']); ?></span>
        </button>
        <button
            class="context-menu-row"
            onclick="return new_content_item(this);"
            data-item-type="<?php echo escape((string) BRAND_GUIDELINES_CONTENT_TYPES['resource']); ?>"
        >
            <i class="fa-solid fa-fw fa-photo-film"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_existing_resource']); ?></span>
        </button>
        <button
            class="context-menu-row"
            onclick="return new_content_item(this);"
            data-item-type="<?php echo escape((string) BRAND_GUIDELINES_CONTENT_TYPES['colour']); ?>"
        >
            <i class="fa-solid fa-fw fa-palette"></i>
            <span><?php echo escape($GLOBALS['lang']['colour']); ?></span>
        </button>
    </div>
    <?php
}

function render_new_content_button(string $id)
{
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

/**
 * Render the button for new content item within a group.
 * @param string $class CSS class to use
 * @param int $type Content item type to create (values of BRAND_GUIDELINES_CONTENT_TYPES)
 */
function render_new_block_element_button(string $class, int $type): void
{
    if (!acl_can_edit_brand_guidelines()) {
        return;
    }
    ?>
    <button
        class="<?php echo escape($class); ?>"
        onclick="return new_content_item(this);"
        data-item-type="<?php echo escape((string) $type); ?>"
    >
        <i class="fa-solid fa-plus"></i>
    </button>
    <?php
}

/**
 * Render a colour page content item. Example:
 * ```php
 * render_block_colour_item([
 *     'ref' => 9,
 *     'name' => 'Black',
 *     'hex' => '000000',
 *     'rgb' => '0, 0, 0',
 *     'cmyk' => '0, 0, 0, 100',
 * ]);
 * ```
 * @param array{ref: int, name: string, hex: string, rgb: string, cmyk: string} $value Colour value to render
 */
function render_block_colour_item(array $value): void
{
    $ref = (int) $value['ref'];
    $hex = ltrim($value['hex'], '#');
    ?>
    <div id="page-content-item-<?php echo $ref; ?>" class="guidelines-colour-block">
        <?php render_item_top_right_menu($ref); ?>
        <div class="guidelines-colour-block--colour" style="background-color: #<?php echo escape($hex); ?>"></div>
        <div class="guidelines-colour-block--details">
            <span><?php echo escape(i18n_get_translated($value['name'])); ?></span>
            <br>
            <div class="type"><?php echo escape($GLOBALS['lang']['brand_guidelines_hex']); ?>:</div>
            <span>#<?php echo escape($hex); ?></span>
            <br>
            <div class="type"><?php echo escape($GLOBALS['lang']['brand_guidelines_rgb']); ?>:</div>
            <span><?php echo escape($value['rgb']); ?></span>
            <br>
            <div class="type"><?php echo escape($GLOBALS['lang']['brand_guidelines_cmyk']); ?>:</div>
            <span><?php echo escape($value['cmyk']); ?></span>
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
        render_item_top_right_menu((int) $item['ref']);
    }
}

function render_item_top_right_menu(int $ref)
{
    if (!acl_can_edit_brand_guidelines()) {
        return;
    }
    ?>
    <div
        class="top-right-menu"
        onclick="showOptionsMenu(this, 'menu-individual');"
        data-item-ref="<?php echo $ref; ?>"
    >
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </div>
    <?php
}