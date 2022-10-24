<?php
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  }
  $id= 2;
  //$type;
  
  if (isset($_GET['id'])) {
    $getID = preg_replace('#[^0-9]#', '', $_GET['id']);
    $type = $_GET['type'];
    $photosDisplay = getPhotos($getID, $type);
    $title;
    
    if ($type == 'event') {
      $sqlGetTitle = mysqli_query($link, "SELECT title ,type FROM locations WHERE id='$getID' AND active='y' LIMIT 1");
      while ($row = mysqli_fetch_array($sqlGetTitle)) { $eventTitle = $row["title"]; $eventType = $row['type']; }
      isset($eventTitle) ? $title = $eventTitle : $title = $eventType;
    } else {
      $username = getUsername($getID);
      $title = $username . '\'s Photos'; 
    }
  } else if (isset($_POST['event'])) {//allows members to upload files if on event page, not on user photo page
    if (isset($id)) {
      $getID = preg_replace("#[^0-9]#", "", $_POST["event"]);
      $errorMsg;
      $targetPath = "events/$getID";
      $fileName = $_FILES["uploaded_file"]["name"]; // The file name
      $fileTmpLoc = $_FILES["uploaded_file"]["tmp_name"]; // File in the PHP tmp folder
      $fileType = $_FILES["uploaded_file"]["type"]; // The type of file it is
      $fileSize = $_FILES["uploaded_file"]["size"]; // File size in bytes
      $fileErrorMsg = $_FILES["uploaded_file"]["error"]; // 0 = false | 1 = true
      $pathSuffix = pathinfo($fileName);
      $pathExt = $pathSuffix['extension'];
      echo $pathExt;

      if (!$fileTmpLoc) { // if file not chosen
        $errorMsg = '<span class="red">ERROR:</span> Please browse for a file before clicking the upload button.';
      } else if($fileSize > 35125441) { // if file size is larger than 35 Megabytes
        $errorMsg = '<span class="red">ERROR:</span> Your file was larger than 35 Megabytes in size.';
        unlink($fileTmpLoc); // Remove the uploaded file from the PHP temp folder
	  } else if (!preg_match("/(jpg|png|jpeg|zip)$/i", $pathExt)) {   
         $errorMsg = '<span class="red">ERROR:</span> Your image was not .gif, .jpg, .png, .jpeg or .zip';
         unlink($fileTmpLoc); 
	  } else if ($fileErrorMsg == 1) { // if file upload error key is equal to 1
        $errorMsg = '<span class="red">ERROR:</span> An error occured while processing the file. Try again.';
	  }
	  
	  //$moveResult = copy($fileTmpLoc, $targetPath . '/' . $fileName);
      
      //this will be directory that will be made if zip file
      $zipPath = $targetPath . '/pics';
      
      if ($pathExt == 'zip') {
        $zip = new ZipArchive;
        $res = $zip->open($fileTmpLoc);
        if ($res === TRUE) {
          mkdir($zipPath);
  		  $zip->extractTo($zipPath);
  		  $zip->close();
  		  //$errorMsg = "File Extracted SuccessFully";
	    } else {
  		  $errorMsg = '<span class="red">Error:</span> File not uploaded. Try again.';
	    }
      }
	  
	  //check if zip path exists
	  if (is_dir($zipPath)) {
	    if ($handle = opendir($zipPath)) {
          while (false !== ($entry = readdir($handle))) {
            if (!is_dir($entry)) {
              if (preg_match("/.(jpg|png|jpeg)$/i", $entry)) {
                $kaboom = explode(".", $entry);
                 $fileName = time().rand() . "." . end($kaboom);
                 if ($moveResult = copy($zipPath . '/' . $entry, $targetPath . '/' . $fileName)) {
                  $errorMsg = cropPhoto($targetPath, $fileName, $getID, $id, $pathSuffix['extension']);
                }
              }   
            } else if (is_dir($entry)) {
              $errorMsg = "<span class=\"red\">Error:</span> If your uploading zip file, please place all photos outside of directory.";
              /*if ($handle1 = opendir($entry)) {
                while (false !== ($entry1 = readdir($handle1))) {
                  if (preg_match("/.(jpg|png|jpeg)$/i", $entry1)) {
                    $kaboom = explode(".", $entry1);
                    $fileName = time().rand() . "." . end($kaboom);
                    $directory = $zipPath . '/' . dirname($entry) . '/' . $entry1;
                    echo $directory .'<br />';
                    if ($moveResult = copy($directory, $targetPath . '/' . $fileName)) {
                      $errorMsg = cropPhoto($targetPath, $fileName, $getID, $id, $pathSuffix['extension']);
                    }
                  }
                }
              }*/
            } else {
              $errorMsg = '<span class="red">Error:</span> There doesn\'t appear to be any photos in the zip file.';
            }
          }//end while (false !== ($entry = readdir($handle)))
          if(is_dir($zipPath)) { system("rm -rf $zipPath"); }
	    }//end if ($handle = opendir($zipPath))
	  } else {
        $fileName = time().rand() . "." . $pathExt;
        $placeFile = move_uploaded_file($fileTmpLoc, $targetPath . '/' . $fileName);
	 
        if ($placeFile != true) {
          @unlink($fileTmpLoc); 
          $errorMsg = '<span class="red">ERROR:</span> File not uploaded. Try again.';
        }
	    $errorMsg = cropPhoto($targetPath, $fileName, $getID, $id, $pathExt);
	  }
	  $photosDisplay = getPhotos($getID, "event");
    } else {
      echo "No Bueno";
    }
  }

?>
<?php include_once("inc/head.inc.php"); ?>
  <style>
    .thumbs { 
      margin-left:auto; 
      margin-right:auto; 
      margin-top: 0px;  
      padding: 10px 5px 0px 5px; 
    }
    img { 
      padding-left: 4px; 
      padding-right: 4px;
    }
  </style>
  <script src="scripts/jquery-1.8.1.min.js"></script>
  <script language="javascript" type="text/javascript">
    function sendPhoto() {
      var formData = $('#photoForm').serialize();
	  var url = "photos.php";
	  if ($("#uploaded_file").val() == "") {
        $("#interactionResults").html('&nbsp; <span class="red">ERROR:</span> Nothing was uploaded.').show().fadeOut(6000);
      } else {
        $.post(url, formData, function(data) {
          $("#return_message").html(data).show();
        });//end post
      }
    }
  </script>
</head>
<body>
  <?php echo isset($title) ? '<h2>' . $title . '</h2>' : ''; ?>
  <?php if (isset($id) && isset($getID) && $type = 'event') { ?>
  <div id="return_message"><?php echo isset($errorMsg) ? $errorMsg : ''; ?></div>
  <form action="photos.php" enctype="multipart/form-data" method="post" id="photoForm">
    Were you at this event and have a photo to upload? Do it here!<br /><span class="grey_color">.jpg, .jpeg and .png only please</span><br />
    <input type="file" name="uploaded_file" id="uploaded_file"/><br />
    <input type="hidden" name="event" id="event" value="<?php echo $getID; ?>" />
	<input type="submit" name="button" id="button" value="Upload It"/>
	<div id="interactionResults" style="font-size:15px; padding:10px;"></div>
  <?php } ?>
  </form>
  <?php if (isset($getID)) { ?>
    <div id="photo_display"><?php echo $photosDisplay != '' ? $photosDisplay : '<div id="no_content">No one has uploaded any photos yet.<br /> If you\'re registered, Be the first!</div>'; ?></div>
  <?php } ?>
</body>
</html>