<?php
  
  include_once("scripts/connectToMySQL.php");
  include_once("scripts/includeFunctions.php");
  include_once("scripts/checkUserLog.php");
  include_once("scripts/Ranking.php");
  include_once("post2.php");
  
  
  function locations($type, $time, $city, $state, $country, $age, $admitHigh) {
    global $link, $logOptions_id, $loggedIn;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    $type = urldecode($type);
    $time = urldecode($time);
    $time = strtotime($time);
    $time = date("Y-m-d", $time);
    //$todaysDateTime = date("Y-m-d H:i:s"); database datetime format
    $city = urldecode($city);
    $state = urldecode($state);
    $country = urldecode($country);
    //removed urldecode because it was stripping + sign. Oddly enough, it didn't strip + on $_POST (postEvent.php), leading to database inconsistencies
    $age = $age;
    $admitHigh = urldecode($admitHigh);
    $result = array();
      
    $type = clean($type);
    $time = clean($time);
    $city = clean($city);
    $state = clean($state);
    $country = clean($country);
    $age = clean($age);
    $admitHigh = clean($admitHigh);
      
    //convert date to dateTime
    $timeLow = $time . ' 00:00:00';
    $timeHigh = $time . ' 23:59:59';
              
    $sql_events_get = mysqli_query($link, "SELECT * FROM locations WHERE (type='$type' OR '$type' = '') AND time BETWEEN '$timeLow' AND '$timeHigh' AND city='$city' AND state='$state' AND country='$country' AND (age='$age' OR '$age' = '') AND admission BETWEEN '0' AND '$admitHigh' AND active='y'");
    $events_check = mysqli_num_rows($sql_events_get);
    if ($events_check == 0) {
      sendResponse(204, $loggedIn);
    } else {
      $i = 1;
      while($row = mysqli_fetch_array($sql_events_get)) {
        $eventID = $row['id'];
        $userID = $row['user_id'];
        $username = getUsername($row['user_id']);
        $unixPostedStamp = strtotime($row['post_date']);
        $unixTimeStamp = strtotime($row['time']);
        $sqlGetRsvpNumber = mysqli_query($link, "SELECT id FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
        $sqlNumRsvps = mysqli_num_rows($sqlGetRsvpNumber);
        $sqlSelectFav = mysqli_query($link, "SELECT id FROM location_favs WHERE location_id='$eventID' AND active='y'");
        $favNumRows = mysqli_num_rows($sqlSelectFav);
        $sqlSelectPhotos = mysqli_query($link, "SELECT * FROM photos WHERE location_id='$eventID'");
        $photoNumRows = mysqli_num_rows($sqlSelectPhotos);
        $eventType = $row['type'];
        $thumbPath = "";
        $photoName = '';
        if ($photoNumRows != 0)
          while ($row1 = mysqli_fetch_array($sqlSelectPhotos)) { $photoName = $row1['photo_name']; }
         
    	if ($photoName == '') {
    	  if ($eventType == 'Party') {
    	    $thumbPath = "events/0/party.jpg"; 
    	  } else if ($eventType == 'Bar') {
    	    $thumbPath = "events/0/bar.png";
    	  } else if ($eventType == 'Club') {
    	    $thumbPath = "events/0/club.png";
    	  } else if ($eventType == 'Dining') {
    	    $thumbPath = "events/0/dining.png";
    	  } else if ($eventType == 'Sports/Fitness') {
    	    $thumbPath = "events/0/sports.gif";
    	  } else if ($eventType == 'Live Music') {
    	    $thumbPath = "events/0/live_music.jpg";
    	  } else if ($eventType == 'Convention') {
    	    $thumbPath = "events/0/convention.png";
    	  } else if ($eventType == 'Indoor') {
    	    $thumbPath = "events/0/indoor.gif";
    	  } else if ($eventType == 'Other') {
    	    $thumbPath = "events/0/outdoor.jpg";
    	  } else {
    	    $thumbPath = "events/0/no-photo.jpeg";
    	  }
    	} else {
    	  $thumbPath = "events/$eventID/thumb_" . $photoName;
    	}
    	 
    	$whosEvent = '';
    	
    	if (isset($id)) {
    	  if ($userID == $id) {
    	    $whosEvent = 'my';
    	  } else {
    	    $sqlGetRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y' LIMIT 1");
            $sqlGetFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y' LIMIT 1");
            $sqlNumRsvp = mysqli_num_rows($sqlGetRsvp);
            $sqlNumFav = mysqli_num_rows($sqlGetFav);
            if ($sqlNumRsvp > 0) { 
              $whosEvent = 'rsvp'; 
            } else if ($sqlNumFav > 0) { 
              $whosEvent = 'fav'; 
            } else {
              $whosEvent = 'none';
            }
          }
        } else if (!isset($id)) {
          $whosEvent = 'none';
        }
        
        //compare times to check if event already happened; databaseDateTime returns date("Y-m-d H:i:s");
        $todaysDateTime = time();
        if ($todaysDateTime > $unixTimeStamp ) {  
          $whosEvent = 'past';
        }
        
        $result[$i] = array(
          'whosEvent' => $whosEvent,
          'eventID' => $eventID,
          'type' => $eventType,
          'time' => $row['time'],
          'unixTimeStamp' => $unixTimeStamp,
          'street' => $row['street'],
          'city' => $row['city'],
          'state' => $row['state'],
          'country' => $row['country'],
          'age' => $row['age'],
          'admission' => $row['admission'],
          'rsvpLimit' => $row['rsvp_limit'],
          'rsvps' => $sqlNumRsvps, 
          'favs' => $favNumRows,
          'comments' => 0, //since these will only be new events where users can not comment or 
          'photos' => 0, //post photos yet, assign 0
          'title' => htmlkarakter($row['title']),
          'keyword1' => $row['keyword1'],
          'keyword2' => $row['keyword2'],
          'keyword3' => $row['keyword3'],
          'description' => $row['description'],
          'latitude' => $row['latitude'],
          'longitude' => $row['longitude'],
          'userID' => $userID,
          'username' => $username, 
          'posted' => $unixPostedStamp,
          'thumbPath' => $thumbPath,
          'loggedIn' => $loggedIn,
        );
        $i++;
      } //end while
        
        sendResponse(200, json_encode($result));
     } //end else
  }
  
  function popular($city, $state, $country,  $gt) {
    global $link, $logOptions_id, $loggedIn;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    
    $city = urldecode($city);
    $state = urldecode($state);
    $country = urldecode($country);
    $city = clean($city);
    $state= clean($state);
    $country = clean($country);
    $gt = preg_replace('#[^><]#', '', $gt);
    $rangeStart = 0;
    $rank = new Ranking;
    $rankingArray = array();
    $result = array();
    $i = 1;
    $sqlNumState = 0;
    $sqlNumCountry = 0; 
	
    //get queries in the past, from most specific to least specific
    $sqlGetCity = mysqli_query($link, "SELECT * FROM locations WHERE city='$city' AND state='$state' AND NOW() $gt time AND active='y' ORDER by time desc LIMIT $rangeStart, 100");
    $sqlNumCity = mysqli_num_rows($sqlGetCity);
     
    if ($sqlNumCity > 0) {
      while ($row = mysqli_fetch_array($sqlGetCity)) {
        $username = getUsername($row['user_id']);
        $eventID = $row['id'];
        $time = $row['time'];
        $type = $row['type'];
        $unixTimeStamp = strtotime($row['time']);
         
        //get the number of times someone has rsvp'd, favorited, commented, or posted a photo to guage hotness
        $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
        //active, to avoid rsvp double count
        $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND active='y'");
        $sqlSelectPhotos = mysqli_query($link, "SELECT * FROM photos WHERE location_id='$eventID'");
        $sqlSelectComm = mysqli_query($link, "SELECT * FROM location_comments WHERE location_id='$eventID'");
        $rsvpNumRows = mysqli_num_rows($sqlSelectRsvp);
        $favNumRows = mysqli_num_rows($sqlSelectFav);
        $photoNumRows = mysqli_num_rows($sqlSelectPhotos);
        $commentNumRows = mysqli_num_rows($sqlSelectComm);
         
        $photoName = '';
        while ($row1 = mysqli_fetch_array($sqlSelectPhotos)) { $photoName = $row1['photo_name']; }
         
    	if ($photoName == '') {
    	  if ($type == 'Party') {
    	    $thumbPath = "events/0/party.jpg"; 
    	  } else if ($type == 'Bar') {
    	    $thumbPath = "events/0/bar.png";
    	  } else if ($type == 'Club') {
    	    $thumbPath = "events/0/club.png";
    	  } else if ($type == 'Dining') {
    	    $thumbPath = "events/0/dining.png";
    	  } else if ($type == 'Sports/Fitness') {
    	    $thumbPath = "events/0/sports.gif";
    	  } else if ($type == 'Live Music') {
    	    $thumbPath = "events/0/live_music.jpg";
    	  } else if ($type == 'Convention') {
    	    $thumbPath = "events/0/convention.png";
    	  } else if ($type == 'Indoor') {
    	    $thumbPath = "events/0/indoor.gif";
    	  } else if ($type == 'Other') {
    	    $thumbPath = "events/0/outdoor.jpg";
    	  } else {
    	    $thumbPath = "events/0/no-photo.jpeg";
    	  }
    	} else {
    	  $thumbPath = "events/$eventID/thumb_" . $photoName;
    	}
         
        //add number of rows and put in magical hotness algorithm
        $total = $rsvpNumRows + $favNumRows + $photoNumRows + $commentNumRows;
        $locRanking = $rank -> hotness($total, 0, $unixTimeStamp);
         
        $street = $row['street'];
        $city = $row['city'];
        $state = $row['state'];
        $country = $row['country'];
        $age = $row['age'];
        $admission = $row['admission'];
        $rsvpLimit = $row['rsvp_limit'];
        $rsvps = $rsvpNumRows;
        $comments = $commentNumRows;
        $photos = $photoNumRows;
        $favs = $favNumRows;
        $title = htmlkarakter($row['title']);
        $keyword1 = $row['keyword1'];
        $keyword2 = $row['keyword2'];
        $keyword3 = $row['keyword3'];
        $description = $row['description'];
        $latitude = $row['latitude'];
        $longitude = $row['longitude'];
        $userID = $row['user_id'];
        $username = $username; 
        $posted = strtotime($row['post_date']);
         
        //place into ranking array to be arsorted later, with $locRanking and $scheduledTime first. 
        //Those will be the primary values for ranking events in order. use backticks to separate because 
        //that will never be in returned query results
        $rankingArray[] = $locRanking . '`' . $unixTimeStamp . '`' . $type . '`' . $eventID . '`' . $street . '`' .
        $city . '`' . $state . '`' . $country . '`' . $time . '`' . $age . '`' . $admission . '`' . $rsvpLimit .
        '`' . $rsvps . '`' . $comments . '`' . $photos . '`' . $title . '`' . $keyword1 . '`' . $keyword2 .
        '`' . $keyword3 . '`' . $description . '`' . $latitude . '`' . $longitude . '`' . $userID . '`' .
        $username . '`' . $posted . '`' . $thumbPath . '`' . $favs;
      }
    }
     
     if ($sqlNumCity < 20) {//if running out of city queries, look up state
       $sqlGetState = mysqli_query($link, "SELECT * FROM locations WHERE state='$state' AND NOW() $gt time AND '$city' <> city AND active='y' ORDER BY time desc LIMIT 20");
       $sqlNumState = mysqli_num_rows($sqlGetState);
       
       if ($sqlNumState > 0) {
         while ($row = mysqli_fetch_array($sqlGetState)) {
           $username = getUsername($row['user_id']);
           $eventID = $row['id'];
           $time = $row['time'];
           $type = $row['type'];
           $unixTimeStamp = strtotime($row['time']);
         
           //get the number of times someone has rsvp'd, favorited, commented, or posted a photo to guage hotness
           $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
           //active, to avoid rsvp double count
           $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND active='y'");
           $sqlSelectPhotos = mysqli_query($link, "SELECT * FROM photos WHERE location_id='$eventID'");
           $sqlSelectComm = mysqli_query($link, "SELECT * FROM location_comments WHERE location_id='$eventID'");
           $rsvpNumRows = mysqli_num_rows($sqlSelectRsvp);
           $favNumRows = mysqli_num_rows($sqlSelectFav);
           $photoNumRows = mysqli_num_rows($sqlSelectPhotos);
           $commentNumRows = mysqli_num_rows($sqlSelectComm);
           
           $photoName = '';
           while($row1 = mysqli_fetch_array($sqlSelectPhotos)) { $photoName = $row1['photo_name']; }
           
           if ($photoName == '') {
    	     if ($type == 'Party') {
    	       $thumbPath = "events/0/party.jpg"; 
    	     } else if ($type == 'Bar') {
    	       $thumbPath = "events/0/bar.png";
    	     } else if ($type == 'Club') {
    	       $thumbPath = "events/0/club.png";
    	     } else if ($type == 'Dining') {
    	       $thumbPath = "events/0/dining.png";
    	     } else if ($type == 'Sports/Fitness') {
    	       $thumbPath = "events/0/sports.gif";
    	     } else if ($type == 'Live Music') {
    	       $thumbPath = "events/0/live_music.jpg";
    	     } else if ($type == 'Convention') {
    	       $thumbPath = "events/0/convention.png";
    	     } else if ($type == 'Indoor') {
    	       $thumbPath = "events/0/indoor.gif";
    	     } else if ($type == 'Outdoor') {
    	       $thumbPath = "events/0/outdoor.jpg";
    	     } else {
    	       $thumbPath = "events/0/no-photo.jpeg";
    	     }
    	   } else {
    	     $thumbPath = "events/$eventID/thumb_" . $photoName;
    	   }
         
           //add number of rows and put in magical hotness algorithm
           $total = $rsvpNumRows + $favNumRows + $photoNumRows + $commentNumRows;
           $locRanking = $rank -> hotness($total, 0, $unixTimeStamp);
         
           $street = $row['street'];
           $city = $row['city'];
           $state = $row['state'];
           $country = $row['country'];
           $age = $row['age'];
           $admission = $row['admission'];
           $rsvpLimit = $row['rsvp_limit'];
           $rsvps = $rsvpNumRows;
           $comments = $commentNumRows;
           $photos = $photoNumRows;
           $favs = $favNumRows;
           $title = htmlkarakter($row['title']);
           $keyword1 = $row['keyword1'];
           $keyword2 = $row['keyword2'];
           $keyword3 = $row['keyword3'];
           $description = $row['description'];
           $latitude = $row['latitude'];
           $longitude = $row['longitude'];
           $userID = $row['user_id'];
           $username = $username; 
           $posted = strtotime($row['post_date']);
         
           $rankingArray[] = $locRanking . '`' . $unixTimeStamp . '`' . $type . '`' . $eventID . '`' . $street . '`' .
           $city . '`' . $state . '`' . $country . '`' . $time . '`' . $age . '`' . $admission . '`' . $rsvpLimit .
           '`' . $rsvps . '`' . $comments . '`' . $photos . '`' . $title . '`' . $keyword1 . '`' . $keyword2 .
           '`' . $keyword3 . '`' . $description . '`' . $latitude . '`' . $longitude . '`' . $userID . '`' .
           $username . '`' . $posted . '`' . $thumbPath . '`' . $favs;
         }//end while
       }//end if
     }
     
     if ($sqlNumState < 20) {//if running out of city & state queries, search the whole damn country
       $sqlGetCountry = mysqli_query($link, "SELECT * FROM locations WHERE country='$country' AND NOW() $gt time AND '$state' <> state AND active='y' ORDER BY time desc LIMIT 20");
       $sqlNumCountry = mysqli_num_rows($sqlGetCountry);
       
       if ($sqlNumCountry > 0) {
         while ($row = mysqli_fetch_array($sqlGetCountry)) {
           $username = getUsername($row['user_id']);
           $eventID = $row['id'];
           $time = $row['time'];
           $type = $row['type'];
           $unixTimeStamp = strtotime($row['time']);
         
           //get the number of times someone has rsvp'd, favorited, commented, or posted a photo to guage hotness
           $sqlSelectRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
           //active, to avoid rsvp double count
           $sqlSelectFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND active='y'");
           $sqlSelectPhotos = mysqli_query($link, "SELECT * FROM photos WHERE location_id='$eventID'");
           $sqlSelectComm = mysqli_query($link, "SELECT * FROM location_comments WHERE location_id='$eventID'");
           $rsvpNumRows = mysqli_num_rows($sqlSelectRsvp);
           $favNumRows = mysqli_num_rows($sqlSelectFav);
           $photoNumRows = mysqli_num_rows($sqlSelectPhotos);
           $commentNumRows = mysqli_num_rows($sqlSelectComm);
           
           $photoName = '';
           while($row1 = mysqli_fetch_array($sqlSelectPhotos)) { $photoName = $row1['photo_name']; }
           
           if ($photoName == '') {
    	     if ($type == 'Party') {
    	       $thumbPath = "events/0/party.jpg"; 
    	     } else if ($type == 'Bar') {
    	       $thumbPath = "events/0/bar.png";
    	     } else if ($type == 'Club') {
    	       $thumbPath = "events/0/club.png";
    	     } else if ($type == 'Dining') {
    	       $thumbPath = "events/0/dining.png";
    	     } else if ($type == 'Sports/Fitness') {
    	       $thumbPath = "events/0/sports.gif";
    	     } else if ($type == 'Live Music') {
    	       $thumbPath = "events/0/live_music.jpg";
    	     } else if ($type == 'Convention') {
    	       $thumbPath = "events/0/convention.png";
    	     } else if ($type == 'Indoor') {
    	       $thumbPath = "events/0/indoor.gif";
    	     } else if ($type == 'Outdoor') {
    	       $thumbPath = "events/0/outdoor.jpg";
    	     } else {
    	       $thumbPath = "events/0/no-photo.jpeg";
    	     }
    	   } else {
    	     $thumbPath = "events/$eventID/thumb_" . $photoName;
    	   }
         
           //add number of rows and put in magical hotness algorithm
           $total = $rsvpNumRows + $favNumRows + $photoNumRows + $commentNumRows;
           $locRanking = $rank -> hotness($total, 0, $unixTimeStamp);
         
           $street = $row['street'];
           $city = $row['city'];
           $state = $row['state'];
           $country = $row['country'];
           $age = $row['age'];
           $admission = $row['admission'];
           $rsvpLimit = $row['rsvp_limit'];
           $rsvps = $rsvpNumRows;
           $comments = $commentNumRows;
           $photos = $photoNumRows;
           $favs = $favNumRows;
           $title = htmlkarakter($row['title']);
           $keyword1 = $row['keyword1'];
           $keyword2 = $row['keyword2'];
           $keyword3 = $row['keyword3'];
           $description = $row['description'];
           $latitude = $row['latitude'];
           $longitude = $row['longitude'];
           $userID = $row['user_id'];
           $username = $username; 
           $posted = strtotime($row['post_date']);
         
           $rankingArray[] = $locRanking . '`' . $unixTimeStamp . '`' . $type . '`' . $eventID . '`' . $street . '`' .
           $city . '`' . $state . '`' . $country . '`' . $time . '`' . $age . '`' . $admission . '`' . $rsvpLimit .
           '`' . $rsvps . '`' . $comments . '`' . $photos . '`' . $title . '`' . $keyword1 . '`' . $keyword2 .
           '`' . $keyword3 . '`' . $description . '`' . $latitude . '`' . $longitude . '`' . $userID . '`' .
           $username . '`' . $posted . '`' . $thumbPath . '`' . $favs;
         }//end while
       }
     }
     
     //now sort by rank
     arsort($rankingArray);
  	 foreach ($rankingArray as $key => $value) {
  	   $kaboom = explode("`", $rankingArray[$key]);
  	   
  	   $eventID = $kaboom[3];
  	   $sqlNumRsvps = 0;
  	   $sqlNumFavs = 0;
  	   
  	   if ($gt == '>') {
  	     $whosEvent = 'past';
  	   } else {
  	     if (isset($id)) {//this is used in featured list, to find out if user is already a part of upcoming event
  	       $sqlGetMy = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' AND user_id='$id' AND active='y' AND in_user_past='y' LIMIT 1");
    	   $sqlNumMy = mysqli_num_rows($sqlGetMy);
    	   $sqlGetRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y' LIMIT 1");
           $sqlNumRsvps = mysqli_num_rows($sqlGetRsvp);
           $sqlGetFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y' LIMIT 1");
           $sqlNumFavs = mysqli_num_rows($sqlGetFav);
    	   
    	   if ($sqlNumMy > 0) {
             $whosEvent = 'my';
    	   } else if ($sqlNumRsvps > 0) {
             $whosEvent = 'rsvp';
    	   } else if ($sqlNumFavs > 0) {
    	     $whosEvent = 'fav';
    	   } else {
    	     $whosEvent = 'none';
    	   }
  	     } else { 
  	       $whosEvent = '!logged';
  	     }
  	   }
  	   
  	   $result[$i] = array(
         'whosEvent' => $whosEvent,
         'ranking' => $kaboom[0],
         'eventID' => $eventID,
         'type' => $kaboom[2],
         'time' => $kaboom[8],   
         'unixTimeStamp' => $kaboom[1],
         'street' => $kaboom[4],
         'city' => $kaboom[5],
         'state' => $kaboom[6],
         'country' => $kaboom[7],
         'age' => $kaboom[9],
         'admission' => $kaboom[10],
         'rsvpLimit' => $kaboom[11],
         'rsvps' => $kaboom[12],
         'comments' => $kaboom[13],
         'photos' => $kaboom[14],
         'favs' => $kaboom[26],
         'thumbPath' => $kaboom[25],
         'title' => $kaboom[15],
         'keyword1' => $kaboom[16],
         'keyword2' => $kaboom[17],
         'keyword3' => $kaboom[18],
         'description' => $kaboom[19],
         'latitude' => $kaboom[20],
         'longitude' => $kaboom[21],
         'userID' => $kaboom[22],
         'username' => $kaboom[23], 
         'posted' => $kaboom[24],
         'loggedIn' => $loggedIn,
       	);
      	$i++;
  	 }//end foreach
  	 
    
  	if ($sqlNumCity != 0 || $sqlNumState != 0 || $sqlNumCountry !=0) {
      sendResponse(200, json_encode($result));
      exit;
    } else {
      sendResponse(204, $loggedIn);
      exit;
    }
  }
  
  function eventFromDetailed($eventID) {
    $eventID = preg_replace('#[^0-9]#', '', $eventID);
    $result = array();
    $i = 1;
    
    $result = getEvents('past', $eventID);
    
    sendResponse(200, json_encode($result));
    exit;
  }
  
  function eventFromMap($eventID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    $eventID = preg_replace('#[^0-9]#', '', $eventID);
    $result = array();
    
    ///THIS WILL ONLY SEND RESPONSE IF USER IS THE ORIGINATOR OF EVENT AND IT HAS PAST
    if (isset($id)) {//make sure logged in user first
      $originatorID = 0;
      $sqlGetOrig = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' AND user_id='$id' LIMIT 1");
      while ($row0 = mysqli_fetch_array($sqlGetOrig)) { 
        $originatorID = $row0['user_id']; 
        
        //find out if event has already past; this should never get called, included for completeness
        $todaysDateTime = time();
        //compare times to check if event already happened; databaseDateTime returns date("Y-m-d H:i:s");
        $databaseDateTime = strtotime($row0['time']);
        if ($todaysDateTime > $databaseDateTime ) {  
          $result[1] = array(
            'whosEvent' => 'past',
            'userID' => $id,
          );
          sendResponse(200, json_encode($result));
          exit; 
        }
      }
      
      //try to see if event past regardless of logged in or not
      $sqlGetDate = mysqli_query($link, "SELECT * FROM locations WHERE id='$eventID' LIMIT 1");
      while ($row1 = mysqli_fetch_array($sqlGetDate)) { 
        $todaysDateTime = time();
        //compare times to check if event already happened; databaseDateTime returns date("Y-m-d H:i:s");
        $databaseDateTime = strtotime($row1['time']);
        if ($todaysDateTime > $databaseDateTime ) {  
          $result[1] = array(
            'whosEvent' => 'past',
            'userID' => isset($id) ? $id : 'none',
          );
          sendResponse(200, json_encode($result));
          exit; 
        }
      }
      $sqlGetRsvp = mysqli_query($link, "SELECT * FROM location_rsvps WHERE location_id='$eventID' AND user_id='$id' AND active='y' LIMIT 1");
      $sqlGetFav = mysqli_query($link, "SELECT * FROM location_favs WHERE location_id='$eventID' AND user_id='$id' AND active='y' LIMIT 1");
      $sqlNumRsvp = mysqli_num_rows($sqlGetRsvp);
      $sqlNumFav = mysqli_num_rows($sqlGetFav);
      
      if ($originatorID != 0) {
        $result[1] = array(
          'whosEvent' => 'my',
          'userID' => $id,
        );
        sendResponse(200, json_encode($result));
        exit;
      } else if ($sqlNumRsvp > 0) {
        $result[1] = array(
          'whosEvent' => 'rsvp',
          'userID' => $id,
        );
        sendResponse(200, json_encode($result));
        exit;
      } else if ($sqlNumFav > 0) {
        $result[1] = array(
          'whosEvent' => 'fav',
          'userID' => $id,
        );
        sendResponse(200, json_encode($result));
        exit;
      } else {
        $result[1] = array(
          'whosEvent' => 'none',
          'userID' => $id,
        );
        sendResponse(200, json_encode($result));
        exit;
      }
    } else { //visiting member, not logged in
      $result[1] = array(
        'whosEvent' => '!logged',
        'userID' => 'none',
      );
      sendResponse(200, json_encode($result));
      exit;
    }
  }
  
  function  users($eventID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(204, "Only members can see this.");
      exit;
    }
    
    $eventID = preg_replace('#[^0-9]#', '', $eventID);
    $result = array();
    $i = 1;
    $creatorID;
    
    $sqlGetCreator = mysqli_query($link, "SELECT user_id FROM locations WHERE id='$eventID'"); 
    while ($row = mysqli_fetch_array($sqlGetCreator)) { $creatorID = $row['user_id']; }
    $sqlGetRsvps = mysqli_query($link, "SELECT user_id FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
    $rsvpNumRows = mysqli_num_rows($sqlGetRsvps);
    
    if ($rsvpNumRows > 0) {
      while ($row = mysqli_fetch_array($sqlGetRsvps)) {
        $userID = $row['user_id'];
        $whoRequest;
        //if (isset($id)) {
        if ($creatorID == $id) { //if creator of this event, they should see everyone on this list
            $sqlGetUser = mysqli_query($link, "SELECT * FROM members WHERE id='$userID' AND active='y' LIMIT 1");
            $whoRequest = 'c'; //c stands for creator
        } else {
            $sqlGetUser = mysqli_query($link, "SELECT * FROM members WHERE id='$userID' AND rsvp_show='y' AND active='y'");
            $whoRequest = 'u';
        }
        $userPic = checkUserPic($userID);
   		$username = getUsername($userID);
    	while ($row = mysqli_fetch_array($sqlGetUser)) {
      	  $result[$i] = array(
        	'id' => $row["id"],
        	'whoRequest' => $whoRequest, 
        	'username' => $username,
        	'userPic' => $userPic,
      	  );
          $i++;
        } //end $sqlGetUser while
      } //end $sqlGetRsvps while 
      sendResponse(200, json_encode($result));
      exit;
    } else {
      sendResponse(204, "No users have rsvp'd yet.");
      exit;
    }
  }
  
  function comments($getID, $last, $photoFlag) {
    global $link, $loggedIn, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    $getID = preg_replace('#[^0-9]#', '', $getID);
    //$oneComment = preg_replace('#[^yn]#', '', $last);
    //$photoFlag = preg_replace('#[^yn]#', '', $photoFlag);
    $result = array();
    $sqlID = 0;
    
    //either return all comments pertaining to photo (when view first appears), or just get the most recent comment (when somebody actually posts something).
    $sqlGetComments;
    if ($photoFlag == 'y') {
      if ($last == 'n') {
        $sqlGetComments = mysqli_query($link, "SELECT * FROM photo_comments WHERE photo_id='$getID' ORDER BY post_date ASC");
      } else {
        $sqlGetComments = mysqli_query($link, "SELECT * FROM photo_comments WHERE photo_id='$getID' AND user_id='$id' ORDER BY post_date DESC LIMIT 1");
      }
    } else {
      if ($last == 'n') {
        $sqlGetComments = mysqli_query($link, "SELECT * FROM location_comments WHERE location_id='$getID' ORDER BY post_date ASC");
      } else {
        $sqlGetComments = mysqli_query($link, "SELECT * FROM location_comments WHERE location_id='$getID' AND user_id='$id' ORDER BY post_date DESC LIMIT 1");
      }
    }
    
    $commentsCheck = mysqli_num_rows($sqlGetComments);
    if ($commentsCheck == 0) {
      sendResponse(204, "Hmm, There doesn't seem to be any comments posted yet. /n Be the first!");
      exit;
    } else {
      $i = 1;
      while ($row = mysqli_fetch_array($sqlGetComments)) {
        //get username
        $userID = $row['user_id'];
        $username = getUsername($userID);
          
        //get user pic
        $userPic = checkUserPic($userID);
    
        //make time format ago
        $postedDate = $row['post_date'];
        $convertedTime = convertDatetime($postedDate);
        $timeAgo = makeAgo($convertedTime);
        
        $comment = htmlkarakter($row['comment']);
        
        if ($photoFlag == 'y') {
          $sqlID = $row['photo_id'];
        } else {
          $sqlID = $row['location_id'];
        }
        
        //is user logged in? 
        $result[$i] = array(
          'getID' => $sqlID,
          'userID' => $userID,
          'username' => $username,
          'userPic' => $userPic,
          'comment' => $comment,
          'postedDate' => $timeAgo,
          'loggedIn' => $loggedIn,
        );
        $i++;
      } // end while
    } //end if/else
    
    sendResponse(200, json_encode($result));
    exit;
  }
  
  function profile($profileID = NULL) {
    global $logOptions_id;
    if (isset($profileID)) {//if visitor is requesting page
      $id = $profileID;
  	} else if (isset($_SESSION['idx'])) {//if member is seeing own page
      $id = $logOptions_id;
 	} else {
      sendResponse(401, 'Unauthorized request');
      exit;
  	}
  
  	$id = preg_replace('#[^0-9]#', '', $id);
  
  	$result = array();
  
  	$i = 1;
  	$result = getUser($id, False);
  	sendResponse(200, json_encode($result));
  	exit;
  }
  
  function events($profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    $todaysDateTime = time();
    $result = array();
    $i = 1;
    $sqlNumRsvps;
    
    if (isset($id)) {
    //select events that user created
    $sqlGetMy = mysqli_query($link, "SELECT * FROM locations WHERE user_id='$profileID' AND active='y' AND in_user_past='y' ORDER BY post_date ASC");
    $sqlNumMy = mysqli_num_rows($sqlGetMy);
    
    if ($sqlNumMy > 0) {
      while($row = mysqli_fetch_array($sqlGetMy)) {
        $eventID = $row['id'];
        $username = getUsername($profileID);
        $eventTyppe;
        $unixPostedStamp = strtotime($row['post_date']);
        //compare times to check if event already happened; databaseDateTime returns date("Y-m-d H:i:s");
        $databaseDateTime = strtotime($row['time']);
        if ($todaysDateTime < $databaseDateTime ) { $eventTyppe = 'my'; } else { $eventTyppe = 'past'; }
        //originalDB is used to let client know where data originated from, so event can be deleted accordingly
        
        //get rsvp number
        $sqlGetRsvpNumber = mysqli_query($link, "SELECT id FROM location_rsvps WHERE location_id='$eventID' AND active='y'");
        $sqlNumRsvps = mysqli_num_rows($sqlGetRsvpNumber);
        
        //get comment number and photo number, for past
        $sqlGetCommentNumber = mysqli_query($link, "SELECT id FROM location_comments WHERE location_id='$eventID'");
        $sqlNumComments = mysqli_num_rows($sqlGetCommentNumber);
        $sqlGetPhotoNumber = mysqli_query($link, "SELECT id FROM photos WHERE location_id='$eventID'");
        $sqlNumPhotos = mysqli_num_rows($sqlGetPhotoNumber);
        
        $result[$i] = array(
        'whosEvent' => $eventTyppe,
        'eventID' => $eventID,
        'originalDB' => 'my',
        'type' => $row['type'],
        'time' => $row['time'],   
        'unixTimeStamp' => $databaseDateTime,
        'street' => $row['street'],
        'city' => $row['city'],
        'state' => $row['state'],
        'country' => $row['country'],
        'age' => $row['age'],
        'admission' => $row['admission'],
        'rsvpLimit' => $row['rsvp_limit'],
        'rsvps' => $sqlNumRsvps,
        'comments' => $sqlNumComments,
        'photos' => $sqlNumPhotos,
        'title' => htmlkarakter($row['title']),
        'keyword1' => $row['keyword1'],
        'keyword2' => $row['keyword2'],
        'keyword3' => $row['keyword3'],
        'description' => $row['description'],
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude'],
        'userID' => $row['user_id'],
        'username' => $username, 
        'posted' => $unixPostedStamp,
       );
      $i++;
      }//end while
    }//end if
    
    //get rsvps if user on own profile, check if rsvps should be shown on visiting profile
    if ($profileID == $id) {
      //select events that user rsvp'd
      $sqlGetRsvp = mysqli_query($link, "SELECT location_id FROM location_rsvps WHERE user_id='$profileID' AND active='y'");
      $sqlNumRsvps = mysqli_num_rows($sqlGetRsvp);
      if ($sqlNumRsvps > 0) {
        while($row = mysqli_fetch_array($sqlGetRsvp)) {
          $locationID = $row['location_id'];

          $rsvp = getEvents('rsvp', $locationID);
          $result = array_merge($result, $rsvp);
        }//end while
      }//end if
    } else {
      $sqlRsvpShow = mysqli_query($link, "SELECT rsvp_show FROM members WHERE id='$profileID' LIMIT 1");
       while($row = mysqli_fetch_array($sqlRsvpShow)) { $rsvpShow = $row['rsvp_show']; }
       if ($rsvpShow == 'y') {
         //select events that user rsvp'd
         $sqlGetRsvp = mysqli_query($link, "SELECT location_id FROM location_rsvps WHERE user_id='$profileID' AND active='y'");
         $sqlNumRsvps = mysqli_num_rows($sqlGetRsvp);
         if ($sqlNumRsvps > 0) {
           while($row = mysqli_fetch_array($sqlGetRsvp)) {
             $locationID = $row['location_id'];
        
             $rsvp = getEvents('rsvp', $locationID);
             $result = array_merge($result, $rsvp);
           }//end while
         }//end if
       } else {
         $sqlNumRsvps = 0;
       }
    }
    
    //select events user favorited
    $sqlGetFav = mysqli_query($link, "SELECT location_id FROM location_favs WHERE user_id='$profileID' AND active='y'");
    $sqlNumFavs = mysqli_num_rows($sqlGetFav);
    if ($sqlNumFavs > 0) {
      while($row = mysqli_fetch_array($sqlGetFav)) {
        $locationID = $row['location_id'];
        
        $fav = getEvents('fav', $locationID);
        $result = array_merge($result, $fav);
      }//end while
    }//end if
     
    if ($sqlNumMy != 0 || $sqlNumRsvps != 0 || $sqlNumFavs != 0) {
      sendResponse(200, json_encode($result));
      exit;
    } else {
      sendResponse(204, "This member has no events");
      exit;
    }
    } else {//user not logged in
      sendResponse(401, 'You need to be logged in to view this.');
      exit;
    }
  }
  
  function photos($getID, $rangeStart, $eventFlag) {
    global $link;
    $getID = preg_replace('#[^0-9]#', '', $getID);
    $rangeStart = preg_replace('#[^0-9]#', '', $rangeStart);
    
    $result = array();
    
    if ($eventFlag == 'y') {//for event multiphotoviewer
      $sqlPhotosGet = mysqli_query($link, "SELECT * FROM photos WHERE location_id='$getID' LIMIT $rangeStart, 16") or die (mysqli_error($link));
    } else {
      $sqlPhotosGet = mysqli_query($link, "SELECT * FROM photos WHERE user_id='$getID' LIMIT $rangeStart, 16") or die (mysqli_error($link));
    }
    $photosCheck = mysqli_num_rows($sqlPhotosGet);
      if ($photosCheck == 0) {
        sendResponse(204, "Hmmm, There doesn't seem to be any photos posted yet. /n Be the first!");
        exit;
      } else {
        $i = 1;
        while($row = mysqli_fetch_array($sqlPhotosGet)) {
          if ($eventFlag == 'y') {
            $targetPath = "events/$getID";
          } else {
            $eventID = $row['location_id'];
            $targetPath = "events/$eventID";
          }
          $thumbFilePath = $targetPath . '/thumb_' . $row['photo_name'];
          $result[$i] = array(
            'photoID' => $row['id'],
            'thumb' => $thumbFilePath,
          );
          $i++;
        } //end while
      } //end else
      
      sendResponse(200, json_encode($result));
      exit;
  }
  
  function detailedPhoto($photoID) {
    global $link, $logOptions_id, $loggedIn;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } 
    $photoID = preg_replace('#[^0-9]#', '', $photoID);
    $result = array();
    //used to identify anonymous people who are not logged in, for photo views
    $ipaddress = getenv('REMOTE_ADDR');
     
    $sqlPhotoGet = mysqli_query($link, "SELECT * FROM photos WHERE id='$photoID' LIMIT 1") or die (mysqli_error($link));
    $photoCheck = mysqli_num_rows($sqlPhotoGet);
    if ($photoCheck == 0) {
       sendResponse(204, "Hmmm, no photo here");
       exit;
    } else {
       while($row = mysqli_fetch_array($sqlPhotoGet)) {
         $eventID = $row['location_id'];
         $targetPath = 'events/' . $eventID . '/resized_' . $row['photo_name'];
         
         //get username
          $userID = $row['user_id'];
          $username = getUsername($userID);
          
          //get event title
          $sqlEvent = mysqli_query($link, "SELECT title, type FROM locations WHERE id='$eventID' LIMIT 1");
          while ($row3 = mysqli_fetch_array($sqlEvent)) { $eventTitle = $row3["title"]; $eventType = $row3['type']; }
          
          //get comment number
          $photoID = $row['id'];
          $sqlNumComments = mysqli_query($link, "SELECT id FROM photo_comments WHERE photo_id='$photoID'");
          $commentNumber = mysqli_num_rows($sqlNumComments);
          
          //is user logged in?; for commenting and voting client side 
          $result[1] = array(
           'photoID' => $row['id'],
           'resized' => $targetPath,
           'eventID' => $eventID,
           'eventTitle' => $eventTitle,
           'eventType' => $eventType,
           'userID' => $userID,
           'username' => $username,
           'score' => $row['score'],
           'postDate' => strtotime($row['post_date']),
           'comments' => $commentNumber,
           'loggedIn' => $loggedIn,
          );
        }//end while
     }//end else
     
     
     if (isset($id)) {
       $sqlPhotoView = mysqli_query($link, "SELECT * FROM photo_views WHERE photo_id='$photoID' AND user_id='$id' LIMIT 1");
       $sqlViewRows = mysqli_num_rows($sqlPhotoView);
       
       if ($sqlViewRows == 0) {
         $sqlInsertView = mysqli_query($link, "INSERT INTO photo_views (photo_id, user_id, ipaddress, post_date) VALUES ('$photoID', '$id', '$ipaddress', now())");
       }
     } else {
       $userID = 00;
       $sqlPhotoView = mysqli_query($link, "SELECT * FROM photo_views WHERE ipaddress='$ipaddress' LIMIT 1");
       $sqlViewRows = mysqli_num_rows($sqlPhotoView);
       
       if ($sqlViewRows == 0) {
         $sqlInsertView = mysqli_query($link, "INSERT INTO photo_views (photo_id, user_id, ipaddress, post_date) VALUES ('$photoID', '$userID', '$ipaddress', now())");
       }
     }
     sendResponse(200, json_encode($result));
     exit;
  }
  
  function followers($profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      //sendResponse(204, "Only members can see this.");
      sendResponse(401, 'Only members can see this.');
      exit;
    }
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    $result = array();
    $i = 1;
    
    
    //get followers
    $sqlGetFollowers = mysqli_query($link, "SELECT following_id FROM follow WHERE follower_id='$profileID' AND active='y'");
    $followerCount = mysqli_num_rows($sqlGetFollowers);
    
    //get who user is following
    $sqlGetFollowing = mysqli_query($link, "SELECT follower_id FROM follow WHERE following_id='$profileID' AND active='y'");
    $followingCount = mysqli_num_rows($sqlGetFollowing);
    
    if ($followerCount != 0) {
      while ($row = mysqli_fetch_array($sqlGetFollowers)) {
        $followerID = $row['following_id'];
        $userPic = checkUserPic($followerID);
      
        $username = getUsername($followerID);
      
        $result[$i] = array(
          'whosFollowing' => 'follower',
          'whosRequesting' => $id,
          'id' => $followerID,
          'username' => $username,
          'userPic' => $userPic,
        );
        $i++;
      }//end while
    }//end if 
    
    if ($followingCount != 0) {
      while ($row = mysqli_fetch_array($sqlGetFollowing)) {
        $followingID = $row['follower_id'];
        $userPic = checkUserPic($followingID);
      
        $username = getUsername($followingID);
      
        $result[$i] = array(
          'whosFollowing' => 'following',
          'whosRequesting' => $id,
          'id' => $followingID,
          'username' => $username,
          'userPic' => $userPic,
        );
        $i++;
      }//end while
    }//end if
  
    //if nobody's following anybody, still have to send whosResquesting to populate client UINavBar
    if ($followerCount == 0 && $followingCount == 0) {
      $result[$i] = array(
            'whosFollowing' => 'none',
            'whosRequesting' => $id,
            'id' => 'none',
            'username' => 'none',
            'userPic' => 'none',
      );
    }
    
    sendResponse(200, json_encode($result));
    exit;
  }
  
  function messages($profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in message.');
      exit;
    }
    
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    $result = array();
    $i = 1;
    
    if ($profileID != $id) { //visiting user 
      $username = getUsername($profileID);
      sendResponse(203, $username);//used to populate client, with response code
      exit;
    } else {
      $sqlRecMessages = mysqli_query($link, "SELECT * FROM private_messages WHERE to_id='$id' AND recipient_delete='n' ORDER BY post_date DESC");
      $receivedCount = mysqli_num_rows($sqlRecMessages);
      
      $sqlSentMessages = mysqli_query($link, "SELECT * FROM private_messages WHERE from_id='$id' AND sender_delete='n' ORDER BY post_date DESC");
      $sentCount = mysqli_num_rows($sqlSentMessages);
      
      if ($receivedCount != 0) {
        while ($row = mysqli_fetch_array($sqlRecMessages)) {
        
          //get user name
          $fromID = $row['from_id'];
          $username = getUsername($fromID);
          
          //get user pic
          $userPic = checkUserPic($fromID);
          
          //get unix timestamp
          $databaseDateTime = strtotime($row['post_date']);
          
          $subject = htmlkarakter($row['subject']);
          $message = htmlkarakter($row['message']);
          
          $result[$i] = array(
            'whichMessage' =>'received',
            'messageID' => $row['id'],
            'userID' => $fromID,
            'username' => $username,
            'userPic' => $userPic,
            'unixTimeStamp' => $databaseDateTime,
            'subject' => $subject,
            'message' => $message,
            'opened' => $row['recipient_opened'],
          );
          $i++;
        }//end while
      
      }//end if
      
      if ($sentCount != 0) {
        while ($row = mysqli_fetch_array($sqlSentMessages)) {
        
          //get user name
          $toID = $row['to_id'];
          $username = getUsername($toID);
          
          //get user pic
          $userPic = checkUserPic($toID);
          
          //get unix timestamp
          $databaseDateTime = strtotime($row['post_date']);
          
          $subject = htmlkarakter($row['subject']);
          $message = htmlkarakter($row['message']);
          
          $result[$i] = array(
            'whichMessage' =>'sent',
            'messageID' => $row['id'],
            'userID' => $toID,
            'username' => $username,
            'userPic' => $userPic,
            'unixTimeStamp' => $databaseDateTime,
            'subject' => $subject,
            'message' => $message,
            'opened' => $row['sender_opened'],
          );
          $i++;
        }//end while
      }//end if
      
      if ($receivedCount == 0 && $sentCount == 0) {
        sendResponse(204, "No Content");
        exit;
      }//end if
      
      sendResponse(200, json_encode($result));
      exit;
    }//end if/else
  }
  
  function editProfile($profileID) {
    global $link, $logOptions_id;
    if (isset($_SESSION['idx'])) {
      $id = $logOptions_id;
    } else {
      sendResponse(401, 'You need to be logged in message.');
      exit;
    }
    
    $profileID = preg_replace('#[^0-9]#', '', $profileID);
    $result = array();
    
    if ($profileID != $id) {
      sendResponse(404, 'Unauthorized Request');
      exit;
    } else {
      $sqlGetInfo = mysqli_query($link, "SELECT * FROM members WHERE id='$id' LIMIT 1");
      while ($row = mysqli_fetch_array($sqlGetInfo)) {
        $userID = $row['id'];
        $userPic = checkUserPic($userID);
        $firstName = htmlkarakter($row['first_name']);
        $lastName = htmlkarakter($row['last_name']);
        $website = htmlkarakter($row['website']);
        $city = htmlkarakter($row['city']);
        $state = htmlkarakter($row['state']);
        $bioBody = htmlkarakter($row['bio_body']);
        $result[1] = array(
          'firstName' => $firstName,
          'lastName'  => $lastName,
          'website'	  => $website,
          'city'      => $city,
          'state'     => $state,
          'country'   => $row['country'],
          'bioBody'   => $bioBody,
          'rsvpShow'  => $row['rsvp_show'],
          'userPic'   => $userPic,
         );
      }//end while
      
      sendResponse(200, json_encode($result));
      exit;
    } //end if/else
  }
?>