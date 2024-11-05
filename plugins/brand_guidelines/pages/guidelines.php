<?php

declare(strict_types=1);

namespace Montala\ResourceSpace\Plugins\BrandGuidelines;

include_once dirname(__DIR__, 3) . '/include/boot.php';
include_once RESOURCESPACE_BASE_PATH . '/include/authenticate.php';
if (!acl_can_view_brand_guidelines()) {
    http_response_code(401);
    exit(escape($lang['error-permissiondenied']));
}

$pages_db = get_all_pages();
$all_pages = get_node_tree(0, $pages_db);
$selected_page = getval('spage', 0, false, 'is_positive_int_loose');
$available_pages = extract_node_options(array_filter($pages_db, __NAMESPACE__ . '\filter_only_pages'), true, true);

if ($available_pages === []) {
    $selected_page_title = '';
} elseif (isset($available_pages[$selected_page])) {
    $selected_page_title = $available_pages[$selected_page];
} else {
    $selected_page = array_key_first($available_pages);
    $selected_page_title = $available_pages[$selected_page];
}

$page_contents = array_map(function($item) {
    $item_content = json_decode($item['content'], true);
    if ($item_content === false) {
        debug("Failed to decode page item (#{$item['ref']}) content. Reason: " . json_last_error_msg());
        return $item;
    }
    $item['content'] = $item_content;
    return $item;
}, get_page_contents((int) $selected_page));
$page_contents_grouped = group_content_items($page_contents);

include_once RESOURCESPACE_BASE_PATH . '/include/header.php';
render_individual_menu();
render_content_menu();
?>
<div class="guidelines-container">
    <button id="guidelines-toc" onclick="jQuery('nav.guidelines-sidebar').slideToggle(150);">
        <i class="fa-solid fa-bars"></i>
        <span> <?php echo escape($lang['brand_guidelines_view_table_of_content']); ?></span>
    </button>
    <nav class="guidelines-sidebar">
    <?php
    foreach ($all_pages as $s => $section) {
        render_navigation_item($section, false);

        if (isset($section['children'])) {
            foreach ($section['children'] as $i => $page) {
                render_navigation_item(
                    $page,
                    (
                        $selected_page == $page['ref']
                        || (
                            $selected_page == 0
                            && $s === array_key_first($all_pages)
                            && $i === array_key_first($section['children'])
                        )
                    )
                );

                if (acl_can_edit_brand_guidelines() && $i === array_key_last($section['children'])) {
                    render_navigation_item(
                        [
                            'ref' => 0,
                            'name' => $lang['brand_guidelines_new_page'],
                            'parent' => $section['ref'],
                        ],
                        false
                    );
                }
            }
        } elseif (acl_can_edit_brand_guidelines()) {
            render_navigation_item(
                [
                    'ref' => 0,
                    'name' => $lang['brand_guidelines_new_page'],
                    'parent' => $section['ref'],
                ],
                false
            );
        }
    }

    if (acl_can_edit_brand_guidelines()) {
        render_navigation_item(
            [
                'ref' => 0,
                'name' => $lang['brand_guidelines_new_section'],
                'parent' => 0,
            ],
            false
        );
    }
    ?>
    </nav>
    <div class="BasicsBox">
        <div class="guidelines-content" data-page="<?php echo escape((string) $selected_page); ?>">
            <h1><?php echo escape($selected_page_title); ?></h1>
            <?php
            foreach ($page_contents_grouped as $item) {
                if ($item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['text']) {
                    $new_content_btn_id = $item['ref'];
                    ?>
                    <div id="page-content-item-<?php echo (int) $item['ref']; ?>"><?php
                        render_item_top_right_menu($item['ref']);
                        echo richtext_input_parser($item['content']['richtext']);
                    ?></div>
                    <?php
                } elseif (
                    $item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['resource']
                    && $item['content']['layout'] === 'full-width'
                ) {
                    render_resource_item($item);
                } elseif ($item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['group']) {
                    $members = implode(',', array_column($item['members'], 'ref'));
                    $new_content_btn_id = "group-{$members}";
                    $group_members_types = array_unique(array_column($item['members'], 'type'));
                    $is_resource_group = reset($group_members_types) === BRAND_GUIDELINES_CONTENT_TYPES['resource'];
                    ?>
                    <div class="group" data-members="<?php echo escape($members); ?>">
                    <?php
                    render_item_top_right_menu(0);

                    if ($is_resource_group) {
                        ?>
                        <div class="image-container">
                        <?php
                    }

                    foreach ($item['members'] as $group_item) {
                        $new_block_item_btn = '';
                        if ($group_item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['colour']) {
                            $new_block_item_btn = 'new guidelines-colour-block';
                            render_block_colour_item(array_merge(
                                ['ref' => $group_item['ref']],
                                $group_item['content']
                            ));
                        } elseif ($group_item['type'] === BRAND_GUIDELINES_CONTENT_TYPES['resource']) {
                            $new_block_item_btn = "new image-{$group_item['content']['layout']}";
                            render_resource_item($group_item);
                        }
                    }
                    
                    if (!(
                        $is_resource_group
                        && count($item['members']) === 2
                        && $item['members'][0]['content']['layout'] === 'half-width'
                    )) {
                        // Render the new block element for everything except second half-width item
                        render_new_block_element_button($new_block_item_btn, $group_item['type']);
                    }

                    if ($is_resource_group) {
                        ?>
                        </div>
                        <?php
                    }
                    ?>
                    </div>
                    <?php
                }

                render_new_content_button("add-new-content-after-{$new_content_btn_id}");
            }

            if ($available_pages !== [] && $page_contents === []) {
                render_new_content_button('add-new-content-end');
            }
            ?>
        </div>
    </div>
</div>
<script>
    function showOptionsMenu(e, target) {
        console.debug('showOptionsMenu(e = %o, target = %o)', e, target);
        hideOptionsMenu();
        const is_responsive = window.matchMedia("(max-width: 600px)").matches;
        const el_pos = calculate_position_offset_parents(e);
        const btn_el = jQuery(e);
        let menu_el = jQuery(`#${target}`);
        let manage_page = 'content';
        let group_members_csv = '';

        // Determine the position offset for the menu so it's within the proximity of the calling "item" element 
        if (target == 'menu-individual') {
            manage_page = btn_el.parents('.guidelines-sidebar').length !== 0 ? 'toc' : 'content';
            // Offset based on the .context-menu-container width adjusted for smaller screens
            off_left = is_responsive ? -195 : 2; 
            off_top = is_responsive ? -220 : -78;
        } else {
            off_left = -35;
            off_top = is_responsive ? -180 : -40;
        }
        console.debug("off_top = %o -- off_left = %o", off_top, off_left);

        // Alter menu options for group related page content items
        if (manage_page === 'content') {
            const group_container = btn_el.parents('.group');
            const group_members = group_container.find('div[id^="page-content-item-"]');
            const menu_options = {
                edit: { element: 'button:has(> i.fa-pen-to-square)', show: true },
                move_up: { element: 'button:has(> i.fa-chevron-up)', show: true },
                move_down: { element: 'button:has(> i.fa-chevron-down)', show: true },
                move_left: { element: 'button:has(> i.fa-chevron-left)', show: false },
                move_right: { element: 'button:has(> i.fa-chevron-right)', show: false },
            };
            const first_group_member = group_members.first().is(btn_el.parent());
            const last_group_member = group_members.last().is(btn_el.parent());

            if (first_group_member && !last_group_member) {
                menu_options.move_up.show = true;
                menu_options.move_right.show = true;
                menu_options.move_down.show = false;
            } else if (last_group_member && !first_group_member) {
                menu_options.move_up.show = false;
                menu_options.move_left.show = true;
                menu_options.move_down.show = true;
            } else if (
                !(first_group_member || last_group_member)
                && group_members.filter(`#${btn_el.parent().attr('id')}`).length
            ) {
                menu_options.move_left.show = true;
                menu_options.move_right.show = true;
                menu_options.move_up.show = false;
                menu_options.move_down.show = false;
            } else if (btn_el.parent('.group').length) {
                // Group functionality (delete or re-order entire group)
                menu_options.edit.show = false;
                group_members_csv = group_container.data('members');
            }

            Object.values(menu_options).forEach((option, i) => {
                if (option.show) {
                    menu_el.find(option.element).removeClass('DisplayNone');
                } else {
                    menu_el.find(option.element).addClass('DisplayNone');
                }
            });
        }

        menu_el
            .css({
                display: 'none',
                top: el_pos.top + off_top,
                left: el_pos.left + off_left,
            })
            .data(
                'item',
                {
                    ref: btn_el.data('item-ref'),
                    manage_page: manage_page,
                    position_after: get_previous_page_content_item_id(e),
                    group_members: group_members_csv,
                }
            )
            .slideDown(150);
    }

    function hideOptionsMenu() {
        let menu_individual = jQuery('#menu-individual');
        let menu_content = jQuery('#menu-content');
        if (menu_individual.is(':visible')) {
            menu_individual.slideUp(150);
        }
        if (menu_content.is(':visible')) {
            menu_content.slideUp(150);
        }
    }

    jQuery('#CentralSpace').on('ModalClosed', (e, modal) => {
        const modal_url = new URL(modal.url);
        const modal_url_params = modal_url.searchParams;

        // Handle manage text content item page (modal) closing
        if (
            modal_url.pathname === `${baseurl_short}plugins/brand_guidelines/pages/manage/content.php`
            && modal_url_params.has('type')
            && modal_url_params.get('type') === '<?php echo escape((string) BRAND_GUIDELINES_CONTENT_TYPES['text']); ?>'
        ) {
            tinymce.remove();
        }
    });

    onkeydown = (e) => {
        // On esc, close down contextual menus 
        if (e.keyCode == 27) {
            hideOptionsMenu();
        }
    };

    onmousedown = (e) => {
        // Close menus when clicking away
        if (!(e.target.closest('#menu-individual') || e.target.closest('#menu-content'))) {
            hideOptionsMenu();
        }
    };

    function edit_item(e) {
        let item = jQuery(e).parent('#menu-individual').data('item');
        console.debug('Edit item - %o', item);
        return ModalLoad(
            `${baseurl}/plugins/brand_guidelines/pages/manage/${item.manage_page}.php?${
                new URLSearchParams({ref: item.ref}).toString()
            }`,
            true,
            true
        );
    }

    function delete_item(e) {
        let item = jQuery(e).parent('#menu-individual').data('item');
        console.debug('Delete item - %o', item);

        if(confirm(
            item.group_members
                ? '<?php echo escape($lang['brand_guidelines_confirm_delete_group_members']); ?>'
                : '<?php echo escape($lang['confirm-deletion']); ?>'
        ))
            {
            let temp_form = document.createElement('form');
            temp_form.setAttribute('method', 'post');
            temp_form.setAttribute(
                'action',
                `${baseurl}/plugins/brand_guidelines/pages/manage/${item.manage_page}.php?${
                    new URLSearchParams({delete: item.ref}).toString()
                }`
            );
            <?php
            if ($CSRF_enabled) {
            ?>
                let csrf = document.createElement('input');
                csrf.setAttribute('type', 'hidden');
                csrf.setAttribute('name', '<?php echo escape($CSRF_token_identifier); ?>');
                csrf.setAttribute('value', '<?php echo generateCSRFToken($usersession, 'delete_item'); ?>');
                temp_form.appendChild(csrf);
            <?php
            }
            ?>
            if (item.group_members) {
                jQuery('<input>').attr({
                    type: 'hidden',
                    name: 'delete_group',
                    value: 'true',
                }).appendTo(temp_form)
                jQuery('<input>').attr({
                    type: 'hidden',
                    name: 'group_members',
                    value: item.group_members,
                }).appendTo(temp_form)
            }
            CentralSpacePost(temp_form, true, false, false);
            };

        hideOptionsMenu();
        return false;
    }

    function reorder_item(e, direction) {
        let item = jQuery(e).parent('#menu-individual').data('item');
        console.debug('Re-order item - %o - direction: %o', item, direction);

        let temp_form = document.createElement("form");
        temp_form.setAttribute("method", "post");
        temp_form.setAttribute(
            "action",
            `${baseurl}/plugins/brand_guidelines/pages/manage/${item.manage_page}.php?${
                new URLSearchParams({
                    ref: item.ref,
                    reorder: direction,
                }).toString()
            }`
        );
        <?php
        if ($CSRF_enabled) {
        ?>
            let csrf = document.createElement("input");
            csrf.setAttribute("type", "hidden");
            csrf.setAttribute("name", "<?php echo escape($CSRF_token_identifier); ?>");
            csrf.setAttribute("value", "<?php echo generateCSRFToken($usersession, "toc_reorder_item"); ?>");
            temp_form.appendChild(csrf);
        <?php
        }
        ?>
        if (item.group_members) {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'group_members',
                value: item.group_members,
            }).appendTo(temp_form)
        }
        CentralSpacePost(temp_form, false, true, false);
        hideOptionsMenu();
        return false;
    }

    function new_content_item(e) {
        console.debug('new_content_item(e = %o)', e);
        hideOptionsMenu();
        let page = jQuery('div.guidelines-content').data('page');
        console.debug({page});
        const btn_el = jQuery(e);

        let item = btn_el.parent('#menu-content').data('item');
        if (!item) {
            // Add new (+) functionality gets called directly (i.e. not via showOptionsMenu())
            item = {
                ref: 0,
                manage_page: 'content',
                position_after: get_previous_page_content_item_id(e),
            };
        }
        console.debug({item});

        const type = btn_el.data('item-type');
        console.debug({type});

        return ModalLoad(
            baseurl + '/plugins/brand_guidelines/pages/manage/content.php?'
            + new URLSearchParams({
                page: page,
                type: type,
                after_item: item.position_after,
            }).toString(),
            true,
            true
        );
    }

    /**
     * Helper function to get the previous content item ID.
     * @param {Element} e DOM element
     * @return {Number}
     */
    function get_previous_page_content_item_id(e)
    {
        const pci_prefix = 'page-content-item-';
        let prev_pci = jQuery(e).prev(`div[id^="${pci_prefix}"]`);
        if (prev_pci.length === 0) {
            prev_pci = jQuery(e).prev('.group').find(`div[id^="${pci_prefix}"]`).last();
        }
        return position_item_after = prev_pci.length !== 0
            ? parseInt(prev_pci.attr('id').substring((pci_prefix.length)), 10)
            : 0;
    }
</script>
<?php
include_once RESOURCESPACE_BASE_PATH . '/include/footer.php';
