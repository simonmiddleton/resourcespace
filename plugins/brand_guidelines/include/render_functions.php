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
        <button class="context-menu-row DisplayNone" onclick="return reorder_item(this, 'up');">
            <i class="fa-solid fa-fw fa-chevron-left"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_move_left']); ?></span>
        </button>
        <button class="context-menu-row DisplayNone" onclick="return reorder_item(this, 'down');">
            <i class="fa-solid fa-fw fa-chevron-right"></i>
            <span><?php echo escape($GLOBALS['lang']['brand_guidelines_move_right']); ?></span>
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
    <div id="menu-content" class="context-menu-container" style="display:none;">
        <button
            class="context-menu-row"
            onclick="return new_content_item(this);"
            data-item-type="<?php echo escape((string) BRAND_GUIDELINES_CONTENT_TYPES['text']); ?>"
        >
            <i class="fa-solid fa-fw fa-align-left"></i>
            <span><?php echo escape($GLOBALS['lang']['text']); ?></span>
        </button>
        <button
            class="context-menu-row"
            onclick="return new_content_item(this);"
            data-item-type="<?php echo escape((string) BRAND_GUIDELINES_CONTENT_TYPES['resource']); ?>"
            data-item-upload-url="<?php echo get_upload_url() . '&forcesingle=true'; ?>"
        >
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
    <button id="<?php echo escape($id); ?>" class="add-new-content-container" onclick="showOptionsMenu(this, 'menu-content');">
        <i class="fa-solid fa-plus"></i>
        <span><?php echo escape($GLOBALS['lang']['brand_guidelines_new_content']); ?></span>
    </button>
    <?php
}

/**
 * Render the button for new content item within a group.
 * @param string $class CSS class to use
 * @param value-of<BRAND_GUIDELINES_CONTENT_TYPES> $type Content item type to create
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

function render_item_top_right_menu(int $ref, array $class = [])
{
    if (!acl_can_edit_brand_guidelines()) {
        return;
    }

    $html_class = array_map('escape', array_filter(array_map('trim', ['top-right-menu', ...$class])));
    ?>
    <div
        class="<?php echo implode(' ', $html_class); ?>"
        onclick="showOptionsMenu(this, 'menu-individual');"
        data-item-ref="<?php echo $ref; ?>"
    >
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </div>
    <?php
}

function render_resource_item(array $item): void
{
    $ref = (int) $item['ref'];
    $layout = $item['content']['layout'];
    $caption = trim($item['content']['caption'] ?? '');
    $resource_view_url = generateURL($GLOBALS['baseurl_short'], ['r' => $item['content']['resource_id']]);
    $no_preview = '';

    $resource_data = get_resource_data($item['content']['resource_id']);
    if ($resource_data === false) {
        $no_preview = sprintf(
            '<a class="grid-item" href="%s" onclick="return ModalLoad(this, true);">%s</a>',
            $resource_view_url,
            get_nopreview_html('default')
        );
    } else {
        $image_sizes = array_column(
            get_image_sizes($resource_data['ref'], true, $resource_data['file_extension'], true),
            null,
            'id'
        );
        $preview = $image_sizes[$item['content']['image_size']];
        $resource_title = i18n_get_translated(get_data_by_field($resource_data['ref'], $GLOBALS['view_title_field']));
        $video_player_ctx = [
            'context' => "item_{$ref}",
            'resource_title' => $resource_title,
        ];
    }

    // todo: implement logic to add nopreviews (use CSS to increase font-size as needed based on container e.g thm/half/full)
    if ($layout === 'full-width') {
    ?>
        <div id="page-content-item-<?php echo $ref; ?>" class="resource-content-full-width grid-container">
        <?php
        if ($no_preview !== '') {
            echo $no_preview;
        } else if (($video_player = render_video_player($resource_data, $video_player_ctx))) {
            echo $video_player;
        } else {
            ?>
            <a class="grid-item" href="<?php echo $resource_view_url; ?>" onclick="return ModalLoad(this, true);">
                <img
                    class="image-full-width"
                    src="<?php echo $preview['url']; ?>"
                    alt="<?php echo escape($resource_title); ?>"
                >
            </a>
            <?php
        }
        render_item_top_right_menu($ref, ['grid-item']);
        ?>
        </div>
    <?php
    } else if ($layout === 'half-width') {
        $video_player = $no_preview !== ''
            ? $no_preview
            : render_video_player(
                $resource_data,
                array_merge(
                    $video_player_ctx,
                    [
                        'max_width' => 466,
                        'max_height' => 262,
                        'caption' => $caption,
                    ]
                )
            );

        if ($no_preview !== '' && $caption !== '') {
            $video_player = str_replace('</a>', '<p>' . escape($caption) . '</p></a>', $no_preview);
        }
        ?>
        <div id="page-content-item-<?php echo $ref; ?>" class="image-half-width grid-container">
        <?php
        if ($video_player !== '') {
            echo $video_player;
        } else {
            ?>
            <a class="grid-item" href="<?php echo $resource_view_url; ?>" onclick="return ModalLoad(this, true);">
                <img src="<?php echo $preview['url']; ?>" alt="<?php echo escape($resource_title); ?>">
                <?php
                if ($caption !== '') {
                    ?><p><?php echo escape($caption); ?></p><?php
                }
                ?>
            </a>
            <?php
        }
        render_item_top_right_menu($ref, ['grid-item']);
        ?>
        </div>
    <?php
    } else {
    ?>
        <div id="page-content-item-<?php echo $ref; ?>" class="image-thumbnail grid-container">
            <?php
            if ($no_preview === '') {
                ?>
                <a class="grid-item" href="<?php echo $resource_view_url; ?>" onclick="return ModalLoad(this, true);">
                    <img src="<?php echo $preview['url']; ?>" alt="<?php echo escape($resource_title); ?>">
                </a>
                <?php
            } else {
                echo $no_preview;
            }
            render_item_top_right_menu($ref, ['grid-item']);
            ?>
        </div>
    <?php
    }
}

/**
 * Render video player for resource content items
 * @param array $resource Resource data {@see get_resource_data()}
 * @param array{context: string, max_width: int, max_height: int} $ctx Set optional context for the rendered video player
 * @return string Either nothing or the video player HTML to render
 */
function render_video_player(array $resource, array $ctx = []): string
{
    if (
        in_array((string) $resource['file_extension'], $GLOBALS['ffmpeg_supported_extensions'])
        && !(isset($resource['is_transcoding']) && $resource['is_transcoding'] !== 0)
    ) {
        $cache = [
            'ffmpeg_preview_max_width' => $GLOBALS['ffmpeg_preview_max_width'],
            'ffmpeg_preview_max_height' => $GLOBALS['ffmpeg_preview_max_height'],
        ];
        $player_html = cast_echo_to_string(function() use ($resource, $ctx) {
            $ref = (int) $resource['ref'];
            $GLOBALS['resource'] = $resource;
            $GLOBALS['access'] = get_resource_access($resource);

            if (isset($ctx['max_width']) && is_positive_int_loose($ctx['max_width'])) {
                $GLOBALS['ffmpeg_preview_max_width'] = $ctx['max_width'];
            }

            if (isset($ctx['max_height']) && is_positive_int_loose($ctx['max_height'])) {
                $GLOBALS['ffmpeg_preview_max_height'] = $ctx['max_height'];
            }

            // Set a context & display so we can distinguish multiple videos on the guidelines page otherwise we may end
            // up with a half-width video rendered at max preview size.
            $GLOBALS['context'] = $ctx['context'];
            $GLOBALS['display'] = 'brand_guidelines';

            include RESOURCESPACE_BASE_PATH . '/pages/video_player.php';
            ?>
            <a
                class="grid-item video-js-resource"
                href="<?php echo generateURL($GLOBALS['baseurl_short'], ['r' => $ref]); ?>"
                onclick="return ModalLoad(this, true);"
            ><?php
                echo escape(trim($ctx['resource_title']) ?: $GLOBALS['lang']['backtoview']);
            ?></a>
            <?php
        });
        unset(
            $GLOBALS['resource'],
            $GLOBALS['access'],
            $GLOBALS['display'],
            $GLOBALS['context'],
        );
        foreach ($cache as $name => $value) {
            $GLOBALS[$name] = $value;
        }
        return $player_html;
    } else {
        return '';
    }
}
