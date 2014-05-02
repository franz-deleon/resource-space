<?php
###############################
## ResourceSpace
## Local Configuration Script
###############################

# All custom settings should be entered in this file.
# Options may be copied from config.default.php and configured here.

# MySQL database settings
$mysql_server = 'wwc01vlt.loctest.gov';
$mysql_username = 'rstmaster';
$mysql_password = 'R3sourceSp!';
$mysql_db = 'resourcespacetdb';

# Base URL of the ResourceSpace installation
$baseurl = isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}/resourcespace" : "";

# Storage specific settings
$storageurl = isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}/filestore" : "";
$storagedir = realpath(dirname(__FILE__) . "/..") . "/filestore";

# These are mediaapi specific configs
$mediadirname = "resourcespace";
$mediadomain = "http://stream.media.loc.gov";
$mediaurl = "{$mediadomain}/{$mediadirname}";

# Email settings
$email_from = '';
$email_notify = '';

#static sync
$syncdir = "/tmp/resourcespace/ingest"; # The sync folder
$staticsync_alternatives_suffix="_derivatives";
$staticsync_mapfolders[] = array(
    "match"=>"/mediaFiles",
    "field"=>"Restricted",
    "level"=>2
);

$spider_password = 'qeMA3E5YraPy';
$scramble_key = 'nyNeGy8A6uNy';

$api_scramble_key = '7YnAGenUmA4y';

# If using FFMpeg to generate video thumbs and previews, uncomment and set next line.
$ffmpeg_path='/usr/bin';

$resource_deletion_state=3;

# Does deleting resources require password entry? (single resource delete)
$delete_requires_password=false;

$collection_purge=true;

$collections_delete_empty=true;

# Paths
$imagemagick_path = '/usr/bin';
$ghostscript_path = '/usr/bin';
$ffmpeg_path = '/usr/bin';
$ffmpeg_snapshot_seconds=12;
$ghostscript_executable = 'gs';
$exiftool_path = '/usr/bin';
$antiword_path = '/usr/bin';
$pdftotext_path = '/usr/bin';

$defaultlanguage = 'en-US';
$ftp_server = 'my.ftp.server';
$ftp_username = 'my_username';
$ftp_password = 'my_password';
$ftp_defaultfolder = 'temp/';
$thumbs_display_fields = array(8,3);
$list_display_fields = array(8,3,12);
$sort_fields = array(12);
$imagemagick_colorspace = "sRGB";

$defaulttheme="greyblu";

# ------------------------------------------------------------------------------------------------------------------
# StaticSync (staticsync.php)
# The ability to synchronise ResourceSpace with a separate and stand-alone filestore.
# ------------------------------------------------------------------------------------------------------------------
$nogo="[folder1]"; # A list of folders to ignore within the sign folder.
$staticsync_autotheme=false; # Automatically create themes based on the first and second levels of the sync folder structure.
# Allow unlimited theme levels to be created based on the folder structure.
# Script will output a new $theme_category_levels number which must then be updated in config.php
$staticsync_folder_structure=false;
# Mapping extensions to resource types for sync'd files
# Format: staticsync_extension_mapping[resource_type]=array("extension 1","extension 2");
$staticsync_extension_mapping_default=1;
$staticsync_extension_mapping[2]=array("xml"); # Document
$staticsync_extension_mapping[3]=array("mov","3gp","avi","mpg","mp4","flv"); # Video
$staticsync_extension_mapping[4]=array("flv","mp3"); #audio
# Uncomment and set the next line to specify a category tree field to use to store the retieved path information for each file. The tree structure will be automatically modified as necessary to match the folder strucutre within the sync folder.
# $staticsync_mapped_category_tree=50;
# Should the generated resource title include the sync folder path?
$staticsync_title_includes_path=false;
# Should the sync'd resource files be 'ingested' i.e. moved into ResourceSpace's own filestore structure?
# In this scenario, the sync'd folder merely acts as an upload mechanism. If path to metadata mapping is used then this allows metadata to be extracted based on the file's location.
$staticsync_ingest=true;
#
# StaticSync Path to metadata mapping
# ------------------------
# It is possible to take path information and map selected parts of the path to metadata fields.
# For example, if you added a mapping for '/projects/' and specified that the second level should be 'extracted' means that 'ABC' would be extracted as metadata into the specified field if you added a file to '/projects/ABC/'
# Hence meaningful metadata can be specified by placing the resource files at suitable positions within the static
# folder heirarchy.
# Use the line below as an example. Repeat this for every mapping you wish to set up
	$staticsync_mapfolders[]=array
		(
		"match"=>"/projects/",
		"field"=>10,
		"level"=>2
		);
#
# in the default local language. Note that custom access levels are not supported. For example, the mapping below would set anything in
# the projects/restricted folder to have a "Restricted" access level.
#	$staticsync_mapfolders[]=array
#		(
#		"match"=>"/projects/restricted",
#		"field"=>"Restricted",
#		"level"=>2
#		);
#
# Suffix to use for alternative files folder
# If staticsync finds a folder in the same directory as a file with the same name as a file but with this suffix appended, then files in the folder will be treated as alternative files for the give file.
# For example a folder/file structure might look like:
# /staticsync_folder/myfile.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative1.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative2.jpg
# /staticsync_folder/myfile.jpg_alternatives/alternative3.jpg
# NOTE: Alternative file processing only works when $staticsync_ingest is set to 'true'.

# if false, the system will always synthesize a title from the filename and path, even
# if an embedded title is found in the file. If true, the embedded title will be used.
$staticsync_prefer_embedded_title = true;

# End of StaticSync settings
# ------------------------------------------------------------------------------------------------------------------

if (file_exists(__DIR__ . '/config.local.php')) require 'config.local.php';