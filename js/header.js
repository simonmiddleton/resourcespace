ResourceSpace.Modules.Header = (function () {
    function init() {
        const header = jQuery('#header-container');
        if (!header.length) {
            return;
        }

        bindEvents(header);
    }

    function bindEvents(header) {
        // header.on('click', function () {
        //     alert('Clicked header!');
        // });

        jQuery('.header-search-field .input-wrapper > button').on('click', function (event) {
            alert('Clicked header-search-field button!');
            // event.stopPropagation();
            // todo: show filter menu (note: in responsive, change button class to "icon-circle-x")
        });

        header.find('nav.primary-navigation > button').on('click', function (event) {
            // event.stopPropagation();
            alert('Clicked primary-navigation button to reveal more links!');
        });
    }

    return {
        init: init
    };
})();