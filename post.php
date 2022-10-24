<?php
 //this class needs to have id fixed!!!!!!!!!!!!!!!!
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  include_once("scripts/LIB_http.php");
  include_once("scripts/LIB_parse.php");
  
  /*if (@!$_SESSION['idx'] || $logOptions_id != $_SESSION['id']) {
    header("location: index.php");
  }*/
  $id = 2;
  
  $errorMsg = '';
  $type = 'Party';
  $daysArray = array();
  $hoursArray = array();
  $minutesArray = array(":00",":30");
  $amPmArray = array("AM","PM");
  $street = '';
  $city = '';
  $state = '';
  $admissionArray = array();
  $title = '';
  
  for ($i = 0; $i < 366; $i++) {
    $incDays = mktime(0, 0, 0, date("m"), date("d")+$i, date("y"));
    $daysArray[] = date("D M j", $incDays);
  }
  
  //$hour = date("g", time()) 
  for ($j = 1; $j <= 12; $j++) {
    $hoursArray[] = $j;
  }
  
  $admissionArray[] = "Free";
  for ($k = 5; $k < 50; $k +=5) {
    $admissionArray[] = $k;
  }
  $admissionArray[] = "50+";
 
 $selectedDays = "--";
 $selectedHours = "--";
 $selectedMinutes = "--";
 $selectedAmPm = "--";
 $selectedAdmission = "--";
 
 if (isset($_POST['button'])) {
    $type = clean($_POST['type']);
    $days = clean($_POST['days']);
    $hours = preg_replace('#[^0-9-]#', '', $_POST['hours']);
    $minutes = preg_replace('#[^0:3-]#', '', $_POST['minutes']);
    $amPm = clean($_POST['ampm']);
    $street = clean($_POST['street']);
    $city = clean($_POST['city']);
    $state = clean($_POST['state']);
    $country = clean($_POST['country']);
    $age = clean($_POST['age']);
    $admission = clean($_POST['admission']);
    $rsvp = preg_replace('#[^0-9]#', '', $_POST['rsvp_limit']);
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    
    if (($admission == "--") || ($days == "--") || ($hours == "--") || ($minutes == "--") || ($amPm == "--")) {
      $errorMsg = "<span class=\"red\">ERROR:</span> You did not submit the following required information:<br /><br />";
    	
      if($admission == "--") {
        $errorMsg .= ' <span class="red">*</span> Admission<br />';
      }
      if($days == "--") {
        $errorMsg .= ' <span class="red">*</span> Event Date<br />';
      }
      if($hours == "--") {
        $errorMsg .= ' <span class="red">*</span> Event Hour<br />';
      }
      if($minutes == "--") {
        $errorMsg .= ' <span class="red">*</span> Complete Time<br />';
      }
      if($amPm == "--") {
        $errorMsg .= ' <span class="red">*</span> AM or PM<br />';
      }
    } else if ((!$street) || (!$city) || (!$state)) {
      $errorMsg = "<span class=\"red\">ERROR:</span> You did not submit the following required information:< br /><br />";
    	
      if(!$street) {
        $errorMsg .= ' <span class="red">*</span> Street<br />';
      }
      if(!$city) {
        $errorMsg .= ' <span class="red">*</span> City<br />';
      }
      if(!$state) {
        $errorMsg .= ' <span class="red">*</span> State<br />';
      }
    } else {
      ///OPEN STREET MAPS
      /*$queryString = 'http://nominatim.openstreetmap.org/search?q=' . urlencode($street) . '+' . urlencode($city) . '+' . urlencode($state) . '+' . $country . '&format=json';
      //$errorMsg = $queryString;
      $query = http_get($queryString, '');
      //$errorMsg = $query['FILE'];
      $latitude = return_between($query['FILE'], "lat\":\"", "\"", EXCL);
      $longitude = return_between($query['FILE'], "lon\":\"", "\"", EXCL);
      if (!$latitude) {
        $latitude = 'no valid latitude coordinates';
      }
      if (!$longitude) {
        $longitude = 'no valid longitude coordinates';
      }
      $errorMsg = 'latitude: ' . $latitude . '<br />longitude: ' . $longitude;*/
    
      ///GOOGLE MAPS
      $queryString = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($street) . ',' . urlencode($city) . ',' . urlencode($state) . ',' . $country . '&sensor=false';
      $query = http_get($queryString, '');
      $latitude = return_between($query['FILE'], "lat\" : ", ",", EXCL);
      $longitude = return_between($query['FILE'], "lng\" : ", "}", EXCL);
      if (!$latitude || !$longitude) {
        $latitude = 0.0;
        $longitude = 0.0;
      }
      
      //$errorMsg = 'latitude: ' . $latitude . '<br />longitude: ' . $longitude;
      
      ///TIME
      $time = $days . ' ' . $hours . '' . $minutes . ' ' . $amPm;
      $time = strtotime($time);
      $time = date("Y-m-d H:i:s", $time);
      //$errorMsg = $time;
      
      $sql_event_check = mysqli_query($link, "SELECT id FROM locations WHERE latitude='$latitude' AND longitude='$longitude' AND time='$time' AND active='y' OR street='$street' AND city='$city' AND time='$time' AND active='y'");
      $event_check = mysqli_num_rows($sql_event_check);
      if ($event_check > 0) {
        $errorMsg = '<span class="red">This event is already posted</span>';
      } else {
        $sql_event_post = mysqli_query($link, "INSERT INTO locations (type, time, street, city, state, country, age, admission, rsvp_limit, title, description, latitude, longitude, post_date, user_id) VALUES ('$type', '$time', '$street', '$city', '$state', '$country', '$age', '$admission', '$rsvp', '$title', '$description', '$latitude', '$longitude', now(), '$id')") or die (mysqli_error($link));
        $eventID = mysqli_insert_id($link);
        mkdir("events/$eventID", 0755);
        $street = '';
        $city = '';
        $state = '';
        $title = '';
        $errorMsg = 'Thanks for posting!';
      }
    }
    
     
 }
 
?>
<?php include_once("inc/head.inc.php"); ?>
  <style>
    .post_table {
      padding: 20px;
      margin-left: auto;
      margin-right: auto;
      border: 1px solid white;
    }
    #subject {
      margin-left: 30px;
    } 
    #email {
      margin-left: 40px;
    }
    #comment_box {
      margin-left: 9px;
    }

  </style>
  <script language="javascript" type="text/javascript">
	// copyright 1999 Idocs, Inc. http://www.idocs.com
	// Distribute this script freely but keep this notice in place
	function numbersonly(myfield, e, dec)
	{
	  var key;
	  var keychar;

	  if (window.event)
   		key = window.event.keyCode;
	  else if (e)
   		key = e.which;
	  else
   		return true;
	  keychar = String.fromCharCode(key);

	  // control keys
	if ((key==null) || (key==0) || (key==8) || 
    	(key==9) || (key==13) || (key==27))
   	  return true;

     // numbers
	else if ((("0123456789").indexOf(keychar) > -1))
      return true;

	// decimal point jump
	else if (dec && (keychar == ".")) {
   	  myfield.form.elements[dec].focus();
      return false;
    } else {
      return false;
    }
  }

</script>
</head>
<body>
  <div id="main_body">
    <h2>Got an event you want to promote? Post it here!</h2>
    <table class="post_table">
      <form method="post" name="postForm" id="postForm">
        <tr> 
          <td class="post_error">
            <?php print "$errorMsg"; ?>
          <td>
        </tr>
        <tr>
          <td class="post_type">
            Type:<select name="type"><option value="party" selected="selected">Party</option>
            							   					   <option value="other">Other</option></select>
          </td>
        </tr>
        <tr>
          <td class="post_admission">
            Admission:<span class="red">*</span><select name="admission"><?php print dropDown($admissionArray, $selectedAdmission); ?>l</select>
          </td>
        </tr>
        <tr>
          <td class="post_date">
            Time:<span class="red">*</span><select name="days"><?php print dropDown($daysArray, $selectedDays); ?></select>
            							   <select name="hours"><?php print dropDown($hoursArray, $selectedHours); ?></select>
            							   <select name="minutes"><?php print dropDown($minutesArray, $selectedMinutes); ?></select>
            							   <select name="ampm"><?php print dropDown($amPmArray, $selectedAmPm); ?></select>
          </td>
        </tr>
        <tr>
          <td class="post_street">
            Street:<span class="red">*</span><input type="text" value="<?php print "$street"; ?>" name="street" id="street" size="40" />
          </td>
        </tr>
        <tr>
          <td class="post_city">
            City:<span class="red">*</span><input type="text" value="<?php print "$city"; ?>" name="city" id="city" size="40" />
          </td>
        </tr>
        <tr>
          <td class="post_state">
            State/Territory/Province:<span class="red">*</span><input type="text" value="<?php print "$state"; ?>" name="state" id="state" size="20" />
          </td>
        </tr>
        <tr>
          <td class="post_country">
            Country:<select name="country"><option value="United+States" selected="selected">United States</option>
            							   					   <option value="Australia">Australia</option>
            							   					   <option value="Canada">Canada</option>
            							   					   <option value="United+Kingdom">United Kingdom</option></select>
          </td>
        </tr>
        <tr>
          <td class="post_age">
            Ages:<select name="age"><option value="18" selected="selected">18+</option>
            							   					   <option value="21">21+</option>
            							   					   <option value="25">25+</option>
            							   					   <option value="30">30+</option>
            							   					   <option value="high_school">High School</select>
          </td>
        </tr>
        <tr> 
          <td class="post_rsvp">
            RSVP Limit: <input type="radio" name="group1" value="unlimited" checked/> Unlimited <input type="radio" name="group1" value="limited" /><input type="text" name="rsvp_limit" id="rsvp_limit" size="4" maxlength="5" onKeyPress="return numbersonly(this, event)"/> Limit <span class="grey_color">(numbers only)</span>
          </td>
        </tr>
        <tr>
          <td class="post_title">
            Title:<input type="text" value="<?php print "$title"; ?>" name="title" id="title" size="40" />
          </td>
        </tr>
        <tr>
          <td class="post_description">
            Description:<textarea name="description" cols="40" rows="5" id="description"></textarea>
          </td>
        </tr> 
        <tr>
          <td class="contact_submit">
            <br /><input type="submit" name="button" id="button" value="Post Event"/>
          </td>
        </tr>
      </form>
    </table>
  </div>