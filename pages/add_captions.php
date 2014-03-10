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

# Handle adding a new file
if ($_FILES)
	{
        if (array_key_exists("newfile",$_FILES))
        	{
            	# Fetch filename / path
            	$processfile=$_FILES['newfile'];
           	    $filename=strtolower(str_replace(" ","_",$processfile['name']));

            	# Work out extension
            	$extension=explode(".",$filename);$extension=trim(strtolower($extension[count($extension)-1]));

            	$new_cap_res=copy_resource($ref, $resource_data['resource_type']);

        		# Find the path for this resource.
            	$path=get_resource_path($new_cap_res, true, "", true, $extension, -1, 1, false, "");
            	$title = getvalescaped('name', 'caption');

            	update_resource($new_cap_res,$path,$resource_data['resource_type'],$title,false,false);

            	# update the related resources
            	mediaapi_update_related_resource($ref, $new_cap_res);

            	# add to the cc url
            	$cc_url = $storageurl . substr($path, strpos($path, 'filestore/') + 9);
            	mediaapi_update_resource_data($ref, 88, $cc_url);

        		# Debug
        		debug("Uploading alternative file $ref with extension $extension to $path");

        		if ($filename!="")
        			{
        			$result=move_uploaded_file($processfile['tmp_name'], $path);
        			if ($result==false)
        				{
        				exit("File upload error. Please check the size of the file you are trying to upload.");
        				}

        			# Log this
        			resource_log($ref,"b","",$ref . ": " . getvalescaped("name","") . ", " . getvalescaped("description","") . ", " . escape_check($filename));
        			}
    		}
	}


include "../include/header.php";
?>

<!--Create a new file-->
<div class="BasicsBox">
    <h1>Add captions file</h1>
    <form method="post" enctype="multipart/form-data"  action="<?php echo $baseurl_short?>pages/add_captions.php">

        <input type="hidden" name="MAX_FILE_SIZE" value="500000000">
        <input type=hidden name=ref value="<?php echo htmlspecialchars($ref) ?>">

        <div class="Question">
        <label><?php echo $lang["resourceid"]?></label><div class="Fixed"><?php echo htmlspecialchars($ref) ?></div>
        <div class="clearerleft"> </div>
        </div>

        <div class="Question">
        <label for="name"><?php echo $lang["name"]?></label><input type=text class="stdwidth" name="name" id="name" value="Caption for resource <?php echo $ref; ?>" maxlength="100">
        <div class="clearerleft"> </div>
        </div>

        <div class="Question">
        <label for="name"><?php echo $lang["description"]?></label><input type=text class="stdwidth" name="description" id="description" value="" maxlength="200">
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
include "../include/footer.php";
?>
