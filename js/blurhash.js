/* BlurHash support 
Before the thumbnail image has loaded, renders a blurred version of the image based on a short string stored with the rest of the resource data.
See: https://blurha.sh/
*/
import { decodeBlurHash } from '../node_modules/fast-blurhash/index.js';

/**
 * Convert a BlurHash string into a base64-encoded image data URL.
 *
 * @param {string} blurhash - The BlurHash string to decode.
 * @param {number} width - The width of the resulting image.
 * @param {number} height - The height of the resulting image.
 * @param {number} [punch=1] - A multiplier to enhance contrast (optional).
 * @returns {string} - A base64-encoded data URL representing the decoded image.
 */
function blurhashToDataURL(blurhash, width, height, punch = 1) {
    const pixels = decodeBlurHash(blurhash, width, height, punch);
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    const imageData = ctx.createImageData(width, height);
    imageData.data.set(pixels);
    ctx.putImageData(imageData, 0, 0);
    return canvas.toDataURL();
}

/**
 * Applies a BlurHash-based placeholder image as the background of the given div.
 *
 * @param {HTMLElement} div - A div element with a `data-blurhash` attribute, loaded from blurhash on the resource table along with the result data.
 */
function blurhashProcessImage(div) {
    var blurhash = div.dataset.blurhash;
    var placeholder = blurhashToDataURL(blurhash, 32, 32);
    div.style.backgroundImage = `url("${placeholder}")`;
}
window.blurhashProcessImage = blurhashProcessImage;
