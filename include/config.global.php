<?php
###############################
## ResourceSpace
## DO NOT COMMIT
###############################

# MySQL database settings
$mysql_server = 'wwc01vlt.loctest.gov';
$mysql_username = 'rstmaster';
$mysql_password = 'R3sourceSp!';
$mysql_db = 'resourcespacetdb';

# Base URL of the ResourceSpace installation
$baseurl = isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}/resourcespace" : "";

# Storage specific settings
$storageurl = isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}/filestore" : "";
$storagedir=realpath(dirname(__FILE__) . "/..") . "/filestore";

# These are mediaapi specific configs
$mediadirname="resourcespace";
$mediaurl="http://stream.media.loc.gov/{$mediadirname}";

# Email settings
$email_from = '';
$email_notify = '';
