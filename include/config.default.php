<?php
/**
 * This file contains the default configuration settings.
 * 
 * **** DO NOT ALTER THIS FILE! ****
 * 
 * If you need to change any of the below values, copy
 * them to config.php and change them there.
 * 
 * This file will be overwritten when you upgrade and
 * ensures that any new configuration options are set to
 * a sensible default value.
 * 
 * @package ResourceSpace
 * @subpackage Configuration
 * 
 * 
 * NOTE (1)
 * 
 * IMPORTANT! If any new fields are added to any of the the field arrays marked with '** SEE NOTE (1)' you should do the following:-
 * 
 * 1)  If adding as an override for e.g a resource type or usergroup, ensure you add the field to 
 *     the $data_joins array in config.php so that the columns are updated when resource data is changed
 *     by other users or scripts
 * 
 *     e.g.
 * 
 *     $data_joins[] = 8;
 * 
 *  2) Once you have made the change, ensure that you run pages/tools/fix_resource_field_column.php, passing in the relevant field
 *    identifier to populate the columns
 * 
 *    e.g.
 * 
 *    https://yoururl.com/pages/tools/fix_resource_field_column.php?field=8");
*/


/* ---------------------------------------------------
BASIC PARAMETERS
------------------------------------------------------ */

#######################################
################################ MySQL:
#######################################
$mysql_server      = 'localhost';
$mysql_server_port = 3306;
$mysql_username    = 'root';
$mysql_password    = '';
$read_only_db_username = "";
$read_only_db_password = "";
$mysql_db          = 'resourcespace';
# $mysql_charset     = 'utf8';

# The path to the MySQL client binaries - e.g. mysqldump
# (only needed if you plan to use the export tool)
# IMPORTANT: no trailing slash
$mysql_bin_path = '/usr/bin';

# Ability to record important DB transactions (e.g. INSERT, UPDATE, DELETE) in a sql file to allow replaying of changes since DB was last backed.
# You may schedule cron jobs to delete this sql log file and perform a mysqldump of the database at the same time.
# Note that there is no built in database backup, you need to take care of this yourself!
#
# WARNING!! Ensure the location defined by $mysql_log_location is not in a web accessible directory -it is advisable to either block access in the web server configuration or make the file write only by the web service account
$mysql_log_transactions = false;
# $mysql_log_location     = '/var/resourcespace_backups/sql_log.sql';

# Use prepared statements
# Default is false until technology proven
$use_mysqli_prepared = false;

# Enable establishing secure connections using SSL
# Requires setting up mysqli_ssl_server_cert and mysqli_ssl_ca_cert
$use_mysqli_ssl = false;

# $mysqli_ssl_server_cert = '/etc/ssl/certs/server.pem';
# $mysqli_ssl_ca_cert     = '/etc/ssl/certs/ca_chain.pem';
#######################################
#######################################


$baseurl="http://my.site/resourcespace"; # The 'base' web address for this installation. Note: no trailing slash
$email_from=""; # Where system e-mails appear to come from. Written to config.php by setup.php
$email_notify=""; # Where resource/research/user requests are sent. Written to config.php by setup.php
$email_notify_usergroups=array(); # Use of email_notify is deprecated as system notifications are now sent to the appropriate users based on permissions and user preferences. This variable can be set to an array of usergroup references and will take precedence.

$email_from_user=true; #enable user-to-user emails to come from user's address by default (for better reply-to), with the user-level option of reverting to the system address

# Scramble resource paths? If this is a public installation then this is a very wise idea.
# Set the scramble key to be a hard-to-guess string (similar to a password).
# To disable, set to the empty string ("").
$scramble_key="abcdef123";

# If you agree to send occasional statistics to Montala, leave this set to 'yes'.
# The following two numeric metrics alone will be sent every 7 days:
# - Number of resources
# - Number of users
# The information will only be used to provide totals on the Montala site, e.g 
# global number of installations, users and resources.
$send_statistics=true;

# Query cache time in minutes. How long before the disk cache is refreshed for a given result set. Should not be necessary to change this.
$query_cache_expires_minutes=30;

# The level of PHP error reporting to use. By default, hide warnings.
$config_error_reporting=E_ALL & ~E_WARNING & ~E_NOTICE;


# ---- Paths to various external utilities ----

# If using ImageMagick/GraphicsMagick, uncomment and set next 2 lines
# $imagemagick_path='/sw/bin';
# $ghostscript_path='/sw/bin';
$ghostscript_executable='gs';

# If using FFMpeg to generate video thumbs and previews, uncomment and set next line.
# $ffmpeg_path='/usr/bin';

# Install Exiftool and set this path to enable metadata-writing when resources are downloaded
# $exiftool_path='/usr/local/bin';

# Path to Antiword - for text extraction / indexing of Microsoft Word Document (.doc) files
# $antiword_path='/usr/bin';

# Path to pdftotext - part of the XPDF project, see http://www.foolabs.com/xpdf/
# Enables extraction of text from PDF files
# $pdftotext_path='/usr/bin';

# Path to blender
# $blender_path='/usr/bin/';

# Path to an archiver utility - uncomment and set the lines below if download of collections is enabled ($collection_download = true)
# Example given for Linux with the zip utility:
# $archiver_path = '/usr/bin';
# $archiver_executable = 'zip';
# $archiver_listfile_argument = "-@ <";

# Example given for Linux with the 7z utility:
# $archiver_path = '/usr/bin';
# $archiver_executable = '7z';
# $archiver_listfile_argument = "@";

# Example given for Windows with the 7z utility:
# $archiver_path = 'C:\Program\7-Zip';
# $archiver_executable = '7z.exe';
# $archiver_listfile_argument = "@";

$use_zip_extension=false; //use php-zip extension instead of $archiver or $zipcommand
$collection_download_tar_size = 100; // Use tar to speed up large collection downloads. Enter value in MB. Downloads above this size will default to using tar. Set value to 0 to disable tar downloads
$collection_download_tar_option=false; // Default to using tar downloads for all downloads

// Path to Python (programming language)
// $python_path = '/usr/bin';

// Path to FITS (File Information Tool Set - https://projects.iq.harvard.edu/fits)
// Make sure user has write access as it needs to write the log file (./fits.log).
// IMPORTANT: requires JAVA > 1.7
// $fits_path = '/opt/fits-1.2.0';

/* ---------------------------------------------------
OTHER PARAMETERS

The below options customise your installation. 
You do not need to review these items immediately
but may want to review them once everything is up 
and running.
------------------------------------------------------ */


# Uncomment and set next two lines to configure storage locations (to use another server for file storage)
#
# Note - these are really only useful on Windows systems where mapping filestore to a remote drive or other location is not trivial.
# On Unix based systems it's usually much easier just to make '/filestore' a symbolic link to another location.
#
#$storagedir="/path/to/filestore"; # Where to put the media files. Can be absolute (/var/www/blah/blah) or relative to the installation. Note: no trailing slash
#$storageurl="http://my.storage.server/filestore"; # Where the storagedir is available. Can be absolute (http://files.example.com) or relative to the installation. Note: no trailing slash
# If you are changing '$storagedir' in your config, please make sure '$storageurl' is also set.

# Optional folder to use for temporary file storage. 
# If using a remote filestore for resources e.g. a NAS this should be added to point to a local drive with fast disk access
# $tempdir = '/var/rstemp';

# Store original files separately from RS previews? If this setting is adjusted with resources in the system you'll need to run ../pages/tools/filestore_separation.php.
$originals_separate_storage=false;

$applicationname="ResourceSpace"; # The name of your implementation / installation (e.g. 'MyCompany Resource System')
$applicationdesc=""; # Subtitle (i18n translated) if $header_text_title=true;
$header_favicon="gfx/interface/favicon.png";

# Is the logo a link to the home page?
$header_link=true;

# Header size class. Options are HeaderSmall, HeaderMid, HeaderLarge.
$header_size="HeaderMid";

# Header includes username to right of user menu icon
$header_include_username=false;

# Custom source location for the header image (includes baseurl, requires leading "/"). Will default to the resourcespace logo if left blank. Recommended image size: 350px(X) x 80px(Y)

$linkedheaderimgsrc="";
###### END SLIM HEADER #######

# Change the Header Logo link to another address by uncommenting and setting the variable below
# $header_link_url=http://my-alternative-header-link

# Used for specifying custom colours for header 
$header_colour_style_override='';
$header_link_style_override='';

# Used for specifying custom colours for home page elements (site text, dash tiles, simple search)
$home_colour_style_override='';

# Used for specifying custom colours for collection bar elements
$collection_bar_background_override='';
$collection_bar_foreground_override='';

# Available languages
# If $defaultlanguage is not set, the brower's default language will be used instead
$defaultlanguage="en"; # default language, uses ISO 639-1 language codes ( en, es etc.)
$languages["en"]="British English";
$languages["en-US"]="American English";
$languages["ar"]="العربية";
$languages["id"]="Bahasa Indonesia"; # Indonesian
$languages["ca"]="Català"; # Catalan
$languages["zh-CN"]="简体字"; # Simplified Chinese
$languages["da"]="Dansk"; # Danish
$languages["de"]="Deutsch"; # German
$languages["el"]="Ελληνικά"; # Greek
$languages["es"]="Español"; # Spanish
$languages["es-AR"]="Español (Argentina)";
$languages["fr"]="Français"; # French
$languages["hr"]="Hrvatski"; # Croatian
$languages["it"]="Italiano"; # Italian
$languages["jp"]="日本語"; # Japanese
$languages["nl"]="Nederlands"; # Dutch
$languages["no"]="Norsk"; # Norwegian
$languages["pl"]="Polski"; # Polish
$languages["pt"]="Português"; # Portuguese
$languages["pt-BR"]="Português do Brasil"; # Brazilian Portuguese
$languages["ru"]="Русский язык"; # Russian
$languages["sk"]="Slovenčina"; # Slovak
$languages["fi"]="Suomi"; # Finnish
$languages["sv"]="Svenska"; # Swedish


# Disable language selection options (Includes Browser Detection for language)
$disable_languages=false;

# Show the language chooser on the user_home.php page
$show_language_chooser=true;

# Allow Browser Language Detection
$browser_language=true;

# Can users change passwords?
$allow_password_change=true;

# search params
# Common keywords to ignore both when searching and when indexing.
# Copy this block to config.php and uncomment the languages you would like to use.

$noadd=array();

# English stop words
$noadd=array_merge($noadd, array("", "a","the","this","then","another","is","with","in","and","where","how","on","of","to", "from", "at", "for", "-", "by", "be"));

# Swedish stop words (copied from http://snowball.tartarus.org/algorithms/swedish/stop.txt 20101124)
#$noadd=array_merge($noadd, array("och", "det", "att", "i", "en", "jag", "hon", "som", "han", "på", "den", "med", "var", "sig", "för", "så", "till", "är", "men", "ett", "om", "hade", "de", "av", "icke", "mig", "du", "henne", "då", "sin", "nu", "har", "inte", "hans", "honom", "skulle", "hennes", "där", "min", "man", "ej", "vid", "kunde", "något", "från", "ut", "när", "efter", "upp", "vi", "dem", "vara", "vad", "över", "än", "dig", "kan", "sina", "här", "ha", "mot", "alla", "under", "någon", "eller", "allt", "mycket", "sedan", "ju", "denna", "själv", "detta", "åt", "utan", "varit", "hur", "ingen", "mitt", "ni", "bli", "blev", "oss", "din", "dessa", "några", "deras", "blir", "mina", "samma", "vilken", "er", "sådan", "vår", "blivit", "dess", "inom", "mellan", "sånt", "varför", "varje", "vilka", "ditt", "vem", "vilket", "sitta", "sådana", "vart", "dina", "vars", "vårt", "våra", "ert", "era", "vilkas"));


# How many results trigger the 'suggestion' feature, -1 disables the feature
# WARNING - there is a significant performance penalty for enabling this feature as it attempts to find the most popular keywords for the entire result set.
# It is not recommended for large systems.
$suggest_threshold=-1; 


$max_results=200000;
$minyear=1980; # The year of the earliest resource record, used for the date selector on the search form. Unless you are adding existing resources to the system, probably best to set this to the current year at the time of installation.

# Set folder for home images. Ex: "gfx/homeanim/mine/" 
# Files should be numbered sequentially, and will be auto-counted.
$homeanim_folder="gfx/homeanim/gfx";

# Set different size for slideshow images (value  in pixels). This is honoured by transform plugin so still allows easy replacement of images. 	
# Can be used as config override in conjunction with $homeanim_folder as above.
# $home_slideshow_width=517;
# $home_slideshow_height=350;

# Small slideshow mode (old slideshow)
$small_slideshow = true; # This is DEPRECATED and is switched off for new installations (by config_new_installs.php)

# Big slideshow mode (Fullscreen slideshow)
# ----------------------------------
# You will need to configure much bigger slideshow images with $home_slideshow_width and $home_slideshow_height, and regenerate
# your slideshow images using the transform plugin. This is recommended to be used along with the slim header.
$slideshow_big=false;

# Number of seconds for slideshow to wait before changing image (must be greater than 1)
$slideshow_photo_delay = 5;


/** Dash Config Options **/
# Enable home dash functionality (on by default, recommended)
$home_dash = true;

# Define the available styles per type.
$tile_styles['srch']  = array('thmbs', 'multi', 'blank');
$tile_styles['ftxt']  = array('ftxt');
$tile_styles['conf']  = array('blank');
$tile_styles['fcthm'] = array('thmbs', 'multi', 'blank');

# use shadows on all tile content (Built in support for transparent tiles)
$dash_tile_shadows=false;
# All user permissions for the dash are revoked and the dash admin can manage a single dash for all users. 
# Only those with admin privileges can modify the dash and this must be done from the Team Centre > Manage all user dash tiles (One dash for all)
$managed_home_dash = false;

# Optional 'quota size' for allocation of a set amount of disk space to this application. Value is in GB (note decimal, not binary, so 1000 multiples).
# $disksize=150;


# GB of disk space left before uploads are disabled.
# This causes disk space to be checked before each upload attempt
# $disk_quota_limit_size_warning_noupload=10;

# Set your time zone below (default GMT)
if (function_exists("date_default_timezone_set")) {date_default_timezone_set("UTC");}

// Configuration used to be allow for date offset based on user local time zone. For this to work well the server (or 
// whatever MySQL uses) should be on the same timezone as PHP
$user_local_timezone = 'UTC';

# IPTC header - Character encoding auto-detection
# If using IPTC headers, specify any non-ascii characters used in your local language
# to aid with character encoding auto-detection.
# Several encodings will be attempted and if a character in this string is returned then this is considered
# a match.
# For English, there is no need to specify anything here (i.e. just an empty string) - this will disable auto-detection and assume UTF-8
# The example below is for Norwegian.
# $iptc_expectedchars="æøåÆØÅ";
$iptc_expectedchars="";

# Which field do we drop the EXIF data in to? (when NOT using exiftool)
# Comment out these lines to disable basic EXIF reading.
# See exiftool for more advanced EXIF/IPTC/XMP extraction.
$exif_comment=18;
$exif_model=52;
$exif_date=12;

# If exiftool is installed, you can optionally enable the metadata report available on the View page. 
# You may want to enable it on the usergroup level by overriding this config option in System Setup.
$metadata_report=false;

# Use Exiftool to attempt to extract specified resolution and unit information from files (ex. Adobe files) upon upload.
$exiftool_resolution_calc=false;

# Set to true to strip out existing EXIF,IPTC,XMP metadata when adding metadata to resources using exiftool.
$exiftool_remove_existing=false; 

# If Exiftool path is set, write metadata to files upon download if possible.
$exiftool_write=true;
# Omit conversion to utf8 when exiftool writes (this happens when $mysql_charset is not set, or $mysql_charset!="utf8")
$exiftool_write_omit_utf8_conversion=false;

/*
These two options allow the user to choose whether they want to write metadata on downloaded files.

$force_exiftool_write_metadata should be used by system admins to force writing or not writing metadata on a file on download
$exiftool_write_option will be used on both resource and collection download. On collection download, an extra option (check box)
will be available so the user can specify whether they want to write metadata on the downloaded files
example use:
$force_exiftool_write_metadata = false; $exiftool_write_option = true; means ResourceSpace will write to the files
$force_exiftool_write_metadata = true; $exiftool_write_option = false; means ResourceSpace will force users to not write metadata to the files

Note: this honours $exiftool_write so if that option is false, this will not work
*/
$force_exiftool_write_metadata = false;
$exiftool_write_option         = false;

#Option to strip tags from rich fields when downloading metadata, by default is FALSE (keeping the tags added by CKEDITOR)
$strip_rich_field_tags = false;

# Set metadata_read to false to omit the option to extract metadata.
$metadata_read=true;

# If metadata_read is true, set whether the default setting on the edit/upload page is to extract metadata (true means the metadata will be extracted)
$metadata_read_default=true;

# If Exiftool path is set, do NOT send files with the following extensions to exiftool for processing. Updated to include common video formats as this can cause slowdowns when multiple downloads are in progress
# For example: $exiftool_no_process=array("eps","png");
$exiftool_no_process=array('aaf',
    '3gp',
    'asf',
    'avchd',
    'avi',
    'cam',
    'dat',
    'dsh',
    'flv',
    'm1v',
    'm2v',
    'mkv',
    'wrap',
    'mov',
    'mpeg',
    'mpg',
    'mpe',
    'mp4',
    'mxf',
    'nsv',
    'ogm',
    'ogv',
    'rm',
    'ram',
    'svi',
    'smi',
    'webm',
    'wmv',
    'divx',
    'xvid',
    'm4v');

/*
ExifTool global options - these get applied to any exiftool command run. For more information on options please see
https://sno.phy.queensu.ca/~phil/exiftool/exiftool_pod.html#Advanced-options

Example use cases:
$exiftool_global_options = "-config '/var/www/test.Exiftool_config'"; # @see https://sno.phy.queensu.ca/~phil/exiftool/config.html
$exiftool_global_options = "-x EXIF:CreateDate"; # exclude tag
*/
$exiftool_global_options = "";

# Which field do we drop the original filename in to?
$filename_field=51;

# If using imagemagick, should colour profiles be preserved? (for larger sizes only - above 'scr')
$imagemagick_preserve_profiles=false;
$imagemagick_quality=90; # JPEG quality (0=worst quality/lowest filesize, 100=best quality/highest filesize)

# Preset quality settings. Used by transform plugin to allow user to select desired from a range of preset quality setting.
# If adding extra quality settings, an accompanying $lang setting must be set e.g. in a plugin language file or using site text (Manage content)
# e.g. $lang['image_quality_10'] = "";
$image_quality_presets = array(100,92,80,50,40);

# Allow editing of internal sizes? This will require additional updates to css settings!
$internal_preview_sizes_editable=false;

# Colorspace usage
# Use "RGB" for ImageMagick versions before 6.7.6-4
# Use "RGB" for GraphicsMagick
# Use "sRGB" for ImageMagick version 6.7.6-4 and newer
$imagemagick_colorspace="RGB";

# Default color profile
# This is going to be used for all rendered files (or just thumbnails if $imagemagick_preserve_profiles
# is set
#$default_icc_file='my-profile.icc';

# To use the Ghostscript command -dUseCIEColor or not (generally true but added in some cases where scripts might want to turn it off).
$dUseCIEColor=true;

# Some files can take a long time to preview, or take too long (PSD) or involve too many sofware dependencies (RAW). 
# If this is a problem, these options allow EXIFTOOL to attempt to grab a preview embedded in the file.
# (Files must be saved with Previews). If a preview image can't be extracted, RS will revert to ImageMagick.
$photoshop_thumb_extract=false;
$cr2_thumb_extract=false; 
$nef_thumb_extract=false;
$dng_thumb_extract=false;
$rw2_thumb_extract=true;
$raf_thumb_extract=false;
$arw_thumb_extract = false;

# Turn on creation of a miff file for Photoshop EPS.
# Off by default because it is 4x slower than just ripping with ghostscript, and bloats filestore.
$photoshop_eps_miff=false;

# Attempt to resolve a height and width of the ImageMagick file formats at view time
# (enabling may cause a slowdown on viewing resources when large files are used)
$imagemagick_calculate_sizes=false;

# If using imagemagick for PDF, EPS and PS files, up to how many pages should be extracted for the previews?
# If this is set to more than one the user will be able to page through the PDF file.
$pdf_pages=30;

# When uploading PDF files, split each page to a separate resource file?
$pdf_split_pages_to_resources=false;

/*
* Video Resolution selection: ability to use the original playback file and any files created via $ffmpeg_alternatives for resolution selection options on the view page.
* Since $video_view_play_hover hides the control bar its use will override the use of resolution selection.
*
* "label": the resolution identifier as it should appear in the selection list.
* "name": accepts names set in $ffmpeg_alternatives[]["name"]. For the main playback file leave empty.
*
* Example: The settings below use the original playback file for 'HD' playback and the $ffmpeg_alternatives file with name "standard"
$videojs_resolution_selection[0]["label"]="HD";
$videojs_resolution_selection[0]["name"]="";// 
$videojs_resolution_selection[1]["label"]="SD";
$videojs_resolution_selection[1]["name"]="standard";
*/

# The default resolution when using resolution selection. Must use the same
# Use label from preferred $ffmpeg_hls_streams entry or value as one of the "label" settings from $videojs_resolution_selection. This will be ignored (i.e. set to 'Auto') if $video_preview_hls_support is enabled.
$videojs_resolution_selection_default_res='HD';

# Create a standard preview video for ffmpeg compatible files? 
/* Examples of preview options to convert to different types (don't forget to set the extension as well):
* MP4: $ffmpeg_preview_options = '-f mp4 -ar 22050 -b 650k -ab 32k -ac 1';
*/
$ffmpeg_preview=true; 
$ffmpeg_preview_seconds=120; # how many seconds to preview
$ffmpeg_preview_extension="flv";
$ffmpeg_preview_min_width=32;
$ffmpeg_preview_min_height=18;
$ffmpeg_preview_max_width=700;
$ffmpeg_preview_max_height=394;
$ffmpeg_preview_options="-f flv -ar 22050 -b:v 650k -ab 32k -ac 1 -strict -2";

# HLS SUPPORT
# Option to generate HTTP Live Streaming (HLS) compatible previews separate sections with an m3u8 playlist to support adaptive bitrate streaming). 
# Requires $videojs to be enabled
# Please test if compatible with your target browsers before enabling. A sample video is at https://videojs.github.io/videojs-contrib-hls/ 
# 0 = no HLS support
# 1 = Create HLS previews
# 2 = Create both HLS and standard preview for incompatible clients to use (this option will take up more disk space)
$video_preview_hls_support=0; 
$video_preview_player_hls=0; 
$video_hls_preview_options=" -ar 22050 -ac 1 -hls_list_size 0 -hls_time 5 ";

# Set the following to configure the HLS preview streams (variants) that will be generated.
# There is a lot of online advice on recommended variants and these should be adjusted depending on your expected client capabilities
# Be aware when adding extra variants that these will take up more disk space
# 
# Array keys as follows:-
# 'label' - How the stream will appear if the resolution switcher is enabled
# 'id' - unique code for the stream  - keep this lower case with only letters as per preview size codes
# 'resolution'  - desired resolution as WIDTHxHEIGHT e.g. 320x180. Leave as '' to match resolution of resource 
# 'bitrate'  - desired video bitrate in kb/s of stream
# 'audio_bitrate'  - desired audio bitrate in kb/s of stream

$video_hls_streams[0]["label"]="Low";
$video_hls_streams[0]["id"]="lo";
$video_hls_streams[0]["resolution"]="320x180";
$video_hls_streams[0]["bitrate"]="140";
$video_hls_streams[0]["audio_bitrate"]="32";

$video_hls_streams[1]["label"]="SD";
$video_hls_streams[1]["id"]="sd";
$video_hls_streams[1]["resolution"]="768x432";
$video_hls_streams[1]["bitrate"]="1200";
$video_hls_streams[1]["audio_bitrate"]="128";

$video_hls_streams[2]["label"]="HQ";
$video_hls_streams[2]["id"]="hi";
$video_hls_streams[2]["resolution"]="";
$video_hls_streams[2]["bitrate"]="2000";
$video_hls_streams[2]["audio_bitrate"]="256";

# ffmpeg_global_options: options to be applied to every ffmpeg command. 
#$ffmpeg_global_options = "-loglevel panic"; # can be used for recent versions of ffmpeg when verbose output prevents run_command completing
#$ffmpeg_global_options = "-v panic"; # use for older versions of ffmpeg  as above
$ffmpeg_global_options = "";
#$ffmpeg_snapshot_fraction=0.1; # Set this to specify a point in the video at which snapshot image is taken. Expressed as a proportion of the video duration so must be set between 0 and 1. Only valid if duration is greater than 10 seconds.
#$ffmpeg_snapshot_seconds=10;  # Set this to specify the number of seconds into the video at which snapshot should be taken, overrides the $ffmpeg_snapshot_fraction setting

/*
Make video previews have multiple snapshots from the video.
Hovering over a search result thumbnail preview, will show the user frames from the video in order for 
the user to get an idea of what the video is about

Note: Set to 0 to disable this feature
*/
$ffmpeg_snapshot_frames = 20;

# $ffmpeg_command_prefix - Ability to add prefix to command when calling ffmpeg 
# Example for use on Linux using nice to avoid slowing down the server
# $ffmpeg_command_prefix = "nice -n 10";

# If uploaded file is in the preview format already, should we transcode it anyway?
# Note this is now ON by default as of switching to MP4 previews, because it's likely that uploaded MP4 files will need a lower bitrate preview and
# were not intended to be the actual preview themselves.
$ffmpeg_preview_force = true;

# Option to always try and play the original file instead of preview - useful if recent change to $ffmpeg_preview_force doesn't suit e.g. if all users are
# on internal network and want to see HQ video
$video_preview_original=false;

# Encode preview asynchronous?
# REQUIRES: $php_path
$ffmpeg_preview_async=false;

# Find out and obey the Pixel Aspect Ratio
$ffmpeg_get_par=false;

# Use New qscale to maintain quality (else uses -sameq)
$ffmpeg_use_qscale = true;

# FFMPEG - generation of alternative video file sizes/formats
# It is possible to automatically generate different file sizes and have them attached as alternative files.
# See below for examples.
# The blocks must be numbered sequentially (0, 1, 2).
# Ensure the formats you are specifiying with vcodec and acodec are supported by checking 'ffmpeg -formats'.
# "lines_min" refers to the minimum number of lines (vertical pixels / height) needed in the source file before this alternative video file will be created. It prevents the creation of alternative files that are larger than the source in the event that alternative files are being used for creating downscaled copies (e.g. for web use).
#
# Params examples for different cases:
# Converting .mov to .avi use "-g 60 -vcodec msmpeg4v2 -acodec pcm_u8 -f avi";
#
# $ffmpeg_alternatives[0]["name"]="QuickTime H.264 WVGA";
# $ffmpeg_alternatives[0]["filename"]="quicktime_h264";
# $ffmpeg_alternatives[0]["extension"]="mov";
# $ffmpeg_alternatives[0]["params"]="-vcodec h264 -s wvga -aspect 16:9 -b 2500k -deinterlace -ab 160k -acodec mp3 -ac 2";
# $ffmpeg_alternatives[0]["lines_min"]=480;
# $ffmpeg_alternatives[0]["alt_type"]='mywebversion';

# $ffmpeg_alternatives[1]["name"]="Larger FLV";
# $ffmpeg_alternatives[1]["filename"]="flash";
# $ffmpeg_alternatives[1]["extension"]="FLV";
# $ffmpeg_alternatives[1]["params"]="-s wvga -aspect 16:9 -b 2500k -deinterlace -ab 160k -acodec mp3 -ac 2";
# $ffmpeg_alternatives[1]["lines_min"]=480;
# $ffmpeg_alternatives[1]["alt_type"]='mywebversion';

# when using $originals_separate_storage=true, store $ffmpeg_alternatives with previews?
$originals_separate_storage_ffmpegalts_as_previews=false;

# To be able to run certain actions asyncronus (eg. preview transcoding), define the path to php:
# $php_path="/usr/bin";

# Use qt-faststart to make mp4 previews start faster
# $qtfaststart_path="/usr/bin";
# $qtfaststart_extensions=array("mp4","m4v","mov");

# Allow users to request accounts?
$allow_account_request=true;

# Should the system allow users to request new passwords via the login screen?
$allow_password_reset=true;

# Search on day in addition to month/year?
$searchbyday=false;

# Allow download of original file for resources with "Restricted" access.
# For the tailor made preview sizes / downloads, this value is set per preview size in the system setup.
$restricted_full_download=false;

# Instead of showing a download size as "Restricted", the download size is hidden - ONLY IF the user has not got the ability to request ("q" permission).
$hide_restricted_download_sizes=false;

# Also search the archive and display a count with every search? (performance penalty)
$archive_search=false;

# Display the Research Request functionality?
# Allows users to request resources via a form, which is e-mailed.
$research_request=false;

# custom research request fields
# see https://www.resourcespace.com/knowledge-base/resourceadmin/user-research-requests
$custom_researchrequest_fields = array();


# Country search in the right nav? (requires a field with the short name 'country')
$country_search=false;

# Resource ID search blank in right nav? (probably only needed if $config_search_for_number is set to true) 
$resourceid_simple_search=false;

# Enable date option on simple search bar
$simple_search_date=true;

# Enable sorting resources in other ways:
$colour_sort=true;
$popularity_sort=true;

# What is the default sort order?
# Options are date, colour, relevance, popularity, country
$default_sort="relevance";

# What is the default sort order when viewing collection resources?
# Options are date, colour, collection, popularity, country, resourcetype
# Note: when users are expecting resources to be shown in the order they provided, make sure this is set to 'collection'
$default_collection_sort = 'collection';

# Enable themes (promoted collections intended for showcasing selected resources)
$enable_themes=true;

# Use the themes page as the home page?
$use_theme_as_home=false;

# Use the recent page as the home page?
$use_recent_as_home=false;


# Show images along with theme category headers (image selected is the most popular within the theme category)
$theme_images=true;
$theme_images_number = 6; # How many to auto-select (if none chosen manually). Smart FCs only display one.
$theme_images_align_right=false; # Align theme images to the right on the themes page? (particularly useful when there are multiple theme images)
$show_theme_collection_stats=false; # Show count of themes and resources in theme category

# Theme direct jump mode
# If set, sub category levels DO NOT appear and must be directly linked to using custom home panels or top navigation items (or similar).
$theme_direct_jump=false;

#Force Collections lists on the Themes page to be in Descending order.
$descthemesorder=false;

##  Advanced Search Options
##  Defaults (all false) shows advanced search in the search bar but not the home page or top navigation.
##  To disable advanced search altogether, set 
##      $advancedsearch_disabled = true;
##      $advanced_search_nav=false;

#Hide advanced search on search bar
$advancedsearch_disabled = false;

# Display the advanced search as a link in the top navigation
$advanced_search_nav = false;

# Show Contributed by on Advanced Search (ability to search for resources contributed by a specific user)
$advanced_search_contributed_by = true;

# Show Media section on Advanced Search
$advanced_search_media_section = true;

# Display a 'Recent' link in the top navigation
$recent_link=true;
# Display 'View New Material' link in the quick search bar (same as 'Recent')
$view_new_material=false;
# For recent_link and view_new_material, and use_recent_as_home, the quantity of resources to return.
$recent_search_quantity=1000;

# Display Help and Advice link in the top navigation
$help_link=true;

# Display Search Results link in top navigation
$search_results_link=true;

# Display a 'My Collections' link in the top navigation
# Note that permission 'b' is needed for collection_manage.php to be displayed
$mycollections_link=false;

# Display a 'My Requests' link in the top navigation
$myrequests_link=false;

# Display a 'Research Request' link in the top navigation
$research_link=true;

# Display a Themes link in Top Navigation if Themes is enabled
$themes_navlink = true;

# Display a 'My Contributions' link in the top navigation for admin (permission C)
$mycontributions_link = false;

# Require terms for download?
$terms_download=false;

# Require terms on first login?
$terms_login=false;

##  Thumbnails options

# In the collection frame, show or hide thumbnails by default? ("hide" is better if collections are not going to be heavily used).
$thumbs_default="show";
#  Automatically show thumbs when you change collection (only if default is show)
$autoshow_thumbs = false;

# Show an Empty Collection link which will empty the collection of resources (not delete them)
$emptycollection = false;

# Options for number of results to display per page:
$results_display_array=array(24,48,72,120,240);
# How many results per page? (default)
$default_perpage=48;
# Options for number of results to display for lists (user admin, public collections, manage collections)
$list_display_array=array(15,30,60);
# How many results per page? (default)
$default_perpage_list=15;


# Group based upload folders? (separate local upload folders for each group)
$groupuploadfolders=false;
# Username based upload folders? (separate local upload folders for each user based on username)
$useruploadfolders=false;

# Enable order by rating? (require rating field updating to rating column)
$orderbyrating=false;

# Zip command to use to create zip archive (uncomment to enable download of collections as a zip file)
# $zipcommand =
# This setting is deprecated and replaced by $collection_download and $collection_download_settings.

# Set $collection_download to true to enable download of collections as archives (e.g. zip files).
# The setting below overrides - if true - the $zipcommand.
# You also have to uncomment and set $collection_download_settings for it to work.
# (And don't forget to set $archiver_path etc. in the path section.)
$collection_download = false;

# The total size, in bytes, of the collection download possible PRIOR to zipping. Prevents users attempting very large downloads.
$collection_download_max_size = 1024 * 1024 * 1024; # default 1GB.

# Example given for Linux with the zip utility:
# $collection_download_settings[0]["name"] = 'ZIP';
# $collection_download_settings[0]["extension"] = 'zip';
# $collection_download_settings[0]["arguments"] = '-j';
# $collection_download_settings[0]["mime"] = 'application/zip';

# Example given for Linux with the 7z utility:
# $collection_download_settings[0]["name"] = 'ZIP';
# $collection_download_settings[0]["extension"] = 'zip';
# $collection_download_settings[0]["arguments"] = 'a -tzip';
# $collection_download_settings[0]["mime"] = 'application/zip';
# $collection_download_settings[1]["name"] = '7Z';
# $collection_download_settings[1]["extension"] = '7z';
# $collection_download_settings[1]["arguments"] = 'a -t7z';
# $collection_download_settings[1]["mime"] = 'application/x-7z-compressed';

# Example given for Linux with tar (saves time if large compressed resources):
# $collection_download_settings[0]["name"] = 'tar file';
# $collection_download_settings[0]["extension"] = 'tar';
# $collection_download_settings[0]["arguments"] = '-cf ';
# $collection_download_settings[0]["mime"] = 'application/tar';
# $archiver_path = '/bin';
# $archiver_executable = 'tar';
# $archiver_listfile_argument = " -T ";


# Example given for Windows with the 7z utility:
# $collection_download_settings[0]["name"] = 'ZIP';
# $collection_download_settings[0]["extension"] = 'zip';
# $collection_download_settings[0]["arguments"] = 'a -scsWIN -tzip';
# $collection_download_settings[0]["mime"] = 'application/zip';
# ...

# Option to write a text file into zipped collections containing resource data
$zipped_collection_textfile=false;
# Set default option for text file download to "no"
$zipped_collection_textfile_default_no=false;

# Enable speed tagging feature? (development)
$speedtagging=false;
$speedtaggingfield=1;
# To set speed tagging field by resource type, you can set $speedtagging_by_type[resource_type]=resource_type_field; 
# default will be $speedtaggingfield
# example to add speed tags for Photo type(1) to the Caption(18) field:
# $speedtagging_by_type[1]=18; 


# A list of types which get the extra video icon in the search results
$videotypes=array(3);

# Small icon above thumbnails showing the resource type
$resource_type_icons=false;
# Map the resource type to a font awesome 4 icon
$resource_type_icons_mapping = array(1 => "camera", 2 => "file", 3 => "video-camera", 4 => "music");


/** USER PREFERENCES **/
$user_preferences = true;

/* Should the "purge users" function be available? */
$user_purge=true;

# List of active plugins.
# Note that multiple plugins must be specified within array() as follows:
# $plugins=array("loader","rss","messaging","googledisplay"); 
$plugins = array('transform', 'rse_version', 'lightbox_preview', 'rse_search_notifications', 'rse_workflow', 'licensemanager');

# Optional list of plugins that cannot be enabled through the UI. Can be useful to lock down system for hosting situations
$disabled_plugins=array();

# Uncomment and set the next line to allow anonymous access. 
# You must set this to the USERNAME of the USER who will represent all your anonymous users
# Note that collections will be shared among all anonymous users - it's therefore usually best to turn off all collections functionality for the anonymous user.
#$anonymous_login="guest";

# Domain Linked Anonymous Access
# Uncomment and set to allow different anonymous access USERS for different domains. 
# The usernames are the same rules for just a single anonymous account but you must match them against the full domain $Baseurl that they will be using.
# Note that collections will be shared among all anonymous users for each domain - it's therefore usually best to turn off all collections functionality for the anonymous user.
/* $anonymous_login = array(
		"http://example.com" => "guest",
		"http://test.com" => "guest2"
		); */

# Alternative anonymous login mode. Automatically create a separate user for each anonymous session and log them in.
# EXPERIMENTAL - use with caution!
# $anonymous_autouser_group=2;

$anonymous_user_session_collection=true;



# Enable collection commenting and ranking
$collection_commenting = false;

# Footer text applied to all e-mails (blank by default)
$email_footer="";

# Contact Sheet feature, and whether contact sheet becomes resource.
# Requires ImageMagick/Ghostscript.
$contact_sheet=true;
# Produce a separate resource file when creating contact sheets?
$contact_sheet_resource=false;
# ID of resource type to create
$contact_sheet_resource_type=1; 
 
# Ajax previews in contact sheet configuration. 
$contact_sheet_previews=true;
# Ajax previews in contact sheet, preview image size in pixels. 
$contact_sheet_preview_size="500x500";
# Select a contact sheet font. Default choices are 
# helvetica,times,courier (standard) and dejavusanscondensed for more Unicode support (but embedding/subsetting makes it slower).
# There are also several other fonts included in the tcpdf lib (but not ResourceSpace), which provide unicode support
# To embed more elaborate fonts, acquire the files from the TCPDF distribution or create your own using TCPDF utilities, and install them in the lib/html2pdf/vendor/tecnickcom/tcpdf/fonts folder.
# If you encounter issues with chinese characters, use "arialunicid0" and make sure GhosScript has ArialUnicodeMS font (on Windows server, this should be there already)
$contact_sheet_font="helvetica";
# allow unicode filenames? (stripped out by default in tcpdf but since collection names may 
# have special characters, probably want to try this on.)
$contact_sheet_unicode_filenames=true;
# Set font sizes for contactsheet
$titlefontsize     = 20; // Contact Sheet Title
$refnumberfontsize = 14; // This includes field text, not just ref number
# If making a contact sheet with list sheet style, use these fields in contact sheet:
$config_sheetlist_fields = array(8); # ** SEE NOTE (1)
$config_sheetlist_include_ref=true;
# If making a contact sheet with thumbnail sheet style, use these fields in contact sheet:
$config_sheetthumb_fields = array(); # ** SEE NOTE (1)
$config_sheetthumb_include_ref=true;
# If making a contact sheet with one resource per page sheet style, use these fields in contact sheet:
$config_sheetsingle_fields = array(8); # ** SEE NOTE (1)
$config_sheetsingle_include_ref=true;

# Use templates rather than setting contactsheet fields by display style?
$contactsheet_use_field_templates=false;
# If $contactsheet_use_field_templates=true uncomment and set:
# 'name' is the displayed name of the template
# 'fields' is an array of fields to use. Fields will be displayed in setting order.
#$contactsheet_field_template[0]['name']='Title only';
#$contactsheet_field_template[0]['fields']=array(8);
#$contactsheet_field_template[0]['name']='Title & Filename';
#$contactsheet_field_template[0]['fields']=array(8,51);

# experimental sorting (doesn't include ASC/DESC yet).
$contactsheet_sorting=false;

# Add header text to contact page?
$contact_sheet_include_header=true;
# Give user option to add header text to contact page?
$contact_sheet_include_header_option=false;
# Show the application name in the header?
$contact_sheet_include_applicationname=true;

# Add logo image to contact page? set contact_sheet_logo if set to true
$include_contactsheet_logo=false;
#$contact_sheet_logo="gfx/contactsheetheader.png"; // can be a png/gif/jpg or PDF file

# if $contact_sheet_logo_resize==false, the image is sized at 300ppi or the PDF retains it's original dimensions.
# if true, the logo is scaled to a hardcoded percentage of the page size.
$contact_sheet_logo_resize=true; 

# Give user option to add/remove logo?
#$contact_sheet_logo_option=true;

# Show contact sheet footer (old $contact_sheet_custom_footerhtml removed as this is now handled in templates and enabled by either showing/ hiding the footer)
$contact_sheet_footer = false;

# Make images in contactsheet links to the resource view page?
$contact_sheet_add_link=true;
# Give user option to enable links?
$contact_sheet_add_link_option=false;
# Give user option to show field name in front of field data
$contact_sheet_field_name_option=false;
# Bold the field name (if shown)?
$contact_sheet_field_name_bold=false;
# Use watermarked previews for contact sheets? If set to 'true' watermarks will be forced rather than judged based on user credentials.
$contact_sheet_force_watermarks=false;
# Give user option to force watermarks?
$contact_sheet_force_watermark_option=false;
# Contactsheet include time with dates?
$contact_sheet_date_include_time=true;
# Contactsheet wordy dates?
$contact_sheet_date_wordy=true;

# Show contact sheet metadata under preview? For thumbnail view only
$contact_sheet_metadata_under_thumbnail=false;

$contact_sheet_single_select_size=false;

# Set this to FALSE in order to remove the link from the collection bar
$contact_sheet_link_on_collection_bar = true;

##  Contact Print settings - paper size options
# To add a custom size, simply add a new line with the size as the value attribute in "<WIDTH>x<HEIGHT>" format. Sizes in mm.
# e.g.
# <option value="216x343">Foolscap</option>
$papersize_select = '
<option value="a4">A4 - 210mm x 297mm</option>
<option value="a3">A3 - 297mm x 420mm</option>
<option value="letter">US Letter - 8.5" x 11"</option>
<option value="legal">US Legal - 8.5" x 14"</option>
<option value="tabloid">US Tabloid - 11" x 17"</option>';

#Optional array to set customised title and margins for named templates
# e.g.
# $contact_sheet_custom_size_settings = array('label'=>array("title"=>"ResourceSpace default label title","margins"=>"0,0,0,0"));

## Columns options (May want to limit options if you are adding text fields to the Thumbnail style contact sheet).
$columns_select = '
<option value=2>2</option>
<option value=3>3</option>
<option value=4 selected>4</option>
<option value=5>5</option>
<option value=6>6</option>
<option value=7>7</option>';

# Show related themes and public collections panel on Resource View page.
$show_related_themes=true;

# Watermarking - generate watermark images for 'internal' (thumb/preview) images.
# Groups with the 'w' permission will see these watermarks when access is 'restricted'.
# Uncomment and set to the location of a watermark graphic.
# NOTE: only available when ImageMagick is installed.
# NOTE: if set, you must be sure watermarks are generated for all images; This can be done using pages/tools/update_previews.php?previewbased=true
# NOTE: also, if set, restricted external emails will recieve watermarked versions. Restricted mails inherit the permissions of the sender, but
# if watermarks are enabled, we must assume restricted access requires the equivalent of the "w" permission
# $watermark="gfx/watermark.png";

# Set to true to watermark thumb/preview for groups with the 'w' permission even when access is 'open'.
# This makes sense if $terms_download is active.
$watermark_open=false;

# Set to true to extend $watermark_open to the search page. $watermark_open must be set to true.
$watermark_open_search=false; 

# Simple search even more simple
# Set to 'true' to make the simple search bar more basic, with just the single search box.
$basic_simple_search=false;

# include an "all" toggle checkbox for Resource Types in Search bar
$searchbar_selectall=false;

# Hide the main simple search field in the searchbar (if using only simple search fields for the searchbar)
$hide_main_simple_search=false;

/*Display keywords as pills on Simple Search. Use tab to create new tags/ pills
Note: full text strings are also accepted as a pill*/
$simple_search_pills_view = false;

# Custom top navigation links.
# You can add as many panels as you like. They must be numbered sequentially starting from zero (0,1,2,3 etc.)
# URL should be absolute, or include $baseurl as below, because a relative URL will not work from the Team Center.
# Since configuration is prior to $lang availability, use a special syntax prefixing the string "(lang)" to access $lang['mytitle']:
# ex:
# $custom_top_nav[0]["title"]="(lang)mytitle";

# $custom_top_nav[0]["title"]="Example Link A";
# $custom_top_nav[0]["link"]="$baseurl/pages/search.php?search=a";
# $custom_top_nav[0]['modal']=false;
#
# $custom_top_nav[1]["title"]="Example Link B";
# $custom_top_nav[1]["link"]="$baseurl/pages/search.php?search=b";
# $custom_top_nav[1]['modal']=true;


# Use original filename when downloading a file?
$original_filenames_when_downloading=true;

# Should the download filename have the size appended to it?
$download_filenames_without_size = false;

# When $original_filenames_when_downloading, should the original filename be prefixed with the resource ID?
# This ensures unique filenames when downloading multiple files.
# WARNING: if switching this off, be aware that when downloading a collection as a zip file, a file with the same name as another file in the collection will overwrite that existing file. It is therefore advisiable to leave this set to 'true'.
$prefix_resource_id_to_filename=true;

# When using $prefix_resource_id_to_filename above, what string should be used prior to the resource ID?
# This is useful to establish that a resource was downloaded from ResourceSpace and that the following number
# is a ResourceSpace resource ID.
$prefix_filename_string="RS";

# Display a 'new' flag next to new themes (by default themes created < 2 weeks ago)
# Note: the age take days as parameter. Anything less than that would mean that a theme becomes old after a few hours which is highly unlikely.
$flag_new_themes     = true;
$flag_new_themes_age = 14;

# Create file checksums?
$file_checksums=false;

# Calculate checksums on first 50k and size if true or on the full file if false
$file_checksums_50k = true;

# Block duplicate files based on checksums? (has performance impact). May not work reliably with $file_checksums_offline=true unless checksum script is run frequently. 
$file_upload_block_duplicates=false;

# checksums will not be generated in realtime; a background cron job must be used
# recommended if files are large, since the checksums can take time
$file_checksums_offline = true;

# $file_integrity_verify_window - set server time window that the file integrity check script can run in.
# This can be resource intensive when checking checksums for a large number of resources.
# Examples: -
# $file_integrity_verify_window = array(22,6);	# # between 10PM and 6AM (first hour is later than second so time must be after first OR before second)
# $file_integrity_verify_window = array(18,0);  # between 6PM and 12AM (midnight)
$file_integrity_verify_window = array(0,0);     # Off by default

# Default group when adding new users;
$default_group=2;

# Enable 'custom' access level?
# Allows fine-grained control over access to resources.
# You may wish to disable this if you are using metadata based access control (search filter on the user group)
$custom_access=true;



# How are numeric searches handled?
#
# If true:
# 		If the search keyword is numeric then the resource with the matching ID will be shown
# If false:
#		The search for the number provided will be performed as with any keyword. However, if a resource with a matching ID number if found then this will be shown first.
$config_search_for_number=false;

# Display the download as a 'save as' link instead of redirecting the browser to the download (which sometimes causes a security warning).
$save_as=false;

# Allow resources to be e-mailed / shared (internally and externally)
$allow_share=true;

# Use a custom stylesheet when sharing externally.
# Note: $custom_stylesheet_external_share_path can be set anywhere inside websites' root folder.
# eg.: '/plugins/your plugin name/css/external_shares.css'
$custom_stylesheet_external_share = false;
$custom_stylesheet_external_share_path = '';

# Hide display of internal URLs when sharing collections. Intended to prevent inadvertently sending external users invalid URLs
$hide_internal_sharing_url=false;

# Allow theme names to be batch edited in the Themes page.
$enable_theme_category_edit=true;

# Should those with 'restricted' access to a resource be able to share the resource?
$restricted_share=false;

# Should those that have been granted open access to an otherwise restricted resource be able to share the resource?
$allow_custom_access_share=false;

# Should a user that has contributed a resource always have open access to it?
$open_access_for_contributor=false;

# Should a user that has contributed a resource always have edit access to it? (even if the resource is live)
$edit_access_for_contributor=false;

# For users that have edit access to main states (e.g Active), make sure edit access is granted only for resources contributed by that user
$edit_only_own_contributions = false;

# Auto-completion of search (quick search only)
$autocomplete_search=true;
$autocomplete_search_items=15;
$autocomplete_search_min_hitcount=10; # The minimum number of times a keyword appears in metadata before it qualifies for inclusion in auto-complete. Helps to hide spurious values.

# Automatically order checkbox lists (alphabetically)
$auto_order_checkbox=true;

# Use a case insensitive sort when automatically order checkbox lists (alphabetically)
$auto_order_checkbox_case_insensitive=false;

# Order checkbox lists vertically (as opposed to horizontally, as HTML tables normally work)
$checkbox_ordered_vertically=true;

# When batch uploading, show the 'add resources to collection' selection box
$enable_add_collection_on_upload=true;

# Batch Uploads, default is "Add to New Collection". Turn off to default to "Do not Add to Collection"
$upload_add_to_new_collection=true;
# Batch Uploads, enables the "Add to New Collection" option.
$upload_add_to_new_collection_opt=true;
# Batch Uploads, enables the "Do Not Add to New Collection" option, set to false to force upload to a collection.
$upload_do_not_add_to_new_collection_opt=true;
# Batch Uploads, require that a collection name is entered, to override the Upload<timestamp> default behavior
$upload_collection_name_required=false;

# When batch uploading, enable the 'copy resource data from existing resource' feature
$enable_copy_data_from=true;

# Enable the 'related resources' field when editing resources.
$enable_related_resources=true;

# Adds an option to the upload page which allows Resources Uploaded together to all be related 
/* requires $enable_related_resources=true */
/* $php_path MUST BE SET */
$relate_on_upload=false;

# Option to make relating all resources at upload the default option if $relate_on_upload is set
$relate_on_upload_default=false;

# Enable the 'keep me logged in on this device' option at the login form
# If the user then selects this, a 100 day expiry time is set on the cookie.
$allow_keep_logged_in=true;
#Remember Me Checked By Default
$remember_me_checked = true;

# Show the contact us link?
$contact_link=true;
$nav2contact_link = false;

# Show the about us link?
$about_link=true;

# When uploading resources (batch upload) and editing the template, should the date be reset to today's date?
# If set to false, the previously entered date is used.
# Please note that if upload_then_edit is enabled, then this will happen at upload stage in order to get the similar behaviour for this mode
$reset_date_upload_template=true;
$reset_date_field=12; # Which date field to reset? (if using multiple date fields)

# When uploading resources (batch upload) and editing the template, should all values be reset to blank or the default value every time?
$blank_edit_template=false;

# Show expiry warning when expiry date has been passed
$show_expiry_warning=true;

# Make selection box in collection edit menu that allows you to select another accessible collection to base the current one upon.
# It is helpful if you would like to make variations on collections that are heavily commented upon or re-ordered.
$enable_collection_copy=true;

# Default resource types to use for searching (leave empty for all)
$default_res_types="";

# Show the Resource ID on the resource view page.
$show_resourceid=true;

# Show the resource type on the resource view page.
$show_resource_type=false;

# Show the access on the resource view page.
$show_access_field=true;

# Show the 'contributed by' on the resource view page.
$show_contributed_by=true;

# Show the extension after the truncated text in the search results.
$show_extension_in_search=false;

# Should the category tree field (if one exists) default to being open instead of closed?
$category_tree_open=false;

# Should searches using the category tree use AND for hierarchical keys?
$category_tree_search_use_and=false;

# Force selection of parent nodes when selecting a sub node? 
# If set to false then each node should be unique to avoid possible corruption when exporting/importing data
$category_tree_add_parents=true;

# Force deselection of child nodes when deselecting a node?
$category_tree_remove_children=true;

# Length of a user session. This is used for statistics (user sessions per day) and also for auto-log out if $session_autologout is set.
$session_length = 300;

# Automatically log a user out at the end of a session (a period of idleness equal to $session_length above).
$session_autologout = true;

# Randomised session hash?
# Setting to 'true' means each new session is completely unique each login. This may be more secure as the hash is less easy to guess but means that only one user can use a given user account at any one time.
$randomised_session_hash=false;

# Allow browsers to save the login information on the login form.
$login_autocomplete=true;

# Option to ignore case when validating username at login. 
$case_insensitive_username=false;

# Password standards - these must be met when a user or admin creates a new password.
$password_min_length=7; # Minimum length of password
$password_min_alpha=1; # Minimum number of alphabetical characters (a-z, A-Z) in any case
$password_min_numeric=1; # Minimum number of numeric characters (0-9)
$password_min_uppercase=0; # Minimum number of upper case alphabetical characters (A-Z)
$password_min_special=0; # Minimum number of 'special' i.e. non alphanumeric characters (!@$%& etc.)

# How often do passwords expire, in days? (set to zero for no expiry).
$password_expiry=0;

# How many failed login attempts per IP address until a temporary ban is placed on this IP
# This helps to prevent dictionary attacks.
$max_login_attempts_per_ip=20;

# How many failed login attempts per username until a temporary ban is placed on this IP
$max_login_attempts_per_username=5;

# How long the user must wait after failing the login $max_login_attempts_per_ip or $max_login_attempts_per_username times.
$max_login_attempts_wait_minutes=10;

# How long to wait (in seconds) before returning a 'password incorrect' message (for logins) or 'e-mail not found' message (for the request new password page)
# This can help to deter 'brute force' attacks, trying to find user's passwords or e-mail addresses in use.
$password_brute_force_delay=4;

# Use imperial instead of metric for the download size guidelines
$imperial_measurements=false;

# Use day-month-year format? If set to false format will be month-day-year.
$date_d_m_y=true;

# What is the default resource type to use for batch upload templates?
$default_resource_type=1;

# When extracting text from documents (e.g. HTML, DOC, TXT, PDF) which field is used for the actual content?
# Comment out the line to prevent extraction of text content
$extracted_text_field=72;

# Enable user rating of resources
# Users can rate resources using a star ratings system on the resource view page.
# Average ratings are automatically calculated and used for the 'popularity' search ordering.
$user_rating=false;

# Enable public collections
# Public collections are collections that have been set as public by users and are searchable at the bottom
# of the themes page. Note that, if turned off, it will still be possible for administrators to set collections
# as public as this is how themes are published.
$enable_public_collections=true;

# Custom User Registration Fields
# -------------------------------
# Additional custom fields that are collected and e-mailed when new users apply for an account
# Uncomment the next line and set the field names, comma separated
#$custom_registration_fields="Phone Number,Department";
# Which of the custom fields are required?
# $custom_registration_required="Phone Number";
# You can also set that particular fields are displayed in different ways as follows:
# $custom_registration_types["Department"]=1;
# Types are as follows:
# 	1: Normal text box (default)
# 	2: Large text box
#   3: Drop down box (set options using $custom_registration_options["Field Name"]=array("Option 1","Option 2","Option 3");
#   4: HTML block, e.g. help text paragraph (set HTML using $custom_registration_html["Field Name"]="<b>Some HTML</b>";
#      Optionally, you can add the language to this, ie. $custom_registration_html["Field Name"]["en"]=...
#   5: Checkbox, set options using $custom_registration_options["Field Name"]=array("0:Option 1","1:Option 2","Option 3");
#      where 0: and 1: are unchecked and checked(respectively) by default, if not specified then assumed unchecked.  Example:
#      $custom_registration_options["Department"]=array("0:Human Resources","1:Marketing","1:Sales","IT");
#      Note that if this field is listed in $custom_registration_required, then the user will be forced to check at least one option.


# Allow user group to be selected as part of user registration?
# User groups available for user selection must be specified using the 'Allow registration selection' option on each user group
# in System Setup.
# Only useful when $user_account_auto_creation=true;
$registration_group_select=false;

# Custom Resource/Collection Request Fields
# -----------------------------------------
# Additional custom fields that are collected and e-mailed when new resources or collections are requested.
# Uncomment the next line and set the field names, comma separated
#$custom_request_fields="Phone Number,Department";
# Which of the custom fields are required?
# $custom_request_required="Phone Number";
# You can also set that particular fields are displayed in different ways as follows:
# $custom_request_types["Department"]=1;
# Types are as follows:
# 	1: Normal text box (default)
# 	2: Large text box
#   3: Drop down box (set options using $custom_request_options["Field Name"]=array("Option 1","Option 2","Option 3");
#   4: HTML block, e.g. help text paragraph (set HTML usign $custom_request_html="<b>Some HTML</b>";


# Send an e-mail to the address set at $email_notify above when user contributed
# resources are submitted (status changes from "User Contributed - Pending Submission" to "User Contributed - Pending Review").
$notify_user_contributed_submitted=true;

# When requesting feedback, allow the user to select resources (e.g. pick preferred photos from a photo shoot).
$feedback_resource_select=false;
# When requesting feedback, display the contents of the specified field (if available) instead of the resource ID. 
#$collection_feedback_display_field=51;


# Uncomment and set the below value to set the maximum size of uploaded file that thumbnail/preview images will be created for.
# This is useful when dealing with very large files that may place a drain on system resources - for example 100MB+ Adobe Photoshop files will take a great deal of cpu/memory for ImageMagick to process and it may be better to skip the automatic preview in this case and add a preview JPEG manually using the "Upload a preview image" function on the resource edit page.
# The value is in MB.
# $preview_generate_max_file_size=100;

# Should resource views be logged for reporting purposes?
# Note that general daily statistics for each resource are logged anyway for the statistics graphs
# - this option relates to specific user tracking for the more detailed report.
$log_resource_views=false;

# A list of file extentions of file types that cannot be uploaded for security reasons.
# For example; uploading a PHP file may allow arbirtary execution of code, depending on server security settings.
$banned_extensions=array("php","cgi","pl","exe","asp","jsp", 'sh', 'bash');

#Set a default access value for the upload page. This will override the default resource template value.
#Change the value of this option to the access id number
$override_access_default=false;
#Set a default status value for the upload page. This will override the default resource template value.
#Change the value of this option to the status id number
$override_status_default=false;

# When adding resource(s), in the upload template by the status and access fields are hidden.
# Set the below option to 'true' to enable these options during this process.
$show_status_and_access_on_upload=false;

# Set Permission required to show "access" and "status" fields on upload, evaluates PHP code so must be preceded with 'return' and end with a semicolon. False = No permission required.
$show_status_and_access_on_upload_perm = "return !checkperm('F*');"; # Stack permissions= " return !checkperm('e0') && !checkperm('c')";

# Access will be shown if this value is set to true. This option acts as an override for the status and access flag.
# Show Status and Access = true && Show Access = true   - Status and Access Shown
# Show Status and Access = false && Show Access = true  - Only Access Shown
# Show Status and Access = true && Show Access = false - Only Status Shown
# Show Status and Access = false && Show Access = false - Neither Shown
# DEFAULT VALUE: = $show_status_and_access_on_upload;
# Please note: add unset($show_access_on_upload); to config if you wish to honour true/false or false/true variations
$show_access_on_upload = &$show_status_and_access_on_upload;

# Permission required to show "access" field on upload, this evaluates PHP code so must be preceded with 'return'. True = No permission required. 
# Example below ensures they have permissions to edit active resources.
# $show_access_on_upload_perm = "return checkperm('e0')"; #Stack permissions= "return checkperm('e0') && checkperm('c');";
$show_access_on_upload_perm = "return true;";


# Mime types by extensions.
# used by pages/download.php to detect the mime type of the file proposed to download.
$mime_type_by_extension = array(
    'mov'  => 'video/quicktime',
    '3gp'  => 'video/3gpp',
    'mpg'  => 'video/mpeg',
    'mp4'  => 'video/mp4',
    'avi'  => 'video/msvideo',
    'mp3'  => 'audio/mpeg',
    'wav'  => 'audio/x-wav',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'png'  => 'image/png',
    'odt'  => 'application/vnd.oasis.opendocument.text',
    'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
    'odp'  => 'application/vnd.oasis.opendocument.presentation',
    'svg'  => 'image/svg+xml',
    'pdf'  => 'application/pdf',
  );

# PHP execution time limit
# Default is 5 minutes.
$php_time_limit=300;

# Cron jobs maximum execution time (Default: 30 minutes)
$cron_job_time_limit = 1800;

# Should the automatically produced video preview file be available as a separate download?
$flv_preview_downloadable=false;

# When multiple dropdowns are used on the simple search box, should selecting something from one or more dropdowns
# limit the options available in the other dropdowns automatically? This adds a performance penalty so is off by default.
$simple_search_dropdown_filtering=false;

# Honor display condition settings on simple search for the included fields.
$simple_search_display_condition=array();

# When searching, also include themes/public collections at the top?
$search_includes_themes=false;
$search_includes_public_collections=false;
$search_includes_user_collections=false;
$search_includes_resources=true;

# Should the Clear button leave collection searches off by default?
$clear_button_unchecks_collections=true;

# include keywords from collection titles when indexing collections
$index_collection_titles = true;
$index_collection_creator = true; 


# Configures separators to use when splitting keywords (in other words - characters to treat as white space)
# You must reindex after altering this if you have existing data in the system (via pages/tools/reindex.php)
# 'Space' is included by default and does not need to be specified below.
# Note: leave non breaking space in
$config_separators=array("/","_",".","; ","-","(",")","'","\"","\\", "?", '’', '“', ' ');


# Resource field verbatim keyword regex
# Using the index value of [resource field], specifies regex criteria for adding verbatim strings to keywords.
# It solves the problem, for example, indexing an entire "nnn.nnn.nnn" string value when '.' are used in $config_separators.
# $resource_field_verbatim_keyword_regex[1] = '/\d+\.\d+\w\d+\.\d+/';		// this example would add 994.1a9.93 to indexed keywords for field 1.  This can be found using quoted search.

# Global permissions
# Permissions that will be prefixed to all user group permissions
# Handy for setting global options, e.g. for fields
$global_permissions="";

# Global permissions
# Permissions that will be removed from all user group permissions
# Useful for temporarily disabling permissions globally, e.g. to make the system readonly during maintenance.
# Suggested setting for a 'read only' mode: $global_permissions_mask="a,t,c,d,e0,e1,e2,e-1,e-2,i,n,h,q";
$global_permissions_mask="";

# User account application - auto creation
# By default this is switched off and applications for new user accounts will be sent as e-mails
# Enabling this option means user accounts will be created but will need to be approved by an administrator
# before the user can log in.
$user_account_auto_creation=false;
$user_account_auto_creation_usergroup=2; # which user group for auto-created accounts? (see also $registration_group_select - allows users to select the group themselves).

# Automatically approve ALL account requests (created via $user_account_auto_creation above)?
$auto_approve_accounts=false;

# Automatically approve accounts that have e-mails ending in given domain names.
# E.g. $auto_approve_domains=array("mycompany.com","othercompany.org");
#
# NOTE - only used if $user_account_auto_creation=true above.
# Do not use with $auto_approve_accounts above as it will override this parameter and approve all accounts regardless of e-mail domain.
#
# Optional additional feature... place users in groups depending on email domain. Use syntax:
# $auto_approve_domains=array("mycompany.com"=>2,"othercompany.org"=>3);
# Where 2 and 3 are the ID numbers for the respective user groups.

$auto_approve_domains=array();

# Allows for usernames to be created based on full name (eg. John Mac -> John_Mac)
# Note: user_account_auto_creation needs to be true.
$user_account_fullname_create=false;

# Display a larger preview image on the edit page?
$edit_large_preview=true;

# Allow sorting by resource ID
$order_by_resource_id=false;

# Enable find similar search?
$enable_find_similar=true;

#Bypass share.php and go straight to e-mail
$bypass_share_screen = false;

# Allow multiple collections to be e-mailed at once
$email_multi_collections = false;

#  Link back to collections from log page - if "" then link is ignored.
#  suggest 
# $back_to_collections_link = "&lt;&lt;-- Back to My Collections &lt;&lt;--";
$back_to_collections_link = "";

# For fields with partial keyword indexing enabled, this determines the minimum infix length
$partial_index_min_word_length=3;

# ---------------------
# Search Display 
# Thumbs Display Fields: array of fields to display on the large thumbnail view.
$thumbs_display_fields=array(8); # ** SEE NOTE (1)
# array of defined thumbs_display_fields to apply CSS modifications to (via $search_results_title_wordwrap, $search_results_title_height, $search_results_title_trim)
$thumbs_display_extended_fields=array();
	# $search_result_title_height=26;
	$search_results_title_trim=30;
	$search_results_title_wordwrap=100; // Force breaking up of very large titles so they wrap to multiple lines (useful when using multi line titles with $search_result_title_height). By default this is set very high so that breaking doesn't occur. If you use titles that have large unbroken words (e.g. filenames with no spaces) then it may be useful to set this value to something lower, e.g. 20
	
# Enable extra large thumbnails option for search screen
$xlthumbs=false;

# Extra Large Display Fields:  array of fields to display on the xlarge thumbnail view.
$xl_thumbs_display_fields=array(8); # ** SEE NOTE (1)
# array of defined xl_thumbs_display_fields to apply CSS modifications to (via $xl_search_results_title_wordwrap, $xl_search_results_title_height, $xl_search_results_title_trim)
$xl_thumbs_display_extended_fields=array();
# $xl_search_result_title_height=26;
$xl_search_results_title_trim=60;
$xl_search_results_title_wordwrap=100;

# SORT Fields: display fields to be added to the sort links in large,small, and xlarge thumbnail views
$sort_fields=array(12); # ** SEE NOTE (1)

# List Display Fields: array of fields to display on the list view
$list_display_fields=array(8,3,12); # ** SEE NOTE (1)
$list_search_results_title_trim=25;


# Related Resource title trim: set to 0 to disable
$related_resources_title_trim=15;


# TITLE field: Default title for all resources 
# Should be used as title on the View and Collections pages.
# This field will be inherited even if Inherit Global fields is set to false.
$view_title_field=8; # ** SEE NOTE (1)

# Searchable Date Field:
$date_field=12; # ** SEE NOTE (1)

# Search for dates into the future. Allows the specified number of years ahead of this year to be added to the year drop down used by simple and advanced search.
$maxyear_extends_current=0;

# Data Joins -- Developer's tool to allow adding additional resource field data to the resource table for use in search displays.
# ex. $data_joins=array(13); to add the expiry date to the general search query result.  
$data_joins=array();

# List View Default Columns
$id_column=true;
$resource_type_column=true;
$date_column=false; // based on creation_date which is a deprecated mapping. The new system distinguishes creation_date (the date the resource record was created) from the date metadata field. creation_date is updated with the date field.
# ---------------------------



# On some PHP installations, the imagerotate() function is wrong and images are rotated in the opposite direction
# to that specified in the dropdown on the edit page.
# Set this option to 'true' to rectify this.
$image_rotate_reverse_options=false;

# Once collections have been published as themes by default they are removed from the user's My Collections. These option leaves them in place.
$themes_in_my_collections=false;

# Show an upload link in the top navigation? (if 't' and 'c' permissions for the current user)
$top_nav_upload=true;
# Show an upload link in the top navigation in addition to 'my contributions' for standard user? (if 'd' permission for the current user)
$top_nav_upload_user=false;
$top_nav_upload_type="plupload"; # The upload type. Options are plupload, ftp, local

# Configure the maximum upload file size; this directly translates into plupload's max_file_size if set
# $plupload_max_file_size = '50M';

# You can set the following line to ''  to disable chunking. May resolve issues with flash uploader.
$plupload_chunk_size='5mb';


# Resource deletion state
# When resources are deleted, the variable below can be set to move the resources into an alternative state instead of removing the resource and its files from the system entirely.
# 
# The resource will still be removed from any collections it has been added to.
#
# Possible options are:
#
# -2	User Contributed Pending Submission (not useful unless deleting user-contributed resources)
# -1	User Contributed Pending Review (not useful unless deleting user-contributed resources) 
# 1		Waiting to be archived
# 2 	Archived
# 3		Deleted (recommended)
$resource_deletion_state=3;

# Does deleting resources require password entry? (single resource delete)
# Off by default as resources are no longer really deleted by default, they are simply moved to a deleted state which is less dangerous - see $resource_deletion_state above.
$delete_requires_password=false;

# Offline processes (e.g. staticsync and create_previews.php) - for process locking, how old does a lock have to be before it is ignored?
$process_locks_max_seconds=60*60*4; # 4 hours default.

# List of extensions that can be processed by ffmpeg.
# Mostly video files.
# @see http://en.wikipedia.org/wiki/List_of_file_formats#Video
$ffmpeg_supported_extensions = array(
		'aaf',
		'3gp',
		'asf',
		'avchd',
		'avi',
		'cam',
		'dat',
		'dsh',
		'flv',
		'm1v',
		'm2v',
		'mkv',
		'wrap',
		'mov',
		'mpeg',
		'mpg',
		'mpe',
		'mp4',
		'mxf',
		'nsv',
		'ogm',
		'ogv',
		'rm',
		'ram',
		'svi',
		'smi',
		'webm',
		'wmv',
		'divx',
		'xvid',
		'm4v',
	);

# A list of extensions which will be ported to mp3 format for preview.
# Note that if an mp3 file is uploaded, the original mp3 file will be used for preview.
$ffmpeg_audio_extensions = array(
    'wav',
    'ogg',
    'aif',
    'aiff',
    'au',
    'cdda',
    'm4a',
    'wma',
    'mp2',
    'aac',
    'ra',
    'rm',
    'gsm'
    );
	
# The audio settings for mp3 previews
$ffmpeg_audio_params = "-acodec libmp3lame -ab 64k -ac 1"; # Default to 64Kbps mono

# A list of file extensions for files which will not have previews automatically generated. This is to work around a problem with colour profiles whereby an image file is produced but is not a valid file format.
$no_preview_extensions=array("icm","icc");

# If set, send a notification when resources expire to this e-mail address.
# This requires batch/expiry_notification.php to be executed periodically via a cron job or similar.
# If this is not set and the script is executed notifications will be sent to resource admins, or users in groups specified in $email_notify_usergroups 
# $expiry_notification_mail="myaddress@mydomain.example";

# What is the default display mode for search results? (smallthumbs/thumbs/list)
$default_display="thumbs";

# Generate thumbs/previews for alternative files?
$alternative_file_previews=true;
$alternative_file_previews_batch=true;


# Permission to show the replace file, preview image only and alternative files options on the resource edit page.
# Overrides required permission of F*
$custompermshowfile=false;

# enable support for storing an alternative type for each alternate file
# to activate, enter the array of support types below. Note that the 
# first value will be the default
# EXAMPLE: 
# $alt_types=array("","Print","Web","Online Store","Detail");
$alt_types=array("");
# organize View page display according to alt_type
$alt_types_organize=false;

# Display col-size image of resource on alternative file management page
$alternative_file_resource_preview=true;

# For alternative file previews... enable a thumbnail mouseover to see the preview image?
$alternative_file_previews_mouseover=false;

# Confine public collections display to the collections posted by the user's own group, sibling groups, parent group and children groups.
# All collections can be accessed via a new 'view all' link.
$public_collections_confine_group=false;

# Show public collections in the top nav?
$public_collections_top_nav=false;

# Show collection name below breadcrumbs?  
$show_collection_name = false;

# Themes simple view - option to show featured collection categories and featured collections (themes) as basic tiles wih no images.
# Can be tested or used for custom link by adding querystring parameter simpleview=true to collections_featured.php e.g. pages/collections_featured.php?simpleview=true
$themes_simple_view=false;
# Option to show images on featured collection and featured collection category tiles if $themes_simple_view is enabled
$themes_simple_images=true;

# Option to show single home slideshow image on featured collection page (collections_featured.php) if $themes_simple_view is enabled
$featured_collection_static_bg = false;

# Navigate to deeper levels in theme category trees? Set to false to link to matching resources directly.
$themes_category_navigate_levels=false;
# If a theme header contains a single collection, allow the title to be a direct link to the collection.
# Drilling down is still possible via the >Expand tool, which replaces >Select when a deeper level exists
$themes_single_collection_shortcut=false;

// Enable to have a background image when $themes_simple_view is enabled
$themes_show_background_image = false;

# Ask the user the intended usage when downloading
$download_usage=false;
$download_usage_options=array("Press","Print","Web","TV","Other");
# Option to block download (hide the button) if user selects specific option(s). Only used as a guide for the user e.g. to indicate that permission should be sought.
#$download_usage_prevent_options=array("Press");

# Should public collections exclude themes
# I.e. once a public collection has been given a theme category, should it be removed from the public collections search results?
$public_collections_exclude_themes=true;

# Show a download summary on the resource view page.
$download_summary=false;

# Ability to hide error messages
$show_error_messages=true;

# Log error messages to a central server. Error paramaters are POSTed along with the system's base URL.
# $log_error_messages_url="https://my.server.url/script_path.php";

# Include detail of errors to user
$show_detailed_errors=false;

# Ability to set that the 'request' button on resources adds the item to the current collection (which then can be requested) instead of starting a request process for this individual item.
$request_adds_to_collection=false;

# Option to change the FFMPEG download name from the default  to a custom string.
# $ffmpeg_preview_download_name = "Video preview";

# Option to change the original download filename (Use %EXTENSION, %extension or %Extension as a placeholder. Using ? is now DEPRECATED. The placeholder will be replaced with the filename extension, using the same case. E.g. "Original %EXTENSION file" -> "Original WMV file")
# $original_download_name="Original %EXTENSION file";


# Generation of alternative image file sizes/formats using ImageMagick/GraphicMagick
# It is possible to automatically generate different file sizes and have them attached as alternative files.
# This works in a similar way to video file alternatives.
# See below for examples.
# The blocks must be numbered sequentially (0, 1, 2).
# 'params' are any extra parameters to pass to ImageMagick for example DPI
# 'source_extensions' is a comma-separated list of the files that will be processed, e.g. "eps,png,gif" (note no spaces).
# 'source_params' are parameters for the source file (e.g. -density 1200)
#
# Example - automatically create a PNG file alternative when an EPS file is uploaded.
# $image_alternatives[0]["name"]="PNG File";
# $image_alternatives[0]["source_extensions"]="eps";
# $image_alternatives[0]["source_params"]="";
# $image_alternatives[0]["filename"]="alternative_png";
# $image_alternatives[0]["target_extension"]="png";
# $image_alternatives[0]["params"]="-density 300"; # 300 dpi
# $image_alternatives[0]["icc"]=false;

# $image_alternatives[1]["name"]="CMYK JPEG";
# $image_alternatives[1]["source_extensions"]="jpg,tif";
# $image_alternatives[1]["source_params"]="";
# $image_alternatives[1]["filename"]="cmyk";
# $image_alternatives[1]["target_extension"]="jpg";
# $image_alternatives[1]["params"]="-quality 100 -flatten $icc_preview_options -profile ".dirname(__FILE__) . "/../iccprofiles/name_of_cmyk_profile.icc"; # Quality 100 JPEG with specific CMYK ICC Profile
# $image_alternatives[1]["icc"]=true; # use source ICC profile in command

# Example - automatically create a JPG2000 file alternative when an TIF file is uploaded
# $image_alternatives[2]['name']              = 'JPG2000 File';
# $image_alternatives[2]['source_extensions'] = 'tif';
# $image_alternatives[2]["source_params"]="";
# $image_alternatives[2]['filename']          = 'New JP2 Alternative';
# $image_alternatives[2]['target_extension']  = 'jp2';
# $image_alternatives[2]['params']            = '';
# $image_alternatives[2]['icc']               = false;


# For reports, the list of default reporting periods
$reporting_periods_default=array(7,30,100,365);


# For checkbox list searching, perform logical AND instead of OR when ticking multiple boxes.
$checkbox_and = false;

# For dynamic keyword list suggestions, use logic 'contains' instead of 'starts with'.
$dynamic_keyword_suggest_contains=false;

# Uncomment if you wish to limit the suggestions to display after a certain number of characters have been entered.
# Useful if your dynamic keyword fields have a large number options.
# Be sure to set this to a value equal to your shortest dynamic keyword option.
# Requires $dynamic_keyword_suggest_contains=true;
# $dynamic_keyword_suggest_contains_characters=2;

# Option to show resource ID in the thumbnail, next to the action icons.
$display_resource_id_in_thumbnail=false;

# Show "Save" and "Clear" buttons at the top of the resource edit form as well as at the bottom
$edit_show_save_clear_buttons_at_top=false;

# Allow empty collections to be shared?
$collection_allow_empty_share=false;

# Allow collections containing resources that are not active to be shared?
$collection_allow_not_approved_share=false;

#Allow the smartsearch to override $access rules when searching
$smartsearch_accessoverride=true;

# Allow special searches to honor resource type settings.
$special_search_honors_restypes=false;

# Image preview zoom using jQuery.zoom (hover over the preview image to zoom in on the resource view page)
$image_preview_zoom=false;

# How many characters from the fields are 'mirrored' on to the resource table. This is used for field displays in search results.
# This is the varchar length of the 'field' columns on the resource table.
# The value can be increased if titles (etc.) are being truncated in search results, but the field column lengths must be altered also.
$resource_field_column_limit=200;

# Resource access filter
# If set, filter searches to resources uploaded by users with the specified user IDs only. '-1' is an alias to the current user.
# For example, to filter search results to only include resources uploaded by the current user themselves and the admin user (by default user ID 1) set:
# $resource_created_by_filter=array(-1,1);
# This is used for the ResourceSpace demo installation.
#
# $resource_created_by_filter=array();


#
# ------------------------ eCommerce Settings -----------------------------
#
# Pricing information for the e-commerce / basket request mode.
# Pricing is size based, so that the user can select the download size they require.
$pricing["scr"]=10;
$pricing["lpr"]=20;
$pricing["hpr"]=30; # (hpr is usually the original file download)
$currency_symbol="&pound;";
$payment_address="payment.address@goes.here"; // you must enable Instant Payment Notifications in your Paypal Account Settings.
$payment_currency="GBP";
$paypal_url="https://www.paypal.com/cgi-bin/webscr";

# Ability to set a field which will store 'Portrait' or 'Landscape' depending on image dimensions
# $portrait_landscape_field=1;


# ------------------------------------------------------------------------------------------------------------------
# StaticSync (staticsync.php)
# The ability to synchronise ResourceSpace with a separate and stand-alone filestore.
# Amend the following to set the ref of the user account that staticsync resources will be 'created by' 
$staticsync_userref=1;

# ------------------------------------------------------------------------------------------------------------------
$syncdir="/dummy/path/to/syncfolder"; # The sync folder
$nogo="[folder1]"; # A list of folders to ignore within the sign folder.

/*
Allow the system to specify the exact folders under the sync directory that need to be synced/ingested in ResourceSpace.
Note: When using $staticsync_whitelist_folders and $nogo configs together, ResourceSpace is going to first check the
folder is in the $staticsync_whitelist_folders folders and then look in the $nogo folders.
*/
$staticsync_whitelist_folders = array();

# Maximum number of files to process per execution of staticsync.php
$staticsync_max_files = 10000;
$staticsync_autotheme=true; # Automatically create themes based on the first and second levels of the sync folder structure.

# Mapping extensions to resource types for sync'd files
# Format: staticsync_extension_mapping[resource_type]=array("extension 1","extension 2");
$staticsync_extension_mapping_default=1;
$staticsync_extension_mapping[3]=array("mov","3gp","avi","mpg","mp4","flv"); # Video
$staticsync_extension_mapping[4]=array("flv");
# Uncomment and set the next line to specify a category tree field to use to store the retieved path information for each file. The tree structure will be automatically modified as necessary to match the folder strucutre within the sync folder (performance penalty).
# $staticsync_mapped_category_tree=50;
# Uncomment and set the next line to specify a text field to store the retrieved path information for each file. This is a time saving alternative to the option above.
# $staticsync_filepath_to_field=100;
# Append multiple mapped values instead of overwritting? This will use the same appending methods used when editing fields. Not used on dropdown, date, category tree, datetime, or radio buttons
$staticsync_extension_mapping_append_values=true;
# Uncomment and set the next line to specify specific fields for $staticsync_extension_mapping_append_values
#$staticsync_extension_mapping_append_values_fields=array();
# Should the generated resource title include the sync folder path?
# This will not be used if $view_title_field is set to th same field as $filename_field.
$staticsync_title_includes_path=true;
# Should the sync'd resource files be 'ingested' i.e. moved into ResourceSpace's own filestore structure?
# In this scenario, the sync'd folder merely acts as an upload mechanism. If path to metadata mapping is used then this allows metadata to be extracted based on the file's location.
$staticsync_ingest=false;
# Option to force ingest of existing files into filestore if switching from $staticsync_ingest=false to $staticsync_ingest=true;
$staticsync_ingest_force=false;
# Try to rotate images automatically when not ingesting resources? If set to TRUE you must also set $imagemagick_preserve_profiles=true;
$autorotate_no_ingest=false;
# Try to rotate images automatically when ingesting resources? If set to TRUE you must also set $imagemagick_preserve_profiles=true;
$autorotate_ingest=false;
# The default workflow state for imported files (-2 = pending submission, -1 = pending review, etc.)
$staticsync_defaultstate=0;
# Archive state to set for resources where files have been deleted/moved from syncdir
$staticsync_deleted_state=2;
# Optional array of archive states for which missing files will be ignored and not marked as deleted, useful when using offline_archive plugin.
$staticsync_ignore_deletion_states = array(2, 3);

# staticsync_revive_state - if this is set then deleted items that later reappear will be moved to this archive state
# $staticsync_revive_state=-1;
#
# StaticSync Path to metadata mapping
# ------------------------
# It is possible to take path information and map selected parts of the path to metadata fields.
# For example, if you added a mapping for '/projects/' and specified that the second level should be 'extracted' means that 'ABC' would be extracted as metadata into the specified field if you added a file to '/projects/ABC/'
# Hence meaningful metadata can be specified by placing the resource files at suitable positions within the static
# folder hierarchy.
# Use the line below as an example. Repeat this for every mapping you wish to set up
#	$staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/",
#		"field"=>10,
#		"level"=>2
#		);
#
# You can also now enter "access" in "field" to set the access level for the resource. The value must match the name of the access level
# in the default local language. Note that custom access levels are not supported. For example, the mapping below would set anything in 
# the projects/restricted folder to have a "Restricted" access level.
#	$staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/restricted",
#		"field"=>"access",
#		"level"=>2
#		);
#
# You can enter "archive" in "field" to set the archive state for the resource. You must include "archive" to the array and its value must match either a default level or a custom archive level. The mapped folder level does not need to match the name of the archive level. Note that this will override $staticsync_defaultstate. For example, the mapping below would set anything in the restricted folder to have an "Archived" archive level.
#   $staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/restricted",
#		"field"=>"archive",
#		"level"=>2,
#		"archive"=>2
#		);
#
# ALTERNATIVE FILES
#
# There are a number of options for adding alternative files automatically using staticsync. These only work when staticsync_ingest is true
#
# OPTION 1 - USE A SUBFOLDER WITH SAME NAME AS PRIMARY FILE
# If staticsync finds a folder in the same directory as a file with the same name as a file but with this suffix appended, then files in the folder will be treated as alternative files for the given file.
# For example a folder/file structure might look like:
# /staticsync_folder/myfile.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative1.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative2.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative3.jpg
# NOTE: Alternative file processing only works when $staticsync_ingest is set to 'true'.
$staticsync_alternatives_suffix="_alternatives";

# OPTION 2 - ADD FILES IN SAME FOLDER WITH DEFINED STRING SUFFIX
# Option to have alternative files located in same directory as primary files but identified by a defined string. As with staticsync_alternatives_suffix this only works when $staticsync_ingest is set to 'true'.
# Can instead use $staticsync_alt_suffix_array below 
#$staticsync_alternative_file_text="_alt_";

# OPTION 3 - ADD FILES IN SAME FOLDER WITH VARIOUS STRING SUFFIXES
# $staticsync_alt_suffixes / $staticsync_alt_suffix_array 
# These can be used instead of $staticsync_alternatives_suffix to 
# support mapping suffixes to the names used for the alternative files
/*
$staticsync_alt_suffixes = true;
$staticsync_alt_suffix_array =array (
    '_alt' => "",
   '_verso' => "Verso",
   '_dng' => "DNG",
   '_orig' => "Original Scan",
   '_tp' => "Title Page",
   '_tpv' => "Title Page Verso",
   '_cov' => "Cover",
   '_ex' => "Enclosure",
   '_scr' => "Inscription"
    );
*/
# $numeric_alt_suffixes = 8;
# Optionally set this to ignore files that aren't at least this many seconds old
# $staticsync_file_minimum_age = 120; 


# if false, the system will always synthesize a title from the filename and path, even
# if an embedded title is found in the file. If true, the embedded title will be used.
$staticsync_prefer_embedded_title = true;

# Do we allow deletion of files located in $syncdir through the UI?
$staticsync_allow_syncdir_deletion=false;



# End of StaticSync settings


# Show tabs on the edit/upload page. Disables collapsible sections
$tabs_on_edit=false;

# Show additional clear and 'show results' buttons at top of advanced search page
$advanced_search_buttons_top=false;

# Allow to selectively disable upload methods.
# Controls are :
# - single_upload            : Enable / disable "Add Single Resource"
# - in_browser_upload        : Enable / disable "Add Resource Batch - In Browser"
$upload_methods = array(
		'single_upload' => true,
		'in_browser_upload' => true
	);

# Hide links to other uploader
$hide_uploadertryother = false;

# Set path to Unoconv (a python-based bridge to OpenOffice) to allow document conversion to PDF.
## $unoconv_path="/usr/bin";
# Files with these extensions will be passed to unoconv (if enabled above) for conversion to PDF and auto thumb-preview generation.
# Default list taken from http://svn.rpmforge.net/svn/trunk/tools/unoconv/docs/formats.txt
$unoconv_extensions=array("ods","xls","xlsx","doc","docx","odt","odp","html","rtf","txt","ppt","pptx","sxw","sdw","html","psw","rtf","sdw","pdb","bib","txt","ltx","sdd","sda","odg","sdc","potx","key");

# Set path to Libre/OpenOffic's packaged python (required for Windows only).
# $unoconv_python_path='';

# Uncomment to set a point in time where collections are considered 'active' and appear in the drop-down. 
# This is based on creation date for now. Older collections are effectively 'archived', but accessible through Manage My Collections.
# You can use any English-language strings supported by php's strtotime() function.
# $active_collections="-3 months";

# Set this to true to separate related resource results into separate sections (ie. PDF, JPG)
$sort_relations_by_filetype=false;

# Set this to true to separate related resource results into separate sections by resource type (ie. Document, Photo)
$sort_relations_by_restype=false;

# When using the "View these resources as a result set" link, show the original resource in search result?
$related_search_show_self=false;

# Use the collection name in the downloaded zip filename when downloading collections as a zip file?
$use_collection_name_in_zip_name=false;

# PDF/EPS base ripping quality in DPI. Note, higher values might greatly increase the resource usage
# on preview generation (see $pdf_dynamic_rip on how to avoid that)
$pdf_resolution=150;

# PDF/EPS dynamic ripping
# Use pdfinfo (pdfs) or identify (eps) to extract document size in order to calculate an efficient ripping resolution 
# Useful mainly if you have odd sized pdfs, as you might in the printing industry; 
# ex: you have very large PDFs, such as 50 to 200 in (will greatly decrease ripping time and avoid overload) 
# or very small, such as PDFs < 5 in (will improve quality of the scr image)
$pdf_dynamic_rip=false;

# Allow for the creation of new site text entries from Manage Content
# note: this is intended for developers who create custom pages or hooks and need to have more manageable content,
$site_text_custom_create=false;

# use hit count functionality to track downloads rather than resource views.
# Notes:-
#  - This esentially switches the counting method for column hit_count from hits to downloads
#  - This does not reset the counter, thus if changed mid life of service will result in an amalgamation of hits pre and downloads post config change
#  - Consider using the superior more accurate $download_summary=true;
$resource_hit_count_on_downloads=false;
$show_hitcount=false;

# Allow player for mp3 files using VideoJS.
$mp3_player=true;

# Show the performance metrics in the footer (for debug)
$config_show_performance_footer=false;

$use_phpmailer=false;

# Allow to disable thumbnail generation during batch resource upload from FTP or local folder.
# In addition to this option, a multi-thread thumbnail generation script is available in the batch
# folder (create_previews.php). You can use it as a cron job, or manually.
# Notes:-
#  - This also works for normal uploads (through web browser)
#  - This setting may be overridden if previews are required at upload time e.g. if Google Vision facial recognition is configured with a dependent field
$enable_thumbnail_creation_on_upload = true;

# Allow Plugin Upload
$enable_plugin_upload = true;

# Disable geocoding features?
$disable_geocoding = false;

# After obtaining an API key, please set the following config option:
# $google_maps_api_key = '';

#Enable geolocating multiple assets on a map that are part of a collection
$geo_locate_collection = false;

# OpenLayers: The default center and zoom for the map view when searching or selecting a new location. This is a world view.
# For example, to specify the USA use: #$geolocation_default_bounds="-10494743.596017,4508852.6025659,4";
# For example, to specify Utah, use $geolocation_default_bounds="-12328577.96607,4828961.5663655,6";
$geolocation_default_bounds="-3.058839178216e-9,2690583.3951564,2";

# The layers to make available. The first is the default.
$geo_layers="osm";
# To enable Google layers, use:
# $geo_layers="osm, gmap, gsat, gphy";

# Cache openstreetmap tiles on your server. This is slower when loading, but eliminates non-ssl content warnings if your site is SSL (requires curl)
$geo_tile_caching=true;

# Optional path to tile cache directory
#$geo_tile_cache_directory="";


# Add OpenLayers configuration options to this variable to overwrite all other options. 
$geo_override_options = "";

// Only high level tiles are included by default. If you require higher resolution tiles 
// you need permitted access to a full tile server, or you can set up your own. 
// See https://wiki.openstreetmap.org/wiki/Tile_servers for more information
// If no servers are available then your zoom ability will be limited

$geo_tile_servers = array();
//$geo_tile_servers[] = 'a.tile.sometileserver.org';
//$geo_tile_servers[] = 'b.tile.sometileserver.org';
//$geo_tile_servers[] = 'c.tile.sometileserver.org';

// How long will tiles be cached? Set to one year by default
// Unless absolutely necessary this should be a long period to avoid too many requests to the tile server
$geo_tile_cache_lifetime = 60*60*24*365;

# Log developer debug information to the debug log (filestore/tmp/debug.txt)?
# As the default location is world-readable it is recommended for live systems to change the location to somewhere outside of the web directory by setting $debug_log_location below
$debug_log=false;

# Optional extended debugging information from backtrace (records pagename and calling functions)
# debug_extended_info = true;

# Debug log location. Optional. Used to specify a full path to debug file. Ensure folder permissions allow write access to both the file and the containing folder by web service account
#$debug_log_location="d:/logs/resourcespace.log";
#$debug_log_location="/var/log/resourcespace/resourcespace.log";

# enable a list of collections that a resource belongs to, on the view page
$view_resource_collections=false;

# enable titles on the search page that help describe the current context
$search_titles=true;
# whether all/additional keywords should be displayed in search titles (ex. "Recent 1000 / pdf")
$search_titles_searchcrumbs=false;

# Other collections management link switches:
$manage_collections_remove_link=true;
$manage_collections_share_link=true;

# Tool at the bottom of the Collection Manager list which allows users to delete any empty collections that they own. 
$collections_delete_empty=false;

# Allow saving searches as 'smart collections' which self-update based on a saved search. 
$allow_smart_collections=false;
# Run Smart collections asynchronously (faster smart collection searches, with the tradeoff that they are updated AFTER the search.
# This may not be appropriate for usergroups that depend on live updates in workflows based on smart collections.
$smart_collections_async=false;


# Allow each user only one rating per resource (can be edited). Note this will remove all accumlated ratings/weighting on newly rated items.
$user_rating_only_once = true;
# if user_rating_only_once, allow a log view of user's ratings (link is in the rating count on the View page):
$user_rating_stats = true;

# Allow a user to CC oneself when sending resources or collections.
$cc_me=false;

# Allow listing of all recipients when sending resources or collection.
$list_recipients=false;

# How many keywords should be included in the search when a single keyword expands via a wildcard. Setting this too high may cause performance issues.
$wildcard_expand_limit=50;

# Default scramble key (never used as a new one is written to config.php during system install)
$api_scramble_key="abcdef123";

# Allow users capable of deleting a full collection (of resources) to do so from the Collection Manage page.
$collection_purge=false;

# enable option to autorotate new images based on embedded camera orientation data
# requires ImageMagick to work.
$camera_autorotation = false;
$camera_autorotation_ext = array('jpg','jpeg','tif','tiff','png'); // only try to autorotate these formats
$camera_autorotation_gm = false;

# if gnash_dump (gnash w/o gui) is compiled, previews are possible:
# Note: gnash-dump must be compiled on the server. http://www.xmission.com/~ink/gnash/gnash-dump/README.txt
# Ubuntu: ./configure --prefix=/usr/local/gnash-dump --enable-renderer=agg \
# --enable-gui=gtk,dump --disable-kparts --disable-nsapi --disable-menus
# several dependencies will also be necessary, according to ./configure
# $dump_gnash_path="/usr/local/gnash-dump/bin";

# show the title of the resource being viewed in the browser title bar
$show_resource_title_in_titlebar = false;


// checkerboard for gif and png with transparency
$transparency_background = "gfx/images/transparency.gif";

# Remove archived resources from collections results unless user has e2 permission (admins).
$collections_omit_archived=false;

# Set path to Calibre to allow ebook conversion to PDF.
# $calibre_path="/usr/bin";
# Files with these extensions will be passed to calibre (if enabled above) for conversion to PDF and auto thumb-preview generation.
# Set path to Calibre to allow ebook conversion to PDF.
# $calibre_path="/usr/bin";
# Files with these extensions will be passed to calibre (if enabled above) for conversion to PDF and auto thumb-preview generation.
$calibre_extensions=array("epub","mobi","lrf","pdb","chm","cbr","cbz");




# ICC Color Management Features (Experimental)
# Note that ImageMagick must be installed and configured with LCMS support
# for this to work

# Enable extraction and use of ICC profiles from original images
$icc_extraction = false;

# target color profile for preview generation
# the file must be located in the /iccprofiles folder
# this target preview will be used for the conversion
# but will not be embedded
$icc_preview_profile = 'sRGB_IEC61966-2-1_black_scaled.icc';

# additional options for profile conversion during preview generation
$icc_preview_options = '-intent perceptual -black-point-compensation';

# play videos/audio on hover instead of on click
$video_search_play_hover=false; // search.php
$video_view_play_hover=false; // view.php
$video_preview_play_hover=false; // preview.php and preview_all.php

# hotkeys for video playback
$keyboard_navigation_video_search=false;
$keyboard_navigation_video_view=false;
$keyboard_navigation_video_preview=false;

# Use an external SMTP server for outgoing emails (e.g. Gmail).
# Requires $use_phpmailer.
$use_smtp=false;
# SMTP settings:
$smtp_secure=''; # '', 'tls' or 'ssl'. For Gmail, 'tls' or 'ssl' is required.
$smtp_host=''; # Hostname, e.g. 'smtp.gmail.com'.
$smtp_port=25; # Port number, e.g. 465 for Gmail using SSL.
$smtp_auth=true; # Send credentials to SMTP server (false to use anonymous access)
$smtp_username=''; # Username (full email address).
$smtp_password=''; # Password.
$smtpautotls = false; # If using PHPMailer, whether to enable TLS encryption automatically if a server supports it, even if `SMTPSecure` is not set to 'tls'.
/* Enable STMP debug for PHPMailer. Available options are (from none to very verbose):
 - 0 - no debug output. If $debug_log is false, this is off as well
 - 1 - output messages sent by the client
 - 2 - responses received from the server
 - 3 - connection information, can help diagnose STARTTLS failures
 - 4 - low-level information, very verbose, don't use for debugging SMTP, only low-level problems
Note: selecting level 3, will also show debug info for level 1 and 2.
*/
$smtp_debug_lvl = 2;

$sharing_userlists=false; // enable users to save/select predefined lists of users/groups when sharing collections and resources.

$enable_ckeditor = true;
$ckeditor_toolbars="'Styles', 'Bold', 'Italic', 'Underline','FontSize', 'RemoveFormat', 'TextColor','BGColor'";
$ckeditor_content_toolbars="
	{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','RemoveFormat' ] },
	{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','-','Undo','Redo' ] },
	{ name: 'styles', items : [ 'Format' ] },
	{ name: 'paragraph', items : [ 'NumberedList','BulletedList' ] },
	{ name: 'links', items : [ 'Link','Unlink' ] },
	{ name: 'insert', items : [ 'Image','HorizontalRule'] },
	{ name: 'tools', items : [ 'Source', 'Maximize' ] }
";

# Automatically save the edit form after making changes?
$edit_autosave=true;

# use_refine_searchstring can improve search string parsing. disabled by Dan due to an issue I was unable to replicate. (tom)  
$use_refine_searchstring=false;

$show_searchitemsdiskusage=true;

# If set, which field will cause warnings to appear when approving requests containing these resources?
#$warn_field_request_approval=115;

# Experimental performance enhancement - two pass mode for search results.
# The first query returns only the necessary number of results for the current search results display
# The second query is the same but returns only a count of the full result set, which is used to pad the result array to the correct size (so counts display correctly).
# This means that large volumes of resource data are not passed around unnecessarily, which can significantly improve performance on systems with large data sets.
$search_sql_double_pass_mode=true;

# Use the new tab ordering system. This will sort the tabs by the order by value set in System Setup
$use_order_by_tab_view=false;

# Allows for themes with a taller header than standard to still be fully visible in System Setup. 
$admin_header_height=120;

# Display link to request log on view page
$display_request_log_link=false;

# Start uploads as soon as files are added to the queue?
$plupload_autostart=false;

# Clear the queue after uploads have completed
$plupload_clearqueue=true;

# Allow Dates to be set within Date Ranges: Ensure to allow By Date to be used in Advanced Search if required.
$daterange_search=false;

# Keyboard navigation allows using left and right arrows to browse through resources in view/search/preview modes
$keyboard_navigation = true;
$keyboard_navigation_pages_use_alt=false;
# with $keyboard_scroll_jump on arrow keys jump from picture to picture in preview_all mode (horizontal only)
$keyboard_scroll_jump=false;


# How long until the Loading popup appears during an ajax request (milliseconds)
$ajax_loading_timer=500;

#Option for downloaded filename to be just <resource id>.extension, without indicating size or whether an alternative file. Will override $original_filenames_when_downloading which is set as default
$download_filename_id_only = false;

# Append the size to the filename when downloading
# Required: $download_filename_id_only = true;
$download_id_only_with_size = false;

# Index the 'contributed by' field?
$index_contributed_by=false;

# Use CKEditor for site content?
$site_text_use_ckeditor=false;

# Upload Options at top of Edit page (Collection, import metadata checkbox) at top of edit page, rather than the bottom (default).
$edit_upload_options_at_top=false;

# option to always send emails from the logged in user
$always_email_from_user=false;

# option to always cc admin on emails from the logged in user
$always_email_copy_admin=false;

#Option for recent link to use recent X days instead of recent X resources
$recent_search_by_days=false;
$recent_search_by_days_default=60;

$simple_search_reset_after_search=false;

#download_chunk_size - for resource downloads. This can be amended to suit local setup. For instance try changing this to 4096 if experiencing slow downloads
$download_chunk_size=(2 << 20); 

#what to search for in advanced search by default - "Global", "Collections" or resource type id (e.g. 1 for photo in default installation, can be comma separated to enable multiple selections
$default_advanced_search_mode="Global";


# Settings for commenting on resources
$comments_resource_enable=true;				# allow users to make comments on resources
$comments_flat_view=false;						# by default, show in a threaded (indented view)
$comments_responses_max_level=10 ;				# maximum number of nested comments / threads
$comments_max_characters=2000;					# maximum number of characters for a comment
$comments_email_notification_address="";		# email address to use for flagged comment notifications
$comments_show_anonymous_email_address=false;	# by default keep anonymous commenter's email address private
$comments_policy_external_url="";				# if specified, will popup a new window fulfilled by URL (when clicking on "comment policy" link)
$comments_view_panel_show_marker=true;			# show an asterisk by the comment view panel title if comments exist

# show the login panel for anonymous users
$show_anonymous_login_panel=true;

$do_not_add_to_new_collection_default=false;  # will set "do not add to a collection" as the default option for upload option
$no_metadata_read_default=false; // If set to true and $metadata_read is false then metadata will be imported by default
$removenever=false; # Remove 'never' option for resource request access expiration and sets default expiry date to 7 days
$hide_resource_share_link=false; // Configurable option to hide the "Share" link on the resource view page.

# Option to email the contributor when their resources have been approved (moved from pending submission/review to active)
$user_resources_approved_email=false; 

# Set to true to move the Search button before the Clear button
$swap_clear_and_search_buttons=false;

# Option to have default date left blank, instead of current date.
$blank_date_upload_template=false;

# Option to show dynamic dropdows as normal dropdowns on the simple search. If set to false, a standard text box is shown instead.
$simple_search_show_dynamic_as_dropdown=true;

# Option to allow users to see all resources that they uploaded, irrespective of 'z' permissions
$uploader_view_override=true;

# Allow user to select archive state in advanced search
$advanced_search_archive_select=true;

# Additional archive states - option to add workflow states to the default list of -2 (pending submission), -1 (Pending review), 0 (Active), 1 (Awaiting archive), 2 (archived) and 3 (deleted)
# Can be used in conjunction with 'z' permissions to restrict access to workflow states.
# Note that for any state you need to create a corresponding language entry e.g.if you had the following additonal states set
# additional_archive_states=array(4,5);
# you would need the following language entries to be set to an appropriate description e.g.
# $lang['status4']="Pending media team review";
# $lang['status5']="Embargoed";

$additional_archive_states=array();

# Option to use CTRL + S on edit page to save data
$ctrls_to_save=false;

# Option to show resource archive status in search results list view
$list_view_status_column=false;

# Removes textbox on the download usage page.
$remove_usage_textbox=false;

# Moves textbox below dropdown on the download usage page.
$usage_textbox_below=false;

# Option to replace text descriptions of search views (x-large, large, small, list) with icons
$iconthumbs=true;

# Option to make filling in usage text box a non-requirement.
$usage_comment_blank=false;

# Option to add a link to the resource view page that allows a user to email the $email_notify address about the resource
$resource_contact_link=false;

# Hide geolocation panel by default (a link to show it will be displayed instead)
$hide_geolocation_panel=false;

# Option to move the welcome text into the Home Picture Panel. Stops text from falling behind other panels.
$welcome_text_picturepanel=false;
# Hide Welcome Text
$no_welcometext = false;

#Show a specified metadata field below the resource preview image on the view page. Useful for photo captions. 
#$display_field_below_preview=18;


# Optional setting to override the default $email_notify address for resource request email notifications, applies to specified resource types
# e.g. for photo (resource type 1 by default)
# $resource_type_request_emails[1]="imageadministrator@my.site"; 
# e.g. for documents (resource type 2 by default)
# $resource_type_request_emails[2]="documentadministrator@my.site";
#Can be used so that along with the users/emails specified by $resource_type_request_emails, the rest of the users can be notified as well
$resource_type_request_emails_and_email_notify = false;

# Set this to true to prevent possible issues with IE and download.php. Found an issue with a stray pragma: no-cache header that seemed to be added by SAML SSO solution.
$download_no_session_cache_limiter=false;

# Option to show only existing shares that have been shared by the user when sharing resources (not collections)
$resource_share_filter_collections=false;

# Set the following to false to disable permission checking before showing edit_all link in collection bar and on Manage my collections page, useful as this can be a performance hit if there are many resources in collections
$edit_all_checkperms=false;

# Option to turn off email sharing.
$email_sharing=true;

#Resource Share Expiry Controls
$resource_share_expire_days=150; #Maximum number of days allowed for the share 
$resource_share_expire_never=true; #Allow the 'Never' option.

#Collections Share Expiry Controls
$collection_share_expire_days=150; #Maximum number of days allowed for the share 
$collection_share_expire_never=true; #Allow the 'Never' option.

# Having keywords_remove_diacritics set to true means that diacritics will be removed for indexing e.g. 'zwälf' is indexed as 'zwalf', 'café' is indexed as 'cafe'.
# The actual data is not changed, this only affects searching and indexing
$keywords_remove_diacritics=false;

# Show tabbed panels in view. Metadata, Location, Comments are grouped in tabs, Related Collection, Related Galleries and Related Resources, Search for Similar are grouped too
$view_panels=false;

# Allow user to select to import or append embedded metadata on a field by field basis
$embedded_data_user_select=false;

# Always display the option to override the import or appending/prepending of embedded metadata for the fields specified in the array
# $embedded_data_user_select_fields=array(1,8);

# Option to show related resources of specified resource types in a table alongside resource data. Thes resource types will not then be shown in the usual related resources area.
# $related_type_show_with_data=array(3,4);

# Option to show the specified resource types as thumbnails if in $related_type_show_with_data array
#$related_type_thumbnail_view = array(3);

# Additional option to show a link for those with edit access allowing upload of new related resources. The resource type will then be automatically selected for the upload
$related_type_upload_link=true;

# Array of preview sizes to always create. This is especially helpful if your preview size is small than the "thm" size.
$always_make_previews=array();

#Display User Ref on the User Edit Page in the header? Example Output: Edit User 12
$display_useredit_ref=false;

# Basic option to visually hide resource types when searching and uploading
# Note: these resource types will still be available (subject to filtering)
$hide_resource_types = array();

# Ability (when uploading new resources) to include a user selectable option to use the embedded filename to generate the title
# Note: you can set a default option by using one of the following values: do_not_use, replace, prefix, suffix
$merge_filename_with_title = FALSE;
$merge_filename_with_title_default = 'do_not_use';

# Add collection link to email when user submits a collection of resources for review (upload stage only)
# Note: this will send a collection containing only the newly uploaded resources
$send_collection_to_admin = FALSE;

# Set to true if you want to share internally a collection which is not private
$ignore_collection_access = FALSE;

# Show group filter and user search at top of team_user.php
$team_user_filter_top=false;

# Stemming support. Indexes stems of words only, so plural / singular (etc) forms of keywords are indexed as if they are equivalent. Requires a full reindex.
$stemming=false;

# Initialize array for classes to be added to <body> element
$body_classes = array();

# Manage requests automatically using $manage_request_admin[resource type ID] = user ID;
# IMPORTANT: the admin user needs to have permissions R and Rb set otherwise this will not work.
// $manage_request_admin[1] = 1; // Photo
// $manage_request_admin[2] = 1; // Document
// $manage_request_admin[3] = 1; // Video
// $manage_request_admin[4] = 1; // Audio

# Notify on resource change. If the primary resource file is replaced or an alternative file is added, users who have 
# downloaded the resource in the last X days will be sent an email notifying them that there has been a change with a link to the resource view page
# Set to 0 to disable this functionality;
$notify_on_resource_change_days=0;

# Enable this option to display a system down message to all users
$system_down_redirect = false;

# Option for the system to empty the configured temp folder of old files when it is creating new temporary files there.
# Expressed as a number of days. If the age of the temporary folder exceeds this number of days then it will be deleted.
# Set to 0 (off) by default. 
# Please use with care e.g. make sure your IIS/Apache service account doesn't have write access to the whole server
$purge_temp_folder_age=0;

# Set how many extra days a reset password link should be valid for. Default is 1 day 
# Note: this is based on server time. The link will always be valid for the remainder of the current server day. 
# If it is set to 0 the link will be valid only on the same day - i.e. until midnight from the time the link is generated
# If it is set to 1 the link will also be valid all the next day
$password_reset_link_expiry =1;

# Show the resource view in a modal when accessed from search results.
$resource_view_modal=true;

# Option to show other standard pages e.g. resource requests in a modal
$modal_default=false;

# Use the "preview" size on the resource view page
$resource_view_use_pre = false;

# Only use use the larger layout on the view page for certain file extensions.
# $resource_view_large_ext = array("jpg", "jpeg", "tif", "tiff", "gif", "png", "svg");

# Use the larger layout on the view page for landscape images, smaller layout for portrait images.
# NOTE: Enabling $resource_view_large_ext will override this.
$resource_view_large_orientation = true;

# Frequency at which the page header will poll for new messages for the user.  Set to 0 (zero) to disable.
$message_polling_interval_seconds = 10;

# How many times must a keyword be used before it is considered eligable for suggesting, when a matching keyword is not found?
# Set to zero to suggest any known keyword regardless of usage.
# Set to a higher value to ensure only popular keywords are suggested.
$soundex_suggest_limit=10;

# Option for custom access to override search filters.
# For this resource, if custom access has been granted for the user or group, nullify the filter for this particular 
$custom_access_overrides_search_filter=false;

# When requesting a resource or resources, is the "reason for request" field mandatory?
$resource_request_reason_required=true;

# Allow ResourceSpace to upload multiple times the same file in a row
# Set to true only if you want RS to create duplicates when client is losing
# connection with the server and tries again to send the last chunk
$plupload_allow_duplicates_in_a_row = false;

# Create all preview sizes at the full target size if image is smaller (except for HPR as this would result in massive images)
$previews_allow_enlarge=false;

# Option to use a random static image from the available slideshow images. Requires slideshow_big=true;
$static_slideshow_image=false;

#Add usergroup column in my messages/actions area
$messages_actions_usergroup = false;

# User preference - user_pref_resource_notifications. Option to receive notifications about resource management e.g. archive state changes 
$user_pref_resource_notifications=false;
# User preference - user_pref_resource_access_notifications. Option to receive notifications about resource access e.g. resource requests
$user_pref_resource_access_notifications=false;

# Administrator default for receiving notifications about resource access e.g. resource requests. Can't use user_pref_resource_access_notifications since this will pick up setting of requesting user
$admin_resource_access_notifications=false;

# User preference - user_pref_user_management_notifications (user admins only). Option to receive notifications about user management changes e.g. account requests
$user_pref_user_management_notifications=false;
# User preference - user_pref_system_management_notifications (System admins only). Option to receive notifications about system events e.g. low disk space
$user_pref_system_management_notifications=true;

# User preference - email_user_notifications. Option to receive emails instead of new style system notifications where appropriate. 
$email_user_notifications=false;

# User preference - email_and_user_notifications. Option to receive emails and new style system notifications where appropriate.
$email_and_user_notifications=false;

# Execution lockout mode - prevents entry of PHP even to admin users (e.g. config overrides and upload of new plugins) - useful on shared / multi-tennant systems.
$execution_lockout=false;

# Load help page in a modal?
$help_modal=true;

# User preference - if set to false, hide the notification popups for new messages
$user_pref_show_notifications=true;

# User preference - daily digest. Sets the default setting for a daily email digest of unread system notifications.
$user_pref_daily_digest=false; 
# Option to set the messages as read once the email is sent
$user_pref_daily_digest_mark_read=true;

// Accompanying user preference option
$user_pref_inactive_digest = false;

/*
Resource types that cannot upload files. They are only being used to store information. Use resource type ID as values for this array.
By default the preview will default to "No preview" icon. In order to get a resource type specific one, make sure you add it to gfx/no_preview/resource_type/
Note: its intended use is with $pdf_resource_type_templates
*/
$data_only_resource_types = array();

/*
Resource type templates are stored in /filestore/system/pdf_templates
A resource type can have more than one template. When generating PDFs, if there is no request for a specific template,
the first one will be used so make sure the the most generic template is the first one.

IMPORTANT: you cannot use <html>, <head>, <body> tags in these templates as they are supposed
           to work with HTML2PDF library. For more information, please visit: http://html2pdf.fr/en/default
           You also cannot have an empty array of templates for a resource type.

Setup example:
$pdf_resource_type_templates = array(
    2 => array('case_studies', 'admins_case_studies')
);
*/
$pdf_resource_type_templates = array();

#Option to display year in a four digit format
$date_yyyy = false;

# Option to display external shares in standard internal collection view when accessed by a logged in user
$external_share_view_as_internal=false;

/*When sharing externally as a specific user group (permission x), limit the user groups shown only if
they are allowed*/
$allowed_external_share_groups = array();

# When sharing externally as a specific user group (permission x), honor group config options (meant to respect settings like $collection_download).
$external_share_groups_config_options=false;

// CSV Download - add original URL column
$csv_export_add_original_size_url_column = false;

# Prevent users without accounts from requesting resources when accessing external shares. If true, external users requesting access will be redirected to the login screen so only recommended if account requests are allowed.
$prevent_external_requests=false;

/*
Display watermark without repeating it
Possible values for position: NorthWest, North, NorthEast, West, Center, East, SouthWest, South, SouthEast

$watermark_single_image = array(
    'scale'    => 40,
    'position' => 'Center',
);
*/

# $offline_job_queue. Enable the job_queue functionality that runs resource heavy tasks to be run offline and send notifications once complete. Initially used by video_tracks plugin 
# If set to true a frequent cron job or scheduled task should be added to run pages/tools/offline_jobs.php 
# NOTE: This setting may be overridden in certain cirumstances  - e.g. if previews are required at upload time because Google Vision facial recognition is configured with a dependent metadata field
$offline_job_queue=false;
# Delete completed jobs from the queue?
$offline_job_delete_completed=false;


# $replace_resource_preserve_option - Option to keep original resource files as alternatives when replacing resource
$replace_resource_preserve_option=false;
# $replace_resource_preserve_default - if $replace_resource_preserve_option is enabled, should the option be checked by default?
$replace_resource_preserve_default=false;

# Option to allow replacement of multiple resources by filename using the "Replace resource batch" functionality
$replace_batch_existing = false;

# When searching collections, return results based on the metadata of the resources inside also
$collection_search_includes_resource_metadata=false;

# E-mail address to send a report to if any of the automated tests (tests/test.php) fail.
# This is used by Montala to automatically test the RS trunk on a nightly basis.
# $email_test_fails_to="example@example.com";

# Should the alternative files be visible to restricted users (they must still request access to download however)
$alt_files_visible_when_restricted=true;

# Option to prevent resource types specified in array from being added to collections. Will not affect existing resources in collections
# e.g. $collection_block_restypes=array(3,4);
$collection_block_restypes=array();

# Retina mode. Use the "next size up" when rending previews and thumbs for a more crisp display on high resolution screens. Note - uses much more bandwidth also.
$retina_mode=false;

# $xframe_options - set this to DENY (prevent all), SAMEORIGIN or ALLOW-FROM with a URL to allow site to be used in an iframe. To disable completely set to "";
$xframe_options = "SAMEORIGIN";


/*
FSTemplate - File System Template. Allows a system to contain an initial batch of resources that are stored elsewhere 
and read only.
Used by Montala for the ResourceSpace trial account templates, so each templated installation doesn't need to completely
copy all the sample assets.
*/
# Applies to resource IDs BELOW this number only. Set the system so the user created resources start at 1000.
# IMPORTANT: once you've set up the $fstemplate_alt_threshold, run the following query: "alter table resource auto_increment = $fstemplate_alt_threshold"
$fstemplate_alt_threshold=0;
# Alternative filestore location for the sample files. The location of the template installation.
$fstemplate_alt_storagedir="";
$fstemplate_alt_storageurl="";
# The scramble key used by the template installation, so paths must be scrambled using this instead for the sample images.
$fstemplate_alt_scramblekey="";

# Ability to switch off responsive on UI
$responsive_ui = true;

# Default action settings
$actions_enable=false;
# If $actions_enable is false, option to enable actions only for users with certain permissions, To enable actions based on users having more than one permission, separate with a comma.
$actions_permissions=array("a","t","R","u","e0");
$actions_resource_requests=true;
$actions_account_requests=true;
$actions_resource_review=true;
$actions_notify_states="-1";
$actions_resource_types_hide="";  // Resource types to exclude from notifications
$actions_approve_hide_groups=""; // Groups to exclude from notifications

# Option to show action links e.g. user requests, resource requests in a modal
$actions_modal=true;

# Option to allow EDTF format when rendering date range field inputs e.g. 2004-06/2006-08, 2005/2006-02 (see http://www.loc.gov/standards/datetime/pre-submission.html#interval)
$daterange_edtf_support=false;

/*
Mappings between resource types and file extensions.
Can be used to automatically create resources in the system based on
the extension of the file.
*/
$resource_type_extension_mapping_default = 1;
$resource_type_extension_mapping         = array(
    2 => array('pdf', 'doc', 'docx', 'epub', 'ppt', 'pptx', 'odt', 'ods', 'tpl', 'ott' , 'rtf' , 'txt' , 'xml'),
    3 => array('mov', '3gp', 'avi', 'mpg', 'mp4', 'flv', 'wmv'),
    4 => array('flac', 'mp3', '3ga', 'cda', 'rec', 'aa', 'au', 'mp4a', 'wav', 'aac', 'ogg'),
);

# New mode that means the upload goes first, then the users edit and approve resources moving them to the correct stage.
$upload_then_edit=false;

# New upload mode that focuses on getting files into the filestore, then working off a queue for further processing (metadata extract, preview creation, etc).
# requires $offline_job_queue=true;
$upload_then_process=false;

# Uncomment and set to an archive state where $upload_then_process files are stored before processing.
# It is strongly recommended that a unique archive state be created to handle this
# $upload_then_process_holding_state=-3;
# $lang['status-3']="Pending upload processing";

#######################################
########################## Annotations:
#######################################
// Ability to annotate images or documents previews.
// Annotations are linked to nodes, the user needs to specify which field a note is bind to.
$annotate_enabled = false;

// Specify which fields can be used to bind to annotations
$annotate_fields = array();


#######################################
################################  IIIF:
#######################################
// Enable IIIF interface. See http://iiif.io for information on the IIIF standard
// If set to true a URL rewrite rule or similar must be configured on the web server for any paths under the <base_url>/iiif path
$iiif_enabled = false;

// User ID to use for IIIF. This user should be granted access only to those resources that are to be published via IIIF using permissions and search filter
// $iiif_userid = 0;

// Field that is used to hold the IIIF identifier e.g. if using TMS this may be the same as the TMS object field
// $iiif_identifier_field = 29;

// Field that is used to hold the IIIF description
// $iiif_description_field = 0;

// Field that contains license information about the resource
// $iiif_license_field = 0;

// Field that defines the position of a particular resource in the default sequence (only one sequence currently supported)
// $iiif_sequence_field = 1;

// Optional prefix that will be added to sequence identifier - useful if just numeric identifers are used e.g. for different views or pages 
// $iiif_sequence_prefix = "View ";
//
// $iiif_custom_sizes
// Set to true to support Mirador/Universal viewer that requires the ability to request arbitrary sizes by 'w,', ',h' 
// Note that this can result in significantly more storage space being required for each resource published via IIIF
// See https://iiif.io/api/image/2.1 for more information 
$iiif_custom_sizes = false;

$iiif_max_width  = 1024;
$iiif_max_height = 1024;

// Tile settings (currently only used by IIIF when $iiif_level is 1)
$preview_tiles = false;
// Tiles can be generated along with normal previews or created upon request.
// If enabling IIIF on an existing system then it is recommended to add all IIIF published resources to a collection first and use the batch/recreate_previews.php script
$preview_tiles_create_auto = true;
// Size in pixels of the tiles. The same value is used for both tile width and height (see https://iiif.io/api/image/2.1/#region for more info)
$preview_tile_size = 1024;
// Available scale factors (see https://iiif.io/api/image/2.1/#size)
$preview_tile_scale_factors = array(1,2,4,8,16);

/*Prevent client side users to get access to the real path of the resource when ResourceSpace is using filestore URLs.
Rather than use a URL like "http://yourdomain/filestore/1_6326bb8314c6c21/1pre_cf33a61f47b5982.jpg", it will use
the download.php page to give back the file. This prevents users from comming back and download the files after their 
permissions to the assets have been revoked.*/
$hide_real_filepath = false;


#######################################
################### Facial recognition:
#######################################
// Requires OpenCV and Python (version 2.7.6)
$facial_recognition = false;

// Set the field that will be used to store the name of the person suggested/ detected
// IMPORTANT: the field type MUST be dynamic keyword list
$facial_recognition_tag_field = null;

// Physical file path to FaceRecognizer model state(s) and data
// Security note: it is best to place it outside of web root
// IMPORTANT: ResourceSpace will not create this folder if it doesn't exist
$facial_recognition_face_recognizer_models_location = '';
#######################################
#######################################


# Ability to connect to a remote system for the loading of configuration. Can be used to create a multi-instance setup, where one ResourceSpace
# installation can connect to different databases / set different filestore paths depending on the URL, and be driven from a central management
# system that provides the configuration.
#
# # The last 33 characters of the returned config must be an MD5 hash and the key and the previous characters up until, but not including, the hash.
/*
 * For example, on the remote system that serves the configuration, to remotely configure the application name:

    $remote_config_key="abcdef12345";

    $config='
    $applicationname="Test Remote Config ";
    ';
    
    echo $config . "#" . md5($remote_config_key . $config);

 */
# $remote_config_url="http://remote-config.mycompany.com";
# $remote_config_key=""; # The baseurl will be hashed with this key and passed as an &sign= value.

// Option to allow administrators to change the value of the 'contributed by' user for a resource.
$edit_contributed_by = false;

# Option to use decimal (KB, MB, GB in multiples of 1000) vs. binary (KiB, MiB, GiB, TiB in multiples of 1024)
$byte_prefix_mode_decimal=true;

// Social media share buttons
$social_media_links = array("facebook", "twitter", "linkedin");

/*
Set the suffix used to identify alternatives for a particular resource when both the original file and its alternatives
are being uploaded in a batch using the UI (plupload)
IMPORTANT: This will only work if the user uploads all files (resource and its alternatives) into the same 
collection.
*/
$upload_alternatives_suffix = '';

// Set this to true if changing the scramble key. If switching from a non-null key set the $scramble_key_old variable
// Run pages/tools/xfer_scrambled.php to move the files, but any omitted should be detected by get_resource_path() if this is set.
$migrating_scrambled = false;
// $scramble_key_old = "";

##################################################
############### Cross-Site Request Forgery (CSRF):
##################################################
$CSRF_enabled = true;
$CSRF_token_identifier = "CSRFToken";
$CSRF_exempt_pages = array("login");
// Allow other systems to make cross-origin requests. The elements of this configuration option should follow the 
// "<scheme>://<hostname>" syntax
$CORS_whitelist = array();
##################################################
##################################################


/* Font selection */
$global_font="WorkSans";

// Sort tabs alphabetically
$sort_tabs = true;

// Replace the preview of a PDF document with pdfjs viewer if user has full access to the resource. This allows the user 
// to see the original file, having the ability to also search within the document.
// IMPORTANT: enable this per resource type as this is only going to work for PDF files to which user has full access
$use_pdfjs_viewer = false;

// Ask users to opt-in registering to access the system. This can address requirements data protection laws (e.g. GDPR) may have
$user_registration_opt_in = true;

// Purge users - option to disable rather than delete inactive users
$user_purge_disable = false;

// Option to automatically disable inactive users after a set number of days (requires cron.php task to be setup)
$inactive_user_disable_days = 0;

# Option to select metadata field that will be used for downloaded filename (do not include file extension)
#$download_filename_field=8;

/*
Ability to generate an automated title using a specific format. Allows to generate a title using a combination between the 
resource title, its ID and file extension.

Supported placeholders:
 - %title -> replaced with the value of the title field of the resource. This allows 
 - %resource -> replaced with the resource ID
 - %extension -> replaces the actual file extension

Example:
    $auto_generated_resource_title_format = '%title-%resource.%extension';
    $auto_generated_resource_title_format = '2018-2019P - %resource.%extension';
    $auto_generated_resource_title_format = 'Photos - %resource.%extension';

To get the title as the filename on download, the following settings should be set:
$download_filename_field = 8; # Set this to the $view_title_field value
$prefix_filename_string = "";
$prefix_resource_id_to_filename = false;
*/

// When uncommented the extensions listed will be removed from any metadata string at the point it is used in generating a download filename. 
// This will not alter the stored metadata value but provides an option to strip from it given file extensions. 
// It is recommended that metadata containing file extensions is not used in a filename to avoid the administration of this option.
// $download_filename_strip_extensions = array(
//  	'jpg',
//   	'jpeg',
//   	'tif',
//   	'png');

$auto_generated_resource_title_format = '';

// List of extensions for which ResourceSpace should only generate the internal preview sizes.
$non_image_types = array();

// List of extensions supported by ghostscript
$ghostscript_extensions = array('ps', 'pdf');

// Generate only the internal preview sizes and show only the original file for download for any of the 
// extensions found in a merge of $non_image_types, $ffmpeg_supported_extensions, $unoconv_extensions and $ghostscript_extensions list
$non_image_types_generate_preview_only = true;

// Enable updated search filter functionality? Allows for simpler setup of more advanced search filters
// Once enabled the filters will gradually be updated as users search.
// NOTE - from v9.3 onwards, enabling this will also update edit and derestrict filters to use the same filters
// To update all the search filters immediately run upgrade/scripts/005_migrate_search_filters.php
// To update edit and derestrict filters run upgrade/scripts/009_migrate_edit_derestrict_filters.php
$search_filter_nodes = true;

// Browse bar 
// Enable/Disable browse bar - in system config
$browse_bar = true;
// Show workflow (archive) states in browse bar?
$browse_bar_workflow=true;

// Batch replace from local folder
$batch_replace_local_folder = "/upload";

// Option to distribute files in filestore more equally. 
// Setting $filestore_evenspread=true; means that all resources with IDs ending in 1 will be stored under filestore/1, whereas historically (with this set to false) this would contain all resources with IDs starting with 1.
// If enabling this after the system has been in use you can run /pages/tools/filetore_migrate.php which will relocate the existing files into the neew folders
// You may also wish to set the option $filestore_migrate=true; which will force the system to check for a file in the old location and move it in the event that it cannot be found.
$filestore_evenspread=false;
$filestore_migrate=false;


// Set $system_download_config=true if you want to allow admin users to download the config.php file, user and configuration data from your server, optionally including resource data
// Most data will be obfuscated unless you set $system_download_config_force_obfuscation = false
// This requires offline jobs to be enabled
//
// Please note: due to the highly configurable nature of ResourceSpace this obfuscation cannot be guaranteed to remove all traces of sensitive data
// and care must still be taken to keep secure any exported data.
$system_download_config = false;
$system_download_config_force_obfuscation = true;


// Block particular config options from shown in the System Config area.
// E.g. $system_config_hide=array("email_from","email_notify");
$system_config_hide=array();

// Request that search engines don't index the entire installtion
$search_engine_noindex=false;

// Request that search engines don't index external shares only
$search_engine_noindex_external_shares=false;

// Use a tile layout for the user/admin/system menus. If false, use a list layout for menus.
$tilenav=true;

// Maximum number of resources beyond which a CSV metadata export will force an offline job to be created (provided that $offline_job_queue==true)
$metadata_export_offline_limit = 10000;// Optional periodic report size parameters
// Maximum number of rows in an emailed report before it will be added as an attachment. 
// Reports with fewer rows than this will be displayed as a table in the message body
$report_rows_attachment_limit = 100;
// Maximum number of rows in an emailed report before the attachment will be compressed into a zip file
$report_rows_zip_limit = 10000;

// Set sytem-wide read-only system with global permissions mask
// This also stops all offline jobs with the exception of user downloads and stops ResoureSpace sql query logging ($mysql_log_transactions).
$system_read_only = false;

// External upload options
// Optional array of usergroup ids that external collection upload links can be 'shared as' in order to limit metadata field and resource type visibility etc.
$upload_link_usergroups = array();
// Workflow state that will be set for all resources uploaded using the share link
$upload_link_workflow_state = -1;
