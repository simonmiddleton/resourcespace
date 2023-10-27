<?php
command_line_only();

// Set up test
$src_filename = 'test_000502.jpg';
$src_fpath = get_temp_dir(false, 'test_000502') . "/{$src_filename}";
$src_url = get_temp_dir(true, 'test_000502') . "/{$src_filename}";
copy(dirname(__DIR__, 2) . '/gfx/homeanim/1.jpg', $src_fpath);

$banned_filename = 'test_000502.php';
$banned_fpath = get_temp_dir(false, 'test_000502') . "/{$banned_filename}";
$banned_url = get_temp_dir(true, 'test_000502') . "/{$banned_filename}";
if (!file_put_contents($banned_fpath, '<?php echo "test_000502";'))
    {
    echo 'Setup: unable to write file - ';
    return false;
    }


if(temp_local_download_remote_file($banned_url) !== false)
    {
    echo 'Downloading a banned remote file - ';
    return false;
    }

if(temp_local_download_remote_file($banned_fpath) !== false)
    {
    echo 'Downloading (copy) a banned local file - ';
    return false;
    }

if(temp_local_download_remote_file($src_url) === false)
    {
    echo 'Downloading from a URL - ';
    return false;
    }

$localcopy = temp_local_download_remote_file($src_fpath);    
if($localcopy === false)
    {
    echo 'Downloading (ie. copy) a file under temp/remote_files/ - ';
    return false;
    }

// Ensure copy does not fail if called twice
$repeatcopy = temp_local_download_remote_file($localcopy);
if($repeatcopy != $localcopy)
    {
    echo 'Repeat call to temp_local_download_remote_file() failed - ';
    return false;
    }

// Teardown
unlink($localcopy);
unlink($src_fpath);
unset($src_filename, $src_fpath, $src_url);

return true;