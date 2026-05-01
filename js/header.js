ResourceSpace.Modules.Header = (() => {
    const header = document.getElementById('header-container');
    const uiCenter = document.getElementById('UICenter');
    let stickyHeaderLastScrollTop = 0;

    const nav = document.querySelector('#header-container > nav.primary-navigation');
    const primaryNavOverflow = {

    };

    function init() {
        if (!header) return;

        bindEvents();
    }

    function bindEvents() {
        // For mobile devices, make only the header search be "sticky"
        const mq_mobile = ResourceSpace.media.max('tablet');
        mq_mobile.addEventListener('change', makeStickyHeader);
        makeStickyHeader(mq_mobile);

        header.addEventListener('click', (e) => {
            // The search container is being replaced in the DOM as part of the reloadSearchBar()
            const headerSearch = e.target.closest('.header-search-field');
            if (!headerSearch) return;

            if (
                // The open/close buttons for the search (filter) panel
                e.target.matches('.input-wrapper > button')
                || e.target.matches('section > button')
                
                // Clicking on the search (filter) panel links (except the clear filters ones), or on the search button
                // will close the panel
                || e.target.matches('#simplesearchbuttons > a:not(:first-of-type) i')
                || e.target.matches('#simplesearchbuttons > a:not(:first-of-type) span')
                // Using e.detail to avoid trigger being called when the form is submitted on Enter (which seems to call
                // click on the submit input virtually)
                || (e.target.matches('#simplesearchbuttons > input') && e.detail > 0)
            ) {
                toggleSearchPanel(e);
            }
        });

        // todo: work on overflowing some of the link if they don't fit on screen
        // Primary navigation links will overflow on smaller desktop sizes (e.g. 1366 - 1500px) 
        window.addEventListener('resize', handlePrimaryNavigationOverflow);
        // window.addEventListener('load', handlePrimaryNavigationOverflow);
        handlePrimaryNavigationOverflow();
    }

    function makeStickyHeader(e) {
        if (e.matches) {
            uiCenter.addEventListener('scroll', handleStickyHeaderScroll);
        } else {
            uiCenter.removeEventListener('scroll', handleStickyHeaderScroll);
            resetStickyHeader();
        }
    }

    function handleStickyHeaderScroll() {
        const currentScroll = uiCenter.scrollTop;
        const scrollThreshold = 10; // avoid jittering
        const logo = header.querySelector('.logo');
        const actions = header.querySelector('.actions');

        if (currentScroll > stickyHeaderLastScrollTop + scrollThreshold) {
            logo.classList.add('is-collapsed');
            actions.classList.add('is-collapsed');
            header.classList.add('is-compact');
        } else if (currentScroll < stickyHeaderLastScrollTop - scrollThreshold) {
            resetStickyHeader();
        }

        stickyHeaderLastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    }

    function resetStickyHeader() {
        const logo = header.querySelector('.logo');
        const actions = header.querySelector('.actions');

        logo.classList.remove('is-collapsed');
        actions.classList.remove('is-collapsed');
        header.classList.remove('is-compact');
    }

    function toggleSearchPanel(e) {
        e.stopPropagation();

        const is_small_screen = ResourceSpace.media.max('desktop').matches;
        const form = e.target.closest('form');
        const panel = document.getElementById('search-filters-panel');
        const isOpen = form.classList.toggle('filters-open');
        form.querySelector('.input-wrapper > button')
            .setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            panel.hidden = false;

            if (is_small_screen) {
                e.target.classList.replace('icon-sliders-horizontal', 'icon-circle-x');
            }
        } else {
            panel.addEventListener(
                'transitionend',
                (event) => {
                    if (event.propertyName !== 'opacity') return;

                    if (!form.classList.contains('filters-open')) {
                        panel.hidden = true;

                        if (is_small_screen) {
                            e.target.classList.replace('icon-circle-x', 'icon-sliders-horizontal');
                        }
                    }
                },
                { once: true }
            );
        }
    }

    function reloadSearchBar() {
        console.debug('Reloading the search (filter) panel container...');
        const searchBar_jq = jQuery('search#SearchBox');
        const url = `${baseurl_short}pages/ajax/reload_searchbar.php?ajax=true&pagename=${encodeURIComponent(pagename)}`;

        jQuery.get(url, function(response) {
            const response_jq = jQuery(response);
            const newSearchBar_jq = response_jq.is('search#SearchBox')
                ? response_jq
                : response_jq.find('search#SearchBox').first();

            if (!newSearchBar_jq.length) {
                console.warn('Invalid response content received (no search#SearchBox)! Unable to reload the search (filter) panel container.');
                return;
            }

            searchBar_jq.replaceWith(newSearchBar_jq);

            if (typeof chosen_config !== 'undefined' && typeof chosen_config['#SearchBox select'] !== 'undefined') {
                jQuery('#SearchBox select').each(function() {
                    ChosenDropdownInit(this, '#SearchBox select');
                });
            }
        });

        return;
    }

    function handlePrimaryNavigationOverflow() {
        console.debug('calling handlePrimaryNavigationOverflow...');
        console.debug('nav = %o', nav);

        // note (useful or not): at 1366, the nav can only be about 25% width wise - maybe use it to simplify calculations
        // restorePrimaryNavOverflowItems();
    }

    function restorePrimaryNavOverflowItems() {
        items
        .sort((a, b) => a.dataset.navIndex - b.dataset.navIndex)
        .forEach((item) => {
            item.classList.remove('menu-item');
            const anchor = item.querySelector('a');
            anchor?.removeAttribute('role');

            list.insertBefore(item, panel);
        });
    }

    return {
        init: init,
        reloadSearchBar: reloadSearchBar,
    };
})();