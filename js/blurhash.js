/* BlurHash support */
import { decodeBlurHash } from '../node_modules/fast-blurhash/index.js';

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

function blurhashProcessImage(div) {
    var blurhash = div.dataset.blurhash;
    var placeholder = blurhashToDataURL(blurhash, 32, 32);console.log(placeholder.length);
    div.style.backgroundImage = `url("${placeholder}")`;
}
window.blurhashProcessImage = blurhashProcessImage;
