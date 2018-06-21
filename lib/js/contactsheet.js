var contactsheet_previewimage_prefix = "";

(function($) {

	 var methods = {
		
        preview : function(collection, filename_uid)
            {
            var url      = baseurl_short + 'pages/ajax/contactsheet.php';
            var formdata = $('#contactsheetform').serialize() + '&preview=true';

            $.ajax({
                url    : url,
                data   : formdata,
                success: function(response)
                    {
                    $(this).rsContactSheet('refresh', response, collection, filename_uid);

                    if(typeof chosen_config !== 'undefined' && chosen_config['#CentralSpace select'] !== 'undefined')
                        {
                        $('#CentralSpace select').trigger('chosen:updated');
                        }
                    },
                beforeSend: function(response) {loadIt();}
                });
            },

        refresh : function(pagecount, collection, filename_uid)
            {
            document.previewimage.src = baseurl_short + 'pages/download.php?tempfile=contactsheet_' + collection + '_' + filename_uid + '.jpg&rnd' + Math.random();

            if(pagecount > 1)
                {
                $('#previewPageOptions').show();

                pagecount++;

                curval                              = $('#previewpage').val();
                $('#previewpage')[0].options.length = 0;

                for(x = 1; x < pagecount; x++)
                    {
                    selected       = false;
                    var selecthtml = '';

                    if(x == curval)
                        {
                        selected = true;
                        }

                    if(selected)
                        {
                        selecthtml = ' selected="selected" ';
                        }

                    $('#previewpage').append('<option value=' + x + ' ' + selecthtml + '>' + x + '/' + (pagecount - 1) + '</option>');
                    }
                }
            else
                {
                $('#previewPageOptions').hide();
                }
            },

        revert: function(collection,filename_uid)
            {
            $('#previewpage')[0].options.length = 0;
            $('#previewpage').append(new Option(1, 1,true,true));
            $('#previewpage').value=1;
            $('#previewPageOptions').hide();
            $(this).rsContactSheet('preview',collection,filename_uid);
            }
	};

  $.fn.rsContactSheet = function( method ) {
    
    // Method calling logic
    if ( methods[method] ) {

      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    }  
  
  };
	

})(jQuery)


function loadIt()
    {
    if(document && document.previewimage && document.previewimage.src)
        {
        document.previewimage.src = baseurl_short+'gfx/images/ajax-loader-on-sheet.gif';
        }
    }
