// Functions to support collections.

// Prevent caching
jQuery.ajaxSetup({ cache: false });
 
function PopCollection(thumbs) {
    if(thumbs == "hide" && collections_popout) {
        ToggleThumbs();
    }
}

function ChangeCollection(collection,k,last_collection,searchParams) {
    console.debug("ChangeCollection(collection = %o, k = %o, last_collection = %o, searchParams = %o)", collection, k, last_collection, searchParams);
    if(typeof last_collection == 'undefined'){last_collection='';}
    if(typeof searchParams == 'undefined') {searchParams='';}
    thumbs = getCookie("thumbs");
    PopCollection(thumbs);
    // Set the collection and update the count display
    CollectionDivLoad(baseurl_short + 'pages/collections.php?collection=' + collection + '&thumbs=' + thumbs + '&last_collection=' + last_collection + '&k=' + k + '&' +searchParams);
}

function UpdateCollectionDisplay(k) {
    thumbs = getCookie("thumbs");
    PopCollection(thumbs);
    // Update the collection count display
    jQuery('#CollectionDiv').load(baseurl_short + 'pages/collections.php?thumbs=' + thumbs + '&k=' + k);
}

function AddResourceToCollection(event,resource,size, collection_id) {

    // Optional params
    if(typeof collection_id === 'undefined') {
        collection_id = '';
    }

    if(event.shiftKey == true) {
        if (typeof prevadded != 'undefined') {
            lastchecked = jQuery('#check' + prevadded);
            if (lastchecked.length != 0) {
                var resourcelist = [];
                addresourceflag = false;
                jQuery('.checkselect').each(function () {
                    if(jQuery(this).attr("id") == lastchecked.attr("id")) {
                        if(addresourceflag == false) {   
                            // Set flag to mark start of resources to add
                            addresourceflag = true;
                        }
                        else { 
                            // Clear flag to mark end of resources to add
                            addresourceflag = false;  
                        }
                    }
                    else if(jQuery(this).attr("id") == 'check'+resource) {
                        // Add resource to list before clearing flag
                        resourceid = jQuery(this).attr("id").substring(5)
                        resourcelist.push(resourceid);
                        jQuery(this).prop('checked',true);
                        if(addresourceflag == false) {
                            addresourceflag = true;
                        }
                        else {
                            addresourceflag = false;
                        }
                    }

                    if(addresourceflag) {
                        // Add resource to list 
                        resourceid = jQuery(this).attr("id").substring(5)
                        resourcelist.push(resourceid);
                        jQuery(this).prop('checked',true);
                        jQuery("#ResourceShell" + resourceid).addClass("Selected");
                    }
                });
                resource = resourcelist.join(",");
            }
        }
    }
    prevadded = resource;

    thumbs = getCookie("thumbs");
    PopCollection(thumbs);

    CollectionDivLoad(baseurl_short + 'pages/collections.php?add=' + resource + '&toCollection=' + collection_id + '&size=' + size + '&thumbs=' + thumbs);
    delete prevremoved;
    if(collection_bar_hide_empty){
	CheckHideCollectionBar();
	}
}

function RemoveResourceFromCollection(event,resource,pagename, collection_id) {
    // Optional params
    if(typeof collection_id === 'undefined') {
        collection_id = '';
    }

    if(event.shiftKey == true) {
        if (typeof prevremoved != 'undefined') {
            lastunchecked=jQuery('#check' + prevremoved)
            if (lastunchecked.length != 0) {
                var resourcelist = [];
                removeresourceflag = false;
                jQuery('.checkselect').each(function () {
                    if(jQuery(this).attr("id") == lastunchecked.attr("id")) {
                        if(removeresourceflag == false) { 
                            // Set flag to mark start of resources to remove
                            removeresourceflag = true;
                        }
                        else { 
                            // Clear flag to mark end of resources to remove
                            removeresourceflag = false;
                        }
                    }
                    else if(jQuery(this).attr("id") == 'check'+resource) {
                        // Add resource to list before clearing flag
                        resourceid = jQuery(this).attr("id").substring(5)
                        resourcelist.push(resourceid);
                        jQuery(this).removeAttr('checked');
                        if(removeresourceflag == false) {
                            removeresourceflag = true;
                        }
                        else {
                            removeresourceflag = false;
                        }
                    }

                    if(removeresourceflag) {
                        // Add resource to list to remove
                        resourceid = jQuery(this).attr("id").substring(5)
                        resourcelist.push(resourceid);
                        jQuery(this).prop('checked',false);
                        jQuery("#ResourceShell" + resourceid).removeClass("Selected");
                    }
                });
                resource = resourcelist.join(",");
            }
        }
    }
    prevremoved = resource;

    thumbs = getCookie("thumbs");
    PopCollection(thumbs);
    CollectionDivLoad( baseurl_short + 'pages/collections.php?remove=' + resource + '&fromCollection=' + collection_id + '&thumbs=' + thumbs);
    // jQuery('#ResourceShell' + resource).fadeOut(); //manual action (by developers) since now we can have a case where we remove from collection bar but keep it in central space because it's for a different collection
    delete prevadded;
    if(collection_bar_hide_empty){
	CheckHideCollectionBar();
	}
}


function UpdateHiddenCollections(checkbox, collection, post_data) {
    var action = (checkbox.checked) ? 'showcollection' : 'hidecollection';
    jQuery.ajax({
        type: 'POST',
        url: baseurl_short + 'pages/ajax/showhide_collection.php?action=' + action + '&collection=' + collection,
        data: post_data,
        success: function(data) {
            if (data.trim() == "HIDDEN") {
                jQuery(checkbox).prop('checked',false);
            }
            else if (data.trim() == "UNHIDDEN") {
                jQuery(checkbox).prop('checked',true);
            }
        },
        error: function (err) {
            console.log("AJAX error : " + JSON.stringify(err, null, 2));
            if(action == 'showcollection') {
                jQuery(checkbox).removeAttr('checked');
            }
            else {
                jQuery(checkbox).prop('checked','checked');
            }
        }
    }); 
}


function ToggleCollectionResourceSelection(e, collection)
    {
    var input = jQuery(e.target);
    var resource = input.data("resource");

    var csrf_token_identifier = input.data("csrf-token-identifier");
    var csrf_token = input.data("csrf-token");
    var csrf_post_data = {};
    csrf_post_data[csrf_token_identifier] = csrf_token;

    if(input.prop("checked"))
        {
        add_resource_to_collection(resource, collection, csrf_post_data)
            .then(function(add_ok)
                {
                if(add_ok)
                    {
                    UpdateSelColSearchFilterBar();
                    jQuery("#ResourceShell" + resource).addClass("Selected");
                    }
                });
        }
    else
        {
        remove_resource_from_collection(resource, collection, csrf_post_data)
            .then(function(remove_ok)
                {
                if(remove_ok)
                    {
                    UpdateSelColSearchFilterBar();
                    jQuery("#ResourceShell" + resource).removeClass("Selected");
                    }
                });
        }

    return true;
    }


function ClearSelectionCollection(t)
    {
    var button = jQuery(t);
    var csrf_token_identifier = button.data("csrf-token-identifier");
    var csrf_token = button.data("csrf-token");

    var default_post_data = {};
    default_post_data[csrf_token_identifier] = csrf_token;
    var post_data = Object.assign({}, default_post_data);
    post_data.ajax = true;
    post_data.action = "clear_selection_collection_resources";

    console.debug("ClearSelectionCollection: post_data = %o", post_data);

    CentralSpaceShowLoading();

    jQuery.ajax({
        type: 'POST',
        url: baseurl + "/pages/ajax/collections.php",
        data: post_data,
        dataType: "json"
        })
        .done(function(response, textStatus, jqXHR)
            {
            if(typeof response.status !== "undefined" && response.status == "success")
                {
                CentralSpaceLoad(window.location.href);
                }
            })
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return;
                }

            var response = data.responseJSON;
            styledalert(jqXHR, response.data.message);
            })
        .always(function()
            {
            CentralSpaceHideLoading();
            });

    return true;
    }


function UpdateSelColSearchFilterBar()
    {
    CentralSpaceShowLoading();

    jQuery.ajax({
        type: 'GET',
        url: baseurl + "/pages/ajax/collections.php",
        data: {
            ajax: true,
            action: "get_selected_resources_counter"
        },
        dataType: "json"
        })
        .done(function(response, textStatus, jqXHR)
            {
            if(response.status == "success")
                {
                var selected_resources = response.data.selected;
                var clear_btns = (selected_resources == 0);

                jQuery(".TopInpageNavLeft").trigger("UpdateForSelectionCollection", [clear_btns]);                    
                }
            })
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return;
                }

            var response = data.responseJSON;
            styledalert(jqXHR, response.data.message);
            })
        .always(function()
            {
            CentralSpaceHideLoading();
            });

    jQuery(".TopInpageNavLeft").one("UpdateForSelectionCollection", function(e, clear)
        {
        UpdateSelectedResourcesCounter(clear);
        UpdateSelectedUnifiedActions(clear);
        UpdateSelectedBtns(clear);
        });

    return;
    }

function UpdateSelectedResourcesCounter(clear)
    {
    console.debug("UpdateSelectedResourcesCounter(clear = %o)", clear);

    if(clear)
        {
        jQuery(".SelectionCollectionLink").parent().remove();

        var orig_srf = jQuery("#OriginalSearchResultFound");

        // We already had the "selected" counter - there is no search results found counter in DOM. Reload in this case.
        if(!orig_srf.length)
            {
            CentralSpaceLoad(window.location.href);
            }

        orig_srf.addClass("InpageNavLeftBlock");
        orig_srf.removeClass("DisplayNone");
        orig_srf.attr("id", "SearchResultFound");

        return;
        }

    CentralSpaceShowLoading();
    jQuery.ajax({
        type: 'GET',
        url: baseurl + "/pages/ajax/collections.php",
        data: {
            action: "render_selected_resources_counter"
        },
        dataType: "html"
        })
        .done(function(response, textStatus, jqXHR)
            {
            var orig_srf = jQuery("#OriginalSearchResultFound");
            var remove_old = false;

            if(orig_srf.length)
                {
                remove_old = true;
                }

            srf = jQuery("#SearchResultFound");
            var srf_copy = srf.clone();

            srf_copy.html(response);
            srf_copy.insertAfter(srf);

            if(remove_old)
                {
                srf.remove();
                return;
                }

            // Hide the field
            srf.attr("id", "OriginalSearchResultFound");
            srf.addClass("DisplayNone");
            srf.removeClass("InpageNavLeftBlock");
            })
        .always(function()
            {
            CentralSpaceHideLoading();
            });

    return;
    }

function UpdateSelectedBtns(clear)
    {
    console.debug("UpdateSelectedBtns(clear = %o)", clear);

    if(clear)
        {
        jQuery("#EditSelectedResourcesBtn").parent().remove();
        jQuery("#ClearSelectedResourcesBtn").parent().remove();
        return;
        }

    var EditBtn_ajax = jQuery.ajax({
        type: 'GET',
        url: baseurl + "/pages/ajax/collections.php",
        data: {
            action: "render_edit_selected_btn",
            restypes: searchparams.restypes,
            archive: searchparams.archive,
        },
        dataType: "html"
        });

    var ClearBtn_ajax = jQuery.ajax({
        type: 'GET',
        url: baseurl + "/pages/ajax/collections.php",
        data: {
            action: "render_clear_selected_btn"
        },
        dataType: "html"
        });

    jQuery.when(EditBtn_ajax, ClearBtn_ajax)
        .then(function(edit_btn_response, clear_btn_response)
            {
            var TopInpageNavLeft = jQuery(".TopInpageNavLeft");
            var btn = jQuery("#EditSelectedResourcesBtn");
            if(!btn.length)
                {
                TopInpageNavLeft.append(edit_btn_response[0]);
                }

            var btn = jQuery("#ClearSelectedResourcesBtn");
            if(!btn.length)
                {
                TopInpageNavLeft.append(clear_btn_response[0]);
                }

            return;
            });

    return;
    }

function UpdateSelectedUnifiedActions(clear)
    {
    console.debug("UpdateSelectedUnifiedActions(clear = %o)", clear);

    var search_action_selection = jQuery("select[id^=search_action_selection");
    var load_actions_action_selection = jQuery("select[id^=load_actions_action_selection").parent();
    var actionspace = (!search_action_selection.length ? load_actions_action_selection : search_action_selection);

    if(clear)
        {
        LoadActions("search", actionspace.parent(), "search", null, searchparams);
        return;
        }

    LoadActions("search", actionspace.parent(), "selection_collection", null, searchparams);

    return;
    }

function RemoveSelectedFromCollection(csrf_id, csrf_token)
    {
    console.debug("RemoveSelectedFromCollection(csrf_id = %o, csrf_token = %o)", csrf_id, csrf_token);
    var k = "";
    if(typeof searchparams.k !== "undefined")
        {
        k = searchparams.k;
        }

    var post_data = {
        ajax: true,
        action: "remove_selected_from_collection"
    };
    post_data[csrf_id] = csrf_token;

    CentralSpaceShowLoading();
    jQuery.ajax({
        type: 'POST',
        url: baseurl + "/pages/ajax/collections.php",
        data: post_data,
        dataType: "json"
        })
        .done(function(response)
            {
            if(response.status == "success")
                {
                UpdateCollectionDisplay(k);
                }
            })
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return;
                }

            var response = data.responseJSON;
            styledalert(jqXHR, response.data.message);
            })
        .always(function()
            {
            CentralSpaceHideLoading();
            });

    return;
    }


function add_resource_to_collection(resource, collection, csrf)
    {
    console.log("add_resource_to_collection: adding resource #%i to collection #%i", resource, collection);
    var post_data = Object.assign({}, csrf);
    post_data.ajax = true;
    post_data.action = "add_resource";
    post_data.resource = resource;
    post_data.collection = collection;

    console.debug("add_resource_to_collection: post_data = %o", post_data);

    var request = jQuery.ajax({
        type: 'POST',
        url: baseurl + "/pages/ajax/collections.php",
        data: post_data,
        dataType: "json"
    });

    return jQuery.when(request)
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return false;
                }

            styledalert(jqXHR, data.responseJSON.data.message);
            return false;
            })
        .then(function(response)
            {
            if(typeof response.status !== "undefined" && response.status == "fail")
                {
                styledalert("", response.data.message);
                return false;
                }

            return true;
            });
    }


function remove_resource_from_collection(resource, collection, csrf)
    {
    console.log("remove_resource_from_collection: removing resource #%i from collection #%i", resource, collection);
    var post_data = Object.assign({}, csrf);
    post_data.ajax = true;
    post_data.action = "remove_resource";
    post_data.resource = resource;
    post_data.collection = collection;

    console.debug("remove_resource_from_collection: post_data = %o", post_data);

    var request = jQuery.ajax({
        type: 'POST',
        url: baseurl + "/pages/ajax/collections.php",
        data: post_data,
        dataType: "json"
    });

    return jQuery.when(request)
        .fail(function(data, textStatus, jqXHR)
            {
            if(typeof data.responseJSON === 'undefined')
                {
                return false;
                }

            styledalert(jqXHR, data.responseJSON.data.message);
            return false;
            })
        .then(function(response)
            {
            if(typeof response.status !== "undefined" && response.status == "fail")
                {
                styledalert("", response.data.message);
                return false;
                }

            return true;
            });
    }