var SlideshowTimer=0;
var SlideshowActive=false;
            
function RegisterSlideshowImage(image, resource, single_image_flag)
    {
    if(typeof single_image_flag === 'undefined')
        {
        single_image_flag = false;
        }

    // If we are only registering one image then remove any images registered so far
    if(single_image_flag)
        {
        SlideshowImages.length = 0;
        }

    SlideshowImages.push(image);
    }

function SlideshowChange()
    {
    if (SlideshowImages.length==0 || !SlideshowActive) {return false;}
    
    SlideshowCurrent++;  
    SlideshowNext = SlideshowCurrent+1;      

    if (SlideshowCurrent>=SlideshowImages.length)
        {
        SlideshowCurrent=0;
        SlideshowNext = SlideshowCurrent+1; 
        }

    if (SlideshowNext>=SlideshowImages.length)
        {
        SlideshowNext=0;
        }

    // Using to images layered resolves flickering in transitions
    jQuery('body').css('background-image','url(' + SlideshowImages[SlideshowCurrent] + '), url(' + SlideshowImages[SlideshowNext] + ')');

    var photo_delay = 1000 * big_slideshow_timer;
        
    if (!StaticSlideshowImage) {SlideshowTimer=window.setTimeout(SlideshowChange, photo_delay);}
    
    return true;
    }

function ActivateSlideshow(show_footer)
    {
    if (!SlideshowActive)
        {
        SlideshowCurrent=-1;
        SlideshowActive=true;
        SlideshowChange();

        if (typeof show_footer == 'undefined' || !show_footer)
            {
            jQuery('#Footer').hide();
            }
        }

        jQuery( document ).ready(function() 
            {
            jQuery('body').css('transition', 'background-image 1s linear');
            });
    }
    
function DeactivateSlideshow()
    {
    jQuery('body').css('background-image','none');
    SlideshowActive=false;
    window.clearTimeout(SlideshowTimer);

    jQuery('#Footer').show();
    }

