<?php
if('cli' !== PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$path = get_resource_path(1, true, '');

// Copy the default slideshow image to this location, for future tests to use
copy(dirname(__FILE__) . '/../../gfx/homeanim/1.jpg', $path);

if(0 >= strlen($path))
    {
    return false;
    }


// Check actual paths/ URLs
sql_query('UPDATE resource SET file_modified = "2017-04-06 17:31:31" WHERE ref = 1');
$file_modified = '2017-04-06+17%3A31%3A31';

$original_scramble_key = $scramble_key;
$scramble_key          = 'c8cf6994c288cf9d75c64017c57d16b24a0fdb4f0b826c66bfa7da19541178e9';

// Reset cache because otherwise we will not pick up the file_modified we set for this test
$get_resource_data_cache = array();

// For most of these cases, we are expecting either a physical path/ URL to filestore,
// so set this config option to false. Enable it only when testing hiding the real file path
$hide_real_filepath = false;

// Original file path/ URL
if("{$storagedir}/1_6326bb8314c6c21/1_71a3211b5d04a88.jpg" != get_resource_path(1, true, '')
    || "{$storageurl}/1_6326bb8314c6c21/1_71a3211b5d04a88.jpg?v={$file_modified}" != get_resource_path(1, false, '')
)
    {
    echo 'Case: Original file path/ URL -- ';
    return false;
    }

// Get specific size (e.g: Preview) path/ URL
if("{$storagedir}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg" != get_resource_path(1, true, 'pre')
    || "{$storageurl}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre')
)
    {
    echo 'Case: Get specific size (e.g: Preview) path/ URL -- ';
    return false;
    }

// Check Generation of folder
if(!file_exists(get_resource_path(100000, true, '', false)))
    {
    $generated_path = get_resource_path(100000, true, '', true);
    copy(dirname(__FILE__) . '/../../gfx/homeanim/1.jpg', $generated_path);

    if(!file_exists($generated_path))
        {
        echo 'Case: Check Generation of folder -- ';
        return false;
        }
    }

// Check looking for specific preview by extension (like videos/ audio)
if("{$storagedir}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.mp4" != get_resource_path(1, true, 'pre', false, 'mp4')
    || "{$storageurl}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.mp4?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'mp4')
)
    {
    echo 'Case: Check looking for specific preview by extension (like videos/ audio) -- ';
    return false;
    }

// Check getting a scrambled version of the path/ URL
if(
    (
        "{$storagedir}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg" != get_resource_path(1, true, 'pre', false, 'jpg')
        || "{$storagedir}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg" != get_resource_path(1, true, 'pre', false, 'jpg', true)
    )
    || (
            "{$storageurl}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'jpg')
            || "{$storageurl}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'jpg', true)
        )
)
    {
    echo 'Case: Check getting a scrambled version of the path/ URL -- ';
    return false;
    }

// Check getting a non-scrambled version of the path/ URL
if("{$storagedir}/1/1pre.jpg" != get_resource_path(1, true, 'pre', false, 'jpg', false)
    || "{$storageurl}/1/1pre.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'jpg', false)
)
    {
    echo 'Case: Check getting a non-scrambled version of the path/ URL -- ';
    return false;
    }

// Check getting a page preview of a document
if("{$storagedir}/1_6326bb8314c6c21/1pre_3_0092923182f54ea.jpg" != get_resource_path(1, true, 'pre', false, 'jpg', true, 3)
    || "{$storageurl}/1_6326bb8314c6c21/1pre_3_0092923182f54ea.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'jpg', true, 3)
)
    {
    echo 'Case: Check getting a page preview of a document -- ';
    return false;
    }

// Check getting the watermarked version of a preview
if("{$storagedir}/1_6326bb8314c6c21/1pre_wm_349473228947cd0.jpg" != get_resource_path(1, true, 'pre', false, 'jpg', true, 1, true)
    || "{$storageurl}/1_6326bb8314c6c21/1pre_wm_349473228947cd0.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'jpg', true, 1, true)
)
    {
    echo 'Case: Check getting the watermarked version of a preview -- ';
    return false;
    }

// Check using the file modified of a resource (URLs only for caching purposes)
// NOTE: empty file_modified parameter has been tested in previous cases when the default value was used
if("{$storageurl}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg?v=2017-04-06+17%3A50%3A50" != get_resource_path(1, false, 'pre', false, 'jpg', true, 1, false, '2017-04-06 17:50:50'))
    {
    echo 'Case: Check using the file modified of a resource (URLs only for caching purposes) -- ';
    return false;
    }

// Check getting alternative paths/ URLs
if("{$storagedir}/1_6326bb8314c6c21/1pre_alt_10_9ef1f74b56b64ed.jpg" != get_resource_path(1, true, 'pre', false, 'jpg', true, 1, false, '', 10)
    || "{$storageurl}/1_6326bb8314c6c21/1pre_alt_10_9ef1f74b56b64ed.jpg?v={$file_modified}" != get_resource_path(1, false, 'pre', false, 'jpg', true, 1, false, '', 10)
)
    {
    echo 'Case: Check getting alternative paths/ URLs -- ';
    return false;
    }

// Check not including file_modified in URLs
// NOTE: including it has been tested previously since this parameter is set to TRUE by default
if("{$storageurl}/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg" != get_resource_path(1, false, 'pre', false, 'jpg', true, 1, false, '', 0, false))
    {
    echo 'Case: Check not including file_modified in URLs -- ';
    return false;
    }

$scramble_key = $original_scramble_key;

return true;