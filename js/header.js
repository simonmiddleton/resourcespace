ResourceSpace.Modules.Header = (() => {
    function init() {
        const header = jQuery('#header-container');
        if (!header.length) {
            return;
        }

        bindEvents(header);
    }

    function bindEvents(header) {
        jQuery('.header-search-field .input-wrapper > button').on('click', function (event) {
            alert('Clicked header-search-field button!');
            // event.stopPropagation();
            // todo: show filter menu (note: in responsive, change button class to "icon-circle-x")
        });

        // todo: see if we can abstract this for settings, messages and user profile
        // header.find('#header-action-settings').on('click', function (event) {
        //     // event.stopPropagation();
        //     // alert('Clicked header-action-settings!');
        //     ModalClose();return ModalLoad(this,true,true,'right');
        // });
    }

    return {
        init: init
    };
})();