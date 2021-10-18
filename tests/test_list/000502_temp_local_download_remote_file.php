<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


// Set up test
$src_filename = 'test_000502.jpg';
$src_fpath = get_temp_dir(false, 'test_000502') . "/{$src_filename}";
$src_url = get_temp_dir(true, 'test_000502') . "/{$src_filename}";
copy(dirname(__DIR__, 2) . '/gfx/homeanim/1.jpg', $src_fpath);


if(temp_local_download_remote_file($src_url) !== false)
    {
    echo 'Downloading from a bad URL - ';
    return false;
    }

if(temp_local_download_remote_file($src_fpath) === false)
    {
    echo 'Downloading (ie. copy) a file under temp/remote_files/ - ';
    return false;
    }


// Teardown
unlink($src_fpath);
unset($src_filename, $src_fpath, $src_url);

return true;