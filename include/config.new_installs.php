


/*

New Installation Defaults
-------------------------

The following configuration options are set for new installations only.
This provides a mechanism for enabling new features for new installations without affecting existing installations (as would occur with changes to config.default.php)

*/
                                
// Set imagemagick default for new installs to expect the newer version with the sRGB bug fixed.
$imagemagick_colorspace = "sRGB";

// No "contact us" link for new installations
$contact_link=false;

$slideshow_big=true;
$home_slideshow_width=1400;
$home_slideshow_height=900;

$homeanim_folder = 'filestore/system/slideshow';


$themes_simple_view=true;
$themes_category_split_pages=true;
$theme_category_levels=8;

$stemming=true;
$case_insensitive_username=true;

$user_pref_user_management_notifications=true;
$themes_show_background_image = true;


$use_zip_extension=true;
$collection_download=true;

$ffmpeg_preview_force = true;
$ffmpeg_preview_extension = 'mp4';
$ffmpeg_preview_options = '-f mp4 -b:v 1200k -b:a 64k -ac 1 -c:v h264 -c:a aac'; 
