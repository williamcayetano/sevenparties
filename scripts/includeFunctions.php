<?php
  include_once("Ranking.php");
  include_once("ak_php_img_lib_2.0.php");
  
function clean($str) {
    global $link;
    $str = preg_replace("#['`]#", "&#039;", $str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str);
    return mysqli_real_escape_string($link, $str);
}

function getUsername($userID) {
    global $link;
    $username = "";
    $firstName = "";
    $lastName = "";
   	$sqlGetUserName = mysqli_query($link, "SELECT username, first_name, last_name FROM members WHERE id='$userID' LIMIT 1");
    while ($row = mysqli_fetch_array($sqlGetUserName)) {
      $username = $row['username'];
      $firstName = htmlkarakter($row['first_name']);
      $lastName = htmlkarakter($row['last_name']);
    }
    
      //check if first, last name replaced
    if ($firstName != "" && $lastName != "") {
      $completeName = $firstName . ' ' . $lastName;
    } else {
      $completeName = $username;
    }
    return $completeName;
}

function htmlkarakter($string) { 
   $string = str_replace(array("&lt;", "&gt;", '&amp;', '&#039;', '&quot;','&lt;', '&gt;'), array("<", ">",'&','\'','"','<','>'), htmlspecialchars_decode($string, ENT_NOQUOTES)); 
   return $string; 
}

function featuredPopular($city, $state, $country,  $gt) {
    global $link;
    $rankingArray = array();
    $sqlGet = '';
    $rank = new Ranking;
    
    if ($city != '') {
      //$sqlGet = mysqli_query($link, "SELECT * FROM locations WHERE city='$city' AND state='$state' AND NOW() $gt time AND active='y' ORDER by time desc LIMIT 0, 100");
      $sqlGet = mysqli_query($link, "SELECT * FROM locations WHERE city='$city' AND state='$state' AND NOW() $gt time AND active='y' ORDER by time desc");
    } else if ($state != '') {
      //$sqlGet = mysqli_query($link, "SELECT * FROM locations WHERE state='$state' AND NOW() $gt time AND '$city' <> city AND active='y' ORDER BY time desc LIMIT 20");
      $sqlGet = mysqli_query($link, "SELECT * FROM locations WHERE state='$state' AND NOW() $gt time AND '$city' <> city AND active='y' ORDER BY time desc");
    } else {
      //$sqlGet = mysqli_query($link, "SELECT * FROM locations WHERE country='$country' AND NOW() $gt time AND '$state' <> state AND active='y' ORDER BY time desc LIMIT 20");
      $sqlGet = mysqli_query($link, "SELECT * FROM locations WHERE country='$country' AND NOW() $gt time AND '$state' <> state AND active='y' ORDER BY time desc");
    }
    
    $sqlNum = mysqli_num_rows($sqlGet);
    
    if ($sqlNum > 0) {
      while ($row = mysqli_fetch_array($sqlGet)) {
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
        //if title exists, use it; otherwise just use type
        $title = $row['title'] ? htmlkarakter($row['title']) : $type;
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
      }//end while $sqlGet
    }//end if $sqlNum
    //finish this
    return $rankingArray;
}

function cropPhoto($targetPath, $fileName, $eventID, $id, $fileExt) {
    global $link;
    
	$target_file = "$targetPath/$fileName"; 
	$resized_file = "$targetPath/resized_$fileName"; 
	$wmax = 425;
	$hmax = 425;
	@ak_img_resize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	
	$target_file = "$targetPath/$fileName"; 
	$resized_file = "$targetPath/maxsized_$fileName"; 
	$wmax = 800;
	$hmax = 800;
	@ak_img_maxsize($target_file, $resized_file, $wmax, $hmax, $fileExt);
	  
	$target_file = "$targetPath/resized_$fileName";
	$thumbnail = "$targetPath/thumb_$fileName";
	$wthumb = 110;
	$hthumb = 110;
	@ak_img_thumb($target_file, $thumbnail, $wthumb, $hthumb, $fileExt);
    
    $sqlPhotoPost = mysqli_query($link, "INSERT INTO photos (photo_name, location_id, user_id, post_date) VALUES ('$fileName', '$eventID', '$id', now())") or die (mysqli_error($link)); 
    $errorMsg = 'Thanks for posting!';
    return $errorMsg;
}

function getPhotos($getID, $type) {
  global $link;
  if ($type == 'event') {
       $sqlPhotosGet = mysqli_query($link, "SELECT * FROM photos WHERE location_id='$getID' AND active='y' ORDER BY id ASC") or die (mysqli_error($link));
    } else {
      $sqlPhotosGet = mysqli_query($link, "SELECT * FROM photos WHERE user_id='$getID' AND active='y' ORDER BY id ASC") or die (mysqli_error($link));
    }
    
    $photoRows = mysqli_num_rows($sqlPhotosGet);
    
    //rank photos by hotness
    if ($photoRows > 0) {
      $rankingArray = array();
      while ($row = mysqli_fetch_array($sqlPhotosGet)) {
        $photoID = $row['id'];
        $eventID = $row['location_id'];
        $name = $row['photo_name'];
        $score = $row['score'];
        $unixPostedStamp = strtotime($row['post_date']);
        
        //get the number of times someone has commented, viewed or voted on photo to guage hotness
        $sqlGetComm = mysqli_query($link, "SELECT * FROM photo_comments WHERE photo_id='$photoID'"); 
        $sqlGetViews = mysqli_query($link, "SELECT * FROM photo_views WHERE photo_id='$photoID'");
        $sqlGetVotes = mysqli_query($link, "SELECT * FROM photo_votes WHERE photo_id='$photoID'");
        $sqlNumComm = mysqli_num_rows($sqlGetComm);
        $sqlNumViews = mysqli_num_rows($sqlGetViews);
        $sqlNumVotes = mysqli_num_rows($sqlGetVotes);
        //rank photos
        $total = $score + $sqlNumComm + $sqlNumViews + $sqlNumVotes;
        $rank = new Ranking;
        $picRanking = $rank -> hotness($total, 0, $unixPostedStamp);
        
        $rankingArray[] = $picRanking . '`' . $unixPostedStamp . '`' . $name . '`' . $eventID . '`' . $photoID; 
        
      }//end while mysqli_fetch_array
      
      //now sort by rank
      arsort($rankingArray);
  	  $index = 0;
  	  
  	  //now format pictures into a table
  	  $photosDisplay = '<table class="thumbs">
                		  <tr>
                 		  <td class="thumbs' . $index . '">';
  	  
  	  foreach ($rankingArray as $key => $value) {
  	    $kaboom = explode("`", $rankingArray[$key]);
  	    $photoName = $kaboom[2];
  	    $eventID = $kaboom[3];
  	    $photoID = $kaboom[4];
  	    
  	    $photosDisplay .= '<a href="photo.php?id=' . $photoID . '"><img src="events/' . $eventID . '/thumb_' . $photoName . '"/></a>';
        $index++;
        if ($index % 8 == 0) 
          $photosDisplay .= '</td></tr><tr><td class="thumbs' . $index / 8 . '">'; 
  	    
  	  }//end foreach
  	  
       $photosDisplay .= '</td></tr></table><br />';
       $index = 0;
    }//end if ($photoRows > 0)
    return isset($photosDisplay) ? $photosDisplay : '';
}

function convertDatetime($str) {
  list($date, $time) = explode(' ', $str);
  list($year, $month, $day) = explode('-', $date);
  list($hour, $minute, $second) = explode(':', $time);
  $timeStamp = mktime($hour, $minute, $second, $month, $day, $year);
  return $timeStamp;
}
    
function makeAgo($timeStamp) {
  $difference = time() - $timeStamp;
  $periods = array("sec", "min", "hr", "day", "week", "month", "year", "decade");
  $lengths = array("60","60","24","7","4.35","12","10");
  for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++)
   	$difference /= $lengths[$j];
   	$difference = round($difference);
  if($difference != 1) $periods[$j].= "s";
   	$text = "$difference $periods[$j] ago";
   	return $text;
}

function imgSet($id, $star) {
  $img = '<img id="star_' . $id . '" src="images/' . $star . '_star.png" height="20" width="20">';
  return $img;
}

function scoreDiv($score) {
  $starDiv;
  if ($score == 5) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'full')
      		 . '&nbsp;' . imgSet(3, 'full') . '&nbsp;' . imgSet(4, 'full') . '</div>';
  } else if ($score > 4 && $score < 5) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'full') 
             . '&nbsp;' . imgSet(3, 'full') . '&nbsp;' . imgSet(4, 'half') . '</div>';
  } else if ($score == 4) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'full') 
             . '&nbsp;' . imgSet(3, 'full') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else if ($score > 3 && $score < 4) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'full') 
             . '&nbsp;' . imgSet(3, 'half') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else if ($score == 3) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'full') 
             . '&nbsp;' . imgSet(3, 'empty') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else if ($score > 2 && $score < 3) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'half') 
             . '&nbsp;' . imgSet(3, 'empty') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else if ($score == 2) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'full') . '&nbsp' . imgSet(2, 'empty')
             . '&nbsp;' . imgSet(3, 'empty') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else if ($score > 1 && $score < 2) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'half') . '&nbsp' . imgSet(2, 'empty') 
             . '&nbsp;' . imgSet(3, 'empty') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else if ($score == 1) {
    $starDiv = '<div id="stars">' . imgSet(0, 'full') . '&nbsp;' . imgSet(1, 'empty') . '&nbsp' . imgSet(2, 'empty') 
             . '&nbsp;' . imgSet(3, 'empty') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  } else {
    $starDiv = '<div id="stars">' . imgSet(0, 'empty') . '&nbsp;' . imgSet(1, 'empty') . '&nbsp' . imgSet(2, 'empty') 
             . '&nbsp;' . imgSet(3, 'empty') . '&nbsp;' . imgSet(4, 'empty') . '</div>';
  }
  return $starDiv;
}

function checkUserPic($userID) {
  $checkPic = "members/$userID/image01.jpg";
  $defaultPic = "members/0/image01.png";
  if (file_exists($checkPic)) {
	$userPic = $checkPic;
  } else {
	$userPic = $defaultPic;
  }
  return $userPic;
}

function dropDown($options_array, $selected = null) 
  { 
    $return = '<option value="'.$selected.'">'.$selected.'</option>'."\n"; 
      foreach($options_array as $option) 
      { 
        if ($option != $selected) {
          $return .= '<option value="'.$option.'">'.$option.'</option>'."\n";
        }
      } 
      return $return; 
  }
?>