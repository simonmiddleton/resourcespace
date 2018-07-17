/*
 * 	Easy Tooltip 1.0 - jQuery plugin
 *	written by Alen Grakalic	
 *	http://cssglobe.com/post/4380/easy-tooltip--jquery-plugin
  *
 *	Copyright (c) 2009 Alen Grakalic (http://cssglobe.com)
 *	Dual licensed under the MIT (MIT-LICENSE.txt)
 *	and GPL (GPL-LICENSE.txt) licenses.
 *
 *	Built for jQuery library
 *	http://jquery.com
 *
 * 
 */
 
// [t17591] Tooltip text affects simple search; gives rise to flickering pointer making input field unusable.
// Problem is caused by pointer events on the tooltip element interfering with hover events on the underlying element. 
// The code in easyTooltip.js has been changed so that pointer events are disabled on hover which eliminates the flicker. 
// Having fixed this, a secondary issue becomes apparent in that when the bespoke tooltip is removed, 
// the title attribute is reinstated, and this gives rise to the appearance of the vanilla css tooltip, which looks odd. 
// To eliminate the css tooltip, the code has been changed to shunt (or sideline) the title attribute to another custom 
// attribute called titlesidelined. Any subsequent hover events will make use of the titlesidelined attribute because 
// the title attribute is no longer populated; thus eliminating the css tooltip.
// Also removed 5 clones of the function within the source which are superfluous.

(function($) {

	$.fn.easyTooltip = function(options){
	  
		// default configuration properties
		var defaults = {	
			xOffset: 10,		
			yOffset: 25,
			charwidth: 25,
			cssclass: "ListviewStyle",
			tooltipId: "easyTooltip",
			clickRemove: false,
			content: "",
			useElement: ""
		}; 
			
		var options = $.extend(defaults, options);  
		var content;
				
		this.each(function() {  				
			var title = $(this).attr("title");				
			var titlesidelined = $(this).attr("titlesidelined");				
			additionalyoffset = 0;
			$(this).hover(function(e){
			    // First time hover occurs: content or title have the tooltip
				content = (options.content != "") ? options.content : title;

			    // Hovers thereafter: title attribute is blanked and so content or titlesidelined have the tooltip											 							   
				if (content == "") { 
					content = titlesidelined; 
				} 

				content = (options.useElement != "") ? $("#" + options.useElement).html() : content;
				// The title attribute is moved to titlesidelined for subsequent use
				$(this).attr("title","");									  				
				$(this).attr("titlesidelined",titlesidelined);									  				

				if (content != "" && content != undefined){	
					//added to prevent tooltip appearing behind cursor and causing flickering
					if (content.length >= 25)
					{additionalyoffset = (Math.floor((content.length) / options.charwidth)) * 12}
					$("body").append("<div id='"+ options.tooltipId + "' class='" +  options.cssclass +"' >"+ content +"</div>");		
					$("#" + options.tooltipId)
						.css("position","absolute")
						.css("top",((e.pageY - options.yOffset) - additionalyoffset) + "px")
						.css("left",(e.pageX + options.xOffset) + "px")						
						.css("display","none")
						.fadeIn("fast")
						.css("pointer-events","none")
				}
			},
			function(){	
				$("#" + options.tooltipId).remove();
			    // Title no longer reinstated; now stored in titlesidelined
				// $(this).attr("title",title);
			});	
			// $(this).mousemove(function(e){
			// 	$("#" + options.tooltipId)
			// 			.css("top",((e.pageY - options.yOffset) - additionalyoffset) + "px")
			// 		.css("left",(e.pageX + options.xOffset) + "px")					
			// });	
			if(options.clickRemove){
				$(this).mousedown(function(e){
					$("#" + options.tooltipId).remove();
   				    // Title no longer reinstated; now stored in titlesidelined
					// $(this).attr("title",title);
				});				
			}
		});
	  
	};

})(jQuery);
