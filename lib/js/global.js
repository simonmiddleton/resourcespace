/* global.js : Functions to support features available globally throughout ResourceSpace */
var modalalign=false;
var modalfit=false;
var CentralSpaceLoading=false;
var ajaxinprogress=false;
	
// prevent all caching of ajax requests by stupid browsers like IE
jQuery.ajaxSetup({ cache: false });

// function to help determine exceptions
function basename(path) {
    return path.replace(/\\/g,'/').replace( /.*\//, '' );
}

// IE 8 does not support console.log unless developer dialog is open, so we need a failsafe here if we're going to use it for debugging
   var alertFallback = false;
   if (typeof console === "undefined" || typeof console.log === "undefined") {
     console = {};
     if (alertFallback) {
         console.log = function(msg) {
              alert(msg);
         };
     } else {
         console.log = function() {};
     }
   }

function is_touch_device()
    {
    return 'ontouchstart' in window // works on most browsers 
        || (navigator.maxTouchPoints > 0)
        || (navigator.msMaxTouchPoints > 0);
    }



function SetCookie (cookieName,cookieValue,nDays,global)
	{
	var today = new Date();
	var expire = new Date();
	if (global === undefined) {
          global = false;
    } 

	if (nDays==null || nDays==0) nDays=1;
	expire.setTime(today.getTime() + 3600000*24*nDays);
	if (global_cookies || global)
		{
		/* Use the root path */
		path = ";path=/";
		}
	else
        {
        path = ";path=" + baseurl_short;
        }

    // Expire first the old cookie which might not be on the desired path (path used to be set to empty string which made 
    // certain cookies be set at /pages) causing issues further down the process (e.g collection bar "thumbs" cookie which
    // was causing a "Exceeding call stack" error)
    var expired_date = new Date();
    expired_date.setTime(today.getTime() + 3600000 * 24 * -1);
    if (window.location.protocol === "https:")
        {
        document.cookie = cookieName + "=" + encodeURI(cookieValue) + ";expires=" + expired_date.toGMTString()+";secure";
        }
    else
        {
        document.cookie = cookieName + "=" + encodeURI(cookieValue) + ";expires=" + expired_date.toGMTString();
        }

    // Set cookie
	if (window.location.protocol === "https:")
        {
        document.cookie = cookieName+"="+encodeURI(cookieValue)+";expires="+expire.toGMTString()+path+";secure";
        }
    else
        {
        document.cookie = cookieName+"="+encodeURI(cookieValue)+";expires="+expire.toGMTString()+path;
        }
	}

function getCookie(c_name)
{
	var i,x,y,ARRcookies=document.cookie.split("; ");
	for (i=0;i<ARRcookies.length;i++)
	{
		x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
		y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
		x=x.replace(/^\s+|\s+$/g,"");
		if (x==c_name)
		{
			return decodeURI(y);
		}
	}
}



/* Keep a global array of timers */
var timers = new Array();
var loadingtimers = new Array();

function ClearTimers()
	{
	// Remove all existing page timers.
	for (var i = 0; i < timers.length; i++)
    	{
	    clearTimeout(timers[i]);
	    }
	}

function ClearLoadingTimers()
	{
	// Remove all existing page timers.
	for (var i = 0; i < loadingtimers.length; i++)
    	{
	    clearTimeout(loadingtimers[i]);
	    }
	}

/* AJAX loading of searchbar contents for search executed outside of searchbar */
function ReloadSearchBar()
	{
	var SearchBar=jQuery('#SearchBarContainer');
	SearchBar.load(baseurl_short+"pages/ajax/reload_searchbar.php?ajax=true&pagename="+pagename, function (response, status, xhr)
			{
			if (status=="error")
				{				
				SearchBar.html(errorpageload  + xhr.status + " " + xhr.statusText + "<br>" + response);		
				}
			else
				{
				// Load completed	
				
				// Add Chosen dropdowns, if configured
				if (typeof chosen_config !== 'undefined' && chosen_config['#SearchBox select']!=='undefined')
					{
					jQuery('#SearchBox select').each(function()
						{
						ChosenDropdownInit(this, '#SearchBox select');
						});
					}
				}		
			});		
	return false;
    }

/* Scroll to top if parameter set - used when changing pages */
function pageScrolltop(element)
	{
	jQuery(element).animate({scrollTop:0}, 'fast');
	}

/* AJAX loading of central space contents given a link */
function CentralSpaceLoad (anchor,scrolltop,modal)
	{
	ajaxinprogress=true;
	var CentralSpace=jQuery('#CentralSpace');
	
	if (typeof modal=='undefined') {modal=false;}
    
    if (basename(window.location.href).substr(0,8)=="edit.php" && typeof anchor.href!='undefined' && basename(anchor.href).substr(0,8)=="edit.php")
        {
        // Edit page not working properly in modal
        modal = false;
        }
        
	if (!modal)
	    {
	    // If what we're loading isn't a modal, close any modal. Ensures the modal closes if a link on it is clicked.
	    ModalClose();
	    }
	else
	    {
	    // Targeting a modal. Set the target to be the modal, not CentralSpace.
	    CentralSpace=jQuery('#modal');
	    }
	    
	// Handle straight urls:
	if (typeof(anchor)!=='object'){ 
		var plainurl=anchor;
		var anchor = document.createElement('a');
		anchor.href=plainurl;
	}

	/* Open as standard link in new tab (no AJAX) if URL is external */
    if (anchor.hostname != "" && window.location.hostname != anchor.hostname)
		{
		var win=window.open(anchor.href,'_blank');win.focus();
		return false;
		}

	/* Handle link normally (no AJAX) if the CentralSpace element does not exist */
	if (!CentralSpace )
		{
		location.href=anchor.href;
		return false;
		} 

	/* more exceptions, going to or from pages without header */
	var fromnoheader=false;
	var tonoheader=false;
	if (
			basename(window.location.href).substr(0,11)=="preview.php" 
			||
			basename(window.location.href).substr(0,15)=="preview_all.php" 
			||
			basename(window.location.href).substr(0,9)=="index.php" 
			||
			basename(window.location.href).substr(0,16)=="team_plugins.php"
			||
			basename(window.location.href).substr(0,19)=="search_advanced.php"
		) { 
			fromnoheader=true; 
		}

	if (	
			basename(anchor.href).substr(0,11)=="preview.php"
			||
			basename(anchor.href).substr(0,15)=="preview_all.php"
			||
			basename(anchor.href).substr(0,9)=="index.php" 
			||
			basename(anchor.href).substr(0,19)=="search_advanced.php" 
		) {
			tonoheader=true;
		}
		
    if (typeof fromnoheaderadd!=='undefined' && !modal) 
        {
        for (var i = 0; i < fromnoheaderadd.length; i++)
            {
            if (basename(window.location.href).substr(0,fromnoheaderadd[i].charindex)==fromnoheaderadd[i].page) fromnoheader=true;
            }
        }
    if (typeof tonoheaderadd!=='undefined' && !modal) 
        {
        for (var i = 0; i < tonoheaderadd.length; i++)
            {
            if (basename(anchor.href).substr(0,tonoheaderadd[i].charindex)==tonoheaderadd[i].page) tonoheader=true;
            }
        }

    // XOR to allow these pages to ajax with themselves
    if((tonoheader || fromnoheader) && !(fromnoheader && tonoheader) && !modal)
        {
        location.href = anchor.href;

        CentralSpace.trigger('CentralSpaceLoadedNoHeader', [{url: anchor.href}]);

        return false;
        }

	var url = anchor.href;
	pagename=basename(url);
	pagename=pagename.substr(0, pagename.lastIndexOf('.'));

	var end_url = url.substr(url.length-1, 1);
	// Drop # at end of url if present
	if (end_url == "#") {
		url = url.substr(0,url.length-1);
	}

	// Attach ajax parameter
	if (url.indexOf("?")!=-1)
		{
		url += '&ajax=true';
		}
	else
		{
		url += '?ajax=true';
		}

	// Reinstate # at end of url if present
	if (end_url == "#") {
		url += end_url;
	}
	
	if (modal) {url+="&modal=true";}
	
	// Fade out the link temporarily while loading. Helps to give the user feedback that their click is having an effect.
	// if (!modal) {jQuery(anchor).fadeTo(0,0.6);}
	
	// Start the timer for the loading box.
	CentralSpaceShowLoading(); 
	var prevtitle=document.title;

	CentralSpace.load(url, function (response, status, xhr)
		{
        if(xhr.status == 403)
            {
            CentralSpaceHideLoading();
            styledalert(errortext,xhr.responseText);
            }
        else if (status=="error")
			{
			CentralSpaceHideLoading();
			CentralSpace.html(errorpageload  + xhr.status + " " + xhr.statusText + "<br>" + response);
			jQuery(anchor).fadeTo(0,1);
			}
		else
			{

			// Load completed
			CentralSpaceHideLoading();
			if (!modal)
					{
					// jQuery(anchor).fadeTo(0,1);
					}
			else
				{
			    // Show the modal
			     ModalCentre();
			    jQuery('#modal_overlay').fadeIn('fast');
			    if (modalalign=='right')
				{
				// Right aligned "drop down" style modal used for My Account.
				
				jQuery('#modal').slideDown('fast');
				}
			    else
				{
				jQuery('#modal').show();
				}
			   
			    }

			// Activate or deactivate the large slideshow, if this function is enabled.			
			if (typeof ActivateSlideshow == 'function' && !modal)
			    {
			    if (basename(anchor.href).substr(0,8)=="home.php")
				{
				ActivateSlideshow();
				}
			    else
				{
				DeactivateSlideshow();
				}
			    }

		    // Only allow reordering when search results are collections
		    if(basename(anchor.href).substr(0, 10) == 'search.php')
		    	{
				var query_strings = get_query_strings(anchor.href);

				if(!is_empty(query_strings))
					{
					if(query_strings.hasOwnProperty('search') && query_strings.search.substring(0, 11) !== '!collection')
						{
						allow_reorder = false;
						}
					}

				CentralSpace.trigger('CentralSpaceSortable');
		    	}

            CentralSpace.trigger('CentralSpaceLoaded', [{url: url}]);

			// Change the browser URL and save the CentralSpace HTML state in the browser's history record.
			if(typeof(top.history.pushState)=='function' && !modal)
				{
				top.history.pushState(document.title+'&&&'+CentralSpace.html(), applicationname, anchor.href);
				}
			}
			
			/* Scroll to top if parameter set - used when changing pages */
		    if (scrolltop==true)
				{
				if (modal)
					{
					pageScrolltop(scrolltopElementModal);
					}
				else
					{
					pageScrolltop(scrolltopElementCentral);
					}
				}
		    
			// Add accessibility enhancement:
			CentralSpace.append('<!-- Use aria-live assertive for high priority changes in the content: -->');
			CentralSpace.append('<span role="status" aria-live="assertive" class="ui-helper-hidden-accessible"></span>');

			// Add global trash bin:
			CentralSpace.append(global_trash_html);
			CentralSpace.trigger('prepareDragDrop');

			// Add Chosen dropdowns, if configured
			if (typeof chosen_config !== 'undefined' && chosen_config['#CentralSpace select']!=='undefined')
				{
				jQuery('#CentralSpace select').each(function()
					{
					ChosenDropdownInit(this, '#CentralSpace select');
					});
				}
			    
			if (typeof AdditionalJs == 'function') {   
			  AdditionalJs();  
			}

            ReloadLinks();
			
        });
    
    jQuery('#UICenter').show(0);
    jQuery("#SearchBarContainer").removeClass("FullSearch");
    
    ajaxinprogress=false;
	return false;
	}


/* When back button is clicked, reload AJAX content stored in browser history record */
top.window.onpopstate = function(event)
	{

	if (!event.state) {return true;} // No state

   page=window.history.state;
   mytitle=page.substr(0, page.indexOf('&&&'));
   if (mytitle.substr(-1,1)!="'" && mytitle.length!=0) {
   page=page.substr(mytitle.length+3);
   document.title=mytitle;    

	// Calculate the name of the page the user is navigating to.
	pagename=basename(document.URL);
	pagename=pagename.substr(0, pagename.lastIndexOf('.'));

	if (pagename=="home" || pagename=="view" || pagename=="preview" || pagename=="search" || pagename=="collection_manage"){
		
		// Certain pages do not handle back navigation well as start-up scripts are not executed. Always reload in these cases.
		
		// The ultimate fix is for this all to be unnecessary due
		// to scripts initialising correctly, perhaps using an event or similar,
		// or for all initialisation to have already completed in the header.php load ~DH
		
		CentralSpaceShowLoading();
		window.location.reload(true);
		return true;
	}
	ModalClose();
	jQuery('#CentralSpace').html(page); 
 	
	}
}


/* AJAX posting of a form, result are displayed in the CentralSpace area. */
function CentralSpacePost (form,scrolltop,modal,update_history)
	{
	update_history = (typeof update_history !== "undefined" ? update_history : true);
	ajaxinprogress=true;
	var url=form.action;
	var CentralSpace=jQuery('#CentralSpace');// for ajax targeting top div

    for(instance in CKEDITOR.instances)
        {
        // Because CKEDITOR keeps track of instances in a global way, sometimes you can end up with some elements not
        // being defined anymore. For this reason, we need to first check if the element still is within DOM
        // CKEditor needs to update textareas before any posting is done otherwise it will post null values
        if(document.body.contains(document.getElementById(instance)))
            {
            CKEDITOR.instances[instance].updateElement();
            }

        // Clean CKEDITOR of any instances that are not valid anymore
        CKEDITOR.instances[instance].destroy(true);
        }
        
	formdata = jQuery(form).serialize();
	if (typeof modal=='undefined') {modal=false;}
	if (!modal)
	    {
	    // If what we're loading isn't a modal, close any modal. Ensures the modal closes if a link on it is clicked.
	    ModalClose();
	    }
	else
	    {
	    // Targeting a modal. Set the target to be the modal, not CentralSpace.
	    CentralSpace=jQuery('#modal');
	    }
	
	if (url.indexOf("?")!=-1)
		{
		url += '&ajax=true';
		}
	else
		{
		url += '?ajax=true';			
		}
	url += '&posting=true';
	CentralSpaceShowLoading();    
	
	var prevtitle=document.title;
	pagename=basename(url);
	pagename=pagename.substr(0, pagename.lastIndexOf('.'));
	jQuery.post(url,formdata,function(data)
		{
		CentralSpaceHideLoading();
		CentralSpace.html(data);
        
        jQuery('#UICenter').show(0);
        jQuery("#SearchBarContainer").removeClass("FullSearch");

        search_show=false;

		// Add global trash bin:
		CentralSpace.append(global_trash_html);
		CentralSpace.trigger('prepareDragDrop');

		// Activate or deactivate the large slideshow, if this function is enabled.			
		if (typeof ActivateSlideshow == 'function' && !modal)
		    {
		    if (basename(form.action).substr(0,8)=="home.php")
			{
			ActivateSlideshow();
			}
		    else
			{
			DeactivateSlideshow();
			}
		    }
			    
		// Change the browser URL and save the CentralSpace HTML state in the browser's history record.
		if(update_history && typeof(top.history.pushState)=='function' && !modal)
			{
			top.history.pushState(document.title+'&&&'+data, applicationname, form.action);
			}
			
		/* Scroll to top if parameter set - used when changing pages */
		if (scrolltop==true && (typeof preventautoscroll == 'undefined' || !preventautoscroll)) {
				if (modal)
				    {
				    pageScrolltop(scrolltopElementModal);
				    }
				else
				    {
				    pageScrolltop(scrolltopElementCentral);
				    }
		}
        
        // Reset scroll prevention flag
        preventautoscroll = false;
		
		// Add Chosen dropdowns, if configured
		if (typeof chosen_config !== 'undefined' && chosen_config['#CentralSpace select']!=='undefined')
			{
			jQuery('#CentralSpace select').each(function()
				{
				ChosenDropdownInit(this, '#CentralSpace select');
				});
			}
			    
		return false;
		})
	.fail(function(result,textStatus) {
		if (result.status>0)                        
			{
            CentralSpaceHideLoading();
            if(result.status == 409)
                {
                // This is used for edit conflicts - show the response
                CentralSpace.append(result.responseText);
                }
            else if(result.status == 403)
                {
                styledalert(errortext,result.responseText);
                }
            else
                {
                CentralSpace.html(errorpageload + result.status + ' ' + result.statusText + '<br>URL:  ' + url + '<br>POST data: ' + jQuery(form).serialize());
                }
			}
		});
	ajaxinprogress=false;
    
    // Reload Browse bar item if required
    if (typeof browsereload !== "undefined")
        {
        toggleBrowseElements(browsereload, true);
        }
        
	return false;
	}


function CentralSpaceShowLoading()
	{ 
	CentralSpaceLoading=true;
	ClearLoadingTimers();
	loadingtimers.push(window.setTimeout("jQuery('#CentralSpace').fadeTo('fast',0.7);jQuery('#LoadingBox').fadeIn('fast');",ajaxLoadingTimer));
	}

function CentralSpaceHideLoading()
	{
	CentralSpaceLoading=false;
	ClearLoadingTimers();
	jQuery('#LoadingBox').fadeOut('fast');  
	jQuery('#CentralSpace').fadeTo('fast',1);
	}



/* AJAX loading of CollectionDiv contents given a link */
function CollectionDivLoad (anchor,scrolltop)
	{
	// Handle straight urls:
	if (typeof(anchor)!=='object'){ 
		var plainurl=anchor;
		var anchor = document.createElement('a');
		anchor.href=plainurl;
	}
	
	/* Handle link normally if the CollectionDiv element does not exist */
	
	if (jQuery('#CollectionDiv').length==0 && top.collections!==undefined)
		{
		top.collections.location.href=anchor.href;
		return false;
		} 
		
	/* Scroll to top if parameter set - used when changing pages */
	if (scrolltop==true) {pageScrolltop(scrolltopElementCollection);}
	
	var url = anchor.href;
	
	if (url.indexOf("?")!=-1)
		{
		url += '&ajax=true';
		}
	else
		{
		url += '?ajax=true';
		}
	
	jQuery('#CollectionDiv').load(url, function ()
		{
        jQuery("#CollectionDiv").trigger("CollectionDiv_loaded");

		if(collection_bar_hide_empty){
			CheckHideCollectionBar();
			}
		});
		
		
	return false;
	}


function directDownload(url)
    {
    dlIFrma = document.getElementById('dlIFrm');

    if(typeof dlIFrma != "undefined")
        {
        dlIFrma.src = url;  
        }
    else
        {
        window.open(url, '_blank').focus();
        }
    }



/* AJAX loading of navigation link */
function ReloadLinks()
    {
    if(!linkreload)
        {
        return false;
        }
        
    var nav2=jQuery('#HeaderNav2');
    if(!nav2.has("#HeaderLinksContainer").length)
        {
        return false;
        }
        
    nav2.load(baseurl_short+"pages/ajax/reload_links.php?ajax=true", function (response, status, xhr)
        {
        // 403 is returned when user is logged out and ajax request! @see revision #12655
        if(xhr.status == 403)
            {               
            window.location = baseurl_short + "login.php";      
            }
        else if(status=="error")
            {
            var SearchBar=jQuery('#SearchBarContainer');		
            SearchBar.html(errorpageload  + xhr.status + " " + xhr.statusText + "<br>" + response);		
            }
        else
            {
            // Load completed
            ActivateHeaderLink(document.location.href);
            }
        });

    headerLinksDropdown();
    return false;
    }

function relateresources (ref,related,action)
	{
	//console.log("relateresources:" +ref + ":" + related + ":" + action);
	url=baseurl_short+"pages/ajax/relate_resources.php?ref=" + ref + "&related=" + related + "&action=" + action;
	jQuery.post(url, function (response, status, xhr)
			{
			if (response.indexOf("error") > 0)
				{				
				alert ("ERROR");
				return false;
				}
			else
				{
				jQuery('#relatedresource' + related).remove();
				return true;
				}		
			});		
		return false;	
	}

/*
When an object of class "CollapsibleSectionHead" is clicked then next element is collpased or expanded.
 */
function registerCollapsibleSections(use_cookies)
	{
	// Default params
	use_cookies = (typeof use_cookies !== 'undefined') ? use_cookies : true;

	jQuery(document).ready(function()
		{
		jQuery('.CollapsibleSectionHead').click(function()
			{
			cur    = jQuery(this).next();
			cur_id = cur.attr("id");
            var cur_state = null;

			if (cur.is(':visible'))
				{
				if(use_cookies)
					{
					SetCookie(cur_id, 'collapsed');
					}

                cur_state = "collapsed";
				jQuery(this).removeClass('expanded');
				jQuery(this).addClass('collapsed');
				}
			else
				{
				if(use_cookies)
					{
					SetCookie(cur_id, 'expanded');
					}

                cur_state = "expanded";
				jQuery(this).addClass('expanded');
				jQuery(this).removeClass('collapsed');
				}

			cur.stop(); // Stop existing animation if any
			cur.slideToggle();

            jQuery("#" + cur_id).trigger("ToggleCollapsibleSection", [{section_id: cur_id, state: cur_state}]);

			return false;
			}).each(function()
				{
				cur_id = jQuery(this).next().attr("id");

				if ((use_cookies && getCookie(cur_id) == 'collapsed') || jQuery(this).hasClass('collapsed'))
					{
					jQuery(this).next().hide();
					jQuery(this).addClass('collapsed');
					}
				else
					{
					jQuery(this).addClass('expanded');
					}
				});
		});
	}

function getQueryStrings()
{ 
	var assoc  = {};
	var decode = function(s) { return decodeURIComponent(s.replace(/\+/g, " ")); };
	var queryString = location.search.substring(1); 
	var keyValues = queryString.split('&'); 

	for(var i in keyValues) {
		if (typeof keyValues[i].split == 'function'){

			var key = keyValues[i].split('=');
			if (key.length > 1) {
				assoc[decode(key[0])] = decode(key[1]);
			} 
		}
	}

	return assoc; 
}

// Take the current query string and attach it to a form action
// Useful when avoiding moving away from the page you are on
function passQueryStrings(params, formID)
{
	var form_action = '';
	var query_string = '';
	var qs = getQueryStrings();

	if(params.constructor !== Array) {
		//console.log('Error - params in passQueryStrings function should be an array!');
		return false;
	}

	// Pass only specified params to the query string
	for(var i = 0; i < params.length; i++) {
		// console.log(params[i]);
		if(qs.hasOwnProperty(params[i]) && query_string === '') {
			query_string = params[i] + '=' + qs[params[i]];
		} else if(qs.hasOwnProperty(params[i]) && query_string !== '') {
			query_string += '&' + params[i] + '=' + qs[params[i]];
		}
	}

    form_action = document.getElementById(formID).action + '?' + query_string;

    if(document.getElementById(formID).action !== form_action) {
    	document.getElementById(formID).action = form_action;
	}

	return true;
}

// Use this function to confirm special searches
// e.g: is_special_search('!collection', 11)
function is_special_search(special_search, string_length)
{
	var query_strings = getQueryStrings();

	if(is_empty(query_strings)) {
		return false;
	}

	if(query_strings.search.substring(0, string_length) === special_search) {
		return true;
	}

	return false;
}

// Check if object is empty or not
// Note: safer solution compared to using keys()
function is_empty(object)
{
	for(var property in object) {
		
		if(object.hasOwnProperty(property)) {
			return false;
		}
	
	}

	return true;
}

// Returns object with all query strings found
// Note: should be used when the location does not have the correct URL
function get_query_strings(url)
{
	if(url.trim() === '')
		{
		//console.error('RS_debug: get_query_strings, parameter "url" can\'t be an empty string!');
		return {};
		}

	var query_strings = {};
	var url_split     = url.split('?');

	if(url_split.length === 1)
		{
		//console.log('RS_debug: no query strings found on ' + url);
		return query_strings;
		}

	url_split = url_split[1];
	url_split = url_split.split('&');

	for(var i = 0; i < url_split.length; i++)
		{
		var var_value_pair = url_split[i].split('=');
		query_strings[var_value_pair[0]] = decodeURI(var_value_pair[1]);
		}

	return query_strings;
}

function ModalLoad(url,jump,fittosize,align)
	{
	// Load to a modal rather than CentralSpace. "url" can be an anchor object.
	
	// No modal? Don't launch a modal. Go to CentralSpaceLoad
	if (!jQuery('#modal')) {return CentralSpaceLoad(url,jump);}
	
	// Window smaller than the modal? No point showing a modal as it wouldn't appear over the background.
	if (jQuery(window).width()<=jQuery('#modal').width()) {return CentralSpaceLoad(url,jump);}
	
	var top, left;


	jQuery('#modal').draggable({ handle: ".RecordHeader", opacity: 0.7 });
	
    // Set modalfit so that resizing does not change the size
    modalfit=false;
    if ((!(typeof fittosize=='undefined') && fittosize)) {
        modalfit=true;
    }
	
    // Set modalalign to store the alignment of the current modal (needs to be global so that resizing the window still correctly aligns the modal)
    modalalign=false;
    if ((!(typeof align=='undefined'))) {
        modalalign=align;
    }
    
    // To help with calling of a popup modal from full modal, can return to previous modal location
    if (!(typeof modalurl=='undefined')) {
            modalbackurl=modalurl;
            }
    
    url=SetContext(url);
    modalurl=url;
    
	return CentralSpaceLoad(url,jump,true); 
	}
	
function ModalPost(form,jump,fittosize)
	// Post to a modal rather than CentralSpace. "url" can be an anchor object.
	{
    var top, left;
	
	var url=form.action;
	jQuery('#modal_overlay').show();
	jQuery('#modal').show();
	jQuery('#modal').draggable({ handle: ".RecordHeader" });
	
     // Set modalfit so that resizing does not change the size
    modalfit=false;
    if ((!(typeof fittosize=='undefined') && fittosize)) {
        modalfit=true;
    }
    
	ModalCentre();
    
    if (!(typeof modalurl=='undefined')) {
			modalbackurl=modalurl;
			}
    modalurl=url;
	return CentralSpacePost(form,jump,true);
	}
    
function ModalCentre()
	{
	// Centre the modal and overlay on the screen. Called automatically when opened and also when the browser is resized, but can also be called manually.
	
    // If modalfit is not specified default to the full modal dimensions
	if (modalalign=='right')
	    {
        modalmaxheight=Math.max(jQuery(window).height() - 100);
	    modalwidth=480;
		if (!TileNav) {modalwidth=250;} // Smaller menu if tile navigation disabled.
	    }
	else if ((!(typeof modalfit=='undefined') && modalfit))
	    {
        modalmaxheight='auto';
	    modalwidth='auto';
	    }
	else
	    {
        modalmaxheight=jQuery('.ui-layout-container').height() - 60;
	    modalwidth=1235;
	    }
    
    
    jQuery('#modal').css({	
        maxHeight: modalmaxheight,
        width: modalwidth
    });

    // Support alignment of modal (e.g. for 'My Account')
    topmargin=30;
    if (modalalign=='right')
	{
        left = Math.max(jQuery(window).width() - jQuery('#modal').outerWidth(), 0) - 20;
        topmargin=50;
	}
    else
	{
        left = Math.max(jQuery(window).width() - jQuery('#modal').outerWidth(), 0) / 2;
	}    

    jQuery('#modal').css({
	top:topmargin + jQuery(window).scrollTop(), 
	left:left + jQuery(window).scrollLeft()
	});

    // Resize knowledge base iframe to correct size if present
    if (jQuery('#knowledge_base').length)
        {
        jQuery('#knowledge_base').css('height', modalmaxheight);
        jQuery('#modal').css('overflow', 'hidden');
        }
    else
        {
        jQuery('#modal').css('overflow', 'auto');
        }

    }
function ModalClose()
	{
	jQuery('#modal_overlay').hide();
	jQuery('#modal').hide();
	jQuery('#modal').html('');
    // Trigger an event so we can detect when a modal is closed
	if (!(typeof modalurl=='undefined')) {
			jQuery('#CentralSpace').trigger('ModalClosed',[{url: modalurl}]);
			delete modalurl;
			}
	}
	
// For modals, set what conteckt the modal was called from and insert if missing
function SetContext(url)
    {
    if(typeof url != 'string'){url = url.href;}
    var query_strings = get_query_strings(url);
    var context = query_strings.context;

    if(context==undefined){
        if(jQuery('#modal').is(":visible")){
            context='Modal';
        } else {
            context='Body';
        }
        if (url.indexOf("?")!=-1){
            url = url + '&context=' + context;
        } else {
            url = url + '?context=' + context;
        }

        
    }

    return url;
    }

// Update header links to add a class that indicates current location 
function ActivateHeaderLink(activeurl)
		{
        var matchedstring=0;
		activelink='';
		
		// Was the 'recent' link clicked?
		var recentlink=(decodeURIComponent(activeurl).indexOf('%21last')>-1 ||
		decodeURIComponent(activeurl).indexOf('!last')>-1);

		jQuery('#HeaderNav2 li a').each(function() { 
            // Remove current class from all header links
            jQuery(this).removeClass('current');

			if(decodeURIComponent(activeurl).indexOf(this.href)>-1

			// Set "recent" rather than "search results" when the search is for recent items.
			|| (recentlink && this.href.indexOf('%21last')>-1))
				{
                    if (this.href.length>matchedstring) {
                    // Find longest matched URL
                    matchedstring=this.href.length;
                    activelink=jQuery(this);
                    }				
				}				
			});
        jQuery(activelink).addClass('current');
		}

function ReplaceUrlParameter(url, parameter, value){
	var parameterstring = new RegExp('\\b(' + parameter + '=).*?(&|$)')
	if(url.search(parameterstring)>=0){
		return url.replace(parameterstring,'$1' + value + '$2');
        }
	return url + (url.indexOf('?')>0 ? '&' : '?') + parameter + '=' + value; 
	}
	
function nl2br(text) {
  return (text).replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2');
}

// Check if given JSON can be parsed or not
function isJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

function styledalert(title,text,minwidth){
 if(typeof minwidth === 'undefined') {
        minwidth = 300;
    }
    jQuery("#modal_dialog").html(text);
    jQuery("#modal_dialog").dialog({
        title: title,
        resizable: false,
        minWidth: minwidth,
        dialogClass: 'no-close',
         buttons: [{
                  text: oktext,
                  click: function() {
                    jQuery( this ).dialog( "close" );
                  }
                }],
        create: function(event, ui)
            {
            if (title == '')
                {
                jQuery('.ui-dialog-title').html('<i class=\'fa fa-info-circle\' ></i>');
                }
            }
        });

    jQuery("#modal_dialog").bind('clickoutside',function(){
        jQuery( this ).dialog( "close" );
    });
    
    }

// Restrict all defined numeric fields 
jQuery(document).ready(function()
	{
	jQuery('.numericinput').keypress(function(e)
		{
		var key = e.which || e.keyCode;
		if (!(key >= 48 && key <= 57) && // Interval of values (0-9)
			 (key !== 8))             // Backspace
			{
			e.preventDefault();
			return false;
			}
		})
	});

function HideCollectionBar() {
	if (typeof collection_bar_hidden === "undefined" || collection_bar_hidden==false){
		colbarresizeon=false;
		myLayout.options.south.spacing_open = 0;
		myLayout.options.south.spacing_closed = 0;
		myLayout.options.south.minSize = 0;
		myLayout.sizePane("south",1);	
		jQuery('#CollectionDiv').hide();
		collection_bar_hidden=true;				
		}
	}


function ShowCollectionBar() {
	if (typeof collection_bar_hidden === "undefined" || collection_bar_hidden==true){
		colbarresizeon=true;
		myLayout.options.south.spacing_open = 6;
		myLayout.options.south.spacing_closed = 6;
		myLayout.options.south.minSize = 40;	
		collection_bar_hidden=false;
		jQuery('#CollectionDiv').show();
		InitThumbs();	
	}
}
				
function CheckHideCollectionBar(){
	if(collection_bar_hide_empty==false || jQuery('#currentusercollection').length==0){
		// Option to hide empty bar not configured or collection panel not yet loaded
		return true;
		}
	
	if(jQuery('.CollectionPanelShell').length==0) {
		HideCollectionBar();
		return false;
	}
	else	{	
		ShowCollectionBar();
		return true;
	}

}

function ChosenDropdownInit(elem, selector)
	{
	if(typeof chosen_config!=='undefined')
		{
		css_width = jQuery(elem).css("width").replace("px","");
		css_boxsixing = jQuery(elem).css("box-sizing");
		if(css_boxsixing=='border-box')
			{
			css_padding_left = jQuery(elem).css("padding-left").replace("px","");
			css_padding_right = jQuery(elem).css("padding-right").replace("px","");
			css_width_total = (parseInt(css_width)+parseInt(css_padding_left)+parseInt(css_padding_right))+"px";
			}
		else
			{
			css_width_total = css_width;
			}
		chosen_config[selector]['width']=css_width_total;
		jQuery(elem).chosen(chosen_config[selector]);
		}
	}

function removeSearchTagInputPills(search_input)
    {
    var tags = search_input.tagEditor('getTags')[0].tags;

    for(i = 0; i < tags.length; i++)
        {
        search_input.tagEditor('removeTag', tags[i]);
        }

    return true;
    }

function array_diff(array_1, array_2)
    {
    var a    = []
    var diff = [];

    for(var i = 0; i < array_1.length; i++)
        {
        a[array_1[i]] = true;
        }

    for(var i = 0; i < array_2.length; i++)
        {
        if(a[array_2[i]])
            {
            delete a[array_2[i]];
            }
        else
            {
            a[array_2[i]] = true;
            }
        }

    for(var k in a)
        {
        diff.push(k);
        }

    return diff;
    }
   
function StripResizeResults(targetImageHeight)
    {
    var screenWidth = jQuery('#CentralSpaceResources').width()-15;
    var images = jQuery('.ImageStrip');
    var accumulatedWidth = 0;
    var imageGap = 15;
    var processArray = [];

    for (var i = 0; i < images.length; i++)
        {
        // Take a copy to get the unscaled image size
        var imageCopy = new Image();imageCopy.src=images[i].src;
        var ratio = imageCopy.width/imageCopy.height;
        var presentationWidth=(targetImageHeight * ratio);
        accumulatedWidth+=presentationWidth+imageGap; // add to our calculation of the row width

        if (accumulatedWidth>screenWidth)
            {
            // Work out a height for the row (excluding the current image which will fall to the next row)
            var factor=screenWidth / (accumulatedWidth-presentationWidth - imageGap);
            //jQuery(images[i]).css("border-color","red");

            // Break at this image (in case we're out of step - this will realign rows)
            //jQuery(images[i]).before("<br />");

            // Rescale images on current row
            for (var n=0; n< processArray.length; n++)
                {
                jQuery(processArray[n]).css("height",Math.floor(factor * targetImageHeight) + "px");
                }

            // Empty process array
            processArray = [];
            accumulatedWidth=images[i].width+imageGap;
            }  

        processArray.push(images[i]);
        }
    }

function LoadActions(pagename,id,type,ref, extra_data)
    {
    CentralSpaceShowLoading();

    if(typeof id === "object")
        {
        var actionspace = id;
        }
    else
        {
        var actionspace=jQuery('#' + id);
        }

    url = baseurl_short+"pages/ajax/load_actions.php";
    var data = {
        actiontype: type,
        ref: ref,
        page: pagename
    };
    var data_obj = Object.assign({}, data, extra_data);
    var request = jQuery.ajax({
        type:'GET',
        url: url,
        data: data_obj,
        async: false
    });

    return jQuery.when(request)
        .then(function(response, status, xhr)
            {
            if(status == "error")
                {
                styledalert(errorpageload  + xhr.status + " " + xhr.statusText, response);
                }
            else
                {
                actionspace.html(response);
                CentralSpaceHideLoading();
                return true;
                }

            CentralSpaceHideLoading();
            return false;
            });
    }

/**
* Get extension of a file
* 
*/
function getFilePathExtension(path)
    {
    var filename   = path.split('\\').pop().split('/').pop();

    return filename.substring(filename.lastIndexOf('.') + 1, filename.length) || filename;
    }
    
/*
*
* Toggles the locking of an edit field 
*
*/
function toggleFieldLock(field) 
    {
    if(typeof lockedfields === 'undefined')
        {
        lockedfields = new Array();
        }
    if (lockedfields.indexOf(field.toString())!=-1)
        {
        jQuery('#lock_icon_' + field + '> i').removeClass('fa-lock');
        jQuery('#lock_icon_' + field + '> i').addClass('fa-unlock');
        jQuery('#lock_icon_' + field).parent().closest('div').removeClass('lockedQuestion');
        lockedfields = jQuery.grep(lockedfields, function(value) {return value != field.toString();});
        //console.log('Unlocking field ' + field);
		SetCookie('lockedfields',lockedfields.map(String));
        }
    else
        {
        jQuery('#lock_icon_' + field + '> i').removeClass('fa-unlock');
        jQuery('#lock_icon_' + field + '> i').addClass('fa-lock');
        jQuery('#lock_icon_' + field).parent().closest('div').addClass('lockedQuestion');
        // Remove checksum as this will break things
        jQuery('#field_' + field + '_checksum').val("");
        jQuery('#' + field + '_checksum').val("");
        lockedfields.push(field.toString());
        //console.log('Locking field ' + field);
		SetCookie('lockedfields',lockedfields);
        }
    if(lockedfields.length > 0)
        {
        jQuery(".save_auto_next").show();
        }
    else
        {
        jQuery(".save_auto_next").hide();
        }
    return true;
    }

/*
*
* Places overflowing header links into a dropdown 
*
*/
function headerLinksDropdown()
    {
    var containerWidth = jQuery("#HeaderLinksContainer").innerWidth() - 30;
    var links =  jQuery("#HeaderLinksContainer").find(".HeaderLink"); // get elements that are links in the header bar
    var linksWidth = 0;
    var caretCreated = false;

    jQuery("#OverFlowLinks").remove(); // remove the drop-down menu div
    if (jQuery(window).width() < 1200 || jQuery('#Header').hasClass('HeaderLarge'))
        {
        return;
        }

    if (jQuery('#OverflowListElement').is(':visible'))
        {
        caretCreated = true;
        }

    for (var i = 0; i < links.length; i++)
        {
		linksWidth += jQuery(links[i]).outerWidth();

        if (linksWidth > containerWidth)
            {
            if (!caretCreated)
                {
				jQuery(links[i- 1]).after('<li id="OverflowListElement"><a href="#" id="DropdownCaret" onclick="showHideLinks();"><span class="fa fa-caret-down"></span></a></li>');
				// append a div to the document.body element that will contain the drop-down menu items
				jQuery(document.body).append('<div id="OverFlowLinks"><ul id="HiddenLinks"></ul></div>'); 
				caretCreated = true;
                }
            // remove the li element from header links and append it to drop-down link list    
            jQuery(links[i]).remove();    
            jQuery(links[i]).appendTo('#HiddenLinks');
            }
        }
    }

/*
*
* Show or hide the header overflow drop down links
*
*/
function showHideLinks()
    {
    jQuery('div#OverFlowLinks').toggle();
	jQuery('div#OverFlowLinks').css('right', 290);
	jQuery('div#OverFlowLinks').css('z-index', 1000);
    
    if (jQuery('#Header').hasClass('HeaderSmall'))
        {
        jQuery('div#OverFlowLinks').css('top', 45);
        }
    else
        {
        jQuery('div#OverFlowLinks').css('top', 64);
        }
    }

/*
* 
* Toggles the edit_multi_checkbox question on edit page when in batch edit mode (ie. multiple == true)
* 
*/
function batch_edit_toggle_edit_multi_checkbox_question(field_ref)
    {
    var question = document.getElementById('question_' + field_ref);
    var modeselect = document.getElementById('modeselect_' + field_ref);
    var findreplace = document.getElementById('findreplace_' + field_ref);

    if(jQuery("#editthis_" + field_ref).prop("checked"))
        {
        question.style.display = 'block';
        modeselect.style.display = 'block';
        }
    else
        {
        question.style.display = 'none';
        modeselect.style.display = 'none';
        findreplace.style.display = 'none';

        document.getElementById('modeselectinput_' + field_ref).selectedIndex = 0;
        }

    return;
    }

/*
* 
* Redirects to a target URL after a specified delay time expressed in milliseconds
* 
*/
function redirectAfterDelay(targetUrl,delayTime) 
	{
	setTimeout(function()
		{ 
		window.location.href = targetUrl;
		}, delayTime);
	}

function UICenterScrollBottom()
	{
	// Smoothly croll to the bottom of the central container. Useful to show content after expanding a section at the bottom of the page (e.g. the uploader)
	window.setTimeout('jQuery(\'#UICenter\').animate({scrollTop: document.getElementById(\'UICenter\').scrollHeight },"slow");',300);
	}



/**
* Detect the users' local time zone using the Internationalisation API
* 
* @returns {string}
*/
function detect_local_timezone()
    {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
	}


	

/**
 * 
 * @summary Escape characters in a string that may cause the string to be interpreted as HTML in the browser. If regex pattern parameter use this to escape characters, otherwise use default regex pattern provided.
 * 
 * @param {string} string - string potentially containing characters to escape - required
 * @param {string} pattern - regex pattern containing HTML characters to escape - not required
 * 
 * @var  {array} entityMap - array of HTML entities with their escaped versions
 * @var {string} default_pattern  - default regex pattern to use if no pattern passed
 * 
 * @returns {string} escaped_string - string with characters escaped 
 * 
 * @see upload_plupload.php plupload.addFileFilter()
 * 
 */	

function escape_HTML(string, pattern)
	{

	var entityMap = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#39;',
		'/': '&#x2F;',
		'`': '&#x60;',
		'=': '&#x3D;'
		};

	var default_pattern = "[&<>\"'`=]";

	// if regex pattern is not passed as argument use default pattern
	pattern =  (pattern === undefined) ? default_pattern : pattern;

	pattern = new RegExp(pattern, "g");	

	var escaped_string = string.replace(pattern, function (s) 
		{
		return entityMap[s];
		});

	return escaped_string;
	}

/* Manage .TabBar clicks so the show/hide the correct .TabbedPanel within .TabBar */

function SelectMetaTab(resourceid,tabnum,modal)
{
	if (modal)
		{
		jQuery('#Modaltabswitch'+tabnum+'-'+resourceid).siblings().removeClass('TabSelected');
		jQuery('#Modaltabswitch'+tabnum+'-'+resourceid).addClass('TabSelected');
		jQuery('div.MetaTabIsModal-'+resourceid).hide();
		jQuery('#Modaltab'+tabnum+'-'+resourceid).show();
		}
	else
		{
		jQuery('#tabswitch'+tabnum+'-'+resourceid).siblings().removeClass('TabSelected');
		jQuery('#tabswitch'+tabnum+'-'+resourceid).addClass('TabSelected');
		jQuery('div.MetaTabIsNotModal-'+resourceid).hide();
		jQuery('#tab'+tabnum+'-'+resourceid).show();
		}
}

// unset cookie
function unsetCookie(cookieName, cpath)
	{
	document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;path=' + cpath;	
	}


/**
* Check if system upgrade is in progress. Reloads the page if it is to show user current status.
* 
* @return void
*/
function check_upgrade_in_progress()
    {
    jQuery.ajax({
        type: 'GET',
        url: baseurl + "/pages/ajax/message.php",
        data: {
            ajax: true,
            check_upgrade_in_progress: true
        },
        dataType: "json"
        })
        .done(function(response)
            {
            if(response.status != "success")
                {
                return;
                }

            if(response.data.upgrade_in_progress)
                {
                window.location.reload(true);
                return;
                }
            });
    
    return;
    }

/**
* Add hidden input dynamically to help further requests maintain modals.
* 
* @param   {string}     form                The form ID to which to append the hidden input
* @param   {boolean}    decision_factor     TRUE to add it, FALSE otherwise
* 
* @return boolean
*/
function add_hidden_modal_input(form, decision_factor)
    {
    if(!decision_factor)
        {
        return false;
        }

    jQuery('<input type="hidden" name="modal" value="true">').appendTo("#" + form);

    return true;
    }

function api(name, params, callback)
    {
    query = {};
    query["function"] = name;
    for (var key in params) {
        query[key] = params[key];
        }
	console.debug("API Query",query);
    postobj = {};
    postobj['query'] = jQuery.param(query);
    postobj['authmode'] = "native";

    jQuery.ajax({
        method: 'POST',
        url: baseurl_short + 'api/?',
        data: postobj,
        })
        .done(function(data, textStatus, jqXHR )
            {
            response = jqXHR.responseText;
            console.debug("API Response",response);
            if(isJson(response))
                {
                response = JSON.parse(response)
                }
            if(typeof callback === "function")
                {
                callback(response);
                }
            })
        .fail(function(jqXHR, textStatus, errorThrown)
            {
            console.debug("API Error",textStatus);
            });
    }
