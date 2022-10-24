<?php

  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  include_once("scripts/checkUserLog.php");

  function userLoc($latitude, $longitude) {
    global $link;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    
    $latitude = preg_replace('#[^0-9.]#', '', $_POST['latitude']);
    $longitude = preg_replace('#[^0-9.]#', '', $_POST['longitude']);
    
    if (isset($id)) {
      $userID = $id;
    } else {
    //if no user, must be anonymous user
      $userID = 00;
    }
    
    $sqlPostLoc = mysqli_query($link, "INSERT INTO mem_locations (user_id, latitude, longitude, post_date) VALUES ('$userID', '$latitude', '$longitude', now())");
  }
  
  function updateEvent($update, $event, $whosEvent, $profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    }
    
    $postType = clean($update);
    $eventID = preg_replace('#[^0-9]#', '', $event);
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    
    if ($id == $profileID) {
      if ($postType == "cancel") {
        if ($whosEvent == "my") {//this user actually started the event, so get rid of it and all references to it
          $updateLoc = mysqli_query($link, "UPDATE locations SET active='n', in_user_past='n' WHERE id='$eventID' AND user_id='$id'");
          $sqlSelectLoc = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' LIMIT 1");
          $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
          $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID'");
          $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);
        
          if ($sqlNumRsvps > 0) {//send a private message to all who made a rsvp
            while ($row = mysqli_fetch_array($sqlSelectRsvp)) {
              $userID = $row['user_id'];
              while ($row = mysqli_fetch_array($sqlSelectLoc)) {
                $street = $row['street'];
                $city = $row['city'];
                $state = $row['state'];
                $time = strtotime($row['time']);
                $meetUserID = $row['user_id'];
              }
              $meetUsername = getUsername($meetUserID);
              $date = date("F j, Y, g:i a", $time); //e.g. March 10, 2001, 5:16 pm
            
              $subject = 'CANCELLATION';
              $pm = 'The event started by ' . $meetUsername . ', on ' . $date . ', located at ' . $street . ' ' . $city . ' ' . $state . ', has been cancelled. Please be advised';
              $sqlPostPm = mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, subject, message, post_date) VALUES ('$userID', '$meetUserID', '$subject', '$pm', now())");
            }//end while
          }//end if
          $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='n' WHERE location_id='$eventID'");
          sendResponse(200, "You've just cancelled on this event.");
          exit;
        } else if ($whosEvent == "rsvp") {
          $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='n' WHERE location_id='$eventID' AND user_id='$id'");
          sendResponse(200, "You've just cancelled on this event.");
          exit;
        } else if ($whosEvent = 'fav') {
          $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID' AND user_id='$id'");
          sendResponse(200, "This event has been removed from your favorites");
          exit;
        } else {
          sendResponse(200, "You haven't rsvp'd or favorited this event");
          exit;
        }
      } else if ($postType == "rsvp") {
    
        //check to see if user has ever rsvpd for this event before
        $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id'");
        $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
        $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);
        $sqlNumFavs = mysqli_num_rows($sqlSelectFav);
      
        if ($sqlNumRsvps > 0) {//user rsvp'd before
      
          while ($row = mysqli_fetch_array($sqlSelectRsvp)) { $active = $row['active'];  $invited = $row['invited']; }
          if ($active == 'n') {//they must have rsvp'd and then cancelled, setting active to n, so just set active to y
            if ($invited == 'y') {
              $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='y' WHERE location_id='$eventID' AND user_id='$id'");
              //if they favorited this event, make it inactive so there won't be duplicate entries
              if ($sqlNumFavs > 0) { $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID' AND user_id='$id'"); }
              sendResponse(200, "You've just RSVP'd for this event!");
              exit;
            } else {
              sendResponse(200, "Unfortunately, your previous rsvp was cancelled by the event starter.\n You can not rsvp for this event again.");
              exit;
            }
          } else {
            sendResponse(200, "You've already rsvp'd for this event");
            exit;
          }
        
        } else {//user has not rsvp'd
          //check to see if user actually started event
          $originatorID = 0;
          $sqlSelectOrig = mysqli_query($link, "SELECT user_id FROM locations WHERE id='$eventID' AND user_id='$id' LIMIT 1");
          while ($row0 = mysqli_fetch_array($sqlSelectOrig)) { $originatorID = $row0['user_id']; }
          if ($originatorID != $id) {
            $sqlPostRsvp = mysqli_query($link, "INSERT INTO location_rsvps (location_id, user_id, post_date) VALUES ('$eventID', '$id', now())");
            if ($sqlNumFavs > 0) { $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$eventID' AND user_id='$id'"); }
            sendResponse(200, "You've just RSVP'd for this event!");
            exit;
          } else {
            sendResponse(200, "You can't rsvp a event you started.");
            exit;
          }
        }
      } else if ($postType == "fav") {
        //check to see if user has ever rsvpd for this event before
        $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y'");
        $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id'");
        $sqlNumRsvps = mysqli_num_rows($sqlSelectRsvp);
        $sqlNumFavs = mysqli_num_rows($sqlSelectFav);
      
        if ($sqlNumRsvps > 0) {//user already rsvp'd, bail
          sendResponse(200, "You've already rsvp'd for this event");
          exit;
        }
      
        if ($sqlNumFavs > 0) {//user favorited before
        
          while ($row = mysqli_fetch_array($sqlSelectFav)) { $active = $row['active']; }
          if ($active = 'n') {//they must have favorited and then cancelled, setting active to n, so just set active to y
            $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='y' WHERE location_id='$eventID' AND user_id='$id'");
            sendResponse(200, "You've just added this event to your favorites.");
            exit;
          } else {
            sendResponse(200, "You've already favorited for this event");
            exit;
          }
        } else {//user has not favorited
          //check to see if user actually started event
          $originatorID = 0;
          $sqlSelectOrig = mysqli_query($link, "SELECT user_id FROM locations WHERE id='$eventID' AND user_id='$id' LIMIT 1");
          while ($row0 = mysqli_fetch_array($sqlSelectOrig)) { $originatorID = $row0['user_id']; }
          if ($originatorID != $id) {
            $sqlPostRsvp = mysqli_query($link, "INSERT INTO location_favs (location_id, user_id, post_date) VALUES ('$eventID', '$id', now())");
            sendResponse(200, "You've just added this event to your favorites.");
            exit;
          } else {
            sendResponse(200, "You can't favorite a event you started.");
            exit;
          }
        }
        
      } else {
        sendResponse(400, 'Invalid request');
        exit;
      }
    }  
  }
  
  function removeRsvp($delUserID, $eventID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(404, "You are not logged in");
      exit;
    }
    $delUserID = preg_replace('#[^0-9]#', '', $delUserID);
    $eventID = preg_replace('#[^0-9]#', '', $eventID);
    $creatorID;
    
    $sqlGetCreator = mysqli_query($link, "SELECT user_id FROM locations WHERE id='$eventID'"); 
    while ($row = mysqli_fetch_array($sqlGetCreator)) { $creatorID = $row['user_id']; }
    
    if ($creatorID == $id) {
      $sqlUpdateUser = mysqli_query($link, "UPDATE location_rsvps SET invited='n', active='n' WHERE location_id='$eventID' AND user_id='$delUserID'");
      $sqlSelectLoc = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' LIMIT 1");
      while ($row = mysqli_fetch_array($sqlSelectLoc)) {
        $street = $row['street'];
        $city = $row['city'];
        $state = $row['state'];
        $time = strtotime($row['time']);
        $meetUserID = $row['user_id'];
      }
      
      $meetUsername = getUsername($id);
      $date = date("F j, Y, g:i a", $time); //e.g. March 10, 2001, 5:16 pm
            
      $subject = 'RSVP CANCELLATION';
      $pm = $meetUsername . ' has cancelled the rsvp you have made for the event on ' . $date . ', located at ' . $street . ' ' . $city . ' ' . $state . '. Please be advised';
      $sqlPostPm = mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, subject, message, post_date) VALUES ('$delUserID', '$id', '$subject', '$pm', now())");
      
      sendResponse(200, "Rsvp cancellation made");
      exit;
    } else {
      sendResponse(203, "No Bueno");
      exit;
    }
  }
  
  function comment($getID, $photoFlag, $comment) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $getID = preg_replace('#[^0-9]#', '', $getID);
    $comment = urldecode($comment);
    $comment = preg_replace( '/\s+/', ' ', $comment); //strip extra whitespace
    $comment = clean($comment);
    
    if ($photoFlag == 'y') {
      $sqlInsertComment = mysqli_query($link, "INSERT INTO photo_comments (photo_id, user_id, comment, post_date) VALUES ('$getID', '$id', '$comment', now())") or die (mysqli_error($link));
    } else {
      $sqlInsertComment = mysqli_query($link, "INSERT INTO location_comments (location_id, user_id, comment, post_date) VALUES ('$getID', '$id', '$comment', now())") or die (mysqli_error($link));
    }
    
    sendResponse(200, 'Thanks for posting!');
    exit;
    
  }
  
  function register($email, $username, $pass1, $gender) {
    global $link;
    $email = urldecode($email);
    $username = urldecode($username);
    $pass1 = urldecode($pass1);
    $gender = preg_replace('#[^mf]#', '', $gender);
    $ipaddress = getenv('REMOTE_ADDR');
    
    
    //email checking code
    if (stristr($email, "'")) {
      sendResponse(406, "Please, no apostrophe's in email address.");
      exit;
    }
    
    if (stristr($email, "`")) {
      sendResponse(406, "Please, no backtick's in email address.");
      exit;
    }
    
    if (stristr($email, "\\")) {
      sendResponse(406, "Please, no backslashes in email address.");
      exit;
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
  	  sendResponse(406, "This email address doesn't appear to be valid.");
      exit;
  	}
    
    if (preg_match('#[^a-z0-9_]#i', $username)) {
      sendResponse(406, "Alphanumeric [a-Z] [0-9] characters only please.");
      exit;
    }
    
    if (strlen($username) < 4 || strlen($pass1) < 4) {
      sendResponse(406, "Member name and password need to be at least 4 characters long");
      exit;
    }
    
    if (strlen($username) > 15) {
      sendResponse(406, "Member name needs to be fewer than 16 characters long");
      exit;
    }
    
    if ($gender == "") {
      sendResponse(406, "You haven't included your gender");
      exit;
    }
    
    $email = mysqli_real_escape_string($link, $email);
    $username = strtolower($username);
    $username = clean($username);
    $pass1 = clean($pass1);
    
    $sql_uname_check = mysqli_query($link, "SELECT username FROM members WHERE username='$username'");
    $uname_check = mysqli_num_rows($sql_uname_check);
    
    $sql_email_check = mysqli_query($link, "SELECT email FROM members WHERE email='$email'");
    $email_check = mysqli_num_rows($sql_email_check);
    
    if ($uname_check > 0) {
      sendResponse(204, "Your username is already in use inside of our system. Please try another");
      exit;
    }
    
    if ($email_check > 0) {
      sendResponse(204, "Your email is already in use inside of our system.");
      exit;
    }
    
    $db_password = md5($pass1);
    
    $sql = mysqli_query($link, "INSERT INTO members (username, password, email, ipaddress, gender, sign_up_date)
      VALUES('$username', '$db_password', '$email', '$ipaddress', '$gender', now())")
      or die (mysqli_error($link));
            
    $id = mysqli_insert_id($link);
      
      
    // Create directory(folder) to hold each user's files(pics, MP3s, etc.)
    mkdir("members/$id", 0755);
    mkdir("members/$id/uploads", 0755);
    
    //set session and cookie
    $_SESSION['id'] = $id;
    $_SESSION['idx'] = base64_encode("g4p3h9xfn8sq03hs2234$id");
    $_SESSION['username'] = $username;
    $_SESSION['userpass'] = $db_password;
    
    $encryptedID = base64_encode("g4enm2c0c4y3dn3727553$id");
    setcookie("idCookie", $encryptedID, time()+60*60*24*100, "/"); // Cookie set to expire in about 30 days
	setcookie("passCookie", $pass1, time()+60*60*24*100, "/"); // Cookie set to expire in about 30 days
    
    sendResponse(200, "$id");
    exit;
  }
  
  function profilePhoto($profileID, $photo) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    
    if ($profileID != $id) {
      sendResponse(404, 'Unauthorized Request');
      exit;
    } else {
    	$fileName = $photo["name"];
        $targetPath = "members/$id";
        $fileTmpLoc = $photo["tmp_name"];
        $fileType = $photo["type"]; 
	    $fileSize = $photo["size"]; 
	   $fileErrorMsg = $photo["error"];  
		$kaboom = explode(".", $fileName); 
		$fileExt = end($kaboom); 
		// Start PHP Image Upload Error Handling --------------------------------------------------
		if (!$fileTmpLoc) { // if file not chosen
      	  sendResponse(404,"ERROR: Photo not sent.");
      	  exit;
		} else if($fileSize > 5242880) { // if file size is larger than 5 Megabytes
      	  unlink($fileTmpLoc); // Remove the uploaded file from the PHP temp folder
      	  sendResponse(413, "ERROR: Your file was larger than 5 Megabytes in size.");
      	  exit;
		} else if (!preg_match("/.(gif|jpg|png|jpeg)$/i", $fileName) ) { 
     	  unlink($fileTmpLoc); 
     	  sendResponse(415, "ERROR: Your image was not .gif, .jpg, or .png. or .jpeg");
     	  exit;
		} else if ($fileErrorMsg == 1) { // if file upload error key is equal to 1
      	  sendResponse(404, "ERROR: An error occured while processing the file. Try again.");
      	  exit;
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
		}
      
      sendResponse(200, "Profile photo updated!");
    } //end else
  }

  function login($username, $pass1) {
    global $link;
    if(isset($_SESSION['idx'])) {
      sendResponse(204, "You are already logged in.");
      exit;
    }
    
    $username = urldecode($username);
    $pass1 = urldecode($pass1);
    
    $username = strtolower($username);
    $username = clean($username);
    $pass1 = clean($pass1);
    
    $pass1 = md5($pass1);
    $sql = mysqli_query($link, "SELECT id, username, password FROM members WHERE username='$username' AND password='$pass1' AND active='y' LIMIT 1");
    $login_check = mysqli_num_rows($sql);
    if ($login_check > 0) {
        while ($row= mysqli_fetch_array($sql)) {
         $id = $row["id"];
         $_SESSION['id'] = $id;
         $_SESSION['idx'] = base64_encode("g4p3h9xfn8sq03hs2234$id");
         $username = $row['username'];
         $_SESSION['username'] = $username;
         $userpass = $row['password'];
         $_SESSION['userpass'] = $userpass;
         
         mysqli_query($link, "UPDATE members SET last_log_date=now() WHERE id='$id' LIMIT 1");
        } // close while
        
        $encryptedID = base64_encode("g4enm2c0c4y3dn3727553$id");
    	setcookie("idCookie", $encryptedID, time()+60*60*24*100, "/"); // Cookie set to expire in about 30 days
		setcookie("passCookie", $pass1, time()+60*60*24*100, "/"); // Cookie set to expire in about 30 days
		
		sendResponse(200, "Success");
		exit;
	} else {
		sendResponse(204, "Incorrect login data, please try again");
		exit;
	} //end $login_check
  }
  
  function event($type, $time, $street, $city, $state, $country, $age, $admission, $rsvp, $title, $description, $latitude, $longitude) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } /*else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }*/
    
    $type = urldecode($type);
    $time = preg_replace('#[^0-9.]#', '', $time);
    $time = date("Y-m-d H:i:s", $time);
    $street = urldecode($street);
    $city = urldecode($city);
    $state = urldecode($state);
    $country = urldecode($country);
    $age = urldecode($age);
    $admission = preg_replace('#[^0-9]#', '', $admission);
    $rsvp = preg_replace('#[^0-9]#', '', $rsvp);
    $title = urldecode($title);
    $description = urldecode($description);
    $latitude = urldecode($latitude);
    $longitude = urldecode($longitude);
      
    $type = clean($type);
    $street = clean($street);
    $city = clean($city);
    $state = clean($state);
    $country = clean($country);
    $age = clean($age);
    $title = clean($title);
    $description = clean($description);
    $latitude = clean($latitude);
    $longitude = clean($longitude);
      
      
    $sql_event_check = mysqli_query($link, "SELECT id FROM locations WHERE latitude='$latitude' AND longitude='$longitude' AND time='$time' OR street='$street' AND city='$city' AND time='$time' AND active='y'");
    $event_check = mysqli_num_rows($sql_event_check);
    if ($event_check > 0) {
      sendResponse(204, 'This event is already posted');
    } else {
      $sql_event_post = mysqli_query($link, "INSERT INTO locations (type, time, street, city, state, country, age, admission, rsvp_limit, title, description, latitude, longitude, post_date, user_id) VALUES ('$type', '$time', '$street', '$city', '$state', '$country', '$age', '$admission', '$rsvp', '$title', '$description', '$latitude', '$longitude', now(), '$id')") or die (mysqli_error($link));
      $eventID = mysqli_insert_id($link);
      //postProfileEvent('posted_events', $eventID, $id);
      mkdir("events/$eventID", 0755);
      sendResponse(200, 'Thanks for posting!');
      exit;
    }
  }
  
  function password($currentPass, $newPass, $retypePass, $profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    
    if ($profileID != $id) {
      sendResponse(401, 'Unauthorized Request');
      exit;
    } else {
      $currentPass = urldecode($currentPass);
      $newPass = urldecode($newPass);
      $retypePass = urldecode($retypePass);
      
      if ($newPass != $retypePass) {
        sendResponse(204, "The confirmation password you provided didn't match your new pasword");
        exit;
      } 
      
      $currentPass = clean($currentPass);
      $newPass = clean($newPass);
      $hashCurPass = md5($currentPass);
      $hashNewPass = md5($newPass);
      
      $sql = mysqli_query($link, "SELECT * FROM members WHERE id='$id' AND password='$hashCurPass'");
      $passCheckNum = mysqli_num_rows($sql);
      
      if ($passCheckNum > 0) {
        $sqlUpdate = mysqli_query($link, "UPDATE members SET password='$hashNewPass' WHERE id='$id'");
        sendResponse(200, "Your password has been changed successfully");
        exit;
      } else {
        sendResponse(204, "Unsuccessful. Your current password did not match your profile.");
        exit;
      }
      
    }//end if else
  }
  
  function delete($deletePass, $profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
     
    if ($profileID != $id) {
      sendResponse(404, 'Unauthorized Request');
      exit;
    } else {
       $deletePass = urldecode($deletePass);
       $deletePass = clean($deletePass);
       $hashDelPass = md5($deletePass);
       
       $sql = mysqli_query($link, "SELECT * FROM members WHERE id='$id' AND password='$hashDelPass'");
       $passCheckNum = mysqli_num_rows($sql);
       if ($passCheckNum > 0) {
         $sqlUpdate = mysqli_query($link, "UPDATE members SET active='n' WHERE id='$id'");
         if (isset($_COOKIE['idCookie'])) {
    		setCookie("idCookie", '', time()-42000, '/');
    		setCookie("passCookie", '', time()-42000, '/');
  		  }
  		  session_destroy();
  		  sendResponse(200, "You have successfully closed your account");
  		  exit;
       } else {
         sendResponse(204, "Unsuccessful. Your current password did not match your profile.");
         exit;
       }
    }//end if else  
  }
  
  function rsvpEdit($rsvpShow, $profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    
    if ($profileID != $id) {
      sendResponse(404, 'Unauthorized Request');
      exit;
    } else {
      $rsvpShow = preg_replace('#[^yn]#', '', $rsvpShow);
      
      $sql = mysqli_query($link, "UPDATE members SET rsvp_show='$rsvpShow' WHERE id='$id'");
      
      sendResponse(200, "Account settings updated");
      exit;
    } 
  }
  
  function logout() {
    //Unset all session variables
    session_unset();
    
   if (isset($_COOKIE['idCookie'])) {
      setCookie("idCookie", '', time()-42000, '/');
      setCookie("passCookie", '', time()-42000, '/');
    }
    
    session_destroy();
    exit;
  }
  
  function delPastEvent($delEventID, $delDBType, $delProfileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $delEventID = preg_replace('#[^0-9]#', '', $delEventID);
    $whosEvent = clean($delDBType);
    $profileID = preg_replace('#[^0-9]#', '', $delProfileID);
    
    if ($id == $profileID) {
      //format whosEvent for db query
      if ($whosEvent == "my") {
        $sqlUpdateMy = mysqli_query($link, "UPDATE locations SET in_user_past='n' WHERE id='$delEventID'");
      } else if ($whosEvent == "rsvp") {
        $sqlUpdateRsvp = mysqli_query($link, "UPDATE location_rsvps SET active='n' WHERE location_id='$delEventID' AND user_id='$id' LIMIT 1");
      } else if ($whosEvent == "fav") {
        $sqlUpdateFav = mysqli_query($link, "UPDATE location_favs SET active='n' WHERE location_id='$delEventID' AND user_id='$id' LIMIT 1");
      } else {
        sendResponse(400, 'Invalid Request');
        exit;
      }
    }
  }
  
  function photo($eventID, $photo) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $eventID = preg_replace('#[^0-9]#', '', $eventID);
    $fileName = $photo["name"];
    
    if (isset($id)) {
    $targetPath = "events/$eventID";
    $fileTmpLoc = $photo["tmp_name"];
    $fileType = $photo["type"]; 
	$fileSize = $photo["size"]; 
	$fileErrorMsg = $photo["error"]; 
	$fileName = preg_replace('#[^a-z.-_0-9]#i', '', $fileName); 
	$kaboom = explode(".", $fileName); 
	$fileExt = end($kaboom); 
	//$fileName = time().rand().".".$fileExt; //Used to randomize website uploads
	// Start PHP Image Upload Error Handling --------------------------------------------------
	if (!$fileTmpLoc) { // if file not chosen
      sendResponse(404,"ERROR: Photo not sent.");
      exit;
	} else if($fileSize > 5242880) { // if file size is larger than 5 Megabytes
	  unlink($fileTmpLoc);
      sendResponse(413, "ERROR: Your file was larger than 5 Megabytes in size.");
      exit;
	} else if (!preg_match("/.(gif|jpg|png|jpeg)$/i", $fileName) ) { 
	  unlink($fileTmpLoc); 
      sendResponse(415, "ERROR: Your image was not .gif, .jpg, or .png. or .jpeg");
      exit; 
	} else if ($fileErrorMsg == 1) { // if file upload error key is equal to 1
      sendResponse(404, "ERROR: An error occured while processing the file. Try again.");
      exit;
	}

    $moveResult = copy($fileTmpLoc, $targetPath . '/' . $fileName);
    if ($moveResult != true) {
      @unlink($fileTmpLoc); 
      sendResponse(404,"ERROR: File not uploaded. Try again.");
      exit;
    }
    
    //resize image
	include_once("scripts/ak_php_img_lib_2.0.php");
	  
	$target_file = "$targetPath/$fileName"; 
	$resized_file = "$targetPath/resized_$fileName"; 
	$wmax = 425;
	$hmax = 425;
	@ak_img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	  
	$target_file = "$targetPath/resized_$fileName";
	$thumbnail = "$targetPath/thumb_$fileName";
	$wthumb = 75;
	$hthumb = 75;
	@ak_img_thumb($target_file, $thumbnail, $wthumb, $hthumb, $fileExt);
    
    $sqlPhotoPost = mysqli_query($link, "INSERT INTO photos (photo_name, location_id, user_id, post_date) VALUES ('$fileName', '$eventID', '$id', now())") or die (mysqli_error($link)); 
    sendResponse(200, 'Thanks for posting!');
    exit;
    }
  }
  
  function rate($rating, $photoID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $rating = preg_replace('#[^0-9.]#', '', $rating);
  	$photoID = preg_replace('#[^0-9]#', '', $photoID);
  	$newScore;
  	
  	//get photo score
  	$sqlPhotoGet = mysqli_query($link, "SELECT score FROM photos WHERE id='$photoID' LIMIT 1") or die (mysqli_error($link));
  	while($row = mysqli_fetch_array($sqlPhotoGet)) { $photoScore = $row['score']; }
  	
  	//get total number of votes
  	$sqlVotesGet = mysqli_query($link, "SELECT score FROM photo_votes WHERE photo_id='$photoID'") or die (mysqli_error($link));
  	$totalVoteNumber = mysqli_num_rows($sqlVotesGet);
  	
  	//has user voted already?
  	$sqlVoteGet = mysqli_query($link, "SELECT score FROM photo_votes WHERE photo_id='$photoID' AND user_id='$id' LIMIT 1") or die (mysqli_error($link));
  	$voteCheck = mysqli_num_rows($sqlVoteGet);
  	if ($voteCheck == 0) {//user hasn't voted yet
  	    $sqlScorePost =  mysqli_query($link, "INSERT INTO photo_votes (photo_id, user_id, score, post_date) VALUES ('$photoID', '$id', '$rating', now())") or die (mysqli_error($link)); 
  	    $totalVoteNumber++;
  	    $newScore = ($photoScore + $rating) / $totalVoteNumber;
  	  
  	} else {//user must have voted already
  	  while ($row = mysqli_fetch_array($sqlVoteGet)) { $userScore = $row['score']; }
  	  $sqlScoreUpdate = mysqli_query($link, "UPDATE photo_votes SET score='$rating', post_date=now() WHERE photo_id='$photoID' AND user_id='$id'") or die (mysqli_error($link));
  	  
  	  if ($totalVoteNumber == 1) { //user is only one that voted for it
  	    $newScore = $rating;
  	  } else {
  	    //reverse score
  	    $origSum = $photoScore * $totalVoteNumber; 
  	    $alteredSum = $origSum - $userScore;
  	    $newScore = ($alteredSum + $rating) / $totalVoteNumber;
  	  }
  	  
  	}
  	
  	$sqlPhotoUpdate = mysqli_query($link, "UPDATE photos SET score='$newScore' WHERE id='$photoID'") or die (mysqli_error($link));
  	
  	sendResponse(200, $newScore);
  	exit;
  }
  
  function follow($profileID, $followFlag) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    
    if ($profileID == $id) { //user looking at their own account; can't have user /follow/unfollow themselves
      sendResponse(404, 'Unauthorized Request');
      exit;
    }
    
    //get user name
    $username = getUsername($profileID);
    
    //now either reject follow or post it
    $sqlGetFollow = mysqli_query($link, "SELECT * FROM follow WHERE following_id='$id' AND follower_id='$profileID' LIMIT 1");
    $followCount = mysqli_num_rows($sqlGetFollow);
    
    if ($followCount != 0) {
      while ($row = mysqli_fetch_array($sqlGetFollow)) {
        $followID = $row['id'];
        $active = $row['active'];
      
        if ($followFlag == 'y' && $active == 'y') {//this should never get called
      	   sendResponse(405, "You are already following $username");
      	   exit;
        } else if ($followFlag == 'y' && $active == 'n') {
          $sqlUpdatefollow = mysqli_query($link, "UPDATE follow SET active='y' WHERE id='$followID'");
          sendResponse(200, "You are now following $username!");
          exit;
        } else if ($followFlag == 'n' && $active == 'y') { 
          $sqlUpdatefollow = mysqli_query($link, "UPDATE follow SET active='n' WHERE id='$followID'");
          sendResponse(200, "You have now unfollowed $username");
          exit;
        } else {//want to unfollow as indicated by $followFlag, currently not Following
          sendResponse(405, "You are not following $username");
          exit;
        }
      }//end while
    } else {
      if ($followFlag == 'y') {
        
        $sqlPostUser = mysqli_query($link, "INSERT INTO follow (follower_id, following_id, post_date) VALUES ('$profileID', '$id', now())"); 
        sendResponse(200, "You are now following $username!");
        exit;
      } else {//this should never get called
        sendResponse(405, "You are not following $username");
        exit;
      }
    }
  }
  
  function message($profileID, $subject, $message) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    $subject = urldecode($subject);
    $message = urldecode($message);
    
    if ($profileID == $id) { //user can't send a message to himself
      sendResponse(404, "Unauthorized Request");
      exit;
    } else {
      $subject = preg_replace( '/\s+/', ' ', $subject); //strip extra whitespace
      $message = preg_replace( '/\s+/', ' ', $message); 
      $subject = clean($subject);
      $message = clean($message);
    
      $sqlPostMessage = mysqli_query($link, "INSERT INTO private_messages (to_id, from_id, subject, message, post_date) VALUES ('$profileID', '$id', '$subject', '$message', now())");
      sendResponse(200, "Message Sent");
      exit;
    }
  }
  
  function delMessage($delMessageID, $delMessageType, $delProfileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $delMessageID = preg_replace('#[^0-9]#', '', $delMessageID);
    $profileID = preg_replace('#[^0-9]#', '', $delProfileID);
    
    if ($profileID == $id) {
      if ($delMessageType == 'r') {
        $sqlUpdateMessage = mysqli_query($link, "UPDATE private_messages SET recipient_delete='y' WHERE id='$delMessageID' AND to_id='$profileID'");
      } else if ($delMessageType == 's') {
        $sqlUpdateMessage = mysqli_query($link, "UPDATE private_messages SET sender_delete='y' WHERE id='$delMessageID' AND from_id='$profileID'");
      }
    }
  }
  
  function opened($messageID, $messageType, $profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $messageID = preg_replace('#[^0-9]#', '', $messageID);
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    
    if ($profileID == $id) { //only perform if user is looking at own private_messages
      if ($messageType == "r") {
    	$sqlUpdateMessage = mysqli_query($link, "UPDATE private_messages SET recipient_opened='y' WHERE id='$messageID'");
       } else if ($messageType == "s") {
         $sqlUpdateMessage = mysqli_query($link, "UPDATE private_messages SET sender_opened='y' WHERE id='$messageID'");
       }
    }
  }
  
  function setProfile($profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in to do that.');
      exit;
    }
    
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    $result = array();
    $i = 1;
    
    if ($profileID != $id) {
      sendResponse(404, 'Unauthorized Request');
      exit;
    } else {
      $firstName = urldecode($_POST['firstName']);
      $lastName = urldecode($_POST['lastName']);
      $website = urldecode($_POST['website']);
      $city = urldecode($_POST['city']);
      $state = urldecode($_POST['state']);
      $country = urldecode($_POST['country']);
      $bio = urldecode($_POST['bio']);
      
      if ($firstName != "" && $lastName != "") {
      	$firstName = clean($firstName);
      	$lastName = clean($lastName);
      	$firstName = substr($firstName, 0, 15);//15 char limit on first/last name
      	$lastName = substr($lastName, 0, 15);
      	$sqlNameUpdate = mysqli_query($link, "UPDATE members SET first_name='$firstName', last_name='$lastName' WHERE id='$id'");
      }
      
      if ($website != "") {
      	$website = clean($website);
      	$sqlWebUpdate = mysqli_query($link, "UPDATE members SET website='$website' WHERE id='$id'");
      }
      
      if ($city != "" && $state != "" && $country != "") {
      	$city = clean($city);
      	$state = clean($state);
      	$country = clean($country);
      	$sqlLocUpdate = mysqli_query($link, "UPDATE members SET city='$city', state='$state', country='$country' WHERE id='$id'");
      }
      
      
      if ($bio != "") {
      	$bio = clean($bio);
      	$sqlBioUpdate = mysqli_query($link, "UPDATE members SET bio_body='$bio' WHERE id='$id'");
      }
      sendResponse(200, "Updated");
      exit;
    }
  }
?>
