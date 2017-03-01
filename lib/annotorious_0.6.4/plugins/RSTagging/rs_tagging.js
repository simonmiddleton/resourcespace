annotorious.plugin.RSTagging = function (opt_config_options)
    {
    console.log('RSTagging: setting config options');

    this._ANNOTATIONS_ENDPOINT = opt_config_options['annotations_endpoint'];
    this._NODES_ENDPOINT       = opt_config_options['nodes_endpoint'];
    this._RESOURCE             = opt_config_options['resource'];

    /*
    Potential feature (implemented on popup only)
    The ability to show users that a tag which was originally attached to an
    annotation is no longer part of that resource metadata and can no longer
    be searched by it. It will show a warning icon before the tag.

    IMPORTANT: if enabling this feature, just uncomment the line below and remove
    the hardcoded value for warn_tags_unsearchable
    */
    // this._WARN_TAGS_UNSEARCHABLE = opt_config_options['warn_tags_unsearchable'];
    this._WARN_TAGS_UNSEARCHABLE = false;
    }


annotorious.plugin.RSTagging.prototype.initPlugin = function (anno)
    {
    console.log('RSTagging: initializing plugin');

    var self = this;

    // Delete annotation
    anno.addHandler('onAnnotationRemoved', function (annotation)
        {
        jQuery.post(
            self._ANNOTATIONS_ENDPOINT,
            {
            action       : 'delete',
            annotation_id: annotation.ref
            },
            function (response)
                {
                if(typeof response.data !== 'undefined' && response.data == true)
                    {
                    console.log('RSTagging: deleted annotation (ID ' + annotation.ref + ')');
                    }
                else if(typeof response.data !== 'undefined' && response.data == false)
                    {
                    console.error('RSTagging: could not delete annotation (ID ' + annotation.ref + ')');
                    }
                },
            'json');
        });
    }


annotorious.plugin.RSTagging.prototype.onInitAnnotator = function (annotator)
    {
    console.log('RSTagging: onInitAnnotator...');

    // Remove the "Click and drag to annotate" we get on top of the image
    jQuery('.annotorious-hint').remove();
    console.log('RSTagging: removed .annotorious-hint element from DOM');

    // Get annotations
    jQuery.get(
        this._ANNOTATIONS_ENDPOINT,
        {
        action  : 'get_resource_annotations', 
        resource: this._RESOURCE
        },
        function (response)
            {
            if(typeof response.data !== 'undefined' && response.data.length > 0)
                {
                for(var key in response.data)
                    {
                    anno.addAnnotation(response.data[key]);
                    }
                }
            },
        'json'
    );

    this._extendPopup(annotator);
    this._extendEditor(annotator);
    }


annotorious.plugin.RSTagging.prototype._extendPopup = function (annotator)
    {
    console.log('RSTagging: extending Annotorious popup...');

    var self = this;

    // Remove text field from popup
    // IMPORTANT: until we implement text fields as nodes, this will have
    // to be hidden from the interface
    jQuery('.annotorious-popup-text').hide();
    console.log('RSTagging: hid .annotorious-popup-text element');

    annotator.popup.addField(function(annotation)
        {
        return self._renderAnnotationTags(annotation);
        });
    }


annotorious.plugin.RSTagging.prototype._extendEditor = function(annotator)
    {
    console.log('RSTagging: extending Annotorious editor...');

    var self = this;

    // Add wizard
    // Note: if there is only one field allowed to be used for annotations, don't bother showing the dropdown,
    // make it the only field to search and just display the text box and tags
    annotator.editor.addField(function (annotation)
        {
        return self._renderWizard(annotation);
        });

    annotator.editor.addField(function (annotation)
        {
        return self._renderAnnotationTags(annotation, false);
        });
    }


/**
* Render tags on Annotorious popup/ editor
* 
* @param {Object} {Annotation} annotation     - Annotation object as retrieved by Annotorious or
*                                               provided by us as object literal
* @param {boolean}             [fixed = true] - By default this means we just show all tags without being
*                                               able to click on them or other functionality available
*                                               that can change their state
* 
* @returns {boolean|Element}
*/
annotorious.plugin.RSTagging.prototype._renderAnnotationTags = function (annotation, fixed)
    {
    var self = this;

    /*
    Annotorious uses one popup/ editor box for all annotations.
    We have to remove all the tags from it that may belong to an 
    old annotation we used before
    */
    jQuery('#RSTagging-tags').remove();

    if(typeof annotation.tags === 'undefined' || annotation.tags.length == 0)
        {
        return false;
        }

    if(typeof fixed === 'undefined')
        {
        fixed = true;
        }

    var tags_container = document.createElement('div');
    tags_container.setAttribute('id', 'RSTagging-tags');

    for(var key in annotation.tags)
        {
        if(!self._WARN_TAGS_UNSEARCHABLE && annotation.tags[key].tag_searchable == '')
            {
            continue;
            }

        var el = document.createElement('span');
        el.className = 'RSTagging-tag';

        // Mark tags that are not attached to a resource (ie. resource_node does not have a 
        // record for this node anymore) with a warning sign
        if(typeof annotation.tags[key].tag_searchable !== 'undefined' 
            && annotation.tags[key].tag_searchable == ''
        )
            {
            el.innerHTML = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>';
            }

        el.innerHTML = el.innerHTML + annotation.tags[key].name;

        tags_container.appendChild(el);
        }

    var clearer = document.createElement('div');
    clearer.className = 'clearer';
    tags_container.appendChild(clearer);

    return tags_container;
    }


annotorious.plugin.RSTagging.prototype._renderWizard = function (annotation)
    {
    console.log('RSTagging: rendering Wizard...');

    var self = this;

    jQuery('.annotorious-editor-text').hide();

    return false;
    }


/*annotorious.plugin.RSTagging.prototype._extendEditor = function(annotator)
    {
    var self      = this;
        container = document.createElement('div');

    // container.className = 'RSTagging-editor-container';

    var addTag = function(annotation, suggested_tag)
        {
        // if(!annotation.tags)
        //     {
        //     annotation.tags = [];
        //     }

        annotation.tags.push(suggested_tag.label);
        };

    // Add a key listener to Annotorious editor (and binds stuff to it)
    annotator.editor.element.addEventListener('keyup', function(event)
        {
        var annotation = annotator.editor.getAnnotation();
        var text       = annotation.text;

        args = {
            field: '3',
            term: text,
            readonly: true
            }

        jQuery.ajax({
            type    : 'GET',
            url     : self._ENDPOINT_URL,
            data    : args,
            dataType: 'json',
            success : function(ajax_response)
                {
                annotation.tags = [];

                for(var key in ajax_response)
                    {
                    addTag(annotation, ajax_response[key]);
                    }
                }
            });
        });

    annotator.editor.addField(function(annotation)
        {
        // console.clear();
        // console.log(annotation);

        if(!annotation)
            {
            return false;
            }

        for(var key in annotation.tags)
            {
            var el = document.createElement('a');

            el.href      = '#';
            el.className = 'RSTagging-tag RSTagging-popup-tag';
            el.innerHTML = annotation.tags[key] + '<br>';

            container.appendChild(el);
            }

        return container;
        });

    }*/