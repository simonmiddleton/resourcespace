annotorious.plugin.RSTagging = function(opt_config_options)
    {
    // this._tags         = [];
    this._ENDPOINT_URL = opt_config_options['endpoint_url'];
    }


// Add initialization code here, if needed (or just skip this method if not)
annotorious.plugin.RSTagging.prototype.initPlugin = function(anno)
    {
    console.log('init code');
    }


annotorious.plugin.RSTagging.prototype.onInitAnnotator = function(annotator)
    {
    jQuery('.annotorious-hint').remove();
    this._extendPopup(annotator);
    this._extendEditor(annotator);
    }







annotorious.plugin.RSTagging.prototype._extendPopup = function(annotator)
    {
    annotator.popup.addField(function(annotation)
        {
        if(!annotation.tags)
            {
            return false;
            }

        var popup_container = document.createElement('div');

        for(var key in annotation.tags)
            {
            var el = document.createElement('a');

            el.href      = '#';
            el.className = 'RSTagging-tag RSTagging-popup-tag';
            el.innerHTML = annotation.tags[key] + '<br>';

            popup_container.appendChild(el);
            }

        return popup_container;
        });
    }


annotorious.plugin.RSTagging.prototype._extendEditor = function(annotator)
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

    }