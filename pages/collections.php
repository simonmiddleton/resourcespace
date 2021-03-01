<?php
include_once dirname(__FILE__)."/../include/db.php";

# External access support (authenticate only if no key provided, or if invalid access key provided)
$k=getvalescaped("k","");if (($k=="") || (!check_access_key_collection(getvalescaped("collection","",true),$k))) {include_once dirname(__FILE__)."/../include/authenticate.php";}
if (checkperm("b")){exit($lang["error-permissiondenied"]);}
include_once dirname(__FILE__)."/../include/research_functions.php";


$sort            = getvalescaped('sort', 'DESC');
$search          = getvalescaped('search', '');
$last_collection = getval('last_collection', '');
$restypes        = getvalescaped('restypes', '');
$archive         = getvalescaped('archive', '');
$daylimit        = getvalescaped('daylimit', '');
$offset          = getvalescaped('offset', '');
$resources_count = getvalescaped('resources_count', '');
$collection      = getvalescaped('collection', '');
$entername       = getvalescaped('entername', '');
$res_access      = getvalescaped('access','');

# if search is not a special search (ie. !recent), use starsearchvalue.
if ($search !="" && strpos($search,"!")!==false)
	{
	$starsearch = "";
	}
else
	{
	$starsearch = getvalescaped("starsearch","");	
    }

/* 
IMPORTANT NOTE: Collections should always show their resources in the order set by a user (via sortorder column 
in collection_resource table). This means that all pages order by 'relevance' and on search page only if we search
for this collection we can rely on the passed order by value.
*/
$order_by = $default_collection_sort;
if('!collection' === substr($search, 0, 11) && "!collection{$collection}" == $search)
    {
    $order_by = getvalescaped('order_by', $default_collection_sort);
    }

$change_col_url="search=" . urlencode($search). "&order_by=" . urlencode($order_by) . "&sort=" . urlencode($sort) . "&restypes=" . urlencode($restypes) . "&archive=" .urlencode($archive) . "&daylimit=" . urlencode($daylimit) . "&offset=" . urlencode($offset) . "&resources_count=" . urlencode($resources_count);

// Set a flag for logged in users if $external_share_view_as_internal is set and logged on user is accessing an external share
$internal_share_access = internal_share_access();

// copied from collection_manage to support compact style collection adds (without redirecting to collection_manage)
$addcollection=getvalescaped("addcollection","");
if ($addcollection!="")
	{
	# Add someone else's collection to your My Collections
	add_collection($userref,$addcollection);
	set_user_collection($userref,$addcollection);
	refresh_collection_frame();
	
   	# Log this
	daily_stat("Add public collection",$userref);
	}

#Remove all from collection
$emptycollection = getvalescaped("emptycollection","",true);
if($emptycollection!='' && getvalescaped("submitted","")=='removeall' && getval("removeall","")!="" && collection_writeable($emptycollection))
    {
    remove_all_resources_from_collection($emptycollection);
    }

if(!isset($thumbs))
    {
    $thumbs=getval("thumbs","unset");
    if($thumbs == "unset")
        {
        $thumbs = $thumbs_default;
        rs_setcookie("thumbs", $thumbs, 1000,"","",false,false);
        }
    }

# Basket mode? - this is for the e-commerce user request modes.
if (isset($userrequestmode) && ($userrequestmode==2 || $userrequestmode==3))
	{
	# Enable basket
	$basket=true;	
	}
else
	{
	$basket=false;
	}

# ------------ Change the collection, if a collection ID has been provided ----------------
if ($collection!="" && $collection!="undefined")
	{
	hook("prechangecollection");
	#change current collection
	
	if (($k=="" || $internal_share_access) && $collection=="new")
		{
		# Create new collection
		if ($entername!=""){ $name=$entername;} 
		else { $name="Default Collection";}
		$new=create_collection ($userref,$name);
		set_user_collection($userref,$new);
		
		# Log this
		daily_stat("New collection",$userref);
		}
	elseif((!isset($usercollection) || $collection!=$usercollection) && $collection!='false')
		{
                $validcollection=sql_value("select ref value from collection where ref='$collection'",0);
                # Switch the existing collection
		if ($k=="" || $internal_share_access) {set_user_collection($userref,$collection);}
		$usercollection=$collection;
		}

	hook("postchangecollection");
	}

// Load collection info. 
// get_user_collections moved before output as function may set cookies
$cinfo=get_collection($usercollection);
if('' == $k || $internal_share_access)
    {
    $list = get_user_collections($userref);
    }

# if the old collection or new collection is being displayed as search results, we'll need to update the search actions so "save results to this collection" is properly displayed
if(substr($search, 0, 11) == '!collection' && ($k == '' || $internal_share_access))
	{ 
	# Extract the collection number - this bit of code might be useful as a function
    $search_collection = explode(' ', $search);
    $search_collection = str_replace('!collection', '', $search_collection[0]);
    $search_collection = explode(',', $search_collection); // just get the number
    $search_collection = escape_check($search_collection[0]);
    if($search_collection==$last_collection || ($last_collection!=='' && $search_collection==$usercollection))
    	{
        ?>
        <script>        	
        	jQuery('.ActionsContainer.InpageNavLeftBlock').load(baseurl + "/pages/ajax/update_search_actions.php?<?php echo $change_col_url?>&collection=<?php echo $search_collection?>", function() {
    			jQuery(this).children(':first').unwrap();
			});
        </script>
        <?php
        }
    }
	


# Check to see if the user can edit this collection.
$allow_reorder=false;
if (($k=="" || $internal_share_access) && (($userref==$cinfo["user"]) || ($cinfo["allow_changes"]==1) || (checkperm("h"))))
	{
	$allow_reorder=true;
	}	
	
# Reordering capability
if ($allow_reorder)
	{
	# Also check for the parameter and reorder as necessary.
	$reorder=getvalescaped("reorder",false);
	if ($reorder)
		{
		$neworder=json_decode(getvalescaped("order",false));
		update_collection_order($neworder,$usercollection);
		exit("SUCCESS");
		}
	}


# Include function for reordering
if ($allow_reorder)
	{
	global $usersession;
	?>
	<script type="text/javascript">
		function ReorderResourcesInCollection(idsInOrder) {
			var newOrder = [];
			jQuery.each(idsInOrder, function() {
				newOrder.push(this.substring(13));
				}); 
			
			jQuery.ajax({
			  type: 'POST',
			  url: '<?php echo $baseurl_short?>pages/collections.php?collection=<?php echo urlencode($usercollection) ?>&search=<?php echo urlencode($search)?>&reorder=true',
			  data:
                {
                order:JSON.stringify(newOrder),
                <?php echo generateAjaxToken('reorder_collection'); ?>
                },
			  success: function() {
                /*
                 * Reload the top results if we're looking at the user's current collection.
                 * The !collectionX part may be urlencoded, or not, depending on how the page was reached.
                 */
			    var results = new RegExp('[\\?&amp;]' + 'search' + '=([^&amp;#]*)').exec(window.location.href);
			    var ref = new RegExp('[\\?&amp;]' + 'ref' + '=([^&amp;#]*)').exec(window.location.href);
			    if ((ref==null)&&(results!== null)&&
                    ('<?php echo urlencode("!collection" . $usercollection); ?>' === results[1]
                    ||
                    '<?php echo ("!collection" . $usercollection); ?>' === results[1])
                    
                    ) CentralSpaceLoad('<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!collection" . $usercollection); ?>',true);
			  }
			});		
		}
		jQuery(document).ready(function() {
			if(is_touch_device())
				{
				return false;
				}

			jQuery('#CollectionSpace').sortable({
				distance: 50,
				connectWith: '#CentralSpaceResources',
				appendTo: 'body',
				zIndex: 99000,
				helper: function(event, ui)
					{
					//Hack to append the element to the body (visible above others divs), 
					//but still belonging to the scrollable container
					jQuery('#CollectionSpace').append('<div id="CollectionSpaceClone" class="ui-state-default">' + ui[0].outerHTML + '</div>');   
					jQuery('#CollectionSpaceClone').hide();
					setTimeout(function() {
						jQuery('#CollectionSpaceClone').appendTo('body'); 
						jQuery('#CollectionSpaceClone').show();
					}, 1);
					
					return jQuery('#CollectionSpaceClone');
					},
				items: '.CollectionPanelShell',

				start: function (event, ui)
					{
					InfoBoxEnabled=false;
					if (jQuery('#InfoBoxCollection')) {jQuery('#InfoBoxCollection').hide();}
					jQuery('#trash_bin').show();
					},

				stop: function(event, ui)
					{
					InfoBoxEnabled=true;
					var idsInOrder = jQuery('#CollectionSpace').sortable("toArray");
					ReorderResourcesInCollection(idsInOrder);
					jQuery('#trash_bin').hide();
					}
			});
			jQuery('.CollectionPanelShell').disableSelection();
		});
		
		
	</script>
<?php } 
else { ?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
			jQuery('.ui-sortable').sortable('disable');
			jQuery('.CollectionPanelShell').enableSelection();			
		});	
	</script>
	<?php 
	} 

	hook('collections_thumbs_loaded');

    if($responsive_ui)
        {
        ?>
        <!-- Responsive -->
        <script type="text/javascript">
        jQuery(document).ready(function()
            {
            if(typeof responsive_newpage !== 'undefined' && responsive_newpage === true)
                {
                hideMyCollectionsCols();
                responsiveCollectionBar();
                responsive_newpage = false;
                }
            }); 
        </script>
        <?php
        }
        ?>
	<!-- Drag and Drop -->
	<script>
		jQuery('#CentralSpace').on('prepareDragDrop', function() {
			jQuery('#CollectionDiv').droppable({
				accept: '.ResourcePanel',

				drop: function(event, ui)
					{
					var query_strings = getQueryStrings();
					if(is_special_search('!collection', 11) && !is_empty(query_strings) && query_strings.search.substring(11) == usercollection)
						{
						// No need to re-add this resource since we are looking at the same collection in both CentralSpace and CollectionDiv
						return false;
						}

					var resource_id = jQuery(ui.draggable).attr("id");
					resource_id = resource_id.replace('ResourceShell', '');

					jQuery('#trash_bin').hide();
					// AddResourceToCollection includes a reload of CollectionDiv 
					AddResourceToCollection(event, resource_id, '');
					}
			});

			jQuery('#trash_bin').droppable({
				accept: '.CollectionPanelShell, .ResourcePanel',
				activeClass: "ui-droppable-active ui-state-hover",
				hoverClass: "ui-state-active",

				drop: function(event, ui) {
					var resource_id = ui.draggable.attr("id");
					resource_id = resource_id.replace('ResourceShell', '');
					jQuery('#trash_bin_delete_dialog').dialog({
						autoOpen: false,
						modal: true,
						resizable: false,
						dialogClass: 'delete-dialog no-close',
						open: function(event,ui) {
							jQuery(this)
								.closest(".ui-dialog")
								.find(".ui-dialog-title")
								.html("<?php echo $lang["trash_bin_delete_dialog_title"] . "<br>(" . $lang["from"]; ?> " + jQuery(this).data('collection_name') + ")");
						},
						buttons: {
							// Confirm removal of this resource from the resolved collection
							"<?php echo $lang['yes']; ?>": function() {
								var class_of_drag=jQuery(this).data('class_of_drag');
								var resource_id = jQuery(this).data("resource_id");
								var collection_id=jQuery(this).data('collection_id');
								var collection_name=jQuery(this).data('collection_name');

								if(collection_id == "")
									{
									console.error('RS_debug: Unable to resolve from which collection drag and drop resource removal is being requested.');
									jQuery(this).dialog('close');
									}
								// RemoveResourceFromCollection includes call to CollectionDivLoad

								RemoveResourceFromCollection(event, resource_id, '<?php echo $pagename; ?>', collection_id);
								// Remove resource from search results if this is not a collection search	
								if(is_special_search('!collection', 11))
									{
									jQuery('#ResourceShell' + resource_id).fadeOut();
									}
								jQuery(this).dialog('close');
							},
							// Cancel resource removal
							"<?php echo $lang['no']; ?>": function() {
								var class_of_drag=jQuery(this).data('class_of_drag');
								var resource_id = jQuery(this).data("resource_id");
								var collection_id=jQuery(this).data('collection_id');
								var collection_name=jQuery(this).data('collection_name');

								if(collection_id == "")
									{
									console.error('RS_debug: Unable to resolve which collection to reload following cancellation of resource removal.');
									jQuery(this).dialog('close');
									}

								// If resource was dragged from the CollectionPanelShell then refresh the current collection within the CollectionDiv
								if (class_of_drag.indexOf("CollectionPanelShell") >= 0)
									{
									collection_id = jQuery("#collection").val();
									CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?collection=' + collection_id);
									}
								jQuery(this).dialog('close');
							}
						}
					});
					// Resolve the collection depending on the origin of the resource been dragged
					var class_of_drag = ui.draggable.attr("class");
					var collection_id = "";
					var collection_name = "";

					// If resource is dragged from the ResourcePanel (ie. CentralSpace) then collection is in querystring
					if (class_of_drag.indexOf("ResourcePanel") >= 0)
						{
						var query_strings = getQueryStrings();
						if( !is_empty(query_strings) )
							{
						    collection_id = query_strings.search.substring(11);
						    // Collection name now stored in custom attribute
   							collection_name = jQuery("#CentralSpaceResources").attr("collectionsearchname").trim();
							}
						}
					
					// If resource is dragged from the CollectionPanelShell then use the collection from within the CollectionDiv
					if (class_of_drag.indexOf("CollectionPanelShell") >= 0)
						{
						collection_id = jQuery("#collection").val();
						collection_name = jQuery("#collection option:selected").text().trim();
						}

					jQuery('#trash_bin').hide();

					// Cancel re-order in case it was triggered
					if(jQuery('#CentralSpace').hasClass('ui-sortable'))
						{
						jQuery('#CentralSpace').sortable('cancel');
						}
					if(jQuery('#CollectionSpace').hasClass('ui-sortable'))
						{
						jQuery('#CollectionSpace').sortable('cancel');
						}

					// Process drop request without dialog when resource is being dragged from bottom collection panel
					if(class_of_drag.indexOf("CollectionPanelShell") >= 0)
						{
						// Handle different cases such as Saved searches
						if(ui.draggable.data('savedSearch') === 'yes')
							{
							CollectionDivLoad('<?php echo $baseurl; ?>/pages/collections.php?removesearch=' + resource_id + '&nc=<?php echo time(); ?>');
							}
						else
							{
							RemoveResourceFromCollection(event, resource_id, '<?php echo $pagename; ?>', collection_id);
							// Remove resource from search results if this is not a collection search	
							if(is_special_search('!collection', 11))
								{
								jQuery('#ResourceShell' + resource_id).fadeOut();
								}
							}
						}
					else
						// Show confirmation dialog when resource is being dragged from top (ie. CentralSpace)
						{
						jQuery('#trash_bin_delete_dialog').data('class_of_drag',class_of_drag)
														  .data('resource_id',resource_id)
														  .data('collection_id',collection_id)
														  .data('collection_name',collection_name);
						jQuery('#trash_bin_delete_dialog').dialog('open');
						}
				}
			});
		});

		jQuery(document).ready(function() {
			jQuery('#CentralSpace').trigger('prepareDragDrop');
			CheckHideCollectionBar();
		});
	</script>
	<!-- End of Drag and Drop -->
	<style>
	#CollectionMenuExp
		{
		height:<?php echo $collection_frame_height-15?>px;
		<?php if ($remove_collections_vertical_line){?>border-right: 0px;<?php }?>
		}
	</style>

	<?php hook("headblock");?>

	</head>

	<body class="CollectBack" id="collectbody">
<div style="display:none;" id="currentusercollection"><?php echo $usercollection?></div>

<script>usercollection='<?php echo htmlspecialchars($usercollection) ?>';</script>
<?php 

$addarray=array();

$add=getvalescaped("add","");
if ($add!="")
	{
	$allowadd=true;
	// If we provide a collection ID use that one instead
	$to_collection = getvalescaped('toCollection', '');

	if(strpos($add,",")>0)
        {
        $addarray=explode(",",$add);
        }
    else
        {
        $addarray[0]=$add;
        unset($add);
        }	

	// If collection has been shared externally need to check access and permissions
    $externalkeys=get_collection_external_access(($to_collection === '') ? $usercollection : $to_collection);
    if(count($externalkeys) > 0)
        {
        if(checkperm("noex"))
            {
            $allowadd=false;
            }
        else
            {
            foreach ($addarray as $add)
                {
                $resaccess = get_resource_access($add);
                // Not permitted if share is open and access is restricted
                if(min(array_column($externalkeys,"access")) < $resaccess)
                    {
                    $allowadd=false;
                    }
                }
            }
        if(!$allowadd)
            {			
            ?>
            <script language="Javascript">alert("<?php echo $lang["sharedcollectionaddblocked"]?>");</script>
            <?php
            }
        }

	if($allowadd)
		{
		foreach ($addarray as $add)
			{
			hook("preaddtocollection");
			#add to current collection		
			if ($usercollection == -$userref || $to_collection == -$userref || add_resource_to_collection($add,($to_collection === '') ? $usercollection : $to_collection,false,getvalescaped("size",""))==false)
				{ ?>
				<script language="Javascript">alert("<?php echo $lang["cantmodifycollection"]?>");</script><?php
				}
			else
				{		
				# Log this	
				daily_stat("Add resource to collection",$add);
			
				# Update resource/keyword kit count
				if ((strpos($search,"!")===false) && ($search!="")) {update_resource_keyword_hitcount($add,$search);}
				hook("postaddtocollection");
				}
			}
		# Show warning?
		if (isset($collection_share_warning) && $collection_share_warning)
			{
			?><script language="Javascript">alert("<?php echo $lang["sharedcollectionaddwarning"]?>");</script><?php
			}
		}
	}

$remove=getvalescaped("remove","");
if ($remove!="")
	{
	// If we provide a collection ID use that one instead
	$from_collection = getvalescaped('fromCollection', '');

	if(strpos($remove,",")>0)
		{
		$removearray=explode(",",$remove);
		}
	else
		{
		$removearray[0]=$remove;
		unset($remove);
		}	
	foreach ($removearray as $remove)
		{
		hook("preremovefromcollection");
		#remove from current collection
		if (remove_resource_from_collection($remove, ($from_collection === '') ? $usercollection : $from_collection) == false)
			{
			?><script language="Javascript">alert("<?php echo $lang["cantmodifycollection"]?>");</script><?php
			}
		else
			{
			# Log this	
			daily_stat("Removed resource from collection",$remove);		
			hook("postremovefromcollection");
			}
		}
	}
	
$addsearch=getvalescaped("addsearch",-1);
if ($addsearch!=-1)
	{
    /*
    When adding search default collection sort should be relevance to address multiple types of searches. If collection
    is used then it will error if user did a simple search and not a !collection search since there is no collection
    sortorder
    */
    $default_collection_sort = 'relevance';

    $order_by = getvalescaped('order_by', getvalescaped('saved_order_by', $default_collection_sort));

    if ($usercollection == -$userref || !collection_writeable($usercollection))
        { ?>
        <script language="Javascript">alert("<?php echo $lang["cantmodifycollection"]?>");</script><?php
        }
    else
        {
        hook("preaddsearch");
        $externalkeys=get_collection_external_access($usercollection);
		if(checkperm("noex") && count($externalkeys)>0)
			{
			// If collection has been shared externally users with this permission can't add resources			
            ?>
            <script language="Javascript">alert("<?php echo $lang["sharedcollectionaddblocked"]?>");</script>
            <?php
			}
		else
			{		
			if (getval("mode","")=="")
				{
				#add saved search
				add_saved_search($usercollection);

				# Log this
				daily_stat("Add saved search to collection",0);
				}
			else
				{
				#add saved search (the items themselves rather than just the query)
				$resourcesnotadded=add_saved_search_items($usercollection, $addsearch, $restypes,$archive, $order_by, $sort, $daylimit, $starsearch, $res_access);
				if (!empty($resourcesnotadded))
					{
					$warningtext="";
					if(isset($resourcesnotadded["blockedtypes"]))
						{
						// There are resource types blocked due to $collection_block_restypes
						$warningtext = $lang["collection_restype_blocked"] . "<br /><br />";
						//$restypes=get_resource_types(implode(",",$collection_block_restypes));
						$blocked_types=get_resource_types(implode(",",$resourcesnotadded["blockedtypes"]));
						foreach($blocked_types as $blocked_type)
							{
							if($warningtext==""){$warningtext.="<ul>";}
							$warningtext.= "<li>" . $blocked_type["name"] . "</li>";
							}
						$warningtext.="</ul>";
						unset($resourcesnotadded["blockedtypes"]);
						}
				
					if (!empty($resourcesnotadded))	
						{
						// There are resources blocked from being added due to archive state
						if($warningtext==""){$warningtext.="<br /><br />";}
						$warningtext .= $lang["notapprovedresources"] . implode(", ",$resourcesnotadded);
						}
			
					?><script language="Javascript">styledalert("<?php echo $lang["status-warning"]; ?>","<?php echo $warningtext; ?>",600);</script><?php
					}
				# Log this
				daily_stat("Add saved search items to collection",0);
				}
			hook("postaddsearch");
			}
		}
	}

$removesearch=getvalescaped("removesearch","");
if ($removesearch!="")
	{
    if (!collection_writeable($usercollection))
        { ?>
        <script language="Javascript">alert("<?php echo $lang["cantmodifycollection"]?>");</script><?php
        }
    else
        {
        hook("preremovesearch");
        #remove saved search
        remove_saved_search($usercollection,$removesearch);
        hook("postremovesearch");
        }
	}
	
$addsmartcollection=getvalescaped("addsmartcollection",-1);
if ($addsmartcollection!=-1)
	{
	
	# add collection which autopopulates with a saved search 
	add_smart_collection();
		
	# Log this
	daily_stat("Added smart collection",0);	
	}
	
$research=getvalescaped("research","");
if ($research!="")
	{
	hook("preresearch");
	$col=get_research_request_collection($research);
	if ($col==false)
		{
		$rr=get_research_request($research);
		$name="Research: " . $rr["name"];  # Do not translate this string, the collection name is translated when displayed!
		$new=create_collection ($rr["user"],$name,1);
		set_user_collection($userref,$new);
		set_research_collection($research,$new);
		}
	else
		{
		set_user_collection($userref,$col);
		# Add research request collection for collection bar actions and name fields.
		$cinfo = get_collection($col);
		$collection_refs = array();
		foreach ($list as $col_ref)
		    {
		    $collection_refs[] = $col_ref["ref"];
		    }
		if (!in_array($col,$collection_refs))
		    {
		    $list[] = $cinfo;
		    }
		}
	hook("postresearch");
	}
	
hook("processusercommand");

$searches=get_saved_searches($usercollection);

$result  = do_search("!collection{$usercollection}", '', $default_collection_sort, 0, -1, "ASC", false, 0, false, false, '', false, true,false);
$count_result = count($result);

$hook_count=hook("countresult","",array($usercollection,$count_result));if (is_numeric($hook_count)) {$count_result=$hook_count;} # Allow count display to be overridden by a plugin (e.g. that adds it's own resources from elsewhere e.g. ResourceConnect).
$feedback=$cinfo["request_feedback"];

# E-commerce functionality. Work out total price, if $basket_stores_size is enabled so that they've already selected a suitable size.
$totalprice=0;
if (isset($userrequestmode) && ($userrequestmode==2 || $userrequestmode==3) && $basket_stores_size)
	{
	foreach ($result as $resource)
		{
		# For each resource in the collection, fetch the price (set in config.php, or config override for group specific pricing)
		$id=(isset($resource["purchase_size"])) ? $resource["purchase_size"] : "";
		if ($id=="") {$id="hpr";} # Treat original size as "hpr".
		if (array_key_exists($id,$pricing))
			{
			$price=$pricing[$id];
			
			# Pricing adjustment hook (for discounts or other price adjustments plugin).
			$priceadjust=hook("adjust_item_price","",array($price,$resource["ref"],$id));
			if ($priceadjust!==false)
				{
				$price=$priceadjust;
				}
			
			$totalprice+=$price;
			}
		else
			{
			$totalprice+=999; # Error.
			}
		}
	}
?><div>

<div id="CollectionMaxDiv" style="display:<?php if ($thumbs=="show") { ?>block<?php } else { ?>none<?php } ?>"><?php

hook('before_collectionmenu');
 
# ---------------------------- Maximised view -------------------------------------------------------------------------
if (hook("replacecollectionsmax", "", array($k!="")))
	{
	# ------------------------ Hook defined view ----------------------------------
	}
else if ($basket)
	{
	# ------------------------ Basket Mode ----------------------------------------
	?>
	<div id="CollectionMenu">
	<h2><?php echo $lang["yourbasket"] ?></h2>
	<form action="<?php echo $baseurl_short?>pages/purchase.php">

	<?php if ($count_result==0) { ?>
	<p><?php echo $lang["yourbasketisempty"] ?></p><br /><br /><br />
	<?php } else { ?>
	<p><?php if ($count_result==1) {echo $lang["yourbasketcontains-1"];} else {echo str_replace("%qty",$count_result,$lang["yourbasketcontains-2"]);} ?>

	<?php if ($basket_stores_size) {
	# If they have already selected the size, we can show a total price here.
	?><br/><?php echo $lang["totalprice"] ?>: <?php echo $currency_symbol . " " . number_format($totalprice,2) ?><?php } ?>
	
	</p>

	<p style="padding-bottom:10px;"><input type="submit" name="buy" value="&nbsp;&nbsp;&nbsp;<?php echo $lang["buynow"] ?>&nbsp;&nbsp;&nbsp;" /></p>
	<?php } ?>
	<?php if (!$disable_collection_toggle) { ?>
    <a id="toggleThumbsLink" href="#" onClick="ToggleThumbs();return false;"><?php echo LINK_CARET ?><?php echo $lang["hidethumbnails"]?></a>
  <?php } ?>
	<a href="<?php echo $baseurl_short?>pages/purchases.php" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["viewpurchases"]?></a>


	</form>
	</div>
	<?php	
	}
elseif (($k != "" && !$internal_share_access) || $collection_download_only)
	{
	# ------------- Anonymous access, slightly different display ------------------
	$tempcol=$cinfo;
	?>
    <div id="CollectionMenu">
    <h2><?php echo i18n_get_collection_name($tempcol)?></h2>
        <br />
        <div class="CollectionStatsAnon">
        <?php echo $lang["created"] . " " . nicedate($tempcol["created"])?><br />
        <?php echo $count_result . " " . $lang["youfoundresources"]?><br />
        </div>
        <?php
        if ($download_usage && ((isset($zipcommand) || $collection_download) && $count_result>0 && count($result)>0)) { ?>
            <a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/terms.php?k=<?php echo urlencode($k) ?>&collection=<?php echo $usercollection ?>&url=<?php echo urlencode("pages/download_usage.php?collection=" .  $usercollection . "&k=" . $k)?>"><?php echo LINK_CARET ?><?php echo $lang["action-download"]?></a>
        <?php } else if ((isset($zipcommand) || $collection_download) && $count_result>0 && count($result)>0) { ?>
        <a href="<?php echo $baseurl_short?>pages/terms.php?k=<?php echo urlencode($k) ?>&collection=<?php echo $usercollection ?>&url=<?php echo urlencode("pages/collection_download.php?collection=" .  $usercollection . "&k=" . $k)?>" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET ?><?php echo $lang["action-download"]?></a>
        <?php }
        if ($feedback) {?><br /><br /><a onclick="return CentralSpaceLoad(this);" href="<?php echo $baseurl_short?>pages/collection_feedback.php?collection=<?php echo urlencode($usercollection) ?>&k=<?php echo urlencode($k) ?>"><?php echo LINK_CARET ?><?php echo $lang["sendfeedback"]?></a><?php } ?>
        <?php if ($count_result>0 && checkperm("q"))
            { 
            # Ability to request a whole collection (only if user has restricted access to any of these resources)
            $min_access=collection_min_access($result);
            if ($min_access!=0)
                {
                ?>
                <br/><a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_request.php?ref=<?php echo urlencode($usercollection) ?>&k=<?php echo urlencode($k) ?>"><?php echo LINK_CARET ?><?php echo $lang["requestall"]?></a>
                <?php
                }
            }
        ?>
        <?php if (!$disable_collection_toggle) { ?>
        <br/><a  id="toggleThumbsLink" href="#" onClick="ToggleThumbs();return false;"><?php echo $lang["hidethumbnails"]?></a>
    <?php } ?>
    </div>
    <?php 
    }
else
    { 
    # -------------------------- Standard display --------------------------------------------
    if ($collection_dropdown_user_access_mode)
        {?>
        <div id="CollectionMenuExp"><?php
        }
    else
        {?>
        <div id="CollectionMenu"><?php
        }
    
    if (!hook("thumbsmenu"))
        {
        if (!hook("replacecollectiontitle") && !hook("replacecollectiontitlemax"))
            {?>
            <h2 id="CollectionsPanelHeader">
                <a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_manage.php"><?php echo $lang["mycollections"]?></a>
            </h2><?php
            }?>
            <form method="get" id="colselect" onsubmit="newcolname=encodeURIComponent(jQuery('#entername').val());CollectionDivLoad('<?php echo $baseurl_short?>pages/collections.php?collection=new&search=<?php echo urlencode($search)?>&k=<?php echo urlencode($k) ?>&entername='+newcolname);return false;">
                <div style="padding:0;margin:0;"><?php echo $lang["currentcollection"]?>: 
                    <br />
                    <select name="collection" id="collection" onchange="if(document.getElementById('collection').value=='new'){document.getElementById('entername').style.display='block';document.getElementById('entername').focus();return false;} <?php if (!checkperm("b")){ ?>ChangeCollection(jQuery(this).val(),'<?php echo urlencode($k)  ?>','<?php echo urlencode($usercollection) ?>','<?php echo $change_col_url?>');<?php } else { ?>document.getElementById('colselect').submit();<?php } ?>" <?php if ($collection_dropdown_user_access_mode){?>class="SearchWidthExp"<?php } else { ?> class="SearchWidth"<?php } ?>>
                    <?php
                    $found=false;
                    for ($n=0;$n<count($list);$n++)
                        {
                        if(in_array($list[$n]['ref'],$hidden_collections))
                            {continue;}

                        if ($collection_dropdown_user_access_mode)
                            {    
                            $colusername=$list[$n]['fullname'];
                            
                            # Work out the correct access mode to display
                            if (!hook('collectionaccessmode'))
                                {
                                if ($list[$n]["public"]==0)
                                    {
                                    $accessmode= $lang["private"];
                                    }
                                else
                                    {
                                    if (strlen($list[$n]["theme"])>0)
                                        {
                                        $accessmode= $lang["theme"];
                                        }
                                    else
                                        {
                                        $accessmode= $lang["public"];
                                        }
                                    }
                                }
                            }
                            

                        #show only active collections if a start date is set for $active_collections 
                        if (strtotime($list[$n]['created']) > ((isset($active_collections))?strtotime($active_collections):1) || ($list[$n]['name']=="Default Collection" && $list[$n]['user']==$userref))
                            {?>
                            <option value="<?php echo $list[$n]["ref"]?>" <?php if ($usercollection==$list[$n]["ref"]) {?> 	selected<?php $found=true;} ?>><?php echo i18n_get_collection_name($list[$n]) ?> <?php if ($collection_dropdown_user_access_mode){echo htmlspecialchars("(". $colusername."/".$accessmode.")"); } ?></option><?php
                            }
                        }

                    if ($found==false)
                        {
                        # Add this one at the end, it can't be found
                        $notfound = $cinfo;
                        if ($notfound !== false)
                            {
                            ?>
                            <option value="<?php echo htmlspecialchars($notfound['ref']); ?>" selected><?php echo i18n_get_collection_name($notfound) ?></option>
                            <?php
                            }
                        elseif($validcollection==0)
                            {
                            ?>
                            <option selected><?php echo $lang["error-collectionnotfound"] ?></option>
                            <?php  
                            }
                        }
                    
                    if ($collection_allow_creation)
                        {?>
                        <option value="new">(<?php echo $lang["createnewcollection"]?>)</option><?php
                        }?>
                    </select>
                    <br /><small><?php echo $count_result . " "; if ($count_result==1){echo $lang["item"];} else {echo $lang["items"];} ?></small>
                    <input type=text id="entername" name="entername" style="display:none;" placeholder="<?php echo $lang['entercollectionname']?>" <?php if ($collection_dropdown_user_access_mode){?>class="SearchWidthExp"<?php } else { ?> class="SearchWidth"<?php } ?>>
                </div>			
            </form>

        <?php
        // Render dropdown actions
        hook("beforecollectiontoolscolumn");

        $resources_count = $count_result;
        render_actions($cinfo, false,true,'',$result);
        hook("aftercollectionsrenderactions");
        ?>
        <ul>
        <?php
        hook('collectiontool');
        if(!$disable_collection_toggle)
            {
            ?>
            <li>
                <a id="toggleThumbsLink" href="#" onClick="ToggleThumbs();return false;"><?php echo $lang['hidethumbnails']; ?></a>
            </li><?php
            }?>
        </ul>
    </div>
    <?php
    }?>

<!--Resource panels-->
<?php if ($collection_dropdown_user_access_mode){?>
<div id="CollectionSpace" class="CollectionSpaceExp">
<?php } else { ?>
<div id="CollectionSpace" class="CollectionSpace">
<?php } ?>

<?php 
# Loop through saved searches
if (isset($cinfo['savedsearch'])&&$cinfo['savedsearch']==null  && ($k=='' || $internal_share_access))
	{ // don't include saved search item in result if this is a smart collection  

	# Setting the save search icon
	$folderurl=$baseurl."/gfx/images/";
	$iconurl=$folderurl."save-search"."_".$language.".gif";
	if (!file_exists($iconurl))
		{
		# A language specific icon is not found, use the default icon
		$iconurl = $folderurl . "save-search.gif";
		}

	for ($n=0;$n<count($searches);$n++)			
		{
		$ref=$searches[$n]["ref"];
		$url=$baseurl_short."pages/search.php?search=" . urlencode($searches[$n]["search"]) . "&restypes=" . urlencode($searches[$n]["restypes"]) . "&archive=" . urlencode($searches[$n]["archive"]);
		?>
		<!--Resource Panel-->
		<div id="ResourceShell<?php echo $searches[$n]['ref']; ?>" class="CollectionPanelShell" data-saved-search="yes">
		<table border="0" class="CollectionResourceAlign"><tr><td>
		<a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $url?>"><img border=0 width=56 height=75 src="<?php echo $iconurl?>"/></a></td>
		</tr></table>
		<?php if(!hook('replacesavedsearchtitle')){?>
		<div class="CollectionPanelInfo">
		<a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $url?>"><?php echo substr($lang["savedsearch"],6)?> <?php echo $n+1?></a>&nbsp;</div><?php } ?>
		<?php if(!hook('replaceremovelink_savedsearch')){?>
		<div class="CollectionPanelTools">
		<a class="removeFromCollection fa fa-minus-circle" onclick="return CollectionDivLoad(this);" href="<?php echo $baseurl_short?>pages/collections.php?removesearch=<?php echo urlencode($ref) ?>&nc=<?php echo time()?>">
		</a></div>	<?php } ?>			
		</div>
		<?php		
		}
}		

# Loop through thumbnails
if ($count_result>0) 
	{
	# loop and display the results
	for ($n=0;$n<count($result) && $n<$count_result && $n<$max_collection_thumbs;$n++)					
		{
		$ref=$result[$n]["ref"];
		?>
<?php if (!hook("resourceview")) { ?>
		<!--Resource Panel-->
		<div class="CollectionPanelShell ResourceType<?php echo $result[$n]['resource_type']; ?>" id="ResourceShell<?php echo urlencode($ref) ?>"
        <?php if (in_array($ref,$addarray)) { ?>style="display:none;"<?php } # Hide new items by default then animate open ?>>
        
		<?php if (!hook("rendercollectionthumb")){?>
        <?php
        
        $access = isset($result[$n]["access"]) ? $result[$n]["access"] : get_resource_access($result[$n]);
		$use_watermark=check_use_watermark();?>
		<table border="0" class="CollectionResourceAlign"><tr><td>
				<a style="position:relative;" onclick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" href="<?php echo $baseurl_short?>pages/view.php?ref=<?php echo urlencode($ref) ?>&search=<?php echo urlencode("!collection" . $usercollection)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&k=<?php echo urlencode($k)?>&curpos=<?php echo $n ?>">
                <?php
                if(1 == $result[$n]['has_image']
                    && file_exists(get_resource_path($ref, true, ($retina_mode ? 'thm' : 'col'), false, $result[$n]['preview_extension'], true, 1, $use_watermark, $result[$n]['file_modified']))
                )
                    {
                    $colimgpath = get_resource_path($ref, false, ($retina_mode ? 'thm':'col'), false, $result[$n]['preview_extension'], true, 1, $use_watermark, $result[$n]['file_modified']);
                    ?>
                    <img class="CollectionPanelThumb" border=0 src="<?php echo $colimgpath; ?>" title="<?php echo htmlspecialchars(i18n_get_translated($result[$n]["field".$view_title_field]))?>" alt="<?php echo htmlspecialchars(i18n_get_translated($result[$n]["field".$view_title_field]))?>"
                    <?php if ($retina_mode) { ?>onload="this.width/=2;this.onload=null;"<?php } ?> /><?php
                    }
				else
						{?>
						<img border=0 src="<?php echo $baseurl_short?>gfx/<?php echo get_nopreview_icon($result[$n]["resource_type"],$result[$n]["file_extension"],true) ?>" />
						<?php
						}
						hook("aftersearchimg","",array($result[$n]))?>
						</a></td>
		</tr></table>
		<?php } /* end hook rendercollectionthumb */?>
		
		<?php 

		$title=$result[$n]["field".$view_title_field];	
		$title_field=$view_title_field;
		if (isset($metadata_template_title_field) && isset($metadata_template_resource_type))
			{
			if ($result[$n]['resource_type']==$metadata_template_resource_type)
				{
				$title=$result[$n]["field".$metadata_template_title_field];
				$title_field=$metadata_template_title_field;
				}	
			}	
		$field_type=sql_value("select type value from resource_type_field where ref=$title_field","", "schema");
		if($field_type==8){
			$title=str_replace("&nbsp;"," ",$title);
		}
		?>	
		<?php if (!hook("replacecolresourcetitle")){?>
		<div class="CollectionPanelInfo"><a onclick="return <?php echo ($resource_view_modal?"Modal":"CentralSpace") ?>Load(this,true);" href="<?php echo $baseurl_short?>pages/view.php?ref=<?php echo urlencode($ref) ?>&search=<?php echo urlencode("!collection" . $usercollection)?>&k=<?php echo urlencode($k) ?>" title="<?php echo htmlspecialchars(i18n_get_translated($result[$n]["field".$view_title_field]))?>"><?php echo htmlspecialchars(tidy_trim(i18n_get_translated($title),14));?></a>&nbsp;</div>
		<?php } ?>
		
		<?php if ($k!="" && $feedback) { # Allow feedback for external access key users
		?>
		<div class="CollectionPanelInfo">
		<span>  <a aria-hidden="true" class="fa fa-comment"onclick="return ModalLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_comment.php?ref=<?php echo urlencode($ref) ?>&collection=<?php echo urlencode($usercollection) ?>&k=<?php echo urlencode($k) ?>"/></span>		
		</div>
		<?php } ?>
		
		<?php hook('before_collectionpaneltools'); ?>
		
		<?php if ($k=="" || $internal_share_access) { ?><div class="CollectionPanelTools">

		<?php if (!isset($cinfo['savedsearch'])||(isset($cinfo['savedsearch'])&&$cinfo['savedsearch']==null)){ // add 'remove' link only if this is not a smart collection 
			?>
            
        <?php
        $rating = '';
        if(isset($rating_field))
            {
            $rating = "field{$rating_field}";
            }
            
            $url = $baseurl_short."pages/view.php?ref=" . $ref . "&amp;search=" . urlencode('!collection' . $usercollection) . "&amp;order_by=" . urlencode($order_by) . "&amp;sort=". urlencode($sort) . "&amp;offset=" . urlencode($offset) . "&amp;archive=" . urlencode($archive) . "&amp;k=" . urlencode($k) . "&amp;curpos=" . urlencode($n) . '&amp;restypes=' . urlencode($restypes);
            
        # Include standard search views    
        include "search_views/resource_tools.php";  
            
			} # End of remove link condition 
		?></div><?php 
		} # End of k="" condition 
		 ?>
		</div>
		<?php
		} # End of ResourceView hook
	  } # End of loop through resources
	  
		# Hook to allow plugins to list additional resources in a collection (e.g. resourceconnect)	  
		hook("thumblistextra");
	?>
	<div class="clearerleft"></div>
	<?php
	} # End of results condition

	
if($count_result > $max_collection_thumbs && !hook('replace_collectionpanel_viewall'))
	{
	?>
	<div class="CollectionPanelShell">
		<table border="0" class="CollectionResourceAlign">
			<tr>
				<td><img/></td>
			</tr>
		</table>
		<div class="CollectionPanelInfo">
			<a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/search.php?search=!collection<?php echo $usercollection?>&k=<?php echo urlencode($k) ?>"><?php echo $lang['viewall']?>...</a>
		</div>
	</div>
	<?php
	}

if (count($addarray)>0 && $addarray[0]!="")
{
# Animate the new item
?>
<script type="text/javascript">
jQuery("#CollectionSpace #ResourceShell<?php echo htmlspecialchars($addarray[0]) ?>").slideDown('fast');
</script>
<?php      
}

?>
</div></div>
<?php 

}


	?><div id="CollectionMinDiv" style="display:<?php if ($thumbs=="hide") { ?>block<?php } else { ?>none<?php } ?>">
	<!--Title-->	
	<?php 
	# ------------------------- Minimised view
	if (!hook("nothumbs")) {

	if (hook("replacecollectionsmin", "", array($k!="")))
		{
		# ------------------------ Hook defined view ----------------------------------
		}
	else if ($basket)
		{
		# ------------------------ Basket Mode ----------------------------------------
		?>
		<div id="CollectionMinTitle"><h2><?php echo $lang["yourbasket"] ?></h2></div>
		<div id="CollectionMinRightNav" class="CollectionBasket">
		<form action="<?php echo $baseurl_short?>pages/purchase.php">
		<ul>
		
		<?php if ($count_result==0) { ?>
		<li><?php echo $lang["yourbasketisempty"] ?></li>
		<?php } else { ?>

		<?php if ($basket_stores_size) {
		# If they have already selected the size, we can show a total price here.
		?><li><?php echo $lang["totalprice"] ?>: <?php echo $currency_symbol . " " . number_format($totalprice,2) ?><?php } ?></li>
		<li><a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/search.php?search=<?php echo urlencode("!collection" . $usercollection)?>"><?php echo $lang["viewall"]?></a></li>
		<li><input type="submit" name="buy" value="&nbsp;&nbsp;&nbsp;<?php echo $lang["buynow"] ?>&nbsp;&nbsp;&nbsp;" /></li>
		<?php } ?>
	  <?php if (!$disable_collection_toggle) { ?>
		<?php /*if ($count_result<=$max_collection_thumbs) { */?><li><a id="toggleThumbsLink" href="#" onClick="ToggleThumbs();return false;"><?php echo $lang["showthumbnails"]?></a></li><?php /*}*/ ?>
	  <?php } ?>
		<li><a href="<?php echo $baseurl_short?>pages/purchases.php" onclick="return CentralSpaceLoad(this,true);"><?php echo $lang["viewpurchases"]?></a></li>
		</ul>
		</form>

		</div>
		<?php	
		} // end of Basket Mode
	elseif (($k != "" && !$internal_share_access) || $collection_download_only)
		{
		# Anonymous access, slightly different display
		$tempcol=$cinfo;
	
		?>
	<div id="CollectionMinTitle" class="ExternalShare"><h2><?php echo i18n_get_collection_name($tempcol)?></h2></div>
	<div id="CollectionMinRightNav" class="ExternalShare">
		<?php if(!hook("replaceanoncollectiontools")){ ?>
		<?php if ((isset($zipcommand) || $collection_download) && $count_result>0 && count($result) > 0) { ?>
		<li><a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/terms.php?k=<?php echo urlencode($k) ?>&url=<?php echo urlencode("pages/collection_download.php?collection=" .  $usercollection . "&k=" . $k)?>"><?php echo $lang["action-download"]?></a></li>
		<?php } ?>
		<?php if ($feedback) {?><li><a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_feedback.php?collection=<?php echo urlencode($usercollection) ?>&k=<?php echo urlencode($k) ?>"><?php echo $lang["sendfeedback"]?></a></li><?php } ?>
		<?php if ($count_result>0)
			{ 
			# Ability to request a whole collection (only if user has restricted access to any of these resources)
			$min_access=collection_min_access($result);
			if ($min_access!=0)
				{
				?>
				<li><a onclick="return CentralSpaceLoad(this,true);" href="<?php echo $baseurl_short?>pages/collection_request.php?ref=<?php echo urlencode($usercollection) ?>&k=<?php echo urlencode($k) ?>"><?php echo 	$lang["requestall"]?></a></li>
				<?php
				}
			}
		?>
	  <?php if (!$disable_collection_toggle) { ?>
		<li><a id="toggleThumbsLink" href="#" onClick="ToggleThumbs();return false;"><?php echo $lang["showthumbnails"]?></a></li>
	  <?php } ?>
	  <?php } # end hook("replaceanoncollectiontools") ?>
	</div>
	<?php 
		}
	else
		{
		?>
		<div class="ToggleThumbsContainer">
			<a id="toggleThumbsLink" href="#" onClick="ToggleThumbs();return false;"><?php echo $lang['showthumbnails']; ?></a>
		</div>

		<?php hook('aftertogglethumbs'); ?>

		<!--Menu-->	
		<div id="CollectionMinRightNav">
    	<?php
	    // Render dropdown actions
		render_actions($cinfo, false, false, "min",$result);
		?>
		</div>

		<!--Collection Dropdown-->
		<?php
		if(!hook('replace_collectionmindroptitle'))
			{
			?>
		<div id="CollectionMinDropTitle"><?php echo $lang['currentcollection']; ?>:&nbsp;</div>
    		<?php
    		} # end hook replace_collectionmindroptitle
    		?>
		<div id="CollectionMinDrop">
	 		<form method="get"
	 			  id="colselect2" 
	 			  onsubmit="newcolname=encodeURIComponent(jQuery('#entername2').val());CollectionDivLoad('<?php echo $baseurl_short; ?>pages/collections.php?thumbs=hide&collection=new&search=<?php echo urlencode($search)?>&k=<?php echo urlencode($k); ?>&search=<?php echo urlencode($search)?>&entername='+newcolname);return false;">
				<div class="MinSearchItem" id="MinColDrop">
					<input type=text id="entername2" name="entername" placeholder="<?php echo $lang['entercollectionname']; ?>" style="display:inline;display:none;" class="SearchWidthExp">
				</div>
				<script>jQuery('#collection').clone().attr('id','collection2').attr('onChange',"if(document.getElementById('collection2').value=='new'){document.getElementById('entername2').style.display='inline';document.getElementById('entername2').focus();return false;}<?php if (!checkperm('b')){ ?>ChangeCollection(jQuery(this).val(),'<?php echo urlencode($k) ?>','<?php echo urlencode($usercollection) ?>','<?php echo $change_col_url ?>');<?php } else { ?>document.getElementById('colselect2').submit();<?php } ?>").prependTo('#MinColDrop');</script>
	  		</form>
		</div>
		<?php
		}
	}
	?>
	<!--Collection Count-->	
	<?php if(!hook("replace_collectionminitems")){?>
	<div id="CollectionMinitems"><strong><?php echo $count_result?></strong>&nbsp;<?php if ($count_result==1){echo $lang["item"];} else {echo $lang["items"];}?></div>
	<?php } # end hook replace_collectionminitems ?>
	</div>

<?php
draw_performance_footer();




if ($chosen_dropdowns_collection) { ?>
<!-- Chosen support -->
<script type="text/javascript">
	chosenColElm = (thumbs=='show' ? '#CollectionMaxDiv' : '#CollectionMinDiv');
	var css_width = jQuery(chosenColElm+" select").css("width");
	jQuery(chosenColElm+" select").each(function(){
		var css_width = jQuery(this).css("width");
		jQuery(this).chosen({disable_search_threshold: chosenCollectionThreshold, 'width': css_width});
	});
	
	jQuery("#CollectionDiv select").on('chosen:showing_dropdown', function(event, params) {
	   
	   var chosen_container = jQuery( event.target ).next( '.chosen-container' );
	   chosen_containerParent = jQuery(chosen_container).parent();
	   var dropdown = chosen_container.find( '.chosen-drop' );
	   var dropdown_top = dropdown.offset().top - jQuery('#CollectionDiv').scrollTop();
	   var dropdown_height = dropdown.height();
	   var viewport_height = jQuery('#CollectionDiv').height();

	   if ( dropdown_top + dropdown_height > viewport_height ) {
		  chosen_container.addClass( 'chosen-drop-up' );
		  myLayout.allowOverflow('south');
	   }
	   switch(thumbs){
	   		case 'show':
	   			
	   			if(jQuery(chosen_containerParent).hasClass('SearchItem')){
					jQuery("#colselect .chosen-drop").css("display","block");
				}
				else{
					jQuery("#CollectionMaxDiv .ActionsContainer .chosen-drop").css("display","block");
				}
	   			break;
	   		case 'hide':
	   			
	   			if(jQuery(chosen_containerParent).attr('id')=='MinColDrop'){
					jQuery("#colselect2 .chosen-drop").css("display","block");
				}
				else{
					jQuery("#CollectionMinRightNav .ActionsContainer .chosen-drop").css("display","block");
				}
				break;
	   }

	});
	
	jQuery("#CollectionDiv select").on('chosen:hiding_dropdown', function(event, params) {
	   
	   myLayout.resetOverflow('south');
	   chosen_containerParent = jQuery( event.target ).next( '.chosen-container' ).parent()
	   jQuery( event.target ).next( '.chosen-container' ).removeClass( 'chosen-drop-up' );
	   switch(thumbs){
	   		case 'show':
	   			if(jQuery(chosen_containerParent).hasClass('SearchItem')){
					jQuery("#colselect .chosen-drop").css("display","none");
				}
				else{
					jQuery("#CollectionMaxDiv .ActionsContainer .chosen-drop").css("display","none");
				}
	   			break;
	   		case 'hide':
	   			if(jQuery(chosen_containerParent).attr('id')=='MinColDrop'){
					jQuery("#colselect2 .chosen-drop").css("display","none");
				}
				else{
					jQuery("#CollectionMinRightNav .ActionsContainer .chosen-drop").css("display","none");
				}
				break;
	   } 
	});
	
	// for some reason creating a collection would not work without specifying this bit of code...
	jQuery('#entername2').keyup(function(e){
		if(e.keyCode == 13){
			jQuery("#colselect2").submit();
		}
	});
	
	jQuery('#entername').keyup(function(e){
		if(e.keyCode == 13){
			jQuery("#colselect").submit();
		}
	});
</script>
<!-- End of chosen support -->
<?php } ?>
