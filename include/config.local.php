<?php
###############################
## ResourceSpace
## DO NOT COMMIT
###############################
//require __DIR__ . '/../lib/fdldebug/Bootstrapper.php';

# MySQL database settings
$mysql_server = '192.168.2.2';
$mysql_username = 'resourcespace';
$mysql_password = 'abc123';
$mysql_db = 'resourcespace';

# Base URL of the installation
if (isset($_SERVER['HTTP_HOST'])) {
    $baseurl = ($_SERVER['HTTP_HOST'] == 'osidev-imac2.loctest.gov') ? 'http://osidev-imac2.loctest.gov/resourcespace' : "http://{$_SERVER['HTTP_HOST']}";
}

# Email settings
$email_from = 'fleo@loc.gov';
$email_notify = 'fleo@loc.gov';