<?php
$suppress_headers=true; # Suppress headers including the XFRAME limitation so that this page can be remotely embedded.

include "../../../include/boot.php";

include_once "../languages/en.php"; # Because this may not be included automatically, i.e. if the plugin is not available to all groups.

# Get variables and check key is valid.
$ref        = getval('ref', '');
$k          = getval('k', '');
$size       = getval('size', 'pre');
$transition = (int)getval('transition', 4, true);
$showtext   = getval('showtext', '0');
    
# Check key is valid
if (!check_access_key_collection($ref,$k))
    {
    exit($lang["embedslideshow_notavailable"]);
    }
    
# Load watermark settings
$use_watermark=check_use_watermark();
ob_start();
?>

<html>
<head>
<link rel="stylesheet" href="<?php echo $baseurl?>/lib/fontawesome/css/all.min.css?css_reload_key=<?php echo $css_reload_key?>">
<link rel="stylesheet" href="<?php echo $baseurl?>/lib/fontawesome/css/v4-shims.min.css?css_reload_key=<?php echo $css_reload_key?>">
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slideshow</title>
    <style>
        /* Reset some default browser padding */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Layout styling */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
       .content {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .slideshow {
            position: relative;
            width: 100%;
            height: 100%;
        }
        .slide {
            position: absolute;
            width: 90%;
            height: 90%;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .slide.active {
            opacity: 1;
        }
        .fas {
            color: #888;
            margin:auto 2px;
            float:left;
            padding: 4px 5px;
            border-radius: 3px;
            -moz-border-radius: 3px;
        }
        .fas:hover {
	        color:#585858;
	    }

        .embedslideshow_text {
            margin-top: 86vh;
            margin-left: 40vw;
        }
        .slide-navigator {
            width:40vw;margin:auto;
        }
        .slide-control {
            width: 8%;
            height: 8%;
            cursor: pointer;
        }
        .slide-control-atpage {
            width: 20%;
            height: 8%;
            cursor: pointer;
        }
        .slide-page-button-style {
            float:left;
            margin-top: 2px;
            min-width: 40px;
            max-width: 60px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #f0f0f0;
            cursor: pointer;
        }
        .slide-page-button-style:hover {
	        color:#444;
	    }
        .slide-page-input-active {
            background-color: white;
            border: 1px solid #999;
            cursor: text;
        }
    </style>

</head>
<body>
<!-- Main Content -->
<main class="content">
    <div class="slideshow">

    <?php
    $page=1;
    # Load images from the slideshow collection
    $resources=do_search("!collection" . $ref);
    if(count($resources) == 0)
        {
        $all_fcs = get_all_featured_collections();
        $refs = array_column($all_fcs, 'ref');
        $ref_key = array_search($ref, $refs);
        if($ref_key !== null && is_featured_collection_category($all_fcs[$ref_key]))
            {
            $resources = get_featured_collection_resources($all_fcs[$ref_key], ['all_fcs' => $all_fcs]);
            $resources = get_resource_data_batch($resources);
            }
        }
    if(count($resources) == 0)
        {
        exit($lang["embedslideshow_notavailable"]);
        }

    foreach ($resources as $resource)
        {
        $file_path=get_resource_path($resource["ref"],true,$size,false,$resource["preview_extension"],-1,1,$use_watermark);
        if (file_exists($file_path))
            {
            $preview_path=get_resource_path($resource["ref"],false,$size,false,$resource["preview_extension"],-1,1,$use_watermark);     
            }
        else
            {
            # Fall back to 'pre' size
            $preview_path=get_resource_path($resource["ref"],false,"pre",false,$resource["preview_extension"],-1,1,$use_watermark);
            }        
        $preview_path .= "&k=" . $k;
    ?>
    <!-- Markup for current resource START -->
    <div class="slide" style="background-image: url(<?php echo escape($preview_path); ?>);" onClick="showNextSlide();return false;">
    <?php 
        global $embedslideshow_textfield,$embedslideshow_resourcedatatextfield;
        if($embedslideshow_textfield && $showtext) 
            { 
            $resource_textdata = i18n_get_translated(get_data_by_field($resource["ref"],$embedslideshow_resourcedatatextfield));
            if($resource_textdata !="") 
                {
                ?>
                <div class="embedslideshow_text" id="embedslideshow_previewtext<?php echo $page ?>"><?php echo escape($resource_textdata);?></div>
                <?php
                }       
            }
    ?>
    </div>

    <!-- Markup for current resource END -->
    <?php 
        $page++;
        }
    $maxpages=$page-1;
    ob_flush();
    ?>
    <!-- Containing section END -->
    </div>
</main>

<!-- Slideshow navigation control markup START -->
<div class="slide-navigator">
<ul>   
    <li id="slide-go-start"  class="slide-control" style="display: inline-block;" onClick="showSlidePage('start');return false;"><i class="fas fa-step-backward"></i></li>
    <li id="slide-go-prev"   class="slide-control" style="display: inline-block;" onClick="showPrevSlide();return false;"><i class="fas fa-backward"></i></li>
    <li id="slide-pause"     class="slide-control" style="display: inline-block;" onClick="pauseSlideShow();return false;"><i class="fas fa-pause"></i></li>
    <li id="slide-play"      class="slide-control" style="display: none;" onClick="playSlideShow();return false;"><i class="fas fa-play"></i></li>
    <li id="slide-go-next"   class="slide-control" style="display: inline-block;" onClick="showNextSlide();return false;"><i class="fas fa-forward"></i></li>
    <li id="slide-go-end"    class="slide-control" style="display: inline-block;" onClick="showSlidePage('end');return false;"><i class="fas fa-step-forward"></i></li>
    <li id="slide-go-atpage" class="slide-control-atpage" style="display: inline-block;">
        <span><input type="number" id="slide-page-number" class="slide-page-button-style" min="1"/></span>
    </li>
    <li class="slide-control" style="display: inline-block;">
        <span style="margin-top:2px;float:left;">&nbsp;&nbsp;&sol;&nbsp;&nbsp;</span>
        <span id="slide-page-count" style="margin-top:2px;float:left;font-size:14px;"></span>
    </li>
</ul><br>
</div>
<!-- Slideshow navigation control markup END -->

<!-- JavaScript for slideshow -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const page_input = document.getElementById("slide-page-number");

    // Event listener for focus - changes input to editable mode
    page_input.addEventListener("focus", function() {
        page_input.classList.add("input-active");
        page_input.value = ""; 
        page_input.placeholder = ""; // Clear placeholder when editing
    });

    // Event listener for blur (when focus is lost) - reverts to button appearance
    page_input.addEventListener("blur", function() {
        if (page_input.value) {
            page_input.classList.remove("input-active");
            page_input.classList.add("button-style");
            page_input.placeholder = page_input.value; // Set entered number as placeholder
        } 
        if (page_input.value > 0 && page_input.value <= slides.length) {
            showSlidePage(page_input.value);
        }
    });

    // Event listener for Enter key press
    page_input.addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            page_input.blur(); // Trigger blur to apply changes
        }
    });
});

const slides = document.querySelectorAll('.slide');
const slideTransition = 1000 * <?php echo (int)$transition;?>;

let slideTimer = 0;
let currentSlide = 0;

function showNextSlide() {
    // Hide current slide
    slides[currentSlide].classList.remove('active');
    // Move to the next slide, or back to the first one if at the end
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
    updatePageNumber(currentSlide);
}

function showPrevSlide() {
    // Hide current slide
    slides[currentSlide].classList.remove('active');
    // Move to the previous slide, or back to the last one if at the start
    currentSlide = currentSlide - 1;
    if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }
    slides[currentSlide].classList.add('active');
    updatePageNumber(currentSlide);
}

function showSlidePage(pageRequest) {
    // Stop the running slideshow 
    clearInterval(slideTimer);
    // Hide current slide
    slides[currentSlide].classList.remove('active');
    if(pageRequest==='start') {
        currentSlide = 0;
    }
    else if(pageRequest==='end') {
        currentSlide = slides.length - 1;
    }
    else if(!isNaN(pageRequest)) {
        currentSlide = pageRequest - 1;
    }
    // Show requested slide and then restart the slideshow
    slides[currentSlide].classList.add('active');
    slideTimer = setInterval(showNextSlide, slideTransition);
    updatePageNumber(currentSlide);
}

function pauseSlideShow() {
    clearInterval(slideTimer);
    document.getElementById('slide-pause').style.display = 'none';
    document.getElementById('slide-play').style.display = 'inline-block';
}

function playSlideShow() {
    showNextSlide();
    document.getElementById('slide-play').style.display = 'none';
    document.getElementById('slide-pause').style.display = 'inline-block';
    slideTimer = setInterval(showNextSlide, slideTransition);
}

function updatePageNumber(slidePageNumber) {
    const slidePageElement = document.getElementById("slide-page-number");
    slidePageElement.value = slidePageNumber + 1; 
    slidePageElement.placeholder = slidePageNumber + 1;
}
// Display number of pages in slideshow
document.getElementById('slide-page-count').textContent = slides.length;

// Initialize slideshow by displaying the first image
slides[currentSlide].classList.add('active');
updatePageNumber(currentSlide);

// Set interval for changing slides
slideTimer = setInterval(showNextSlide, slideTransition); 
</script>

</body>
</html>
