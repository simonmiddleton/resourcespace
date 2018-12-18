<?php
/**
* Add in CSS overrides for UI elements
*
* @package ResourceSpace
*/

include_once "../../include/db.php";
include_once "../../include/general.php";
include_once "../../include/authenticate.php";
//include_once "../../include/render_functions.php";

header("Content-type: text/javascript");
?>

function ToggleBrowseBar() 
	{
	if (typeof rsbrowse === "undefined" || rsbrowse == 'hide')
		{
		jQuery('#BrowseBarTab').removeClass('BrowseBarHidden');
		jQuery('#CentralSpaceContainer').addClass('BrowseMode');
		jQuery('#Footer').addClass('BrowseMode');
		jQuery('#BrowseBar').removeClass('BrowseBarHidden');
		jQuery('#BrowseBar').addClass('BrowseBarVisible');
		rsbrowse = 'show';
		SetCookie('rsbrowse', 'show');
		}
	else
		{
		jQuery('#BrowseBar').removeClass('BrowseBarVisible');
		jQuery('#BrowseBar').addClass('BrowseBarHidden');
		jQuery('#CentralSpaceContainer').removeClass('BrowseMode');
		jQuery('#Footer').removeClass('BrowseMode');
		jQuery('#BrowseBarTab').addClass('BrowseBarHidden');
		rsbrowse = 'hide';
		SetCookie('rsbrowse', 'hide');
		}
	}

function renderBrowseItem(node, parent)
    {
        //console.log("name " + node.name);
        //console.log("class " + node.class);
        //console.log("type " + node.type);
        //console.log("id " + node.id);
        //console.log("level " + node.level);
        //console.log("expandable " + node.expandable);
        //console.log("link " + node.link);
    parentid = parent.attr('data-browse-id');
    //curel = jQuery("[data-browse-id='" + browse_id + "']");
    newlevel = parent.attr("data-browse-level");
    newlevel++;

        //console.log("parent: " + parentid);
   
    indent = "<div class='BrowseBarStructure backline'>&nbsp;</div>";
    var refreshel = "<a href='#' class='browse_refresh' onclick='LoadBrowseElements(\"%BROWSE_ID%\",true);return false;' ><i class='fa fa-refresh reloadicon'></i></a>";
    var refreshel = refreshel.replace("%BROWSE_ID%",node.id);
    //console.log("refreshel " + refreshel);
    if(node.expandable != "false")
        {
        //console.log('expandable');
        expand = "<div class='BrowseBarStructure'><a href='#' class='browse_expand browse_closed' onclick='LoadBrowseElements(\"%BROWSE_ID%\");return false;'></a></div>";
        //expand = expand.replace("%BROWSE_TYPE%",node.type);
        expand = expand.replace("%BROWSE_ID%",node.id);
        }
    else
        {
        //console.log("defaultindent");
        expand = indent;    
        }
    
    rowindent = "";
    for (i = 0; i < newlevel; i++)
        { 
        rowindent += indent;
        }

    //console.log("rowindent " + rowindent);
    //console.log("expand " + expand);

    brwstmplt = jQuery('#BrowseBarTemplate').html();
    //brwstmplt = brwstmplt.replace("%BROWSE_TYPE%",node.type);
    brwstmplt = brwstmplt.replace("%BROWSE_DROP%", node.drop ? "BrowseBarDroppable" : "");
    brwstmplt = brwstmplt.replace("%BROWSE_NAME%",node.name);
    brwstmplt = brwstmplt.replace("%BROWSE_LEVEL%",newlevel);
    brwstmplt = brwstmplt.replace("%BROWSE_INDENT%",rowindent);        
    brwstmplt = brwstmplt.replace("%BROWSE_CLASS%",node.class);       
    brwstmplt = brwstmplt.replace("%BROWSE_EXPAND%",expand);
    if(node.link != "")
        {
        link = "<a class='BrowseBarLink' href='%BROWSE_LINK%' onclick='return CentralSpaceLoad(this,false);'>&nbsp;%BROWSE_NAME%</a>";
        link = link.replace("%BROWSE_LINK%",node.link);  
        link = link.replace("%BROWSE_NAME%",node.name);  
        if(node.modal)
            {
            link = link.replace("CentralSpaceLoad(this,false)","ModalLoad(this,false,true)");
            }
            
        brwstmplt = brwstmplt.replace("%BROWSE_TEXT%",link);  
        }
    else
        {
        text = "<div class='BrowseBarLink' >&nbsp;%BROWSE_NAME%</div>";
        text = text.replace("%BROWSE_NAME%",node.name);  
        brwstmplt = brwstmplt.replace("%BROWSE_TEXT%",text);
        }
    brwstmplt = brwstmplt.replace("%BROWSE_PARENT%",parentid); 
    brwstmplt = brwstmplt.replace("%BROWSE_ID%",node.id);
    brwstmplt = brwstmplt.replace("%BROWSE_REFRESH%",refreshel);
   
    parent.after(brwstmplt);
    //newel = jQuery(brwstmplt).insertAfter(parent)
    //console.log(brwstmplt);
    }


function LoadBrowseElements(browse_id, reload)
    {
	if (typeof reload=='undefined') {reload=false;}
    console.log("LoadBrowseElements(" + browse_id +")");
    curel = jQuery("[data-browse-id='" + browse_id + "']");
    
    if(!curel.length)
        {
        // Node not present, load parent first
        //console.log("item not present. Searching for parent");
        item_elements = browse_id.split('-');
        item_elements.pop();
        console.log("elements " + item_elements);
        if (item_elements.length  < 1)
            {            
            // This is the root node and is not present, give up
            return false;
            }
            
        parentitem = item_elements.join('-');
        console.log("Loading parent item: " + parentitem);
        LoadBrowseElements(parentitem, true);
        }
            
    //curlevel =curel.attr("data-browse-level");
    loaded =curel.attr("data-browse-loaded");
    openclose = curel.find("a.browse_expand");
    refreshicon = curel.find("a.browse_refresh i");

    if(typeof browseopen === 'undefined')
        {
        //console.log('Creating browseopen array for id: ' + browse_id);
        browseopen = new Array();
        }

    //console.log("curel : " + "[data-browse-id='" + browse_id + "']");
    //sublevel = curlevel;
    //sublevel++;
    
    //console.log("browse_id: " + browse_id);
    //console.log("loaded: " + loaded);

    curstatus = curel.attr("data-browse-status");
    
    if(curstatus=="open" && !reload)
        {
        // Hide the children and close
        jQuery("[data-browse-parent='" + browse_id + "']").slideUp();
        jQuery("[data-browse-parent|='" + browse_id + "']").slideUp();
        jQuery("[data-browse-parent|='" + browse_id + "']").find("a.browse_expand").removeClass("browse_expanded");
        jQuery("[data-browse-parent|='" + browse_id + "']").find("a.browse_expand").addClass("browse_closed");
        openclose.removeClass("browse_expanded");
        openclose.addClass("browse_closed");
        curel.attr("data-browse-status","closed");
        curel.removeClass("BrowseOpen");
        if (browseopen.indexOf(browse_id)!=-1)
            {
            browseopen = jQuery.grep(browseopen, function(value) {return value != browse_id;});
            //console.log('Closing browse node ' + browse_id);
            SetCookie('browseopen',browseopen);
            }
        return true;
        }
        
    if(loaded==1 && !reload)
        {
        // Show the child items
        jQuery("[data-browse-parent='" + browse_id + "']").slideDown();
        openclose.removeClass("browse_closed");
        openclose.addClass("browse_expanded");
        curel.attr("data-browse-status","open");
        curel.addClass("BrowseOpen");
        return true;
        }
    

    //console.log('"Adding spin to ' + refreshicon);
    refreshicon.addClass('fa-spin');


    // Remove any child items before load/reload
    jQuery("[data-browse-parent|='" + browse_id + "']").remove();
    
    //CentralSpaceShowLoading();
    //console.log("element: " + curel);
                    
    url = baseurl_short+"pages/ajax/load_browsebar.php";
    
    
    var post_data = {
                    id: browse_id,
                    //level: curlevel
                    };
    
    jQuery.ajax({
            type:"GET",
            url: url,
            data: post_data,
            dataType: "json",
            async:false            
            }).done(function(response, status, xhr)
                {
                //console.log(status);
                if (status=="401")
                    {				
                    alert(errorpageload  + xhr.status + " " + xhr.statusText + "<br>" + response);		
                    }
                else
                    {
                    // Load completed	
                    // Parse response items
                    //console.log(response);
                    // Reverse so each can be appended in turn and still appear in correct order 
                    response.items.reverse();
                    response.items.forEach(function (item) {
                        renderBrowseItem(item, curel);
                    });
                    
                    // Show all immediate children
                    jQuery("[data-browse-parent='" + browse_id + "']").slideDown();
                    //console.log("loaded");
                    }
                
                curel.attr("data-browse-status","open");
                curel.attr("data-browse-loaded","1");

                if (browseopen.indexOf(browse_id)==-1)
                    {
                    //console.log('Adding to browseopen: ' + browse_id);
                    browseopen.push(browse_id);
                    SetCookie('browseopen',browseopen);
                    }

                CentralSpaceHideLoading();

                openclose.removeClass("browse_closed");
                openclose.addClass("browse_expanded");
                curel.addClass("BrowseOpen");
                //console.log("removing spin from " + refreshicon);
                refreshicon.removeClass("fa-spin");
                });

    BrowseBarDropInit();
    return true;          
    }
    
function ReloadBrowseBar()
    {
    //console.log(" reloading - " + browseopen);
    browseopen = jQuery.cookie("browseopen") ? jQuery.cookie("browseopen").split(/,/) : new Array();
    browseopen.sort();
    browseopen.forEach(function (item)
        {
        console.log("Reloading browse node = " + item);
        LoadBrowseElements(item, true);
        });
    BrowseBarDropInit();
    }

function BrowseBarDropInit()
    {
    jQuery(".BrowseBarDroppable").droppable({
        accept: ".ResourcePanel, .CollectionPanelShell",

        drop: function(event, ui)
            {
            dropped = jQuery(ui.draggable);
            var resource_id = dropped.attr("id");
            resource_id = resource_id.replace("ResourceShell", "");
            
            // Get the drop target
            browsetarget = jQuery(this).attr("data-browse-id");
            item_elements = browsetarget.split('-'); 
            droptype = item_elements[0];
            console.log("dropped classes " + dropped.attr("class"));
            switch(droptype)
                {
                case 'R':
                    tgt_rt = item_elements[1].replace('RT:','');
                    console.log("looking for class " + ' ResourceType' + tgt_rt);
                    if(dropped.hasClass('ResourceType' + tgt_rt))
                        {
                        nodeid = item_elements[item_elements.length - 1].replace('N:','');
                        //alert('OK - node ' + nodeid);
                        
                        var post_data = {
                            action: 'add_node',
                            node: nodeid,
                            resource: resource_id,
                            <?php echo generateAjaxToken('browse_action'); ?>
                            };
                        
                        BrowseAction(post_data);
                        }
                    else
                        {
                        //styledalert('<?php echo $lang['error-invalid_resource_type']; ?>');
                        browse_err = document.createElement( "div" ),
                        jQuery(browse_err).html('<?php echo $lang["save-error"] ?>');
                        jQuery(browsetarget).append(browse_err);
                        jQuery(browse_err).fadeOut('slow');
                        }
                    break;
                case 'FC':
                case 'C':
                    cid = item_elements[item_elements.length - 1].replace('C:','');
                    AddResourceToCollection(event,resource_id,'', cid);
                    break;
                default:
                    
                }
            },

        tolerance: "pointer"
        });
    }

function BrowseAction(post_data)
    { 
    //var post_data = data;
    console.log(post_data);
    CentralSpaceShowLoading();
    url = baseurl_short+"pages/ajax/browse_action.php";
    jQuery.ajax({
            type:'POST',
            url: url,
            data: post_data,
            async:false            
			}).done(function(response, status, xhr)
                {
                if (status=="error")
                    {				
                    actionspace.html(errorpageload  + xhr.status + " " + xhr.statusText + "<br>" + response);		
                    }
                else
                    {
                    // Load completed	
                    console.log('OK');
                  }
                CentralSpaceHideLoading();
                return true;
                });
	return false; 
    //console.log("Dropped resource #" + resource_id  + " on " +  browsetarget);
    }