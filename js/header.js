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
            // todo: show filter menu (note: in responsive, change button class to "icon-circle-x")
            e.stopPropagation();
            alert('Clicked header-search-field button!');
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