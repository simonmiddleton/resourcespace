/**
 * A general menu overlay module.
 *
 * The menu overlay requires the following HTML attibutes to function correctly:
 * - data-menu;
 * - data-menu-panel role="menu" hidden - the actual menu that pops up;
 * - data-menu-trigger aria-haspopup="menu" aria-expanded="false" - (usually) the button which triggers the menu panel
 * - role="menuitem" - to help keyboard users focus on the first item (and navigate through them) when the menu opens;
 *
 * The generic styling is done using the following elements: #menu-overlay, .menu-panel, .menu.is-open .menu-panel, .menu-item. 
 * The li.menu-overflow is used purely to create valid HTML.
 *
 * Example HTML structure used for the primary navigation overflown links:
* ```html
* <nav class="primary-navigation menu" aria-label="Main menu" data-menu>
*     <ul>
*         <li>
*             <a href="/pages/collections_featured.php" onclick="return CentralSpaceLoad(this, true);">Collections</a>
*         </li>
*         <li>
*             <a href="/pages/search.php?search=%21last1000&amp;order_by=resourceid&amp;sort=desc" onclick="return CentralSpaceLoad(this, true);">Browse</a>
*         </li>
*         <li class="menu-overflow">
*             <ul class="menu-panel" data-menu-panel role="menu" hidden>
*                 <li class="menu-item">
*                     <a href="https://example.com" target="_blank" rel="noopener noreferrer" role="menuitem">Example external link</a>
*                 </li>
*                 <li class="menu-item">
*                     <a href="/pages/contact.php" onclick="return CentralSpaceLoad(this, true);" role="menuitem">Contact us</a>
*                 </li>
*             </ul>
*         </li>
*     </ul>
*     <button type="button" class="icon-ellipsis-vertical" data-menu-trigger aria-haspopup="menu" aria-expanded="false"></button>
* </nav>
* ```
 */
ResourceSpace.Modules.MenuOverlay = (() => {
    const overlay = document.getElementById('menu-overlay');
    const templateMenuCloseBtn = document.getElementById('menu-overlay-close-button');
    let openMenu = null;
    const mq_mobile = ResourceSpace.media.max('tablet');

    function init() {
        if (!overlay) return;

        bindEvents();
    }

    function bindEvents() {
        document.addEventListener('click', (e) => {
            const menu = e.target.closest('[data-menu]');
            if (!menu) return;

            // If needed to allow clicks within the menu panel, consider separating out some of the logic to a
            // different listener (more specific like the panel itself).
            const openBtn = menu.querySelector('[data-menu-trigger]');
            const closeBtn = menu.querySelector('[data-menu-trigger-close]');
            const trigger = e.target === openBtn || e.target === closeBtn
                ? e.target
                : e.target.closest('[data-menu-panel]');

            if (!trigger) return;

            // e.preventDefault(); // IMPORTANT: commented to allow external links in the primary navigation to continue
            // working. If we need to use this, consider adding some logic to cater for both cases.
            toggle(menu);
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && openMenu) close(openMenu);
        });

        // Close when clicking on the overlay
        overlay.addEventListener('click', () => {
            if (openMenu) close(openMenu);
        });

        // Reposition on resize/scroll
        window.addEventListener('resize', () => {
            if (!openMenu) return;
            const trigger = openMenu.querySelector('[data-menu-trigger]');
            const panel = openMenu.querySelector('[data-menu-panel]');
            if (trigger && panel && !panel.hidden) positionPanel(trigger, panel);
        });

        window.addEventListener('scroll', () => {
            if (!openMenu) return;
            const trigger = openMenu.querySelector('[data-menu-trigger]');
            const panel = openMenu.querySelector('[data-menu-panel]');
            if (trigger && panel && !panel.hidden) positionPanel(trigger, panel);
        }, true);
    }

    function showOverlay() {
        overlay.hidden = false;
    }

    function hideOverlay() {
        overlay.hidden = true;
    }

    function setExpanded(menu, expanded) {
        const trigger = menu.querySelector('[data-menu-trigger]');
        if (trigger) {
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
    }

    function positionPanel(trigger, panel) {
        const r = trigger.getBoundingClientRect();

        // Default: below trigger, right-aligned.
        const gap = 6;
        let top = r.bottom + gap;
        let left = r.right - panel.offsetWidth;

        // Clamp into viewport with a small margin
        const margin = 16;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        left = Math.max(margin, Math.min(left, vw - panel.offsetWidth - margin));

        // If it would go off the bottom, flip it above
        if (top + panel.offsetHeight + margin > vh) {
            top = r.top - panel.offsetHeight - gap;
        }
        top = Math.max(margin, Math.min(top, vh - panel.offsetHeight - margin));

        panel.style.top = `${top}px`;
        panel.style.left = `${left}px`;
    }

    function open(menu) {
        console.debug('Open menu (%o)', menu);

        if (openMenu && openMenu !== menu) {
            close(openMenu);
        }

        const trigger = menu.querySelector('[data-menu-trigger]');
        const panel = menu.querySelector('[data-menu-panel]');
        if (!trigger || !panel) return;

        // Make the panel measurable before positioning
        panel.hidden = false;
        menu.classList.add('is-open');
        showOverlay();
        setExpanded(menu, true);

        if (mq_mobile.matches) {
            const closeBtn = templateMenuCloseBtn.content.cloneNode(true);
            menu.appendChild(closeBtn);
        }

        // Ensure layout has panel dimensions
        requestAnimationFrame(() => {
            positionPanel(trigger, panel);

            // Focus the first item for keyboard users
            const firstItem = panel.querySelector('[role="menuitem"]');
            if (firstItem) {
                firstItem.focus({ preventScroll: true });
            }
        });

        openMenu = menu;
    }

    function close(menu) {
        console.debug('Close menu (%o)', menu);

        const panel = menu.querySelector('[data-menu-panel]');
        if (panel) {
            panel.hidden = true;
        }

        menu.classList.remove('is-open');
        hideOverlay();
        setExpanded(menu, false);

        if (mq_mobile.matches) {
            menu.querySelector('[data-menu-trigger-close]')?.remove();
        }

        if (openMenu === menu) {
            openMenu = null;
        }
    }

    function toggle(menu) {
        if (menu.classList.contains('is-open')) {
            close(menu);
        } else {
            open(menu);
        }
    }

    return {
        init: init
    };
})();
