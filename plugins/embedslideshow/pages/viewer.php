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

$player_width   = getval('width', 0, true);
$player_height  = getval('height', 0, true);
if ($player_width === 0 || $player_height === 0)
    {
    exit("Invalid height and width parameters.");
    }
$player_height = $player_height - 48;
$player_ratio = $player_width / $player_height;
    
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

        header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1em;
            position: fixed;
            width: 100%;
            z-index: 10;
        }

        header {
            top: 0;
        }
        h1 {
            text-align: center;
        }
       .content {
            margin-top: 60px; /* Offset for header */
            margin-bottom: 60px; /* Offset for footer */
            margin-right: 200px; /* Offset for sidebar */
            display: flex;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 120px); /* 100vh minus header and footer */
            overflow: hidden;
        }
        .slideshow {
            position: relative;
            width: 100%;
            height: 100%;
        }
        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        .slide.active {
            opacity: 1;
        }
        .embedslideshow_text {
            margin-top: 10px;
            margin-left: 10px;
        }
        .slide-page-button-style {
            width: 60px;
            height: 40px;
            text-align: center;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f0f0f0;
            cursor: pointer;
        }
        .slide-page-input-active {
            background-color: white;
            border: 1px solid #999;
            cursor: text;
        }

    </style>

</head>
<body>
<!-- Header -->
<header>
    <h1>Slideshow</h1>
</header>

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
    <div class="slide" style="background-image: url(<?php echo escape($preview_path); ?>);">

    <?php 
        global $embedslideshow_textfield,$embedslideshow_resourcedatatextfield;
        if($embedslideshow_textfield && $showtext) 
            { 
            $resource_textdata = i18n_get_translated(get_data_by_field($resource["ref"],$embedslideshow_resourcedatatextfield));
            if($resource_textdata !="") 
                {
                ?>
                <div class="embedslideshow_text" id="embedslideshow_previewtext<?php echo $page ?>"><?php echo $resource_textdata;?></div>
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
<ul>   
    <li id="slide-go-start" style="cursor: pointer;display: inline-block;" onClick="showSlidePage('start');return false;"><i class="fas fa-step-backward"></i>&nbsp;</li>
    <li id="slide-go-prev" style="cursor: pointer;display: inline-block;" onClick="showPrevSlide();return false;"><i class="fas fa-backward"></i>&nbsp;</li>
    <li id="slide-pause" style="cursor: pointer;display: inline-block;" onClick="pauseSlideShow();return false;"><i class="fas fa-pause"></i>&nbsp;</li>
    <li id="slide-play" style="cursor: pointer;display: none;" onClick="playSlideShow();return false;"><i class="fas fa-play"></i>&nbsp;</li>
    <li id="slide-go-next" style="cursor: pointer;display: inline-block;" onClick="showNextSlide();return false;"><i class="fas fa-forward"></i>&nbsp;</li>
    <li id="slide-go-end" style="cursor: pointer;display: inline-block;" onClick="showSlidePage('end');return false;"><i class="fas fa-step-forward"></i>&nbsp;</li>
    <li id="slide-go-atpage" style="cursor: pointer;display: inline-block;">
        <span>&nbsp;<input type="number" id="slide-page-number" class="slide-page-button-style" placeholder="   JUMP"/></span>&nbsp;
    </li>
</ul>
<!-- Slideshow navigation control markup END -->

<!-- JavaScript for slideshow -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const page_input = document.getElementById("slide-page-number");

    // Event listener for focus - changes input to editable mode
    page_input.addEventListener("focus", function() {
        page_input.classList.add("input-active");
        page_input.placeholder = ""; // Clear placeholder when editing
    });

    // Event listener for blur (when focus is lost) - reverts to button appearance
    page_input.addEventListener("blur", function() {
        if (page_input.value) {
            page_input.classList.remove("input-active");
            page_input.classList.add("button-style");
            page_input.placeholder = page_input.value; // Set entered number as placeholder
        } else {
            page_input.placeholder = "   JUMP"; // Reset placeholder if no input
        }
        if (page_input.value > 0 && page_input.value <= slides.length) {
            showSlidePage(page_input.value);
            page_input.value = ""; // Reset value and placeholder after jump
            page_input.placeholder = "   JUMP"; 
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

// Initialize slideshow by displaying the first image
slides[currentSlide].classList.add('active');

// Set interval for changing slides
slideTimer = setInterval(showNextSlide, slideTransition); 
</script>

</body>
</html>
