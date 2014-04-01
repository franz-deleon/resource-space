<?php
###############################
## ResourceSpace
## DO NOT COMMIT
###############################

# MySQL database settings
$mysql_server = '';
$mysql_username = '';
$mysql_password = '';
$mysql_db = '';

# Base URL of the installation
$baseurl = "http://{$_SERVER['HTTP_HOST']}";

$storageurl="http://{$_SERVER['HTTP_HOST']}/filestore";
$storagedir=realpath(dirname(__FILE__) . "/../filestore");

$mediadirname="rspace";
$mediaurl="http://stream.media.loc.gov/{$mediadirname}";

# Email settings
$email_from = '';
$email_notify = '';