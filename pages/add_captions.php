<?php
include "../include/db.php";
include "../include/authenticate.php";
include "../include/general.php";
include "../include/resource_functions.php";
include "../include/image_processing.php";

$ref=getvalescaped("ref","");

$search=getvalescaped("search","");
$offset=getvalescaped("offset","",true);
$order_by=getvalescaped("order_by","");
$archive=getvalescaped("archive","",true);
$restypes=getvalescaped("restypes","");
if (strpos($search,"!")!==false) {$restypes="";}

$default_sort="DESC";
if (substr($order_by,0,5)=="field"){$default_sort="ASC";}
$sort=getval("sort",$default_sort);


# Fetch resource data.
$resource_data=get_resource_data($ref);

# Not allowed to edit this resource?
if ((!get_edit_access($ref,$resource_data["archive"], false,$resource_data) || checkperm('A')) && $ref>0) {exit ("Permission denied.");}

hook("pageevaluation");

$save_errors = array();

# Handle adding a new file
if ($_FILES)
	{
        if (array_key_exists("newfile",$_FILES))
        	{
            	# Fetch filename / path
            	$processfile=$_FILES['newfile'];
            	if ($processfile['error'] === 0) {
            	    $type_mapping = array(
            	       88 => 'caption',
            	       99 => 'transcript',
            	    );

            		$type=getval('url_type', 'caption');
            	    $resource_type_field_id = array_search($type, $type_mapping);
            	    $resource_type_id = mediaapi_get_resource_type_id($type);
                	$filename=strtolower(str_replace(" ","_",$processfile['name']));

                	# Work out extension
                	$extension=explode(".",$filename);$extension=trim(strtolower($extension[count($extension)-1]));

                	$new_cap_res=create_resource($resource_type_id);

            		# Find the path for this resource.
                	$path=get_resource_path($new_cap_res, true, "", true, $extension, -1, 1, false, "");
                	$title = ($title=getvalescaped('name', null) && !empty($title)) ? $title : "{$type} for resource id: {$ref}";

                	update_resource($new_cap_res,$path,$resource_type_id,$title,false,false);

                	//mediaapi_extract_text_from_file($ref, '/vagrant/www/resourcespace/public/filestore/3/6/5_2d25ac3705718a6/365_a670555dfe55a1a.xml'); // 72 is extracted_txt

                	# update the related resources
                	mediaapi_delete_related_resource_of_type($ref, $resource_type_id);
                	mediaapi_update_related_resource($ref, $new_cap_res);

                	# add to the cc url
                	$storagedirbasename = basename($storagedir);
                	$url                = rtrim($mediaurl, '/ ') . substr($path, strpos($path, $storagedirbasename) + strlen($storagedirbasename));

                	mediaapi_update_resource_data($ref, $resource_type_field_id, $url);

            		if ($filename!="")
            			{
            			$result=move_uploaded_file($processfile['tmp_name'], $path);
            			if ($result==false)
            				{
            				exit("File upload error. Please check the size of the file you are trying to upload.");
            				}

            		    mediaapi_insert_extract_text_from_file($new_cap_res, $path); // 72 is extracted_txt

            			# Log this
            			resource_log($ref,"b","",$ref . ": " . getvalescaped("name","") . ", " . getvalescaped("description","") . ", " . escape_check($filename));
            			}

                    redirect ($baseurl_short."pages/edit.php?ref=$ref&search=".urlencode($search)."&offset=$offset&order_by=$order_by&sort=$sort&archive=$archive");
            	} else {
            	    $save_errors[] = "Problem uploading file.";
            	}
    		}
	}


include "../include/header.php";
?>

<!--Create a new file-->
<div class="BasicsBox">
    <h1>Add captions/transcripts file</h1>
    <form method="post" enctype="multipart/form-data"  action="<?php echo $baseurl_short?>pages/add_captions.php">

        <input type="hidden" name="MAX_FILE_SIZE" value="500000000">
        <input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">

        <div class="Question">
        <label><?php echo $lang["resourceid"]?></label><div class="Fixed"><?php echo htmlspecialchars($ref) ?></div>
        <div class="clearerleft"> </div>
        </div>

        <div class="Question">
        <label for="name"><?php echo $lang["name"]?></label><input type=text class="stdwidth" name="name" id="name" value="" maxlength="100">
        <div class="clearerleft"> </div>
        </div>

        <div class="Question">
        <label for="name">URL type</label>
            <select class="stdwidth" name="url_type" id="type">
               <option value="caption">Caption</option>
        	   <option value="transcript">Transcript</option>
        	</select>
        <div class="clearerleft"> </div>
        </div>

        <div class="Question">
        <label for="name">Extracted Text <br /><small>(Leave blank to populate automatically)</small></label><textarea name="field_72" rows="10" cols="48"></textarea>
        <div class="clearerleft"> </div>
        </div>

        <div class="Question">
        <label for="userfile"><?php echo $lang["uploadreplacementfile"] ?></label>
        <input type="file" name="newfile" id="newfile" size="80">
        <div class="clearerleft"> </div>
        </div>

        <div class="Inline"><input name="Submit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["create"]?>&nbsp;&nbsp;" /></div>
	</form>
</div>

<?php
foreach ($save_errors as $save_error_field=>$save_error_message)
	{
	?>
    <script type="text/javascript">
    alert('<?php echo htmlspecialchars($save_error_message) ?>');
    </script><?php
    }

include "../include/footer.php";
?>
