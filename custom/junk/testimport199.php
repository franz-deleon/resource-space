<?php

$scramble_key = 'aLaJUpuqyGYM';

//$filename = '/home/abenn/works/rspace/data/load/remoteimport_test.xml';
$scripttag = $_GET['scripttag'];
$filename = "custom/metadata/remoteimport_morevideos.xml";
if ( file_exists($filename) ) {
  echo "<p>{$filename} exists</p>";
}
$f = fopen($filename, 'r');
$xml_source = fread($f,filesize($filename));
$md5r = md5($scramble_key . $xml_source);

?>
<FORM action="plugins/remoteimport/pages/update.php" method="POST">
<input type="hidden" name="xml" value="<?php echo base64_encode($xml_source) ?>"/>
<input type="hidden" name="sign" value="<?php echo $md5r ?>"/>
<input type="submit" name="submit" value="Import resources"/>
</FORM>
