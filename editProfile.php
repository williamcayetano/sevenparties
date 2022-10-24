<?php  
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  
  /*if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
  } else {
    header('location: index.php');
  }*/
  $id= 2;
  
  $countryArray = array("United States", "Australia", "Canada", "United Kingdom");
  $errorMsg = '';
  $firstName = '';
  $lastName = '';
  $website = '';
  $bioBody = '';
  $city = '';
  $state = '';
  
   $sqlGetInfo = mysqli_query($link, "SELECT * FROM members WHERE id='$id' AND active='y' LIMIT 1");
   while ($row = mysqli_fetch_array($sqlGetInfo)) {
     $userID = $row['id'];
     $userPic = checkUserPic($userID);
     $firstName = htmlkarakter($row['first_name']);
     $lastName = htmlkarakter($row['last_name']);
     $website = htmlkarakter($row['website']);
     $city = htmlkarakter($row['city']);
     $state = htmlkarakter($row['state']);
     $selectedCountry = $row['country'];
     $bioBody = htmlkarakter($row['bio_body']);
        
     $selectedCountry != "" ? $selectedCountry : $selectedCountry = '--';
  }
  
  if (isset($_POST['button']) && isset($id)) {
    if ($_FILES['fileField']['name']) {
      $fileName = $_FILES['fileField']["name"];
      $targetPath = "members/$id";
      $fileTmpLoc = $_FILES['fileField']["tmp_name"];
      //$fileType = $_FILES['fileField']["type"]; 
	  $fileSize = $_FILES['fileField']["size"]; 
	  $fileErrorMsg = $_FILES['fileField']["error"];  
	  $kaboom = explode(".", $fileName); 
	  $fileExt = end($kaboom); 
	  // Start PHP Image Upload Error Handling --------------------------------------------------
	  if (!$fileTmpLoc) { // if file not chosen
      	$errorMsg = '<span class="red">ERROR:</span> Please browse for a file before clicking the upload button.';
	  } else if($fileSize > 3000000) { // if file size is larger than 5 Megabytes
      	$errorMsg = '<span class="red">ERROR:</span> Your file was larger than 35 Megabytes in size.';
        unlink($fileTmpLoc); // Remove the uploaded file from the PHP temp folder
	  } else if (!preg_match("/.(jpg|png|jpeg)$/i", $fileName) ) { 
     	 $errorMsg = '<span class="red">ERROR:</span> Your image was not .jpg, .png, or .jpeg';
         unlink($fileTmpLoc); 
	  } else if ($fileErrorMsg == 1) { // if file upload error key is equal to 1
      	 $errorMsg = '<span class="red">ERROR:</span> An error occured while processing the file. Try again.';
	  } else {
		$newName = "image01.jpg";
        $place_file = move_uploaded_file($fileTmpLoc, "members/$id/".$newName);
        
        //resize image
	    include_once("scripts/ak_php_img_lib_2.0.php");
	    $target_file = "members/$id/$newName";
	    $resized_file = "members/$id/$newName"; //you can change this to any other folder to separate smaller images
	    $wmax = 150;
	    $hmax = 200;
	    @ak_img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	    header('location: profile.php?id=' . $id);
	    //$errorMsg .= ' * Profile pic updated<br />';
	  }
    }
  }
    
  if (isset($_POST['firstName']) && isset($id)) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    if ($firstName != "" && $lastName != "") {
      $firstName = clean($firstName);
      $lastName = clean($lastName);
      $firstName = substr($firstName, 0, 15);//15 char limit on first/last name
      $lastName = substr($lastName, 0, 15);
      $sqlNameUpdate = mysqli_query($link, "UPDATE members SET first_name='$firstName', last_name='$lastName' WHERE id='$id'");
      if (!$sqlNameUpdate) {
	    $errorMsg = '<span class="red">ERROR:</span> Problems connecting to server, please try again later.';
	  } else {
	    header('location: profile.php?id=' . $id);
	    //$errorMsg .= ' * First and last name updated<br />';
	  }
    }  
  }
    
  if (isset($_POST['website']) && isset($id)) {
    $website = $_POST['website'];
    
    if ($website != "") {
      $website = strtolower($website);
      $website = preg_replace("%http://%", '', $website);
      $website = clean($website);
      $sqlWebUpdate = mysqli_query($link, "UPDATE members SET website='$website' WHERE id='$id'");
      if (!$sqlWebUpdate) {
	    $errorMsg = '<span class="red">ERROR:</span> Problems connecting to server, please try again later.';
	  } else {
	    header('location: profile.php?id=' . $id);
	    //$errorMsg .= ' * Website updated<br />';
	  }
	}
  }
  
  if (isset($_POST['city']) && isset($id)) {
    $city = $_POST['city'];
    $state = $_POST['state'];
    $country = $_POST['country'];
    
    if ($city != "" && $state != "" && $country != "") {
      $city = clean($city);
      $state = clean($state);
      $country = clean($country);
      $sqlLocUpdate = mysqli_query($link, "UPDATE members SET city='$city', state='$state', country='$country' WHERE id='$id'");
      if (!$sqlLocUpdate) {
	    $errorMsg = '<span class="red">ERROR:</span> Problems connecting to server, please try again later.';
	  } else {
	    header('location: profile.php?id=' . $id);
	    //$errorMsg .= ' * Location updated<br />';
	  }
    }
  }
    
  if (isset($_POST['bioBody']) && isset($id)) {
    $bio = $_POST['bioBody'];
    if ($bio != "") {
      $bio = clean($bio);
      $sqlBioUpdate = mysqli_query($link, "UPDATE members SET bio_body='$bio' WHERE id='$id'");
      if (!$sqlBioUpdate) {
	    $errorMsg = '<span class="red">ERROR:</span> Problems connecting to server, please try again later.';
	  } else {
	    header('location: profile.php?id=' . $id);
	    //$errorMsg .= ' * Bio updated<br />';
	  }
	}
  }
  
?>

<?php include_once("inc/head.inc.php"); ?>
  <style>
    #content_wrapper {
      color: white;
    }
  </style>
  <script src="scripts/jquery-1.8.1.min.js"></script>
  <script>
    function update() {
      var formData = $('#editForm').serialize();
    
    }
  </script>
</head>
<body>
  <div id="content_wrapper">
    <h2>Edit Your Profile Data Here</h2>
      <a href="editSettings.php">Edit Account Settings</a>
    <h3><?php echo $errorMsg; ?></h3>
    <form action="editProfile.php" enctype="multipart/form-data" method="post" name="editForm" id="editForm">
    <hr class="horizontal" />
      Load Photo:<input type="file" name="fileField" id="fileField" class="formFields" size="42" />
      <hr class="horizontal" />
      First Name:<input name="firstName" type="text" class="formFields" id="firstName" value="<?php print "$firstName"; ?>" size="12" maxlength="20" />&nbsp;&nbsp;
      Last Name:<input name="lastName" type="text" class="formFields" id="lastName" value="<?php print "$lastName"; ?>" size="12" maxlength="20" /> 
      <hr class="horizontal" />
      City:<input name="city" type="text" class="formFields" id="city" value="<?php print "$city"; ?>" size="12" maxlength="20" />&nbsp;&nbsp;
      State/Territory/Province:<input name="state" type="text" class="formFields" id="state" value="<?php print "$state"; ?>" size="12" maxlength="20" /> 
      Country:<select name="country"><?php print dropDown($countryArray, $selectedCountry); ?></select>
      <hr class="horizontal" />
      Website:&nbsp;&nbsp;&nbsp;&nbsp;http://<input name="website" type="text" class="formFields" id="website" value="<?php print "$website"; ?>" size="36" maxlength="32" />
      <hr class="horizontal" />
      About You:<textarea name="bioBody" cols="" rows="5" class="formFields"><?php print "$bioBody"; ?></textarea><br />
      <input type="submit" name="button" id="button" value="Submit" />
    </form>
  </div><!--end content_wrapper-->
</body>
</html>