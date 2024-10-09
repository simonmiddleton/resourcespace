<?php
include "../../../include/boot.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Read the image from disk (ensure this path is correct)
    $imageFilePath = 'tesla.png';  // Path to your image
    $maskData = $input['mask'];    // Base64 encoded mask from the frontend
    $prompt = isset($input['prompt']) ? $input['prompt'] : '';

    // Decode the mask data from base64
    list($type, $maskData) = explode(';', $maskData);
    list(, $maskData)      = explode(',', $maskData);
    $maskData = base64_decode($maskData);

    // Prepare the OpenAI API request using multipart/form-data
    $url = 'https://api.openai.com/v1/images/edits';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $openai_gpt_api_key",
        "Content-Type: multipart/form-data"
    ]);

    // Prepare the data array using CURLFile for both image and mask
    $data = [
        'model' => 'dall-e-2',  // Specify model (if applicable)
        'image' => new CURLFile($imageFilePath, 'image/png'),
        'mask' => new CURLStringFile($maskData, 'image/png'),
        'prompt' => $prompt,
        'n' => 1,
        'size' => '1024x1024'
    ];

    // Attach the form data
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Check for errors in the cURL request
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        $json=json_decode($response,true);
        $url=$json["data"][0]["url"] ?? "";
        if ($url!="")
            {
            header('Content-Type: application/json');
            echo json_encode(["image_base64"=>base64_encode(file_get_contents($url))]);
            }
        else
            {
            echo $response;
            }
    }

    curl_close($ch);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Mask Tool</title>
    <style>
        canvas {
            border: 1px solid black;
        }
        #penSize {
            margin: 10px;
        }
    </style>
</head>
<body>
    <img id="image" src="tesla.png" alt="Your Image" hidden>
    <div class="canvas-container">
    <canvas id="canvas"></canvas>
    </div>
    <br>
    <label for="penSize">Pen Size: </label>
    <input type="range" id="penSize" min="1" max="100" value="50">
    <br>
    <button id="clearBtn">Clear</button>
    <button id="submitBtn">Submit</button>
    <br>
    <textarea id="prompt" required placeholder="Prompt for regeneration">Complete image as appropriate</textarea>
<style>
        /* Apply checkerboard pattern directly to the canvas container */
        #canvas {
            background-image:
      linear-gradient(45deg, #ccc 25%, transparent 25%), 
      linear-gradient(135deg, #ccc 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, #ccc 75%),
      linear-gradient(135deg, transparent 75%, #ccc 75%);
    background-size:25px 25px; /* Must be a square */
    background-position:0 0, 12.5px 0, 12.5px -12.5px, 0px 12.5px; /* Must be half of one side of the square */
        }
    </style>
</head>
<body>
    <script>
        const canvas = document.getElementById('canvas');
        const image = document.getElementById('image');
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let penSize = document.getElementById('penSize').value;
        let lastX = 0;
        let lastY = 0;

        // Set canvas dimensions based on the image
        image.onload = () => {
            canvas.width = image.width;
            canvas.height = image.height;
            ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
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

        // Clear the canvas
        document.getElementById('clearBtn').addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
        });

        // Submit canvas as mask
        document.getElementById('submitBtn').addEventListener('click', async () => {
            const mask = canvas.toDataURL('image/png');
            const prompt = document.getElementById('prompt').value;
            const originalImage = image.src;

            // Send mask and image to the backend via AJAX
            const response = await fetch('mask_test.php', {
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
                    canvas.width = image.width;
                    canvas.height = image.height;
                    ctx.clearRect(0, 0, canvas.width, canvas.height);  // Clear previous drawings
                    ctx.drawImage(image, 0, 0, canvas.width, canvas.height);  // Draw new image
                };
            } else {
                console.error('Failed to get a valid image URL from OpenAI.');
            }

        });
    </script>
</body>
</html>
