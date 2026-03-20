ResourceSpace.Modules.Header = (() => {
    const header = document.getElementById('header-container');
    const uiCenter = document.getElementById('UICenter');
    let stickyHeaderLastScrollTop = 0;

    function init() {
        if (!header) return;

        bindEvents();
    }

    function bindEvents() {
        // For mobile devices, make only the header search be "sticky"
        const mq_mobile = window.matchMedia('(max-width: 360px)');
        mq_mobile.addEventListener('change', makeStickyHeader);
        makeStickyHeader(mq_mobile);

        header.querySelector('.header-search-field .input-wrapper > button').addEventListener('click', (e) => {
            e.stopPropagation();

            const mq_tablet = window.matchMedia('(max-width: 768px)');
            const is_small_screen = mq_tablet.matches;

            // WIP!!!
            const form = e.target.closest('form');
            const panel = document.getElementById('search-filters-panel');
            const isOpen = form.classList.toggle('filters-open');
            e.target.setAttribute('aria-expanded', String(isOpen));

            if (isOpen) {
                panel.hidden = false;

                if (is_small_screen) {
                    e.target.classList.replace('icon-sliders-horizontal', 'icon-circle-x');
                }
            } else {
                panel.addEventListener(
                    'transitionend',
                    () => {
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
        });

        // todo: see if we can abstract this for settings, messages and user profile
        // header.find('#header-action-settings').on('click', function (event) {
        //     // event.stopPropagation();
        //     // alert('Clicked header-action-settings!');
        //     ModalClose();return ModalLoad(this,true,true,'right');
        // });
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

    return {
        init: init
    };
})();