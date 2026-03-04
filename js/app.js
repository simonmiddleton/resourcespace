window.ResourceSpace = {
    Modules: {},

    init: function () {
        // Init all registered modules (they can self-skip if DOM isn't present; for an example see header.js)
        for (const name in ResourceSpace.Modules) {
            if (ResourceSpace.Modules.hasOwnProperty(name) && ResourceSpace.Modules[name].init) {
                ResourceSpace.Modules[name].init();
            }
        }
    },
};

// todo: determine if this is really the best place to initialise. We might need to do this routine on each page load instead
jQuery(() => ResourceSpace.init());