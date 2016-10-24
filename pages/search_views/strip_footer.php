<script>


function ResizeResults()
	{
	var screenWidth = jQuery('#CentralSpaceResources').width()-10;
	var images = jQuery('.ImageStrip');
	var accumulatedWidth = 0;
	var imageGap = 15;
        var imageHeight = 150;
	var processArray = [];

	for (var i = 0; i < images.length; i++) {
        accumulatedWidth+=images[i].width+imageGap;
	if (accumulatedWidth>screenWidth)
		{
		// Work out a height
		var factor=screenWidth / (accumulatedWidth-images[i].width - imageGap);
		//console.log(factor);jQuery(images[i]).css("border-color","red");

		// Rescale images on current row
		for (var n=0; n< processArray.length; n++)
			{
			jQuery(processArray[n]).css("height",Math.round(factor * imageHeight,2) + "px");
			}
		// Empty process array
		processArray = [];
		accumulatedWidth=images[i].width+imageGap;
		}  

	processArray.push(images[i]);
    	}
      }

jQuery(document).ready(function () {ResizeResults();});
jQuery(window).resize(function () {ResizeResults();});

</script>

<style>
.ImageStrip
        {
        height:150px;
        width:auto;
        margin:10px 10px 0 0;
        xfloat:left;
        xvisibility:hidden;
        }
#CentralSpaceResources  
        {
text-align:justify;
padding-right:10px;
        }

</style>

