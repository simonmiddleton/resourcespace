
const canvas = document.getElementById('canvas');
const image = document.getElementById('image');
const ctx = canvas.getContext('2d');
let drawing = false;
let penSize = document.getElementById('penSize').value;
let lastX = 0;
let lastY = 0;

image.onload = drawImageOnCanvas;

function drawImageOnCanvas() {
    canvas.width = image.width
    canvas.height = image.height;
    ctx.drawImage(image, 0, 0, image.width, image.height);
    //alert(image.width + "x" + image.height)
};

// Adjust pen size
document.getElementById('penSize').addEventListener('input', function () {
    penSize = this.value;
});

// Start drawing
canvas.addEventListener('mousedown', (e) => {
    drawing = true;
    [lastX, lastY] = getMousePos(e);
});

canvas.addEventListener('mouseup', () => drawing = false);
canvas.addEventListener('mousemove', draw);

function draw(e) {
    if (!drawing) return;

    const [x, y] = getMousePos(e);
    ctx.lineWidth = penSize;
    ctx.globalCompositeOperation = 'destination-out';
    ctx.strokeStyle = "black";
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();

    [lastX, lastY] = [x, y];
}

// Get mouse position relative to the canvas
function getMousePos(e) {
    const rect = canvas.getBoundingClientRect();
    return [
        e.clientX - rect.left,
        e.clientY - rect.top
    ];
}

// Submit canvas as mask
document.getElementById('submitBtn').addEventListener('click', async () => {

   
    const tempCanvas = document.createElement('canvas');
    const tempCtx = tempCanvas.getContext('2d');
    tempCanvas.width = 1024;
    tempCanvas.height = 1024;
    tempCtx.drawImage(canvas, 0, 0);

    const mask = tempCanvas.toDataURL('image/png');
    const prompt = document.getElementById('prompt').value;
    const originalImage = image.src;

    CentralSpaceShowProcessing();

    // Send mask and image to the backend via AJAX
    const response = await fetch(submit_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mask: mask,
            image: originalImage,
            prompt: prompt
        })
    });
    
    const result = await response.json();  // Parse the response as JSON

    // Check if a valid image URL is returned
    if (result.image_base64) {

        const base64Image = result.image_base64;  // Extract the base64 image data

        // Update the image src behind the canvas with the new one
        image.src = `data:image/png;base64,${base64Image}`;

        // Redraw the canvas with the new image once it loads
        image.onload = () => {

            // I have no idea why this is necessary or why it does anything, but without it the following drawImage() fails to draw anything.
            canvas.width = canvas.width;
            canvas.height = canvas.height;

            ctx.drawImage(image, 0, 0);  // Draw new image

            document.getElementById('downloadOptions').style.visibility='visible';
        };
    } else {
        console.error('Failed to get a valid image URL from OpenAI.');
    }
    CentralSpaceHideProcessing();
});

// Get the download button and add an event listener
document.getElementById('downloadBtn').addEventListener('click', () => {
    // Convert the canvas content to a data URL (PNG format)
    const dataURL = canvas.toDataURL(document.getElementById('downloadType').value);

    if (document.getElementById('downloadAction').value=='download')
        {
        // Download the file (or really, save, as it's already on the user's system)

        // Create a temporary link element
        const link = document.createElement('a');
        link.href = dataURL;
        link.download = 'ai_edited.' + document.getElementById('downloadType').value.split('/')[1];  // The name of the downloaded image

        // Programmatically click the link to trigger the download
        link.click();
        }

    if (document.getElementById('downloadAction').value=='alternative')
        {
        // Save as alternative file
        CentralSpaceShowProcessing();

        fetch(alternative_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                imageData: dataURL,
                imageType: document.getElementById('downloadType').value
            })
        })
        .then(response => response.json())
        .then(result => {
            console.log('Image submitted successfully:', result);
            window.location.href=view_url;
        })
        .catch(error => {
            alert('Error submitting image:' + error);
        });
        }
    


});
