annotorious.plugin.RSFaceRecognition = function(opt_config_options)
    {
    console.log('RSFaceRecognition: setting config options');

    this._FACIAL_RECOGNITION_ENDPOINT = opt_config_options['facial_recognition_endpoint'];
    this._RESOURCE                    = opt_config_options['resource'];
    }

annotorious.plugin.RSFaceRecognition.prototype.initPlugin = function(anno)
    {
    console.log('RSFaceRecognition: initializing plugin');

    var self           = this;
    var image_prepared = false;

    anno.addHandler('onSelectionCompleted', function (selectionCompletedEvent)
        {
        jQuery.post(
            self._FACIAL_RECOGNITION_ENDPOINT,
            {
            action    : 'prepare_selected_area',
            resource  : self._RESOURCE,
            shape     : selectionCompletedEvent.shape
            },
            function (response)
                {
                if(typeof response.error !== 'undefined')
                    {
                    styledalert('Error: ' + response.error.title, response.error.detail);

                    console.error('RSFaceRecognition: '
                        + response.error.status + ' '
                        + response.error.title + ' - '
                        + response.error.detail);

                    return;
                    }

                image_prepared = response.data;

                console.info('RSFaceRecognition: image_prepared = ' + image_prepared);
                },
            'json');
        });

    anno.addHandler('onEditorShown', function (annotation)
        {
        // This feature MUST not be triggered on existing annotations
        if(annotation)
            {
            return;
            }

        var tags_container = document.getElementById('RSTagging-tags');

        // IMPORTANT: because onSelectionCompleted event gets triggered later, we need to wait in order to
        // get the shape information
        setTimeout(function ()
            {
            if(!image_prepared)
                {
                return;
                }

            // With big model states (tens of thousands of faces), it can take a bit to get a prediction
            CentralSpaceShowLoading();

            jQuery.get(
                self._FACIAL_RECOGNITION_ENDPOINT,
                {
                action    : 'predict_label',
                resource  : self._RESOURCE
                },
                function (response)
                    {
                    if(typeof response.error !== 'undefined')
                        {
                        styledalert('Error: ' + response.error.title, response.error.detail);

                        CentralSpaceHideLoading();

                        console.error('RSFaceRecognition: '
                        + response.error.status + ' '
                        + response.error.title + ' - '
                        + response.error.detail);

                        return;
                        }

                    // Add a clearer after tags container (for both new and existing annotations)
                    var clearer       = document.createElement('div');
                    clearer.className = 'clearer';
                    tags_container.parentNode.insertBefore(clearer, tags_container.nextSibling);

                    // Add the returned tag
                    tags_container.appendChild(self.renderTag(response.data, 'suggested', true));

                    CentralSpaceHideLoading();
                    },
                'json');
            }, 
            200);
        });
    }

annotorious.plugin.RSFaceRecognition.prototype.onInitAnnotator = function(annotator) {}


/**
* Render an Annotation tag
* 
* @param {Object} {Tag} tag
* @param {String}       extra_classes - Add extra CSS classes to a tag
* @param {Boolean}      actionable    - Set to true if tags should be wrapped in links to
*                                     allow triggering actions
* 
* @returns {Element}
*/
annotorious.plugin.RSFaceRecognition.prototype.renderTag = function (tag, extra_classes, actionable)
    {
    var tag_element = document.createElement('span');

    // Add CSS classes
    tag_element.className = 'RSTagging-tag';
    if(typeof extra_classes !== 'undefined' && extra_classes != '')
        {
        tag_element.className += ' ' + extra_classes;
        }

    tag_element.innerHTML = '<i class="fa fa-database" aria-hidden="true"></i>' + tag.name;

    // Wrap tags in links in order to trigger certain actions (e.g: accept/denied (suggested)/ remove tags)
    if(typeof actionable !== 'undefined' && typeof actionable === 'boolean' && actionable == true)
        {
        var a_element = document.createElement('a');
        a_element.setAttribute('href', '#');
        a_element.setAttribute('onclick', 'return false;');

        // Actionable tags also have extra information which we'll need later on for 
        // saving an annotation (either new/ existing one)
        tag_element.dataset.ref               = tag.ref;
        tag_element.dataset.resourceTypeField = tag.resource_type_field;
        tag_element.dataset.name              = tag.name;
        tag_element.dataset.parent            = tag.parent;
        tag_element.dataset.orderBy           = tag.order_by;

        a_element.appendChild(tag_element);

        return a_element;
        }

    return tag_element;
    }