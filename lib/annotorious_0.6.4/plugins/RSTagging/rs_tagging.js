annotorious.plugin.RSTagging = function(opt_config_options)
    {
    console.log('RSTagging: setting config options');

    // this._tags           = [];
    this._NODES_ENDPOINT = opt_config_options['nodes_endpoint'];
    }


// Add initialization code here, if needed (or just skip this method if not)
annotorious.plugin.RSTagging.prototype.initPlugin = function(anno)
    {
    console.log('RSTagging: initializing plugin');
    }


annotorious.plugin.RSTagging.prototype.onInitAnnotator = function(annotator)
    {
    console.log('RSTagging: onInitAnnotator...');

    // Remove the "Click and drag to annotate" we get on top of the image
    jQuery('.annotorious-hint').remove();
    console.log('RSTagging: removed .annotorious-hint element from DOM');

    this._extendPopup(annotator);
    this._extendEditor(annotator);
    }


annotorious.plugin.RSTagging.prototype._extendPopup = function(annotator)
    {
    console.log('RSTagging: extending Annotorious popup...');

    annotator.popup.addField(function(annotation)
        {
        /*Annotorious uses one popup box for all annotations
        We have to remove all the tags from popup that may belong
        to an old annotation we hovered over before*/
        jQuery('#RSTagging-tags').remove();

        if(typeof annotation.tags === 'undefined' || annotation.tags.length == 0)
            {
            return false;
            }

        var popup_container = document.createElement('div');
        popup_container.setAttribute('id', 'RSTagging-tags');

        for(var key in annotation.tags)
            {
            var el = document.createElement('span');

            el.className = 'RSTagging-tag';

            // Mark tags that are not attached to a resource (ie. resource_node does not have a 
            // record for this node anymore) with a warning sign
            if(annotation.tags[key].tag_searchable == '')
                {
                el.innerHTML = '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>';
                }

            el.innerHTML = el.innerHTML + annotation.tags[key].name;

            popup_container.appendChild(el);
            }

        return popup_container;
        });
    }


annotorious.plugin.RSTagging.prototype._extendEditor = function(annotator)
    {
    console.log('RSTagging: extending Annotorious editor...');

    // console.log(annotator);
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