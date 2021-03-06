<?php
/**
 * preprocessing script for RS Static sync
 * it reads content from mediaServices schema using mediaApi
 * and puts physical mediafiles with their metada in a certain directory structure
 * that staticsync script expects
 *
 * @author Muhibo Yusuf <myus@loc.gov>
 *
 */
include_once("rspaceapi.php");
include("classes/FieldMap.php");
include("classes/MediaApiClient.php");
include("config/config.php");
include("classes/IngestTracking.php");
include("classes/IngestLogger.php");


/**
 * queries the RS schema to fetch the media already ingested.
 * @return array
 */
function getIngestedResources()  {
    $results = sql_query("select r.ref, rd.value from resource r LEFT JOIN resource_data rd on r.ref = rd.resource where rd.resource_type_field = 77");
    $map = array();
    foreach ( $results as $result ) {
        $map[$result['value']] = $result['ref'];
    }
    return $map;
}
/**
 *
 * @param array Media
 * @return boolean
 */
function isValid($media) {
    if(!empty($media["derivatives"]) && isset($media['uuid'])) {
        return true;
    }
    return false;
}

/**
 *
 * @param type $arr
 * @param type $absoluteFileName
 * Writes json metadata to a file
 */
function writeMetadata($arr, $absoluteFileName) {
    $metaHandle = fopen($absoluteFileName, "w");
    fwrite($metaHandle, json_encode($arr));
    fclose($metaHandle);
}

/***
 *
 */
function downloadThumbNail($url,$destinationDir, $uuid) {
   if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
       IngestLogger::writeEntry("Not valid URL\t UUID: $uuid\t URL: $url",'Info');
        return;
    }
    $arr = parse_url($url);
    $path_parts = pathinfo($arr["path"]);
    $filename = $uuid. "." . $path_parts["extension"];
   if(remoteFileExists($url)) {
        file_put_contents("$destinationDir/$filename", file_get_contents($url));
       

   }
}

function arguments($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
      if (preg_match('/--([^=]+)=(.*)/',$arg,$reg)) {
        $_ARG[$reg[1]] = $reg[2];
      } elseif(preg_match('/^-([a-zA-Z0-9])/',$arg,$reg)) {
            $_ARG[$reg[1]] = 'true';
      } else {
            $_ARG['input'][]=$arg;
      }
    }
  return $_ARG;
}

/**
 * 
 *
 * @param type $url
 * @return boolean
 */
function remoteFileExists($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    $result = curl_exec($curl);
    $ret = false;
    //if request did not fail
    if ($result !== false) {
        //if request was ok, check response code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode == 200) {
            $ret = true;
        }
    }

    curl_close($curl);

    return $ret;
}



/**
 * set up directory paths for data transfer
 */


$args = arguments($argv);

if(isset($args['reset']) && $args['reset']=='true') {
    $result = sql_query("delete from ingest_tracking where id>1");
   IngestLogger::writeEntry("Resetting the IngestTracking Table",'Info');
    system("rm -rf $syncDirectory" . "/*");

}
$existingResources =  getIngestedResources();


$ingestTracking = new IngestTracking();
$lastPageProcessed = $ingestTracking->getLastPageNumberProcessed();
//$lastPageProcessed = 1734;
$lastPageProcessed = 1859; // want to process webcasts which have ccUrl

  $page = $lastPageProcessed+1;
  $numpages_in_this_run = 0;
  $itemsProcessed = 0;
  $max_pages = 1;
  do {
      IngestLogger::writeEntry("processing page $page",'Info');
      $params = array(
         'page' => $page,
         //'uuid' => 'EA7526E15DBD0226E0438C93F0280226',
      );
      $mediaApiClient = new MediaApiClient($mediaUrl);
      $mediaArray = json_decode($mediaApiClient->getMedia($params), true);
      $headers = $mediaApiClient->getHeaders();
      $mediaArray = empty($headers['item-count']) ? array($mediaArray) : $mediaArray;
      $pageCount =  $mediaApiClient->getPageCount();

      foreach ($mediaArray as $media) {
          if (isValid($media)) {
            $uuid = $media['uuid'];
            if(isset($existingResources[$uuid])) {
                IngestLogger::writeEntry("$uuid already exists in RS skipping..",'Info');
                continue;
            }
       
        $firstDerivativeFileName =$media["derivatives"][0]["fileName"] . ".". $media["derivatives"][0]["fileExtension"];
        $fdFullPathName = $sourcemediaFileBaseDir . "/" . $media['derivatives'][0]['filePath'] . "/" . $media["derivatives"][0]["fileName"] . ".". $media["derivatives"][0]["fileExtension"];
        $thumbnailUrl = $media['thumbnailUrl'];
        $ccUrl = $media['ccUrl'];
        $destinationPath = "$syncDirectory/$uuid";
        //make directory for this media
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, TRUE);

        } 
        $metaDataFile = $destinationPath . "/" . $uuid . ".json";
        //write metadata
        writeMetadata($media, $metaDataFile);
    
        if(!empty($thumbnailUrl)) {
            downloadThumbNail($thumbnailUrl,$destinationPath, $uuid);
        }
        if(!empty($ccUrl)) {
            downloadThumbNail($ccUrl, $destinationPath, $uuid);
        }
     
        //copy main derivative
        if(file_exists($fdFullPathName)) {
            copy($fdFullPathName, $destinationPath . "/$firstDerivativeFileName"); 
            //get thumbnail
       
        } else {
            IngestLogger::writeEntry("file: $fdFullPathName doesn't exist skipping",'data');
            continue;
        }
        $itemsProcessed++;
        //copy all derivatives to the alternativeFile directory
        $derivativeDir = $destinationPath . "/" . $firstDerivativeFileName . "_derivatives";
        if (!is_dir($derivativeDir)) {
            mkdir($derivativeDir, 0777, TRUE);
        }   
            
            $metaDataFile = $destinationPath . "/" . $uuid . ".json";
            //write metadata
            writeMetadata($media, $metaDataFile);

            if(!empty($thumbnailUrl)) {
                downloadThumbNail($thumbnailUrl,$destinationPath, $uuid);
            }

            //copy main derivative
            if(file_exists($fdFullPathName)) {
                copy($fdFullPathName, $destinationPath . "/$firstDerivativeFileName");
                //get thumbnail

            } else {
                IngestLogger::writeEntry("file: $fdFullPathName doesn't exist skipping",'data');
                continue;
            }
            $itemsProcessed++;
            //copy all derivatives to the alternativeFile directory
            $derivativeDir = $destinationPath . "/" . $firstDerivativeFileName . "_derivatives";
            if (!is_dir($derivativeDir)) {
                mkdir($derivativeDir, 0777, TRUE);

            }
            foreach($media["derivatives"] as $derivative) {
                $sourceDerivative = $sourcemediaFileBaseDir . "/" .$derivative["filePath"] . "/" . $derivative["fileName"] . "." . $derivative["fileExtension"];
                $destinationDerivative = $derivativeDir . "/" . $derivative['fileName'] . "." . $derivative['fileExtension'];
                if (file_exists($sourceDerivative)) {
                    $derivativeMetataFile = $destinationDerivative . ".json";
                    copy($sourceDerivative, $destinationDerivative);
                    writeMetadata($derivative, $derivativeMetataFile);
                } else {
                    IngestLogger::writeEntry("Derivative: $sourceDerivative doesn't exist skipping",'Info');
                    continue;
                }
            }
           
        }
  }

  $data["last_page_number_processed"] = $page;
  $data["total_number_of_records_processed"] = $itemsProcessed;
  $page++;
  $numpages_in_this_run++;

  } while ($page<=$pageCount && $numpages_in_this_run<$max_pages);

  $ingestTracking->writeData($data);
  IngestLogger::writeEntry("DONE: Processed $itemsProcessed",'Info');
