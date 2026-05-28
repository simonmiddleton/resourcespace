window.ResourceSpace = {
    Modules: {},
    config: {
        // Breakpoints as defined by the CSS
        breakpoints: {
            mobile: 360,
            tablet: 768,
            desktop: 1366,
        },
    },

    media: {
        min(name) {
            return window.matchMedia(`(min-width: ${ResourceSpace.config.breakpoints[name]}px)`);
        },

        max(name) {
            return window.matchMedia(`(max-width: ${ResourceSpace.config.breakpoints[name] - 1}px)`);
        },

        between(min, max) {
            return window.matchMedia(
                `(min-width: ${ResourceSpace.config.breakpoints[min]}px) and (max-width: ${ResourceSpace.config.breakpoints[max] - 1}px)`
            );
        },
    },

    init: function () {
        // Init all registered modules (they can self-skip if DOM isn't present; for an example see header.js)
        for (const module of Object.values(ResourceSpace.Modules)) {
            if (module.init) {
                module.init();
            }
        }
    },
};

jQuery(() => ResourceSpace.init());